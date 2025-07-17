<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FileUploadSetting;
use App\Models\Pitch;
use App\Models\Project;
use App\Services\FileManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PresignedUploadController extends Controller
{
    protected FileManagementService $fileManagementService;

    public function __construct(FileManagementService $fileManagementService)
    {
        $this->fileManagementService = $fileManagementService;
    }

    /**
     * Generate a presigned URL for file upload
     */
    public function generatePresignedUrl(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'context' => 'required|string|in:'.implode(',', FileUploadSetting::getValidContexts()),
                'filename' => 'required|string|max:255',
                'mime_type' => 'required|string|max:100',
                'file_size' => 'required|integer|min:1|max:'.(1024 * 1024 * 1024), // Max 1GB
                'metadata' => 'sometimes|array',
                'metadata.project_id' => 'sometimes|integer|exists:projects,id',
                'metadata.pitch_id' => 'sometimes|integer|exists:pitches,id',
            ]);

            $context = $validated['context'];
            $fileName = $validated['filename'];
            $mimeType = $validated['mime_type'];
            $fileSize = $validated['file_size'];
            $metadata = $validated['metadata'] ?? [];

            // Validate file type
            if (! $this->isFileTypeAllowed($mimeType)) {
                throw ValidationException::withMessages([
                    'mime_type' => 'File type not allowed. Supported types: audio/*, application/pdf, image/*, application/zip',
                ]);
            }

            // Check authorization based on context
            $this->validateAuthorization($request, $context, $metadata);

            // Generate presigned URL
            $result = $this->fileManagementService->generatePresignedUploadUrl(
                $context,
                $fileName,
                $mimeType,
                $fileSize,
                $metadata,
                $request->user()
            );

            // Add context information for frontend
            $result['context'] = $context;
            $result['filename'] = $fileName;
            $result['file_size'] = $fileSize;
            $result['mime_type'] = $mimeType;
            $result['metadata'] = $metadata;

            Log::info('Presigned URL generated successfully', [
                'context' => $context,
                'filename' => $fileName,
                'user_id' => $request->user()?->id,
                's3_key' => $result['s3_key'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to generate presigned URL', [
                'error' => $e->getMessage(),
                'request_data' => $request->except(['metadata']),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate presigned URL',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Complete the upload process by creating file records
     */
    public function completeUpload(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'context' => 'required|string|in:'.implode(',', FileUploadSetting::getValidContexts()),
                's3_key' => 'required|string|max:500',
                'filename' => 'required|string|max:255',
                'mime_type' => 'required|string|max:100',
                'file_size' => 'required|integer|min:1',
                'metadata' => 'sometimes|array',
                'metadata.project_id' => 'sometimes|integer|exists:projects,id',
                'metadata.pitch_id' => 'sometimes|integer|exists:pitches,id',
            ]);

            $context = $validated['context'];
            $s3Key = $validated['s3_key'];
            $fileName = $validated['filename'];
            $mimeType = $validated['mime_type'];
            $fileSize = $validated['file_size'];
            $metadata = $validated['metadata'] ?? [];

            // Verify the file was actually uploaded to S3
            if (! $this->verifyFileExists($s3Key)) {
                return response()->json([
                    'success' => false,
                    'error' => 'File not found in storage',
                    'message' => 'The uploaded file could not be verified in storage.',
                ], 404);
            }

            // Check authorization again
            $this->validateAuthorization($request, $context, $metadata);

            // Create file record based on context
            $fileRecord = $this->createFileRecord($context, $s3Key, $fileName, $mimeType, $fileSize, $metadata, $request->user());

            Log::info('Upload completed successfully', [
                'context' => $context,
                'filename' => $fileName,
                'file_record_id' => $fileRecord->id,
                'user_id' => $request->user()?->id,
                's3_key' => $s3Key,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'file_id' => $fileRecord->id,
                    'filename' => $fileRecord->file_name,
                    'size' => $fileRecord->size,
                    'mime_type' => $fileRecord->mime_type,
                    'context' => $context,
                    'created_at' => $fileRecord->created_at->toISOString(),
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to complete upload', [
                'error' => $e->getMessage(),
                'request_data' => $request->except(['metadata']),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to complete upload',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Validate authorization for upload based on context
     */
    protected function validateAuthorization(Request $request, string $context, array $metadata): void
    {
        $user = $request->user();

        // For client portal uploads, we might use signed URLs instead of user auth
        if (! $user && $context === FileUploadSetting::CONTEXT_CLIENT_PORTALS) {
            // This would be handled by signed URL middleware in routes
            return;
        }

        if (! $user) {
            throw new \Exception('Authentication required for this upload context.');
        }

        // Check project-specific authorization
        if ($context === FileUploadSetting::CONTEXT_PROJECTS && isset($metadata['project_id'])) {
            $project = Project::find($metadata['project_id']);
            if (! $project || ! Gate::forUser($user)->allows('uploadFile', $project)) {
                throw new \Exception('Upload not allowed for this project.');
            }
        }

        // Check pitch-specific authorization
        if ($context === FileUploadSetting::CONTEXT_PITCHES && isset($metadata['pitch_id'])) {
            $pitch = Pitch::find($metadata['pitch_id']);
            if (! $pitch || ! Gate::forUser($user)->allows('uploadFile', $pitch)) {
                throw new \Exception('Upload not allowed for this pitch.');
            }
        }
    }

    /**
     * Create file record based on context
     */
    protected function createFileRecord(string $context, string $s3Key, string $fileName, string $mimeType, int $fileSize, array $metadata, $uploader)
    {
        switch ($context) {
            case FileUploadSetting::CONTEXT_PROJECTS:
                if (! isset($metadata['project_id'])) {
                    throw new \Exception('Project ID required for project file uploads.');
                }
                $project = Project::findOrFail($metadata['project_id']);

                return $this->fileManagementService->createProjectFileFromS3(
                    $project, $s3Key, $fileName, $fileSize, $mimeType, $uploader, $metadata
                );

            case FileUploadSetting::CONTEXT_PITCHES:
                if (! isset($metadata['pitch_id'])) {
                    throw new \Exception('Pitch ID required for pitch file uploads.');
                }
                $pitch = Pitch::findOrFail($metadata['pitch_id']);

                return $this->fileManagementService->createPitchFileFromS3(
                    $pitch, $s3Key, $fileName, $fileSize, $mimeType, $uploader, $metadata
                );

            case FileUploadSetting::CONTEXT_CLIENT_PORTALS:
                if (! isset($metadata['project_id'])) {
                    throw new \Exception('Project ID required for client portal uploads.');
                }
                $project = Project::findOrFail($metadata['project_id']);

                return $this->fileManagementService->createProjectFileFromS3(
                    $project, $s3Key, $fileName, $fileSize, $mimeType, null, array_merge($metadata, ['client_upload' => true])
                );

            default:
                throw new \Exception("Unsupported upload context: {$context}");
        }
    }

    /**
     * Verify that the file actually exists in S3 storage
     */
    protected function verifyFileExists(string $s3Key): bool
    {
        try {
            return \Storage::disk('s3')->exists($s3Key);
        } catch (\Exception $e) {
            Log::error('Failed to verify file existence in S3', [
                's3_key' => $s3Key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if a file type is allowed
     */
    protected function isFileTypeAllowed(string $mimeType): bool
    {
        $allowedTypes = ['audio/*', 'application/pdf', 'image/*', 'application/zip'];

        foreach ($allowedTypes as $allowedType) {
            if (str_ends_with($allowedType, '/*')) {
                $prefix = substr($allowedType, 0, -2);
                if (str_starts_with($mimeType, $prefix)) {
                    return true;
                }
            } elseif ($mimeType === $allowedType) {
                return true;
            }
        }

        return false;
    }
}

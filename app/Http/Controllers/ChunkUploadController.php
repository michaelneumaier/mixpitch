<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ChunkProcessingService;
use App\Services\FileManagementService;
use App\Models\UploadSession;
use App\Models\UploadChunk;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\FileUploadSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Exceptions\FileUploadException;
use Illuminate\Support\Str;

class ChunkUploadController extends Controller
{
    protected ChunkProcessingService $chunkProcessingService;
    protected FileManagementService $fileManagementService;

    public function __construct(
        ChunkProcessingService $chunkProcessingService,
        FileManagementService $fileManagementService
    ) {
        $this->middleware('auth:sanctum');
        $this->chunkProcessingService = $chunkProcessingService;
        $this->fileManagementService = $fileManagementService;
    }

    /**
     * Get upload validation rules based on context
     */
    protected function getUploadValidationRules(string $context = FileUploadSetting::CONTEXT_GLOBAL): array
    {
        $settings = FileUploadSetting::getSettings($context);
        $maxFileSizeKB = $settings[FileUploadSetting::MAX_FILE_SIZE_MB] * 1024; // Convert MB to KB for Laravel validation
        $maxChunkSizeKB = $settings[FileUploadSetting::CHUNK_SIZE_MB] * 1024; // Convert MB to KB for Laravel validation
        
        return [
            'file_rules' => "required|file|max:{$maxFileSizeKB}",
            'chunk_rules' => "required|file|max:{$maxChunkSizeKB}",
            'total_size_max' => $settings[FileUploadSetting::MAX_FILE_SIZE_MB] * 1024 * 1024, // bytes
            'chunk_size_max' => $settings[FileUploadSetting::CHUNK_SIZE_MB] * 1024 * 1024, // bytes
            'settings' => $settings
        ];
    }

    /**
     * Determine context from model type
     */
    protected function getContextFromModelType(string $modelType): string
    {
        return match($modelType) {
            'projects' => FileUploadSetting::CONTEXT_PROJECTS,
            'pitches' => FileUploadSetting::CONTEXT_PITCHES,
            default => FileUploadSetting::CONTEXT_GLOBAL
        };
    }

    /**
     * Create a new upload session
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createUploadSession(Request $request): JsonResponse
    {
        try {
            // Get context and validation rules
            $modelType = $request->input('model_type', 'global');
            $context = $this->getContextFromModelType($modelType);
            $validationRules = $this->getUploadValidationRules($context);
            
            // Validate the request
            $validator = Validator::make($request->all(), [
                'original_filename' => 'required|string|max:255',
                'total_size' => 'required|integer|min:1|max:' . $validationRules['total_size_max'],
                'total_chunks' => 'required|integer|min:1',
                'chunk_size' => 'required|integer|min:1024|max:' . $validationRules['chunk_size_max'],
                'model_type' => 'required|string|in:projects,pitches,global',
                'model_id' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $modelType = $request->input('model_type');
            $modelId = $request->input('model_id');

            // Validate model if specified
            $model = null;
            if ($modelId && $modelType !== 'global') {
                if ($modelType === 'projects') {
                    $model = Project::find($modelId);
                    if (!$model || $model->user_id !== $user->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Project not found or access denied'
                        ], 404);
                    }
                } elseif ($modelType === 'pitches') {
                    $model = Pitch::find($modelId);
                    if (!$model || $model->user_id !== $user->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Pitch not found or access denied'
                        ], 404);
                    }
                }
            }

            // Create upload session
            $uploadSession = UploadSession::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model ? $model->id : null,
                'original_filename' => $request->input('original_filename'),
                'total_size' => $request->input('total_size'),
                'chunk_size' => $request->input('chunk_size'),
                'total_chunks' => $request->input('total_chunks'),
                'uploaded_chunks' => 0,
                'status' => UploadSession::STATUS_PENDING,
                'metadata' => [
                    'context' => $modelType,
                    'created_via' => 'enhanced_uploader'
                ],
                'expires_at' => now()->addHours(24), // 24 hour expiration
            ]);

            Log::info('Upload session created', [
                'upload_session_id' => $uploadSession->id,
                'user_id' => $user->id,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'filename' => $request->input('original_filename'),
                'total_size' => $request->input('total_size'),
                'total_chunks' => $request->input('total_chunks')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Upload session created successfully',
                'data' => [
                    'id' => $uploadSession->id,
                    'expires_at' => $uploadSession->expires_at->toISOString(),
                    'chunk_size' => $uploadSession->chunk_size,
                    'total_chunks' => $uploadSession->total_chunks,
                    'status' => $uploadSession->status
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error creating upload session', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating upload session'
            ], 500);
        }
    }

    /**
     * Handle simple (non-chunked) file upload
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function simpleUpload(Request $request): JsonResponse
    {
        try {
            // Get context and validation rules
            $modelType = $request->input('model_type', 'global');
            $context = $this->getContextFromModelType($modelType);
            $validationRules = $this->getUploadValidationRules($context);
            
            // Validate the request
            $validator = Validator::make($request->all(), [
                'file' => $validationRules['file_rules'],
                'model_type' => 'required|string|in:projects,pitches,global',
                'model_id' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $file = $request->file('file');
            $modelType = $request->input('model_type');
            $modelId = $request->input('model_id');

            // Validate model if specified
            $model = null;
            if ($modelId && $modelType !== 'global') {
                if ($modelType === 'projects') {
                    $model = Project::find($modelId);
                    if (!$model || $model->user_id !== $user->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Project not found or access denied'
                        ], 404);
                    }
                } elseif ($modelType === 'pitches') {
                    $model = Pitch::find($modelId);
                    if (!$model || $model->user_id !== $user->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Pitch not found or access denied'
                        ], 404);
                    }
                }
            }

            // Use existing FileManagementService for simple uploads
            if ($model instanceof Project) {
                $fileRecord = $this->fileManagementService->uploadProjectFile($model, $file, $user);
            } elseif ($model instanceof Pitch) {
                $fileRecord = $this->fileManagementService->uploadPitchFile($model, $file, $user);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Simple upload requires a valid model (project or pitch)'
                ], 400);
            }

            Log::info('Simple file upload completed', [
                'user_id' => $user->id,
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'file_record_id' => $fileRecord->id,
                'filename' => $file->getClientOriginalName(),
                'file_size' => $file->getSize()
            ]);

            // For FilePond compatibility, check if this is a FilePond request
            if ($request->hasHeader('Accept') && str_contains($request->header('Accept'), 'application/json')) {
                return response()->json([
                    'success' => true,
                    'message' => 'File uploaded successfully',
                    'data' => [
                        'file_id' => $fileRecord->id,
                        'file_type' => get_class($fileRecord),
                        'original_filename' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize()
                    ]
                ]);
            } else {
                // FilePond expects just the file ID as plain text response
                return response($fileRecord->id)->header('Content-Type', 'text/plain');
            }

        } catch (FileUploadException $e) {
            Log::error('File upload exception during simple upload', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Unexpected error during simple upload', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred during file upload'
            ], 500);
        }
    }

    /**
     * Upload an individual chunk
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadChunk(Request $request): JsonResponse
    {
        try {
            // First, get the upload session to determine context
            $uploadSessionId = $request->input('upload_session_id');
            $uploadSession = UploadSession::find($uploadSessionId);
            
            if (!$uploadSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload session not found'
                ], 404);
            }

            // Get context-based validation rules
            $context = $this->getContextFromModelType($uploadSession->model_type);
            $validationRules = $this->getUploadValidationRules($context);
            
            // Validate the request
            $validator = Validator::make($request->all(), [
                'upload_session_id' => 'required|string|exists:upload_sessions,id',
                'chunk_index' => 'required|integer|min:0',
                'chunk' => $validationRules['chunk_rules'],
                'chunk_hash' => 'nullable|string|size:64', // SHA256 hash is 64 characters
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $chunkIndex = $request->input('chunk_index');
            $chunkHash = $request->input('chunk_hash');
            $chunkFile = $request->file('chunk');

            // Check authorization - user must own the upload session
            if ($uploadSession->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to upload session'
                ], 403);
            }

            // Check if session is expired
            if ($uploadSession->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload session has expired'
                ], 410);
            }

            // Check if session is in valid state for chunk uploads
            if (!in_array($uploadSession->status, [UploadSession::STATUS_PENDING, UploadSession::STATUS_UPLOADING])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload session is not in a valid state for chunk uploads',
                    'current_status' => $uploadSession->status
                ], 409);
            }

            // Transition to uploading status if still pending
            if ($uploadSession->status === UploadSession::STATUS_PENDING) {
                $uploadSession->transitionTo(UploadSession::STATUS_UPLOADING);
            }

            // Store the chunk
            $storagePath = $this->chunkProcessingService->storeChunk(
                $chunkFile,
                $uploadSessionId,
                $chunkIndex,
                $chunkHash
            );

            // Get updated progress
            $progress = $this->chunkProcessingService->getUploadProgress($uploadSessionId);

            Log::info('Chunk uploaded successfully', [
                'upload_session_id' => $uploadSessionId,
                'chunk_index' => $chunkIndex,
                'user_id' => Auth::id(),
                'progress_percentage' => $progress['progress_percentage']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Chunk uploaded successfully',
                'data' => [
                    'chunk_index' => $chunkIndex,
                    'storage_path' => $storagePath,
                    'progress' => $progress
                ]
            ]);

        } catch (FileUploadException $e) {
            Log::error('File upload exception during chunk upload', [
                'upload_session_id' => $request->input('upload_session_id'),
                'chunk_index' => $request->input('chunk_index'),
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Unexpected error during chunk upload', [
                'upload_session_id' => $request->input('upload_session_id'),
                'chunk_index' => $request->input('chunk_index'),
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred during chunk upload'
            ], 500);
        }
    }

    /**
     * Assemble chunks into final file
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function assembleFile(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'upload_session_id' => 'required|string|exists:upload_sessions,id',
                'chunk_hashes' => 'nullable|array',
                'chunk_hashes.*' => 'string|size:64', // SHA256 hash is 64 characters
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $uploadSessionId = $request->input('upload_session_id');
            $chunkHashes = $request->input('chunk_hashes', []);

            // Find and authorize the upload session
            $uploadSession = UploadSession::find($uploadSessionId);
            if (!$uploadSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload session not found'
                ], 404);
            }

            // Check authorization - user must own the upload session
            if ($uploadSession->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to upload session'
                ], 403);
            }

            // Check if session is expired
            if ($uploadSession->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload session has expired'
                ], 410);
            }

            // Check if session is in valid state for assembly
            if ($uploadSession->status !== UploadSession::STATUS_UPLOADING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload session is not ready for assembly',
                    'current_status' => $uploadSession->status
                ], 409);
            }

            // Validate that all chunks are uploaded
            if (!$uploadSession->isComplete()) {
                $progress = $this->chunkProcessingService->getUploadProgress($uploadSessionId);
                return response()->json([
                    'success' => false,
                    'message' => 'Not all chunks have been uploaded',
                    'progress' => $progress
                ], 400);
            }

            // Validate chunk set completeness
            $uploadedChunks = UploadChunk::where('upload_session_id', $uploadSessionId)
                ->where('status', '!=', UploadChunk::STATUS_PENDING)
                ->orderBy('chunk_index')
                ->get();

            if ($uploadedChunks->count() !== $uploadSession->total_chunks) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incomplete chunk set detected',
                    'expected_chunks' => $uploadSession->total_chunks,
                    'uploaded_chunks' => $uploadedChunks->count()
                ], 400);
            }

            // Check for missing chunks in sequence
            $expectedIndices = range(0, $uploadSession->total_chunks - 1);
            $actualIndices = $uploadedChunks->pluck('chunk_index')->toArray();
            $missingIndices = array_diff($expectedIndices, $actualIndices);

            if (!empty($missingIndices)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing chunks detected',
                    'missing_chunk_indices' => array_values($missingIndices)
                ], 400);
            }

            // Finalize the upload (this will assemble chunks and create the file record)
            $fileRecord = $this->chunkProcessingService->finalizeUpload($uploadSession);

            Log::info('File assembly completed successfully', [
                'upload_session_id' => $uploadSessionId,
                'user_id' => Auth::id(),
                'file_record_id' => $fileRecord->id,
                'file_record_type' => get_class($fileRecord),
                'original_filename' => $uploadSession->original_filename
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File assembled successfully',
                'data' => [
                    'file_id' => $fileRecord->id,
                    'file_type' => get_class($fileRecord),
                    'original_filename' => $uploadSession->original_filename,
                    'file_size' => $uploadSession->total_size,
                    'upload_session_id' => $uploadSessionId
                ]
            ]);

        } catch (FileUploadException $e) {
            Log::error('File upload exception during assembly', [
                'upload_session_id' => $request->input('upload_session_id'),
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            // Cleanup on failure
            if (isset($uploadSession)) {
                $this->chunkProcessingService->cleanupChunks($uploadSession->id);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Unexpected error during file assembly', [
                'upload_session_id' => $request->input('upload_session_id'),
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Cleanup on failure
            if (isset($uploadSession)) {
                $this->chunkProcessingService->cleanupChunks($uploadSession->id);
            }

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred during file assembly'
            ], 500);
        }
    }

    /**
     * Get upload status and progress information
     * 
     * @param string $uploadSessionId
     * @return JsonResponse
     */
    public function getUploadStatus(string $uploadSessionId): JsonResponse
    {
        try {
            // Find and authorize the upload session
            $uploadSession = UploadSession::find($uploadSessionId);
            if (!$uploadSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload session not found'
                ], 404);
            }

            // Check authorization - user must own the upload session
            if ($uploadSession->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to upload session'
                ], 403);
            }

            // Get detailed progress information
            $progress = $this->chunkProcessingService->getUploadProgress($uploadSessionId);

            Log::debug('Upload status requested', [
                'upload_session_id' => $uploadSessionId,
                'user_id' => Auth::id(),
                'status' => $progress['status'],
                'progress_percentage' => $progress['progress_percentage']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Upload status retrieved successfully',
                'data' => $progress
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving upload status', [
                'upload_session_id' => $uploadSessionId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving upload status'
            ], 500);
        }
    }

    /**
     * Clean up chunks for an upload session
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function cleanupChunks(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'upload_session_id' => 'required|string|exists:upload_sessions,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $uploadSessionId = $request->input('upload_session_id');

            // Find and authorize the upload session
            $uploadSession = UploadSession::find($uploadSessionId);
            if (!$uploadSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload session not found'
                ], 404);
            }

            // Check authorization - user must own the upload session
            if ($uploadSession->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to upload session'
                ], 403);
            }

            // Only allow cleanup for failed or completed sessions, or expired sessions
            $allowedStatuses = [
                UploadSession::STATUS_FAILED,
                UploadSession::STATUS_COMPLETED
            ];

            if (!in_array($uploadSession->status, $allowedStatuses) && !$uploadSession->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cleanup chunks for active upload session',
                    'current_status' => $uploadSession->status
                ], 409);
            }

            // Perform cleanup
            $cleanupSuccess = $this->chunkProcessingService->cleanupChunks($uploadSessionId);

            if ($cleanupSuccess) {
                Log::info('Manual chunk cleanup completed', [
                    'upload_session_id' => $uploadSessionId,
                    'user_id' => Auth::id(),
                    'session_status' => $uploadSession->status
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Chunks cleaned up successfully'
                ]);
            } else {
                Log::warning('Manual chunk cleanup completed with errors', [
                    'upload_session_id' => $uploadSessionId,
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Chunk cleanup completed with some errors'
                ], 500);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error during manual chunk cleanup', [
                'upload_session_id' => $request->input('upload_session_id'),
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during chunk cleanup'
            ], 500);
        }
    }

    /**
     * Cancel an upload session and clean up resources
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function cancelUpload(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'upload_session_id' => 'required|string|exists:upload_sessions,id',
                'reason' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $uploadSessionId = $request->input('upload_session_id');
            $reason = $request->input('reason', 'User cancelled upload');

            // Find and authorize the upload session
            $uploadSession = UploadSession::find($uploadSessionId);
            if (!$uploadSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload session not found'
                ], 404);
            }

            // Check authorization - user must own the upload session
            if ($uploadSession->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to upload session'
                ], 403);
            }

            // Only allow cancellation for active sessions
            $cancellableStatuses = [
                UploadSession::STATUS_PENDING,
                UploadSession::STATUS_UPLOADING,
                UploadSession::STATUS_ASSEMBLING
            ];

            if (!in_array($uploadSession->status, $cancellableStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel upload session in current status',
                    'current_status' => $uploadSession->status
                ], 409);
            }

            // Mark session as failed with cancellation reason
            $uploadSession->markAsFailed("Cancelled by user: {$reason}");

            // Clean up chunks
            $cleanupSuccess = $this->chunkProcessingService->cleanupChunks($uploadSessionId);

            Log::info('Upload session cancelled by user', [
                'upload_session_id' => $uploadSessionId,
                'user_id' => Auth::id(),
                'reason' => $reason,
                'cleanup_success' => $cleanupSuccess
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Upload cancelled successfully',
                'data' => [
                    'upload_session_id' => $uploadSessionId,
                    'cleanup_success' => $cleanupSuccess
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error during upload cancellation', [
                'upload_session_id' => $request->input('upload_session_id'),
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during upload cancellation'
            ], 500);
        }
    }
}
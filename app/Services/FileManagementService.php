<?php

namespace App\Services;

use App\Exceptions\File\FileDeletionException;
use App\Exceptions\File\FileUploadException;
use App\Exceptions\File\StorageLimitException;
use App\Jobs\GenerateFileWaveform;
use App\Models\FileUploadSetting;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileManagementService
{
    protected UserStorageService $userStorageService;

    public function __construct(UserStorageService $userStorageService)
    {
        $this->userStorageService = $userStorageService;
    }

    /**
     * Upload a file for a Project.
     * Authorization should be checked before calling this method.
     *
     * @param  User|null  $uploader  // Made nullable to support client uploads without accounts
     * @param  array  $metadata  // Optional metadata for client uploads
     *
     * @throws FileUploadException|StorageLimitException
     */
    public function uploadProjectFile(Project $project, UploadedFile $file, ?User $uploader = null, array $metadata = []): ProjectFile
    {
        // Increase execution time for large file uploads
        set_time_limit(300); // 5 minutes for large file uploads

        // Authorization is assumed to be handled by the caller (e.g., Policy check or signed URL validation)

        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        // Validate file size using project context settings
        $maxFileSizeMB = FileUploadSetting::getSetting(
            FileUploadSetting::MAX_FILE_SIZE_MB,
            FileUploadSetting::CONTEXT_PROJECTS
        );
        $maxFileSize = $maxFileSizeMB * 1024 * 1024; // Convert MB to bytes

        if ($fileSize > $maxFileSize) {
            throw new FileUploadException("File '{$fileName}' ({$fileSize} bytes) exceeds the maximum allowed size of {$maxFileSizeMB}MB ({$maxFileSize} bytes).");
        }

        // Check user storage capacity instead of project capacity
        $user = $uploader ?? $project->user;
        if (! $this->userStorageService->hasUserStorageCapacity($user, $fileSize)) {
            $used = $this->userStorageService->getUserStorageUsed($user);
            $limit = $this->userStorageService->getUserStorageLimit($user);
            throw new StorageLimitException(
                'Upload would exceed your storage limit. '.
                'Used: '.number_format($used / (1024 ** 3), 2).'GB, '.
                'Limit: '.number_format($limit / (1024 ** 3), 2).'GB'
            );
        }

        try {
            return DB::transaction(function () use ($project, $file, $fileName, $fileSize, $uploader, $metadata) {
                // Store the file securely
                $path = Storage::disk('s3')->putFileAs(
                    'projects/'.$project->id,
                    $file,
                    $fileName
                );

                $uploaderInfo = $uploader ? ['uploader_id' => $uploader->id] : ['client_upload' => true];
                Log::info('Project file uploaded to S3', array_merge([
                    'filename' => $fileName,
                    'path' => $path,
                    'project_id' => $project->id,
                ], $uploaderInfo, $metadata));

                $projectFile = $project->files()->create([
                    'storage_path' => $path,
                    'file_path' => $path,
                    'file_name' => $fileName, // Store original name
                    'original_file_name' => $fileName,
                    'size' => $fileSize,
                    'user_id' => $uploader?->id, // Track uploader (null for client uploads)
                    'mime_type' => $file->getMimeType(),
                    'metadata' => ! empty($metadata) ? json_encode($metadata) : null, // Store client upload metadata
                ]);

                // Dispatch waveform generation job for audio files
                if ($projectFile->isAudioFile()) {
                    Log::info('Dispatching waveform generation job for project file', [
                        'project_file_id' => $projectFile->id,
                        'file_name' => $fileName,
                        'mime_type' => $file->getMimeType(),
                    ]);

                    GenerateFileWaveform::dispatch($projectFile);
                }

                // Atomically update user storage usage instead of project storage
                $user = $uploader ?? $project->user;
                $this->userStorageService->incrementUserStorage($user, $fileSize);

                return $projectFile;
            });
        } catch (\Exception $e) {
            $uploaderInfo = $uploader ? ['uploader_id' => $uploader->id] : ['client_upload' => true];
            Log::error('Error uploading project file', array_merge([
                'project_id' => $project->id,
                'filename' => $fileName,
                'error' => $e->getMessage(),
            ], $uploaderInfo));

            if (isset($path) && Storage::disk('s3')->exists($path)) {
                Storage::disk('s3')->delete($path);
                Log::info('Cleaned up orphaned S3 file after upload failure', ['path' => $path]);
            }
            throw new FileUploadException("Failed to upload file '{$fileName}'.", 0, $e);
        }
    }

    /**
     * Upload a file for a Pitch.
     * Authorization and status validation should be checked before calling this method.
     *
     * @param  User  $uploader  // Keep uploader to associate file record
     *
     * @throws FileUploadException|StorageLimitException
     */
    public function uploadPitchFile(Pitch $pitch, UploadedFile $file, User $uploader): PitchFile
    {
        // Increase execution time for large file uploads
        set_time_limit(300); // 5 minutes for large file uploads

        // Authorization and status validation are assumed to be handled by the caller

        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        // Validate file size using pitch context settings
        $maxFileSizeMB = FileUploadSetting::getSetting(
            FileUploadSetting::MAX_FILE_SIZE_MB,
            FileUploadSetting::CONTEXT_PITCHES
        );
        $maxFileSize = $maxFileSizeMB * 1024 * 1024; // Convert MB to bytes

        if ($fileSize > $maxFileSize) {
            throw new FileUploadException("File '{$fileName}' ({$fileSize} bytes) exceeds the maximum allowed size of {$maxFileSizeMB}MB ({$maxFileSize} bytes).");
        }

        // Check user storage capacity instead of pitch capacity
        if (! $this->userStorageService->hasUserStorageCapacity($uploader, $fileSize)) {
            $used = $this->userStorageService->getUserStorageUsed($uploader);
            $limit = $this->userStorageService->getUserStorageLimit($uploader);
            throw new StorageLimitException(
                'Upload would exceed your storage limit. '.
                'Used: '.number_format($used / (1024 ** 3), 2).'GB, '.
                'Limit: '.number_format($limit / (1024 ** 3), 2).'GB'
            );
        }

        try {
            return DB::transaction(function () use ($pitch, $file, $fileName, $fileSize, $uploader) {
                // Store the file securely
                $path = Storage::disk('s3')->putFileAs(
                    'pitches/'.$pitch->id,
                    $file,
                    $fileName
                );

                Log::info('Pitch file uploaded to S3', ['filename' => $fileName, 'path' => $path, 'pitch_id' => $pitch->id, 'uploader_id' => $uploader->id]);

                $pitchFile = $pitch->files()->create([
                    'storage_path' => $path,
                    'file_path' => $path,
                    'file_name' => $fileName,
                    'original_file_name' => $fileName,
                    'size' => $fileSize,
                    'user_id' => $uploader->id, // Pitch creator is uploader
                    'mime_type' => $file->getMimeType(),
                ]);

                // Update user storage usage instead of pitch storage
                $this->userStorageService->incrementUserStorage($uploader, $fileSize);

                // If file upload triggers waveform generation
                if (str_starts_with($pitchFile->mime_type, 'audio/')) {
                    dispatch(new \App\Jobs\GenerateAudioWaveform($pitchFile));
                }

                return $pitchFile;
            });
        } catch (\Exception $e) {
            Log::error('Error uploading pitch file', ['pitch_id' => $pitch->id, 'filename' => $fileName, 'error' => $e->getMessage()]);
            if (isset($path) && Storage::disk('s3')->exists($path)) {
                Storage::disk('s3')->delete($path);
                Log::info('Cleaned up orphaned S3 file after pitch upload failure', ['path' => $path]);
            }
            throw new FileUploadException("Failed to upload file '{$fileName}'.", 0, $e);
        }
    }

    /**
     * Delete a Project file.
     * Authorization should be checked before calling this method.
     *
     * @throws FileDeletionException
     */
    public function deleteProjectFile(ProjectFile $projectFile): bool
    {
        // Authorization is assumed to be handled by the caller

        $project = $projectFile->project;
        $filePath = $projectFile->storage_path ?: $projectFile->file_path;

        try {
            return DB::transaction(function () use ($projectFile, $project, $filePath) {
                $fileSize = $projectFile->size ?: $projectFile->file_size ?: 0; // Handle both field names

                // Delete DB record first
                $deleted = $projectFile->delete();

                if (! $deleted) {
                    throw new FileDeletionException('Failed to delete file record from database.');
                }

                // Decrement user storage used - only if file size is valid
                if ($fileSize > 0 && $project) {
                    try {
                        $this->userStorageService->decrementUserStorage($project->user, $fileSize);
                    } catch (\Exception $storageEx) {
                        Log::warning('Failed to decrement user storage usage during file deletion', [
                            'project_id' => $project->id,
                            'user_id' => $project->user->id,
                            'file_size' => $fileSize,
                            'error' => $storageEx->getMessage(),
                        ]);
                        // Don't fail the whole operation for storage tracking issues
                    }
                }

                // Delete file from S3 (don't fail if S3 delete fails, file record is already gone)
                try {
                    if (Storage::disk('s3')->exists($filePath)) {
                        Storage::disk('s3')->delete($filePath);
                        Log::info('Project file deleted from S3', ['path' => $filePath, 'project_id' => $project?->id]);
                    } else {
                        Log::warning('Project file not found on S3 during deletion', ['path' => $filePath, 'project_id' => $project?->id]);
                    }
                } catch (\Exception $storageEx) {
                    Log::error('Failed to delete project file from S3, but DB record removed', ['path' => $filePath, 'error' => $storageEx->getMessage()]);
                    // Don't fail the whole operation for S3 cleanup issues
                }

                // Clear cached relationships to prevent stale data
                if ($project) {
                    $project->refresh();
                    $project->unsetRelation('files'); // Clear the files relationship cache
                }

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Error deleting project file', ['file_id' => $projectFile->id, 'error' => $e->getMessage()]);
            throw new FileDeletionException('Failed to delete project file: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete a Pitch file.
     * Authorization and status validation should be checked before calling this method.
     *
     * @throws FileDeletionException
     */
    public function deletePitchFile(PitchFile $pitchFile): bool
    {
        // Authorization and Status checks are assumed to be handled by the caller

        $pitch = $pitchFile->pitch;
        $filePath = $pitchFile->storage_path ?: $pitchFile->file_path;

        try {
            DB::transaction(function () use ($pitchFile, $pitch, $filePath) {
                $fileSize = $pitchFile->size;

                $deleted = $pitchFile->delete();

                if ($deleted) {
                    // Decrement user storage used instead of pitch storage
                    $this->userStorageService->decrementUserStorage($pitch->user, $fileSize);

                    // Delete file from S3
                    try {
                        if (Storage::disk('s3')->exists($filePath)) {
                            Storage::disk('s3')->delete($filePath);
                            Log::info('Pitch file deleted from S3', ['path' => $filePath, 'pitch_id' => $pitch->id]);
                        } else {
                            Log::warning('Pitch file not found on S3 during deletion', ['path' => $filePath, 'pitch_id' => $pitch->id]);
                        }
                    } catch (\Exception $storageEx) {
                        Log::error('Failed to delete pitch file from S3, but DB record removed', ['path' => $filePath, 'error' => $storageEx->getMessage()]);
                    }

                    // Clear cached relationships to prevent stale data
                    if ($pitch) {
                        $pitch->refresh();
                        $pitch->unsetRelation('files'); // Clear the files relationship cache
                    }
                } else {
                    throw new FileDeletionException('Failed to delete file record from database.');
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Error deleting pitch file', ['file_id' => $pitchFile->id, 'error' => $e->getMessage()]);
            throw new FileDeletionException('Failed to delete pitch file: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate a temporary download or streaming URL for a file.
     *
     * @param  ProjectFile|PitchFile  $fileModel
     * @param  int  $minutes  Expiration time in minutes
     * @param  bool  $forceDownload  If true, sets headers to force download; if false, allows streaming/inline display.
     */
    public function getTemporaryDownloadUrl($fileModel, int $minutes = 15, bool $forceDownload = true): string
    {
        // Authorization is assumed to be handled by the caller

        $filePath = $fileModel->storage_path ?: $fileModel->file_path;
        $fileName = $fileModel->original_file_name ?: $fileModel->file_name; // Use original if available

        try {
            if (empty($filePath)) {
                Log::error('Attempted to generate download URL for file with empty path.', ['file_model_id' => $fileModel->id, 'model_type' => get_class($fileModel)]);
                throw new \InvalidArgumentException('File path is missing.');
            }

            $options = [];
            if ($forceDownload) {
                $options = [
                    'ResponseContentDisposition' => 'attachment; filename="'.addslashes($fileName).'"',
                ];
            }

            return Storage::disk('s3')->temporaryUrl(
                $filePath,
                now()->addMinutes($minutes),
                $options
            );
        } catch (\Exception $e) {
            Log::error('Failed to generate temporary download URL.', [
                'file_model_id' => $fileModel->id,
                'model_type' => get_class($fileModel),
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Could not generate download URL.', 0, $e);
        }
    }

    /**
     * Set the preview track for a project.
     * Authorization should be checked before calling this method.
     *
     * @throws \InvalidArgumentException
     */
    public function setProjectPreviewTrack(Project $project, ProjectFile $file): void
    {
        // Authorization is assumed to be handled by the caller

        if ($file->project_id !== $project->id) {
            throw new \InvalidArgumentException('File does not belong to the specified project.');
        }

        $project->preview_track = $file->id;
        $project->save();
    }

    /**
     * Clear the preview track for a project.
     * Authorization should be checked before calling this method.
     */
    public function clearProjectPreviewTrack(Project $project): void
    {
        // Authorization is assumed to be handled by the caller

        $project->preview_track = null;
        $project->save();
    }

    /**
     * Create a ProjectFile record for a file that was uploaded directly to S3.
     * Authorization should be checked before calling this method.
     *
     * @throws FileUploadException|StorageLimitException
     */
    public function createProjectFileFromS3(Project $project, string $s3Key, string $fileName, int $fileSize, string $mimeType, ?User $uploader = null, array $metadata = []): ProjectFile
    {
        // Authorization is assumed to be handled by the caller

        // Validate file size using project context settings
        $maxFileSizeMB = FileUploadSetting::getSetting(
            FileUploadSetting::MAX_FILE_SIZE_MB,
            FileUploadSetting::CONTEXT_PROJECTS
        );
        $maxFileSize = $maxFileSizeMB * 1024 * 1024; // Convert MB to bytes

        if ($fileSize > $maxFileSize) {
            throw new FileUploadException("File '{$fileName}' ({$fileSize} bytes) exceeds the maximum allowed size of {$maxFileSizeMB}MB ({$maxFileSize} bytes).");
        }

        // Check user storage capacity instead of project capacity
        $user = $uploader ?? $project->user;
        if (! $this->userStorageService->hasUserStorageCapacity($user, $fileSize)) {
            $used = $this->userStorageService->getUserStorageUsed($user);
            $limit = $this->userStorageService->getUserStorageLimit($user);
            throw new StorageLimitException(
                'Upload would exceed your storage limit. '.
                'Used: '.number_format($used / (1024 ** 3), 2).'GB, '.
                'Limit: '.number_format($limit / (1024 ** 3), 2).'GB'
            );
        }

        // Verify the file actually exists in S3
        if (! Storage::disk('s3')->exists($s3Key)) {
            throw new FileUploadException("File '{$fileName}' not found in S3 storage.");
        }

        try {
            return DB::transaction(function () use ($project, $s3Key, $fileName, $fileSize, $mimeType, $uploader, $metadata) {
                $uploaderInfo = $uploader ? ['uploader_id' => $uploader->id] : ['client_upload' => true];
                Log::info('Creating ProjectFile record from S3 upload', array_merge([
                    'filename' => $fileName,
                    's3_key' => $s3Key,
                    'project_id' => $project->id,
                    'file_size' => $fileSize,
                ], $uploaderInfo, $metadata));

                $projectFile = $project->files()->create([
                    'storage_path' => $s3Key,
                    'file_path' => $s3Key,
                    'file_name' => $fileName,
                    'original_file_name' => $fileName,
                    'size' => $fileSize,
                    'user_id' => $uploader?->id,
                    'mime_type' => $mimeType,
                    'metadata' => ! empty($metadata) ? json_encode($metadata) : null,
                ]);

                // Atomically update user storage usage instead of project storage
                $user = $uploader ?? $project->user;
                $this->userStorageService->incrementUserStorage($user, $fileSize);

                // Clear cached relationships to ensure fresh data
                $project->unsetRelation('files');

                return $projectFile;
            });
        } catch (\Exception $e) {
            $uploaderInfo = $uploader ? ['uploader_id' => $uploader->id] : ['client_upload' => true];
            Log::error('Error creating ProjectFile record from S3 upload', array_merge([
                'project_id' => $project->id,
                'filename' => $fileName,
                's3_key' => $s3Key,
                'error' => $e->getMessage(),
            ], $uploaderInfo));

            throw new FileUploadException("Failed to create file record for '{$fileName}'.", 0, $e);
        }
    }

    /**
     * Create a PitchFile record for a file that was uploaded directly to S3.
     * Authorization should be checked before calling this method.
     *
     * @throws FileUploadException|StorageLimitException
     */
    public function createPitchFileFromS3(Pitch $pitch, string $s3Key, string $fileName, int $fileSize, string $mimeType, ?User $uploader = null, array $metadata = []): PitchFile
    {
        // Authorization is assumed to be handled by the caller

        // Validate file size using pitch context settings
        $maxFileSizeMB = FileUploadSetting::getSetting(
            FileUploadSetting::MAX_FILE_SIZE_MB,
            FileUploadSetting::CONTEXT_PITCHES
        );
        $maxFileSize = $maxFileSizeMB * 1024 * 1024; // Convert MB to bytes

        if ($fileSize > $maxFileSize) {
            throw new FileUploadException("File '{$fileName}' ({$fileSize} bytes) exceeds the maximum allowed size of {$maxFileSizeMB}MB ({$maxFileSize} bytes).");
        }

        // Check user storage capacity instead of pitch capacity
        $user = $uploader ?? $pitch->user;
        if (! $this->userStorageService->hasUserStorageCapacity($user, $fileSize)) {
            $used = $this->userStorageService->getUserStorageUsed($user);
            $limit = $this->userStorageService->getUserStorageLimit($user);
            throw new StorageLimitException(
                'Upload would exceed your storage limit. '.
                'Used: '.number_format($used / (1024 ** 3), 2).'GB, '.
                'Limit: '.number_format($limit / (1024 ** 3), 2).'GB'
            );
        }

        // Verify the file actually exists in S3
        if (! Storage::disk('s3')->exists($s3Key)) {
            throw new FileUploadException("File '{$fileName}' not found in S3 storage.");
        }

        try {
            return DB::transaction(function () use ($pitch, $s3Key, $fileName, $fileSize, $mimeType, $uploader, $metadata) {
                $uploaderInfo = $uploader ? ['uploader_id' => $uploader->id] : ['client_upload' => true];

                // Determine revision round for this file
                // For client management projects with revisions, tag new files with current revision round
                $revisionRound = 1; // Default for first submission
                if ($pitch->project->isClientManagement() && $pitch->revisions_used > 0) {
                    // Use current revisions_used count as the revision round
                    $revisionRound = $pitch->revisions_used;
                }

                Log::info('Creating PitchFile record from S3 upload', array_merge([
                    'filename' => $fileName,
                    's3_key' => $s3Key,
                    'pitch_id' => $pitch->id,
                    'file_size' => $fileSize,
                    'revision_round' => $revisionRound,
                ], $uploaderInfo, $metadata));

                $pitchFile = $pitch->files()->create([
                    'storage_path' => $s3Key,
                    'file_path' => $s3Key,
                    'file_name' => $fileName,
                    'original_file_name' => $fileName,
                    'size' => $fileSize,
                    'user_id' => $uploader?->id,
                    'mime_type' => $mimeType,
                    'metadata' => ! empty($metadata) ? json_encode($metadata) : null,
                    'revision_round' => $revisionRound,
                    'superseded_by_revision' => false,
                ]);

                // Atomically update user storage usage instead of pitch storage
                $user = $uploader ?? $pitch->user;
                $this->userStorageService->incrementUserStorage($user, $fileSize);

                // If file upload triggers waveform generation
                if (str_starts_with($pitchFile->mime_type, 'audio/')) {
                    dispatch(new \App\Jobs\GenerateAudioWaveform($pitchFile));
                }

                // Clear cached relationships to ensure fresh data
                $pitch->unsetRelation('files');

                return $pitchFile;
            });
        } catch (\Exception $e) {
            $uploaderInfo = $uploader ? ['uploader_id' => $uploader->id] : ['client_upload' => true];
            Log::error('Error creating PitchFile record from S3 upload', array_merge([
                'pitch_id' => $pitch->id,
                'filename' => $fileName,
                's3_key' => $s3Key,
                'error' => $e->getMessage(),
            ], $uploaderInfo));

            throw new FileUploadException("Failed to create file record for '{$fileName}'.", 0, $e);
        }
    }

    /**
     * Generate a presigned URL for direct upload to S3/R2
     * Authorization should be checked before calling this method.
     *
     * @param  string  $context  The upload context (projects, pitches, client_portals)
     * @param  string  $fileName  Original file name
     * @param  string  $mimeType  File MIME type
     * @param  int  $fileSize  File size in bytes
     * @param  array  $metadata  Additional metadata (model_type, model_id, etc.)
     * @param  User|null  $uploader  The user who will upload (null for client uploads)
     * @return array Contains presigned_url, s3_key, expires_at
     *
     * @throws FileUploadException|StorageLimitException
     */
    public function generatePresignedUploadUrl(string $context, string $fileName, string $mimeType, int $fileSize, array $metadata = [], ?User $uploader = null): array
    {
        // Validate context
        if (! FileUploadSetting::validateContext($context)) {
            throw new FileUploadException("Invalid upload context: {$context}");
        }

        // Validate file size using context settings
        $maxFileSizeMB = FileUploadSetting::getSetting(
            FileUploadSetting::MAX_FILE_SIZE_MB,
            $context
        );
        $maxFileSize = $maxFileSizeMB * 1024 * 1024; // Convert MB to bytes

        if ($fileSize > $maxFileSize) {
            throw new FileUploadException("File '{$fileName}' ({$fileSize} bytes) exceeds the maximum allowed size of {$maxFileSizeMB}MB ({$maxFileSize} bytes).");
        }

        // For authenticated uploads, check storage capacity
        if ($uploader) {
            if (! $this->userStorageService->hasUserStorageCapacity($uploader, $fileSize)) {
                $used = $this->userStorageService->getUserStorageUsed($uploader);
                $limit = $this->userStorageService->getUserStorageLimit($uploader);
                throw new StorageLimitException(
                    'Upload would exceed your storage limit. '.
                    'Used: '.number_format($used / (1024 ** 3), 2).'GB, '.
                    'Limit: '.number_format($limit / (1024 ** 3), 2).'GB'
                );
            }
        }

        try {
            // Generate unique S3 key
            $s3Key = $this->generateS3Key($context, $fileName, $metadata);

            // Check if we're in testing mode (using fake storage)
            $diskConfig = Storage::disk('s3')->getConfig();
            if (app()->environment('testing') && (! isset($diskConfig['driver']) || $diskConfig['driver'] !== 's3')) {
                // Return a mock presigned URL for testing
                $presignedUrl = 'https://test-bucket.s3.amazonaws.com/'.$s3Key.'?presigned=true';
                $expiresAt = now()->addMinutes(15);
            } else {
                // Get S3 client from Laravel's Storage facade
                $s3Client = Storage::disk('s3')->getAdapter()->getClient();
                $bucket = config('filesystems.disks.s3.bucket');

                // Generate presigned URL for PUT operation
                $cmd = $s3Client->getCommand('PutObject', [
                    'Bucket' => $bucket,
                    'Key' => $s3Key,
                    'ContentType' => $mimeType,
                    'ContentLength' => $fileSize,
                ]);

                // Set expiration (15 minutes by default)
                $expiresAt = now()->addMinutes(15);
                $request = $s3Client->createPresignedRequest($cmd, $expiresAt);
                $presignedUrl = (string) $request->getUri();
            }

            Log::info('Generated presigned upload URL', [
                's3_key' => $s3Key,
                'context' => $context,
                'filename' => $fileName,
                'file_size' => $fileSize,
                'uploader_id' => $uploader?->id,
                'expires_at' => $expiresAt->toISOString(),
                'metadata' => $metadata,
            ]);

            return [
                'presigned_url' => $presignedUrl,
                's3_key' => $s3Key,
                'expires_at' => $expiresAt->toISOString(),
                'upload_method' => 'PUT',
                'headers' => [
                    'Content-Type' => $mimeType,
                    'Content-Length' => $fileSize,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate presigned upload URL', [
                'context' => $context,
                'filename' => $fileName,
                'error' => $e->getMessage(),
                'uploader_id' => $uploader?->id,
            ]);

            throw new FileUploadException("Failed to generate upload URL for '{$fileName}'.", 0, $e);
        }
    }

    /**
     * Generate S3 key based on context and metadata
     */
    protected function generateS3Key(string $context, string $fileName, array $metadata = []): string
    {
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uniqueId = Str::ulid();

        // Generate folder based on context and metadata
        $folder = match ($context) {
            FileUploadSetting::CONTEXT_PROJECTS => $this->generateProjectFolder($metadata),
            FileUploadSetting::CONTEXT_PITCHES => $this->generatePitchFolder($metadata),
            FileUploadSetting::CONTEXT_CLIENT_PORTALS => $this->generateClientPortalFolder($metadata),
            default => 'uploads/'
        };

        return $folder.$uniqueId.'.'.$fileExtension;
    }

    /**
     * Generate folder path for project uploads
     */
    protected function generateProjectFolder(array $metadata): string
    {
        if (isset($metadata['project_id'])) {
            return "projects/{$metadata['project_id']}/";
        }

        return 'projects/';
    }

    /**
     * Generate folder path for pitch uploads
     */
    protected function generatePitchFolder(array $metadata): string
    {
        if (isset($metadata['pitch_id'])) {
            return "pitches/{$metadata['pitch_id']}/";
        }

        return 'pitches/';
    }

    /**
     * Generate folder path for client portal uploads
     */
    protected function generateClientPortalFolder(array $metadata): string
    {
        if (isset($metadata['project_id'])) {
            return "client-uploads/{$metadata['project_id']}/";
        }

        return 'client-uploads/';
    }

    /**
     * Upload a new version of an existing pitch file
     * Authorization should be checked before calling this method
     *
     * @param  PitchFile  $existingFile  The file to create a new version of
     * @param  string  $s3Key  S3 key where file was uploaded
     * @param  string  $fileName  Original filename
     * @param  int  $fileSize  File size in bytes
     * @param  string  $mimeType  File MIME type
     * @param  User  $uploader  User uploading the version
     * @return PitchFile The newly created version
     *
     * @throws FileUploadException
     */
    public function uploadFileVersion(
        PitchFile $existingFile,
        string $s3Key,
        string $fileName,
        int $fileSize,
        string $mimeType,
        User $uploader
    ): PitchFile {
        try {
            return DB::transaction(function () use ($existingFile, $s3Key, $fileName, $fileSize, $mimeType, $uploader) {
                // Get root file (in case existingFile is already a version)
                $rootFile = $existingFile->getRootFile();
                $pitch = $rootFile->pitch;

                // Calculate next version number - find first available number
                // Get all existing version numbers (including soft-deleted)
                $existingVersionNumbers = $rootFile->versions()
                    ->withTrashed()
                    ->pluck('file_version_number')
                    ->push($rootFile->file_version_number ?? 1)
                    ->sort()
                    ->values();

                // Find first available version number (start from V2)
                $newVersionNumber = 2;
                while ($existingVersionNumbers->contains($newVersionNumber)) {
                    $newVersionNumber++;
                }

                Log::info('Creating new file version', [
                    'root_file_id' => $rootFile->id,
                    'existing_file_id' => $existingFile->id,
                    'new_version_number' => $newVersionNumber,
                    's3_key' => $s3Key,
                    'filename' => $fileName,
                ]);

                // Create new file record
                $newFile = $pitch->files()->create([
                    'storage_path' => $s3Key,
                    'file_path' => $s3Key,
                    'file_name' => $fileName,
                    'original_file_name' => $fileName,
                    'size' => $fileSize,
                    'user_id' => $uploader->id,
                    'mime_type' => $mimeType,
                    'parent_file_id' => $rootFile->id,
                    'file_version_number' => $newVersionNumber,
                    'included_in_working_version' => true,
                    'revision_round' => $pitch->revisions_used ?? 1,
                    'superseded_by_revision' => false,
                ]);

                // Update user storage usage
                $this->userStorageService->incrementUserStorage($uploader, $fileSize);

                // Trigger waveform generation for audio files
                if (str_starts_with($newFile->mime_type, 'audio/')) {
                    dispatch(new \App\Jobs\GenerateAudioWaveform($newFile));
                }

                // Exclude all other versions from working version
                $rootFile->getAllVersionsWithSelf()
                    ->where('id', '!=', $newFile->id)
                    ->each(fn ($v) => $v->excludeFromWorkingVersion());

                // Clear cached relationships
                $pitch->unsetRelation('files');

                Log::info('File version created successfully', [
                    'new_file_id' => $newFile->id,
                    'version_number' => $newVersionNumber,
                ]);

                return $newFile;
            });
        } catch (\Exception $e) {
            Log::error('Error creating file version', [
                'existing_file_id' => $existingFile->id,
                'error' => $e->getMessage(),
            ]);
            throw new FileUploadException("Failed to create new version of file '{$fileName}'.", 0, $e);
        }
    }

    /**
     * Bulk upload new versions for multiple files with auto-matching
     *
     * @param  Pitch  $pitch  The pitch to upload files to
     * @param  array  $uploadedFiles  Array of uploaded file data ['name' => ..., 's3_key' => ..., 'size' => ..., 'type' => ...]
     * @param  User  $uploader  User uploading the versions
     * @param  array|null  $manualMatches  Optional manual overrides [existing_file_id => uploaded_file_data]
     * @return array Results with 'created_versions' and 'new_files' keys
     */
    public function bulkUploadFileVersions(
        Pitch $pitch,
        array $uploadedFiles,
        User $uploader,
        ?array $manualMatches = null
    ): array {
        $createdVersions = [];
        $newFiles = [];

        // Get existing files for matching
        $existingFiles = $pitch->files()->latestVersions()->get();

        // Use manual matches if provided, otherwise auto-match
        if ($manualMatches !== null) {
            $matched = $manualMatches;

            // Calculate which uploaded files are NOT in manual matches
            // Files not matched should be created as new PitchFiles
            $matchedS3Keys = array_column($manualMatches, 's3_key');
            $unmatched = array_filter($uploadedFiles, function ($file) use ($matchedS3Keys) {
                return ! in_array($file['s3_key'], $matchedS3Keys);
            });

            Log::info('Bulk upload with manual matches', [
                'total_uploaded' => count($uploadedFiles),
                'manual_matched' => count($manualMatches),
                'calculated_unmatched' => count($unmatched),
                'matched_s3_keys' => $matchedS3Keys,
                'unmatched_files' => array_column($unmatched, 'name'),
            ]);
        } else {
            $matchResult = $this->matchFilesByName($existingFiles, $uploadedFiles);
            $matched = $matchResult['matched'];
            $unmatched = $matchResult['unmatched'];
        }

        // Create versions for matched files
        foreach ($matched as $existingFileId => $uploadData) {
            try {
                $existingFile = PitchFile::findOrFail($existingFileId);

                $newVersion = $this->uploadFileVersion(
                    $existingFile,
                    $uploadData['s3_key'],
                    $uploadData['name'],
                    $uploadData['size'],
                    $uploadData['type'],
                    $uploader
                );

                $createdVersions[] = $newVersion;
            } catch (\Exception $e) {
                Log::error('Error creating file version in bulk upload', [
                    'existing_file_id' => $existingFileId,
                    'filename' => $uploadData['name'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Create new PitchFile records for unmatched files
        $createdNewFiles = [];
        foreach ($unmatched as $uploadData) {
            try {
                $newFile = $this->createPitchFileFromS3(
                    $pitch,
                    $uploadData['s3_key'],
                    $uploadData['name'],
                    $uploadData['size'],
                    $uploadData['type'] ?? 'application/octet-stream',
                    $uploader
                );

                $createdNewFiles[] = $newFile;

                Log::info('Created new PitchFile from unmatched upload', [
                    'pitch_id' => $pitch->id,
                    'file_id' => $newFile->id,
                    'filename' => $uploadData['name'],
                ]);
            } catch (\Exception $e) {
                Log::error('Error creating new file in bulk upload', [
                    'pitch_id' => $pitch->id,
                    'filename' => $uploadData['name'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'created_versions' => $createdVersions,
            'new_files' => $createdNewFiles,
        ];
    }

    /**
     * Auto-match uploaded files to existing files by filename
     * Returns suggested mapping and unmatched files
     *
     * @param  \Illuminate\Support\Collection  $existingFiles  Collection of existing PitchFile models
     * @param  array  $uploadedFiles  Array of uploaded file data ['name' => ..., 's3_key' => ..., etc]
     * @return array ['matched' => [...], 'unmatched' => [...]]
     */
    public function matchFilesByName(\Illuminate\Support\Collection $existingFiles, array $uploadedFiles): array
    {
        $matched = [];
        $unmatched = [];

        foreach ($uploadedFiles as $uploaded) {
            $uploadedBasic = $this->normalizeFilename($uploaded['name']);
            $uploadedFuzzy = $this->fuzzyNormalizeFilename($uploaded['name']);
            $matchFound = false;
            $bestMatch = null;
            $bestScore = 0.0;

            foreach ($existingFiles as $existing) {
                // Skip files that are already matched
                if (isset($matched[$existing->id])) {
                    continue;
                }

                $existingBasic = $this->normalizeFilename($existing->file_name);

                // Try exact match first (fast path)
                if ($uploadedBasic === $existingBasic) {
                    $matched[$existing->id] = $uploaded;
                    $matchFound = true;
                    break;
                }

                // Try fuzzy match
                $existingFuzzy = $this->fuzzyNormalizeFilename($existing->file_name);
                $similarity = $this->calculateFileSimilarity($uploadedFuzzy, $existingFuzzy);

                // Threshold: 65% similarity (allows for one extra descriptor word difference)
                if ($similarity >= 0.65 && $similarity > $bestScore) {
                    $bestMatch = $existing;
                    $bestScore = $similarity;
                }
            }

            if (! $matchFound && $bestMatch) {
                $matched[$bestMatch->id] = $uploaded;
                $matchFound = true;

                Log::info('Fuzzy file match found', [
                    'uploaded' => $uploaded['name'],
                    'matched_to' => $bestMatch->file_name,
                    'similarity_score' => round($bestScore * 100, 2).'%',
                ]);
            }

            if (! $matchFound) {
                $unmatched[] = $uploaded;
            }
        }

        Log::info('File matching results', [
            'total_uploaded' => count($uploadedFiles),
            'matched' => count($matched),
            'unmatched' => count($unmatched),
        ]);

        return compact('matched', 'unmatched');
    }

    /**
     * Normalize filename for matching (removes extension, lowercases, trims)
     */
    protected function normalizeFilename(string $filename): string
    {
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

        return strtolower(trim($nameWithoutExt));
    }

    /**
     * Aggressively normalize filename for fuzzy matching
     * Strips track numbers, version indicators, artist prefixes, and descriptors
     *
     * @return array{normalized: string, tokens: array}
     */
    protected function fuzzyNormalizeFilename(string $filename): array
    {
        // Remove extension, lowercase, trim
        $name = strtolower(trim(pathinfo($filename, PATHINFO_FILENAME)));

        // Strip leading track numbers: 01, 02, 1., 2-, etc.
        $name = preg_replace('/^\d+[\.\-\s]*/', '', $name);

        // Strip version indicators at end: v1, v2, 1a, 1b
        $name = preg_replace('/\s*[_\-\s]*(v\d+|\d+[a-z])\s*$/i', '', $name);

        // Extract song name from "Artist - Song" format
        if (str_contains($name, ' - ')) {
            $parts = explode(' - ', $name, 2);
            $name = $parts[1] ?? $parts[0];
        }

        // Remove common descriptors at the end
        $descriptors = [
            // Quality/stage descriptors
            'rough', 'final', 'master', 'demo', 'draft',
            // Version descriptors
            'mix', 'version', 'edit', 'alt', 'alternate',
            // Length descriptors
            'extended', 'short', 'long', 'full',
            // Content descriptors
            'clean', 'dirty', 'explicit', 'radio', 'club',
            'instrumental', 'acapella', 'vocals', 'beat',
            // Copy indicators
            'copy', 'backup', 'old', 'new', 'latest', 'test',
        ];
        $pattern = '/\s+('.implode('|', $descriptors).')\s*$/i';
        $name = preg_replace($pattern, '', $name);

        // Normalize separators and whitespace
        $name = str_replace(['_', '-'], ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim($name);

        // Tokenize
        $tokens = array_filter(explode(' ', $name), fn ($t) => strlen($t) > 0);

        return [
            'normalized' => $name,
            'tokens' => $tokens,
        ];
    }

    /**
     * Calculate similarity between two normalized file info arrays
     * Uses combination of token-based (Jaccard) and string similarity
     *
     * @param  array{normalized: string, tokens: array}  $info1
     * @param  array{normalized: string, tokens: array}  $info2
     * @return float Similarity score from 0.0 to 1.0
     */
    protected function calculateFileSimilarity(array $info1, array $info2): float
    {
        // Token-based similarity (Jaccard coefficient)
        $tokens1 = $info1['tokens'];
        $tokens2 = $info2['tokens'];

        if (empty($tokens1) || empty($tokens2)) {
            return 0.0;
        }

        $intersection = array_intersect($tokens1, $tokens2);

        // Check if one token set is a complete subset of the other
        // If all tokens from one file exist in the other, it's a perfect match
        $isSubset = (count($intersection) === count($tokens1)) || (count($intersection) === count($tokens2));
        if ($isSubset && count($intersection) > 0) {
            return 1.0; // Perfect match if one is subset of other
        }

        $union = array_unique(array_merge($tokens1, $tokens2));
        $tokenSimilarity = count($intersection) / count($union);

        // String-based similarity
        $str1 = $info1['normalized'];
        $str2 = $info2['normalized'];
        similar_text($str1, $str2, $stringSimilarity);
        $stringSimilarity = $stringSimilarity / 100; // Convert to 0-1 range

        // Weighted combination (tokens more important)
        return ($tokenSimilarity * 0.7) + ($stringSimilarity * 0.3);
    }
}

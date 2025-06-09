<?php
namespace App\Services;

use App\Models\Project;
use App\Models\Pitch;
use App\Models\ProjectFile;
use App\Models\PitchFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\File\FileUploadException;
use App\Exceptions\File\StorageLimitException;
use App\Exceptions\File\FileDeletionException;
use App\Exceptions\File\UnauthorizedActionException;

class FileManagementService
{
    /**
     * Upload a file for a Project.
     * Authorization should be checked before calling this method.
     *
     * @param Project $project
     * @param UploadedFile $file
     * @param User|null $uploader // Made nullable to support client uploads without accounts
     * @param array $metadata // Optional metadata for client uploads
     * @return ProjectFile
     * @throws FileUploadException|StorageLimitException
     */
    public function uploadProjectFile(Project $project, UploadedFile $file, ?User $uploader = null, array $metadata = []): ProjectFile
    {
        // Authorization is assumed to be handled by the caller (e.g., Policy check or signed URL validation)

        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        // Validate file size and project storage capacity
        // Fetch max size from config, provide a sensible default (e.g., 100MB in bytes)
        $maxFileSize = config('files.max_project_file_size', 100 * 1024 * 1024);
        if ($fileSize > $maxFileSize) {
            throw new FileUploadException("File '{$fileName}' ({$fileSize} bytes) exceeds the maximum allowed size of {$maxFileSize} bytes.");
        }
        if (!$project->hasStorageCapacity($fileSize)) { // Assume this instance method exists in Project model
            throw new StorageLimitException('Project storage limit reached. Cannot upload file.');
        }

        try {
            return DB::transaction(function () use ($project, $file, $fileName, $fileSize, $uploader, $metadata) {
                // Store the file securely
                $path = Storage::disk('s3')->putFileAs(
                    'projects/' . $project->id,
                    $file,
                    $fileName
                );

                $uploaderInfo = $uploader ? ['uploader_id' => $uploader->id] : ['client_upload' => true];
                Log::info('Project file uploaded to S3', array_merge([
                    'filename' => $fileName, 
                    'path' => $path, 
                    'project_id' => $project->id
                ], $uploaderInfo, $metadata));

                $projectFile = $project->files()->create([
                    'storage_path' => $path,
                    'file_path' => $path,
                    'file_name' => $fileName, // Store original name
                    'original_file_name' => $fileName,
                    'size' => $fileSize,
                    'user_id' => $uploader?->id, // Track uploader (null for client uploads)
                    'mime_type' => $file->getMimeType(),
                    'metadata' => !empty($metadata) ? json_encode($metadata) : null, // Store client upload metadata
                ]);

                // Atomically update project storage usage
                $project->incrementStorageUsed($fileSize); // Assume this method exists

                return $projectFile;
            });
        } catch (\Exception $e) {
            $uploaderInfo = $uploader ? ['uploader_id' => $uploader->id] : ['client_upload' => true];
            Log::error('Error uploading project file', array_merge([
                'project_id' => $project->id, 
                'filename' => $fileName, 
                'error' => $e->getMessage()
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
     * @param Pitch $pitch
     * @param UploadedFile $file
     * @param User $uploader // Keep uploader to associate file record
     * @return PitchFile
     * @throws FileUploadException|StorageLimitException
     */
    public function uploadPitchFile(Pitch $pitch, UploadedFile $file, User $uploader): PitchFile
    {
        // Authorization and Status checks are assumed to be handled by the caller (e.g., Policy check)

        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        // File size / Pitch storage limits
        // Fetch max size from config, provide a sensible default (e.g., 100MB in bytes)
        $maxFileSize = config('files.max_pitch_file_size', 100 * 1024 * 1024);
        if ($fileSize > $maxFileSize) {
            throw new FileUploadException("File '{$fileName}' ({$fileSize} bytes) exceeds the maximum allowed size of {$maxFileSize} bytes.");
        }
        if (!$pitch->hasStorageCapacity($fileSize)) { // Assume instance method in Pitch model
            throw new StorageLimitException('Pitch storage limit exceeded. Cannot upload file.');
        }

        try {
            return DB::transaction(function () use ($pitch, $file, $fileName, $fileSize, $uploader) {
                // Store the file securely
                $path = Storage::disk('s3')->putFileAs(
                    'pitches/' . $pitch->id,
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

                // Update pitch storage usage
                $pitch->incrementStorageUsed($fileSize); // Assume this method exists

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
     * @param ProjectFile $projectFile
     * @return bool
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

                if (!$deleted) {
                    throw new FileDeletionException('Failed to delete file record from database.');
                }

                // Decrement storage used - only if file size is valid
                if ($fileSize > 0 && $project) {
                    try {
                        $project->decrementStorageUsed($fileSize);
                    } catch (\Exception $storageEx) {
                        Log::warning('Failed to decrement storage usage during file deletion', [
                            'project_id' => $project->id,
                            'file_size' => $fileSize,
                            'error' => $storageEx->getMessage()
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

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Error deleting project file', ['file_id' => $projectFile->id, 'error' => $e->getMessage()]);
            throw new FileDeletionException('Failed to delete project file: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete a Pitch file.
     * Authorization and status validation should be checked before calling this method.
     *
     * @param PitchFile $pitchFile
     * @return bool
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
                    // Decrement storage used
                    $pitch->decrementStorageUsed($fileSize); // Assume this method exists

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
                } else {
                     throw new FileDeletionException('Failed to delete file record from database.');
                }
            });
            return true;
        } catch (\Exception $e) {
            Log::error('Error deleting pitch file', ['file_id' => $pitchFile->id, 'error' => $e->getMessage()]);
            throw new FileDeletionException('Failed to delete pitch file: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate a temporary download or streaming URL for a file.
     *
     * @param ProjectFile|PitchFile $fileModel
     * @param int $minutes Expiration time in minutes
     * @param bool $forceDownload If true, sets headers to force download; if false, allows streaming/inline display.
     * @return string
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
                    'ResponseContentDisposition' => 'attachment; filename="' . addslashes($fileName) . '"'
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
                 'error' => $e->getMessage()
             ]);
             throw new \RuntimeException('Could not generate download URL.', 0, $e);
        }
    }

    /**
     * Set the preview track for a project.
     * Authorization should be checked before calling this method.
     *
     * @param Project $project
     * @param ProjectFile $file
     * @return void
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
     *
     * @param Project $project
     * @return void
     */
    public function clearProjectPreviewTrack(Project $project): void
    {
         // Authorization is assumed to be handled by the caller

        $project->preview_track = null;
        $project->save();
    }
} 
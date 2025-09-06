<?php

namespace App\Services;

use App\Exceptions\GoogleDrive\GoogleDriveAuthException;
use App\Exceptions\GoogleDrive\GoogleDriveFileException;
use App\Exceptions\GoogleDrive\GoogleDriveQuotaException;
use App\Models\User;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GoogleDriveService
{
    protected ?GoogleClient $client = null;

    protected ?GoogleDrive $driveService = null;

    protected FileValidationService $fileValidationService;

    protected UserStorageService $userStorageService;

    public function __construct(
        FileValidationService $fileValidationService,
        UserStorageService $userStorageService
    ) {
        $this->fileValidationService = $fileValidationService;
        $this->userStorageService = $userStorageService;
    }

    /**
     * Initialize Google Client lazily
     */
    protected function initializeClient(): void
    {
        if ($this->client !== null) {
            return;
        }

        $this->client = new GoogleClient;
        $this->client->setClientId(config('googledrive.oauth.client_id'));
        $this->client->setClientSecret(config('googledrive.oauth.client_secret'));
        $this->client->setRedirectUri(url(config('googledrive.oauth.redirect_uri')));
        $this->client->setScopes(config('googledrive.oauth.scopes'));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        $this->driveService = new GoogleDrive($this->client);
    }

    /**
     * Get Google OAuth authorization URL
     */
    public function getAuthUrl(): string
    {
        $this->initializeClient();

        return $this->client->createAuthUrl();
    }

    /**
     * Get Google OAuth authorization URL for a specific user
     */
    public function getAuthorizationUrl(User $user): string
    {
        return $this->getAuthUrl();
    }

    /**
     * Handle OAuth callback and store tokens
     *
     * @throws GoogleDriveAuthException
     */
    public function handleCallback(string $code, User $user): void
    {
        $this->initializeClient();

        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                throw new GoogleDriveAuthException('OAuth error: '.$token['error_description'] ?? $token['error']);
            }

            $this->storeTokens($user, $token);
        } catch (GoogleServiceException $e) {
            Log::error('Google Drive OAuth callback failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new GoogleDriveAuthException('Failed to complete Google Drive authorization: '.$e->getMessage());
        }
    }

    /**
     * Store encrypted OAuth tokens for user
     */
    protected function storeTokens(User $user, array $token): void
    {
        $user->google_drive_tokens = Crypt::encryptString(json_encode($token));
        $user->google_drive_connected_at = now();
        $user->save();
    }

    /**
     * Get decrypted tokens for user
     *
     * @throws GoogleDriveAuthException
     */
    protected function getTokens(User $user): array
    {
        if (! $user->google_drive_tokens) {
            throw new GoogleDriveAuthException('User has not connected Google Drive');
        }

        try {
            return json_decode(Crypt::decryptString($user->google_drive_tokens), true);
        } catch (\Exception $e) {
            Log::error('Failed to decrypt Google Drive tokens', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw new GoogleDriveAuthException('Failed to decrypt Google Drive tokens');
        }
    }

    /**
     * Set up client with user's tokens and refresh if needed
     *
     * @throws GoogleDriveAuthException
     */
    protected function setupClientForUser(User $user): void
    {
        $this->initializeClient();

        $tokens = $this->getTokens($user);
        $this->client->setAccessToken($tokens);

        // Check if token needs refresh
        if ($this->client->isAccessTokenExpired()) {
            if (! $this->client->getRefreshToken()) {
                throw new GoogleDriveAuthException('Refresh token not available. User needs to re-authorize.');
            }

            try {
                $newTokens = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());

                if (isset($newTokens['error'])) {
                    throw new GoogleDriveAuthException('Token refresh failed: '.$newTokens['error_description'] ?? $newTokens['error']);
                }

                $this->storeTokens($user, $newTokens);
            } catch (GoogleServiceException $e) {
                Log::error('Google Drive token refresh failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                throw new GoogleDriveAuthException('Failed to refresh Google Drive token: '.$e->getMessage());
            }
        }
    }

    /**
     * List files in user's Google Drive
     *
     * @throws GoogleDriveAuthException|GoogleDriveFileException
     */
    public function listFiles(
        User $user,
        ?string $folderId = null,
        ?string $searchQuery = null,
        ?int $pageSize = null,
        ?string $pageToken = null
    ): array {
        $this->setupClientForUser($user);

        $pageSize = $pageSize ?? config('googledrive.ui.files_per_page', 50);

        try {
            $query = $this->buildFileQuery($folderId, $searchQuery);
            $parameters = [
                'q' => $query,
                'pageSize' => $pageSize,
                'fields' => 'nextPageToken, files(id, name, mimeType, size, modifiedTime, thumbnailLink, webViewLink, parents)',
                'orderBy' => 'modifiedTime desc',
            ];

            if ($pageToken) {
                $parameters['pageToken'] = $pageToken;
            }

            $results = $this->driveService->files->listFiles($parameters);

            // Build breadcrumbs if we're in a specific folder
            $breadcrumbs = [];
            if ($folderId && $folderId !== 'root') {
                $breadcrumbs = $this->buildBreadcrumbs($folderId);
            } else {
                $breadcrumbs = [['id' => 'root', 'name' => 'My Drive']];
            }

            return [
                'files' => $this->formatFiles($results->getFiles()),
                'breadcrumbs' => $breadcrumbs,
                'nextPageToken' => $results->getNextPageToken(),
            ];
        } catch (GoogleServiceException $e) {
            Log::error('Failed to list Google Drive files', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'folder_id' => $folderId,
            ]);
            throw new GoogleDriveFileException('Failed to list Google Drive files: '.$e->getMessage());
        }
    }

    /**
     * Build query for file listing based on configuration
     */
    protected function buildFileQuery(?string $folderId, ?string $searchQuery = null): string
    {
        $conditions = [];

        // If search query provided, search globally instead of limiting to folder
        if ($searchQuery) {
            $conditions[] = "name contains '{$searchQuery}'";
        } else {
            // Filter by parent folder
            if ($folderId) {
                $conditions[] = "'{$folderId}' in parents";
            } else {
                $conditions[] = "'root' in parents";
            }
        }

        // Filter by allowed mime types (including folders for navigation)
        $allowedTypes = config('googledrive.file_handling.allowed_mime_types', []);
        if (! empty($allowedTypes)) {
            $mimeConditions = array_map(fn ($type) => "mimeType='{$type}'", $allowedTypes);
            // Always include folders for navigation
            $mimeConditions[] = "mimeType='application/vnd.google-apps.folder'";
            $conditions[] = '('.implode(' or ', $mimeConditions).')';
        }

        // Exclude trashed files
        $conditions[] = 'trashed=false';

        return implode(' and ', $conditions);
    }

    /**
     * Build breadcrumb navigation for folder path
     */
    protected function buildBreadcrumbs(string $folderId): array
    {
        $breadcrumbs = [];
        $currentFolderId = $folderId;

        try {
            // Build path from current folder back to root
            while ($currentFolderId && $currentFolderId !== 'root') {
                $folder = $this->driveService->files->get($currentFolderId, [
                    'fields' => 'id, name, parents',
                ]);

                array_unshift($breadcrumbs, [
                    'id' => $folder->getId(),
                    'name' => $folder->getName(),
                ]);

                // Get parent folder
                $parents = $folder->getParents();
                $currentFolderId = $parents ? $parents[0] : null;
            }

            // Add root folder at the beginning
            array_unshift($breadcrumbs, ['id' => 'root', 'name' => 'My Drive']);

        } catch (GoogleServiceException $e) {
            Log::warning('Failed to build breadcrumbs', [
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
            // Fallback to just showing current folder
            $breadcrumbs = [
                ['id' => 'root', 'name' => 'My Drive'],
                ['id' => $folderId, 'name' => 'Current Folder'],
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Format files for consistent API response
     */
    protected function formatFiles(array $files): array
    {
        return array_map(function ($file) {
            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize(),
                'modifiedTime' => $file->getModifiedTime(),
                'thumbnailLink' => $file->getThumbnailLink(),
                'webViewLink' => $file->getWebViewLink(),
                'isAudio' => $this->isAudioFile($file->getMimeType()),
                'formattedSize' => $this->formatFileSize($file->getSize()),
            ];
        }, $files);
    }

    /**
     * Check if file is audio based on mime type
     */
    protected function isAudioFile(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'audio/');
    }

    /**
     * Format file size in human readable format
     */
    protected function formatFileSize(?string $size): string
    {
        if (! $size) {
            return 'Unknown';
        }

        $bytes = (int) $size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return round($bytes / pow(1024, $power), 2).' '.$units[$power];
    }

    /**
     * Download file from Google Drive to local storage
     *
     * @throws GoogleDriveAuthException|GoogleDriveFileException|GoogleDriveQuotaException
     */
    public function downloadFile(User $user, string $fileId): array
    {
        $this->setupClientForUser($user);

        try {
            // Get file metadata first
            $file = $this->driveService->files->get($fileId, ['fields' => 'id, name, size, mimeType']);

            // Validate file
            $this->validateFileForDownload($user, $file);

            // Download file content
            $response = $this->driveService->files->get($fileId, ['alt' => 'media']);
            $content = $response->getBody()->getContents();

            // Store file locally
            $fileName = $this->generateUniqueFileName($file->getName());
            $path = 'google-drive-imports/'.$user->id.'/'.$fileName;

            Storage::put($path, $content);

            Log::info('Google Drive file downloaded successfully', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'file_name' => $file->getName(),
                'local_path' => $path,
            ]);

            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'size' => $file->getSize(),
                'mimeType' => $file->getMimeType(),
                'localPath' => $path,
                'temporaryUrl' => Storage::temporaryUrl($path, now()->addHours(1)),
            ];

        } catch (GoogleServiceException $e) {
            Log::error('Failed to download Google Drive file', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            throw new GoogleDriveFileException('Failed to download file: '.$e->getMessage());
        }
    }

    /**
     * Validate file before download
     *
     * @throws GoogleDriveFileException|GoogleDriveQuotaException
     */
    protected function validateFileForDownload(User $user, DriveFile $file): void
    {
        // Check file size limits
        $maxSize = config('googledrive.file_handling.max_file_size_mb') * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            throw new GoogleDriveFileException('File size exceeds maximum allowed size');
        }

        // Check mime type
        $allowedTypes = config('googledrive.file_handling.allowed_mime_types');
        if (! empty($allowedTypes) && ! in_array($file->getMimeType(), $allowedTypes)) {
            throw new GoogleDriveFileException('File type not supported');
        }

        // Check user storage quota
        $remainingStorage = $this->userStorageService->getUserStorageRemaining($user);
        if ($file->getSize() > $remainingStorage) {
            throw new GoogleDriveQuotaException('Insufficient storage space to download file');
        }
    }

    /**
     * Generate unique file name to avoid conflicts
     */
    protected function generateUniqueFileName(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);

        return $baseName.'_'.Str::random(8).'.'.$extension;
    }

    /**
     * Disconnect Google Drive for user
     */
    public function disconnect(User $user): void
    {
        try {
            // Revoke token if possible
            if ($user->google_drive_tokens) {
                $this->initializeClient();
                $tokens = $this->getTokens($user);
                $this->client->setAccessToken($tokens);
                $this->client->revokeToken();
            }
        } catch (\Exception $e) {
            Log::warning('Failed to revoke Google Drive token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Clear stored data
        $user->google_drive_tokens = null;
        $user->google_drive_connected_at = null;
        $user->save();

        // Clear any cached data
        Cache::forget("google_drive_files_{$user->id}");

        Log::info('Google Drive disconnected for user', ['user_id' => $user->id]);
    }

    /**
     * Check if user has Google Drive connected
     */
    public function isConnected(User $user): bool
    {
        return ! is_null($user->google_drive_tokens);
    }

    /**
     * Get connection status with additional details
     */
    public function getConnectionStatus(User $user): array
    {
        if (! $this->isConnected($user)) {
            return [
                'connected' => false,
                'connected_at' => null,
                'needs_reauth' => false,
            ];
        }

        try {
            $this->setupClientForUser($user);

            return [
                'connected' => true,
                'connected_at' => $user->google_drive_connected_at,
                'needs_reauth' => false,
            ];
        } catch (GoogleDriveAuthException $e) {
            return [
                'connected' => false,
                'connected_at' => $user->google_drive_connected_at,
                'needs_reauth' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Import a Google Drive file directly to a Project or Pitch model
     *
     * @param  string  $fileId  Google Drive file ID
     * @param  Model  $model  Project or Pitch model to attach the file to
     * @return array Result with success status and file info
     *
     * @throws GoogleDriveAuthException|GoogleDriveFileException
     */
    public function importFileToModel(User $user, string $fileId, Model $model): array
    {
        try {
            $this->setupClientForUser($user);

            // Get file metadata first
            $file = $this->driveService->files->get($fileId, [
                'fields' => 'id,name,mimeType,size,parents',
            ]);

            // Validate file can be imported to this model type
            if (! $this->canImportToModel($model, $file->getMimeType())) {
                throw new GoogleDriveFileException('File type not allowed for this model');
            }

            // Check file size limits based on model context
            $maxSize = $this->getMaxFileSizeForModel($model);
            if ($file->getSize() && $file->getSize() > $maxSize) {
                throw new GoogleDriveFileException("File too large. Maximum size is {$this->formatFileSize($maxSize)}");
            }

            // Download file content
            $response = $this->driveService->files->get($fileId, ['alt' => 'media']);
            $content = $response->getBody()->getContents();

            // Generate unique filename
            $originalName = $file->getName();
            $uniqueName = $this->generateUniqueFileName($originalName);

            // Store file in appropriate S3 path
            $s3Key = $this->generateS3KeyForModel($model, $uniqueName);
            Storage::disk('s3')->put($s3Key, $content, [
                'visibility' => 'public',
                'Content-Type' => $file->getMimeType(),
            ]);

            // Create file record using FileManagementService
            $fileManagementService = app(\App\Services\FileManagementService::class);

            if ($model instanceof \App\Models\Project) {
                $fileRecord = $fileManagementService->createProjectFileFromS3(
                    $model,
                    $s3Key,
                    $originalName,
                    $file->getSize() ?? 0,
                    $file->getMimeType(),
                    $user
                );
            } elseif ($model instanceof \App\Models\Pitch) {
                $fileRecord = $fileManagementService->createPitchFileFromS3(
                    $model,
                    $s3Key,
                    $originalName,
                    $file->getSize() ?? 0,
                    $file->getMimeType(),
                    $user
                );
            } else {
                throw new GoogleDriveFileException('Unsupported model type for file import');
            }

            Log::info('File imported from Google Drive successfully', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'original_name' => $originalName,
                'unique_name' => $uniqueName,
                's3_key' => $s3Key,
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'file_record_id' => $fileRecord->id,
            ]);

            return [
                'success' => true,
                'file_record' => $fileRecord,
                'original_name' => $originalName,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ];

        } catch (GoogleServiceException $e) {
            Log::error('Failed to import Google Drive file', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to import file: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check if file can be imported to the given model type
     */
    protected function canImportToModel(Model $model, string $mimeType): bool
    {
        // Get context-specific allowed mime types
        if ($model instanceof \App\Models\Project) {
            $context = \App\Models\FileUploadSetting::CONTEXT_PROJECTS;
        } elseif ($model instanceof \App\Models\Pitch) {
            $context = \App\Models\FileUploadSetting::CONTEXT_PITCHES;
        } else {
            return false;
        }

        // Check against upload settings
        $allowedTypes = config('googledrive.file_handling.allowed_mime_types', []);

        return in_array($mimeType, $allowedTypes) ||
               str_starts_with($mimeType, 'audio/') ||
               str_starts_with($mimeType, 'image/') ||
               $mimeType === 'application/pdf';
    }

    /**
     * Get maximum file size for the given model type
     */
    protected function getMaxFileSizeForModel(Model $model): int
    {
        if ($model instanceof \App\Models\Project) {
            $context = \App\Models\FileUploadSetting::CONTEXT_PROJECTS;
        } elseif ($model instanceof \App\Models\Pitch) {
            $context = \App\Models\FileUploadSetting::CONTEXT_PITCHES;
        } else {
            $context = \App\Models\FileUploadSetting::CONTEXT_GLOBAL;
        }

        $settings = \App\Models\FileUploadSetting::getSettings($context);
        $maxSizeMB = $settings[\App\Models\FileUploadSetting::MAX_FILE_SIZE_MB] ?? 200;

        return $maxSizeMB * 1024 * 1024; // Convert MB to bytes
    }

    /**
     * Generate S3 key for model-specific file storage
     */
    protected function generateS3KeyForModel(Model $model, string $filename): string
    {
        if ($model instanceof \App\Models\Project) {
            return "projects/{$model->id}/files/{$filename}";
        } elseif ($model instanceof \App\Models\Pitch) {
            return "pitches/{$model->id}/files/{$filename}";
        } else {
            return "uploads/{$filename}";
        }
    }

    /**
     * Backup a file from MixPitch to Google Drive
     *
     * @param  User  $user  User performing the backup
     * @param  int  $fileId  ID of the file to backup
     * @param  string  $fileType  Type of file (project_file or pitch_file)
     * @param  string  $destinationFolderId  Google Drive folder ID to upload to
     * @return array Result with success status and details
     *
     * @throws GoogleDriveAuthException|GoogleDriveFileException
     */
    public function backupFileToGoogleDrive(User $user, int $fileId, string $fileType, string $destinationFolderId): array
    {
        try {
            $this->setupClientForUser($user);

            // Get the file record based on type
            if ($fileType === 'project_file') {
                $fileRecord = \App\Models\ProjectFile::findOrFail($fileId);
            } elseif ($fileType === 'pitch_file') {
                $fileRecord = \App\Models\PitchFile::findOrFail($fileId);
            } else {
                throw new GoogleDriveFileException('Unsupported file type for backup');
            }

            // Validate that the file has a storage path
            $storagePath = $fileRecord->storage_path ?? $fileRecord->file_path;
            if (empty($storagePath)) {
                throw new GoogleDriveFileException("File '{$fileRecord->file_name}' does not have a valid storage location");
            }

            // Check if the file exists in S3
            if (! Storage::disk('s3')->exists($storagePath)) {
                throw new GoogleDriveFileException("File '{$fileRecord->file_name}' not found in storage at: {$storagePath}");
            }

            // Get the file content from S3
            $fileContent = Storage::disk('s3')->get($storagePath);

            // Create Google Drive file
            $driveFile = new DriveFile;
            $driveFile->setName($fileRecord->file_name);
            $driveFile->setParents([$destinationFolderId]);
            $driveFile->setMimeType($fileRecord->mime_type);

            // Upload file to Google Drive
            $result = $this->driveService->files->create(
                $driveFile,
                [
                    'data' => $fileContent,
                    'mimeType' => $fileRecord->mime_type,
                    'uploadType' => 'multipart',
                ]
            );

            Log::info('File backed up to Google Drive successfully', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'file_type' => $fileType,
                'file_name' => $fileRecord->file_name,
                'google_drive_file_id' => $result->getId(),
                'destination_folder_id' => $destinationFolderId,
            ]);

            return [
                'success' => true,
                'google_drive_file_id' => $result->getId(),
                'file_name' => $fileRecord->file_name,
                'size' => $fileRecord->size,
            ];

        } catch (GoogleServiceException $e) {
            Log::error('Failed to backup file to Google Drive', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'file_type' => $fileType,
                'destination_folder_id' => $destinationFolderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to backup file: '.$e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('Unexpected error during Google Drive backup', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'file_type' => $fileType,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'An unexpected error occurred: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Create a folder in Google Drive
     *
     * @param  User  $user  User creating the folder
     * @param  string  $folderName  Name of the folder to create
     * @param  string  $parentFolderId  Parent folder ID (default: 'root')
     * @return array Result with success status and folder details
     *
     * @throws GoogleDriveAuthException|GoogleDriveFileException
     */
    public function createFolder(User $user, string $folderName, string $parentFolderId = 'root'): array
    {
        try {
            $this->setupClientForUser($user);

            $driveFile = new DriveFile;
            $driveFile->setName($folderName);
            $driveFile->setParents([$parentFolderId]);
            $driveFile->setMimeType('application/vnd.google-apps.folder');

            $result = $this->driveService->files->create($driveFile);

            Log::info('Folder created in Google Drive', [
                'user_id' => $user->id,
                'folder_name' => $folderName,
                'parent_folder_id' => $parentFolderId,
                'created_folder_id' => $result->getId(),
            ]);

            return [
                'success' => true,
                'folder_id' => $result->getId(),
                'folder_name' => $folderName,
            ];

        } catch (GoogleServiceException $e) {
            Log::error('Failed to create folder in Google Drive', [
                'user_id' => $user->id,
                'folder_name' => $folderName,
                'parent_folder_id' => $parentFolderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create folder: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Backup multiple files to Google Drive in a batch operation
     *
     * @param  User  $user  User performing the backup
     * @param  array  $files  Array of files to backup [['id' => int, 'type' => string], ...]
     * @param  string  $destinationFolderId  Google Drive folder ID to upload to
     * @param  string|null  $projectFolderName  Optional project folder name to create
     * @return array Result with success/failure counts and details
     */
    public function batchBackupFiles(User $user, array $files, string $destinationFolderId, ?string $projectFolderName = null): array
    {
        $results = [
            'success_count' => 0,
            'failure_count' => 0,
            'total_count' => count($files),
            'errors' => [],
            'project_folder_id' => null,
        ];

        try {
            // Optionally create a project-specific folder
            $targetFolderId = $destinationFolderId;
            if ($projectFolderName) {
                $folderResult = $this->createFolder($user, $projectFolderName, $destinationFolderId);
                if ($folderResult['success']) {
                    $targetFolderId = $folderResult['folder_id'];
                    $results['project_folder_id'] = $targetFolderId;
                } else {
                    $results['errors'][] = "Failed to create project folder: {$folderResult['error']}";
                }
            }

            // Backup each file
            foreach ($files as $file) {
                try {
                    $backupResult = $this->backupFileToGoogleDrive(
                        $user,
                        $file['id'],
                        $file['type'],
                        $targetFolderId
                    );

                    if ($backupResult['success']) {
                        $results['success_count']++;
                    } else {
                        $results['failure_count']++;
                        $results['errors'][] = $backupResult['error'];
                    }
                } catch (\Exception $e) {
                    $results['failure_count']++;
                    $results['errors'][] = "Failed to backup file ID {$file['id']}: {$e->getMessage()}";
                }
            }

            Log::info('Batch backup completed', [
                'user_id' => $user->id,
                'success_count' => $results['success_count'],
                'failure_count' => $results['failure_count'],
                'total_count' => $results['total_count'],
            ]);

            return $results;

        } catch (\Exception $e) {
            Log::error('Batch backup failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $results['failure_count'] = $results['total_count'];
            $results['errors'][] = 'Batch backup failed: '.$e->getMessage();

            return $results;
        }
    }
}

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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
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

        $this->client = new GoogleClient();
        $this->client->setClientId(config('googledrive.oauth.client_id'));
        $this->client->setClientSecret(config('googledrive.oauth.client_secret'));
        $this->client->setRedirectUri(config('googledrive.oauth.redirect_uri'));
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
        if (!$user->google_drive_tokens) {
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
            if (!$this->client->getRefreshToken()) {
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
        ?int $pageSize = null,
        ?string $pageToken = null
    ): array {
        $this->setupClientForUser($user);

        $pageSize = $pageSize ?? config('googledrive.ui.files_per_page', 50);
        
        try {
            $query = $this->buildFileQuery($folderId);
            $parameters = [
                'q' => $query,
                'pageSize' => $pageSize,
                'fields' => 'nextPageToken, files(id, name, mimeType, size, modifiedTime, thumbnailLink, webViewLink)',
                'orderBy' => 'modifiedTime desc',
            ];

            if ($pageToken) {
                $parameters['pageToken'] = $pageToken;
            }

            $results = $this->driveService->files->listFiles($parameters);

            return [
                'files' => $this->formatFiles($results->getFiles()),
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
    protected function buildFileQuery(?string $folderId): string
    {
        $conditions = [];

        // Filter by parent folder
        if ($folderId) {
            $conditions[] = "'{$folderId}' in parents";
        } else {
            $conditions[] = "'root' in parents";
        }

        // Filter by allowed mime types
        $allowedTypes = config('googledrive.file_handling.allowed_mime_types', []);
        if (!empty($allowedTypes)) {
            $mimeConditions = array_map(fn($type) => "mimeType='{$type}'", $allowedTypes);
            $conditions[] = '('.implode(' or ', $mimeConditions).')';
        }

        // Exclude trashed files
        $conditions[] = 'trashed=false';

        return implode(' and ', $conditions);
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
        if (!$size) {
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
        if (!empty($allowedTypes) && !in_array($file->getMimeType(), $allowedTypes)) {
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
        return !is_null($user->google_drive_tokens);
    }

    /**
     * Get connection status with additional details
     */
    public function getConnectionStatus(User $user): array
    {
        if (!$this->isConnected($user)) {
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
}
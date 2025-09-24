<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LinkAnalysisService
{
    /**
     * Analyze a sharing link to extract file metadata.
     */
    public function analyzeLink(string $url): array
    {
        $domain = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        Log::info('Starting link analysis', [
            'url' => $url,
            'domain' => $domain,
        ]);

        return match (true) {
            str_contains($domain, 'wetransfer.com') || str_contains($domain, 'we.tl') => $this->analyzeWeTransfer($url),
            str_contains($domain, 'drive.google.com') => $this->analyzeGoogleDrive($url),
            str_contains($domain, 'dropbox.com') || str_contains($domain, 'db.tt') => $this->analyzeDropbox($url),
            str_contains($domain, '1drv.ms') || str_contains($domain, 'onedrive.live.com') => $this->analyzeOneDrive($url),
            default => throw new \InvalidArgumentException("Unsupported domain: {$domain}")
        };
    }

    /**
     * Analyze a WeTransfer link.
     */
    protected function analyzeWeTransfer(string $url): array
    {
        try {
            $userAgent = config('linkimport.processing.user_agent', 'MixPitch-LinkImporter/1.0');
            $timeout = config('linkimport.security.timeout_seconds', 60);

            Log::debug('Starting WeTransfer analysis', ['url' => $url]);

            // Step 1: Get the transfer ID from the URL
            $transferId = $this->extractWeTransferId($url);
            if (! $transferId) {
                throw new \Exception('Could not extract transfer ID from WeTransfer URL');
            }

            Log::debug('Extracted transfer ID', ['transfer_id' => $transferId]);

            // Step 2: Follow redirects to get the actual transfer page
            $transferPageUrl = $this->resolveWeTransferRedirects($url, $userAgent, $timeout);

            Log::debug('Resolved transfer page URL', ['transfer_page_url' => $transferPageUrl]);

            // Step 3: Fetch the transfer page and extract file information
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'User-Agent' => $userAgent,
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Referer' => $url,
                ])
                ->get($transferPageUrl);

            if (! $response->successful()) {
                throw new \Exception("Failed to access WeTransfer page. HTTP status: {$response->status()}");
            }

            $html = $response->body();

            // Extract security hash and recipient ID from the final URL
            $urlParts = $this->parseWeTransferDownloadUrl($transferPageUrl);

            $files = $this->parseWeTransferHtml($html, $transferId);

            // Add URL-extracted metadata to all files
            foreach ($files as &$file) {
                $file['security_hash'] = $file['security_hash'] ?? $urlParts['security_hash'] ?? null;
                $file['recipient_id'] = $file['recipient_id'] ?? $urlParts['recipient_id'] ?? null;
            }

            if (empty($files)) {
                throw new \Exception('No files found in WeTransfer link. The transfer may have expired or been deleted.');
            }

            Log::info('WeTransfer analysis completed', [
                'url' => $url,
                'transfer_id' => $transferId,
                'files_found' => count($files),
            ]);

            return $files;

        } catch (\Exception $e) {
            Log::error('WeTransfer analysis failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Parse WeTransfer HTML to extract file information.
     */
    protected function parseWeTransferHtml(string $html, string $transferId): array
    {
        $files = [];

        Log::debug('Parsing WeTransfer HTML', [
            'html_length' => strlen($html),
            'transfer_id' => $transferId,
        ]);

        // Pattern 1: Look for Next.js server-side props (modern WeTransfer)
        if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.+?)<\/script>/', $html, $matches)) {
            try {
                $data = json_decode($matches[1], true);
                Log::debug('Found __NEXT_DATA__ structure', ['data_keys' => array_keys($data ?? [])]);

                // Look for file data in Next.js props
                $props = $data['props']['pageProps'] ?? null;
                if ($props) {
                    // Modern WeTransfer structure: metadata contains the file info
                    if (isset($props['metadata'])) {
                        $metadata = $props['metadata'];
                        $securityHash = $props['securityHash'] ?? null;
                        $transferIdFull = $metadata['id'] ?? $transferId;

                        // Check if this is a multi-file transfer
                        $filesCount = $metadata['files_count'] ?? 1;
                        $isMultiFile = $filesCount > 1;

                        Log::debug('WeTransfer metadata analysis', [
                            'files_count' => $filesCount,
                            'is_multi_file' => $isMultiFile,
                            'transfer_id' => $transferIdFull,
                            'metadata_keys' => array_keys($metadata),
                        ]);

                        if ($isMultiFile) {
                            // For multi-file transfers, we need to fetch individual file details
                            // The title might just be one file name, not all files
                            Log::info('Multi-file transfer detected, attempting to fetch individual file details', [
                                'files_count' => $filesCount,
                                'title' => $metadata['title'] ?? 'unknown',
                            ]);

                            // Try to get individual file information from the transfer API
                            $multiFiles = $this->fetchWeTransferFileList($transferIdFull, $securityHash);

                            if (! empty($multiFiles)) {
                                $files = array_merge($files, $multiFiles);
                                Log::debug('Successfully fetched multi-file details', [
                                    'files_found' => count($multiFiles),
                                ]);
                            } else {
                                // Fallback: Create placeholder entries based on files_count
                                for ($i = 1; $i <= $filesCount; $i++) {
                                    $placeholderName = $i === 1 && isset($metadata['title'])
                                        ? $metadata['title']
                                        : "File_{$i}";

                                    $files[] = [
                                        'filename' => $placeholderName,
                                        'size' => $metadata['size'] ?? 0,
                                        'mime_type' => $this->guessMimeType($placeholderName),
                                        'file_id' => null, // Will need to be resolved during download
                                        'transfer_id' => $transferIdFull,
                                        'security_hash' => $securityHash,
                                        'recipient_id' => null,
                                        'download_url' => null,
                                        'placeholder' => $i > 1, // Mark as placeholder except first file
                                    ];
                                }

                                Log::debug('Created placeholder files for multi-file transfer', [
                                    'files_created' => $filesCount,
                                ]);
                            }
                        } else {
                            // Single file transfer (existing logic)
                            if (isset($metadata['title'])) {
                                $filename = $metadata['title'];
                                $files[] = [
                                    'filename' => $filename,
                                    'size' => $metadata['size'] ?? 0,
                                    'mime_type' => $this->guessMimeType($filename),
                                    'file_id' => $metadata['id'] ?? null,
                                    'transfer_id' => $transferIdFull,
                                    'security_hash' => $securityHash,
                                    'recipient_id' => null,
                                    'download_url' => null,
                                ];

                                Log::debug('Extracted file from WeTransfer metadata', [
                                    'filename' => $filename,
                                    'transfer_id' => $transferIdFull,
                                    'security_hash' => $securityHash,
                                ]);
                            }
                        }
                    }

                    // Legacy structure: check for transfer object (keeping for compatibility)
                    if (empty($files) && isset($props['transfer'])) {
                        $transfer = $props['transfer'];

                        // Extract file from transfer data
                        if (isset($transfer['title'])) {
                            $filename = $transfer['title'];
                            $files[] = [
                                'filename' => $filename,
                                'size' => $transfer['size'] ?? 0,
                                'mime_type' => $this->guessMimeType($filename),
                                'file_id' => $transfer['id'] ?? null,
                                'transfer_id' => $transferId,
                                'security_hash' => $transfer['security_hash'] ?? null,
                                'recipient_id' => $transfer['recipient_id'] ?? null,
                                'download_url' => null, // Will be generated via API
                            ];
                        }

                        // Also check for files array if it exists
                        if (isset($transfer['files']) && is_array($transfer['files'])) {
                            foreach ($transfer['files'] as $file) {
                                $files[] = [
                                    'filename' => $file['name'] ?? $file['title'] ?? 'unknown',
                                    'size' => $file['size'] ?? 0,
                                    'mime_type' => $this->guessMimeType($file['name'] ?? $file['title'] ?? ''),
                                    'file_id' => $file['id'] ?? null,
                                    'transfer_id' => $transferId,
                                    'security_hash' => $transfer['security_hash'] ?? null,
                                    'recipient_id' => $transfer['recipient_id'] ?? null,
                                    'download_url' => null,
                                ];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::debug('Failed to parse WeTransfer __NEXT_DATA__ JSON', ['error' => $e->getMessage()]);
            }
        }

        // Pattern 2: Look for JavaScript state data (legacy WeTransfer)
        if (empty($files) && preg_match('/window\.__INITIAL_STATE__\s*=\s*({.+?});/', $html, $matches)) {
            try {
                $data = json_decode($matches[1], true);
                Log::debug('Found __INITIAL_STATE__ data', ['data_keys' => array_keys($data ?? [])]);

                if (isset($data['transfer']['files'])) {
                    foreach ($data['transfer']['files'] as $file) {
                        $files[] = [
                            'filename' => $file['name'] ?? 'unknown',
                            'size' => $file['size'] ?? 0,
                            'mime_type' => $this->guessMimeType($file['name'] ?? ''),
                            'file_id' => $file['id'] ?? null,
                            'transfer_id' => $transferId,
                            'security_hash' => $data['transfer']['security_hash'] ?? null,
                            'recipient_id' => $data['transfer']['recipient_id'] ?? null,
                            'download_url' => null, // Will be generated later
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::debug('Failed to parse WeTransfer __INITIAL_STATE__ JSON', ['error' => $e->getMessage()]);
            }
        }

        // Pattern 2: Look for window.transfer or similar data
        if (empty($files) && preg_match('/window\.transfer\s*=\s*({.+?});/', $html, $matches)) {
            try {
                $data = json_decode($matches[1], true);
                Log::debug('Found window.transfer data', ['data_keys' => array_keys($data ?? [])]);

                if (isset($data['files'])) {
                    foreach ($data['files'] as $file) {
                        $files[] = [
                            'filename' => $file['name'] ?? 'unknown',
                            'size' => $file['size'] ?? 0,
                            'mime_type' => $this->guessMimeType($file['name'] ?? ''),
                            'file_id' => $file['id'] ?? null,
                            'transfer_id' => $transferId,
                            'download_url' => null,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::debug('Failed to parse window.transfer JSON', ['error' => $e->getMessage()]);
            }
        }

        // Pattern 3: Look for any JSON containing files array
        if (empty($files)) {
            preg_match_all('/("files"\s*:\s*\[.*?\])/s', $html, $jsonMatches);
            foreach ($jsonMatches[1] as $jsonMatch) {
                try {
                    $jsonData = json_decode('{'.$jsonMatch.'}', true);
                    if (isset($jsonData['files']) && is_array($jsonData['files'])) {
                        foreach ($jsonData['files'] as $file) {
                            if (is_array($file) && isset($file['name'])) {
                                $files[] = [
                                    'filename' => $file['name'],
                                    'size' => $file['size'] ?? 0,
                                    'mime_type' => $this->guessMimeType($file['name']),
                                    'file_id' => $file['id'] ?? null,
                                    'transfer_id' => $transferId,
                                    'download_url' => null,
                                ];
                            }
                        }
                        if (! empty($files)) {
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Pattern 4: Look for data attributes in HTML elements
        if (empty($files)) {
            preg_match_all('/data-filename="([^"]+)"/', $html, $filenames);
            preg_match_all('/data-filesize="(\d+)"/', $html, $filesizes);
            preg_match_all('/data-fileid="([^"]+)"/', $html, $fileids);

            $count = count($filenames[1]);
            for ($i = 0; $i < $count; $i++) {
                $filename = $filenames[1][$i] ?? 'unknown';
                $size = (int) ($filesizes[1][$i] ?? 0);
                $fileId = $fileids[1][$i] ?? null;

                $files[] = [
                    'filename' => $filename,
                    'size' => $size,
                    'mime_type' => $this->guessMimeType($filename),
                    'file_id' => $fileId,
                    'transfer_id' => $transferId,
                    'download_url' => null,
                ];
            }
        }

        // Pattern 5: Extract from page title as fallback
        if (empty($files)) {
            if (preg_match('/<title>([^<]+)<\/title>/', $html, $titleMatch)) {
                $title = $titleMatch[1];
                // Look for file extensions in title
                if (preg_match('/([^\/]+\.(mp3|wav|flac|pdf|zip|jpg|png|aiff|m4a|aac))/i', $title, $fileMatch)) {
                    $files[] = [
                        'filename' => $fileMatch[1],
                        'size' => 0,
                        'mime_type' => $this->guessMimeType($fileMatch[1]),
                        'file_id' => null,
                        'transfer_id' => $transferId,
                        'download_url' => null,
                    ];
                }
            }
        }

        Log::debug('WeTransfer HTML parsing completed', [
            'files_found' => count($files),
            'file_names' => array_column($files, 'filename'),
        ]);

        return $files;
    }

    /**
     * Extract WeTransfer transfer ID from URL.
     */
    protected function extractWeTransferId(string $url): ?string
    {
        // Handle various WeTransfer URL formats:
        // https://we.tl/t-XXXXXXXXXX
        // https://wetransfer.com/downloads/XXXXXXXXXX
        // https://wetransfer.com/downloads/XXXXXXXXXX/YYYYYYYY

        if (preg_match('/we\.tl\/t-([a-zA-Z0-9]+)/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('/wetransfer\.com\/downloads\/([a-zA-Z0-9]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Resolve WeTransfer redirects to get the actual transfer page URL.
     */
    protected function resolveWeTransferRedirects(string $url, string $userAgent, int $timeout): string
    {
        $maxRedirects = config('linkimport.security.max_redirects', 5);
        $currentUrl = $url;
        $redirectCount = 0;

        while ($redirectCount < $maxRedirects) {
            Log::debug('Following WeTransfer redirect', [
                'current_url' => $currentUrl,
                'redirect_count' => $redirectCount,
            ]);

            $response = Http::timeout($timeout)
                ->withHeaders([
                    'User-Agent' => $userAgent,
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->withoutRedirecting()
                ->get($currentUrl);

            if ($response->redirect()) {
                $location = $response->header('Location');
                if ($location) {
                    // Handle relative redirects
                    if (! parse_url($location, PHP_URL_HOST)) {
                        $parsed = parse_url($currentUrl);
                        $baseUrl = $parsed['scheme'].'://'.$parsed['host'];
                        $location = $baseUrl.(str_starts_with($location, '/') ? '' : '/').$location;
                    }

                    $currentUrl = $location;
                    $redirectCount++;

                    continue;
                }
            }

            // No more redirects, we've found the final URL
            break;
        }

        if ($redirectCount >= $maxRedirects) {
            throw new \Exception('Too many redirects while resolving WeTransfer URL');
        }

        return $currentUrl;
    }

    /**
     * Generate WeTransfer download URL for a file.
     */
    public function generateWeTransferDownloadUrl(string $transferId, string $fileId): string
    {
        // WeTransfer download URLs typically follow this pattern
        // This may need to be adjusted based on actual WeTransfer API
        return "https://wetransfer.com/api/v4/transfers/{$transferId}/download/{$fileId}";
    }

    /**
     * Analyze a Google Drive link.
     */
    protected function analyzeGoogleDrive(string $url): array
    {
        try {
            Log::info('Starting Google Drive analysis', ['url' => $url]);

            // Extract ID and determine if it's a file or folder
            $driveInfo = $this->parseGoogleDriveUrl($url);

            if (! $driveInfo) {
                throw new \Exception('Could not parse Google Drive URL');
            }

            Log::debug('Parsed Google Drive URL', $driveInfo);

            if ($driveInfo['type'] === 'file') {
                return $this->analyzeGoogleDriveFile($driveInfo['id']);
            } else {
                return $this->analyzeGoogleDriveFolder($driveInfo['id']);
            }

        } catch (\Exception $e) {
            Log::error('Google Drive analysis failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Parse Google Drive URL to extract ID and determine type (file or folder).
     */
    protected function parseGoogleDriveUrl(string $url): ?array
    {
        // Parse different Google Drive URL patterns
        $patterns = [
            // File URLs
            '/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/' => 'file',
            '/drive\.google\.com\/open\?id=([a-zA-Z0-9_-]+)/' => 'file',

            // Folder URLs
            '/drive\.google\.com\/drive\/folders\/([a-zA-Z0-9_-]+)/' => 'folder',
            '/drive\.google\.com\/drive\/u\/\d+\/folders\/([a-zA-Z0-9_-]+)/' => 'folder',
        ];

        foreach ($patterns as $pattern => $type) {
            if (preg_match($pattern, $url, $matches)) {
                return [
                    'id' => $matches[1],
                    'type' => $type,
                ];
            }
        }

        return null;
    }

    /**
     * Analyze a single Google Drive file.
     */
    protected function analyzeGoogleDriveFile(string $fileId): array
    {
        $apiKey = config('linkimport.google_drive.api_key');

        // If we have an API key, try to get metadata via API
        if ($apiKey) {
            return $this->analyzeGoogleDriveFileWithApi($fileId);
        }

        // Fallback: Create file info with direct download URL and let the download job handle it
        Log::info('No Google Drive API key configured, using direct download approach', ['file_id' => $fileId]);

        return [
            [
                'filename' => "GoogleDrive_File_{$fileId}", // Placeholder name
                'size' => 0, // Unknown size
                'mime_type' => 'application/octet-stream', // Unknown type initially
                'file_id' => $fileId,
                'download_url' => $this->buildGoogleDriveDirectDownloadUrl($fileId),
                'metadata' => ['id' => $fileId, 'source' => 'google_drive_direct'],
                'requires_fallback' => true, // Flag for the job to handle differently
            ],
        ];
    }

    /**
     * Analyze Google Drive file using API (when API key is available).
     */
    protected function analyzeGoogleDriveFileWithApi(string $fileId): array
    {
        $apiKey = config('linkimport.google_drive.api_key');
        $baseUrl = config('linkimport.google_drive.base_url');
        $timeout = config('linkimport.google_drive.timeout_seconds', 60);

        Log::debug('Fetching Google Drive file metadata via API', ['file_id' => $fileId]);

        $response = Http::timeout($timeout)->get("{$baseUrl}/files/{$fileId}", [
            'key' => $apiKey,
            'fields' => 'id,name,mimeType,size,webContentLink',
        ]);

        if (! $response->successful()) {
            if ($response->status() === 404) {
                throw new \Exception('Google Drive file not found or not publicly accessible');
            }
            if ($response->status() === 403) {
                throw new \Exception('Google Drive file is private and requires authentication');
            }
            throw new \Exception("Google Drive API error: {$response->status()}");
        }

        $fileData = $response->json();

        return [
            [
                'filename' => $fileData['name'],
                'size' => (int) ($fileData['size'] ?? 0),
                'mime_type' => $fileData['mimeType'] ?? 'application/octet-stream',
                'file_id' => $fileData['id'],
                'download_url' => $this->buildGoogleDriveDownloadUrl($fileData['id']),
                'metadata' => $fileData,
            ],
        ];
    }

    /**
     * Build direct Google Drive download URL (no API key required).
     */
    protected function buildGoogleDriveDirectDownloadUrl(string $fileId): string
    {
        return "https://drive.google.com/uc?export=download&id={$fileId}";
    }

    /**
     * Analyze a Google Drive folder and list its files.
     */
    protected function analyzeGoogleDriveFolder(string $folderId): array
    {
        $apiKey = config('linkimport.google_drive.api_key');

        if (! $apiKey) {
            // For folders, we need API access to list contents
            // Without API key, we can't analyze folders
            throw new \Exception('Google Drive folders require API key for analysis. Please configure GOOGLE_DRIVE_API_KEY in your .env file.');
        }

        $baseUrl = config('linkimport.google_drive.base_url');
        $timeout = config('linkimport.google_drive.timeout_seconds', 60);
        $pageSize = config('linkimport.google_drive.page_size', 50);
        $maxFiles = config('linkimport.google_drive.max_files_per_folder', 100);

        Log::debug('Fetching Google Drive folder contents', ['folder_id' => $folderId]);

        $files = [];
        $nextPageToken = null;

        do {
            $params = [
                'key' => $apiKey,
                'q' => "'{$folderId}' in parents and trashed=false",
                'fields' => 'nextPageToken,files(id,name,mimeType,size,webContentLink)',
                'pageSize' => min($pageSize, $maxFiles - count($files)),
            ];

            if ($nextPageToken) {
                $params['pageToken'] = $nextPageToken;
            }

            $response = Http::timeout($timeout)->get("{$baseUrl}/files", $params);

            if (! $response->successful()) {
                if ($response->status() === 404) {
                    throw new \Exception('Google Drive folder not found or not publicly accessible');
                }
                if ($response->status() === 403) {
                    throw new \Exception('Google Drive folder is private and requires authentication');
                }
                throw new \Exception("Google Drive API error: {$response->status()}");
            }

            $responseData = $response->json();
            $folderFiles = $responseData['files'] ?? [];

            // Filter out folders (we only want files for import)
            $folderFiles = array_filter($folderFiles, function ($file) {
                return $file['mimeType'] !== 'application/vnd.google-apps.folder';
            });

            foreach ($folderFiles as $file) {
                $files[] = [
                    'filename' => $file['name'],
                    'size' => (int) ($file['size'] ?? 0),
                    'mime_type' => $file['mimeType'] ?? 'application/octet-stream',
                    'file_id' => $file['id'],
                    'download_url' => $this->buildGoogleDriveDownloadUrl($file['id']),
                    'metadata' => $file,
                ];

                // Respect max files limit
                if (count($files) >= $maxFiles) {
                    Log::warning('Google Drive folder contains more files than limit', [
                        'folder_id' => $folderId,
                        'max_files' => $maxFiles,
                        'files_found' => count($files),
                    ]);
                    break 2;
                }
            }

            $nextPageToken = $responseData['nextPageToken'] ?? null;

        } while ($nextPageToken && count($files) < $maxFiles);

        if (empty($files)) {
            throw new \Exception('No accessible files found in Google Drive folder');
        }

        Log::info('Google Drive folder analysis completed', [
            'folder_id' => $folderId,
            'files_found' => count($files),
        ]);

        return $files;
    }

    /**
     * Build Google Drive download URL for a file.
     */
    protected function buildGoogleDriveDownloadUrl(string $fileId): string
    {
        $apiKey = config('linkimport.google_drive.api_key');
        $baseUrl = config('linkimport.google_drive.base_url');

        return "{$baseUrl}/files/{$fileId}?alt=media&key={$apiKey}";
    }

    /**
     * Analyze a Dropbox link.
     */
    protected function analyzeDropbox(string $url): array
    {
        // For now, return a placeholder - Dropbox analysis would require API integration
        Log::info('Dropbox analysis requested', ['url' => $url]);

        return [
            [
                'filename' => 'Dropbox_File',
                'size' => 0,
                'mime_type' => 'application/octet-stream',
                'download_url' => null,
            ],
        ];
    }

    /**
     * Analyze a OneDrive link.
     */
    protected function analyzeOneDrive(string $url): array
    {
        // For now, return a placeholder - OneDrive analysis would require API integration
        Log::info('OneDrive analysis requested', ['url' => $url]);

        return [
            [
                'filename' => 'OneDrive_File',
                'size' => 0,
                'mime_type' => 'application/octet-stream',
                'download_url' => null,
            ],
        ];
    }

    /**
     * Fetch individual file list from WeTransfer API for multi-file transfers.
     */
    protected function fetchWeTransferFileList(string $transferId, ?string $securityHash): array
    {
        $files = [];
        $timeout = config('linkimport.security.timeout_seconds', 60);
        $userAgent = config('linkimport.processing.user_agent', 'MixPitch-LinkImporter/1.0');

        try {
            // Try different API endpoints to get file list
            $apiUrls = [
                "https://wetransfer.com/api/v4/transfers/{$transferId}",
                "https://wetransfer.com/api/v4/transfers/{$transferId}/files",
            ];

            foreach ($apiUrls as $apiUrl) {
                Log::debug('Attempting to fetch file list from WeTransfer API', [
                    'api_url' => $apiUrl,
                    'transfer_id' => $transferId,
                ]);

                $payload = [];
                if ($securityHash) {
                    $payload['security_hash'] = $securityHash;
                }

                $response = Http::timeout($timeout)
                    ->withHeaders([
                        'User-Agent' => $userAgent,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->get($apiUrl, $payload);

                if ($response->successful()) {
                    $data = $response->json();

                    Log::debug('WeTransfer API response received', [
                        'api_url' => $apiUrl,
                        'response_keys' => array_keys($data ?? []),
                        'response_data' => $data, // Full response for debugging
                    ]);

                    // Look for files in various response structures
                    $fileList = $this->extractFilesFromApiResponse($data, $transferId, $securityHash);

                    if (! empty($fileList)) {
                        $files = $fileList;
                        Log::info('Successfully fetched file list from WeTransfer API', [
                            'api_url' => $apiUrl,
                            'files_count' => count($files),
                        ]);
                        break; // Stop trying other endpoints
                    }
                } else {
                    Log::debug('WeTransfer API request failed', [
                        'api_url' => $apiUrl,
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch WeTransfer file list', [
                'transfer_id' => $transferId,
                'error' => $e->getMessage(),
            ]);
        }

        return $files;
    }

    /**
     * Extract files from WeTransfer API response.
     */
    protected function extractFilesFromApiResponse(array $data, string $transferId, ?string $securityHash): array
    {
        $files = [];

        // Pattern 1: Response is a direct array of files (e.g., /transfers/{id}/files endpoint)
        if (is_array($data) && isset($data[0]) && is_array($data[0])) {
            Log::debug('Processing direct array response from WeTransfer API', [
                'items_count' => count($data),
            ]);

            foreach ($data as $file) {
                if (is_array($file)) {
                    // Try different possible field names for filename
                    $filename = $file['name'] ?? $file['filename'] ?? $file['title'] ?? null;
                    $fileId = $file['id'] ?? $file['file_id'] ?? null;
                    $size = $file['size'] ?? $file['file_size'] ?? 0;

                    if ($filename) {
                        $files[] = [
                            'filename' => $filename,
                            'size' => $size,
                            'mime_type' => $this->guessMimeType($filename),
                            'file_id' => $fileId,
                            'transfer_id' => $transferId,
                            'security_hash' => $securityHash,
                            'recipient_id' => null,
                            'download_url' => null,
                        ];

                        Log::debug('Extracted file from API response', [
                            'filename' => $filename,
                            'file_id' => $fileId,
                            'size' => $size,
                        ]);
                    }
                }
            }
        }

        // Pattern 2: Files nested in object
        if (empty($files) && isset($data['files']) && is_array($data['files'])) {
            foreach ($data['files'] as $file) {
                if (is_array($file) && isset($file['name'])) {
                    $files[] = [
                        'filename' => $file['name'],
                        'size' => $file['size'] ?? 0,
                        'mime_type' => $this->guessMimeType($file['name']),
                        'file_id' => $file['id'] ?? null,
                        'transfer_id' => $transferId,
                        'security_hash' => $securityHash,
                        'recipient_id' => null,
                        'download_url' => null,
                    ];
                }
            }
        }

        // Pattern 3: Nested in transfer object
        if (empty($files) && isset($data['transfer']['files']) && is_array($data['transfer']['files'])) {
            foreach ($data['transfer']['files'] as $file) {
                if (is_array($file) && isset($file['name'])) {
                    $files[] = [
                        'filename' => $file['name'],
                        'size' => $file['size'] ?? 0,
                        'mime_type' => $this->guessMimeType($file['name']),
                        'file_id' => $file['id'] ?? null,
                        'transfer_id' => $transferId,
                        'security_hash' => $securityHash,
                        'recipient_id' => null,
                        'download_url' => null,
                    ];
                }
            }
        }

        Log::debug('File extraction completed', [
            'files_extracted' => count($files),
        ]);

        return $files;
    }

    /**
     * Guess the MIME type based on file extension.
     */
    protected function guessMimeType(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'flac' => 'audio/flac',
            'aiff', 'aif' => 'audio/aiff',
            'm4a' => 'audio/mp4',
            'aac' => 'audio/aac',
            'ogg' => 'audio/ogg',
            'wma' => 'audio/x-ms-wma',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            '7z' => 'application/x-7z-compressed',
            'tar' => 'application/x-tar',
            'gz' => 'application/gzip',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            'txt' => 'text/plain',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            default => 'application/octet-stream'
        };
    }

    /**
     * Validate that a URL is safe to process.
     */
    public function validateUrl(string $url): bool
    {
        // Basic URL validation
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Check if domain is in allowed list
        $domain = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $allowedDomains = config('linkimport.allowed_domains', []);

        foreach ($allowedDomains as $allowedDomain) {
            if (str_ends_with($domain, $allowedDomain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the estimated total size of files from analysis results.
     */
    public function getEstimatedTotalSize(array $files): int
    {
        return array_sum(array_column($files, 'size'));
    }

    /**
     * Check if the analyzed files are within size limits.
     */
    public function validateFileSizes(array $files): void
    {
        $maxFileSize = config('linkimport.security.max_file_size', 500 * 1024 * 1024);
        $maxTotalSize = config('linkimport.processing.max_total_size_per_import', 1024 * 1024 * 1024);
        $maxFiles = config('linkimport.processing.max_files_per_link', 20);

        // Check file count
        if (count($files) > $maxFiles) {
            throw new \Exception("Too many files in link. Maximum allowed: {$maxFiles}");
        }

        // Check individual file sizes
        foreach ($files as $file) {
            if (($file['size'] ?? 0) > $maxFileSize) {
                $filename = $file['filename'] ?? 'unknown';
                $sizeMB = round($maxFileSize / 1024 / 1024);
                throw new \Exception("File '{$filename}' is too large. Maximum size: {$sizeMB}MB");
            }
        }

        // Check total size
        $totalSize = $this->getEstimatedTotalSize($files);
        if ($totalSize > $maxTotalSize) {
            $totalSizeMB = round($maxTotalSize / 1024 / 1024);
            throw new \Exception("Total size of files is too large. Maximum total: {$totalSizeMB}MB");
        }
    }

    /**
     * Parse WeTransfer download URL to extract transfer metadata.
     */
    protected function parseWeTransferDownloadUrl(string $url): array
    {
        $result = [
            'transfer_id' => null,
            'security_hash' => null,
            'recipient_id' => null,
        ];

        // Parse URL: https://wetransfer.com/downloads/{transfer_id}/{security_hash}?params
        if (preg_match('/\/downloads\/([a-f0-9]+)\/([a-f0-9]+)/', $url, $matches)) {
            $result['transfer_id'] = $matches[1];
            $result['security_hash'] = $matches[2];
        }

        // Extract recipient_id from query parameters if present
        $urlParts = parse_url($url);
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $queryParams);

            // Look for recipient identifier in various parameter names
            foreach (['t_rid', 'recipient_id', 'rid'] as $param) {
                if (isset($queryParams[$param])) {
                    $result['recipient_id'] = $queryParams[$param];
                    break;
                }
            }
        }

        Log::debug('Parsed WeTransfer URL metadata', $result);

        return $result;
    }

    /**
     * Extract CSRF token from WeTransfer HTML page.
     */
    protected function extractCSRFToken(string $html): ?string
    {
        // Look for CSRF token in meta tags
        if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

        // Look for CSRF token in forms
        if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

        // Look for CSRF token in JavaScript variables
        if (preg_match('/csrfToken["\']?\s*[:=]\s*["\']([^"\']+)["\']/', $html, $matches)) {
            return $matches[1];
        }

        // Look for CSRF in __NEXT_DATA__
        if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.+?)<\/script>/', $html, $matches)) {
            try {
                $data = json_decode($matches[1], true);
                $csrf = $data['props']['pageProps']['csrfToken'] ??
                       $data['csrf'] ??
                       $data['token'] ?? null;

                if ($csrf) {
                    return $csrf;
                }
            } catch (\Exception $e) {
                // Continue to other methods
            }
        }

        return null;
    }

    /**
     * Create a WeTransfer API session with proper headers and CSRF token.
     */
    public function createWeTransferSession(string $transferPageUrl): array
    {
        $userAgent = config('linkimport.processing.user_agent', 'MixPitch-LinkImporter/1.0');
        $timeout = config('linkimport.security.timeout_seconds', 60);

        // Get the transfer page to extract CSRF token
        $response = Http::timeout($timeout)
            ->withHeaders([
                'User-Agent' => $userAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ])
            ->get($transferPageUrl);

        if (! $response->successful()) {
            throw new \Exception("Failed to create WeTransfer session. HTTP status: {$response->status()}");
        }

        $html = $response->body();
        $csrfToken = $this->extractCSRFToken($html);

        Log::debug('WeTransfer session created', [
            'csrf_found' => $csrfToken ? 'yes' : 'no',
            'page_url' => $transferPageUrl,
        ]);

        return [
            'csrf_token' => $csrfToken,
            'cookies' => $response->cookies(),
            'html' => $html,
        ];
    }
}

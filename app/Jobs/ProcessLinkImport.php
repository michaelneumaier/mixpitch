<?php

namespace App\Jobs;

use App\Events\LinkImportCompleted;
use App\Events\LinkImportUpdated;
use App\Models\ImportedFile;
use App\Models\LinkImport;
use App\Services\FileManagementService;
use App\Services\LinkAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessLinkImport implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 minutes

    public $tries = 3;

    public $backoff = [60, 120, 300]; // Backoff delays in seconds

    protected LinkImport $linkImport;

    /**
     * Create a new job instance.
     */
    public function __construct(LinkImport $linkImport)
    {
        $this->linkImport = $linkImport;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'link-import:'.$this->linkImport->id;
    }

    /**
     * Execute the job.
     */
    public function handle(
        LinkAnalysisService $analysisService,
        FileManagementService $fileService
    ): void {
        try {
            Log::info('Starting link import processing', [
                'import_id' => $this->linkImport->id,
                'url' => $this->linkImport->source_url,
            ]);

            // Update status to analyzing
            $this->linkImport->update([
                'status' => LinkImport::STATUS_ANALYZING,
                'started_at' => now(),
            ]);

            // Step 1: Analyze the link to get file metadata
            $files = $analysisService->analyzeLink($this->linkImport->source_url);

            Log::info('Link analysis completed', [
                'import_id' => $this->linkImport->id,
                'files_detected' => count($files),
            ]);

            // Validate the detected files
            $analysisService->validateFileSizes($files);

            // Update with detected files and switch to importing status
            $metadata = $this->linkImport->metadata ?? [];
            $metadata['progress'] = [
                'completed' => 0,
                'total' => count($files),
                'current_file' => '',
            ];

            $this->linkImport->update([
                'detected_files' => $files,
                'metadata' => $metadata,
                'status' => LinkImport::STATUS_IMPORTING,
            ]);

            // Broadcast progress update (disabled - using polling instead)
            // event(new LinkImportUpdated($this->linkImport->id, $this->linkImport->project_id));

            // Step 2: Download and import each file
            $importedFileIds = [];

            foreach ($files as $index => $fileInfo) {
                try {
                    Log::debug('Processing file', [
                        'import_id' => $this->linkImport->id,
                        'file_index' => $index,
                        'filename' => $fileInfo['filename'] ?? 'unknown',
                    ]);

                    // Update status to show we're downloading this file
                    $metadata = $this->linkImport->metadata ?? [];
                    $metadata['progress'] = [
                        'completed' => count($importedFileIds),
                        'total' => count($files),
                        'current_file' => $fileInfo['filename'] ?? 'Unknown',
                        'stage' => 'downloading',
                    ];
                    $this->linkImport->update(['metadata' => $metadata]);

                    $projectFile = $this->importSingleFile($fileInfo, $fileService);
                    $importedFileIds[] = $projectFile->id;

                    // Update progress after successful import
                    $metadata = $this->linkImport->metadata ?? [];
                    $metadata['progress'] = [
                        'completed' => count($importedFileIds),
                        'total' => count($files),
                        'current_file' => $fileInfo['filename'] ?? 'Unknown',
                        'stage' => 'completed',
                    ];

                    $this->linkImport->update(['metadata' => $metadata]);

                    // Broadcast progress update (disabled - using polling instead)
                    // event(new LinkImportUpdated($this->linkImport->id, $this->linkImport->project_id));

                } catch (\Exception $e) {
                    Log::error('Failed to import individual file', [
                        'import_id' => $this->linkImport->id,
                        'file_info' => $fileInfo,
                        'error' => $e->getMessage(),
                    ]);

                    // Continue with other files instead of failing the entire import
                    // Store the error in metadata for review
                    $metadata = $this->linkImport->metadata ?? [];
                    $metadata['file_errors'][] = [
                        'filename' => $fileInfo['filename'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                    $this->linkImport->update(['metadata' => $metadata]);
                }
            }

            // Mark as completed
            $this->linkImport->update([
                'status' => LinkImport::STATUS_COMPLETED,
                'imported_files' => $importedFileIds,
                'completed_at' => now(),
            ]);

            Log::info('Link import completed successfully', [
                'import_id' => $this->linkImport->id,
                'files_imported' => count($importedFileIds),
            ]);

            // Broadcast completion event (disabled - using polling instead)
            // event(new LinkImportCompleted($this->linkImport->id, $this->linkImport->project_id));

        } catch (\Exception $e) {
            Log::error('Link import failed', [
                'import_id' => $this->linkImport->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->linkImport->update([
                'status' => LinkImport::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            // Still broadcast the failure for UI updates (disabled - using polling instead)
            // event(new LinkImportUpdated($this->linkImport->id, $this->linkImport->project_id));

            throw $e;
        }
    }

    /**
     * Import a single file from the analyzed link.
     */
    protected function importSingleFile(array $fileInfo, FileManagementService $fileService): \App\Models\ProjectFile
    {
        $filename = $fileInfo['filename'] ?? 'imported_file';
        $mimeType = $fileInfo['mime_type'] ?? 'application/octet-stream';
        $expectedSize = $fileInfo['size'] ?? 0;

        // For now, we'll create a simple implementation that works with direct download URLs
        // In a production environment, this would be more sophisticated with streaming to S3

        if (isset($fileInfo['download_url']) && $fileInfo['download_url']) {
            return $this->downloadAndStoreFile($fileInfo, $fileService);
        } else {
            // For WeTransfer and other services that don't provide direct download URLs,
            // we'll need to implement more sophisticated extraction
            return $this->handleComplexFileExtraction($fileInfo, $fileService);
        }
    }

    /**
     * Download a file from a direct URL and store it.
     */
    protected function downloadAndStoreFile(array $fileInfo, FileManagementService $fileService): \App\Models\ProjectFile
    {
        $downloadUrl = $fileInfo['download_url'];
        $filename = $fileInfo['filename'] ?? 'imported_file';
        $mimeType = $fileInfo['mime_type'] ?? 'application/octet-stream';

        Log::debug('Downloading file from direct URL', [
            'import_id' => $this->linkImport->id,
            'filename' => $filename,
            'url' => $downloadUrl,
        ]);

        $timeout = config('linkimport.security.timeout_seconds', 60);
        $userAgent = config('linkimport.processing.user_agent');

        // Download the file
        $response = Http::timeout($timeout)
            ->withHeaders(['User-Agent' => $userAgent])
            ->get($downloadUrl);

        if (! $response->successful()) {
            throw new \Exception("Failed to download file: HTTP {$response->status()}");
        }

        $fileContent = $response->body();
        $actualSize = strlen($fileContent);

        // Create a temporary file
        $tempPath = tempnam(sys_get_temp_dir(), 'linkimport_');
        file_put_contents($tempPath, $fileContent);

        try {
            // Use the FileManagementService to store the file
            $projectFile = $fileService->storeProjectFile(
                project: $this->linkImport->project,
                file: new \Illuminate\Http\UploadedFile(
                    $tempPath,
                    $filename,
                    $mimeType,
                    null,
                    true
                ),
                user: $this->linkImport->user,
                metadata: [
                    'import_source' => 'link_import',
                    'source_url' => $this->linkImport->source_url,
                    'original_size' => $fileInfo['size'] ?? 0,
                ]
            );

            // Update project file with import source
            $projectFile->update(['import_source' => 'link_import']);

            // Create imported file record
            ImportedFile::create([
                'link_import_id' => $this->linkImport->id,
                'project_file_id' => $projectFile->id,
                'source_filename' => $filename,
                'source_url' => $downloadUrl,
                'size_bytes' => $actualSize,
                'mime_type' => $mimeType,
                'imported_at' => now(),
            ]);

            return $projectFile;

        } finally {
            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    /**
     * Handle WeTransfer file extraction using modern API.
     */
    protected function handleComplexFileExtraction(array $fileInfo, FileManagementService $fileService): \App\Models\ProjectFile
    {
        $filename = $fileInfo['filename'] ?? 'imported_file';
        $mimeType = $fileInfo['mime_type'] ?? 'application/octet-stream';
        $transferId = $fileInfo['transfer_id'] ?? null;
        $securityHash = $fileInfo['security_hash'] ?? null;
        $recipientId = $fileInfo['recipient_id'] ?? null;

        Log::info('Starting WeTransfer file extraction', [
            'import_id' => $this->linkImport->id,
            'filename' => $filename,
            'transfer_id' => $transferId,
            'security_hash' => $securityHash ? 'present' : 'missing',
            'recipient_id' => $recipientId ? 'present' : 'missing',
        ]);

        if (! $transferId) {
            throw new \Exception('Missing transfer ID for WeTransfer file extraction');
        }

        if (! $securityHash) {
            throw new \Exception('Missing security hash for WeTransfer file extraction');
        }

        // Use the modern WeTransfer download API
        return $this->downloadWeTransferFileViaModernAPI($transferId, $securityHash, $recipientId, $filename, $mimeType, $fileInfo, $fileService);
    }

    /**
     * Get WeTransfer download URL for a file.
     */
    protected function getWeTransferDownloadUrl(string $transferId, ?string $fileId, array $fileInfo): ?string
    {
        // If we already have a download URL from the analysis, use it
        if (! empty($fileInfo['download_url'])) {
            return $fileInfo['download_url'];
        }

        // Try to construct the download URL based on WeTransfer patterns
        if ($fileId) {
            // Modern WeTransfer API pattern
            return "https://wetransfer.com/api/v4/transfers/{$transferId}/download/{$fileId}";
        }

        return null;
    }

    /**
     * Download WeTransfer file via modern API.
     */
    protected function downloadWeTransferFileViaModernAPI(string $transferId, string $securityHash, ?string $recipientId, string $filename, string $mimeType, array $fileInfo, FileManagementService $fileService): \App\Models\ProjectFile
    {
        $timeout = config('linkimport.security.timeout_seconds', 120);
        $userAgent = config('linkimport.processing.user_agent', 'MixPitch-LinkImporter/1.0');

        Log::debug('Starting modern WeTransfer API download', [
            'transfer_id' => $transferId,
            'security_hash' => substr($securityHash, 0, 6).'...',
            'filename' => $filename,
        ]);

        try {
            // Step 1: Create session and get CSRF token
            $linkAnalysisService = app(\App\Services\LinkAnalysisService::class);
            $transferPageUrl = "https://wetransfer.com/downloads/{$transferId}/{$securityHash}";

            if ($recipientId) {
                $transferPageUrl .= '?t_rid='.urlencode($recipientId);
            }

            $session = $linkAnalysisService->createWeTransferSession($transferPageUrl);

            // Step 2: Choose API approach based on whether we have individual file ID
            $fileId = $fileInfo['file_id'] ?? null;

            if ($fileId) {
                // Individual file download approach (preferred for multi-file transfers)
                Log::info('Using individual file download approach', [
                    'filename' => $filename,
                    'file_id' => $fileId,
                ]);

                $apiApproaches = [
                    // Approach 1: Individual file download with file ID
                    [
                        'url' => "https://wetransfer.com/api/v4/transfers/{$transferId}/files/{$fileId}/download",
                        'payload' => [
                            'security_hash' => $securityHash,
                        ],
                    ],
                    // Approach 2: Individual file download alternative endpoint
                    [
                        'url' => "https://wetransfer.com/api/v4/transfers/{$transferId}/files/{$fileId}",
                        'payload' => [
                            'security_hash' => $securityHash,
                            'intent' => 'download',
                        ],
                    ],
                    // Fallback to entire transfer (will download as zip)
                    [
                        'url' => "https://wetransfer.com/api/v4/transfers/{$transferId}/download",
                        'payload' => [
                            'intent' => 'entire_transfer',
                            'security_hash' => $securityHash,
                        ],
                    ],
                ];
            } else {
                // Entire transfer approach (original logic for single files or when no file_id)
                Log::info('Using entire transfer download approach', [
                    'filename' => $filename,
                ]);

                $apiApproaches = [
                    // Approach 1: Try without recipient_id first
                    [
                        'url' => "https://wetransfer.com/api/v4/transfers/{$transferId}/download",
                        'payload' => [
                            'intent' => 'entire_transfer',
                            'security_hash' => $securityHash,
                        ],
                    ],
                    // Approach 2: Try with recipient_id if available
                    [
                        'url' => "https://wetransfer.com/api/v4/transfers/{$transferId}/download",
                        'payload' => [
                            'intent' => 'entire_transfer',
                            'security_hash' => $securityHash,
                            'recipient_id' => $recipientId,
                        ],
                    ],
                    // Approach 3: Try direct file download approach
                    [
                        'url' => "https://wetransfer.com/api/v4/transfers/{$transferId}/files/download",
                        'payload' => [
                            'security_hash' => $securityHash,
                        ],
                    ],
                ];
            }

            $directLink = null;
            $lastError = null;

            foreach ($apiApproaches as $index => $approach) {
                if ($index === 1 && ! $recipientId) {
                    continue;
                } // Skip recipient approach if no recipient_id

                $apiUrl = $approach['url'];
                $payload = array_filter($approach['payload']); // Remove null values

                $headers = [
                    'User-Agent' => $userAgent,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Referer' => $transferPageUrl,
                ];

                // Add CSRF token if available
                if ($session['csrf_token']) {
                    $headers['X-CSRF-Token'] = $session['csrf_token'];
                }

                Log::debug('Trying WeTransfer API approach', [
                    'approach' => $index + 1,
                    'api_url' => $apiUrl,
                    'payload_keys' => array_keys($payload),
                    'csrf_present' => isset($session['csrf_token']),
                ]);

                // Convert CookieJar to array format for Laravel HTTP client
                $cookiesArray = [];
                $domain = 'wetransfer.com';
                if (isset($session['cookies']) && $session['cookies'] instanceof \GuzzleHttp\Cookie\CookieJar) {
                    foreach ($session['cookies'] as $cookie) {
                        $cookiesArray[$cookie->getName()] = $cookie->getValue();
                    }
                }

                $response = Http::timeout($timeout)
                    ->withHeaders($headers)
                    ->withCookies($cookiesArray, $domain)
                    ->post($apiUrl, $payload);

                if (! $response->successful()) {
                    $lastError = "HTTP {$response->status()}: {$response->body()}";
                    Log::debug('WeTransfer API approach failed', [
                        'approach' => $index + 1,
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);

                    continue; // Try next approach
                }

                $responseData = $response->json();
                $directLink = $responseData['direct_link'] ?? null;

                if ($directLink) {
                    Log::info('Successfully got direct download link', [
                        'approach' => $index + 1,
                        'filename' => $filename,
                        'direct_link_domain' => parse_url($directLink, PHP_URL_HOST),
                    ]);
                    break; // Success! Exit the loop
                } else {
                    Log::debug('No direct link in API response', [
                        'approach' => $index + 1,
                        'response_keys' => array_keys($responseData),
                    ]);
                    $lastError = 'No direct_link in response: '.json_encode($responseData);
                }
            }

            if (! $directLink) {
                Log::warning('All WeTransfer API approaches failed', [
                    'last_error' => $lastError,
                ]);
                throw new \Exception("All WeTransfer API approaches failed. Last error: {$lastError}");
            }

            Log::info('Got direct download link from WeTransfer API', [
                'filename' => $filename,
                'direct_link_domain' => parse_url($directLink, PHP_URL_HOST),
            ]);

            // Step 3: Download the file from the direct link
            return $this->downloadFileFromDirectLink($directLink, $filename, $mimeType, $fileService);

        } catch (\Exception $e) {
            Log::error('Modern WeTransfer API download failed', [
                'transfer_id' => $transferId,
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            // Fallback to placeholder
            return $this->createWeTransferPlaceholder($transferId, null, $filename, $mimeType, $fileService);
        }
    }

    /**
     * Download WeTransfer file from a direct URL.
     */
    protected function downloadWeTransferFile(string $downloadUrl, string $filename, string $mimeType, FileManagementService $fileService): \App\Models\ProjectFile
    {
        $timeout = config('linkimport.security.timeout_seconds', 120);
        $userAgent = config('linkimport.processing.user_agent');

        Log::debug('Downloading WeTransfer file from URL', [
            'filename' => $filename,
            'url' => $downloadUrl,
        ]);

        $response = Http::timeout($timeout)
            ->withHeaders([
                'User-Agent' => $userAgent,
                'Accept' => '*/*',
                'Referer' => $this->linkImport->source_url,
            ])
            ->get($downloadUrl);

        if (! $response->successful()) {
            throw new \Exception("Failed to download WeTransfer file: HTTP {$response->status()}");
        }

        $fileContent = $response->body();
        $actualSize = strlen($fileContent);

        Log::info('Successfully downloaded WeTransfer file', [
            'filename' => $filename,
            'size' => $actualSize,
        ]);

        return $this->storeDownloadedFile($fileContent, $filename, $mimeType, $actualSize, $downloadUrl, $fileService);
    }

    /**
     * Download file from direct link (usually from WeTransfer API response).
     */
    protected function downloadFileFromDirectLink(string $directLink, string $filename, string $mimeType, FileManagementService $fileService): \App\Models\ProjectFile
    {
        $timeout = config('linkimport.security.timeout_seconds', 120);
        $userAgent = config('linkimport.processing.user_agent', 'MixPitch-LinkImporter/1.0');

        Log::debug('Downloading file from direct link', [
            'filename' => $filename,
            'link_domain' => parse_url($directLink, PHP_URL_HOST),
        ]);

        // Check file size first to decide on streaming vs in-memory download
        Log::debug('Making HEAD request to determine file size', [
            'direct_link' => $directLink,
            'filename' => $filename,
        ]);

        $headResponse = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => $userAgent,
                'Referer' => $this->linkImport->source_url,
            ])
            ->head($directLink);

        $contentLength = $headResponse->header('Content-Length');
        $fileSizeBytes = $contentLength ? intval($contentLength) : 0;
        $fileSizeMB = round($fileSizeBytes / 1024 / 1024, 2);
        $streamingThreshold = config('linkimport.processing.streaming_threshold_mb', 50) * 1024 * 1024; // Default 50MB
        $streamingThresholdMB = config('linkimport.processing.streaming_threshold_mb', 50);

        Log::info('File size information', [
            'filename' => $filename,
            'size_bytes' => $fileSizeBytes,
            'size_mb' => $fileSizeMB,
            'streaming_threshold_mb' => $streamingThresholdMB,
            'streaming_threshold_bytes' => $streamingThreshold,
            'will_stream' => $fileSizeBytes > $streamingThreshold,
            'head_response_status' => $headResponse->status(),
            'content_length_header' => $contentLength,
        ]);

        if ($fileSizeBytes > $streamingThreshold) {
            // Use streaming download for large files
            return $this->downloadFileWithStreaming($directLink, $filename, $mimeType, $fileSizeBytes, $userAgent, $fileService);
        } else {
            // Use in-memory download for small files
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'User-Agent' => $userAgent,
                    'Accept' => '*/*',
                    'Referer' => $this->linkImport->source_url,
                ])
                ->get($directLink);

            if (! $response->successful()) {
                throw new \Exception("Failed to download from direct link: HTTP {$response->status()}");
            }

            $fileContent = $response->body();
            $actualSize = strlen($fileContent);

            Log::info('Successfully downloaded file from direct link (in-memory)', [
                'filename' => $filename,
                'size' => $actualSize,
            ]);

            return $this->storeDownloadedFile($fileContent, $filename, $mimeType, $actualSize, $directLink, $fileService);
        }
    }

    /**
     * Download large file using streaming to avoid memory issues.
     */
    protected function downloadFileWithStreaming(string $directLink, string $filename, string $mimeType, int $expectedSize, string $userAgent, FileManagementService $fileService): \App\Models\ProjectFile
    {
        $timeout = config('linkimport.security.timeout_seconds', 300); // Longer timeout for large files
        $tempPath = tempnam(sys_get_temp_dir(), 'linkimport_stream_');

        Log::info('Starting streaming download for large file', [
            'filename' => $filename,
            'expected_size_mb' => round($expectedSize / 1024 / 1024, 2),
            'temp_path' => $tempPath,
        ]);

        try {
            // Use Guzzle directly for streaming support
            $client = new \GuzzleHttp\Client([
                'timeout' => $timeout,
                'headers' => [
                    'User-Agent' => $userAgent,
                    'Accept' => '*/*',
                    'Referer' => $this->linkImport->source_url,
                ],
            ]);

            $response = $client->request('GET', $directLink, [
                'sink' => $tempPath, // Stream directly to file
                'progress' => function ($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) use ($filename) {
                    if ($downloadTotal > 0) {
                        $percent = round(($downloadedBytes / $downloadTotal) * 100, 1);
                        if ($downloadedBytes % (1024 * 1024 * 10) === 0 || $percent % 10 === 0) { // Log every 10MB or 10%
                            Log::debug('Download progress', [
                                'filename' => $filename,
                                'downloaded_mb' => round($downloadedBytes / 1024 / 1024, 2),
                                'total_mb' => round($downloadTotal / 1024 / 1024, 2),
                                'percent' => $percent,
                            ]);
                        }
                    }
                },
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception("Failed to stream download: HTTP {$response->getStatusCode()}");
            }

            $actualSize = filesize($tempPath);

            Log::info('Successfully completed streaming download', [
                'filename' => $filename,
                'actual_size_mb' => round($actualSize / 1024 / 1024, 2),
                'expected_size_mb' => round($expectedSize / 1024 / 1024, 2),
            ]);

            // Upload the streamed file to S3 using FileManagementService
            $projectFile = $fileService->uploadProjectFile(
                project: $this->linkImport->project,
                file: new \Illuminate\Http\UploadedFile(
                    $tempPath,
                    $filename,
                    $mimeType,
                    null,
                    true
                ),
                uploader: $this->linkImport->user,
                metadata: [
                    'import_source' => 'link_import',
                    'source_url' => $this->linkImport->source_url,
                    'original_size' => $actualSize,
                    'streamed_download' => true,
                ]
            );

            // Update project file with import source
            $projectFile->update(['import_source' => 'link_import']);

            // Create imported file record
            $this->linkImport->imported_files()->create([
                'original_filename' => $filename,
                'project_file_id' => $projectFile->id,
                'file_size' => $actualSize,
                'mime_type' => $mimeType,
                'download_url' => $directLink,
            ]);

            return $projectFile;

        } catch (\Exception $e) {
            Log::error('Streaming download failed', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    /**
     * Store downloaded file content.
     */
    protected function storeDownloadedFile(string $fileContent, string $filename, string $mimeType, int $actualSize, string $sourceUrl, FileManagementService $fileService): \App\Models\ProjectFile
    {
        // Create a temporary file with the downloaded content
        $tempPath = tempnam(sys_get_temp_dir(), 'linkimport_wt_');
        file_put_contents($tempPath, $fileContent);

        try {
            // Use the FileManagementService to store the file
            $projectFile = $fileService->uploadProjectFile(
                project: $this->linkImport->project,
                file: new \Illuminate\Http\UploadedFile(
                    $tempPath,
                    $filename,
                    $mimeType,
                    null,
                    true
                ),
                uploader: $this->linkImport->user,
                metadata: [
                    'import_source' => 'link_import',
                    'source_url' => $this->linkImport->source_url,
                    'original_size' => $actualSize,
                ]
            );

            // Update project file with import source
            $projectFile->update(['import_source' => 'link_import']);

            // Create imported file record
            ImportedFile::create([
                'link_import_id' => $this->linkImport->id,
                'project_file_id' => $projectFile->id,
                'source_filename' => $filename,
                'source_url' => $sourceUrl,
                'size_bytes' => $actualSize,
                'mime_type' => $mimeType,
                'imported_at' => now(),
            ]);

            return $projectFile;

        } finally {
            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    /**
     * Create a placeholder file for WeTransfer files that couldn't be downloaded.
     */
    protected function createWeTransferPlaceholder(string $transferId, ?string $fileId, string $filename, string $mimeType, FileManagementService $fileService): \App\Models\ProjectFile
    {
        $placeholderContent = "WeTransfer File Import Information\n";
        $placeholderContent .= "=====================================\n\n";
        $placeholderContent .= "Original filename: {$filename}\n";
        $placeholderContent .= "Transfer ID: {$transferId}\n";
        $placeholderContent .= 'File ID: '.($fileId ?: 'Unknown')."\n";
        $placeholderContent .= "Source URL: {$this->linkImport->source_url}\n";
        $placeholderContent .= 'Import time: '.now()->toISOString()."\n\n";
        $placeholderContent .= "Note: This file could not be automatically downloaded.\n";
        $placeholderContent .= "The WeTransfer link may have expired or require manual download.\n";

        // Create a temporary file with placeholder content
        $tempPath = tempnam(sys_get_temp_dir(), 'linkimport_wt_placeholder_');
        file_put_contents($tempPath, $placeholderContent);

        try {
            // Store as a text file
            $projectFile = $fileService->uploadProjectFile(
                project: $this->linkImport->project,
                file: new \Illuminate\Http\UploadedFile(
                    $tempPath,
                    $filename.'.download_info.txt',
                    'text/plain',
                    null,
                    true
                ),
                uploader: $this->linkImport->user,
                metadata: [
                    'import_source' => 'link_import',
                    'source_url' => $this->linkImport->source_url,
                    'original_filename' => $filename,
                    'placeholder' => true,
                    'transfer_id' => $transferId,
                    'file_id' => $fileId,
                ]
            );

            // Update project file with import source
            $projectFile->update(['import_source' => 'link_import']);

            // Create imported file record
            ImportedFile::create([
                'link_import_id' => $this->linkImport->id,
                'project_file_id' => $projectFile->id,
                'source_filename' => $filename,
                'source_url' => $this->linkImport->source_url,
                'size_bytes' => strlen($placeholderContent),
                'mime_type' => 'text/plain',
                'imported_at' => now(),
            ]);

            return $projectFile;

        } finally {
            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessLinkImport job failed permanently', [
            'import_id' => $this->linkImport->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        $this->linkImport->update([
            'status' => LinkImport::STATUS_FAILED,
            'error_message' => 'Job failed after '.$this->attempts().' attempts: '.$exception->getMessage(),
            'completed_at' => now(),
        ]);

        // Broadcast the failure (disabled - using polling instead)
        // event(new LinkImportUpdated($this->linkImport->id, $this->linkImport->project_id));
    }
}

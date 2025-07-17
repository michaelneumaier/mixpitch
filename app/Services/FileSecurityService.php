<?php

namespace App\Services;

use App\Models\UploadSession;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileSecurityService
{
    // Maximum file size for malware scanning (100MB)
    const MAX_SCAN_SIZE = 100 * 1024 * 1024;

    // Suspicious file patterns
    protected array $suspiciousPatterns = [
        // Script injections
        '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
        '/javascript:/i',
        '/vbscript:/i',
        '/data:text\/html/i',

        // Event handlers
        '/on\w+\s*=/i',

        // Dangerous HTML elements
        '/<iframe\b/i',
        '/<object\b/i',
        '/<embed\b/i',
        '/<form\b/i',
        '/<meta\b.*http-equiv/i',

        // PHP code injection
        '/<\?php/i',
        '/<\?=/i',
        '/<%/i',

        // Server-side includes
        '/<!--#/i',

        // Suspicious URLs
        '/https?:\/\/[^\s]*\.(exe|bat|cmd|scr|pif)/i',

        // Base64 encoded suspicious content
        '/data:.*base64.*script/i',
    ];

    // Known malicious file signatures (hex patterns)
    protected array $maliciousSignatures = [
        // PE executable signatures
        '4D5A', // MZ header (Windows executable)

        // ELF executable signatures
        '7F454C46', // ELF header (Linux executable)

        // Mach-O executable signatures
        'FEEDFACE', // Mach-O 32-bit
        'FEEDFACF', // Mach-O 64-bit

        // Java class files
        'CAFEBABE', // Java class file

        // Archive bombs (zip bombs)
        '504B0304', // ZIP file header (need additional validation)
    ];

    /**
     * Perform comprehensive security validation on an uploaded file
     */
    public function validateFileSecurity(UploadedFile $file): array
    {
        $errors = [];

        try {
            // Basic security checks
            $this->validateFileBasics($file);

            // Scan for malicious content
            $this->scanForMaliciousContent($file);

            // Validate file signature
            $this->validateFileSignature($file);

            // Check for archive bombs
            $this->checkForArchiveBombs($file);

            // Validate file name security
            $this->validateFileNameSecurity($file);

        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
            Log::warning('File security validation failed', [
                'filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
        }

        return $errors;
    }

    /**
     * Create secure temporary storage for chunks with proper access controls
     */
    public function createSecureChunkStorage(string $uploadId): string
    {
        // Create secure directory structure
        $chunkDir = 'secure_chunks/'.date('Y/m/d')."/{$uploadId}";
        $fullPath = storage_path("app/{$chunkDir}");

        // Create directory with restricted permissions
        if (! File::exists($fullPath)) {
            File::makeDirectory($fullPath, 0700, true);

            // Create .htaccess to deny web access
            $htaccessContent = "Order Deny,Allow\nDeny from all";
            File::put($fullPath.'/.htaccess', $htaccessContent);

            // Create index.php to prevent directory listing
            $indexContent = "<?php\n// Access denied\nhttp_response_code(403);\nexit('Access denied');";
            File::put($fullPath.'/index.php', $indexContent);

            Log::info('Created secure chunk storage', [
                'upload_id' => $uploadId,
                'path' => $chunkDir,
            ]);
        }

        return $chunkDir;
    }

    /**
     * Store chunk with security validation and access controls
     */
    public function storeSecureChunk(UploadedFile $chunk, string $uploadId, int $chunkIndex, ?string $expectedHash = null): string
    {
        // Validate chunk security
        $securityErrors = $this->validateChunkSecurity($chunk, $uploadId, $chunkIndex);
        if (! empty($securityErrors)) {
            throw new \Exception('Chunk security validation failed: '.implode(', ', $securityErrors));
        }

        // Create secure storage directory
        $chunkDir = $this->createSecureChunkStorage($uploadId);

        // Generate secure filename
        $secureFileName = $this->generateSecureChunkFilename($uploadId, $chunkIndex);
        $storagePath = "{$chunkDir}/{$secureFileName}";

        // Store chunk with restricted permissions
        $chunk->storeAs($chunkDir, $secureFileName, 'local');

        // Set restrictive file permissions
        $fullPath = storage_path("app/{$storagePath}");
        chmod($fullPath, 0600);

        // Validate stored chunk integrity
        if ($expectedHash) {
            $actualHash = hash_file('sha256', $fullPath);
            if (! hash_equals($expectedHash, $actualHash)) {
                // Remove invalid chunk immediately
                unlink($fullPath);
                throw new \Exception('Chunk integrity validation failed');
            }
        }

        Log::info('Secure chunk stored', [
            'upload_id' => $uploadId,
            'chunk_index' => $chunkIndex,
            'storage_path' => $storagePath,
        ]);

        return $storagePath;
    }

    /**
     * Validate chunk-specific security
     */
    protected function validateChunkSecurity(UploadedFile $chunk, string $uploadId, int $chunkIndex): array
    {
        $errors = [];

        // Validate upload session exists and is valid
        $session = UploadSession::find($uploadId);
        if (! $session) {
            $errors[] = 'Invalid upload session';

            return $errors;
        }

        if ($session->isExpired()) {
            $errors[] = 'Upload session has expired';

            return $errors;
        }

        // Validate chunk index
        if ($chunkIndex < 0 || $chunkIndex >= $session->total_chunks) {
            $errors[] = 'Invalid chunk index';
        }

        // Validate chunk size (should not exceed expected chunk size significantly)
        $maxChunkSize = $session->chunk_size * 1.1; // Allow 10% variance
        if ($chunk->getSize() > $maxChunkSize) {
            $errors[] = 'Chunk size exceeds expected maximum';
        }

        // Basic file validation
        if (! $chunk->isValid()) {
            $errors[] = 'Invalid chunk file';
        }

        return $errors;
    }

    /**
     * Generate secure filename for chunk storage
     */
    protected function generateSecureChunkFilename(string $uploadId, int $chunkIndex): string
    {
        // Use hash-based naming to prevent predictable filenames
        $hash = hash('sha256', $uploadId.$chunkIndex.config('app.key'));

        return substr($hash, 0, 16).'_'.$chunkIndex.'.chunk';
    }

    /**
     * Validate basic file security properties
     */
    protected function validateFileBasics(UploadedFile $file): void
    {
        // Check file size limits
        if ($file->getSize() > self::MAX_SCAN_SIZE) {
            Log::info('File too large for security scanning', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ]);

            return; // Skip detailed scanning for very large files
        }

        // Check for null bytes in filename
        if (strpos($file->getClientOriginalName(), "\0") !== false) {
            throw new \Exception('File name contains null bytes');
        }

        // Check for path traversal in filename
        if (strpos($file->getClientOriginalName(), '..') !== false) {
            throw new \Exception('File name contains path traversal sequences');
        }

        // Check for suspicious extensions
        $extension = strtolower($file->getClientOriginalExtension());
        $dangerousExtensions = [
            'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar',
            'php', 'asp', 'aspx', 'jsp', 'pl', 'py', 'rb', 'sh', 'ps1',
            'htaccess', 'htpasswd',
        ];

        if (in_array($extension, $dangerousExtensions)) {
            throw new \Exception("File extension '{$extension}' is not allowed");
        }
    }

    /**
     * Scan file content for malicious patterns
     */
    protected function scanForMaliciousContent(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();

        // Only scan text-based files and PDFs for content
        if (! $this->shouldScanContent($mimeType)) {
            return;
        }

        $content = file_get_contents($file->getPathname());
        if ($content === false) {
            throw new \Exception('Unable to read file content for security scanning');
        }

        // Scan for suspicious patterns
        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new \Exception('File contains potentially malicious content');
            }
        }

        // Check for excessive repetition (potential zip bomb indicator)
        $this->checkForExcessiveRepetition($content);
    }

    /**
     * Validate file signature against known malicious signatures
     */
    protected function validateFileSignature(UploadedFile $file): void
    {
        $handle = fopen($file->getPathname(), 'rb');
        if (! $handle) {
            throw new \Exception('Unable to read file for signature validation');
        }

        $header = fread($handle, 32);
        fclose($handle);

        $headerHex = strtoupper(bin2hex($header));

        // Check against known malicious signatures
        foreach ($this->maliciousSignatures as $signature) {
            if (strpos($headerHex, $signature) === 0) {
                throw new \Exception('File contains malicious signature');
            }
        }
    }

    /**
     * Check for archive bombs (zip bombs, etc.)
     */
    protected function checkForArchiveBombs(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();

        // Check ZIP files for potential bombs
        if (in_array($mimeType, ['application/zip', 'application/x-zip-compressed'])) {
            $this->validateZipFile($file);
        }
    }

    /**
     * Validate ZIP file for potential bombs
     */
    protected function validateZipFile(UploadedFile $file): void
    {
        if (! class_exists('ZipArchive')) {
            Log::warning('ZipArchive not available for validation');

            return;
        }

        $zip = new \ZipArchive;
        $result = $zip->open($file->getPathname());

        if ($result !== true) {
            // If we can't open it, it might be corrupted or not a real ZIP
            throw new \Exception('Invalid or corrupted ZIP file');
        }

        try {
            $totalUncompressedSize = 0;
            $fileCount = $zip->numFiles;

            // Check for excessive file count
            if ($fileCount > 10000) {
                throw new \Exception('ZIP file contains too many files (potential zip bomb)');
            }

            // Check compression ratios
            for ($i = 0; $i < min($fileCount, 100); $i++) { // Check first 100 files
                $stat = $zip->statIndex($i);
                if ($stat) {
                    $totalUncompressedSize += $stat['size'];

                    // Check individual file compression ratio
                    if ($stat['comp_size'] > 0) {
                        $ratio = $stat['size'] / $stat['comp_size'];
                        if ($ratio > 1000) { // Compression ratio > 1000:1 is suspicious
                            throw new \Exception('ZIP file contains highly compressed content (potential zip bomb)');
                        }
                    }
                }
            }

            // Check total uncompressed size vs file size
            $compressedSize = $file->getSize();
            if ($compressedSize > 0) {
                $totalRatio = $totalUncompressedSize / $compressedSize;
                if ($totalRatio > 100) { // Total ratio > 100:1 is suspicious
                    throw new \Exception('ZIP file has suspicious compression ratio (potential zip bomb)');
                }
            }

        } finally {
            $zip->close();
        }
    }

    /**
     * Validate file name for security issues
     */
    protected function validateFileNameSecurity(UploadedFile $file): void
    {
        $fileName = $file->getClientOriginalName();

        // Check length
        if (strlen($fileName) > 255) {
            throw new \Exception('File name is too long');
        }

        // Check for control characters
        if (preg_match('/[\x00-\x1F\x7F]/', $fileName)) {
            throw new \Exception('File name contains control characters');
        }

        // Check for reserved names (Windows)
        $baseName = pathinfo($fileName, PATHINFO_FILENAME);
        $reservedNames = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];

        if (in_array(strtoupper($baseName), $reservedNames)) {
            throw new \Exception('File name uses reserved system name');
        }
    }

    /**
     * Check if file content should be scanned based on MIME type
     */
    protected function shouldScanContent(string $mimeType): bool
    {
        $scannable = [
            'text/plain',
            'text/html',
            'text/css',
            'text/javascript',
            'application/javascript',
            'application/json',
            'application/xml',
            'text/xml',
            'application/pdf',
        ];

        return in_array($mimeType, $scannable) || strpos($mimeType, 'text/') === 0;
    }

    /**
     * Check for excessive repetition in content (zip bomb indicator)
     */
    protected function checkForExcessiveRepetition(string $content): void
    {
        $length = strlen($content);
        if ($length < 1000) {
            return; // Too small to be a concern
        }

        // Sample the content to check for repetition
        $sampleSize = min(1000, $length);
        $sample = substr($content, 0, $sampleSize);

        // Check for repeated patterns
        $uniqueChars = count(array_unique(str_split($sample)));
        $repetitionRatio = $sampleSize / $uniqueChars;

        if ($repetitionRatio > 100) { // Very high repetition
            throw new \Exception('File contains excessive repetition (potential bomb)');
        }
    }

    /**
     * Clean up secure chunk storage with proper security
     */
    public function cleanupSecureChunks(string $uploadId): bool
    {
        try {
            $chunkDir = 'secure_chunks/'.date('Y/m/d')."/{$uploadId}";
            $fullPath = storage_path("app/{$chunkDir}");

            if (File::exists($fullPath)) {
                // Securely delete all files in the directory
                $files = File::allFiles($fullPath);
                foreach ($files as $file) {
                    // Overwrite file with random data before deletion (basic secure delete)
                    $this->secureDeleteFile($file->getPathname());
                }

                // Remove directory
                File::deleteDirectory($fullPath);

                Log::info('Secure chunk cleanup completed', [
                    'upload_id' => $uploadId,
                    'path' => $chunkDir,
                ]);

                return true;
            }

            return true; // Directory doesn't exist, consider it cleaned

        } catch (\Exception $e) {
            Log::error('Failed to cleanup secure chunks', [
                'upload_id' => $uploadId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Securely delete a file by overwriting with random data
     */
    protected function secureDeleteFile(string $filePath): void
    {
        try {
            if (! file_exists($filePath)) {
                return;
            }

            $fileSize = filesize($filePath);
            if ($fileSize === false || $fileSize === 0) {
                unlink($filePath);

                return;
            }

            // Overwrite with random data (single pass - sufficient for most cases)
            $handle = fopen($filePath, 'r+b');
            if ($handle) {
                fseek($handle, 0);
                $randomData = random_bytes(min($fileSize, 8192));

                for ($written = 0; $written < $fileSize; $written += strlen($randomData)) {
                    $remaining = $fileSize - $written;
                    $writeData = $remaining < strlen($randomData) ? substr($randomData, 0, $remaining) : $randomData;
                    fwrite($handle, $writeData);
                }

                fclose($handle);
            }

            // Finally delete the file
            unlink($filePath);

        } catch (\Exception $e) {
            Log::warning('Secure file deletion failed, falling back to normal deletion', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);

            // Fallback to normal deletion
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * Validate hash integrity with timing-safe comparison
     */
    public function validateHashIntegrity(string $filePath, string $expectedHash, string $algorithm = 'sha256'): bool
    {
        if (! file_exists($filePath)) {
            return false;
        }

        $actualHash = hash_file($algorithm, $filePath);
        if ($actualHash === false) {
            return false;
        }

        return hash_equals($expectedHash, $actualHash);
    }

    /**
     * Generate secure hash for file
     */
    public function generateSecureHash(string $filePath, string $algorithm = 'sha256'): ?string
    {
        if (! file_exists($filePath)) {
            return null;
        }

        return hash_file($algorithm, $filePath);
    }
}

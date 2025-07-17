<?php

namespace App\Services;

use App\Models\FileUploadSetting;
use App\Models\Pitch;
use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FileValidationService
{
    protected FileUploadSettingsService $settingsService;

    protected FileManagementService $fileManagementService;

    // MIME type to file signature mappings for security validation
    protected array $fileSignatures = [
        // Audio formats
        'audio/mpeg' => [
            ['FF FB', 'FF F3', 'FF F2'], // MP3
            ['49 44 33'], // ID3 tag
        ],
        'audio/wav' => [
            ['52 49 46 46'], // RIFF header
        ],
        'audio/x-wav' => [
            ['52 49 46 46'], // RIFF header
        ],
        'audio/flac' => [
            ['66 4C 61 43'], // fLaC
        ],
        'audio/aac' => [
            ['FF F1', 'FF F9'], // AAC ADTS
        ],
        'audio/ogg' => [
            ['4F 67 67 53'], // OggS
        ],
        'audio/x-m4a' => [
            ['00 00 00 18 66 74 79 70'], // ftyp
            ['00 00 00 20 66 74 79 70'],
        ],
        'audio/mp4' => [
            ['00 00 00 18 66 74 79 70'], // ftyp
            ['00 00 00 20 66 74 79 70'],
        ],

        // Image formats (for project images)
        'image/jpeg' => [
            ['FF D8 FF'], // JPEG
        ],
        'image/png' => [
            ['89 50 4E 47 0D 0A 1A 0A'], // PNG
        ],
        'image/gif' => [
            ['47 49 46 38'], // GIF
        ],
        'image/webp' => [
            ['52 49 46 46', '57 45 42 50'], // RIFF + WEBP
        ],

        // Document formats
        'application/pdf' => [
            ['25 50 44 46'], // %PDF
        ],
        'text/plain' => [
            // Text files don't have reliable signatures, validate by content
        ],
    ];

    // Dangerous file extensions that should never be allowed
    protected array $dangerousExtensions = [
        'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar',
        'php', 'asp', 'aspx', 'jsp', 'pl', 'py', 'rb', 'sh', 'ps1',
    ];

    // Context-specific allowed MIME types
    protected array $contextAllowedMimeTypes = [
        FileUploadSetting::CONTEXT_PROJECTS => [
            'audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/flac', 'audio/aac',
            'audio/ogg', 'audio/x-m4a', 'audio/mp4', 'audio/webm',
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'text/plain',
        ],
        FileUploadSetting::CONTEXT_PITCHES => [
            'audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/flac', 'audio/aac',
            'audio/ogg', 'audio/x-m4a', 'audio/mp4', 'audio/webm',
        ],
        FileUploadSetting::CONTEXT_CLIENT_PORTALS => [
            'audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/flac', 'audio/aac',
            'audio/ogg', 'audio/x-m4a', 'audio/mp4', 'audio/webm',
            'application/pdf', 'text/plain',
        ],
    ];

    public function __construct(
        FileUploadSettingsService $settingsService,
        FileManagementService $fileManagementService
    ) {
        $this->settingsService = $settingsService;
        $this->fileManagementService = $fileManagementService;
    }

    /**
     * Validate a file for upload in a specific context
     */
    public function validateFile(UploadedFile $file, string $context, $model = null): array
    {
        $errors = [];

        try {
            // Basic file validation
            $this->validateBasicFile($file);

            // MIME type validation
            $this->validateMimeType($file, $context);

            // File signature validation for security
            $this->validateFileSignature($file);

            // Size validation
            $this->validateFileSize($file, $context);

            // Context-specific validation
            if ($model) {
                $this->validateContextSpecific($file, $context, $model);
            }

            // Security validation
            $this->validateSecurity($file);

        } catch (ValidationException $e) {
            $errors = array_merge($errors, $e->errors());
        } catch (\Exception $e) {
            $errors['file'] = [$e->getMessage()];
        }

        return $errors;
    }

    /**
     * Validate multiple files for upload
     */
    public function validateFiles(array $files, string $context, $model = null): array
    {
        $allErrors = [];

        foreach ($files as $index => $file) {
            if ($file instanceof UploadedFile) {
                $errors = $this->validateFile($file, $context, $model);
                if (! empty($errors)) {
                    $allErrors["file_{$index}"] = $errors;
                }
            }
        }

        return $allErrors;
    }

    /**
     * Basic file validation
     */
    protected function validateBasicFile(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new ValidationException(validator([], []), [
                'file' => ['The uploaded file is invalid or corrupted.'],
            ]);
        }

        if ($file->getSize() === 0) {
            throw new ValidationException(validator([], []), [
                'file' => ['The uploaded file is empty.'],
            ]);
        }

        // Check for dangerous file extensions
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, $this->dangerousExtensions)) {
            throw new ValidationException(validator([], []), [
                'file' => ['This file type is not allowed for security reasons.'],
            ]);
        }
    }

    /**
     * Validate MIME type against context-specific allowed types
     */
    protected function validateMimeType(UploadedFile $file, string $context): void
    {
        $mimeType = $file->getMimeType();
        $allowedTypes = $this->contextAllowedMimeTypes[$context] ?? [];

        if (empty($allowedTypes)) {
            // If no specific types defined for context, use global audio types
            $allowedTypes = $this->contextAllowedMimeTypes[FileUploadSetting::CONTEXT_PROJECTS];
        }

        if (! in_array($mimeType, $allowedTypes)) {
            $allowedTypesString = implode(', ', $allowedTypes);
            throw new ValidationException(validator([], []), [
                'file' => ["File type '{$mimeType}' is not allowed. Allowed types: {$allowedTypesString}"],
            ]);
        }
    }

    /**
     * Validate file signature matches MIME type for security
     */
    protected function validateFileSignature(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();

        // Skip signature validation for text files
        if ($mimeType === 'text/plain') {
            return;
        }

        if (! isset($this->fileSignatures[$mimeType])) {
            Log::warning("No file signature validation available for MIME type: {$mimeType}");

            return;
        }

        $fileHandle = fopen($file->getPathname(), 'rb');
        if (! $fileHandle) {
            throw new ValidationException(validator([], []), [
                'file' => ['Unable to read file for validation.'],
            ]);
        }

        $header = fread($fileHandle, 32); // Read first 32 bytes
        fclose($fileHandle);

        $headerHex = strtoupper(bin2hex($header));
        $signatures = $this->fileSignatures[$mimeType];

        $validSignature = false;
        foreach ($signatures as $signature) {
            if (is_array($signature)) {
                // Multiple possible signatures
                foreach ($signature as $sig) {
                    $sig = str_replace(' ', '', $sig);
                    if (strpos($headerHex, $sig) === 0) {
                        $validSignature = true;
                        break 2;
                    }
                }
            } else {
                $signature = str_replace(' ', '', $signature);
                if (strpos($headerHex, $signature) === 0) {
                    $validSignature = true;
                    break;
                }
            }
        }

        if (! $validSignature) {
            throw new ValidationException(validator([], []), [
                'file' => ['File content does not match the declared file type. This may indicate a security risk.'],
            ]);
        }
    }

    /**
     * Validate file size against context-specific limits
     */
    protected function validateFileSize(UploadedFile $file, string $context): void
    {
        $fileSize = $file->getSize();
        $maxSize = $this->settingsService->getSetting('max_file_size_mb', $context) * 1024 * 1024;

        if ($fileSize > $maxSize) {
            $maxSizeMB = round($maxSize / (1024 * 1024), 2);
            $fileSizeMB = round($fileSize / (1024 * 1024), 2);

            throw new ValidationException(validator([], []), [
                'file' => ["File size ({$fileSizeMB}MB) exceeds the maximum allowed size of {$maxSizeMB}MB for this context."],
            ]);
        }
    }

    /**
     * Context-specific validation (storage limits, etc.)
     */
    protected function validateContextSpecific(UploadedFile $file, string $context, $model): void
    {
        $fileSize = $file->getSize();

        // Validate storage capacity for projects and pitches
        if ($model instanceof Project) {
            if (! $model->hasStorageCapacity($fileSize)) {
                throw new ValidationException(validator([], []), [
                    'file' => ['Project storage limit would be exceeded by this upload.'],
                ]);
            }
        } elseif ($model instanceof Pitch) {
            if (! $model->hasStorageCapacity($fileSize)) {
                throw new ValidationException(validator([], []), [
                    'file' => ['Pitch storage limit would be exceeded by this upload.'],
                ]);
            }
        }

        // Additional context-specific validations can be added here
        switch ($context) {
            case FileUploadSetting::CONTEXT_PITCHES:
                $this->validatePitchSpecific($file, $model);
                break;
            case FileUploadSetting::CONTEXT_PROJECTS:
                $this->validateProjectSpecific($file, $model);
                break;
            case FileUploadSetting::CONTEXT_CLIENT_PORTALS:
                $this->validateClientPortalSpecific($file, $model);
                break;
        }
    }

    /**
     * Security validation for malicious content
     */
    protected function validateSecurity(UploadedFile $file): void
    {
        // Check for embedded scripts in files
        $this->scanForMaliciousContent($file);

        // Validate file name for security
        $this->validateFileName($file);
    }

    /**
     * Scan file for potentially malicious content
     */
    protected function scanForMaliciousContent(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();

        // For text-based files, scan for suspicious content
        if (strpos($mimeType, 'text/') === 0 || $mimeType === 'application/pdf') {
            $content = file_get_contents($file->getPathname());

            // Look for suspicious patterns
            $suspiciousPatterns = [
                '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
                '/javascript:/i',
                '/vbscript:/i',
                '/onload\s*=/i',
                '/onerror\s*=/i',
                '/<iframe\b/i',
                '/<object\b/i',
                '/<embed\b/i',
            ];

            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    throw new ValidationException(validator([], []), [
                        'file' => ['File contains potentially malicious content and cannot be uploaded.'],
                    ]);
                }
            }
        }
    }

    /**
     * Validate file name for security
     */
    protected function validateFileName(UploadedFile $file): void
    {
        $fileName = $file->getClientOriginalName();

        // Check for path traversal attempts
        if (strpos($fileName, '..') !== false || strpos($fileName, '/') !== false || strpos($fileName, '\\') !== false) {
            throw new ValidationException(validator([], []), [
                'file' => ['File name contains invalid characters.'],
            ]);
        }

        // Check for null bytes
        if (strpos($fileName, "\0") !== false) {
            throw new ValidationException(validator([], []), [
                'file' => ['File name contains invalid characters.'],
            ]);
        }

        // Validate file name length
        if (strlen($fileName) > 255) {
            throw new ValidationException(validator([], []), [
                'file' => ['File name is too long (maximum 255 characters).'],
            ]);
        }
    }

    /**
     * Pitch-specific validation
     */
    protected function validatePitchSpecific(UploadedFile $file, $pitch): void
    {
        // Ensure only audio files for pitches
        $mimeType = $file->getMimeType();
        if (strpos($mimeType, 'audio/') !== 0) {
            throw new ValidationException(validator([], []), [
                'file' => ['Only audio files are allowed for pitch uploads.'],
            ]);
        }

        // Additional pitch-specific validations can be added here
    }

    /**
     * Project-specific validation
     */
    protected function validateProjectSpecific(UploadedFile $file, $project): void
    {
        // Projects can have audio, images, and documents
        // Additional project-specific validations can be added here
    }

    /**
     * Client portal-specific validation
     */
    protected function validateClientPortalSpecific(UploadedFile $file, $model): void
    {
        // Client portals have specific requirements
        // Additional client portal-specific validations can be added here
    }

    /**
     * Get allowed file types for a context
     */
    public function getAllowedMimeTypes(string $context): array
    {
        return $this->contextAllowedMimeTypes[$context] ?? [];
    }

    /**
     * Get human-readable allowed file types for a context
     */
    public function getAllowedFileTypesDescription(string $context): string
    {
        $mimeTypes = $this->getAllowedMimeTypes($context);
        $descriptions = [];

        foreach ($mimeTypes as $mimeType) {
            switch ($mimeType) {
                case 'audio/mpeg':
                    $descriptions[] = 'MP3';
                    break;
                case 'audio/wav':
                case 'audio/x-wav':
                    $descriptions[] = 'WAV';
                    break;
                case 'audio/flac':
                    $descriptions[] = 'FLAC';
                    break;
                case 'audio/aac':
                    $descriptions[] = 'AAC';
                    break;
                case 'audio/ogg':
                    $descriptions[] = 'OGG';
                    break;
                case 'audio/x-m4a':
                    $descriptions[] = 'M4A';
                    break;
                case 'audio/mp4':
                    $descriptions[] = 'MP4 Audio';
                    break;
                case 'image/jpeg':
                    $descriptions[] = 'JPEG';
                    break;
                case 'image/png':
                    $descriptions[] = 'PNG';
                    break;
                case 'image/gif':
                    $descriptions[] = 'GIF';
                    break;
                case 'image/webp':
                    $descriptions[] = 'WebP';
                    break;
                case 'application/pdf':
                    $descriptions[] = 'PDF';
                    break;
                case 'text/plain':
                    $descriptions[] = 'Text';
                    break;
            }
        }

        return implode(', ', array_unique($descriptions));
    }

    /**
     * Validate chunk integrity using hash verification
     */
    public function validateChunkIntegrity(string $chunkPath, string $expectedHash, string $algorithm = 'sha256'): bool
    {
        if (! file_exists($chunkPath)) {
            return false;
        }

        $actualHash = hash_file($algorithm, $chunkPath);

        return hash_equals($expectedHash, $actualHash);
    }

    /**
     * Generate hash for a file chunk
     */
    public function generateChunkHash(string $chunkPath, string $algorithm = 'sha256'): string
    {
        return hash_file($algorithm, $chunkPath);
    }

    /**
     * Validate file against existing FileManagementService constraints
     * This ensures compatibility with existing validation logic
     */
    public function validateWithExistingConstraints(UploadedFile $file, string $context, $model = null): array
    {
        $errors = [];

        try {
            // Use existing config-based validation for backward compatibility
            $this->validateAgainstConfigLimits($file, $context);

            // Validate against existing storage tracking
            if ($model) {
                $this->validateExistingStorageLimits($file, $model);
            }

            // Validate using our enhanced validation
            $enhancedErrors = $this->validateFile($file, $context, $model);
            $errors = array_merge($errors, $enhancedErrors);

        } catch (ValidationException $e) {
            $errors = array_merge($errors, $e->errors());
        } catch (\Exception $e) {
            $errors['file'] = [$e->getMessage()];
        }

        return $errors;
    }

    /**
     * Validate against existing config-based file size limits
     */
    protected function validateAgainstConfigLimits(UploadedFile $file, string $context): void
    {
        $fileSize = $file->getSize();

        // Check against existing config limits for backward compatibility
        $configKey = match ($context) {
            FileUploadSetting::CONTEXT_PROJECTS => 'files.max_project_file_size',
            FileUploadSetting::CONTEXT_PITCHES => 'files.max_pitch_file_size',
            default => 'files.max_project_file_size'
        };

        $maxSize = config($configKey, 200 * 1024 * 1024); // Default 200MB

        if ($fileSize > $maxSize) {
            $maxSizeMB = round($maxSize / (1024 * 1024), 2);
            $fileSizeMB = round($fileSize / (1024 * 1024), 2);

            throw new ValidationException(validator([], []), [
                'file' => ["File '{$file->getClientOriginalName()}' ({$fileSizeMB}MB) exceeds the maximum allowed size of {$maxSizeMB}MB."],
            ]);
        }
    }

    /**
     * Validate against existing storage limits using the same logic as FileManagementService
     */
    protected function validateExistingStorageLimits(UploadedFile $file, $model): void
    {
        $fileSize = $file->getSize();

        if ($model instanceof Project) {
            // Use the same validation logic as FileManagementService
            if (! $model->hasStorageCapacity($fileSize)) {
                throw new ValidationException(validator([], []), [
                    'file' => ['Project storage limit reached. Cannot upload file.'],
                ]);
            }
        } elseif ($model instanceof Pitch) {
            // Use the same validation logic as FileManagementService
            if (! $model->hasStorageCapacity($fileSize)) {
                throw new ValidationException(validator([], []), [
                    'file' => ['Pitch storage limit exceeded. Cannot upload file.'],
                ]);
            }
        }
    }

    /**
     * Get comprehensive validation errors with detailed messages for storage limits
     */
    public function getDetailedValidationErrors(UploadedFile $file, string $context, $model = null): array
    {
        $errors = [];

        try {
            // Basic validation
            $this->validateBasicFile($file);
        } catch (ValidationException $e) {
            $errors = array_merge($errors, $e->errors());
        }

        try {
            // MIME type validation with detailed error
            $this->validateMimeType($file, $context);
        } catch (ValidationException $e) {
            $errors = array_merge($errors, $e->errors());
        }

        try {
            // Size validation with detailed breakdown
            $this->validateFileSizeDetailed($file, $context);
        } catch (ValidationException $e) {
            $errors = array_merge($errors, $e->errors());
        }

        try {
            // Storage limit validation with detailed breakdown
            if ($model) {
                $this->validateStorageLimitsDetailed($file, $model);
            }
        } catch (ValidationException $e) {
            $errors = array_merge($errors, $e->errors());
        }

        try {
            // Security validation
            $this->validateSecurity($file);
        } catch (ValidationException $e) {
            $errors = array_merge($errors, $e->errors());
        }

        return $errors;
    }

    /**
     * Validate file size with detailed error messages
     */
    protected function validateFileSizeDetailed(UploadedFile $file, string $context): void
    {
        $fileSize = $file->getSize();
        $fileName = $file->getClientOriginalName();

        // Check against enhanced settings
        $maxSize = $this->settingsService->getSetting('max_file_size_mb', $context) * 1024 * 1024;

        // Also check against config limits for compatibility
        $configKey = match ($context) {
            FileUploadSetting::CONTEXT_PROJECTS => 'files.max_project_file_size',
            FileUploadSetting::CONTEXT_PITCHES => 'files.max_pitch_file_size',
            default => 'files.max_project_file_size'
        };
        $configMaxSize = config($configKey, 200 * 1024 * 1024);

        // Use the more restrictive limit
        $effectiveMaxSize = min($maxSize, $configMaxSize);

        if ($fileSize > $effectiveMaxSize) {
            $maxSizeMB = round($effectiveMaxSize / (1024 * 1024), 2);
            $fileSizeMB = round($fileSize / (1024 * 1024), 2);

            $errorMessage = "File '{$fileName}' ({$fileSizeMB}MB) exceeds the maximum allowed size of {$maxSizeMB}MB";

            // Add context-specific guidance
            switch ($context) {
                case FileUploadSetting::CONTEXT_PITCHES:
                    $errorMessage .= ' for pitch uploads. Consider compressing your audio file or using a different format.';
                    break;
                case FileUploadSetting::CONTEXT_PROJECTS:
                    $errorMessage .= ' for project uploads. Large files can be uploaded in chunks automatically.';
                    break;
                case FileUploadSetting::CONTEXT_CLIENT_PORTALS:
                    $errorMessage .= ' for client portal uploads. Please contact support if you need to upload larger files.';
                    break;
                default:
                    $errorMessage .= '. Please reduce the file size and try again.';
            }

            throw new ValidationException(validator([], []), [
                'file' => [$errorMessage],
            ]);
        }
    }

    /**
     * Validate storage limits with detailed error messages and suggestions
     */
    protected function validateStorageLimitsDetailed(UploadedFile $file, $model): void
    {
        $fileSize = $file->getSize();
        $fileName = $file->getClientOriginalName();

        if ($model instanceof Project) {
            if (! $model->hasStorageCapacity($fileSize)) {
                $currentUsage = $model->storage_used ?? 0;
                $totalLimit = $model->total_storage_limit_bytes ?? config('files.default_project_storage_limit', 1024 * 1024 * 1024); // 1GB default
                $remainingSpace = $totalLimit - $currentUsage;

                $currentUsageMB = round($currentUsage / (1024 * 1024), 2);
                $totalLimitMB = round($totalLimit / (1024 * 1024), 2);
                $remainingSpaceMB = round($remainingSpace / (1024 * 1024), 2);
                $fileSizeMB = round($fileSize / (1024 * 1024), 2);

                $errorMessage = "Cannot upload '{$fileName}' ({$fileSizeMB}MB). ";
                $errorMessage .= "Project storage: {$currentUsageMB}MB used of {$totalLimitMB}MB total. ";
                $errorMessage .= "Available space: {$remainingSpaceMB}MB. ";
                $errorMessage .= 'Please delete some files or upgrade your storage plan.';

                throw new ValidationException(validator([], []), [
                    'file' => [$errorMessage],
                ]);
            }
        } elseif ($model instanceof Pitch) {
            if (! $model->hasStorageCapacity($fileSize)) {
                $currentUsage = $model->storage_used ?? 0;
                $totalLimit = config('files.default_pitch_storage_limit', 500 * 1024 * 1024); // 500MB default
                $remainingSpace = $totalLimit - $currentUsage;

                $currentUsageMB = round($currentUsage / (1024 * 1024), 2);
                $totalLimitMB = round($totalLimit / (1024 * 1024), 2);
                $remainingSpaceMB = round($remainingSpace / (1024 * 1024), 2);
                $fileSizeMB = round($fileSize / (1024 * 1024), 2);

                $errorMessage = "Cannot upload '{$fileName}' ({$fileSizeMB}MB). ";
                $errorMessage .= "Pitch storage: {$currentUsageMB}MB used of {$totalLimitMB}MB total. ";
                $errorMessage .= "Available space: {$remainingSpaceMB}MB. ";
                $errorMessage .= 'Please delete some files to make space.';

                throw new ValidationException(validator([], []), [
                    'file' => [$errorMessage],
                ]);
            }
        }
    }

    /**
     * Check if file would exceed storage limits without throwing exception
     */
    public function wouldExceedStorageLimit(UploadedFile $file, $model): bool
    {
        if (! $model) {
            return false;
        }

        $fileSize = $file->getSize();

        if ($model instanceof Project || $model instanceof Pitch) {
            return ! $model->hasStorageCapacity($fileSize);
        }

        return false;
    }

    /**
     * Get storage usage information for a model
     */
    public function getStorageInfo($model): array
    {
        if (! $model) {
            return [];
        }

        $info = [];

        if ($model instanceof Project) {
            $currentUsage = $model->storage_used ?? 0;
            $totalLimit = $model->total_storage_limit_bytes ?? config('files.default_project_storage_limit', 1024 * 1024 * 1024);

            $info = [
                'type' => 'project',
                'current_usage_bytes' => $currentUsage,
                'total_limit_bytes' => $totalLimit,
                'remaining_bytes' => $totalLimit - $currentUsage,
                'current_usage_mb' => round($currentUsage / (1024 * 1024), 2),
                'total_limit_mb' => round($totalLimit / (1024 * 1024), 2),
                'remaining_mb' => round(($totalLimit - $currentUsage) / (1024 * 1024), 2),
                'usage_percentage' => $totalLimit > 0 ? round(($currentUsage / $totalLimit) * 100, 1) : 0,
            ];
        } elseif ($model instanceof Pitch) {
            $currentUsage = $model->storage_used ?? 0;
            $totalLimit = config('files.default_pitch_storage_limit', 500 * 1024 * 1024);

            $info = [
                'type' => 'pitch',
                'current_usage_bytes' => $currentUsage,
                'total_limit_bytes' => $totalLimit,
                'remaining_bytes' => $totalLimit - $currentUsage,
                'current_usage_mb' => round($currentUsage / (1024 * 1024), 2),
                'total_limit_mb' => round($totalLimit / (1024 * 1024), 2),
                'remaining_mb' => round(($totalLimit - $currentUsage) / (1024 * 1024), 2),
                'usage_percentage' => $totalLimit > 0 ? round(($currentUsage / $totalLimit) * 100, 1) : 0,
            ];
        }

        return $info;
    }

    /**
     * Validate file for chunked upload session
     */
    public function validateForChunkedUpload(UploadedFile $file, string $context, $model = null, int $totalChunks = 1): array
    {
        $errors = [];

        try {
            // All standard validations
            $errors = $this->validateWithExistingConstraints($file, $context, $model);

            // Additional chunked upload specific validations
            if ($totalChunks > 1) {
                $this->validateChunkedUploadSpecific($file, $totalChunks);
            }

        } catch (ValidationException $e) {
            $errors = array_merge($errors, $e->errors());
        } catch (\Exception $e) {
            $errors['file'] = [$e->getMessage()];
        }

        return $errors;
    }

    /**
     * Validate chunked upload specific requirements
     */
    protected function validateChunkedUploadSpecific(UploadedFile $file, int $totalChunks): void
    {
        // Validate chunk count is reasonable
        if ($totalChunks > 10000) {
            throw new ValidationException(validator([], []), [
                'file' => ['File has too many chunks. Please use larger chunk sizes.'],
            ]);
        }

        // Validate that file size justifies chunking
        $fileSize = $file->getSize();
        $minChunkingSize = 10 * 1024 * 1024; // 10MB minimum for chunking

        if ($totalChunks > 1 && $fileSize < $minChunkingSize) {
            Log::info('Small file being chunked unnecessarily', [
                'file_size' => $fileSize,
                'total_chunks' => $totalChunks,
                'filename' => $file->getClientOriginalName(),
            ]);
        }
    }
}

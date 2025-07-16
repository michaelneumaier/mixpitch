<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\FileUploadSetting;
use Illuminate\Support\Facades\Log;

class ValidateUploadSettings
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $context = 'global'): Response
    {
        // Skip validation for non-upload requests
        if (!$this->isUploadRequest($request)) {
            return $next($request);
        }

        try {
            // Determine context from request if not provided
            if ($context === 'auto') {
                $context = $this->determineContextFromRequest($request);
            }

            // Validate context
            if (!FileUploadSetting::validateContext($context)) {
                return response()->json([
                    'error' => 'Invalid upload context',
                    'valid_contexts' => FileUploadSetting::getValidContexts()
                ], 400);
            }

            // Get settings for the context
            $settings = FileUploadSetting::getSettings($context);

            // Validate file size if file is present
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $this->validateFileSize($file, $settings, $context);
            }

            // Validate chunk size for chunked uploads
            if ($request->hasFile('chunk')) {
                $chunk = $request->file('chunk');
                $this->validateChunkSize($chunk, $settings, $context);
            }

            // Validate total upload size for session creation
            if ($request->has('total_size')) {
                $this->validateTotalSize($request->input('total_size'), $settings, $context);
            }

            // Add settings to request for use in controllers
            $request->merge(['_upload_settings' => $settings, '_upload_context' => $context]);

            Log::info('Upload validation passed', [
                'context' => $context,
                'settings' => $settings,
                'request_path' => $request->path()
            ]);

        } catch (\Exception $e) {
            Log::error('Upload validation failed', [
                'context' => $context,
                'error' => $e->getMessage(),
                'request_path' => $request->path()
            ]);

            return response()->json([
                'error' => 'Upload validation failed',
                'message' => $e->getMessage()
            ], 422);
        }

        return $next($request);
    }

    /**
     * Check if this is an upload request
     */
    private function isUploadRequest(Request $request): bool
    {
        return $request->hasFile('file') || 
               $request->hasFile('chunk') || 
               $request->has('total_size') ||
               str_contains($request->path(), 'upload');
    }

    /**
     * Determine context from request parameters
     */
    private function determineContextFromRequest(Request $request): string
    {
        // Check for explicit model_type parameter
        if ($request->has('model_type')) {
            return match($request->input('model_type')) {
                'projects' => FileUploadSetting::CONTEXT_PROJECTS,
                'pitches' => FileUploadSetting::CONTEXT_PITCHES,
                'client_portals' => FileUploadSetting::CONTEXT_CLIENT_PORTALS,
                default => FileUploadSetting::CONTEXT_GLOBAL
            };
        }

        // Check route parameters
        if ($request->route('project')) {
            return FileUploadSetting::CONTEXT_PROJECTS;
        }

        if ($request->route('pitch')) {
            return FileUploadSetting::CONTEXT_PITCHES;
        }

        // Check path patterns
        $path = $request->path();
        if (str_contains($path, 'project')) {
            return FileUploadSetting::CONTEXT_PROJECTS;
        }

        if (str_contains($path, 'pitch')) {
            return FileUploadSetting::CONTEXT_PITCHES;
        }

        if (str_contains($path, 'client-portal')) {
            return FileUploadSetting::CONTEXT_CLIENT_PORTALS;
        }

        return FileUploadSetting::CONTEXT_GLOBAL;
    }

    /**
     * Validate file size against settings
     */
    private function validateFileSize($file, array $settings, string $context): void
    {
        $maxSizeBytes = $settings[FileUploadSetting::MAX_FILE_SIZE_MB] * 1024 * 1024;
        $actualSize = $file->getSize();

        if ($actualSize > $maxSizeBytes) {
            throw new \Exception(
                "File size ({$this->formatBytes($actualSize)}) exceeds maximum allowed size for {$context} context ({$settings[FileUploadSetting::MAX_FILE_SIZE_MB]}MB)"
            );
        }
    }

    /**
     * Validate chunk size against settings
     */
    private function validateChunkSize($chunk, array $settings, string $context): void
    {
        $maxChunkSizeBytes = $settings[FileUploadSetting::CHUNK_SIZE_MB] * 1024 * 1024;
        $actualSize = $chunk->getSize();

        if ($actualSize > $maxChunkSizeBytes) {
            throw new \Exception(
                "Chunk size ({$this->formatBytes($actualSize)}) exceeds maximum allowed chunk size for {$context} context ({$settings[FileUploadSetting::CHUNK_SIZE_MB]}MB)"
            );
        }
    }

    /**
     * Validate total upload size for session creation
     */
    private function validateTotalSize(int $totalSize, array $settings, string $context): void
    {
        $maxSizeBytes = $settings[FileUploadSetting::MAX_FILE_SIZE_MB] * 1024 * 1024;

        if ($totalSize > $maxSizeBytes) {
            throw new \Exception(
                "Total upload size ({$this->formatBytes($totalSize)}) exceeds maximum allowed size for {$context} context ({$settings[FileUploadSetting::MAX_FILE_SIZE_MB]}MB)"
            );
        }
    }

    /**
     * Format bytes for human-readable output
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 1) . 'GB';
        } elseif ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . 'MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1) . 'KB';
        } else {
            return $bytes . 'B';
        }
    }
}
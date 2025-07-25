<?php

namespace App\Http\Middleware;

use App\Models\FileUploadSetting;
use App\Models\Pitch;
use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

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
        if (! $this->isUploadRequest($request)) {
            return $next($request);
        }

        try {
            // Determine context from request if not provided
            if ($context === 'auto') {
                $context = $this->determineContextFromRequest($request);
            }

            // Validate context
            if (! FileUploadSetting::validateContext($context)) {
                return response()->json([
                    'error' => 'Invalid upload context',
                    'valid_contexts' => FileUploadSetting::getValidContexts(),
                ], 400);
            }

            // Check authorization before validating file sizes
            $this->validateAuthorization($request, $context);

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

            // Validate file size for presigned URL requests
            if ($request->has('file_size') && ! $request->hasFile('file')) {
                $this->validateTotalSize($request->input('file_size'), $settings, $context);
            }

            // Add settings to request for use in controllers
            $request->merge(['_upload_settings' => $settings, '_upload_context' => $context]);

            Log::info('Upload validation passed', [
                'context' => $context,
                'settings' => $settings,
                'request_path' => $request->path(),
            ]);

        } catch (\Exception $e) {
            Log::error('Upload validation failed', [
                'context' => $context,
                'error' => $e->getMessage(),
                'request_path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Upload validation failed',
                'message' => $e->getMessage(),
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
               $request->has('file_size') || // For presigned URL requests
               str_contains($request->path(), 'upload') ||
               str_contains($request->path(), 'presigned');
    }

    /**
     * Determine context from request parameters
     */
    private function determineContextFromRequest(Request $request): string
    {
        // Check for explicit context parameter (used by presigned URL endpoints)
        if ($request->has('context')) {
            return $request->input('context');
        }

        // Check for explicit model_type parameter
        if ($request->has('model_type')) {
            return match ($request->input('model_type')) {
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

        if (str_contains($path, 'presigned')) {
            return FileUploadSetting::CONTEXT_GLOBAL; // Default for presigned, will be overridden by context param
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
     * Validate authorization for uploads
     */
    private function validateAuthorization(Request $request, string $context): void
    {
        // Skip authorization for non-authenticated requests (client portals use signed URLs)
        if (! $request->user()) {
            return;
        }

        if ($context === FileUploadSetting::CONTEXT_PROJECTS) {
            $project = $this->extractProjectFromRequest($request);
            if ($project && ! Gate::forUser($request->user())->allows('uploadFile', $project)) {
                throw new \Exception('Upload not allowed for this project. Project may be completed or you may not have permission.');
            }
        }

        if ($context === FileUploadSetting::CONTEXT_PITCHES) {
            $pitch = $this->extractPitchFromRequest($request);
            if ($pitch && ! Gate::forUser($request->user())->allows('uploadFile', $pitch)) {
                throw new \Exception('Upload not allowed for this pitch. Pitch may be in a terminal state or you may not have permission.');
            }
        }
    }

    /**
     * Extract project from request
     */
    private function extractProjectFromRequest(Request $request): ?Project
    {
        // Try route parameter first
        if ($request->route('project')) {
            return $request->route('project');
        }

        // Try request parameter
        if ($request->has('project_id')) {
            return Project::find($request->input('project_id'));
        }

        return null;
    }

    /**
     * Extract pitch from request
     */
    private function extractPitchFromRequest(Request $request): ?Pitch
    {
        // Try route parameter first
        if ($request->route('pitch')) {
            return $request->route('pitch');
        }

        // Try request parameter
        if ($request->has('pitch_id')) {
            return Pitch::find($request->input('pitch_id'));
        }

        return null;
    }

    /**
     * Format bytes for human-readable output
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 1).'GB';
        } elseif ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1).'MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1).'KB';
        } else {
            return $bytes.'B';
        }
    }
}

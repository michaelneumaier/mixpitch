<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FileUploadSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UploadSettingsController extends Controller
{
    /**
     * Get upload settings for a specific context
     */
    public function getSettings(Request $request, string $context = FileUploadSetting::CONTEXT_GLOBAL): JsonResponse
    {
        try {
            // Validate context
            if (! FileUploadSetting::validateContext($context)) {
                return response()->json([
                    'error' => 'Invalid context',
                    'valid_contexts' => FileUploadSetting::getValidContexts(),
                ], 400);
            }

            // Get settings for the context
            $settings = FileUploadSetting::getSettings($context);

            // Add metadata for frontend consumption
            $response = [
                'context' => $context,
                'settings' => $settings,
                'metadata' => [
                    'schema' => FileUploadSetting::getSettingsSchema(),
                    'defaults' => FileUploadSetting::getContextDefaults($context),
                    'validation_rules' => FileUploadSetting::getContextValidationRules($context),
                ],
                'computed' => [
                    'max_file_size_bytes' => $settings[FileUploadSetting::MAX_FILE_SIZE_MB] * 1024 * 1024,
                    'chunk_size_bytes' => $settings[FileUploadSetting::CHUNK_SIZE_MB] * 1024 * 1024,
                    'session_timeout_ms' => $settings[FileUploadSetting::SESSION_TIMEOUT_HOURS] * 60 * 60 * 1000,
                    'uppy_restrictions' => [
                        'maxFileSize' => $settings[FileUploadSetting::MAX_FILE_SIZE_MB] * 1024 * 1024,
                        'maxNumberOfFiles' => null, // Will be set by component
                        'allowedFileTypes' => ['audio/*', 'application/pdf', 'image/*', 'application/zip'],
                    ],
                    'upload_config' => [
                        'chunkSize' => $settings[FileUploadSetting::CHUNK_SIZE_MB] * 1024 * 1024,
                        'limit' => $settings[FileUploadSetting::MAX_CONCURRENT_UPLOADS],
                        'retryDelays' => array_fill(0, $settings[FileUploadSetting::MAX_RETRY_ATTEMPTS], 1000),
                    ],
                ],
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Failed to get upload settings', [
                'context' => $context,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve upload settings',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get upload settings for model-based context detection
     */
    public function getSettingsForModel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model_type' => 'required|string|in:App\\Models\\Project,App\\Models\\Pitch',
            'model_id' => 'required|integer|exists:projects,id|exists:pitches,id',
        ]);

        try {
            // Determine context from model type
            $context = match ($validated['model_type']) {
                'App\\Models\\Project' => FileUploadSetting::CONTEXT_PROJECTS,
                'App\\Models\\Pitch' => FileUploadSetting::CONTEXT_PITCHES,
                default => FileUploadSetting::CONTEXT_GLOBAL
            };

            return $this->getSettings($request, $context);

        } catch (\Exception $e) {
            Log::error('Failed to get model-based upload settings', [
                'model_type' => $validated['model_type'],
                'model_id' => $validated['model_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve upload settings for model',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Test upload settings by validating a hypothetical file
     */
    public function testSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'context' => 'required|string',
            'file_size' => 'required|integer|min:1',
            'file_type' => 'required|string',
        ]);

        try {
            $context = $validated['context'];
            $fileSize = $validated['file_size'];
            $fileType = $validated['file_type'];

            if (! FileUploadSetting::validateContext($context)) {
                return response()->json(['error' => 'Invalid context'], 400);
            }

            $settings = FileUploadSetting::getSettings($context);
            $maxSizeBytes = $settings[FileUploadSetting::MAX_FILE_SIZE_MB] * 1024 * 1024;

            $result = [
                'context' => $context,
                'file_size' => $fileSize,
                'file_type' => $fileType,
                'max_allowed_size' => $maxSizeBytes,
                'is_size_valid' => $fileSize <= $maxSizeBytes,
                'is_type_valid' => $this->isFileTypeAllowed($fileType),
                'chunking_enabled' => $settings[FileUploadSetting::ENABLE_CHUNKING],
                'will_be_chunked' => $settings[FileUploadSetting::ENABLE_CHUNKING] && $fileSize > ($settings[FileUploadSetting::CHUNK_SIZE_MB] * 1024 * 1024),
                'settings_used' => $settings,
            ];

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to test upload settings', [
                'request_data' => $validated,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to test upload settings',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Check if a file type is allowed
     */
    private function isFileTypeAllowed(string $mimeType): bool
    {
        $allowedTypes = ['audio/*', 'application/pdf', 'image/*', 'application/zip'];

        foreach ($allowedTypes as $allowedType) {
            if (str_ends_with($allowedType, '/*')) {
                $prefix = substr($allowedType, 0, -2);
                if (str_starts_with($mimeType, $prefix)) {
                    return true;
                }
            } elseif ($mimeType === $allowedType) {
                return true;
            }
        }

        return false;
    }
}

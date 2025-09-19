<?php

namespace App\Livewire;

use App\Models\Pitch;
use App\Models\Project;
use App\Services\FileManagementService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class GlobalFileUploader extends Component
{
    public bool $isVisible = false;

    public int $totalCount = 0;

    public int $uploadingCount = 0;

    public int $failedCount = 0;

    public int $completedCount = 0;

    public float $aggregateProgress = 0.0;

    protected ?FileManagementService $fileManagementService = null;

    public function boot(FileManagementService $fileManagementService): void
    {
        $this->fileManagementService = $fileManagementService;
    }

    /**
     * Handle completed batch from GlobalUploadManager (JS).
     * $payload is an array of items with keys: name, size, type, key, meta{ modelType, modelId, context }
     */
    public function handleGlobalUploadSuccess(array $payload): void
    {
        if (empty($payload)) {
            return;
        }

        try {
            // Track unique model contexts from uploaded files
            $modelContexts = [];

            foreach ($payload as $fileData) {
                $this->processUploadedFile($fileData);

                // Extract model context for event dispatch
                $meta = $fileData['meta'] ?? [];
                $modelType = $meta['modelType'] ?? null;
                $modelId = isset($meta['modelId']) ? (int) $meta['modelId'] : null;

                if ($modelType && $modelId) {
                    $contextKey = $modelType.':'.$modelId;
                    if (! isset($modelContexts[$contextKey])) {
                        $modelContexts[$contextKey] = [
                            'model_type' => $modelType,
                            'model_id' => $modelId,
                            'count' => 0,
                        ];
                    }
                    $modelContexts[$contextKey]['count']++;
                }
            }

            $this->completedCount += count($payload);

            // Dispatch context-specific events for each model that received files
            foreach ($modelContexts as $context) {
                $this->dispatch('filesUploaded', [
                    'count' => $context['count'],
                    'source' => 'global_uploader',
                    'model_type' => $context['model_type'],
                    'model_id' => $context['model_id'],
                ]);
            }

            // Simple separate event for storage updates
            $this->dispatch('storageChanged');

            $message = count($payload) === 1 ? 'File uploaded successfully!' : count($payload).' files uploaded successfully!';
            Toaster::success($message);
        } catch (\Throwable $e) {
            Log::error('Global uploader: failed to process uploaded files', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Toaster::error('Upload processing failed: '.$e->getMessage());
        }
    }

    protected function processUploadedFile(array $fileData): void
    {
        $filename = $fileData['name'] ?? 'uploaded_file';
        $s3Key = $fileData['key'] ?? null;
        $size = isset($fileData['size']) ? (int) $fileData['size'] : 0;
        $type = $fileData['type'] ?? 'application/octet-stream';

        $meta = $fileData['meta'] ?? [];
        $modelType = $meta['modelType'] ?? null;
        $modelId = isset($meta['modelId']) ? (int) $meta['modelId'] : null;
        $context = $meta['context'] ?? null;

        if (! $s3Key || ! $modelType || ! $modelId) {
            throw new \InvalidArgumentException('Missing required upload metadata.');
        }

        if ($modelType === Project::class) {
            $project = Project::findOrFail($modelId);

            // Special handling for client portal uploads
            if ($context === 'client_portal' && $project->isClientManagement()) {
                // For client portal uploads, we don't need auth check as the signed URL is the authorization
                $this->fileManagementService->createProjectFileFromS3(
                    $project,
                    $s3Key,
                    $filename,
                    $size,
                    $type,
                    null, // No authenticated user for client portal uploads
                    [
                        'uploaded_by_client' => true,
                        'client_email' => $project->client_email,
                        'upload_context' => 'client_portal',
                    ]
                );

                return;
            }

            // Regular upload authorization check
            if (! Gate::allows('uploadFile', $project)) {
                throw new \RuntimeException('Not authorized to upload to this project.');
            }

            $this->fileManagementService->createProjectFileFromS3(
                $project,
                $s3Key,
                $filename,
                $size,
                $type,
                auth()->user()
            );

            return;
        }

        if ($modelType === Pitch::class) {
            $pitch = Pitch::findOrFail($modelId);

            // Special handling for client management projects where producer is uploading
            if ($pitch->project->isClientManagement() && auth()->check() && (int) auth()->id() === (int) $pitch->user_id) {
                // Producer uploading to their own pitch in a client management project
                $this->fileManagementService->createPitchFileFromS3(
                    $pitch,
                    $s3Key,
                    $filename,
                    $size,
                    $type,
                    auth()->user()
                );

                return;
            }

            // Regular authorization check for other cases
            if (! Gate::allows('uploadFile', $pitch)) {
                throw new \RuntimeException('Not authorized to upload to this pitch.');
            }

            $this->fileManagementService->createPitchFileFromS3(
                $pitch,
                $s3Key,
                $filename,
                $size,
                $type,
                auth()->user()
            );

            return;
        }

        throw new \InvalidArgumentException('Unsupported modelType for upload: '.$modelType);
    }

    public function render()
    {
        return view('livewire.global-file-uploader');
    }
}

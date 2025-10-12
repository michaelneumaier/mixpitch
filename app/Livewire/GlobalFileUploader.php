<?php

namespace App\Livewire;

use App\Models\Pitch;
use App\Models\Project;
use App\Services\FileManagementService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
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

        // Special handling for version uploads - don't process, just notify modal
        if (isset($meta['isVersionUpload']) && $meta['isVersionUpload']) {
            $eventPayload = [
                'name' => $filename,
                'key' => $s3Key,
                'size' => $size,
                'type' => $type,
                'meta' => $meta,
            ];

            // Dispatch browser CustomEvent to notify UploadVersionModal
            $this->js("
                window.dispatchEvent(new CustomEvent('version-file-uploaded', {
                    detail: ".json_encode($eventPayload).'
                }));
            ');

            return; // Early return - don't create file record
        }

        if (! $s3Key || ! $modelType || ! $modelId) {
            throw new \InvalidArgumentException('Missing required upload metadata.');
        }

        if ($modelType === Project::class) {
            $project = Project::findOrFail($modelId);

            // Check if authenticated user is the client for this project
            $isAuthenticatedClient = auth()->check() &&
                ($project->client_user_id === auth()->id() ||
                 $project->client_email === auth()->user()->email);

            // Special handling for client portal uploads (authenticated or unauthenticated)
            if ($project->isClientManagement() && ($context === 'client_portal' || $isAuthenticatedClient)) {
                // For client portal uploads, authorization is via signed URL (unauthenticated)
                // or via client relationship (authenticated)
                $this->fileManagementService->createProjectFileFromS3(
                    $project,
                    $s3Key,
                    $filename,
                    $size,
                    $type,
                    auth()->check() ? auth()->user() : null, // Pass auth user if authenticated
                    [
                        'uploaded_by_client' => true,
                        'client_email' => auth()->check() ? auth()->user()->email : $project->client_email,
                        'upload_context' => 'client_portal',
                        'authenticated_client' => auth()->check(),
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

    /**
     * Process bulk version uploads in background
     * Called via Livewire.dispatch from JavaScript when uploads complete
     */
    #[On('processBulkVersionsBackground')]
    public function processBulkVersions(int $pitchId, array $files, array $manualOverrides): void
    {
        Log::info('Processing bulk versions in background', [
            'pitch_id' => $pitchId,
            'file_count' => count($files),
            'overrides_count' => count($manualOverrides),
        ]);

        try {
            $pitch = Pitch::findOrFail($pitchId);

            // Authorization check
            if (! Gate::allows('uploadFile', $pitch)) {
                Log::warning('Unauthorized bulk version upload attempt', [
                    'pitch_id' => $pitchId,
                    'user_id' => auth()->id(),
                ]);
                Toaster::error('Not authorized to upload to this pitch');

                return;
            }

            // Convert overrides format: [index => fileId] + files → [fileId => fileData]
            $manualMatches = [];
            foreach ($manualOverrides as $index => $fileId) {
                if ($fileId && isset($files[$index])) {
                    $manualMatches[$fileId] = $files[$index];
                }
            }

            // Use existing service method
            $result = $this->fileManagementService->bulkUploadFileVersions(
                $pitch,
                $files,
                auth()->user(),
                $manualMatches
            );

            $versionCount = count($result['created_versions']);
            $newFileCount = count($result['new_files']);

            $messages = [];
            if ($versionCount > 0) {
                $messages[] = "{$versionCount} new version".($versionCount !== 1 ? 's' : '');
            }
            if ($newFileCount > 0) {
                $messages[] = "{$newFileCount} new file".($newFileCount !== 1 ? 's' : '');
            }

            Toaster::success('Uploaded '.implode(' and ', $messages));

            // Dispatch events for UI refresh
            $this->dispatch('filesUploaded', [
                'count' => $versionCount + $newFileCount,
                'model_type' => Pitch::class,
                'model_id' => $pitchId,
            ]);
            $this->dispatch('refreshFiles');

            Log::info('Bulk versions processed successfully in background', [
                'pitch_id' => $pitchId,
                'versions_created' => $versionCount,
                'new_files' => $newFileCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing bulk versions in background', [
                'pitch_id' => $pitchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Toaster::error('Failed to process files: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.global-file-uploader');
    }
}

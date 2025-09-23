<?php

namespace App\Livewire;

use App\Models\FileUploadSetting;
use App\Services\FileManagementService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class UppyFileUploader extends Component
{
    public Model $model;

    public bool $allowMultiple = true;

    public string $uploadContext = FileUploadSetting::CONTEXT_GLOBAL;

    public array $acceptedFileTypes = [];

    public int $maxFiles = 1000;

    // Dynamic settings loaded from FileUploadSetting
    protected array $uploadSettings = [];

    // Uppy specific properties
    public array $uploadedFiles = [];

    protected ?FileManagementService $fileManagementService = null;

    protected function getFileManagementService(): FileManagementService
    {
        if (! $this->fileManagementService) {
            $this->fileManagementService = app(FileManagementService::class);
        }

        return $this->fileManagementService;
    }

    public function mount(Model $model, array $config = [])
    {
        $this->model = $model;
        $this->fileManagementService = app(FileManagementService::class);

        // Determine upload context based on model type
        $this->uploadContext = $this->determineUploadContext($model);

        // Load accepted file types for this context
        $this->loadAcceptedFileTypes();

        // Load upload settings for this context
        $this->loadUploadSettings();

        // Apply configuration overrides
        $this->applyConfiguration($config);

        Log::info('UppyFileUploader initialized', [
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'context' => $this->uploadContext,
            'allow_multiple' => $this->allowMultiple,
            'settings' => $this->uploadSettings,
        ]);
    }

    /**
     * Listen for file uploads from any source (including Google Drive)
     */
    #[On('filesUploaded')]
    public function handleFilesUploaded($eventData): void
    {
        // Check if this event is for our model
        if (isset($eventData['model_type']) && isset($eventData['model_id'])) {
            if ($eventData['model_type'] === get_class($this->model) && $eventData['model_id'] == $this->model->id) {

                $source = $eventData['source'] ?? 'uppy';

                // Only show messages for non-Google Drive sources (Google Drive shows its own)
                if ($source !== 'google_drive') {
                    $count = $eventData['count'] ?? 1;
                    $message = $count === 1
                        ? 'File uploaded successfully!'
                        : "{$count} files uploaded successfully!";

                    Toaster::success($message);
                }

                // Dispatch refresh event to parent components
                $this->dispatch('refreshFiles');
            }
        }
    }

    /**
     * Handle successful Uppy S3 uploads
     */
    public function handleUploadSuccess($uploadData)
    {
        if (empty($uploadData)) {
            return;
        }

        try {
            foreach ($uploadData as $fileData) {
                $this->processS3UploadedFile($fileData);
            }

            $this->dispatch('filesUploaded', [
                'count' => count($uploadData),
                'model_type' => get_class($this->model),
                'model_id' => $this->model->id,
            ]);

            // Show success notification
            $fileCount = count($uploadData);
            $message = $fileCount === 1
                ? 'File uploaded successfully!'
                : "{$fileCount} files uploaded successfully!";
            Toaster::success($message);

            // Dispatch event to reset the uploader immediately
            $this->dispatch('resetUploader');

            // Clear uploaded files after processing
            $this->uploadedFiles = [];

        } catch (\Exception $e) {
            Log::error('Uppy upload processing failed', [
                'error' => $e->getMessage(),
                'upload_data' => $uploadData,
                'model_type' => get_class($this->model),
                'model_id' => $this->model->id,
            ]);

            $this->dispatch('fileUploadError', [
                'message' => 'Upload processing failed: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Process a file uploaded directly to S3 via Uppy
     */
    protected function processS3UploadedFile(array $fileData): void
    {
        try {
            Log::info('Processing S3 uploaded file', [
                'file_data' => $fileData,
                'model_type' => get_class($this->model),
                'model_id' => $this->model->id,
            ]);

            // Extract file information from Uppy response
            $filename = $fileData['name'] ?? 'uploaded_file';
            $s3Key = $fileData['key'] ?? null;
            $size = $fileData['size'] ?? 0;
            $type = $fileData['type'] ?? 'application/octet-stream';

            if (! $s3Key) {
                throw new \Exception('No S3 key provided in upload data');
            }

            // Check authorization before creating file records
            if ($this->model instanceof \App\Models\Project) {
                if (! Gate::allows('uploadFile', $this->model)) {
                    throw new \Exception('You are not authorized to upload files to this project. Project may be completed or you may not have permission.');
                }

                $fileRecord = $this->getFileManagementService()->createProjectFileFromS3(
                    $this->model,
                    $s3Key,
                    $filename,
                    $size,
                    $type,
                    auth()->user()
                );
            } elseif ($this->model instanceof \App\Models\Pitch) {
                if (! Gate::allows('uploadFile', $this->model)) {
                    throw new \Exception('You are not authorized to upload files to this pitch. Pitch may be in a terminal state or you may not have permission.');
                }

                $fileRecord = $this->getFileManagementService()->createPitchFileFromS3(
                    $this->model,
                    $s3Key,
                    $filename,
                    $size,
                    $type,
                    auth()->user()
                );
            } else {
                throw new \Exception('Unsupported model type for file upload');
            }

            Log::info('S3 uploaded file processed successfully', [
                'file_record_id' => $fileRecord->id,
                'filename' => $filename,
                's3_key' => $s3Key,
                'model_type' => get_class($this->model),
                'model_id' => $this->model->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process S3 uploaded file', [
                'error' => $e->getMessage(),
                'file_data' => $fileData,
                'model_type' => get_class($this->model),
                'model_id' => $this->model->id,
            ]);
            throw $e;
        }
    }

    /**
     * Get upload configuration for frontend
     */
    public function getUploadConfig(): array
    {
        $maxFileSize = $this->uploadSettings[FileUploadSetting::MAX_FILE_SIZE_MB] ?? 200;
        $chunkSize = $this->uploadSettings[FileUploadSetting::CHUNK_SIZE_MB] ?? 5;
        $maxConcurrent = $this->uploadSettings[FileUploadSetting::MAX_CONCURRENT_UPLOADS] ?? 3;
        $maxRetries = $this->uploadSettings[FileUploadSetting::MAX_RETRY_ATTEMPTS] ?? 3;
        $enableChunking = $this->uploadSettings[FileUploadSetting::ENABLE_CHUNKING] ?? true;

        return [
            'allowMultiple' => $this->allowMultiple,
            'maxFileSize' => $maxFileSize * 1024 * 1024, // Convert MB to bytes
            'maxFiles' => $this->maxFiles,
            'acceptedFileTypes' => $this->acceptedFileTypes,
            'context' => $this->uploadContext,
            'modelId' => $this->model->id,
            'modelType' => get_class($this->model),
            's3Endpoint' => '/s3/multipart',
            'csrfToken' => csrf_token(),

            // Uppy-specific configuration
            'restrictions' => [
                'maxFileSize' => $maxFileSize * 1024 * 1024,
                'maxNumberOfFiles' => $this->allowMultiple ? $this->maxFiles : 1,
                'allowedFileTypes' => $this->acceptedFileTypes,
            ],

            // Chunking configuration
            'chunking' => [
                'enabled' => $enableChunking,
                'chunkSize' => $chunkSize * 1024 * 1024, // Convert MB to bytes
                'limit' => $maxConcurrent,
                'retryDelays' => array_fill(0, $maxRetries, 1000), // 1 second delays
            ],

            // Settings metadata
            'settings' => $this->uploadSettings,
            'settingsContext' => $this->uploadContext,
        ];
    }

    /**
     * Load accepted file types for the current context
     */
    protected function loadAcceptedFileTypes(): void
    {
        try {
            // Get context-specific file types from configuration
            $contextTypes = config("file-types.contexts.{$this->uploadContext}");

            if ($contextTypes) {
                $this->acceptedFileTypes = $contextTypes;
            } else {
                $this->acceptedFileTypes = config('file-types.allowed_types', [
                    'audio/*',
                    'video/*',
                    'application/pdf',
                    'image/*',
                    'application/zip',
                ]);
            }

            Log::info('Accepted file types loaded', [
                'context' => $this->uploadContext,
                'accepted_types' => $this->acceptedFileTypes,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load accepted file types, using defaults', [
                'context' => $this->uploadContext,
                'error' => $e->getMessage(),
            ]);

            // Fallback to default values
            $this->acceptedFileTypes = [
                'audio/*',
                'video/*',
                'application/pdf',
                'image/*',
                'application/zip',
            ];
        }
    }

    /**
     * Load upload settings for the current context
     */
    protected function loadUploadSettings(): void
    {
        try {
            $this->uploadSettings = FileUploadSetting::getSettings($this->uploadContext);

            Log::info('Upload settings loaded', [
                'context' => $this->uploadContext,
                'settings' => $this->uploadSettings,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load upload settings, using defaults', [
                'context' => $this->uploadContext,
                'error' => $e->getMessage(),
            ]);

            // Fallback to default values
            $this->uploadSettings = FileUploadSetting::DEFAULT_VALUES;
        }
    }

    /**
     * Refresh upload settings (useful for dynamic updates)
     */
    public function refreshSettings(): void
    {
        $this->loadUploadSettings();
        $this->dispatch('settingsUpdated', $this->getUploadConfig());
    }

    /**
     * Determine upload context based on model type
     */
    protected function determineUploadContext(Model $model): string
    {
        if ($model instanceof \App\Models\Project) {
            return FileUploadSetting::CONTEXT_PROJECTS;
        } elseif ($model instanceof \App\Models\Pitch) {
            return FileUploadSetting::CONTEXT_PITCHES;
        }

        return FileUploadSetting::CONTEXT_GLOBAL;
    }

    /**
     * Apply configuration overrides
     */
    protected function applyConfiguration(array $config): void
    {
        if (isset($config['allowMultiple'])) {
            $this->allowMultiple = (bool) $config['allowMultiple'];
            $this->maxFiles = $this->allowMultiple ? 10 : 1;
        }

        if (isset($config['maxFiles'])) {
            $this->maxFiles = (int) $config['maxFiles'];
        }

        if (isset($config['acceptedFileTypes'])) {
            $this->acceptedFileTypes = $config['acceptedFileTypes'];
        }

        // Allow overriding upload settings
        if (isset($config['uploadSettings']) && is_array($config['uploadSettings'])) {
            $this->uploadSettings = array_merge($this->uploadSettings, $config['uploadSettings']);
        }

        Log::info('Configuration applied', [
            'config' => $config,
            'final_settings' => $this->uploadSettings,
        ]);
    }

    /**
     * Format file size from bytes to human readable format
     */
    public function formatFileSize(int $bytes): string
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

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.uppy-file-uploader', [
            'uploadConfig' => $this->getUploadConfig(),
        ]);
    }
}

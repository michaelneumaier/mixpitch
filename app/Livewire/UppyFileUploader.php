<?php

namespace App\Livewire;

use App\Services\FileManagementService;
use App\Models\FileUploadSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class UppyFileUploader extends Component
{
    public Model $model;
    public bool $allowMultiple = true;
    public string $uploadContext = FileUploadSetting::CONTEXT_GLOBAL;
    public array $acceptedFileTypes = ['audio/*', 'application/pdf', 'image/*', 'application/zip'];
    public string $maxFileSize = '200MB';
    public int $maxFiles = 1000;
    
    // Uppy specific properties
    public array $uploadedFiles = [];
    
    protected ?FileManagementService $fileManagementService = null;

    protected function getFileManagementService(): FileManagementService
    {
        if (!$this->fileManagementService) {
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
        
        // Apply configuration overrides
        $this->applyConfiguration($config);
        
        Log::info('UppyFileUploader initialized', [
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'context' => $this->uploadContext,
            'allow_multiple' => $this->allowMultiple
        ]);
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
                'model_id' => $this->model->id
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
                'model_id' => $this->model->id
            ]);
            
            $this->dispatch('fileUploadError', [
                'message' => 'Upload processing failed: ' . $e->getMessage()
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
                'model_id' => $this->model->id
            ]);

            // Extract file information from Uppy response
            $filename = $fileData['name'] ?? 'uploaded_file';
            $s3Key = $fileData['key'] ?? null;
            $size = $fileData['size'] ?? 0;
            $type = $fileData['type'] ?? 'application/octet-stream';

            if (!$s3Key) {
                throw new \Exception('No S3 key provided in upload data');
            }

            // Use the file management service to create a database record
            // Since the file is already in S3, we'll create a record directly
            if ($this->model instanceof \App\Models\Project) {
                $fileRecord = $this->getFileManagementService()->createProjectFileFromS3(
                    $this->model,
                    $s3Key,
                    $filename,
                    $size,
                    $type,
                    auth()->user()
                );
            } elseif ($this->model instanceof \App\Models\Pitch) {
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
                'model_id' => $this->model->id
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to process S3 uploaded file', [
                'error' => $e->getMessage(),
                'file_data' => $fileData,
                'model_type' => get_class($this->model),
                'model_id' => $this->model->id
            ]);
            throw $e;
        }
    }

    /**
     * Get upload configuration for frontend
     */
    public function getUploadConfig(): array
    {
        return [
            'allowMultiple' => $this->allowMultiple,
            'maxFileSize' => $this->convertMaxFileSize($this->maxFileSize),
            'maxFiles' => $this->maxFiles,
            'acceptedFileTypes' => $this->acceptedFileTypes,
            'context' => $this->uploadContext,
            'modelId' => $this->model->id,
            'modelType' => get_class($this->model),
            's3Endpoint' => '/s3/multipart',
            'csrfToken' => csrf_token(),
        ];
    }

    /**
     * Convert max file size string to bytes
     */
    protected function convertMaxFileSize(string $maxFileSize): int
    {
        $size = strtoupper($maxFileSize);
        $unit = substr($size, -2);
        $value = (int) substr($size, 0, -2);

        switch ($unit) {
            case 'KB':
                return $value * 1024;
            case 'MB':
                return $value * 1024 * 1024;
            case 'GB':
                return $value * 1024 * 1024 * 1024;
            default:
                return (int) $maxFileSize; // Assume bytes
        }
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
        
        if (isset($config['maxFileSize'])) {
            $this->maxFileSize = $config['maxFileSize'];
        }
        
        if (isset($config['acceptedFileTypes'])) {
            $this->acceptedFileTypes = $config['acceptedFileTypes'];
        }
        
        Log::info('Configuration applied', ['config' => $config]);
    }

    /**
     * Format file size from bytes to human readable format
     */
    public function formatFileSize(int $bytes): string
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
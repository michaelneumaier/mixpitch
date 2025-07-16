<?php

namespace App\Livewire;

use App\Livewire\FileUploader;
use App\Services\FileManagementService;
use App\Models\FileUploadSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use RahulHaque\Filepond\Facades\Filepond;

class EnhancedFileUploader extends Component
{
    public Model $model;
    public bool $allowMultiple = false;
    public bool $enableChunking = true;
    public string $uploadContext = FileUploadSetting::CONTEXT_GLOBAL;
    public array $acceptedFileTypes = ['audio/*', 'application/pdf', 'image/*', 'application/zip'];
    public string $maxFileSize = '200MB';
    public int $maxFiles = 1;
    
    // FilePond specific properties
    public array $filePondFiles = [];
    public array $tempFileIds = [];
    
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
        
        Log::info('EnhancedFileUploader initialized', [
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'context' => $this->uploadContext,
            'allow_multiple' => $this->allowMultiple
        ]);
    }

    /**
     * Handle FilePond file uploads
     */
    public function handleFilePondUpload($tempFileIds)
    {
        if (empty($tempFileIds)) {
            return;
        }

        $this->tempFileIds = $tempFileIds;
        
        try {
            foreach ($tempFileIds as $tempFileId) {
                $this->processFilePondFile($tempFileId);
            }
            
            $this->dispatch('filesUploaded', [
                'count' => count($tempFileIds),
                'model_type' => get_class($this->model),
                'model_id' => $this->model->id
            ]);
            
            // Clear temp file IDs after processing
            $this->tempFileIds = [];
            
        } catch (\Exception $e) {
            Log::error('FilePond upload processing failed', [
                'error' => $e->getMessage(),
                'temp_file_ids' => $tempFileIds,
                'model_type' => get_class($this->model),
                'model_id' => $this->model->id
            ]);
            
            $this->dispatch('fileUploadError', [
                'message' => 'Upload processing failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Process a single FilePond temporary file with S3 support
     */
    protected function processFilePondFile(string $tempFileId): void
    {
        // Increase execution time for large file processing
        $originalTimeLimit = ini_get('max_execution_time');
        set_time_limit(300); // 5 minutes for large file processing
        
        try {
            // Decrypt the temp file ID to get the filepond model
            $decrypted = \Illuminate\Support\Facades\Crypt::decrypt($tempFileId);
            $filepondId = $decrypted['id'];
            
            // Get the filepond record
            $filepondModel = config('filepond.model');
            $filepond = $filepondModel::find($filepondId);
            
            if (!$filepond || !$filepond->filepath) {
                throw new \Exception("Temporary file not found: {$tempFileId}");
            }
            
            // Get file from S3/R2
            $tempDisk = config('filepond.temp_disk', 'local');
            
            if (!Storage::disk($tempDisk)->exists($filepond->filepath)) {
                throw new \Exception("Temporary file not found in storage: {$filepond->filepath}");
            }
            
            Log::info('Starting FilePond file processing', [
                'temp_file_id' => $tempFileId,
                'filepond_id' => $filepondId,
                'original_name' => $filepond->filename,
                'filepath' => $filepond->filepath,
                'temp_disk' => $tempDisk
            ]);
            
            // Create a temporary local file for processing
            $downloadStart = microtime(true);
            $tempContent = Storage::disk($tempDisk)->get($filepond->filepath);
            $downloadTime = round(microtime(true) - $downloadStart, 2);
            
            $localTempFile = tempnam(sys_get_temp_dir(), 'filepond_process_');
            file_put_contents($localTempFile, $tempContent);
            
            // Create UploadedFile object
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $localTempFile,
                $filepond->filename,
                $filepond->mimetypes ?: 'application/octet-stream',
                null,
                true
            );
            
            Log::info('FilePond file downloaded and prepared', [
                'temp_file_id' => $tempFileId,
                'original_name' => $filepond->filename,
                'file_size' => $uploadedFile->getSize(),
                'file_size_mb' => round($uploadedFile->getSize() / 1024 / 1024, 2),
                'download_time_seconds' => $downloadTime
            ]);
            
            // Use existing file management service to handle the upload
            $uploadStart = microtime(true);
            if ($this->model instanceof \App\Models\Project) {
                $fileRecord = $this->getFileManagementService()->uploadProjectFile(
                    $this->model, 
                    $uploadedFile, 
                    auth()->user()
                );
            } elseif ($this->model instanceof \App\Models\Pitch) {
                $fileRecord = $this->getFileManagementService()->uploadPitchFile(
                    $this->model, 
                    $uploadedFile, 
                    auth()->user()
                );
            } else {
                throw new \Exception('Unsupported model type for file upload');
            }
            $uploadTime = round(microtime(true) - $uploadStart, 2);
            
            Log::info('FilePond file processed successfully', [
                'temp_file_id' => $tempFileId,
                'file_record_id' => $fileRecord->id,
                'original_name' => $filepond->filename,
                'model_type' => get_class($this->model),
                'model_id' => $this->model->id,
                'processing_time_seconds' => $uploadTime
            ]);
            
            // Clean up temporary files
            Storage::disk($tempDisk)->delete($filepond->filepath);
            if (file_exists($localTempFile)) {
                unlink($localTempFile);
            }
            
            // Delete the filepond record
            $filepond->delete();
            
        } finally {
            // Restore original time limit
            set_time_limit($originalTimeLimit);
        }
    }


    /**
     * Get upload configuration for frontend
     */
    public function getUploadConfig(): array
    {
        return [
            'allowMultiple' => $this->allowMultiple,
            'enableChunking' => $this->enableChunking,
            'maxFileSize' => $this->maxFileSize,
            'maxFiles' => $this->maxFiles,
            'acceptedFileTypes' => $this->acceptedFileTypes,
            'context' => $this->uploadContext,
            'modelId' => $this->model->id,
            'modelType' => get_class($this->model),
        ];
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
        
        if (isset($config['enableChunking'])) {
            $this->enableChunking = (bool) $config['enableChunking'];
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
     * Render the component
     */
    public function render()
    {
        return view('livewire.enhanced-file-uploader', [
            'uploadConfig' => $this->getUploadConfig(),
        ]);
    }
}
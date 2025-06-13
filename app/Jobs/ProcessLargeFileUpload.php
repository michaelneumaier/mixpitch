<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Pitch;
use App\Models\User;
use App\Services\FileManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessLargeFileUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes timeout for large files
    public $tries = 3; // Retry up to 3 times

    protected $model;
    protected $tempFilePath;
    protected $originalFilename;
    protected $uploader;
    protected $metadata;

    /**
     * Create a new job instance.
     */
    public function __construct($model, string $tempFilePath, string $originalFilename, ?User $uploader = null, array $metadata = [])
    {
        $this->model = $model;
        $this->tempFilePath = $tempFilePath;
        $this->originalFilename = $originalFilename;
        $this->uploader = $uploader;
        $this->metadata = $metadata;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Increase execution time for this job
            set_time_limit(600);

            // Verify temp file still exists
            if (!Storage::disk('local')->exists($this->tempFilePath)) {
                throw new \RuntimeException("Temporary file not found: {$this->tempFilePath}");
            }

            // Get full path to temp file
            $fullTempPath = Storage::disk('local')->path($this->tempFilePath);

            // Create UploadedFile instance from the temp file
            $uploadedFile = new UploadedFile(
                $fullTempPath,
                $this->originalFilename,
                mime_content_type($fullTempPath),
                null,
                true
            );

            // Process the upload using FileManagementService
            $fileManagementService = app(FileManagementService::class);

            if ($this->model instanceof Project) {
                $fileManagementService->uploadProjectFile($this->model, $uploadedFile, $this->uploader, $this->metadata);
                Log::info('Large project file uploaded successfully via job', [
                    'filename' => $this->originalFilename,
                    'project_id' => $this->model->id
                ]);
            } elseif ($this->model instanceof Pitch) {
                $fileManagementService->uploadPitchFile($this->model, $uploadedFile, $this->uploader);
                Log::info('Large pitch file uploaded successfully via job', [
                    'filename' => $this->originalFilename,
                    'pitch_id' => $this->model->id
                ]);
            }

            // Clean up temporary file
            Storage::disk('local')->delete($this->tempFilePath);
            Log::info('Cleaned up temporary file after successful upload', ['path' => $this->tempFilePath]);

        } catch (\Exception $e) {
            Log::error('Error processing large file upload job', [
                'filename' => $this->originalFilename,
                'model_type' => get_class($this->model),
                'model_id' => $this->model->id,
                'error' => $e->getMessage(),
                'temp_path' => $this->tempFilePath
            ]);

            // Clean up temp file on failure too
            if (Storage::disk('local')->exists($this->tempFilePath)) {
                Storage::disk('local')->delete($this->tempFilePath);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Large file upload job failed permanently', [
            'filename' => $this->originalFilename,
            'model_type' => get_class($this->model),
            'model_id' => $this->model->id,
            'error' => $exception->getMessage(),
            'temp_path' => $this->tempFilePath
        ]);

        // Clean up temp file on permanent failure
        if (Storage::disk('local')->exists($this->tempFilePath)) {
            Storage::disk('local')->delete($this->tempFilePath);
            Log::info('Cleaned up temporary file after job failure', ['path' => $this->tempFilePath]);
        }
    }
} 
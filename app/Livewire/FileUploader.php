<?php

namespace App\Livewire;

use App\Exceptions\FileUploadException;
use App\Exceptions\StorageLimitException;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\FileManagementService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Masmerise\Toaster\Toaster;

class FileUploader extends Component
{
    use WithFileUploads;

    public Model $model; // Project or Pitch model instance

    public $file = null; // Single file upload approach

    // Track progress
    public $uploadProgress = [];

    // Don't initialize in constructor to avoid potential infinite loop
    protected $fileManagementService;

    // Custom event listener for upload progress
    protected $listeners = [
        'upload:progress' => 'handleUploadProgress',
    ];

    // Use mount instead of boot to initialize properly
    public function mount(Model $model)
    {
        $this->model = $model;

        // Validate the model type to prevent misuse
        if (! ($model instanceof Project) && ! ($model instanceof Pitch)) {
            throw new \InvalidArgumentException('The model must be a Project or Pitch instance');
        }

        // Initialize the service through dependency injection in mount
        $this->fileManagementService = app(FileManagementService::class);
    }

    public function rules()
    {
        // Common rules
        $maxFileSizeKB = config('filesystems.limits.max_file_size_kb', 200 * 1024); // Default 200MB
        $allowedMimes = 'mp3,wav,aac,ogg,pdf,jpg,jpeg,png,gif,zip'; // Combine allowed types

        return [
            'file' => [
                'required',
                'file',
                'mimes:'.$allowedMimes,
                'max:'.$maxFileSizeKB,
            ],
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'A file is required.',
            'file.file' => 'The uploaded item must be a file.',
            'file.mimes' => 'Invalid file type. Allowed types: :values.',
            'file.max' => 'The file must not be greater than :max kilobytes.',
        ];
    }

    // Updated hook to reset progress on file changes
    public function updatedFile()
    {
        // Reset progress when file changes
        $this->uploadProgress = [];

        if ($this->file) {
            // Only log success but don't validate yet
            Log::info('FileUploader: File selected', [
                'original_filename' => $this->file->getClientOriginalName(),
                'size' => $this->file->getSize(),
                'mime' => $this->file->getMimeType(),
            ]);
        }
    }

    public function saveFile()
    {
        try {
            // Increase execution time for large file uploads
            set_time_limit(300); // 5 minutes for large file uploads

            // Validate the file
            $this->validate();

            // This line will only execute if validation passes
            if (! $this->file) {
                Toaster::error('No file selected for upload.');

                return ['success' => false, 'error' => 'No file selected for upload.'];
            }

            Log::info('FileUploader: Starting saveFile process', [
                'model_type' => get_class($this->model),
                'model_id' => $this->model->id,
            ]);

            // Ensure we have the FileManagementService
            if (! $this->fileManagementService) {
                $this->fileManagementService = app(FileManagementService::class);
            }

            // Get the authenticated user
            $user = Auth::user();

            if (! $user) {
                throw new FileUploadException('User not authenticated. Cannot upload files.');
            }

            // Get initial file information
            $tempFilename = $this->file->getFilename();
            $originalFilename = $this->file->getClientOriginalName();
            $mimeType = $this->file->getMimeType();
            $size = $this->file->getSize();

            Log::info('FileUploader: Processing Livewire temporary file', [
                'filename' => $originalFilename,
                'mime' => $mimeType,
                'size' => $size,
            ]);

            // For very large files (over 50MB), use async processing
            $largeSizeThreshold = 50 * 1024 * 1024; // 50MB
            $useAsyncProcessing = $size > $largeSizeThreshold;

            if ($useAsyncProcessing) {
                Log::info('FileUploader: Large file detected, using async processing', [
                    'filename' => $originalFilename,
                    'size' => $size,
                    'threshold' => $largeSizeThreshold,
                ]);
            }

            try {
                // Test S3 connection first (but only in production)
                if ((config('filesystems.default') === 's3' || config('filesystems.cloud') === 's3') &&
                    ! app()->environment('local')) {
                    if (! $this->testS3Connection()) {
                        throw new FileUploadException('Cannot connect to storage service. Please try again later or contact support.');
                    }
                }

                // --- Store the file locally first using Livewire's method ---
                $persistentTempDir = 'livewire-tmp-processing';
                $persistentTempFilename = uniqid('processed-', true).'.'.$this->file->getClientOriginalExtension();

                Log::info('FileUploader: Storing temporary file locally before processing.', [
                    'directory' => $persistentTempDir,
                    'filename' => $persistentTempFilename,
                    'original' => $originalFilename,
                ]);

                // Store the file in storage/app/livewire-tmp-processing
                $storedPathRelative = $this->file->storeAs($persistentTempDir, $persistentTempFilename, 'local');

                if (! $storedPathRelative) {
                    Log::error('FileUploader: Failed to store temporary file locally using Livewire storeAs.');
                    throw new FileUploadException('Could not save temporary file for processing.');
                }

                // Get the absolute path to the locally stored temporary file
                $tmpPath = Storage::disk('local')->path($storedPathRelative);

                if (! file_exists($tmpPath)) {
                    Log::error('FileUploader: Locally stored temporary file not found after storeAs.', ['path' => $tmpPath]);
                    throw new FileUploadException('Could not locate saved temporary file.');
                }
                // --- End of local storage ---

                Log::info("FileUploader: Confirmed locally stored temp file exists at {$tmpPath}");

                if ($useAsyncProcessing) {
                    // For large files, dispatch to queue for background processing
                    \App\Jobs\ProcessLargeFileUpload::dispatch(
                        $this->model,
                        $storedPathRelative,
                        $originalFilename,
                        $user,
                        $this->model instanceof Project ? ['uploaded_by_client' => false] : []
                    );

                    Log::info('FileUploader: Large file queued for async processing', [
                        'filename' => $originalFilename,
                        'temp_path' => $storedPathRelative,
                    ]);

                    // Success feedback for async processing
                    Toaster::success("Large file {$originalFilename} is being processed in the background. You'll be notified when it's ready.");

                    // Return success for async processing
                    return ['success' => true, 'message' => "Large file {$originalFilename} is being processed in the background."];

                } else {
                    // For smaller files, process synchronously as before
                    // Now create an UploadedFile instance from the locally stored temporary file
                    $uploadedFile = new UploadedFile(
                        $tmpPath,
                        $originalFilename, // Use the original name for the UploadedFile object
                        $mimeType,
                        null,
                        true // Mark as test so UploadedFile doesn't try to move it
                    );

                    Log::info('FileUploader: Created standard UploadedFile from locally stored temp file', [
                        'path' => $tmpPath,
                        'originalName' => $originalFilename,
                        'mime' => $uploadedFile->getMimeType(),
                        'size' => $uploadedFile->getSize(),
                    ]);

                    // Customize the stored filename if needed (currently not used, FileManagementService uses original)
                    $customFilename = $originalFilename;

                    // --- Pass to FileManagementService ---
                    if ($this->model instanceof Project) {
                        $this->fileManagementService->uploadProjectFile($this->model, $uploadedFile, $user);
                        Log::info("FileUploader: Successfully uploaded project file {$originalFilename}");
                    } elseif ($this->model instanceof Pitch) {
                        $this->fileManagementService->uploadPitchFile($this->model, $uploadedFile, $user);
                        Log::info("FileUploader: Successfully uploaded pitch file {$originalFilename}");
                    }

                    // --- Cleanup ---
                    Log::info('FileUploader: Deleting locally stored temporary file.', ['path' => $tmpPath]);
                    Storage::disk('local')->delete($storedPathRelative); // Delete using relative path

                    // Success feedback for sync processing
                    Toaster::success("Successfully uploaded {$originalFilename}");

                    // Return success for sync processing
                    return ['success' => true, 'message' => "Successfully uploaded {$originalFilename}"];
                }

                // Clear the file input and progress
                $this->reset('file');
                $this->uploadProgress = [];

            } catch (StorageLimitException $e) {
                Log::error("FileUploader: Storage limit exceeded for file {$originalFilename}", ['error' => $e->getMessage()]);
                Toaster::error($e->getMessage());
                // Keep track of error in progress
                $this->uploadProgress[$tempFilename] = 'Error: '.$e->getMessage();

                return ['success' => false, 'error' => $e->getMessage()];
            } catch (FileUploadException $e) {
                Log::error("FileUploader: File upload exception for file {$originalFilename}", ['error' => $e->getMessage()]);
                Toaster::error("Error uploading {$originalFilename}: ".$e->getMessage());
                $this->uploadProgress[$tempFilename] = 'Error: '.$e->getMessage();

                return ['success' => false, 'error' => $e->getMessage()];
            } catch (\Exception $e) {
                Log::error("FileUploader: General error uploading file {$originalFilename}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'error_class' => get_class($e),
                ]);

                // Provide more specific error messages based on common issues
                $errorMessage = $this->getErrorFriendlyMessage($e);

                Toaster::error($errorMessage);
                $this->uploadProgress[$tempFilename] = 'Error: '.$errorMessage;

                return ['success' => false, 'error' => $errorMessage];
            }

            Log::info('FileUploader: Finished saveFile process');

            // Emit event to refresh parent component's file list
            $this->dispatch('filesUploaded');
            $this->dispatch('refreshContestData');
            $this->js('window.dispatchEvent(new CustomEvent("filesUploaded"));');
        } catch (\Exception $e) {
            Log::error('Error in FileUploader::saveFile', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'model_type' => get_class($this->model),
                'model_id' => $this->model->id,
            ]);
            Toaster::error('An error occurred while uploading the file.');

            return ['success' => false, 'error' => 'An error occurred while uploading the file.'];
        }
    }

    /**
     * Handle the Livewire upload:progress event.
     *
     * @param  string  $name  The temporary name of the file being uploaded.
     * @param  int  $progress  The progress percentage (0-100).
     */
    public function handleUploadProgress($name, $progress)
    {
        try {
            // Only log at debug level to avoid filling the logs
            Log::debug("Upload progress for {$name}: {$progress}%");
            $this->uploadProgress[$name] = $progress;
        } catch (\Exception $e) {
            // Silently log errors to avoid disrupting the upload
            Log::error('Error handling upload progress', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.file-uploader');
    }

    /**
     * Clean up resources when the component is dehydrated.
     */
    public function dehydrate()
    {
        // Explicitly clean up resources to prevent memory issues
        if ($this->file && method_exists($this->file, 'cleanupTemporaryFile')) {
            try {
                $this->file->cleanupTemporaryFile();
            } catch (\Exception $e) {
                Log::error('Error cleaning up temporary file', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Format bytes to human readable format
     *
     * @param  int  $bytes
     * @param  int  $precision
     * @return string
     */
    public function formatFileSize($bytes, $precision = 2)
    {
        if ($bytes === null || $bytes <= 0) {
            return '0 bytes';
        }

        $units = ['bytes', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Get a more friendly error message based on the exception type
     *
     * @return string
     */
    protected function getErrorFriendlyMessage(\Exception $e)
    {
        $errorMessage = 'An unexpected error occurred uploading the file. Please try again later or contact support.';
        $errorMsg = $e->getMessage();

        // Add more specific messages based on common issues
        if (strpos($errorMsg, 'file size') !== false) {
            $errorMessage = 'The file size exceeds the allowed limit. Please upload a file smaller than '.$this->formatFileSize(config('filesystems.limits.max_file_size_kb', 200 * 1024)).'.';
        } elseif (strpos($errorMsg, 'file type') !== false) {
            $errorMessage = 'Invalid file type. Allowed types: mp3, wav, aac, ogg, pdf, jpg, jpeg, png, gif, zip.';
        } elseif (strpos($errorMsg, 'storage limit') !== false) {
            $errorMessage = 'Storage limit exceeded. Please contact support for assistance.';
        } elseif (strpos($errorMsg, 'file upload') !== false) {
            $errorMessage = 'Error uploading the file. Please try again later or contact support.';
        }
        // S3 specific errors
        elseif (strpos($errorMsg, 'AWS') !== false || strpos($errorMsg, 'S3') !== false) {
            $errorMessage = 'Error connecting to storage service. Please try again later or contact support.';
        } elseif (strpos($errorMsg, 'credentials') !== false || strpos($errorMsg, 'authorization') !== false) {
            $errorMessage = 'Storage authentication error. Please contact support.';
        } elseif (strpos($errorMsg, 'connect') !== false || strpos($errorMsg, 'network') !== false) {
            $errorMessage = 'Network error connecting to storage. Please check your connection and try again.';
        } elseif (strpos($errorMsg, 'filesystem') !== false || strpos($errorMsg, 'disk') !== false) {
            $errorMessage = 'Storage filesystem error. Please contact support.';
        }

        return $errorMessage;
    }

    /**
     * Check if basic S3 connection is working
     *
     * @return bool
     */
    protected function testS3Connection()
    {
        try {
            // Try to get storage disk info
            $disk = \Illuminate\Support\Facades\Storage::disk('s3');

            // Check if the credentials are valid
            $isConfigured = ! empty(config('filesystems.disks.s3.key')) &&
                          ! empty(config('filesystems.disks.s3.secret')) &&
                          ! empty(config('filesystems.disks.s3.region')) &&
                          ! empty(config('filesystems.disks.s3.bucket'));

            if (! $isConfigured) {
                Log::error('S3 is not properly configured. Missing credentials.');

                return false;
            }

            // Try a simple operation
            $testPath = 'test-connection-'.uniqid();
            $disk->put($testPath, 'test connection');
            $exists = $disk->exists($testPath);
            $disk->delete($testPath); // Clean up

            return $exists;
        } catch (\Exception $e) {
            // Log the error with detailed information
            Log::error('S3 connection test failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return false;
        }
    }
}

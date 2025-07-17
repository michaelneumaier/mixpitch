<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupTemporaryAudioFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $temporaryFilePaths;

    protected $retainFinalFile;

    /**
     * Create a new job instance.
     *
     * @param  array  $temporaryFilePaths  Array of S3 file paths to clean up
     * @param  string  $retainFinalFile  Path to the final file that should NOT be deleted
     */
    public function __construct(array $temporaryFilePaths, ?string $retainFinalFile = null)
    {
        $this->temporaryFilePaths = $temporaryFilePaths;
        $this->retainFinalFile = $retainFinalFile;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! config('audio.storage.cleanup_temp_files', true)) {
            Log::info('Temporary file cleanup is disabled in configuration');

            return;
        }

        Log::info('Starting cleanup of temporary audio files', [
            'temp_files' => $this->temporaryFilePaths,
            'retain_file' => $this->retainFinalFile,
        ]);

        $deletedCount = 0;
        $failedCount = 0;

        foreach ($this->temporaryFilePaths as $filePath) {
            // Skip if this is the final file we want to retain
            if ($this->retainFinalFile && $filePath === $this->retainFinalFile) {
                Log::info('Skipping cleanup of final file', ['file' => $filePath]);

                continue;
            }

            try {
                if (Storage::disk('s3')->exists($filePath)) {
                    Storage::disk('s3')->delete($filePath);
                    Log::info('Deleted temporary file', ['file' => $filePath]);
                    $deletedCount++;
                } else {
                    Log::debug('Temporary file already deleted or not found', ['file' => $filePath]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to delete temporary file', [
                    'file' => $filePath,
                    'error' => $e->getMessage(),
                ]);
                $failedCount++;
            }
        }

        Log::info('Temporary file cleanup completed', [
            'deleted' => $deletedCount,
            'failed' => $failedCount,
            'total_processed' => count($this->temporaryFilePaths),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Temporary file cleanup job failed', [
            'temp_files' => $this->temporaryFilePaths,
            'retain_file' => $this->retainFinalFile,
            'error' => $exception->getMessage(),
        ]);
    }
}

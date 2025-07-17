<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupTemporaryAudioFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audio:cleanup-temp-files 
                           {--dry-run : Show what would be deleted without actually deleting}
                           {--days=7 : Delete files older than this many days}
                           {--path=processed-audio/ : S3 path prefix to search for temporary files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up temporary audio files from S3 storage';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');
        $pathPrefix = $this->option('path');

        $this->info('Cleaning up temporary audio files from S3...');
        $this->info("Path prefix: {$pathPrefix}");
        $this->info("Older than: {$days} days");
        $this->info('Dry run: '.($dryRun ? 'Yes' : 'No'));

        if ($dryRun) {
            $this->warn('This is a dry run - no files will be deleted');
        }

        $this->newLine();

        try {
            // Get all files in the temporary directory
            $files = Storage::disk('s3')->files($pathPrefix);

            if (empty($files)) {
                $this->info("No files found in {$pathPrefix}");

                return 0;
            }

            $this->info('Found '.count($files).' files to examine');

            $cutoffDate = Carbon::now()->subDays($days);
            $deletedCount = 0;
            $keptCount = 0;
            $failedCount = 0;

            $this->withProgressBar($files, function ($file) use ($cutoffDate, $dryRun, &$deletedCount, &$keptCount, &$failedCount) {
                try {
                    $lastModified = Storage::disk('s3')->lastModified($file);
                    $fileDate = Carbon::createFromTimestamp($lastModified);

                    if ($fileDate->isBefore($cutoffDate)) {
                        if ($dryRun) {
                            $this->line("Would delete: {$file} (modified: {$fileDate->format('Y-m-d H:i:s')})");
                            $deletedCount++;
                        } else {
                            Storage::disk('s3')->delete($file);
                            $deletedCount++;
                        }
                    } else {
                        $keptCount++;
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to process {$file}: ".$e->getMessage());
                    $failedCount++;
                }
            });

            $this->newLine(2);
            $this->info('Cleanup summary:');
            $this->info('Files '.($dryRun ? 'would be deleted' : 'deleted').": {$deletedCount}");
            $this->info("Files kept (too recent): {$keptCount}");
            if ($failedCount > 0) {
                $this->error("Files failed to process: {$failedCount}");
            }

            if (! $dryRun) {
                Log::info('Manual temporary file cleanup completed', [
                    'deleted' => $deletedCount,
                    'kept' => $keptCount,
                    'failed' => $failedCount,
                    'days_threshold' => $days,
                    'path_prefix' => $pathPrefix,
                ]);
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Error during cleanup: '.$e->getMessage());
            Log::error('Manual temporary file cleanup failed', [
                'error' => $e->getMessage(),
                'path_prefix' => $pathPrefix,
            ]);

            return 1;
        }
    }
}

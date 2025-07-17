<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupCachedZips extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zips:cleanup 
                            {--days=30 : Number of days to keep ZIP archives before deletion}
                            {--dry-run : Run in dry-run mode without actually deleting files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old cached ZIP files from S3 storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Cleaning up cached ZIP files older than {$days} days...");
        if ($dryRun) {
            $this->warn('DRY RUN: No files will actually be deleted');
        }

        // Get all files in the project_archives directory
        $files = Storage::disk('s3')->files('project_archives');

        $cutoffDate = now()->subDays($days);
        $deleteCount = 0;
        $totalSize = 0;

        $this->output->progressStart(count($files));

        foreach ($files as $file) {
            $this->output->progressAdvance();

            // Get the last modified time for the file
            try {
                $lastModified = Storage::disk('s3')->lastModified($file);
                $lastModifiedDate = \Carbon\Carbon::createFromTimestamp($lastModified);
                $size = Storage::disk('s3')->size($file);

                // If older than the cutoff date, delete it
                if ($lastModifiedDate->lt($cutoffDate)) {
                    $this->info("  Found old ZIP: {$file} (Last modified: {$lastModifiedDate->format('Y-m-d H:i:s')})");
                    $totalSize += $size;

                    if (! $dryRun) {
                        if (Storage::disk('s3')->delete($file)) {
                            $deleteCount++;
                            Log::info('Deleted old cached ZIP file', [
                                'file' => $file,
                                'last_modified' => $lastModifiedDate,
                                'size' => $size,
                            ]);
                        } else {
                            $this->error("  Failed to delete {$file}");
                            Log::error('Failed to delete old cached ZIP file', [
                                'file' => $file,
                            ]);
                        }
                    } else {
                        $deleteCount++;
                    }
                }
            } catch (\Exception $e) {
                $this->error("Error processing file {$file}: {$e->getMessage()}");
                Log::error('Error during cached ZIP cleanup', [
                    'file' => $file,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->output->progressFinish();

        // Format bytes to human-readable format
        $formattedSize = $this->formatBytes($totalSize);

        if ($dryRun) {
            $this->info("DRY RUN: Would have deleted {$deleteCount} ZIP files (approx. {$formattedSize})");
        } else {
            $this->info("Successfully deleted {$deleteCount} ZIP files (approx. {$formattedSize})");
        }
    }

    /**
     * Format bytes to a human-readable format
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}

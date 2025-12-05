<?php

namespace App\Jobs;

use App\Models\BulkDownload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupOldBulkDownloads implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job - Delete bulk download archives older than 24 hours
     */
    public function handle(): void
    {
        $cutoffTime = now()->subHours(24);

        // Find old completed or failed downloads
        $oldDownloads = BulkDownload::where('created_at', '<', $cutoffTime)
            ->whereIn('status', ['completed', 'failed'])
            ->get();

        if ($oldDownloads->isEmpty()) {
            Log::info('No old bulk downloads to clean up');

            return;
        }

        $deletedCount = 0;
        $failedCount = 0;

        foreach ($oldDownloads as $download) {
            try {
                // Delete file from R2 storage if it exists
                if ($download->storage_path && Storage::disk('s3')->exists($download->storage_path)) {
                    Storage::disk('s3')->delete($download->storage_path);
                    Log::info('Deleted bulk download archive from R2', [
                        'archive_id' => $download->id,
                        'storage_path' => $download->storage_path,
                    ]);
                }

                // Delete database record
                $download->delete();
                $deletedCount++;

            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Failed to cleanup bulk download', [
                    'archive_id' => $download->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Bulk download cleanup completed', [
            'total_found' => $oldDownloads->count(),
            'deleted' => $deletedCount,
            'failed' => $failedCount,
        ]);
    }
}

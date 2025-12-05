<?php

namespace App\Services;

use App\Models\BulkDownload;
use App\Models\PitchFile;
use App\Models\ProjectFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BulkDownloadService
{
    public function requestBulkDownload(array $fileIds, string $context = 'project'): string
    {
        // Validate input
        if (empty($fileIds)) {
            throw new \InvalidArgumentException('File IDs array cannot be empty');
        }

        // Validate configuration
        $this->validateConfiguration();

        // Validate files and get models
        $files = $this->validateAndGetFiles($fileIds, $context);

        if ($files->isEmpty()) {
            throw new \Exception('No valid files found for download');
        }

        // Validate that all files have storage paths
        $missingPaths = $files->filter(function ($file) {
            return empty($file->storage_path) && empty($file->file_path);
        });

        if ($missingPaths->isNotEmpty()) {
            Log::error('Files missing storage paths', [
                'file_ids' => $missingPaths->pluck('id')->toArray(),
            ]);

            throw new \Exception('Some files are missing storage paths and cannot be downloaded');
        }

        // Validate total size doesn't exceed 4GB limit
        $totalSize = $files->sum('size');
        $maxSize = 4 * 1024 * 1024 * 1024; // 4GB in bytes

        if ($totalSize > $maxSize) {
            $totalSizeMB = number_format($totalSize / 1048576, 2);

            Log::warning('Bulk download exceeds 4GB limit', [
                'total_size_bytes' => $totalSize,
                'total_size_mb' => $totalSizeMB,
                'file_count' => $files->count(),
                'user_id' => auth()->id(),
            ]);

            throw new \InvalidArgumentException(
                "Total file size exceeds 4GB limit for ZIP downloads. Total: {$totalSizeMB} MB. ".
                'Please select fewer files or download them individually.'
            );
        }

        // Generate unique archive ID
        $archiveId = Str::uuid()->toString();

        // Create pending download record
        $bulkDownload = BulkDownload::create([
            'id' => $archiveId,
            'user_id' => auth()->id(),
            'file_ids' => $fileIds,
            'archive_name' => $this->generateArchiveName($context, $files->count()),
            'status' => 'pending',
        ]);

        // Prepare message for CF Queue
        $message = [
            'archive_id' => $archiveId,
            'files' => $files->map(fn ($file) => [
                'storage_path' => $file->storage_path ?? $file->file_path,
                'filename' => $file->original_file_name,
                'size' => $file->size,
            ])->toArray(),
            'archive_name' => $bulkDownload->archive_name,
            'callback_url' => config('services.cloudflare.bulk_download_callback_url')
                ?? route('bulk-download.callback'),
            'callback_secret' => config('services.cloudflare.callback_secret'),
        ];

        // Send to Cloudflare Queue
        $this->sendToCloudflareQueue($message);

        // Update status to processing
        $bulkDownload->update(['status' => 'processing']);

        Log::info('Bulk download requested', [
            'archive_id' => $archiveId,
            'file_count' => $files->count(),
            'context' => $context,
        ]);

        return $archiveId;
    }

    protected function validateAndGetFiles(array $fileIds, string $context)
    {
        $modelClass = $context === 'pitch' ? PitchFile::class : ProjectFile::class;

        $files = $modelClass::whereIn('id', $fileIds)->get();

        // Verify all requested files were found
        if ($files->count() !== count($fileIds)) {
            $foundIds = $files->pluck('id')->toArray();
            $missingIds = array_diff($fileIds, $foundIds);

            Log::warning('Some files not found for bulk download', [
                'missing_ids' => $missingIds,
                'context' => $context,
            ]);
        }

        // Authorization checks - verify user can download each file
        // Note: PitchFile uses 'downloadFile', ProjectFile uses 'download'
        $ability = $context === 'pitch' ? 'downloadFile' : 'download';

        foreach ($files as $file) {
            if (! auth()->user()->can($ability, $file)) {
                Log::warning('Unauthorized bulk download attempt', [
                    'user_id' => auth()->id(),
                    'file_id' => $file->id,
                    'context' => $context,
                    'ability' => $ability,
                ]);

                throw new \Illuminate\Auth\Access\AuthorizationException(
                    'You are not authorized to download one or more of these files'
                );
            }
        }

        return $files;
    }

    protected function validateConfiguration(): void
    {
        $requiredConfig = [
            'services.cloudflare.queue_url' => 'Cloudflare Queue URL',
            'services.cloudflare.api_token' => 'Cloudflare API Token',
            'services.cloudflare.callback_secret' => 'Cloudflare Callback Secret',
        ];

        $missing = [];

        foreach ($requiredConfig as $key => $name) {
            if (! config($key)) {
                $missing[] = $name;
            }
        }

        if (! empty($missing)) {
            Log::error('Missing bulk download configuration', [
                'missing' => $missing,
            ]);

            throw new \Exception(
                'Bulk download is not properly configured. Missing: '.implode(', ', $missing)
            );
        }
    }

    protected function generateArchiveName(string $context, int $fileCount): string
    {
        $prefix = $context === 'pitch' ? 'pitch-files' : 'project-files';
        $timestamp = now()->format('Y-m-d');

        return "{$prefix}-{$timestamp}-{$fileCount}-files.zip";
    }

    protected function sendToCloudflareQueue(array $message): void
    {
        $queueUrl = config('services.cloudflare.queue_url');

        if (! $queueUrl) {
            throw new \Exception('Cloudflare Queue URL not configured');
        }

        // Cloudflare Queues HTTP API expects: { "body": { ...message data... } }
        $payload = [
            'body' => $message,
        ];

        $response = Http::withToken(config('services.cloudflare.api_token'))
            ->post($queueUrl, $payload);

        if (! $response->successful()) {
            Log::error('Failed to send message to Cloudflare Queue', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Failed to enqueue bulk download request');
        }

        Log::info('Message sent to Cloudflare Queue', [
            'archive_id' => $message['archive_id'],
        ]);
    }
}

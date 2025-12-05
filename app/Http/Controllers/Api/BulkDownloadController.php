<?php

namespace App\Http\Controllers\Api;

use App\Events\BulkDownloadCompleted;
use App\Http\Controllers\Controller;
use App\Models\BulkDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BulkDownloadController extends Controller
{
    /**
     * Callback from Cloudflare Worker when archive is ready
     */
    public function callback(Request $request)
    {
        // Verify signature from Worker
        $this->verifySignature($request);

        $validated = $request->validate([
            'archive_id' => 'required|uuid',
            'status' => 'required|in:completed,failed',
            'storage_path' => 'required_if:status,completed|string',
            'size' => 'nullable|integer',
            'error' => 'nullable|string',
        ]);

        $bulkDownload = BulkDownload::findOrFail($validated['archive_id']);

        if ($validated['status'] === 'completed') {
            $bulkDownload->update([
                'status' => 'completed',
                'storage_path' => $validated['storage_path'],
                'completed_at' => now(),
            ]);

            Log::info('Bulk download completed', [
                'archive_id' => $validated['archive_id'],
                'storage_path' => $validated['storage_path'],
            ]);

            // Trigger event for real-time notifications
            event(new BulkDownloadCompleted($bulkDownload));
        } else {
            $bulkDownload->update([
                'status' => 'failed',
                'error_message' => $validated['error'] ?? 'Unknown error occurred',
            ]);

            Log::error('Bulk download failed', [
                'archive_id' => $validated['archive_id'],
                'error' => $validated['error'] ?? 'Unknown error',
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Get download status (for polling)
     */
    public function status(string $id)
    {
        $bulkDownload = BulkDownload::where('user_id', auth()->id())
            ->findOrFail($id);

        // Generate fresh download URL if completed and not already generated or expired
        $downloadUrl = null;
        if ($bulkDownload->status === 'completed') {
            if (! $bulkDownload->download_url || $bulkDownload->download_url_expires_at?->isPast()) {
                $downloadUrl = $bulkDownload->generateDownloadUrl(60);
            } else {
                $downloadUrl = $bulkDownload->download_url;
            }
        }

        return response()->json([
            'status' => $bulkDownload->status,
            'completed_at' => $bulkDownload->completed_at,
            'download_url' => $downloadUrl,
            'error_message' => $bulkDownload->error_message,
        ]);
    }

    /**
     * Generate download URL and redirect
     */
    public function download(string $id)
    {
        $bulkDownload = BulkDownload::where('user_id', auth()->id())
            ->findOrFail($id);

        // Check status
        if ($bulkDownload->status === 'failed') {
            abort(410, 'Download failed: '.$bulkDownload->error_message);
        }

        if ($bulkDownload->status === 'processing' || $bulkDownload->status === 'pending') {
            abort(425, 'Download is still being prepared. Please wait.');
        }

        if ($bulkDownload->status !== 'completed') {
            abort(400, 'Download not available');
        }

        // Check if archive is too old (older than 24 hours)
        if ($bulkDownload->created_at->lt(now()->subHours(24))) {
            Log::warning('Attempted to download expired archive', [
                'archive_id' => $id,
                'user_id' => auth()->id(),
                'created_at' => $bulkDownload->created_at,
            ]);

            abort(410, 'Download expired. Archives are only available for 24 hours.');
        }

        try {
            // Generate fresh presigned URL (60 minutes)
            $url = $bulkDownload->generateDownloadUrl(60);

            Log::info('Bulk download URL generated', [
                'archive_id' => $id,
                'user_id' => auth()->id(),
            ]);

            return redirect($url);

        } catch (\Exception $e) {
            Log::error('Failed to generate download URL', [
                'archive_id' => $id,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Failed to generate download URL. Please try again.');
        }
    }

    /**
     * Verify HMAC signature from Cloudflare Worker
     */
    protected function verifySignature(Request $request): void
    {
        $signature = $request->header('X-Signature');
        $archiveId = $request->input('archive_id');
        $secret = config('services.cloudflare.callback_secret');

        if (! $signature || ! $archiveId) {
            abort(401, 'Missing signature or archive ID');
        }

        $expectedSignature = base64_encode(
            hash_hmac('sha256', $archiveId, $secret, true)
        );

        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('Invalid callback signature', [
                'archive_id' => $archiveId,
                'ip' => $request->ip(),
            ]);

            abort(401, 'Invalid signature');
        }
    }
}

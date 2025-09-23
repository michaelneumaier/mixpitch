<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateVideoThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The file instance (can be PitchFile or ProjectFile).
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $file;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $file  The file instance (PitchFile or ProjectFile)
     * @return void
     */
    public function __construct(Model $file)
    {
        $this->file = $file;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting video thumbnail generation', [
                'file_id' => $this->file->id,
                'file_type' => get_class($this->file),
                'file_path' => $this->file->file_path,
                'mime_type' => $this->file->mime_type,
            ]);

            // Check if the file is actually a video file
            if (! $this->file->isVideoFile()) {
                Log::warning('Attempted to generate thumbnail for non-video file', [
                    'file_id' => $this->file->id,
                    'mime_type' => $this->file->mime_type,
                ]);

                return;
            }

            // Check if file exists in storage
            if (! Storage::disk('s3')->exists($this->file->file_path)) {
                Log::error('Video file not found in storage', [
                    'file_id' => $this->file->id,
                    'file_path' => $this->file->file_path,
                ]);

                return;
            }

            // Generate thumbnail using external service or FFmpeg
            $thumbnailPath = $this->generateThumbnail();

            if ($thumbnailPath) {
                // Update file with thumbnail information
                $this->updateFileWithThumbnail($thumbnailPath);

                Log::info('Video thumbnail generated successfully', [
                    'file_id' => $this->file->id,
                    'thumbnail_path' => $thumbnailPath,
                ]);
            } else {
                Log::error('Failed to generate video thumbnail', [
                    'file_id' => $this->file->id,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Video thumbnail generation failed', [
                'file_id' => $this->file->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark the job as failed after all retries
            if ($this->attempts() >= $this->tries) {
                $this->markThumbnailGenerationFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Generate thumbnail for the video file
     *
     * @return string|null The S3 path of the generated thumbnail
     */
    protected function generateThumbnail(): ?string
    {
        try {
            // Get signed URL for the video file
            $videoUrl = Storage::disk('s3')->temporaryUrl(
                $this->file->file_path,
                now()->addHours(1)
            );

            // For this implementation, we'll use a placeholder approach
            // In production, you would integrate with:
            // 1. AWS Lambda with FFmpeg
            // 2. External video processing service
            // 3. Server-side FFmpeg if available

            // Generate thumbnail path
            $pathInfo = pathinfo($this->file->file_path);
            $thumbnailPath = $pathInfo['dirname'].'/thumbnails/'.$pathInfo['filename'].'_thumb.jpg';

            // Placeholder: Create a simple thumbnail using external service
            // This would be replaced with actual video processing
            $thumbnailGenerated = $this->processVideoThumbnail($videoUrl, $thumbnailPath);

            return $thumbnailGenerated ? $thumbnailPath : null;

        } catch (\Exception $e) {
            Log::error('Error generating video thumbnail', [
                'file_id' => $this->file->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Process video thumbnail using external service or FFmpeg
     */
    protected function processVideoThumbnail(string $videoUrl, string $thumbnailPath): bool
    {
        try {
            // This is a placeholder implementation
            // In production, you would:

            // Option 1: Use AWS Lambda with FFmpeg
            /*
            $response = Http::post(config('services.video_processor.endpoint'), [
                'video_url' => $videoUrl,
                'thumbnail_path' => $thumbnailPath,
                'timestamp' => 5, // Extract thumbnail at 5 seconds
            ]);
            */

            // Option 2: Use a service like Cloudinary, Mux, or similar
            /*
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.video_processor.api_key'),
            ])->post('https://api.cloudinary.com/v1_1/your-cloud/video/upload', [
                'file' => $videoUrl,
                'transformation' => 'c_thumb,w_300,h_200,so_5',
                'resource_type' => 'video',
            ]);
            */

            // Option 3: Server-side FFmpeg (if available)
            /*
            $command = sprintf(
                'ffmpeg -i "%s" -ss 00:00:05 -vframes 1 -vf scale=300:200 "%s"',
                $videoUrl,
                $thumbnailPath
            );
            $result = exec($command, $output, $returnCode);
            return $returnCode === 0;
            */

            // For now, create a placeholder thumbnail
            // This should be replaced with actual video processing
            $placeholderThumbnail = $this->createPlaceholderThumbnail();

            if ($placeholderThumbnail) {
                Storage::disk('s3')->put($thumbnailPath, $placeholderThumbnail);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Video thumbnail processing failed', [
                'file_id' => $this->file->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create a placeholder thumbnail image
     *
     * @return string|null Base64 encoded image data
     */
    protected function createPlaceholderThumbnail(): ?string
    {
        // Create a simple placeholder image (1x1 pixel PNG)
        // In production, this would be replaced with actual video frame extraction
        $placeholder = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==');

        return $placeholder;
    }

    /**
     * Update the file model with thumbnail information
     */
    protected function updateFileWithThumbnail(string $thumbnailPath): void
    {
        $metadata = $this->file->metadata ?? [];
        $metadata['thumbnail_path'] = $thumbnailPath;
        $metadata['thumbnail_generated_at'] = now()->toISOString();
        $metadata['thumbnail_processed'] = true;

        $this->file->update([
            'metadata' => $metadata,
        ]);
    }

    /**
     * Mark thumbnail generation as failed
     */
    protected function markThumbnailGenerationFailed(string $error): void
    {
        $metadata = $this->file->metadata ?? [];
        $metadata['thumbnail_processed'] = false;
        $metadata['thumbnail_error'] = $error;
        $metadata['thumbnail_failed_at'] = now()->toISOString();

        $this->file->update([
            'metadata' => $metadata,
        ]);
    }
}

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

class GenerateFileWaveform implements ShouldQueue
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
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Get the file URL for processing
            $fileUrl = $this->getFileUrl();

            Log::info('Processing audio file for waveform generation', [
                'file_type' => get_class($this->file),
                'file_id' => $this->file->id,
                'file_path' => $this->getFilePath(),
                'fileUrl' => $fileUrl,
            ]);

            if (empty($fileUrl)) {
                Log::error('Failed to generate waveform: Could not get URL for file', [
                    'file_type' => get_class($this->file),
                    'file_id' => $this->file->id,
                    'file_path' => $this->getFilePath(),
                ]);

                return;
            }

            // Extract duration and generate waveform using external API
            $result = $this->processAudioWithExternalService($fileUrl);

            if (! $result) {
                Log::warning('Failed to process audio with external service', [
                    'file_type' => get_class($this->file),
                    'file_id' => $this->file->id,
                    'file_path' => $this->getFilePath(),
                ]);

                return;
            }

            // Update the file with the generated waveform data and duration
            $this->file->update([
                'waveform_peaks' => json_encode($result['waveform_peaks']),
                'waveform_processed' => true,
                'waveform_processed_at' => now(),
                'duration' => $result['duration'],
            ]);

            Log::info("Waveform and duration successfully generated for {$this->getFileType()} ID: {$this->file->id}");
        } catch (\Exception $e) {
            Log::error("Failed to generate waveform for {$this->getFileType()} ID: {$this->file->id}. Error: {$e->getMessage()}", [
                'exception' => $e,
                'file_path' => $this->getFilePath(),
            ]);

            // Mark as failed after retries
            if ($this->attempts() >= $this->tries) {
                $this->file->update([
                    'waveform_processed' => true,
                    'waveform_processed_at' => now(),
                ]);
            }
        }
    }

    /**
     * Get the file URL for processing based on file type
     *
     * @return string|null
     */
    protected function getFileUrl()
    {
        if ($this->file instanceof \App\Models\PitchFile) {
            return $this->file->getOriginalFileUrl(120); // 2 hour expiration for processing
        }

        if ($this->file instanceof \App\Models\ProjectFile) {
            return $this->file->signedUrl(120); // 2 hour expiration for processing
        }

        return null;
    }

    /**
     * Get the file path based on file type
     *
     * @return string
     */
    protected function getFilePath()
    {
        return $this->file->file_path ?? '';
    }

    /**
     * Get a human-readable file type name
     *
     * @return string
     */
    protected function getFileType()
    {
        return class_basename(get_class($this->file));
    }

    /**
     * Process audio file with external service
     *
     * @param  string  $fileUrl
     * @return array|null
     */
    protected function processAudioWithExternalService($fileUrl)
    {
        try {
            // Use AWS Lambda for waveform generation
            $lambdaUrl = config('services.aws.lambda_audio_processor_url', null);

            if ($lambdaUrl) {
                Log::info('Using AWS Lambda for waveform generation');

                return $this->processAudioWithAwsLambda($fileUrl);
            }

            // If Lambda is not configured, use fallback method
            Log::warning('AWS Lambda audio processor not configured, using fallback method');

            return $this->generateFallbackWaveformData();

        } catch (\Exception $e) {
            Log::error('Error calling AWS Lambda audio processor', [
                'error' => $e->getMessage(),
                'file_url' => $fileUrl,
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback to estimation method
            return $this->generateFallbackWaveformData();
        }
    }

    /**
     * Process audio file with AWS Lambda
     *
     * @param  string  $fileUrl
     * @return array|null
     */
    protected function processAudioWithAwsLambda($fileUrl)
    {
        try {
            $lambdaUrl = config('services.aws.lambda_audio_processor_url');

            // Append /waveform to the URL if it's not already there
            if (! str_ends_with($lambdaUrl, '/waveform')) {
                $lambdaUrl .= '/waveform';
            }

            // Properly encode the URL - ensure spaces are encoded as %20
            $encodedFileUrl = str_replace(' ', '%20', $fileUrl);

            // Make sure URL is properly formatted
            if (! filter_var($encodedFileUrl, FILTER_VALIDATE_URL)) {
                Log::error('Invalid file URL format even after encoding', [
                    'original_url' => $fileUrl,
                    'encoded_url' => $encodedFileUrl,
                ]);

                return $this->generateFallbackWaveformData();
            }

            Log::info('Calling AWS Lambda audio processor', [
                'lambda_url' => $lambdaUrl,
                'file_url' => $fileUrl,
                'encoded_file_url' => $encodedFileUrl,
                'file_path' => $this->getFilePath(),
            ]);

            // Make the request to the Lambda function with the encoded URL
            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($lambdaUrl, [
                    'file_url' => $encodedFileUrl, // Use the encoded URL here
                    'peaks_count' => 200, // Number of data points in the waveform
                ]);

            // Log complete response for debugging
            Log::info('Lambda response details', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body_excerpt' => substr($response->body(), 0, 1000),
            ]);

            if ($response->successful()) {
                // Get the initial response data
                $responseData = $response->json();

                // First, check if the response has a nested structure with statusCode and body
                // This is common with API Gateway integrations
                if (isset($responseData['statusCode']) && isset($responseData['body'])) {
                    // If the body is a JSON string, decode it
                    if (is_string($responseData['body'])) {
                        try {
                            // Handle double-encoded JSON (body is a JSON string)
                            $data = json_decode($responseData['body'], true);

                            // If json_decode returns null but the body isn't empty, it might be a regular string or escaped JSON
                            if ($data === null && ! empty($responseData['body'])) {
                                // Try to remove escaped quotes and decode again
                                $cleanBody = trim($responseData['body'], '"');
                                $cleanBody = str_replace('\"', '"', $cleanBody);
                                $data = json_decode($cleanBody, true);

                                // If still null, use the body directly
                                if ($data === null) {
                                    $data = ['message' => $responseData['body']];
                                }
                            }
                        } catch (\Exception $e) {
                            Log::warning('Error decoding Lambda response body', [
                                'error' => $e->getMessage(),
                                'body' => $responseData['body'],
                            ]);
                            $data = ['message' => $responseData['body']];
                        }
                    } else {
                        // Body is already an array/object
                        $data = $responseData['body'];
                    }
                } else {
                    // Response is already the data we need
                    $data = $responseData;
                }

                // Check for error messages in the data
                if (isset($data['error']) || isset($data['message'])) {
                    $errorMessage = $data['error'] ?? $data['message'] ?? 'Unknown error';

                    // Special handling for the pattern matching error
                    if (strpos($errorMessage, 'did not match the expected pattern') !== false) {
                        Log::error('Lambda URL validation error', [
                            'error' => $errorMessage,
                            'file_url' => $fileUrl,
                        ]);

                        // Try to fix the URL if possible
                        // Some Lambda functions expect URLs without special characters or need encoding
                        $modifiedUrl = str_replace(' ', '%20', $fileUrl);

                        Log::info('Retrying with modified URL', [
                            'original' => $fileUrl,
                            'modified' => $modifiedUrl,
                        ]);

                        // Retry with modified URL
                        if ($modifiedUrl !== $fileUrl) {
                            return $this->processAudioWithExternalService($modifiedUrl);
                        }
                    }

                    Log::error('Lambda returned error in response', [
                        'error' => $errorMessage,
                    ]);

                    return $this->generateFallbackWaveformData();
                }

                // Verify we have the expected data
                if (isset($data['duration']) || isset($data['peaks'])) {
                    Log::info('Successfully processed audio file with AWS Lambda', [
                        'file_type' => $this->getFileType(),
                        'file_id' => $this->file->id,
                        'duration' => $data['duration'] ?? 'unknown',
                        'peaks_count' => isset($data['peaks']) ? count($data['peaks']) : 0,
                    ]);

                    return [
                        'duration' => $data['duration'] ?? 0,
                        'waveform_peaks' => $data['peaks'] ?? [],
                    ];
                } else {
                    Log::warning('Lambda response missing duration or peaks data', [
                        'response_data' => $data,
                    ]);
                }
            }

            Log::error('AWS Lambda audio processing failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // Fallback to estimation method
            return $this->generateFallbackWaveformData();
        } catch (\Exception $e) {
            Log::error('Error calling AWS Lambda audio processor', [
                'error' => $e->getMessage(),
                'file_url' => $fileUrl,
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback to estimation method
            return $this->generateFallbackWaveformData();
        }
    }

    /**
     * Generate fallback waveform data when AWS Lambda processing fails
     *
     * @return array
     */
    protected function generateFallbackWaveformData()
    {
        Log::info('Using fallback method for waveform generation', [
            'file_type' => $this->getFileType(),
            'file_id' => $this->file->id,
        ]);

        $numPeaks = 200;
        $duration = $this->estimateDurationFromFileSize();
        $peaks = $this->generatePlaceholderWaveform($numPeaks);

        return [
            'duration' => $duration,
            'waveform_peaks' => $peaks,
        ];
    }

    /**
     * Estimate duration based on file size when external processing is not available
     * This is a fallback method and not very accurate, but better than nothing
     *
     * @return float
     */
    protected function estimateDurationFromFileSize()
    {
        try {
            Log::info('Accessing S3 for file size determination', [
                'file_type' => $this->getFileType(),
                'file_id' => $this->file->id,
                'file_path' => $this->getFilePath(),
                's3_disk_configured' => config('filesystems.disks.s3') ? 'yes' : 'no',
                'default_disk' => config('filesystems.default'),
            ]);

            if (! Storage::disk('s3')->exists($this->getFilePath())) {
                Log::warning('File not found in S3 during size estimation', [
                    'file_path' => $this->getFilePath(),
                ]);

                return 180; // Default to 3 minutes as a fallback
            }

            $size = Storage::disk('s3')->size($this->getFilePath());
            $fileName = $this->file instanceof \App\Models\PitchFile ? $this->file->file_name : $this->file->original_file_name;
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);

            Log::info('File size determined from S3', [
                'file_type' => $this->getFileType(),
                'file_id' => $this->file->id,
                'size' => $size,
                'extension' => $extension,
            ]);

            // Very rough estimation based on typical bitrates
            // MP3: ~128kbps = 16KB/s
            // WAV: ~1411kbps = 176KB/s
            switch (strtolower($extension)) {
                case 'mp3':
                    return $size / (16 * 1024);
                case 'wav':
                    return $size / (176 * 1024);
                case 'flac':
                    return $size / (88 * 1024);
                case 'aac':
                case 'm4a':
                    return $size / (19 * 1024);
                default:
                    return $size / (16 * 1024); // Default to MP3 estimate
            }
        } catch (\Exception $e) {
            Log::warning('Could not estimate duration from file size', [
                'error' => $e->getMessage(),
                'file_path' => $this->getFilePath(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 180; // Default to 3 minutes as a fallback
        }
    }

    /**
     * Generate placeholder waveform data when external processing is not available
     *
     * @param  int  $numPeaks
     * @return array
     */
    protected function generatePlaceholderWaveform($numPeaks)
    {
        $peaks = [];

        // Generate a simple sine wave pattern as placeholder
        for ($i = 0; $i < $numPeaks; $i++) {
            $position = $i / ($numPeaks - 1);
            $amplitude = 0.5 + 0.4 * sin($position * 2 * M_PI * 3);
            $peaks[] = [-$amplitude, $amplitude];
        }

        return $peaks;
    }
}

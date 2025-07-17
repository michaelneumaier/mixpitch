<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TestLambdaDeployment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:lambda-deployment {--test-file= : Path to test audio file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the AWS Lambda deployment and R2 integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Lambda deployment and R2 integration...');

        // Check configuration
        $this->info('Checking configuration...');
        $lambdaUrl = config('services.aws.lambda_audio_processor_url');
        $awsLambdaEnabled = config('audio.aws_lambda.enabled');

        if (! $lambdaUrl) {
            $this->error('Lambda URL not configured in services.aws.lambda_audio_processor_url');

            return 1;
        }

        if (! $awsLambdaEnabled) {
            $this->warn('AWS Lambda is disabled in audio.aws_lambda.enabled');
        }

        $this->info("✓ Lambda URL: {$lambdaUrl}");
        $this->info('✓ Lambda enabled: '.($awsLambdaEnabled ? 'Yes' : 'No'));

        // Check R2 configuration
        $this->info('Checking R2 configuration...');
        $r2Config = [
            'CF_R2_ACCESS_KEY_ID' => config('services.cloudflare.r2_access_key_id'),
            'CF_R2_SECRET_ACCESS_KEY' => config('services.cloudflare.r2_secret_access_key'),
            'CF_R2_ENDPOINT' => config('services.cloudflare.r2_endpoint'),
            'CF_R2_BUCKET' => config('services.cloudflare.r2_bucket'),
        ];

        foreach ($r2Config as $key => $value) {
            if (empty($value)) {
                $this->warn("⚠ {$key} not configured - will fallback to S3");
            } else {
                $this->info("✓ {$key} configured");
            }
        }

        // Test lambda endpoint connectivity
        $this->info('Testing Lambda endpoint connectivity...');

        try {
            $response = Http::timeout(30)->get($lambdaUrl);

            if ($response->successful()) {
                $this->info('✓ Lambda endpoint is reachable');
            } else {
                $this->error('✗ Lambda endpoint returned status: '.$response->status());
                $this->line('Response body: '.$response->body());
            }
        } catch (\Exception $e) {
            $this->error('✗ Failed to connect to Lambda endpoint: '.$e->getMessage());

            return 1;
        }

        // Test with sample audio file
        $testFile = $this->option('test-file');

        if (! $testFile) {
            $this->info('No test file specified. Looking for existing pitch files...');

            // Find a pitch file to test with
            $pitchFile = \App\Models\PitchFile::whereRaw("LOWER(file_name) LIKE '%.mp3'")
                ->orWhereRaw("LOWER(file_name) LIKE '%.wav'")
                ->orWhereRaw("LOWER(file_name) LIKE '%.ogg'")
                ->orWhereRaw("LOWER(file_name) LIKE '%.aac'")
                ->orWhereRaw("LOWER(file_name) LIKE '%.m4a'")
                ->orWhereRaw("LOWER(file_name) LIKE '%.flac'")
                ->first();

            if (! $pitchFile) {
                $this->warn('No audio files found in database. Use --test-file option to specify a test file.');

                return 0;
            }

            $this->info("Found audio file: {$pitchFile->file_name} (ID: {$pitchFile->id})");

            // Get file URL
            try {
                $fileUrl = $pitchFile->getOriginalFileUrl(60);
                $this->info("File URL: {$fileUrl}");
            } catch (\Exception $e) {
                $this->error('Failed to get file URL: '.$e->getMessage());

                return 1;
            }
        } else {
            // Upload test file temporarily
            $this->info("Uploading test file: {$testFile}");

            if (! file_exists($testFile)) {
                $this->error("Test file not found: {$testFile}");

                return 1;
            }

            $tempPath = 'temp/test-audio-'.time().'.'.pathinfo($testFile, PATHINFO_EXTENSION);
            Storage::disk('s3')->put($tempPath, file_get_contents($testFile));

            $fileUrl = Storage::disk('s3')->url($tempPath);
            $this->info("Test file uploaded: {$fileUrl}");
        }

        // Test Lambda processing
        $this->info('Testing Lambda processing...');

        $payload = [
            'file_url' => $fileUrl,
            'target_format' => 'mp3',
            'target_bitrate' => '192k',
            'apply_watermark' => true,
            'watermark_settings' => [
                'type' => 'periodic_tone',
                'frequency' => 1000,
                'volume' => 0.5,
                'duration' => 0.8,
                'interval' => 20,
                'pitch_id' => 'test',
                'project_id' => 'test',
            ],
        ];

        $this->info('Sending payload to Lambda...');
        $this->line('Payload: '.json_encode($payload, JSON_PRETTY_PRINT));

        try {
            $response = Http::timeout(300)->post($lambdaUrl.'/transcode', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $this->info('✓ Lambda processing successful!');
                $this->line('Response: '.json_encode($responseData, JSON_PRETTY_PRINT));

                // Verify the processed file
                if (isset($responseData['success']) && $responseData['success']) {
                    if (isset($responseData['output_url'])) {
                        $this->info('✓ Processed file URL: '.$responseData['output_url']);

                        // Test downloading the processed file
                        $this->info('Testing download of processed file...');
                        try {
                            $downloadResponse = Http::timeout(60)->get($responseData['output_url']);

                            if ($downloadResponse->successful()) {
                                $fileSize = strlen($downloadResponse->body());
                                $this->info("✓ Successfully downloaded processed file ({$fileSize} bytes)");

                                // Verify it's actually audio content
                                $content = $downloadResponse->body();
                                if (empty($content)) {
                                    $this->error('✗ Downloaded file is empty');
                                } elseif (str_starts_with(trim($content), '<?xml')) {
                                    $this->error('✗ Downloaded file contains XML error response');
                                } else {
                                    $this->info('✓ Downloaded file appears to be valid audio content');
                                }
                            } else {
                                $this->error('✗ Failed to download processed file: '.$downloadResponse->status());
                            }
                        } catch (\Exception $e) {
                            $this->error('✗ Download failed: '.$e->getMessage());
                        }
                    } else {
                        $this->error('✗ No output_url in response');
                    }
                } else {
                    $this->error('✗ Lambda processing failed: '.($responseData['error'] ?? 'Unknown error'));
                }
            } else {
                $this->error('✗ Lambda request failed: '.$response->status());
                $this->line('Response body: '.$response->body());
            }
        } catch (\Exception $e) {
            $this->error('✗ Lambda processing failed: '.$e->getMessage());

            return 1;
        }

        // Clean up test file if we uploaded one
        if (isset($tempPath)) {
            Storage::disk('s3')->delete($tempPath);
            $this->info("Cleaned up test file: {$tempPath}");
        }

        $this->info('Lambda deployment test completed!');

        return 0;
    }
}

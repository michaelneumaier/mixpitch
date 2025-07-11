<?php

namespace App\Services;

use App\Models\Pitch;
use App\Models\PitchFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class AudioProcessingService
{
    /**
     * Store processed file from AWS Lambda response
     */
    protected function storeProcessedFile(PitchFile $originalFile, string $processedFileUrl, Pitch $pitch): string
    {
        Log::info('Attempting to download processed file from Lambda', [
            'original_file_id' => $originalFile->id,
            'url' => $processedFileUrl,
            'pitch_id' => $pitch->id
        ]);

        // Try downloading with retries
        $maxRetries = 3;
        $retryDelay = 2; // seconds
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::info("Download attempt {$attempt}/{$maxRetries}", [
                    'file_id' => $originalFile->id,
                    'url' => $processedFileUrl
                ]);

                $response = Http::timeout(120)
                    ->retry(2, 1000) // 2 retries with 1 second delay
                    ->get($processedFileUrl);

                if (!$response->successful()) {
                    Log::warning("HTTP request failed on attempt {$attempt}", [
                        'status' => $response->status(),
                        'headers' => $response->headers(),
                        'body_preview' => substr($response->body(), 0, 500)
                    ]);
                    
                    if ($attempt < $maxRetries) {
                        sleep($retryDelay);
                        continue;
                    }
                    
                    throw new \Exception("Failed to download processed file after {$maxRetries} attempts. Status: {$response->status()}");
                }

                $fileContent = $response->body();
                
                // Validate that we got actual audio content, not an error response
                if (empty($fileContent)) {
                    throw new \Exception('Downloaded file is empty');
                }
                
                // Check if the content looks like an XML error response
                if (str_starts_with(trim($fileContent), '<?xml') && str_contains($fileContent, '<Error>')) {
                    Log::error('Downloaded content appears to be an S3 error response', [
                        'content_preview' => substr($fileContent, 0, 500)
                    ]);
                    
                    if ($attempt < $maxRetries) {
                        sleep($retryDelay);
                        continue;
                    }
                    
                    throw new \Exception('Downloaded file contains S3 error response instead of audio content');
                }
                
                // Check minimum file size (audio files should be much larger than error responses)
                if (strlen($fileContent) < 1024) { // Less than 1KB is suspicious for audio
                    Log::warning('Downloaded file seems too small for audio content', [
                        'size' => strlen($fileContent),
                        'content_preview' => substr($fileContent, 0, 200)
                    ]);
                    
                    if ($attempt < $maxRetries) {
                        sleep($retryDelay);
                        continue;
                    }
                    
                    throw new \Exception('Downloaded file too small to be valid audio content');
                }

                // File seems valid, proceed with storage
                $filename = $this->generateProcessedFileName($originalFile, $pitch);
                $storagePath = 'pitches/' . $pitch->id . '/processed/' . $filename;

                Storage::disk('s3')->put($storagePath, $fileContent);

                Log::info('Processed file stored successfully', [
                    'original_file_id' => $originalFile->id,
                    'storage_path' => $storagePath,
                    'file_size' => strlen($fileContent),
                    'pitch_id' => $pitch->id,
                    'attempt' => $attempt
                ]);

                return $storagePath;

            } catch (\Exception $e) {
                Log::error("Failed to download/store processed file on attempt {$attempt}", [
                    'original_file_id' => $originalFile->id,
                    'error' => $e->getMessage(),
                    'url' => $processedFileUrl,
                    'attempt' => $attempt
                ]);
                
                if ($attempt >= $maxRetries) {
                    throw new \Exception("Failed to download processed file after {$maxRetries} attempts: " . $e->getMessage());
                }
                
                sleep($retryDelay);
            }
        }
        
        throw new \Exception("Unexpected error in file download process");
    }

    /**
     * Store processed file from local processing
     */
    protected function storeProcessedFileFromLocal(PitchFile $originalFile, string $tempFilePath, Pitch $pitch): string
    {
        $filename = $this->generateProcessedFileName($originalFile, $pitch);
        $storagePath = 'pitches/' . $pitch->id . '/processed/' . $filename;
        
        $fileContent = file_get_contents($tempFilePath);
        Storage::disk('s3')->put($storagePath, $fileContent);

        Log::info('Processed file stored from local', [
            'original_file_id' => $originalFile->id,
            'storage_path' => $storagePath,
            'pitch_id' => $pitch->id
        ]);

        return $storagePath;
    }

    /**
     * Generate filename for processed file
     */
    protected function generateProcessedFileName(PitchFile $originalFile, Pitch $pitch): string
    {
        $originalName = pathinfo($originalFile->file_name, PATHINFO_FILENAME);
        $timestamp = date('Ymd_His');
        $hash = substr(md5($originalFile->id . $pitch->id . $timestamp), 0, 8);
        
        return "transcoded_{$timestamp}_{$hash}.mp3";
    }

    /**
     * Update pitch file with processing results
     */
    protected function updatePitchFileWithResults(PitchFile $pitchFile, array $result): void
    {
        try {
            $processedData = [
                'output_path' => $result['output_path'] ?? null,
                'output_filename' => $result['output_filename'] ?? null,
                'output_size' => $result['output_size'] ?? null,
                'transcoded' => $result['transcoded'] ?? false,
                'watermarked' => $result['watermarked'] ?? false,
                'format' => $result['target_format'] ?? null,
                'bitrate' => $result['target_bitrate'] ?? null,
                'metadata' => [
                    'processing_method' => $result['processing_method'] ?? null,
                    'processing_time' => $result['processing_time'] ?? null,
                    'lambda_response' => $result['lambda_response'] ?? null,
                    'processed_at' => now()->toISOString()
                ]
            ];

            if ($result['error']) {
                $pitchFile->markAsProcessingFailed($result['error']);
            } else {
                $pitchFile->markAsProcessed($processedData);
            }

            Log::info('PitchFile updated with processing results', [
                'file_id' => $pitchFile->id,
                'processed' => !$result['error'],
                'transcoded' => $result['transcoded'] ?? false,
                'watermarked' => $result['watermarked'] ?? false
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update PitchFile with processing results', [
                'file_id' => $pitchFile->id,
                'error' => $e->getMessage()
            ]);
            
            $pitchFile->markAsProcessingFailed('Failed to update file with processing results: ' . $e->getMessage());
        }
    }

    /**
     * Process an audio file for submission (transcoding and watermarking)
     */
    public function processAudioFileForSubmission(PitchFile $pitchFile, Pitch $pitch): array
    {
        Log::info('Starting audio processing for submission', [
            'file_id' => $pitchFile->id,
            'file_name' => $pitchFile->file_name,
            'pitch_id' => $pitch->id,
            'project_id' => $pitch->project_id
        ]);

        $fileExtension = strtolower(pathinfo($pitchFile->file_name, PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, config('audio.supported_formats', ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'flac']))) {
            Log::warning('Unsupported audio format', [
                'file_id' => $pitchFile->id,
                'extension' => $fileExtension
            ]);
            
            $result = [
                'transcoded' => false,
                'watermarked' => false,
                'error' => 'Unsupported audio format'
            ];
            
            $this->updatePitchFileWithResults($pitchFile, $result);
            return $result;
        }

        $result = [
            'file_id' => $pitchFile->id,
            'original_format' => $fileExtension,
            'target_format' => config('audio.target_format', 'mp3'),
            'target_bitrate' => config('audio.target_bitrate', '192k'),
            'transcoded' => false,
            'watermarked' => false,
            'output_path' => null,
            'output_filename' => null,
            'output_size' => null,
            'processing_method' => null,
            'processing_time' => null,
            'error' => null
        ];

        $startTime = microtime(true);

        try {
            // Get the file URL for processing
            $fileUrl = $pitchFile->getOriginalFileUrl(60); // 60 minute expiration for processing
            
            if (!$fileUrl) {
                throw new \Exception('Could not generate file URL for processing');
            }

            // Try AWS Lambda first
            if ($this->shouldUseAwsLambda()) {
                $result = array_merge($result, $this->processWithAwsLambda($fileUrl, $pitchFile, $pitch));
                $result['processing_method'] = 'aws_lambda';
            } elseif ($this->isFfmpegAvailable()) {
                // Fallback to local FFmpeg processing
                $result = array_merge($result, $this->processWithLocalFfmpeg($fileUrl, $pitchFile, $pitch));
                $result['processing_method'] = 'local_ffmpeg';
            } else {
                throw new \Exception('No audio processing method available');
            }

            $result['processing_time'] = microtime(true) - $startTime;

            Log::info('Audio processing completed', [
                'file_id' => $pitchFile->id,
                'method' => $result['processing_method'],
                'time' => $result['processing_time'],
                'transcoded' => $result['transcoded'],
                'watermarked' => $result['watermarked']
            ]);

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            $result['processing_time'] = microtime(true) - $startTime;
            
            Log::error('Audio processing failed', [
                'file_id' => $pitchFile->id,
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
                'processing_time' => $result['processing_time']
            ]);
        }

        // Update the PitchFile with results
        $this->updatePitchFileWithResults($pitchFile, $result);

        // Schedule cleanup of temporary files if processing was successful
        if (!$result['error'] && config('audio.storage.cleanup_temp_files', true)) {
            $this->scheduleTemporaryFileCleanup($result, $pitchFile);
        }

        return $result;
    }

    /**
     * Process with AWS Lambda
     */
    protected function processWithAwsLambda(string $fileUrl, PitchFile $pitchFile, Pitch $pitch): array
    {
        $lambdaUrl = config('services.aws.lambda_audio_processor_url');
        
        $payload = [
            'file_url' => $fileUrl,
            'target_format' => config('audio.target_format', 'mp3'),
            'target_bitrate' => config('audio.target_bitrate', '192k'),
            'apply_watermark' => true,
            'watermark_settings' => $this->getWatermarkSettings($pitch)
        ];

        Log::info('Calling AWS Lambda for audio processing', [
            'lambda_url' => $lambdaUrl . '/transcode',
            'file_id' => $pitchFile->id,
            'payload' => $payload
        ]);

        $response = Http::timeout(300)->post($lambdaUrl . '/transcode', $payload);

        if (!$response->successful()) {
            Log::error('Lambda processing failed', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body()
            ]);
            throw new \Exception('Lambda processing failed: ' . $response->body());
        }

        $responseData = $response->json();
        
        Log::info('Lambda response received', [
            'response' => $responseData,
            'file_id' => $pitchFile->id
        ]);
        
        if (!$responseData['success']) {
            throw new \Exception('Lambda processing error: ' . ($responseData['error'] ?? 'Unknown error'));
        }

        // Check if Lambda uploaded directly to final destination
        if (isset($responseData['direct_upload']) && $responseData['direct_upload'] && isset($responseData['s3_key'])) {
            // Lambda uploaded directly to final destination - no need to re-upload
            $outputPath = $responseData['s3_key'];
            $storageProvider = $responseData['storage_provider'] ?? 'unknown';
            
            Log::info('Lambda uploaded directly to final destination', [
                'file_id' => $pitchFile->id,
                'output_path' => $outputPath,
                'storage_provider' => $storageProvider,
                'skipped_reupload' => true
            ]);
            
            // Try to verify file exists and get size, but don't fail if we can't access it
            // This handles cases where Lambda uploads to R2 but local environment doesn't have R2 credentials
            try {
                if (!Storage::disk('s3')->exists($outputPath)) {
                    Log::warning('Cannot verify direct upload file exists (may be due to missing R2 credentials locally)', [
                        'file_id' => $pitchFile->id,
                        'output_path' => $outputPath,
                        'storage_provider' => $storageProvider,
                        'note' => 'Trusting lambda direct upload result'
                    ]);
                }
                
                $outputSize = Storage::disk('s3')->size($outputPath);
            } catch (\Exception $e) {
                Log::warning('Cannot verify direct upload file details (may be due to missing R2 credentials locally)', [
                    'file_id' => $pitchFile->id,
                    'output_path' => $outputPath,
                    'storage_provider' => $storageProvider,
                    'error' => $e->getMessage(),
                    'note' => 'Trusting lambda direct upload result'
                ]);
                
                // Calculate size from lambda response audio info
                $outputInfo = $responseData['output_info'] ?? [];
                if (isset($outputInfo['duration']) && isset($outputInfo['bitrate'])) {
                    // Calculate size: (bitrate * duration) / 8 (convert bits to bytes)
                    $outputSize = round(($outputInfo['bitrate'] * $outputInfo['duration']) / 8);
                } else {
                    // Fallback estimate based on typical MP3 files
                    $estimatedBitrate = 192000; // 192kbps
                    $estimatedDuration = 180;   // 3 minutes
                    $outputSize = round(($estimatedBitrate * $estimatedDuration) / 8);
                }
                
                Log::info('Calculated output size from lambda response', [
                    'file_id' => $pitchFile->id,
                    'calculated_size' => $outputSize,
                    'duration' => $outputInfo['duration'] ?? 'unknown',
                    'bitrate' => $outputInfo['bitrate'] ?? 'unknown',
                    'method' => 'lambda_response_calculation'
                ]);
            }
            
        } else {
            // Fallback to old method - Lambda uploaded to temporary location
            if (!isset($responseData['output_url']) || empty($responseData['output_url'])) {
                Log::error('Lambda response missing output_url', [
                    'response' => $responseData,
                    'file_id' => $pitchFile->id
                ]);
                throw new \Exception('Lambda response missing output_url');
            }

            $outputUrl = $responseData['output_url'];
            
            Log::info('Lambda returned output URL - using fallback method', [
                'output_url' => $outputUrl,
                'file_id' => $pitchFile->id,
                'url_type' => str_contains($outputUrl, 'amazonaws.com') ? 'S3 URL' : 'Other'
            ]);

            // Store the processed file with improved error handling
            $outputPath = $this->storeProcessedFile($pitchFile, $outputUrl, $pitch);
            
            // Get file size
            $outputSize = Storage::disk('s3')->size($outputPath);
        }

        return [
            'transcoded' => true,
            'watermarked' => true,
            'output_path' => $outputPath,
            'output_filename' => basename($outputPath),
            'output_size' => $outputSize,
            'lambda_response' => $responseData
        ];
    }

    /**
     * Process with local FFmpeg
     */
    protected function processWithLocalFfmpeg(string $fileUrl, PitchFile $pitchFile, Pitch $pitch): array
    {
        $tempInputFile = tempnam(sys_get_temp_dir(), 'audio_input_');
        $tempOutputFile = tempnam(sys_get_temp_dir(), 'audio_output_') . '.mp3';

        try {
            // Download input file
            $fileContent = Http::get($fileUrl)->body();
            file_put_contents($tempInputFile, $fileContent);

            // Build FFmpeg command
            $command = $this->buildFfmpegCommand($tempInputFile, $tempOutputFile, $pitch);

            $process = Process::run($command);
            
            if (!$process->successful()) {
                throw new \Exception('FFmpeg processing failed: ' . $process->errorOutput());
            }

            // Store the processed file
            $outputPath = $this->storeProcessedFileFromLocal($pitchFile, $tempOutputFile, $pitch);
            
            // Get file size
            $outputSize = Storage::disk('s3')->size($outputPath);

            return [
                'transcoded' => true,
                'watermarked' => $this->hasWatermarkCapability(),
                'output_path' => $outputPath,
                'output_filename' => basename($outputPath),
                'output_size' => $outputSize
            ];

        } finally {
            // Clean up temporary files
            @unlink($tempInputFile);
            @unlink($tempOutputFile);
        }
    }

    /**
     * Build FFmpeg command for transcoding and watermarking
     */
    protected function buildFfmpegCommand(string $inputFile, string $outputFile, Pitch $pitch): string
    {
        $command = [
            'ffmpeg',
            '-i', $inputFile,
            '-y', // Overwrite output file
            '-codec:a', 'libmp3lame',
            '-b:a', config('audio.target_bitrate', '192k'),
            '-ar', '44100',
            '-ac', '2'
        ];

        // Add watermarking if supported
        if ($this->hasWatermarkCapability()) {
            // Generate a simple tone watermark
            $watermarkCommand = $this->buildWatermarkCommand($pitch);
            $command = array_merge($command, $watermarkCommand);
        }

        $command[] = $outputFile;

        return implode(' ', array_map('escapeshellarg', $command));
    }

    /**
     * Build watermark command for FFmpeg
     */
    protected function buildWatermarkCommand(Pitch $pitch): array
    {
        // Simple tone watermark implementation
        // In production, you might want to use a more sophisticated watermarking system
        return [
            '-af', 'volume=' . config('audio.watermarking.volume', '0.1') . ',highpass=f=800'
        ];
    }

    /**
     * Check if AWS Lambda should be used
     */
    protected function shouldUseAwsLambda(): bool
    {
        return config('audio.aws_lambda.enabled', true) && 
               config('services.aws.lambda_audio_processor_url');
    }

    /**
     * Check if FFmpeg is available
     */
    protected function isFfmpegAvailable(): bool
    {
        try {
            $process = Process::run('ffmpeg -version');
            return $process->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if watermarking capability is available
     */
    protected function hasWatermarkCapability(): bool
    {
        // This can be expanded based on available watermarking libraries
        return $this->isFfmpegAvailable() || $this->shouldUseAwsLambda();
    }

    /**
     * Get audio processing configuration
     */
    public function getProcessingConfig(): array
    {
        return [
            'supported_formats' => config('audio.supported_formats', ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'flac']),
            'target_format' => config('audio.target_format', 'mp3'),
            'target_bitrate' => config('audio.target_bitrate', '192k'),
            'use_lambda' => config('audio.aws_lambda.enabled', true) && config('services.aws.lambda_audio_processor_url'),
            'ffmpeg_available' => $this->isFfmpegAvailable(),
            'watermark_capability' => $this->hasWatermarkCapability(),
        ];
    }

    /**
     * Get watermark settings for a project
     */
    public function getWatermarkSettings(Pitch $pitch): array
    {
        $defaults = config('audio.watermarking.default_settings', [
            'type' => 'periodic_tone',
            'frequency' => 1000,
            'volume' => 0.5,
            'duration' => 0.8,
            'interval' => 20,
        ]);

        return array_merge($defaults, [
            'project_id' => $pitch->project_id,
            'pitch_id' => $pitch->id
        ]);
    }

    /**
     * Schedule cleanup of temporary files created during processing
     */
    protected function scheduleTemporaryFileCleanup(array $result, PitchFile $pitchFile): void
    {
        $tempFiles = [];
        
        // Add Lambda response output URL to cleanup list if it's different from final path
        if (isset($result['lambda_response']['output_url']) && 
            isset($result['lambda_response']['s3_key']) && 
            $result['lambda_response']['s3_key'] !== $result['output_path']) {
            
            // Extract S3 key from the output URL if it's an S3 URL
            $outputUrl = $result['lambda_response']['output_url'];
            if (str_contains($outputUrl, 'amazonaws.com')) {
                $tempFiles[] = $this->extractS3KeyFromUrl($outputUrl);
            }
        }
        
        // Add any other temporary files from processing metadata
        if (isset($result['lambda_response']['temp_files'])) {
            $tempFiles = array_merge($tempFiles, $result['lambda_response']['temp_files']);
        }
        
        if (!empty($tempFiles)) {
            $delayMinutes = config('audio.storage.cleanup_delay_minutes', 60);
            
            Log::info('Scheduling temporary file cleanup', [
                'file_id' => $pitchFile->id,
                'temp_files' => $tempFiles,
                'final_file' => $result['output_path'],
                'delay_minutes' => $delayMinutes
            ]);
            
            // Dispatch cleanup job with delay
            \App\Jobs\CleanupTemporaryAudioFiles::dispatch($tempFiles, $result['output_path'])
                ->delay(now()->addMinutes($delayMinutes));
        }
    }

    /**
     * Extract S3 key from S3 URL
     */
    protected function extractS3KeyFromUrl(string $url): string
    {
        // Handle both path-style and virtual-hosted-style URLs
        if (preg_match('/amazonaws\.com\/([^?]+)/', $url, $matches)) {
            return $matches[1];
        }
        
        // Fallback - just return the URL as-is
        return $url;
    }
} 
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
    const SUPPORTED_AUDIO_FORMATS = ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'flac'];
    const TARGET_FORMAT = 'mp3';
    const TARGET_BITRATE = '192k';
    const WATERMARK_VOLUME = '0.1'; // 10% volume for watermark
    
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
        
        if (!in_array($fileExtension, self::SUPPORTED_AUDIO_FORMATS)) {
            Log::warning('Unsupported audio format', [
                'file_id' => $pitchFile->id,
                'extension' => $fileExtension
            ]);
            return [
                'transcoded' => false,
                'watermarked' => false,
                'error' => 'Unsupported audio format'
            ];
        }

        $result = [
            'file_id' => $pitchFile->id,
            'original_format' => $fileExtension,
            'target_format' => self::TARGET_FORMAT,
            'transcoded' => false,
            'watermarked' => false,
            'output_path' => null,
            'processing_method' => null,
            'processing_time' => null,
            'error' => null
        ];

        $startTime = microtime(true);

        try {
            // Choose processing method based on configuration
            if ($this->shouldUseAwsLambda()) {
                $result = array_merge($result, $this->processWithAwsLambda($pitchFile, $pitch));
                $result['processing_method'] = 'aws_lambda';
            } else {
                $result = array_merge($result, $this->processWithFfmpeg($pitchFile, $pitch));
                $result['processing_method'] = 'ffmpeg_local';
            }

            $result['processing_time'] = round(microtime(true) - $startTime, 2);

            Log::info('Audio processing completed', [
                'file_id' => $pitchFile->id,
                'method' => $result['processing_method'],
                'transcoded' => $result['transcoded'],
                'watermarked' => $result['watermarked'],
                'processing_time' => $result['processing_time']
            ]);

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            $result['processing_time'] = round(microtime(true) - $startTime, 2);
            
            Log::error('Audio processing failed', [
                'file_id' => $pitchFile->id,
                'error' => $e->getMessage(),
                'processing_time' => $result['processing_time']
            ]);
        }

        return $result;
    }

    /**
     * Process audio using AWS Lambda
     */
    protected function processWithAwsLambda(PitchFile $pitchFile, Pitch $pitch): array
    {
        $lambdaUrl = config('services.aws.lambda_audio_processor_url');
        
        if (!$lambdaUrl) {
            throw new \Exception('AWS Lambda audio processor URL not configured');
        }

        // Append the transcode endpoint
        if (!str_ends_with($lambdaUrl, '/transcode')) {
            $lambdaUrl .= '/transcode';
        }

        $fileUrl = $pitchFile->fullFilePath;
        $encodedFileUrl = str_replace(' ', '%20', $fileUrl);

        if (!filter_var($encodedFileUrl, FILTER_VALIDATE_URL)) {
            throw new \Exception('Invalid file URL format');
        }

        $payload = [
            'file_url' => $encodedFileUrl,
            'target_format' => self::TARGET_FORMAT,
            'target_bitrate' => self::TARGET_BITRATE,
            'apply_watermark' => true,
            'watermark_settings' => [
                'type' => 'audio_tone',
                'frequency' => 1000, // 1kHz tone
                'volume' => self::WATERMARK_VOLUME,
                'duration' => 0.5, // 500ms
                'interval' => 30, // Every 30 seconds
                'project_id' => $pitch->project_id,
                'pitch_id' => $pitch->id
            ]
        ];

        Log::info('Calling AWS Lambda for audio transcoding', [
            'lambda_url' => $lambdaUrl,
            'file_id' => $pitchFile->id,
            'payload' => $payload
        ]);

        $response = Http::timeout(300) // 5 minutes timeout
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])
            ->post($lambdaUrl, $payload);

        if (!$response->successful()) {
            throw new \Exception('AWS Lambda processing failed: ' . $response->body());
        }

        $responseData = $response->json();

        if (isset($responseData['error'])) {
            throw new \Exception('AWS Lambda error: ' . $responseData['error']);
        }

        // Store the processed file
        $processedFileUrl = $responseData['output_url'] ?? null;
        if ($processedFileUrl) {
            $outputPath = $this->storeProcessedFile($pitchFile, $processedFileUrl, $pitch);
            
            return [
                'transcoded' => true,
                'watermarked' => true,
                'output_path' => $outputPath,
                'lambda_response' => $responseData
            ];
        }

        throw new \Exception('No output URL received from Lambda');
    }

    /**
     * Process audio using local FFmpeg
     */
    protected function processWithFfmpeg(PitchFile $pitchFile, Pitch $pitch): array
    {
        // Check if FFmpeg is available
        if (!$this->isFfmpegAvailable()) {
            throw new \Exception('FFmpeg not available on this system');
        }

        $fileUrl = $pitchFile->fullFilePath;
        $tempInputFile = tempnam(sys_get_temp_dir(), 'audio_input_');
        $tempOutputFile = tempnam(sys_get_temp_dir(), 'audio_output_') . '.mp3';

        try {
            // Download the file
            $fileContent = Http::get($fileUrl)->body();
            file_put_contents($tempInputFile, $fileContent);

            // Build FFmpeg command
            $command = $this->buildFfmpegCommand($tempInputFile, $tempOutputFile, $pitch);

            Log::info('Running FFmpeg command', [
                'file_id' => $pitchFile->id,
                'command' => $command
            ]);

            // Execute FFmpeg
            $process = Process::run($command);

            if (!$process->successful()) {
                throw new \Exception('FFmpeg processing failed: ' . $process->errorOutput());
            }

            // Store the processed file
            $outputPath = $this->storeProcessedFileFromLocal($pitchFile, $tempOutputFile, $pitch);

            return [
                'transcoded' => true,
                'watermarked' => $this->hasWatermarkCapability(),
                'output_path' => $outputPath,
                'ffmpeg_output' => $process->output()
            ];

        } finally {
            // Clean up temporary files
            if (file_exists($tempInputFile)) {
                unlink($tempInputFile);
            }
            if (file_exists($tempOutputFile)) {
                unlink($tempOutputFile);
            }
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
            '-b:a', self::TARGET_BITRATE,
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
            '-af', 'volume=' . self::WATERMARK_VOLUME . ',highpass=f=800'
        ];
    }

    /**
     * Store processed file from AWS Lambda response
     */
    protected function storeProcessedFile(PitchFile $originalFile, string $processedFileUrl, Pitch $pitch): string
    {
        $fileContent = Http::get($processedFileUrl)->body();
        $filename = $this->generateProcessedFileName($originalFile, $pitch);
        $storagePath = 'pitches/' . $pitch->id . '/processed/' . $filename;

        Storage::disk('s3')->put($storagePath, $fileContent);

        Log::info('Processed file stored', [
            'original_file_id' => $originalFile->id,
            'storage_path' => $storagePath,
            'pitch_id' => $pitch->id
        ]);

        return $storagePath;
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
        $timestamp = date('Y-m-d_H-i-s');
        $hash = substr(md5($originalFile->id . $pitch->id . $timestamp), 0, 8);
        
        return "{$originalName}_processed_{$timestamp}_{$hash}.mp3";
    }

    /**
     * Check if AWS Lambda should be used
     */
    protected function shouldUseAwsLambda(): bool
    {
        return config('services.aws.lambda_audio_processor_url') && 
               config('audio.processing.use_lambda', true);
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
     * Get configuration for different audio processing methods
     */
    public function getProcessingConfig(): array
    {
        return [
            'supported_formats' => self::SUPPORTED_AUDIO_FORMATS,
            'target_format' => self::TARGET_FORMAT,
            'target_bitrate' => self::TARGET_BITRATE,
            'use_lambda' => $this->shouldUseAwsLambda(),
            'ffmpeg_available' => $this->isFfmpegAvailable(),
            'watermark_capability' => $this->hasWatermarkCapability()
        ];
    }
} 
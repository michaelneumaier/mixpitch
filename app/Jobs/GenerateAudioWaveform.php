<?php

namespace App\Jobs;

use App\Models\PitchFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateAudioWaveform implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The pitch file instance.
     *
     * @var \App\Models\PitchFile
     */
    protected $pitchFile;

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
     * @param \App\Models\PitchFile $pitchFile
     * @return void
     */
    public function __construct(PitchFile $pitchFile)
    {
        $this->pitchFile = $pitchFile;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Check if the file exists
        $filePath = storage_path('app/public/' . $this->pitchFile->file_path);
        
        if (!file_exists($filePath)) {
            Log::error("Failed to generate waveform: File does not exist at {$filePath}");
            return;
        }

        try {
            // Extract duration directly from ffmpeg output
            $durationInfo = null;
            $ffmpegInfo = "ffmpeg -i " . escapeshellarg($filePath) . " 2>&1";
            exec($ffmpegInfo, $output, $returnCode);
            
            // Parse duration from ffmpeg output
            $duration = null;
            foreach ($output as $line) {
                if (preg_match('/Duration: ([0-9]{2}):([0-9]{2}):([0-9]{2}\.[0-9]+)/', $line, $matches)) {
                    $hours = intval($matches[1]);
                    $minutes = intval($matches[2]);
                    $seconds = floatval($matches[3]);
                    $duration = $hours * 3600 + $minutes * 60 + $seconds;
                    Log::info("Extracted duration: {$duration} seconds for file ID: {$this->pitchFile->id}");
                    break;
                }
            }
            
            if ($duration === null) {
                Log::warning("Failed to extract duration from ffmpeg output for file ID: {$this->pitchFile->id}");
            }
            
            // Generate waveform data
            $waveformPeaks = $this->generateWaveformPeaks($filePath);
            
            // Update the pitch file with the generated waveform data and duration
            $this->pitchFile->update([
                'waveform_peaks' => json_encode($waveformPeaks),
                'waveform_processed' => true,
                'waveform_processed_at' => now(),
                'duration' => $duration,
            ]);

            Log::info("Waveform and duration successfully generated for file ID: {$this->pitchFile->id}");
        } catch (\Exception $e) {
            Log::error("Failed to generate waveform for file ID: {$this->pitchFile->id}. Error: {$e->getMessage()}");
            
            // Mark as failed after retries
            if ($this->attempts() >= $this->tries) {
                $this->pitchFile->update([
                    'waveform_processed' => true,
                    'waveform_processed_at' => now(),
                ]);
            }
        }
    }

    /**
     * Generate waveform peaks using FFmpeg.
     *
     * @param string $filePath
     * @return array
     */
    protected function generateWaveformPeaks($filePath)
    {
        // Number of peaks to generate (controls visualization resolution)
        $numPeaks = 200;
        
        // Extract audio samples using FFmpeg
        $tempFile = storage_path('app/temp_' . uniqid() . '.dat');
        
        // FFmpeg command to extract audio samples to a raw data file
        $ffmpegCmd = "ffmpeg -i " . escapeshellarg($filePath) . " -ac 1 -filter:a aresample=8000 -map 0:a -c:a pcm_s16le -f data " . escapeshellarg($tempFile);
        
        exec($ffmpegCmd, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception("FFmpeg command failed with code {$returnCode}");
        }
        
        // Process the raw audio data
        $rawData = file_get_contents($tempFile);
        unlink($tempFile); // Clean up the temporary file
        
        // Convert raw PCM data to peaks
        $samples = [];
        $dataLength = strlen($rawData);
        
        // PCM 16-bit signed little-endian format
        for ($i = 0; $i < $dataLength; $i += 2) {
            if ($i + 1 < $dataLength) {
                // Convert bytes to signed 16-bit integer
                $sample = unpack('s', substr($rawData, $i, 2))[1];
                $samples[] = $sample;
            }
        }
        
        // Calculate peaks by averaging samples
        $peaks = [];
        $samplesPerPeak = floor(count($samples) / $numPeaks);
        
        if ($samplesPerPeak > 0) {
            for ($i = 0; $i < $numPeaks; $i++) {
                $start = $i * $samplesPerPeak;
                $end = min(($i + 1) * $samplesPerPeak, count($samples));
                
                if ($start < $end) {
                    $subset = array_slice($samples, $start, $end - $start);
                    
                    // Find min and max in this segment
                    $min = min($subset);
                    $max = max($subset);
                    
                    // Normalize to range between -1 and 1 (common for waveform representation)
                    $normMin = $min / 32768;
                    $normMax = $max / 32768;
                    
                    // Store min and max as the peak values
                    $peaks[] = [$normMin, $normMax];
                } else {
                    $peaks[] = [0, 0];
                }
            }
        } else {
            // Not enough samples, create empty peaks
            for ($i = 0; $i < $numPeaks; $i++) {
                $peaks[] = [0, 0];
            }
        }
        
        return $peaks;
    }
}

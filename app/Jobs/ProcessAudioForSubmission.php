<?php

namespace App\Jobs;

use App\Models\Pitch;
use App\Models\PitchFile;
use App\Services\AudioProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessAudioForSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pitch;
    protected $audioFiles;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 600; // 10 minutes for audio processing

    /**
     * Create a new job instance.
     */
    public function __construct(Pitch $pitch, array $audioFiles = [])
    {
        $this->pitch = $pitch;
        $this->audioFiles = $audioFiles;
    }

    /**
     * Execute the job.
     */
    public function handle(AudioProcessingService $audioProcessingService)
    {
        // Only process for Standard Workflow projects
        if (!$this->pitch->project->isStandard()) {
            Log::info('Skipping audio processing for non-standard workflow', [
                'pitch_id' => $this->pitch->id,
                'workflow_type' => $this->pitch->project->workflow_type
            ]);
            return;
        }

        // If no specific files provided, process all audio files
        if (empty($this->audioFiles)) {
            $this->audioFiles = $this->pitch->files()
                ->whereRaw("LOWER(file_path) LIKE '%.mp3'")
                ->orWhereRaw("LOWER(file_path) LIKE '%.wav'")
                ->orWhereRaw("LOWER(file_path) LIKE '%.ogg'")
                ->orWhereRaw("LOWER(file_path) LIKE '%.aac'")
                ->orWhereRaw("LOWER(file_path) LIKE '%.m4a'")
                ->orWhereRaw("LOWER(file_path) LIKE '%.flac'")
                ->get()
                ->toArray();
        }

        if (empty($this->audioFiles)) {
            Log::info('No audio files to process', ['pitch_id' => $this->pitch->id]);
            return;
        }

        Log::info('Starting audio processing for submission', [
            'pitch_id' => $this->pitch->id,
            'project_id' => $this->pitch->project_id,
            'file_count' => count($this->audioFiles)
        ]);

        try {
            $results = [];
            
            foreach ($this->audioFiles as $audioFile) {
                $pitchFile = is_array($audioFile) ? PitchFile::find($audioFile['id']) : $audioFile;
                
                if (!$pitchFile) {
                    Log::warning('Audio file not found', ['file_id' => $audioFile['id'] ?? 'unknown']);
                    continue;
                }

                Log::info('Processing audio file', [
                    'file_id' => $pitchFile->id,
                    'file_name' => $pitchFile->file_name,
                    'pitch_id' => $this->pitch->id
                ]);

                try {
                    $result = $audioProcessingService->processAudioFileForSubmission($pitchFile, $this->pitch);
                    $results[] = $result;
                    
                    Log::info('Audio file processed successfully', [
                        'file_id' => $pitchFile->id,
                        'transcoded' => $result['transcoded'],
                        'watermarked' => $result['watermarked'],
                        'output_path' => $result['output_path'] ?? null
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Failed to process audio file', [
                        'file_id' => $pitchFile->id,
                        'pitch_id' => $this->pitch->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Continue processing other files even if one fails
                    continue;
                }
            }

            // Update pitch with processing results
            $this->pitch->update([
                'audio_processed' => true,
                'audio_processed_at' => now(),
                'audio_processing_results' => json_encode($results)
            ]);

            Log::info('Audio processing completed for submission', [
                'pitch_id' => $this->pitch->id,
                'processed_files' => count($results),
                'total_files' => count($this->audioFiles)
            ]);

        } catch (\Exception $e) {
            Log::error('Audio processing failed for submission', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Mark as failed after max attempts
            if ($this->attempts() >= $this->tries) {
                $this->pitch->update([
                    'audio_processed' => false,
                    'audio_processed_at' => now(),
                    'audio_processing_error' => $e->getMessage()
                ]);
            }
            
            throw $e;
        }
    }
} 
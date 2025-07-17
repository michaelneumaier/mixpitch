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

class ProcessAudioForSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The pitch file to process.
     *
     * @var \App\Models\PitchFile
     */
    public $pitchFile;

    /**
     * The maximum number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     *
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
    public function handle(AudioProcessingService $audioProcessingService)
    {
        // Store the file ID before refreshing
        $fileId = $this->pitchFile->id;

        try {
            // Refresh the PitchFile from database to ensure we have current data
            $this->pitchFile = PitchFile::with('pitch.project')->find($fileId);

            // Verify the PitchFile still exists
            if (! $this->pitchFile) {
                Log::error('PitchFile not found in database', [
                    'file_id' => $fileId,
                ]);
                throw new \Exception('PitchFile not found in database');
            }

            // Verify relationships loaded correctly
            if (! $this->pitchFile->pitch) {
                Log::error('Pitch relationship not found for PitchFile', [
                    'file_id' => $this->pitchFile->id,
                    'pitch_id' => $this->pitchFile->pitch_id,
                ]);
                throw new \Exception('Pitch relationship not found for PitchFile '.$this->pitchFile->id);
            }

            // Store pitch reference safely
            $pitch = $this->pitchFile->pitch;

            // Verify project relationship exists
            if (! $pitch->project) {
                Log::error('Project relationship not found for Pitch', [
                    'file_id' => $this->pitchFile->id,
                    'pitch_id' => $this->pitchFile->pitch_id,
                    'project_id' => $pitch->project_id ?? 'null',
                ]);
                throw new \Exception('Project relationship not found for Pitch '.$pitch->id);
            }

            $project = $pitch->project;

            Log::info('Processing audio file for submission', [
                'file_id' => $this->pitchFile->id,
                'file_name' => $this->pitchFile->file_name,
                'pitch_id' => $this->pitchFile->pitch_id,
                'project_id' => $project->id,
            ]);

            // Check if the file should be processed
            if (! $this->pitchFile->shouldBeWatermarked()) {
                Log::info('File does not require watermarking, skipping processing', [
                    'file_id' => $this->pitchFile->id,
                    'project_workflow' => $project->workflow ?? 'unknown',
                ]);

                return;
            }

            // Check if already processed
            if ($this->pitchFile->audio_processed) {
                Log::info('File already processed, skipping', [
                    'file_id' => $this->pitchFile->id,
                    'processed_at' => $this->pitchFile->audio_processed_at,
                ]);

                return;
            }

            // Process the file
            $result = $audioProcessingService->processAudioFileForSubmission($this->pitchFile, $pitch);

            Log::info('Audio processing completed for file', [
                'file_id' => $this->pitchFile->id,
                'success' => ! $result['error'],
                'transcoded' => $result['transcoded'] ?? false,
                'watermarked' => $result['watermarked'] ?? false,
                'processing_time' => $result['processing_time'] ?? 0,
            ]);

            if ($result['error']) {
                Log::error('Audio processing failed for file', [
                    'file_id' => $this->pitchFile->id,
                    'error' => $result['error'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('ProcessAudioForSubmission job encountered an error', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw the exception to trigger the failed() method
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        $fileId = $this->pitchFile->id ?? 'unknown';

        Log::error('ProcessAudioForSubmission job failed', [
            'file_id' => $fileId,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark the file as failed if we can access it
        try {
            if ($this->pitchFile && method_exists($this->pitchFile, 'markAsProcessingFailed')) {
                $this->pitchFile->markAsProcessingFailed('Job failed: '.$exception->getMessage());
            } else {
                // Try to find the file by ID and mark it as failed
                $pitchFile = PitchFile::find($fileId);
                if ($pitchFile) {
                    $pitchFile->markAsProcessingFailed('Job failed: '.$exception->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to mark PitchFile as failed in job failure handler', [
                'file_id' => $fileId,
                'original_error' => $exception->getMessage(),
                'handler_error' => $e->getMessage(),
            ]);
        }
    }
}

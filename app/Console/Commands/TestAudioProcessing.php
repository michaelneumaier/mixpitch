<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PitchFile;
use App\Jobs\ProcessAudioForSubmission;
use App\Services\AudioProcessingService;
use Illuminate\Support\Facades\Log;

class TestAudioProcessing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:audio-processing 
                          {pitch_file_id : The ID of the pitch file to process}
                          {--method=auto : Processing method (auto, lambda, ffmpeg)}
                          {--queue : Whether to use queue or process directly}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test audio processing functionality for ProcessAudioForSubmission job';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pitchFileId = $this->argument('pitch_file_id');
        $method = $this->option('method');
        $useQueue = $this->option('queue');
        
        $this->info("Testing audio processing for PitchFile ID: {$pitchFileId}");
        $this->info("Processing method: {$method}");
        $this->info("Use queue: " . ($useQueue ? 'Yes' : 'No'));
        
        // Find the pitch file
        $pitchFile = PitchFile::with('pitch.project')->find($pitchFileId);
        
        if (!$pitchFile) {
            $this->error("PitchFile with ID {$pitchFileId} not found");
            return 1;
        }
        
        $this->info("Found PitchFile: {$pitchFile->file_name}");
        $this->info("Pitch ID: {$pitchFile->pitch_id}");
        $this->info("Project ID: {$pitchFile->pitch->project_id}");
        $this->info("Current processing status: " . ($pitchFile->audio_processed ? 'Processed' : 'Not processed'));
        
        // Check if file should be watermarked
        if (!$pitchFile->shouldBeWatermarked()) {
            $this->warn("This file does not require watermarking based on project workflow");
            if (!$this->confirm('Continue anyway?')) {
                return 0;
            }
        }
        
        // Reset processing status if needed
        if ($pitchFile->audio_processed && $this->confirm('File already processed. Reset status?')) {
            $pitchFile->update([
                'audio_processed' => false,
                'audio_processed_at' => null,
                'audio_processing_data' => null,
            ]);
            $this->info("Reset processing status");
        }
        
        $this->info("Starting processing...");
        
        if ($useQueue) {
            // Test with queue
            $this->info("Dispatching ProcessAudioForSubmission job to queue...");
            dispatch(new ProcessAudioForSubmission($pitchFile));
            $this->info("Job dispatched successfully. Check logs for processing results.");
        } else {
            // Test directly
            $this->info("Processing directly with AudioProcessingService...");
            
            $audioProcessingService = app(AudioProcessingService::class);
            
            // Override method if specified
            if ($method !== 'auto') {
                $this->info("Note: Method override not implemented in this test. Using auto detection.");
            }
            
            try {
                $result = $audioProcessingService->processAudioFileForSubmission($pitchFile, $pitchFile->pitch);
                
                $this->info("Processing completed!");
                $this->info("Results:");
                $this->line("  - Transcoded: " . ($result['transcoded'] ? 'Yes' : 'No'));
                $this->line("  - Watermarked: " . ($result['watermarked'] ? 'Yes' : 'No'));
                $this->line("  - Processing method: " . ($result['processing_method'] ?? 'Unknown'));
                $this->line("  - Processing time: " . number_format($result['processing_time'] ?? 0, 2) . " seconds");
                $this->line("  - Output path: " . ($result['output_path'] ?? 'None'));
                $this->line("  - Output size: " . ($result['output_size'] ?? 'Unknown'));
                
                if ($result['error']) {
                    $this->error("Processing error: " . $result['error']);
                    return 1;
                }
                
                // Check if file was actually processed
                $pitchFile->refresh();
                if ($pitchFile->audio_processed) {
                    $this->info("✓ PitchFile successfully marked as processed");
                } else {
                    $this->warn("⚠ PitchFile not marked as processed - check logs");
                }
                
            } catch (\Exception $e) {
                $this->error("Processing failed: " . $e->getMessage());
                Log::error('TestAudioProcessing failed', [
                    'pitch_file_id' => $pitchFileId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return 1;
            }
        }
        
        return 0;
    }
}

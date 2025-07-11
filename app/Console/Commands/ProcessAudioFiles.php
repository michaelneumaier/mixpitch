<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Jobs\ProcessAudioForSubmission;
use App\Services\AudioProcessingService;
use Illuminate\Support\Facades\Log;

class ProcessAudioFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audio:process 
                            {--pitch_id= : Specific pitch ID to process}
                            {--all : Process all unprocessed audio files}
                            {--force : Force reprocessing even if already processed}
                            {--workflow= : Only process specific workflow type (standard, contest, direct_hire, client_management)}
                            {--dry-run : Show what would be processed without actually processing}
                            {--sync : Process synchronously instead of queuing jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process audio files for transcoding and watermarking';

    /**
     * Execute the console command.
     */
    public function handle(AudioProcessingService $audioProcessingService)
    {
        $this->info('Starting audio processing...');

        // Get processing configuration
        $config = $audioProcessingService->getProcessingConfig();
        $this->displayConfig($config);

        if ($this->option('pitch_id')) {
            return $this->processSinglePitch($this->option('pitch_id'), $audioProcessingService);
        }

        if ($this->option('all')) {
            return $this->processAllFiles($audioProcessingService);
        }

        $this->error('Please specify either --pitch_id or --all option');
        return 1;
    }

    /**
     * Process a single pitch
     */
    protected function processSinglePitch($pitchId, AudioProcessingService $audioProcessingService)
    {
        $pitch = Pitch::find($pitchId);
        
        if (!$pitch) {
            $this->error("Pitch with ID {$pitchId} not found");
            return 1;
        }

        $this->info("Processing pitch: {$pitch->id} (Project: {$pitch->project->name})");

        // Check workflow type filter
        if ($this->option('workflow') && $pitch->project->workflow_type !== $this->option('workflow')) {
            $this->warn("Skipping pitch {$pitch->id} - workflow type '{$pitch->project->workflow_type}' doesn't match filter");
            return 0;
        }

        // Only process Standard workflows unless force is specified
        if (!$pitch->project->isStandard() && !$this->option('force')) {
            $this->warn("Skipping pitch {$pitch->id} - not a Standard workflow (use --force to override)");
            return 0;
        }

        // Check if already processed
        if ($pitch->audio_processed && !$this->option('force')) {
            $this->warn("Pitch {$pitch->id} already processed. Use --force to reprocess.");
            return 0;
        }

        // Get audio files
        $audioFiles = $this->getAudioFiles($pitch);

        if ($audioFiles->isEmpty()) {
            $this->info("No audio files found for pitch {$pitch->id}");
            return 0;
        }

        $this->info("Found {$audioFiles->count()} audio files:");
        foreach ($audioFiles as $file) {
            $this->line("  - {$file->file_name} ({$file->id})");
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run - no actual processing performed');
            return 0;
        }

        if ($this->option('sync')) {
            return $this->processSynchronously($pitch, $audioFiles, $audioProcessingService);
        } else {
            return $this->processAsynchronously($pitch, $audioFiles);
        }
    }

    /**
     * Process all unprocessed files
     */
    protected function processAllFiles(AudioProcessingService $audioProcessingService)
    {
        $query = Pitch::with(['project', 'files']);

        // Apply workflow filter
        if ($this->option('workflow')) {
            $query->whereHas('project', function ($q) {
                $q->where('workflow_type', $this->option('workflow'));
            });
        } else {
            // Default to Standard workflows only
            $query->whereHas('project', function ($q) {
                $q->where('workflow_type', 'standard');
            });
        }

        // Filter by processing status
        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->where('audio_processed', false)
                  ->orWhereNull('audio_processed');
            });
        }

        $pitches = $query->get();

        if ($pitches->isEmpty()) {
            $this->info('No pitches found to process');
            return 0;
        }

        $this->info("Found {$pitches->count()} pitches to process");

        $processedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        $bar = $this->output->createProgressBar($pitches->count());
        $bar->start();

        foreach ($pitches as $pitch) {
            $audioFiles = $this->getAudioFiles($pitch);

            if ($audioFiles->isEmpty()) {
                $skippedCount++;
                $bar->advance();
                continue;
            }

            if ($this->option('dry-run')) {
                $this->newLine();
                $this->info("Would process pitch {$pitch->id} with {$audioFiles->count()} audio files");
                $skippedCount++;
                $bar->advance();
                continue;
            }

            try {
                if ($this->option('sync')) {
                    $this->processSynchronously($pitch, $audioFiles, $audioProcessingService);
                } else {
                    $this->processAsynchronously($pitch, $audioFiles);
                }
                $processedCount++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error processing pitch {$pitch->id}: {$e->getMessage()}");
                $errorCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Processing complete:");
        $this->info("  Processed: {$processedCount}");
        $this->info("  Skipped: {$skippedCount}");
        $this->info("  Errors: {$errorCount}");

        return $errorCount > 0 ? 1 : 0;
    }

    /**
     * Get audio files for a pitch
     */
    protected function getAudioFiles(Pitch $pitch)
    {
        return $pitch->files()
            ->where(function($query) {
                $query->whereRaw("LOWER(file_path) LIKE '%.mp3'")
                      ->orWhereRaw("LOWER(file_path) LIKE '%.wav'")
                      ->orWhereRaw("LOWER(file_path) LIKE '%.ogg'")
                      ->orWhereRaw("LOWER(file_path) LIKE '%.aac'")
                      ->orWhereRaw("LOWER(file_path) LIKE '%.m4a'")
                      ->orWhereRaw("LOWER(file_path) LIKE '%.flac'");
            })
            ->get();
    }

    /**
     * Process files synchronously
     */
    protected function processSynchronously(Pitch $pitch, $audioFiles, AudioProcessingService $audioProcessingService)
    {
        $this->info("Processing synchronously...");

        try {
            $processedCount = 0;
            $errorCount = 0;
            
            foreach ($audioFiles as $audioFile) {
                $this->info("Processing file: {$audioFile->file_name}");
                
                $result = $audioProcessingService->processAudioFileForSubmission($audioFile, $pitch);
                
                if ($result['error']) {
                    $this->error("Error: {$result['error']}");
                    $errorCount++;
                } else {
                    $this->info("Success - Transcoded: {$result['transcoded']}, Watermarked: {$result['watermarked']}");
                    $processedCount++;
                }
            }

            $this->info("Processing completed for pitch {$pitch->id}:");
            $this->info("  Processed: {$processedCount}");
            $this->info("  Errors: {$errorCount}");
            
            return $errorCount > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error("Processing failed: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Process files asynchronously
     */
    protected function processAsynchronously(Pitch $pitch, $audioFiles)
    {
        $this->info("Dispatching to queue...");

        try {
            foreach ($audioFiles as $audioFile) {
                dispatch(new ProcessAudioForSubmission($audioFile));
            }
            $this->info("Jobs dispatched for {$audioFiles->count()} files from pitch {$pitch->id}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to dispatch jobs: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Display processing configuration
     */
    protected function displayConfig($config)
    {
        $this->info('Audio Processing Configuration:');
        $this->line("  Supported formats: " . implode(', ', $config['supported_formats']));
        $this->line("  Target format: {$config['target_format']}");
        $this->line("  Target bitrate: {$config['target_bitrate']}");
        $this->line("  Use Lambda: " . ($config['use_lambda'] ? 'Yes' : 'No'));
        $this->line("  FFmpeg available: " . ($config['ffmpeg_available'] ? 'Yes' : 'No'));
        $this->line("  Watermark capability: " . ($config['watermark_capability'] ? 'Yes' : 'No'));
        $this->newLine();
    }
}

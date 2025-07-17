<?php

namespace App\Console\Commands;

use App\Jobs\GenerateAudioWaveform;
use App\Models\PitchFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateWaveforms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waveform:generate 
                            {--file_id= : Specific file ID to process}
                            {--all : Process all audio files}
                            {--force : Force regeneration even if already processed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate waveforms for audio files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! $this->option('all') && ! $this->option('file_id')) {
            $this->error('Please specify either --all or --file_id option');

            return 1;
        }

        $force = $this->option('force');

        if ($this->option('all')) {
            $this->processAllFiles($force);
        } else {
            $this->processSingleFile($this->option('file_id'), $force);
        }

        return 0;
    }

    /**
     * Process all audio files
     */
    protected function processAllFiles($force)
    {
        $query = PitchFile::query();

        // Filter to audio files
        $query->where(function ($q) {
            $q->whereRaw("LOWER(file_path) LIKE '%.mp3'")
                ->orWhereRaw("LOWER(file_path) LIKE '%.wav'")
                ->orWhereRaw("LOWER(file_path) LIKE '%.ogg'")
                ->orWhereRaw("LOWER(file_path) LIKE '%.aac'")
                ->orWhereRaw("LOWER(file_path) LIKE '%.m4a'")
                ->orWhereRaw("LOWER(file_path) LIKE '%.flac'");
        });

        // Skip already processed files unless force option is used
        if (! $force) {
            $query->where(function ($q) {
                $q->where('waveform_processed', false)
                    ->orWhereNull('waveform_processed');
            });
        }

        $files = $query->get();
        $count = $files->count();

        if ($count === 0) {
            $this->info('No audio files found to process');

            return;
        }

        $this->info("Processing {$count} audio files...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($files as $file) {
            $this->dispatchJob($file);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('All files have been queued for processing');
    }

    /**
     * Process a single file
     */
    protected function processSingleFile($fileId, $force)
    {
        $file = PitchFile::find($fileId);

        if (! $file) {
            $this->error("File with ID {$fileId} not found");

            return;
        }

        // Check if it's an audio file
        $extension = strtolower(pathinfo($file->file_path, PATHINFO_EXTENSION));
        $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac'];

        if (! in_array($extension, $audioExtensions)) {
            $this->error("File is not an audio file (found extension: {$extension})");

            return;
        }

        // Check if already processed
        if ($file->waveform_processed && ! $force) {
            $this->warn('File already has waveform data. Use --force to regenerate.');

            return;
        }

        $this->info("Processing file ID: {$fileId}");
        $this->dispatchJob($file);
        $this->info('File has been queued for processing');
    }

    /**
     * Dispatch job for waveform generation
     */
    protected function dispatchJob(PitchFile $file)
    {
        try {
            // Reset the processed flag to ensure the job runs
            $file->update([
                'waveform_processed' => false,
                'waveform_processed_at' => null,
            ]);

            // Dispatch the job
            GenerateAudioWaveform::dispatch($file);

        } catch (\Exception $e) {
            $this->error("Error queuing file {$file->id}: {$e->getMessage()}");
            Log::error('Error queuing waveform generation', [
                'file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

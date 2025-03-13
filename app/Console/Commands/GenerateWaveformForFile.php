<?php

namespace App\Console\Commands;

use App\Jobs\GenerateAudioWaveform;
use App\Models\PitchFile;
use Illuminate\Console\Command;

class GenerateWaveformForFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waveform:generate
                            {file_id? : The ID of the PitchFile to generate a waveform for}
                            {--all : Generate waveforms for all audio files}
                            {--force : Force regeneration of waveform even if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate waveform data for pitch audio files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $this->generateForAllAudioFiles();
        } else {
            $fileId = $this->argument('file_id');
            
            if (!$fileId) {
                $fileId = $this->askForFileId();
            }
            
            $this->generateForSingleFile($fileId);
        }
    }
    
    /**
     * Ask user to provide a file ID.
     */
    private function askForFileId()
    {
        return $this->ask('Enter the ID of the PitchFile to generate a waveform for');
    }
    
    /**
     * Generate waveform for a single file.
     */
    private function generateForSingleFile($fileId)
    {
        $pitchFile = PitchFile::find($fileId);
        
        if (!$pitchFile) {
            $this->error("PitchFile with ID {$fileId} not found.");
            return 1;
        }
        
        if ($pitchFile->waveform_processed && !$this->option('force')) {
            if (!$this->confirm("This file already has waveform data. Do you want to regenerate it?")) {
                $this->info("Operation cancelled.");
                return 0;
            }
        }
        
        // Check if this is an audio file
        $extension = strtolower(pathinfo($pitchFile->file_name, PATHINFO_EXTENSION));
        $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac'];
        
        if (!in_array($extension, $audioExtensions)) {
            $this->error("File with ID {$fileId} is not an audio file ({$pitchFile->file_name}).");
            return 1;
        }
        
        $this->info("Generating waveform for file ID {$fileId} ({$pitchFile->file_name})...");
        
        // Process immediately (synchronously)
        $job = new GenerateAudioWaveform($pitchFile);
        $job->handle();
        
        $this->info("Waveform generation completed!");
        return 0;
    }
    
    /**
     * Generate waveforms for all audio files.
     */
    private function generateForAllAudioFiles()
    {
        $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac'];
        
        // Build a query using multiple OR conditions for file extensions instead of REGEXP
        $query = PitchFile::where(function($q) use ($audioExtensions) {
            foreach ($audioExtensions as $ext) {
                $q->orWhere('file_name', 'LIKE', '%.' . $ext);
                // Also check for uppercase extensions
                $q->orWhere('file_name', 'LIKE', '%.' . strtoupper($ext));
            }
        });
        
        if (!$this->option('force')) {
            $query->where(function($q) {
                $q->whereNull('waveform_processed')
                  ->orWhere('waveform_processed', false);
            });
        }
        
        $files = $query->get();
        $total = $files->count();
        
        if ($total === 0) {
            $this->info("No audio files found that need waveform generation.");
            return 0;
        }
        
        $this->info("Found {$total} audio file(s) to process.");
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        foreach ($files as $pitchFile) {
            // Dispatch job to background
            GenerateAudioWaveform::dispatch($pitchFile);
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("All jobs dispatched to the queue for processing!");
        $this->info("Make sure your queue worker is running: php artisan queue:work");
        
        return 0;
    }
} 
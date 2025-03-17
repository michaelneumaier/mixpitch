<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pitch;
use Illuminate\Support\Facades\DB;

class CalculatePitchStorageUsed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pitches:calculate-storage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and update the total storage used for all pitches';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Calculating storage usage for all pitches...');
        
        // Get all pitches
        $pitches = Pitch::all();
        
        $bar = $this->output->createProgressBar(count($pitches));
        $bar->start();
        
        foreach ($pitches as $pitch) {
            // Calculate total storage used
            $totalBytes = DB::table('pitch_files')
                ->where('pitch_id', $pitch->id)
                ->sum('size');
                
            // Update the pitch
            $pitch->total_storage_used = $totalBytes;
            $pitch->save();
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('Storage usage calculation completed!');
    }
}

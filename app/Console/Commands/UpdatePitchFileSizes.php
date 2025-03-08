<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PitchFile;
use Illuminate\Support\Facades\Storage;

class UpdatePitchFileSizes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pitch:update-file-sizes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update size field for existing pitch files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating pitch file sizes...');
        
        $files = PitchFile::whereNull('size')->orWhere('size', 0)->get();
        $count = 0;
        
        foreach ($files as $file) {
            try {
                $size = Storage::disk('public')->size($file->file_path);
                $file->size = $size;
                $file->save();
                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to update size for file ID {$file->id}: {$e->getMessage()}");
            }
        }
        
        $this->info("Updated sizes for {$count} pitch files.");
    }
}

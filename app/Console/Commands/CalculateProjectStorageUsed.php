<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateProjectStorageUsed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:calculate-storage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and update the total storage used for all projects';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Calculating storage usage for all projects...');

        // Get all projects
        $projects = Project::all();

        $bar = $this->output->createProgressBar(count($projects));
        $bar->start();

        foreach ($projects as $project) {
            // Calculate total storage used
            $totalBytes = DB::table('project_files')
                ->where('project_id', $project->id)
                ->sum('size');

            // Update the project
            $project->total_storage_used = $totalBytes;
            $project->save();

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Storage usage calculation completed!');
    }
}

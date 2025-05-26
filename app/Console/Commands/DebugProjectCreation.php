<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Observers\ProjectObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DebugProjectCreation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:project';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug direct hire project creation and observer behavior';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Direct Hire project creation test...');
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Create users for the test
        $this->info('Creating test users...');
        $owner = User::factory()->create();
        $producer = User::factory()->create();
        
        $this->info("Owner created with ID: {$owner->id}");
        $this->info("Producer created with ID: {$producer->id}");
        
        // Create a direct hire project
        $this->info('Creating Direct Hire project...');
        $project = Project::factory()
            ->configureWorkflow(Project::WORKFLOW_TYPE_DIRECT_HIRE, [
                'target_producer_id' => $producer->id
            ])
            ->create([
                'user_id' => $owner->id,
                'name' => 'Debug Direct Hire Project',
                'artist_name' => 'Debug Artist',
                'project_type' => 'single',
                'collaboration_type' => ['Mixing'],
                'budget' => 100,
                'deadline' => now()->addDays(30),
            ]);
            
        $this->info("Project created with ID: {$project->id}");
        
        // Check if the project was created correctly
        $this->info('Checking project creation...');
        $projectCheck = Project::find($project->id);
        
        if (!$projectCheck) {
            $this->error('Project was not found in the database!');
            return 1;
        }
        
        $this->info("Project details:");
        $this->info("- Name: {$projectCheck->name}");
        $this->info("- Workflow Type: {$projectCheck->workflow_type}");
        $this->info("- Target Producer ID: {$projectCheck->target_producer_id}");
        
        if ($projectCheck->workflow_type !== Project::WORKFLOW_TYPE_DIRECT_HIRE) {
            $this->error('Project is not of type Direct Hire!');
        }
        
        if ($projectCheck->target_producer_id != $producer->id) {
            $this->error('Target producer ID does not match!');
        }
        
        // Trigger the observer manually
        $this->info('Manually triggering the ProjectObserver created method...');
        $observer = app(ProjectObserver::class);
        $observer->created($projectCheck);
        
        // Check if a pitch was created
        $this->info('Checking for pitch creation...');
        $pitch = Pitch::where('project_id', $project->id)
                     ->where('user_id', $producer->id)
                     ->first();
                     
        if ($pitch) {
            $this->info("Success! Pitch created with ID: {$pitch->id}");
            $this->info("Pitch status: {$pitch->status}");
            
            // Check for pitch events
            $events = $pitch->events()->get();
            if ($events->count() > 0) {
                $this->info("Pitch events found: {$events->count()}");
                foreach ($events as $event) {
                    $this->info("- Event: {$event->event_type}, Status: {$event->status}");
                }
            } else {
                $this->warn('No pitch events were created!');
            }
            
            // Check for notifications
            $notifications = \App\Models\Notification::where('related_id', $pitch->id)
                                                    ->where('related_type', Pitch::class)
                                                    ->get();
            if ($notifications->count() > 0) {
                $this->info("Notifications found: {$notifications->count()}");
                foreach ($notifications as $notification) {
                    $this->info("- Type: {$notification->type}, User: {$notification->user_id}");
                }
            } else {
                $this->warn('No notifications were created!');
            }
        } else {
            $this->error('No pitch was created!');
            
            // Debug information
            $this->info('Last 5 database queries:');
            $queries = DB::getQueryLog();
            $lastQueries = array_slice($queries, -5);
            foreach ($lastQueries as $index => $query) {
                $this->info("{$index}: " . $query['query']);
            }
            
            // Check database tables
            $this->info('Checking database tables...');
            $pitchesTableExists = Schema::hasTable('pitches');
            $this->info("Pitches table exists: " . ($pitchesTableExists ? 'Yes' : 'No'));
            
            // Try to get the error from logs
            $this->info('Checking logs for errors...');
            // This would normally be more sophisticated, but this is just for debugging
        }
        
        return 0;
    }
}

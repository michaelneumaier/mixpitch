<?php

namespace App\Console\Commands;

use App\Models\Pitch;
use App\Models\PitchEvent;
use App\Models\User;
use Illuminate\Console\Command;

class DiagnoseRatings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnose:ratings {userId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose rating issues for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('userId');

        if (! $userId) {
            // Find all users with completed pitches
            $users = User::whereHas('pitches', function ($query) {
                $query->where('status', Pitch::STATUS_COMPLETED);
            })
                ->get();

            if ($users->isEmpty()) {
                $this->error('No users with completed pitches found.');

                return 1;
            }

            $this->info('Found '.$users->count().' users with completed pitches.');

            foreach ($users as $user) {
                $this->diagnoseSingleUser($user);
            }

            return 0;
        }

        $user = User::find($userId);
        if (! $user) {
            $this->error('User not found.');

            return 1;
        }

        $this->diagnoseSingleUser($user);

        return 0;
    }

    protected function diagnoseSingleUser(User $user)
    {
        $this->info('Diagnosing user: '.$user->name.' (ID: '.$user->id.')');
        $this->info('Role: '.$user->role);

        // Get completed pitches
        $completedPitches = $user->pitches()
            ->where('status', Pitch::STATUS_COMPLETED)
            ->get();

        $this->info('Completed pitches count: '.$completedPitches->count());

        if ($completedPitches->isEmpty()) {
            $this->warn('No completed pitches found for this user.');

            return;
        }

        $pitchIds = $completedPitches->pluck('id')->toArray();
        $this->info('Completed pitch IDs: '.implode(', ', $pitchIds));

        // Get ratings for these pitches
        $ratings = PitchEvent::whereIn('pitch_id', $pitchIds)
            ->where('event_type', 'status_change')
            ->where('status', Pitch::STATUS_COMPLETED)
            ->whereNotNull('rating')
            ->get();

        $this->info('Ratings count: '.$ratings->count());

        if ($ratings->isEmpty()) {
            $this->warn('No ratings found for completed pitches.');

            // Check all completion events
            $completionEvents = PitchEvent::whereIn('pitch_id', $pitchIds)
                ->where('event_type', 'status_change')
                ->where('status', Pitch::STATUS_COMPLETED)
                ->get();

            $this->info('Completion events count: '.$completionEvents->count());

            foreach ($completionEvents as $event) {
                $this->line('Event ID: '.$event->id.
                    ', Pitch ID: '.$event->pitch_id.
                    ', Rating: '.($event->rating ?? 'NULL').
                    ', Created by: '.$event->created_by.
                    ', Created at: '.$event->created_at);
            }

            return;
        }

        // Display ratings
        foreach ($ratings as $rating) {
            $pitch = $completedPitches->firstWhere('id', $rating->pitch_id);
            $projectName = $pitch->project->name ?? 'Unknown Project';

            $this->line('Pitch ID: '.$rating->pitch_id.
                ', Project: '.$projectName.
                ', Rating: '.$rating->rating.
                ', Created by: '.$rating->created_by.
                ', Created at: '.$rating->created_at);
        }

        // Calculate average
        $average = $ratings->avg('rating');
        $count = $ratings->count();

        $this->info('Average rating: '.$average.' (raw)');
        $this->info('Average rating: '.round($average, 1).' (rounded to 1 decimal)');
        $this->info('Average rating: '.number_format($average, 1).' (formatted for display)');
        $this->info('Number of ratings: '.$count);

        // Compare with the function
        $calculated = $user->calculateAverageRating();
        $this->info('Function calculation: '.($calculated['average'] ?? 'NULL').' ('.$calculated['count'].' ratings)');

        $this->newLine();
    }
}

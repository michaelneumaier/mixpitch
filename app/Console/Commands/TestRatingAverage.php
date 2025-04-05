<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Pitch;
use App\Models\PitchEvent;
use Illuminate\Support\Facades\DB;

class TestRatingAverage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:rating-average {userId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the rating average calculation with mixed-value ratings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('userId');
        if (!$userId) {
            // Use the first user that has completed pitches
            $user = User::whereHas('pitches', function($query) {
                $query->where('status', Pitch::STATUS_COMPLETED);
            })->first();
            
            if (!$user) {
                $this->error('No users with completed pitches found.');
                return 1;
            }
            
            $userId = $user->id;
        } else {
            $user = User::find($userId);
            if (!$user) {
                $this->error('User not found.');
                return 1;
            }
        }
        
        $this->info("Testing rating average for user: {$user->name} (ID: {$user->id})");
        
        // Get completed pitches
        $completedPitches = $user->pitches()
            ->where('status', Pitch::STATUS_COMPLETED)
            ->get();
            
        $this->info("Found {$completedPitches->count()} completed pitches");
        
        if ($completedPitches->isEmpty()) {
            $this->error('No completed pitches found.');
            return 1;
        }
        
        // Get one pitch to modify its rating
        $pitch = $completedPitches->first();
        
        // Find the completion event for this pitch
        $completionEvent = PitchEvent::where('pitch_id', $pitch->id)
            ->where('event_type', 'status_change')
            ->where('status', Pitch::STATUS_COMPLETED)
            ->orderBy('created_at', 'desc')
            ->first();
            
        if (!$completionEvent) {
            $this->error('No completion event found for this pitch.');
            return 1;
        }
        
        $this->info("Original rating: " . ($completionEvent->rating ?? 'NULL'));
        
        // Create a mixed set of ratings to ensure we get a decimal average
        $ratings = [];
        
        // Modify the existing completion event
        $completionEvent->rating = 4;
        $completionEvent->save();
        $ratings[] = 4;
        
        // Create additional test events with different ratings
        for ($i = 1; $i <= 4; $i++) {
            // Use a different pitch for each additional event
            $targetPitch = $completedPitches[$i % $completedPitches->count()];
            
            $rating = $i + 1; // Ratings 2-5
            $ratings[] = $rating;
            
            PitchEvent::create([
                'pitch_id' => $targetPitch->id,
                'event_type' => 'status_change',
                'status' => Pitch::STATUS_COMPLETED,
                'comment' => 'Test rating event created for testing decimal averages',
                'created_by' => $user->id,
                'rating' => $rating
            ]);
            
            $this->info("Created test event with rating: $rating");
        }
        
        // Now calculate the average and display it
        $ratingData = $user->calculateAverageRating();
        
        $this->info("Expected average of ratings (" . implode(', ', $ratings) . "): " . 
            number_format(array_sum($ratings) / count($ratings), 1));
            
        $this->info("Calculated average: " . number_format($ratingData['average'], 1));
        $this->info("Rating count: " . $ratingData['count']);
        
        // Check how it looks in the formatter
        $this->info("Formatted for display: " . number_format($ratingData['average'], 1) . " ★");
        
        return 0;
    }
}

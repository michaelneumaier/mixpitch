<?php

namespace App\Console\Commands;

use App\Models\FileComment;
use App\Models\PitchEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixTimezoneData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timezone:fix-data {--dry-run : Run without making changes} {--model= : Fix specific model (PitchEvent, FileComment)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix timestamps that were incorrectly stored in user timezones instead of UTC';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $specificModel = $this->option('model');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('Starting timezone data fix...');
        $this->info('This will convert timestamps stored in user timezones back to UTC.');
        $this->newLine();

        if (! $dryRun && ! $this->confirm('Are you sure you want to proceed?')) {
            $this->info('Operation cancelled.');

            return 0;
        }

        DB::beginTransaction();

        try {
            $models = $specificModel ? [$specificModel] : ['PitchEvent', 'FileComment'];

            foreach ($models as $model) {
                match ($model) {
                    'PitchEvent' => $this->fixPitchEvents($dryRun),
                    'FileComment' => $this->fixFileComments($dryRun),
                    default => $this->error("Unknown model: {$model}"),
                };
            }

            if ($dryRun) {
                DB::rollBack();
                $this->newLine();
                $this->info('✅ Dry run completed - no changes were made');
            } else {
                DB::commit();
                $this->newLine();
                $this->info('✅ Timezone data fixed successfully');
            }

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error fixing timezone data: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Fix PitchEvent timestamps
     */
    protected function fixPitchEvents(bool $dryRun): void
    {
        $this->info('📝 Fixing PitchEvent timestamps...');

        // Get all pitch events created by users with timezones set
        $events = PitchEvent::whereHas('user', function ($query) {
            $query->whereNotNull('timezone');
        })->with('user')->get();

        $fixedCount = 0;
        $bar = $this->output->createProgressBar($events->count());

        foreach ($events as $event) {
            if (! $event->user || ! $event->user->timezone) {
                $bar->advance();

                continue;
            }

            // The timestamp was stored as if it were in the user's timezone
            // but it's actually labeled as UTC. We need to convert it back.
            $userTimezone = $event->user->timezone;
            $storedTime = $event->created_at;

            // Create a Carbon instance treating the stored time as if it's in the user's timezone
            $actualUtc = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $storedTime->format('Y-m-d H:i:s'),
                $userTimezone
            )->setTimezone('UTC');

            // Only update if there's actually a difference
            if ($storedTime->format('Y-m-d H:i:s') !== $actualUtc->format('Y-m-d H:i:s')) {
                if (! $dryRun) {
                    $event->created_at = $actualUtc;
                    $event->updated_at = Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        $event->updated_at->format('Y-m-d H:i:s'),
                        $userTimezone
                    )->setTimezone('UTC');
                    $event->save();
                }
                $fixedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Fixed {$fixedCount} PitchEvent records");
    }

    /**
     * Fix FileComment timestamps
     */
    protected function fixFileComments(bool $dryRun): void
    {
        $this->info('💬 Fixing FileComment timestamps...');

        // Get all file comments created by users with timezones set
        $comments = FileComment::whereHas('user', function ($query) {
            $query->whereNotNull('timezone');
        })->with('user')->get();

        $fixedCount = 0;
        $bar = $this->output->createProgressBar($comments->count());

        foreach ($comments as $comment) {
            if (! $comment->user || ! $comment->user->timezone) {
                $bar->advance();

                continue;
            }

            $userTimezone = $comment->user->timezone;
            $storedTime = $comment->created_at;

            // Create a Carbon instance treating the stored time as if it's in the user's timezone
            $actualUtc = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $storedTime->format('Y-m-d H:i:s'),
                $userTimezone
            )->setTimezone('UTC');

            if ($storedTime->format('Y-m-d H:i:s') !== $actualUtc->format('Y-m-d H:i:s')) {
                if (! $dryRun) {
                    $comment->created_at = $actualUtc;
                    $comment->updated_at = Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        $comment->updated_at->format('Y-m-d H:i:s'),
                        $userTimezone
                    )->setTimezone('UTC');
                    $comment->save();
                }
                $fixedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Fixed {$fixedCount} FileComment records");
    }
}

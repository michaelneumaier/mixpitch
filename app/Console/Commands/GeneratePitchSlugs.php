<?php

namespace App\Console\Commands;

use App\Models\Pitch;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GeneratePitchSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-pitch-slugs {--force : Force regeneration of all slugs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate slugs for pitches that don\'t have one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $forceAll = $this->option('force');

        if ($forceAll) {
            $pitches = Pitch::all();
            $this->info('Regenerating slugs for all '.$pitches->count().' pitches...');
        } else {
            $pitches = Pitch::whereNull('slug')->orWhere('slug', '')->get();
            $this->info('Generating slugs for '.$pitches->count().' pitches without slugs...');
        }

        $bar = $this->output->createProgressBar($pitches->count());
        $bar->start();

        $updated = 0;

        foreach ($pitches as $pitch) {
            $this->generateSlugForPitch($pitch);
            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Successfully updated slugs for '.$updated.' pitches.');
    }

    /**
     * Generate a unique slug for a pitch
     *
     * @return void
     */
    private function generateSlugForPitch(Pitch $pitch)
    {
        // First try to use the title if it exists
        if (! empty($pitch->title)) {
            $baseSlug = Str::slug($pitch->title);
        } else {
            // Otherwise use pitch-{id} as a fallback
            $baseSlug = 'pitch-'.$pitch->id;
        }

        // Find a unique slug within the same project
        $slug = $baseSlug;
        $count = 1;

        while (
            Pitch::where('project_id', $pitch->project_id)
                ->where('id', '!=', $pitch->id)
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$count;
            $count++;
        }

        $pitch->slug = $slug;
        $pitch->save();
    }
}

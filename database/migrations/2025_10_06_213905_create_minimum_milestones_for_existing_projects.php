<?php

use App\Models\Pitch;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration backfills milestones for existing client management projects
     * that have a payment_amount > 0 but no milestones set up.
     */
    public function up(): void
    {
        Log::info('Starting migration to create minimum milestones for existing client management projects');

        // Find all client management projects with pitches that have payment_amount > 0 and no milestones
        $projects = Project::where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->with('pitches')
            ->get();

        $projectsProcessed = 0;
        $milestonesCreated = 0;
        $projectsSkipped = 0;

        foreach ($projects as $project) {
            $pitch = $project->pitches()->first();

            // Skip if no pitch exists
            if (! $pitch) {
                Log::warning('Client management project has no pitch', ['project_id' => $project->id]);
                $projectsSkipped++;

                continue;
            }

            // Skip if payment_amount is 0 or null
            $paymentAmount = $pitch->payment_amount ?? 0;
            if ($paymentAmount <= 0) {
                Log::info('Skipping project - payment amount is $0', [
                    'project_id' => $project->id,
                    'pitch_id' => $pitch->id,
                ]);
                $projectsSkipped++;

                continue;
            }

            // Skip if milestones already exist
            $milestoneCount = $pitch->milestones()->count();
            if ($milestoneCount > 0) {
                Log::info('Skipping project - milestones already exist', [
                    'project_id' => $project->id,
                    'pitch_id' => $pitch->id,
                    'milestone_count' => $milestoneCount,
                ]);
                $projectsSkipped++;

                continue;
            }

            // Create the minimum milestone
            try {
                $milestone = $pitch->milestones()->create([
                    'name' => 'Project Payment',
                    'description' => 'Full payment for project deliverables',
                    'amount' => $paymentAmount,
                    'sort_order' => 1,
                    'status' => 'pending',
                    'payment_status' => null,
                ]);

                Log::info('Created milestone for existing project', [
                    'project_id' => $project->id,
                    'pitch_id' => $pitch->id,
                    'milestone_id' => $milestone->id,
                    'amount' => $paymentAmount,
                ]);

                $projectsProcessed++;
                $milestonesCreated++;
            } catch (\Exception $e) {
                Log::error('Failed to create milestone for project', [
                    'project_id' => $project->id,
                    'pitch_id' => $pitch->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Migration completed', [
            'projects_processed' => $projectsProcessed,
            'milestones_created' => $milestonesCreated,
            'projects_skipped' => $projectsSkipped,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * This migration is not reversible as we cannot safely determine which milestones
     * were created by this migration vs. manually created by producers.
     */
    public function down(): void
    {
        Log::warning('Rollback of minimum milestone migration was attempted - no action taken');
        // We don't delete milestones on rollback as we can't safely identify which ones were auto-created
        // Producers may have already customized or split these milestones
    }
};

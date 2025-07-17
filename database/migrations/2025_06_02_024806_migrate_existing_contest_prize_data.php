<?php

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, clean up any existing test data
        DB::table('contest_prizes')->delete();

        // Migrate existing contest prize data to new structure
        $contestProjects = DB::table('projects')
            ->where('workflow_type', 'contest')
            ->where('prize_amount', '>', 0)
            ->get();

        foreach ($contestProjects as $project) {
            // Check if prize already exists for this project/placement combination
            $existingPrize = DB::table('contest_prizes')
                ->where('project_id', $project->id)
                ->where('placement', '1st')
                ->first();

            if (! $existingPrize) {
                // Create a 1st place cash prize from existing prize_amount
                DB::table('contest_prizes')->insert([
                    'project_id' => $project->id,
                    'placement' => '1st',
                    'prize_type' => 'cash',
                    'cash_amount' => $project->prize_amount,
                    'currency' => $project->prize_currency ?? 'USD',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Update project budgets based on total cash prizes
        // Get all contest projects and update them individually for SQLite compatibility
        $contestProjects = DB::table('projects')
            ->where('workflow_type', 'contest')
            ->get();

        foreach ($contestProjects as $project) {
            $totalCashPrizes = DB::table('contest_prizes')
                ->where('project_id', $project->id)
                ->where('prize_type', 'cash')
                ->sum('cash_amount') ?? 0;

            DB::table('projects')
                ->where('id', $project->id)
                ->update(['budget' => $totalCashPrizes]);
        }

        // Update project deadlines from submission_deadline where deadline is null
        DB::table('projects')
            ->where('workflow_type', 'contest')
            ->whereNull('deadline')
            ->whereNotNull('submission_deadline')
            ->update([
                'deadline' => DB::raw('submission_deadline'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all contest prizes that were created from migration
        // This is a destructive operation, so we'll only remove entries that look like migrated data
        DB::table('contest_prizes')
            ->where('placement', '1st')
            ->where('prize_type', 'cash')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('projects')
                    ->whereRaw('projects.id = contest_prizes.project_id')
                    ->where('workflow_type', 'contest');
            })
            ->delete();

        // Note: We don't reverse the budget/deadline updates as they might have been
        // manually modified after the migration
    }
};

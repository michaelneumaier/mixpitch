<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Pitch;
use App\Services\ContestJudgingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Masmerise\Toaster\Toaster;

class ContestJudgingController extends Controller
{
    protected $contestJudgingService;

    public function __construct(ContestJudgingService $contestJudgingService)
    {
        $this->contestJudgingService = $contestJudgingService;
    }

    /**
     * Display the contest judging interface
     */
    public function index(Project $project)
    {
        // Authorization check
        $this->authorize('judgeContest', $project);

        // Ensure it's a contest project
        if (!$project->isContest()) {
            abort(404, 'Not a contest project');
        }

        // Load contest data
        $contestEntries = $project->getContestEntries();
        $contestResult = $project->contestResult;
        $isFinalized = $project->isJudgingFinalized();
        $canFinalize = $project->canFinalizeJudging();

        return view('contest.judging.index', compact(
            'project',
            'contestEntries',
            'contestResult',
            'isFinalized',
            'canFinalize'
        ));
    }

    /**
     * Display contest results (public if allowed)
     */
    public function results(Project $project)
    {
        // Authorization check
        $this->authorize('viewContestResults', $project);

        // Ensure it's a contest project
        if (!$project->isContest()) {
            abort(404, 'Not a contest project');
        }

        // Check if results are available
        if (!$project->isJudgingFinalized()) {
            if (Auth::check() && Auth::user()->can('judgeContest', $project)) {
                return redirect()->route('projects.contest.judging', $project)
                    ->with('info', 'Contest judging is not yet finalized.');
            }
            
            abort(404, 'Contest results not yet available');
        }

        // Load results data
        $contestResult = $project->contestResult;
        $contestEntries = $project->getContestEntries();

        return view('contest.results.index', compact(
            'project',
            'contestResult',
            'contestEntries'
        ));
    }

    /**
     * Update a contest entry placement
     */
    public function updatePlacement(Request $request, Project $project, Pitch $pitch)
    {
        // Authorization check
        $this->authorize('setContestPlacement', $pitch);

        // Validate request
        $request->validate([
            'placement' => 'nullable|string|in:1st,2nd,3rd,runner-up'
        ]);

        try {
            $result = $this->contestJudgingService->setPlacement(
                $project,
                $pitch,
                $request->input('placement')
            );

            if ($result) {
                $placementLabel = $request->input('placement') ? match($request->input('placement')) {
                    '1st' => '1st Place',
                    '2nd' => '2nd Place',
                    '3rd' => '3rd Place',
                    'runner-up' => 'Runner-up',
                    default => $request->input('placement')
                } : 'No Placement';

                return response()->json([
                    'success' => true,
                    'message' => "Placement updated to: {$placementLabel}"
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update placement'
            ], 422);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update placement. Please try again.'
            ], 500);
        }
    }

    /**
     * Finalize contest judging
     */
    public function finalize(Request $request, Project $project)
    {
        // Authorization check
        $this->authorize('finalizeContestJudging', $project);

        // Validate request
        $request->validate([
            'judging_notes' => 'nullable|string|max:1000'
        ]);

        try {
            $result = $this->contestJudgingService->finalizeJudging(
                $project,
                Auth::user(),
                $request->input('judging_notes')
            );

            if ($result) {
                Toaster::success('Contest judging has been finalized! All participants have been notified.');
                
                return redirect()->route('projects.contest.results', $project);
            }

            Toaster::error('Failed to finalize contest judging.');
            return back();

        } catch (\InvalidArgumentException $e) {
            Toaster::error($e->getMessage());
            return back();
        } catch (\Exception $e) {
            Toaster::error('Failed to finalize judging. Please try again.');
            return back();
        }
    }

    /**
     * Reopen contest judging (admin only)
     */
    public function reopen(Project $project)
    {
        // Authorization check
        $this->authorize('reopenContestJudging', $project);

        try {
            $result = $this->contestJudgingService->reopenJudging($project, Auth::user());

            if ($result) {
                Toaster::success('Contest judging has been reopened.');
                return redirect()->route('projects.contest.judging', $project);
            }

            Toaster::error('Failed to reopen contest judging.');
            return back();

        } catch (\InvalidArgumentException $e) {
            Toaster::error($e->getMessage());
            return back();
        } catch (\Exception $e) {
            Toaster::error('Failed to reopen judging. Please try again.');
            return back();
        }
    }

    /**
     * Display contest analytics
     */
    public function analytics(Project $project)
    {
        // Authorization check
        $this->authorize('judgeContest', $project);

        if (!$project->isContest()) {
            abort(404, 'Not a contest project');
        }

        // Gather analytics data
        $contestEntries = $project->getContestEntries();
        $contestResult = $project->contestResult;
        
        $analytics = [
            'total_entries' => $contestEntries->count(),
            'placed_entries' => $contestResult ? $contestResult->getPlacedCount() : 0,
            'unplaced_entries' => $contestEntries->count() - ($contestResult ? $contestResult->getPlacedCount() : 0),
            'entries_by_date' => $contestEntries->groupBy(function($entry) {
                return $entry->created_at->format('Y-m-d');
            })->map->count(),
            'contest_duration' => $project->created_at->diffInDays($project->submission_deadline),
            'judging_duration' => $project->submission_deadline->diffInDays($project->judging_finalized_at ?? now()),
            'is_finalized' => $project->isJudgingFinalized(),
            'finalized_at' => $project->judging_finalized_at,
        ];

        return view('contest.analytics.index', compact(
            'project',
            'contestEntries',
            'contestResult',
            'analytics'
        ));
    }

    /**
     * Export contest results
     */
    public function export(Project $project)
    {
        // Authorization check
        $this->authorize('export', $project->contestResult);

        if (!$project->isContest() || !$project->isJudgingFinalized()) {
            abort(404, 'Contest results not available for export');
        }

        // Generate export data
        $contestEntries = $project->getContestEntries();
        $contestResult = $project->contestResult;

        // Create CSV data
        $csvData = [];
        $csvData[] = ['Entry ID', 'Producer Name', 'Producer Email', 'Submission Date', 'Placement', 'Prize'];

        foreach ($contestEntries as $entry) {
            $placement = $contestResult ? $contestResult->hasPlacement($entry->id) : null;
            $prize = $placement ? $this->getPrizeForPlacement($project, $placement) : 'None';
            
            $csvData[] = [
                $entry->id,
                $entry->user->name,
                $entry->user->email,
                $entry->created_at->format('Y-m-d H:i:s'),
                $placement ?: 'Not Placed',
                $prize
            ];
        }

        // Generate filename
        $filename = 'contest-results-' . $project->slug . '-' . now()->format('Y-m-d') . '.csv';

        // Create CSV response
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Announce contest results formally (to reach 100% completion)
     */
    public function announceResults(Project $project)
    {
        // Authorization check
        $this->authorize('judgeContest', $project);

        // Validate this is a finalized contest
        if (!$project->isContest() || !$project->isJudgingFinalized()) {
            return response()->json([
                'success' => false,
                'message' => 'Contest must be finalized before announcing results'
            ], 422);
        }

        try {
            $result = $this->contestJudgingService->announceResults($project, Auth::user());

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contest results have been formally announced!'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to announce contest results'
            ], 422);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to announce results. Please try again.'
            ], 500);
        }
    }

    /**
     * Helper method to get prize information for a placement
     */
    private function getPrizeForPlacement(Project $project, string $placement): string
    {
        if (!$project->hasPrizes()) {
            return 'No Prize';
        }

        $prizes = $project->contestPrizes();
        
        $placementMap = [
            '1st' => '1st',
            '2nd' => '2nd', 
            '3rd' => '3rd',
            'runner-up' => 'runner_up'
        ];

        $placementKey = $placementMap[$placement] ?? null;
        if (!$placementKey) {
            return 'No Prize';
        }

        $prize = $prizes->where('placement', $placementKey)->first();
        if (!$prize) {
            return 'No Prize';
        }

        return $prize->getDisplayValue();
    }
} 
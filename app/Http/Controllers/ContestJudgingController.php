<?php

namespace App\Http\Controllers;

use App\Models\Pitch;
use App\Models\Project;
use App\Services\ContestEarlyClosureService;
use App\Services\ContestJudgingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Masmerise\Toaster\Toaster;

class ContestJudgingController extends Controller
{
    protected $contestJudgingService;

    protected $contestEarlyClosureService;

    public function __construct(
        ContestJudgingService $contestJudgingService,
        ContestEarlyClosureService $contestEarlyClosureService
    ) {
        $this->contestJudgingService = $contestJudgingService;
        $this->contestEarlyClosureService = $contestEarlyClosureService;
    }

    /**
     * Display the contest judging interface
     */
    public function index(Project $project)
    {
        // Authorization check
        $this->authorize('judgeContest', $project);

        // Ensure it's a contest project
        if (! $project->isContest()) {
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
        if (! $project->isContest()) {
            abort(404, 'Not a contest project');
        }

        // Check if results are available
        if (! $project->isJudgingFinalized()) {
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
        try {
            // Log the incoming request for debugging
            \Log::info('Contest placement update request', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'user_id' => Auth::id(),
                'placement' => $request->input('placement'),
                'request_data' => $request->all(),
            ]);

            // Authorization check
            $this->authorize('setContestPlacement', $pitch);

            // Validate request
            $validated = $request->validate([
                'placement' => 'nullable|string|in:1st,2nd,3rd,runner-up',
            ]);

            // Additional business logic validation
            if (! $project->isContest()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This project is not a contest.',
                ], 422);
            }

            if ($project->isJudgingFinalized()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contest judging has been finalized and cannot be modified.',
                ], 422);
            }

            // Check if pitch is eligible for placement
            $eligibleStatuses = [
                \App\Models\Pitch::STATUS_CONTEST_ENTRY,
                \App\Models\Pitch::STATUS_CONTEST_WINNER,
                \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP,
            ];

            if (! in_array($pitch->status, $eligibleStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This entry is not eligible for placement.',
                ], 422);
            }

            $result = $this->contestJudgingService->setPlacement(
                $project,
                $pitch,
                $validated['placement'] ?: null
            );

            if ($result) {
                \Log::info('Contest placement updated successfully', [
                    'project_id' => $project->id,
                    'pitch_id' => $pitch->id,
                    'placement' => $validated['placement'],
                    'user_id' => Auth::id(),
                ]);

                // Get updated available placements for all pitches
                $judgingService = app(ContestJudgingService::class);
                $availablePlacements = [];

                // Get all contest entries to update their available placements
                $contestEntries = $project->getContestEntries();
                foreach ($contestEntries as $entry) {
                    $entryPlacements = $judgingService->getAvailablePlacementsForPitch($project, $entry);
                    // We only need the placement options for the frontend
                    foreach ($entryPlacements as $value => $label) {
                        if (! isset($availablePlacements[$value])) {
                            $availablePlacements[$value] = $label;
                        }
                    }
                }

                // Get current winners for updating the Current Winners section
                $contestResult = $project->contestResult;
                $currentWinners = [
                    'first_place' => null,
                    'second_place' => null,
                    'third_place' => null,
                    'runner_ups' => [],
                ];

                if ($contestResult) {
                    if ($contestResult->first_place_pitch_id) {
                        $firstPlace = $contestEntries->firstWhere('id', $contestResult->first_place_pitch_id);
                        if ($firstPlace) {
                            $currentWinners['first_place'] = [
                                'id' => $firstPlace->id,
                                'slug' => $firstPlace->slug,
                                'user_name' => $firstPlace->user->name,
                                'title' => $firstPlace->title,
                            ];
                        }
                    }

                    if ($contestResult->second_place_pitch_id) {
                        $secondPlace = $contestEntries->firstWhere('id', $contestResult->second_place_pitch_id);
                        if ($secondPlace) {
                            $currentWinners['second_place'] = [
                                'id' => $secondPlace->id,
                                'slug' => $secondPlace->slug,
                                'user_name' => $secondPlace->user->name,
                                'title' => $secondPlace->title,
                            ];
                        }
                    }

                    if ($contestResult->third_place_pitch_id) {
                        $thirdPlace = $contestEntries->firstWhere('id', $contestResult->third_place_pitch_id);
                        if ($thirdPlace) {
                            $currentWinners['third_place'] = [
                                'id' => $thirdPlace->id,
                                'slug' => $thirdPlace->slug,
                                'user_name' => $thirdPlace->user->name,
                                'title' => $thirdPlace->title,
                            ];
                        }
                    }

                    if ($contestResult->runner_up_pitch_ids) {
                        foreach ($contestResult->runner_up_pitch_ids as $runnerUpId) {
                            $runnerUp = $contestEntries->firstWhere('id', $runnerUpId);
                            if ($runnerUp) {
                                $currentWinners['runner_ups'][] = [
                                    'id' => $runnerUp->id,
                                    'slug' => $runnerUp->slug,
                                    'user_name' => $runnerUp->user->name,
                                    'title' => $runnerUp->title,
                                ];
                            }
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => $validated['placement'] ?
                        "Placement updated to: {$this->getPlacementLabel($validated['placement'])}" :
                        'Placement cleared successfully',
                    'placement' => $validated['placement'],
                    'placement_label' => $validated['placement'] ? $this->getPlacementLabel($validated['placement']) : null,
                    'availablePlacements' => $availablePlacements,
                    'currentWinners' => $currentWinners,
                    'pitch_id' => $pitch->id,
                    'pitch_slug' => $pitch->slug,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update placement. Please try again.',
            ], 422);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            \Log::warning('Contest placement authorization failed', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to judge this contest entry.',
            ], 403);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Contest placement validation failed', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid placement value provided.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\InvalidArgumentException $e) {
            \Log::error('Contest placement business logic error', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Contest placement unexpected error', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.',
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
            'judging_notes' => 'nullable|string|max:1000',
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

        if (! $project->isContest()) {
            abort(404, 'Not a contest project');
        }

        // Gather analytics data
        $contestEntries = $project->getContestEntries();
        $contestResult = $project->contestResult;

        $analytics = [
            'total_entries' => $contestEntries->count(),
            'placed_entries' => $contestResult ? $contestResult->getPlacedCount() : 0,
            'unplaced_entries' => $contestEntries->count() - ($contestResult ? $contestResult->getPlacedCount() : 0),
            'entries_by_date' => $contestEntries->groupBy(function ($entry) {
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

        if (! $project->isContest() || ! $project->isJudgingFinalized()) {
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
                $prize,
            ];
        }

        // Generate filename
        $filename = 'contest-results-'.$project->slug.'-'.now()->format('Y-m-d').'.csv';

        // Create CSV response
        $callback = function () use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
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
        if (! $project->isContest() || ! $project->isJudgingFinalized()) {
            return response()->json([
                'success' => false,
                'message' => 'Contest must be finalized before announcing results',
            ], 422);
        }

        try {
            $result = $this->contestJudgingService->announceResults($project, Auth::user());

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contest results have been formally announced!',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to announce contest results',
            ], 422);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to announce results. Please try again.',
            ], 500);
        }
    }

    /**
     * Helper method to get prize information for a placement
     */
    private function getPrizeForPlacement(Project $project, string $placement): string
    {
        if (! $project->hasPrizes()) {
            return 'No Prize';
        }

        $prizes = $project->contestPrizes();

        $placementMap = [
            '1st' => '1st',
            '2nd' => '2nd',
            '3rd' => '3rd',
            'runner-up' => 'runner_up',
        ];

        $placementKey = $placementMap[$placement] ?? null;
        if (! $placementKey) {
            return 'No Prize';
        }

        $prize = $prizes->where('placement', $placementKey)->first();
        if (! $prize) {
            return 'No Prize';
        }

        return $prize->getDisplayValue();
    }

    private function getPlacementLabel(string $placement): string
    {
        $placementMap = [
            '1st' => '1st Place',
            '2nd' => '2nd Place',
            '3rd' => '3rd Place',
            'runner-up' => 'Runner-up',
        ];

        return $placementMap[$placement] ?? $placement;
    }

    /**
     * Close contest submissions early
     */
    public function closeEarly(Request $request, Project $project)
    {
        // Authorization check
        $this->authorize('closeContestEarly', $project);

        // Validate request
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->contestEarlyClosureService->closeContestEarly(
                $project,
                Auth::user(),
                $request->input('reason')
            );

            if ($result) {
                Toaster::success('Contest submissions have been closed early. All participants have been notified.');

                return redirect()->route('projects.contest.judging', $project);
            }

            Toaster::error('Failed to close contest early.');

            return back();

        } catch (\InvalidArgumentException $e) {
            Toaster::error($e->getMessage());

            return back();
        } catch (\Exception $e) {
            Toaster::error('Failed to close contest early. Please try again.');

            return back();
        }
    }

    /**
     * Reopen contest submissions (undo early closure)
     */
    public function reopenSubmissions(Project $project)
    {
        // Authorization check
        $this->authorize('reopenContestSubmissions', $project);

        try {
            $result = $this->contestEarlyClosureService->reopenContestSubmissions(
                $project,
                Auth::user()
            );

            if ($result) {
                Toaster::success('Contest submissions have been reopened. Participants have been notified.');

                return redirect()->route('projects.manage', $project);
            }

            Toaster::error('Failed to reopen contest submissions.');

            return back();

        } catch (\InvalidArgumentException $e) {
            Toaster::error($e->getMessage());

            return back();
        } catch (\Exception $e) {
            Toaster::error('Failed to reopen submissions. Please try again.');

            return back();
        }
    }
}

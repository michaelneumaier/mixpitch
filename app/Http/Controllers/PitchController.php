<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;
use App\Exceptions\Pitch\PitchCreationException;
use App\Exceptions\Pitch\PitchUpdateException;
use App\Exceptions\Pitch\InvalidStatusTransitionException;
use App\Helpers\RouteHelpers;
use Illuminate\Support\Str;

use App\Models\Project;
use App\Models\Pitch;
use App\Models\PitchSnapshot;
use App\Services\PitchWorkflowService;
use App\Http\Requests\Pitch\StorePitchRequest;
use App\Models\PitchEvent;
use Masmerise\Toaster\Toaster;

class PitchController extends Controller
{
    protected $pitchWorkflowService;

    public function __construct(PitchWorkflowService $pitchWorkflowService)
    {
        $this->pitchWorkflowService = $pitchWorkflowService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Project $project)
    {
        try {
            $this->authorize('createPitch', $project);
        } catch (AuthorizationException $e) {
            return redirect()->route('projects.show', $project)
                ->with('error', 'You cannot submit a pitch for this project. ' . $e->getMessage());
        }

        $user = Auth::user();
        $userPitch = $project->userPitch($user->id);

        if ($userPitch) {
            // If user already has a pitch, redirect them to it instead of creating a new one
            // Use RouteHelpers for URL generation
            return redirect(RouteHelpers::pitchUrl($userPitch))
                ->with('info', 'You have already submitted a pitch for this project.');
        }

        return view('pitches.create', compact('project'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePitchRequest $request, Project $project)
    {
        $user = Auth::user();

        try {
            $this->authorize('createPitch', $project);

            $pitch = $this->pitchWorkflowService->createPitch(
                $project,
                $user,
                $request->validated()
            );

            // Use RouteHelpers for URL generation
            return redirect(RouteHelpers::pitchUrl($pitch)) // Use $project
                ->with('success', 'Pitch created successfully!');
        } catch (AuthorizationException $e) {
            return redirect()->route('projects.show', $project)
                ->with('error', 'You cannot submit a pitch for this project: ' . $e->getMessage());
        } catch (PitchCreationException $e) {
            Log::error('Pitch creation failed: ' . $e->getMessage(), ['project_id' => $project->id, 'user_id' => $user->id]);
            return redirect()->route('projects.show', $project)
                ->with('error', 'Failed to create pitch: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Unexpected error creating pitch: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString() // Consider limiting trace in production
            ]);
            return redirect()->route('projects.show', $project)
                ->with('error', 'An unexpected error occurred while creating the pitch.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pitch = Pitch::find($id);

        if (!$pitch) {
            abort(404, 'Pitch not found');
        }

        return redirect()->route('projects.pitches.show', [
            'project' => $pitch->project,
            'pitch' => $pitch
        ]);
    }

    /**
     * Display the pitch with the new URL pattern
     */
    public function showProjectPitch(Project $project, Pitch $pitch)
    {
        if ($pitch->project_id !== $project->id) {
            abort(404, 'Pitch not found for this project');
        }
        
        try {
            $this->authorize('view', $pitch);
            // Eager load necessary relationships
            $pitch->load(['user', 'project.user', 'events', 'snapshots']);
        } catch (AuthorizationException $e) {
            abort(403, 'You are not authorized to view this pitch.');
        }

        $user = auth()->user();

        // Only redirect project owners for standard workflow types
        // Allow direct access for client management and direct hire workflows
        if ($user && $user->id === $project->user_id && 
            !$project->isClientManagement() && !$project->isDirectHire()) {
            
            // Check if pitch has snapshots - if so, redirect to latest snapshot
            $latestSnapshot = $pitch->snapshots()->orderBy('created_at', 'desc')->first();
            
            if ($latestSnapshot) {
                return redirect()->route('projects.pitches.snapshots.show', [
                    'project' => $project->slug, 
                    'pitch' => $pitch->slug, 
                    'snapshot' => $latestSnapshot->id
                ])->with('info', 'Redirected to the latest snapshot for review.');
            }
            
            // No snapshots - redirect to manage page (original behavior)
            return redirect()->route('projects.manage', $project)
                ->with('info', 'Project owners should manage pitches from the project management page.');
        }

        return view('pitches.show', compact('pitch'));
    }

    public function showSnapshot(Pitch $pitch, PitchSnapshot $pitchSnapshot)
    {
        if ($pitchSnapshot->pitch_id !== $pitch->id) {
            abort(404);
        }

        try {
            $this->authorize('view', $pitch);
            // Eager load necessary relationships
            $pitch->load(['user', 'project.user', 'events', 'snapshots']);
        } catch (AuthorizationException $e) {
            abort(403, 'You are not authorized to view this pitch.');
        }

        $user = auth()->user();

        // Only redirect project owners for standard workflow types
        // Allow direct access for client management and direct hire workflows
        if ($user && $user->id === $pitch->project->user_id && 
            !$pitch->project->isClientManagement() && !$pitch->project->isDirectHire()) {
            
            // Check if this is not already the latest snapshot
            $latestSnapshot = $pitch->snapshots()->orderBy('created_at', 'desc')->first();
            
            if ($latestSnapshot && $latestSnapshot->id !== $pitchSnapshot->id) {
                return redirect()->route('projects.pitches.snapshots.show', [
                    'project' => $pitch->project->slug, 
                    'pitch' => $pitch->slug, 
                    'snapshot' => $latestSnapshot->id
                ])->with('info', 'Redirected to the latest snapshot for review.');
            }
            
            // If this is the latest snapshot or no snapshots exist, allow access
            // This allows project owners to view the current latest snapshot directly
        }

        $snapshotData = $pitchSnapshot->snapshot_data;

        return view('pitches.show-snapshot', compact('pitch', 'pitchSnapshot', 'snapshotData'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pitch $pitch)
    {
        return redirect()->route('projects.pitches.edit', [
            'project' => $pitch->project,
            'pitch' => $pitch
        ]);
    }

    /**
     * Edit the pitch with the new URL pattern
     */
    public function editProjectPitch(Project $project, Pitch $pitch)
    {
        if ($pitch->project_id !== $project->id) {
            abort(404, 'Pitch not found for this project');
        }
        
        try {
            $this->authorize('update', $pitch);
        } catch (AuthorizationException $e) {
            abort(403, 'You are not authorized to edit this pitch.');
        }

        // For contest entries, use the dedicated contest management view
        if ($project->isContest() && $pitch->status === Pitch::STATUS_CONTEST_ENTRY) {
            return view('pitches.edit-contest-livewire', compact('pitch'));
        }

        // Define $currentSnapshot as null so it's always available in the view
        $currentSnapshot = null; 
        // TODO: Optionally, fetch the relevant snapshot if needed, e.g.:
        // $currentSnapshot = $pitch->snapshots()->latest()->first(); 
        // Or perhaps the specific one linked to the last 'revisions_requested' event.

        // Pass both $pitch and $currentSnapshot to the view
        // return view('pitches.edit', compact('pitch', 'currentSnapshot')); // Old view
        
        // Return a view that loads the ManagePitch Livewire component
        return view('pitches.edit-livewire', compact('pitch'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project, Pitch $pitch)
    {
        $user = Auth::user();

        try {
            // Ensure the pitch belongs to the project specified in the route
            if ($pitch->project_id !== $project->id) {
                abort(404, 'Pitch not found for this project');
            }
            
            $this->authorize('update', $pitch);

            // TODO: Add validation logic here if needed, 
            //       as UpdatePitchRequest was removed.
            // Example: $validatedData = $request->validate([...rules...]);
            $validatedData = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                // Add other fields that can be updated here
            ]);

            // Move update logic from PitchService here
            $pitch->fill($validatedData); // Use validated data
            $pitch->save();

            // Use RouteHelpers for URL generation
            return redirect(RouteHelpers::pitchUrl($pitch))
                ->with('success', 'Pitch updated successfully!');
        } catch (AuthorizationException $e) {
            return redirect()->back()
                ->with('error', 'You are not authorized to update this pitch: ' . $e->getMessage());
        } catch (PitchUpdateException $e) {
            Log::error('Pitch update failed: ' . $e->getMessage(), ['pitch_id' => $pitch->id, 'user_id' => $user->id]);
            return redirect()->back()
                ->with('error', 'Failed to update pitch: ' . $e->getMessage());
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Unexpected error updating pitch: ' . $e->getMessage(), [
                'pitch_id' => $pitch->id,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'An unexpected error occurred while updating the pitch.');
        }
    }

    /**
     * Show the latest snapshot for a pitch
     */
    public function showLatestSnapshot(Pitch $pitch)
    {
        $latestSnapshot = $pitch->snapshots()->orderBy('created_at', 'desc')->first();

        if ($latestSnapshot) {
            return redirect()->route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $latestSnapshot->id]);
        }

        return redirect()->back()->with('error', 'No snapshots available to review.');
    }

    /**
     * Process the confirmed deletion from the Livewire component
     */
    public function destroyConfirmed(Project $project, Pitch $pitch)
    {
        if ($pitch->project_id !== $project->id) {
            abort(404, 'Pitch not found for this project');
        }

        $user = Auth::user();

        try {
            $this->authorize('delete', $pitch);
            
            // Get FileManagementService
            $fileManagementService = app(\App\Services\FileManagementService::class);
            
            Log::info('Starting pitch deletion process', [
                'pitch_id' => $pitch->id,
                'user_id' => $user->id,
                'files_count' => $pitch->files()->count(),
                'snapshots_count' => $pitch->snapshots()->count(),
                'events_count' => $pitch->events()->count(),
            ]);
            
            DB::transaction(function () use ($pitch, $fileManagementService) {
                // Step 1: Delete all pitch files (includes S3 cleanup)
                $pitch->files()->each(function ($pitchFile) use ($fileManagementService) {
                    try {
                        $fileManagementService->deletePitchFile($pitchFile);
                        Log::info('Pitch file deleted during pitch cleanup', [
                            'file_id' => $pitchFile->id,
                            'file_name' => $pitchFile->file_name,
                            'pitch_id' => $pitchFile->pitch_id
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to delete pitch file during pitch deletion', [
                            'file_id' => $pitchFile->id,
                            'file_name' => $pitchFile->file_name,
                            'pitch_id' => $pitchFile->pitch_id,
                            'error' => $e->getMessage()
                        ]);
                        // Continue with other files - don't fail entire deletion
                    }
                });

                // Step 2: Soft delete the pitch (cascade will handle related records)
                $pitch->delete();
                
                Log::info('Pitch deletion completed successfully', [
                    'pitch_id' => $pitch->id
                ]);
            });

            // Redirect to project page after delete
            return redirect()->route('projects.show', $project)
                ->with('success', 'Pitch deleted successfully.');
        } catch (AuthorizationException $e) {
            return redirect()->back()
                ->with('error', 'You are not authorized to delete this pitch: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error deleting pitch: ' . $e->getMessage(), [
                'pitch_id' => $pitch->id,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'An unexpected error occurred while deleting the pitch.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // This seems like a generic destroy route not specific to project/pitch context, might be unused or needs refactoring
        // For safety, just redirect to dashboard
        return redirect()->route('dashboard');
    }

    /**
     * Display the payment details for a pitch
     * Redirects to the new project-based payment overview route.
     */
    public function showProjectPitchPayment(Project $project, Pitch $pitch)
    {
        if ($pitch->project_id !== $project->id) {
            abort(404, 'Pitch not found for this project');
        }
        
        // Use RouteHelpers for URL generation
        return redirect(RouteHelpers::pitchPaymentUrl($pitch));
    }

    /**
     * Original payment method redirects to new URL
     */
    public function showPayment(Pitch $pitch)
    {
        // Use RouteHelpers for URL generation
        return redirect(RouteHelpers::pitchPaymentUrl($pitch));
    }

    /**
     * Generate and set a unique slug for a pitch
     * 
     * @param Pitch $pitch The pitch model to set a slug for
     * @return void
     */
    private function generateAndSetSlug(Pitch $pitch)
    {
        // Sluggable trait might handle this automatically, but ensure uniqueness if needed
        $slug = Str::slug($pitch->title); // Assuming title exists
        $count = Pitch::where('project_id', $pitch->project_id)->where('slug', 'LIKE', $slug . '%')->count();
        $pitch->slug = $count > 0 ? "{$slug}-{$count}" : $slug;
        $pitch->save(); // Save immediately after setting slug
    }

    /**
     * Update the status of a pitch.
     * This handles the POST requests from the Blade component buttons.
     */
    public function changeStatus(Request $request, Project $project, Pitch $pitch)
    {
        $direction = $request->input('direction');
        $newStatus = $request->input('newStatus');
        $user = Auth::user();

        // Validate input
        if (!in_array($direction, ['forward', 'backward']) || !in_array($newStatus, Pitch::getStatuses())) {
            return redirect()->back()->with('error', 'Invalid status change request.');
        }

        try {
            // Special handling for returning to review
            if ($direction === 'backward' && $newStatus === Pitch::STATUS_READY_FOR_REVIEW) {
                // Ensure pitch is in a revertible status
                $revertibleStatuses = [
                    Pitch::STATUS_APPROVED,
                    Pitch::STATUS_REVISIONS_REQUESTED,
                    Pitch::STATUS_DENIED,
                ];
                if (!in_array($pitch->status, $revertibleStatuses)) {
                    throw new InvalidStatusTransitionException(
                        $pitch->status,
                        $newStatus,
                        'Pitch cannot be returned to review from its current status.'
                    );
                }
                // Authorization is handled within the service method
                // $this->authorize('returnToReview', $pitch); // Policy check should be added to service/policy

                $this->pitchWorkflowService->returnPitchToReview($pitch, $user);

                return redirect()->route('projects.manage', $project)->with('success', 'Pitch status returned to Ready for Review.');
            }

            // Original logic for other transitions (e.g., Pending -> In Progress)
            // Authorization check based on the status transition
            if ($pitch->status === Pitch::STATUS_PENDING && $newStatus === Pitch::STATUS_IN_PROGRESS) {
                $this->authorize('approveInitial', $pitch);
            } else if ($direction === 'backward' &&
                      ($pitch->status === Pitch::STATUS_IN_PROGRESS ||
                       $pitch->status === Pitch::STATUS_PENDING_REVIEW)) {
                // Assuming 'Remove Access' maps to some policy
                 $this->authorize('update', $pitch); // Use a generic update or a specific policy ability
            } else {
                // Fallback authorization (needs review based on allowed transitions)
                if ($user->id !== $project->user_id) {
                     throw new AuthorizationException('You are not authorized to perform this status change.');
                }
                 //$this->authorize('update', $pitch); // Original fallback
            }

            // Validate the transition itself (using the existing logic for non-revert cases)
            $availableTransitions = Pitch::$transitions[$direction][$pitch->status] ?? null;
            $isValidTransition = false;
            if (is_array($availableTransitions)) {
                $isValidTransition = in_array($newStatus, $availableTransitions);
            } else {
                $isValidTransition = $availableTransitions === $newStatus;
            }

            if (!$isValidTransition) {
                throw new InvalidStatusTransitionException(
                    $pitch->status,
                    $newStatus,
                    'Invalid status transition attempted.'
                );
            }

            $oldStatus = $pitch->status; // Store old status for event logging

            // Update the status
            $pitch->status = $newStatus;
            $pitch->save();

            // Record the event
            PitchEvent::createStatusChangeEvent(
                $pitch,
                $user,
                $oldStatus, // Use the stored old status
                $newStatus
            );

            return redirect()->route('projects.manage', $project)->with('success', 'Pitch status updated successfully.');

        } catch (AuthorizationException $e) {
            Log::warning('Unauthorized status change attempt', [
                'user_id' => $user->id, 'pitch_id' => $pitch->id, 'project_id' => $project->id,
                'direction' => $direction, 'new_status' => $newStatus, 'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'You are not authorized to change this pitch status.');
        } catch (InvalidStatusTransitionException $e) {
             Log::warning('Invalid status transition attempt', [
                'user_id' => $user->id, 'pitch_id' => $pitch->id, 'project_id' => $project->id,
                'direction' => $direction, 'old_status' => $pitch->getOriginal('status'), 'new_status' => $newStatus, 'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error changing pitch status via Controller', [
                'pitch_id' => $pitch->id,
                'direction' => $direction,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'An unexpected error occurred while changing the pitch status.');
        }
    }

    /**
     * Handle the request to return a completed pitch to the approved status.
     *
     * @param Request $request
     * @param Project $project From route model binding
     * @param Pitch $pitch From route model binding
     * @return \Illuminate\Http\RedirectResponse
     */
    public function returnToApproved(Request $request, Project $project, Pitch $pitch)
    {
        $user = Auth::user();

        try {
            // Ensure the pitch belongs to the project specified in the route
            if ($pitch->project_id !== $project->id) {
                abort(404, 'Pitch not found for this project');
            }
            
            // Use policy for authorization check first
            $this->authorize('returnToApproved', $pitch);

            // Call the service method
            $this->pitchWorkflowService->returnPitchToApproved($pitch, $user);

            Toaster::success('Pitch status returned to Approved.');
            Log::info('Pitch returned to approved via controller', ['pitch_id' => $pitch->id, 'user_id' => $user->id]);

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to perform this action.');
            Log::warning('Unauthorized attempt to return pitch to approved', ['pitch_id' => $pitch->id, 'user_id' => $user->id]);
        } catch (InvalidStatusTransitionException $e) {
            Toaster::error($e->getMessage());
            Log::warning('Invalid status transition attempt: return to approved', ['pitch_id' => $pitch->id, 'user_id' => $user->id, 'error' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            Toaster::error($e->getMessage()); // Show service runtime error message
            Log::error('Runtime error returning pitch to approved', ['pitch_id' => $pitch->id, 'user_id' => $user->id, 'error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Toaster::error('An unexpected error occurred. Please try again.');
            Log::error('Unexpected error returning pitch to approved', ['pitch_id' => $pitch->id, 'user_id' => $user->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        // Redirect back to the project management page or pitch page
        return redirect()->route('projects.manage', $project->slug);
        // Or: return redirect()->route('projects.pitches.show', ['project' => $project->slug, 'pitch' => $pitch->slug]);
    }
}

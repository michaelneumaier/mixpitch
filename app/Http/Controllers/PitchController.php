<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Project;
use App\Models\Pitch;
use App\Models\PitchSnapshot;

class PitchController extends Controller
{
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
        if (auth()->check()) {
            $userPitch = $project->userPitch(auth()->id());

            if ($userPitch) {
                return redirect()->route('projects.pitches.show', ['project' => $userPitch->project->slug, 'pitch' => $userPitch->slug]);
            }

            // Check if a new pitch can be created for this project
            [$canCreatePitch, $errorMessage] = $project->canCreatePitch(auth()->user());
            if (!$canCreatePitch) {
                return redirect()->route('projects.show', $project->slug)
                    ->with('error', $errorMessage);
            }
        }

        return view('pitches.create', compact('project'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Project $project = null)
    {
        $request->validate([
            'project_id' => 'required_without:project|exists:projects,id',
            'agree_terms' => 'accepted', // This checks that the terms checkbox was checked
        ]);

        // Get the project - either from the route parameter or the request
        if (!$project) {
            $project = Project::findOrFail($request->project_id);
        } elseif (!$request->has('project_id')) {
            // If using the new route pattern, add project_id to the request
            $request->merge(['project_id' => $project->id]);
        }

        // Check if a new pitch can be created for this project
        [$canCreatePitch, $errorMessage] = $project->canCreatePitch(auth()->user());
        if (!$canCreatePitch) {
            return redirect()->route('projects.show', $project->slug)
                ->with('error', $errorMessage);
        }

        $pitch = new Pitch();
        $pitch->user_id = Auth::id(); // Assumes the user is logged in and you're using Laravel's authentication
        $pitch->project_id = $project->id;
        $pitch->status = Pitch::STATUS_PENDING; // Set initial status to pending, requiring owner approval
        
        // Generate and set the slug using our helper method
        $this->generateAndSetSlug($pitch);

        // Create an initial event record
        $pitch->events()->create([
            'event_type' => 'status_change',
            'comment' => 'Pitch created and pending project owner approval',
            'status' => $pitch->status,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('projects.pitches.show', ['project' => $project->slug, 'pitch' => $pitch->slug])->with('success', 'Your pitch has been created and is pending approval from the project owner.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Find the pitch or return 404
        $pitch = Pitch::find($id);

        if (!$pitch) {
            abort(404, 'Pitch not found');
        }

        // Redirect to the new URL pattern
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
        // Verify the pitch belongs to the specified project
        if ($pitch->project_id !== $project->id) {
            abort(404, 'Pitch not found for this project');
        }

        // Retrieve the authenticated user
        $user = auth()->user();

        // Check if the user is the project owner
        if ($user->id === $project->user_id) {
            // Redirect project owners to the manage project page
            return redirect()->route('projects.manage', $project)
                ->with('info', 'Project owners should manage pitches from the project management page.');
        }

        // Check if the user is the pitch owner
        if ($user->id === $pitch->user_id) {
            // Allow pitch owners to view the pitch details
            return view('pitches.show', compact('pitch'));
        }

        // If the user is not authorized, redirect or show an error
        abort(403, 'Unauthorized action.');
    }

    public function showSnapshot(Pitch $pitch, PitchSnapshot $pitchSnapshot)
    {
        // Ensure the snapshot belongs to the given pitch
        if ($pitchSnapshot->pitch_id !== $pitch->id) {
            abort(404);
        }

        // Retrieve the authenticated user
        $user = auth()->user();

        // Check if the user is the project owner
        if ($user->id === $pitch->project->user_id) {
            // Redirect project owners to the manage project page
            return redirect()->route('projects.manage', $pitch->project)
                ->with('info', 'Project owners should manage pitches from the project management page.');
        }

        // Retrieve the snapshot data
        $snapshotData = $pitchSnapshot->snapshot_data;

        // Return a view to display the snapshot data
        return view('pitches.show-snapshot', compact('pitch', 'pitchSnapshot', 'snapshotData'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pitch $pitch)
    {
        // Redirect to the new URL pattern
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
        // Verify the pitch belongs to the specified project
        if ($pitch->project_id !== $project->id) {
            abort(404, 'Pitch not found for this project');
        }

        // Check if the user is authorized to edit the pitch
        $this->authorize('update', $pitch);

        return view('pitches.edit', compact('pitch'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $pitch = Pitch::findOrFail($id);

        // Check if the authenticated user is the pitch owner
        if (auth()->id() !== $pitch->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'response_to_feedback' => 'required_if:has_revisions,1|string|nullable',
        ]);

        // If responding to revisions, create a new snapshot and update status
        if ($pitch->status === Pitch::STATUS_REVISIONS_REQUESTED) {
            // Create a new snapshot with response to feedback
            $previousSnapshot = null;
            if ($pitch->current_snapshot_id) {
                $previousSnapshot = PitchSnapshot::find($pitch->current_snapshot_id);
            }

            $version = 1;
            if ($previousSnapshot && isset($previousSnapshot->snapshot_data['version'])) {
                $version = $previousSnapshot->snapshot_data['version'] + 1;
            }

            // Get all associated files for this pitch
            $fileIds = $pitch->files->pluck('id')->toArray();

            // Create the new snapshot
            $snapshot = new PitchSnapshot();
            $snapshot->pitch_id = $pitch->id;
            $snapshot->project_id = $pitch->project_id;
            $snapshot->user_id = auth()->id();
            $snapshot->status = 'pending';
            $snapshot->snapshot_data = [
                'version' => $version,
                'response_to_feedback' => $request->response_to_feedback,
                'previous_snapshot_id' => $pitch->current_snapshot_id,
                'file_ids' => $fileIds  // Include file IDs in the snapshot data
            ];
            $snapshot->save();

            // Create an event to record the response to feedback
            if (!empty($request->response_to_feedback)) {
                $pitch->events()->create([
                    'event_type' => 'revision_submitted',
                    'user_id' => auth()->id(),
                    'created_by' => auth()->id(),
                    'snapshot_id' => $snapshot->id,
                    'comment' => "Revision submitted with response: " . $request->response_to_feedback
                ]);
            }

            // Update the previous snapshot status to 'revision_addressed'
            if ($previousSnapshot) {
                $previousSnapshot->status = 'revision_addressed';
                $previousSnapshot->save();
            }

            // Update the current snapshot reference
            $pitch->current_snapshot_id = $snapshot->id;

            // Update the pitch status to ready for review
            $pitch->changeStatus('forward', Pitch::STATUS_READY_FOR_REVIEW, 'Revisions submitted in response to feedback');

            return redirect()->route('projects.pitches.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug])
                ->with('success', 'Your revisions have been submitted successfully and are now under review.');
        }

        return redirect()->route('projects.pitches.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug])
            ->with('error', 'This pitch is not currently awaiting revisions.');
    }

    /**
     * Submit revisions for a pitch
     */
    public function submitRevisions(Request $request, Pitch $pitch)
    {
        // Ensure the authenticated user owns this pitch
        if (!auth()->check() || $pitch->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        // Validate that the pitch is in the correct status for revisions
        if ($pitch->status === Pitch::STATUS_REVISIONS_REQUESTED) {
            // Get the current snapshot which should be the one with revisions requested
            $previousSnapshot = $pitch->currentSnapshot;

            // Begin a database transaction to ensure atomicity
            \DB::beginTransaction();

            try {
                // Determine the version number for the new snapshot
                $version = 1;
                $lastSnapshot = $pitch->snapshots()->orderBy('created_at', 'desc')->first();
                if ($lastSnapshot) {
                    $version = $lastSnapshot->snapshot_data['version'] + 1;
                }

                // Get the current files to include in the snapshot
                $fileIds = $pitch->files->pluck('id')->toArray();

                // Create the new snapshot
                $snapshot = new PitchSnapshot();
                $snapshot->pitch_id = $pitch->id;
                $snapshot->project_id = $pitch->project_id;
                $snapshot->user_id = auth()->id();
                $snapshot->status = 'pending';
                $snapshot->snapshot_data = [
                    'version' => $version,
                    'response_to_feedback' => $request->response_to_feedback,
                    'previous_snapshot_id' => $pitch->current_snapshot_id,
                    'file_ids' => $fileIds  // Include file IDs in the snapshot data
                ];
                $snapshot->save();

                // Create an event to record the response to feedback
                if (!empty($request->response_to_feedback)) {
                    $pitch->events()->create([
                        'event_type' => 'revision_submitted',
                        'user_id' => auth()->id(),
                        'created_by' => auth()->id(),
                        'snapshot_id' => $snapshot->id,
                        'comment' => "Revision submitted with response: " . $request->response_to_feedback
                    ]);
                }

                // Update the previous snapshot status to 'revision_addressed'
                if ($previousSnapshot) {
                    $previousSnapshot->status = 'revision_addressed';
                    $previousSnapshot->save();
                }

                // Update the current snapshot reference
                $pitch->current_snapshot_id = $snapshot->id;

                // Validate the transition to ready_for_review status
                [$canSubmit, $errorMessage] = $pitch->canSubmitForReview();
                if (!$canSubmit) {
                    \DB::rollBack();
                    return redirect()->route('projects.pitches.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug])
                        ->with('error', $errorMessage);
                }

                // Update the pitch status to ready for review
                $pitch->changeStatus('forward', Pitch::STATUS_READY_FOR_REVIEW, 'Revisions submitted in response to feedback');

                // Commit the transaction
                \DB::commit();

                return redirect()->route('projects.pitches.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug])
                    ->with('success', 'Your revisions have been submitted successfully and are now under review.');
            } catch (\Exception $e) {
                // Roll back the transaction if something goes wrong
                \DB::rollBack();
                \Log::error('Failed to submit revisions: ' . $e->getMessage(), [
                    'pitch_id' => $pitch->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return redirect()->route('projects.pitches.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug])
                    ->with('error', 'An error occurred while submitting revisions: ' . $e->getMessage());
            }
        }

        return redirect()->route('projects.pitches.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug])
            ->with('error', 'This pitch is not currently awaiting revisions.');
    }

    public function updateStatus(Request $request, Pitch $pitch)
    {
        $project = $pitch->project;

        // Ensure the authenticated user is the owner of the project
        if (!auth()->check() || $project->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $direction = $request->input('direction');

        try {
            $pitch->changeStatus($direction);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('projects.show', $project)->with('status', 'Pitch status updated successfully.');
    }

    /**
     * Change the status of a pitch
     */
    public function changeStatus(Request $request, Project $project, Pitch $pitch)
    {
        // Ensure the authenticated user is the owner of the project
        if (!auth()->check() || $project->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $direction = $request->input('direction');
        $newStatus = $request->input('new_status');

        // Begin a database transaction to ensure atomicity
        \DB::beginTransaction();

        try {
            // Validate the status change based on the requested direction and new status
            if ($newStatus) {
                $validationPassed = false;
                $errorMessage = '';

                switch ($newStatus) {
                    case Pitch::STATUS_APPROVED:
                        [$validationPassed, $errorMessage] = $pitch->canApprove($pitch->current_snapshot_id);
                        break;
                    case Pitch::STATUS_DENIED:
                        [$validationPassed, $errorMessage] = $pitch->canDeny($pitch->current_snapshot_id);
                        break;
                    case Pitch::STATUS_REVISIONS_REQUESTED:
                        [$validationPassed, $errorMessage] = $pitch->canRequestRevisions($pitch->current_snapshot_id);
                        break;
                    case Pitch::STATUS_IN_PROGRESS:
                        if ($pitch->status === Pitch::STATUS_READY_FOR_REVIEW) {
                            [$validationPassed, $errorMessage] = $pitch->canCancelSubmission();
                        } else {
                            $validationPassed = true;
                        }
                        break;
                    case Pitch::STATUS_COMPLETED:
                        [$validationPassed, $errorMessage] = $pitch->canComplete();
                        break;
                    default:
                        // For other transitions, rely on the model's validateStatusTransition method
                        $validationPassed = true;
                }

                if (!$validationPassed) {
                    throw new \Exception($errorMessage);
                }

                $pitch->changeStatus($direction, $newStatus);
            } else {
                $pitch->changeStatus($direction);
            }

            \DB::commit();

            return redirect()->route('projects.show', $project)
                ->with('status', 'Pitch status updated successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();

            // Log the error for debugging
            \Log::error('Failed to change pitch status', [
                'pitch_id' => $pitch->id,
                'attempted_direction' => $direction,
                'attempted_status' => $newStatus ?? 'default transition',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    /**
     * Show the latest snapshot for a pitch
     *
     * @param Pitch $pitch
     * @return \Illuminate\Http\RedirectResponse
     */
    public function showLatestSnapshot(Pitch $pitch)
    {
        // Retrieve the latest snapshot for this pitch
        $latestSnapshot = $pitch->snapshots()->orderBy('created_at', 'desc')->first();

        if ($latestSnapshot) {
            return redirect()->route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $latestSnapshot->id]);
        }

        return redirect()->back()->with('error', 'No snapshots available to review.');
    }

    /**
     * Process the confirmed deletion from the Livewire component
     */
    public function destroyConfirmed(Project $project = null, Pitch $pitch = null)
    {
        // If pitch is null but project is provided, it means we're using the new route pattern
        // and Laravel has bound parameters in the wrong order
        if ($pitch === null && $project !== null && $project instanceof Pitch) {
            $pitch = $project;
            $project = null;
        }
        
        // Ensure the pitch exists
        if (!$pitch || !$pitch->exists) {
            abort(404, 'Pitch not found');
        }

        // Ensure the authenticated user is the pitch owner
        if (Auth::id() !== $pitch->user_id) {
            return redirect()->back()->withErrors(['You are not authorized to delete this pitch.']);
        }

        // Begin database transaction to ensure all-or-nothing deletion
        \DB::beginTransaction();

        try {
            // 1. Delete all pitch events (audit trail)
            $pitch->events()->delete();

            // 2. Delete pitch notifications
            \Illuminate\Support\Facades\DB::table('notifications')
                ->whereRaw("json_extract(data, '$.pitch_id') = ?", [$pitch->id])
                ->delete();

            // 3. Delete pitch snapshots and their associated files
            foreach ($pitch->snapshots as $snapshot) {
                // Handle files associated with snapshots
                if (isset($snapshot->snapshot_data['file_ids']) && is_array($snapshot->snapshot_data['file_ids'])) {
                    foreach ($snapshot->snapshot_data['file_ids'] as $fileId) {
                        $file = \App\Models\PitchFile::find($fileId);
                        if ($file) {
                            // Delete physical file if it exists
                            if ($file->file_path && \Storage::disk('public')->exists($file->file_path)) {
                                \Storage::disk('public')->delete($file->file_path);
                            }
                            $file->delete();
                        }
                    }
                }

                $snapshot->delete();
            }

            // 4. Delete any remaining pitch files not associated with snapshots
            $pitch->files()->get()->each(function ($file) {
                if ($file->file_path && \Storage::disk('public')->exists($file->file_path)) {
                    \Storage::disk('public')->delete($file->file_path);
                }
                $file->delete();
            });

            // 5. If there are any comments associated with the pitch, delete them
            if (method_exists($pitch, 'comments')) {
                $pitch->comments()->delete();
            }

            // 6. Delete the pitch itself
            $projectId = $pitch->project_id; // Store the project ID before deleting the pitch
            $pitch->delete();

            // Commit the transaction
            \DB::commit();

            // Redirect to the dashboard with success message
            return redirect()->route('dashboard')
                ->with('success', 'Your pitch has been successfully deleted.');
        } catch (\Exception $e) {
            // Roll back the transaction if any operation fails
            \DB::rollBack();

            // Log the error
            \Illuminate\Support\Facades\Log::error('Pitch deletion failed: ' . $e->getMessage());

            // Redirect with error message
            return redirect()->back()->withErrors(['Failed to delete pitch. Please try again or contact support.']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // This method is part of the resource controller but we're using destroyConfirmed instead
        // for our deletion flow to better handle the Livewire component integration
        return redirect()->route('dashboard');
    }

    /**
     * Display the payment details for a pitch
     *
     * @param  \App\Models\Pitch  $pitch
     * @return \Illuminate\Http\Response
     */
    public function showProjectPitchPayment(Project $project, Pitch $pitch)
    {
        // Verify the pitch belongs to the specified project
        if ($pitch->project_id !== $project->id) {
            abort(404, 'Pitch not found for this project');
        }

        // Redirect to payment overview
        return redirect()->route('projects.pitches.payment.overview', [
            'project' => $project,
            'pitch' => $pitch
        ]);
    }

    /**
     * Original payment method redirects to new URL
     */
    public function showPayment(Pitch $pitch)
    {
        // Redirect to the new URL pattern
        return redirect()->route('projects.pitches.payment', [
            'project' => $pitch->project,
            'pitch' => $pitch
        ]);
    }

    /**
     * Generate and set a unique slug for a pitch
     * 
     * @param Pitch $pitch The pitch model to set a slug for
     * @return void
     */
    private function generateAndSetSlug(Pitch $pitch)
    {
        // Manually set a slug to avoid the sluggable trait error
        $user = Auth::user();
        $username = $user ? ($user->username ?? 'user-' . $user->id) : 'user-' . Auth::id();
        $baseSlug = \Illuminate\Support\Str::slug($username);
        
        // Check if the slug already exists for this project
        $existingCount = \App\Models\Pitch::where('project_id', $pitch->project_id)
            ->where('slug', 'like', $baseSlug . '%')
            ->count();
            
        // Make the slug unique within the project
        $pitch->slug = $existingCount > 0 ? $baseSlug . '-' . ($existingCount + 1) : $baseSlug;
        
        // Disable automatic slug generation
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $pitch->saveQuietly(); // This bypasses the slug generation
            \Illuminate\Support\Facades\DB::commit();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            throw $e;
        }
    }
}

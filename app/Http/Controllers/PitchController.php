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
use App\Http\Requests\Pitch\UpdatePitchRequest;
use App\Services\PitchService;

class PitchController extends Controller
{
    protected $pitchWorkflowService;
    protected $pitchService;

    public function __construct(PitchWorkflowService $pitchWorkflowService, PitchService $pitchService)
    {
        $this->pitchWorkflowService = $pitchWorkflowService;
        $this->pitchService = $pitchService;
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

        if ($user && $user->id === $project->user_id) {
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

        $user = auth()->user();

        if ($user && $user->id === $pitch->project->user_id) {
            return redirect()->route('projects.manage', $pitch->project)
                ->with('info', 'Project owners should manage pitches from the project management page.');
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

        return view('pitches.edit', compact('pitch'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePitchRequest $request, Pitch $pitch)
    {
        $user = Auth::user();

        try {
            $this->authorize('update', $pitch);

            $updatedPitch = $this->pitchService->updatePitch(
                $pitch,
                $user,
                $request->validated()
            );

            // Use RouteHelpers for URL generation
            return redirect(RouteHelpers::pitchUrl($updatedPitch))
                ->with('success', 'Pitch updated successfully!');
        } catch (AuthorizationException $e) {
            return redirect()->back()
                ->with('error', 'You are not authorized to update this pitch: ' . $e->getMessage());
        } catch (PitchUpdateException $e) {
            Log::error('Pitch update failed: ' . $e->getMessage(), ['pitch_id' => $pitch->id, 'user_id' => $user->id]);
            return redirect()->back()
                ->with('error', 'Failed to update pitch: ' . $e->getMessage());
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
        
        try {
            $this->authorize('delete', $pitch);
            $this->pitchService->deletePitch($pitch, Auth::user());
            
            // Redirect to the project page after successful deletion
            return redirect()->route('projects.show', $project)
                ->with('success', 'Your pitch has been successfully deleted.');
        } catch (AuthorizationException $e) {
            Log::warning('Unauthorized attempt to delete pitch', ['pitch_id' => $pitch->id, 'user_id' => Auth::id()]);
            // Use RouteHelpers for URL generation
            return redirect(RouteHelpers::pitchUrl($pitch))
                 ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Pitch deletion failed: ' . $e->getMessage(), ['pitch_id' => $pitch->id, 'user_id' => Auth::id()]);
            // Use RouteHelpers for URL generation
            return redirect(RouteHelpers::pitchUrl($pitch))
                ->with('error', 'Failed to delete pitch. Please try again or contact support.');
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
}

<?php

namespace App\Http\Controllers;

use App\Models\Pitch;
use App\Models\PitchSnapshot;
use App\Models\Project;
use App\Services\PitchWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PitchSnapshotController extends Controller
{
    protected $pitchWorkflowService;

    public function __construct(PitchWorkflowService $pitchWorkflowService)
    {
        $this->pitchWorkflowService = $pitchWorkflowService;
    }

    /**
     * Approve a pitch snapshot using route model binding.
     */
    public function approve(Request $request, Project $project, Pitch $pitch, PitchSnapshot $snapshot)
    {
        // Verify relationships (optional, but good practice)
        if ($pitch->project_id !== $project->id || $snapshot->pitch_id !== $pitch->id) {
            Log::warning('Model relationship mismatch in PitchSnapshotController@approve', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'snapshot_id' => $snapshot->id,
            ]);
            abort(404, 'Resource relationship mismatch.');
        }

        try {
            $this->authorize('approveSubmission', $pitch);

            $this->pitchWorkflowService->approveSubmittedPitch($pitch, $snapshot->id, Auth::user());

            // Redirect back to the manage project page
            return redirect()->route('projects.manage', $project)
                ->with('success', 'Pitch has been approved successfully!');

        } catch (AuthorizationException $e) {
            Log::warning('Authorization failed in PitchSnapshotController@approve', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);

            return redirect()->back()->with('error', 'You are not authorized to approve this pitch.');
        } catch (\Exception $e) {
            Log::error('Error approving pitch snapshot: '.$e->getMessage(), [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'snapshot_id' => $snapshot->id,
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'Failed to approve pitch: An unexpected error occurred.');
        }
    }

    /**
     * Deny a pitch snapshot using route model binding.
     */
    public function deny(Request $request, Project $project, Pitch $pitch, PitchSnapshot $snapshot)
    {
        // Verify relationships
        if ($pitch->project_id !== $project->id || $snapshot->pitch_id !== $pitch->id) {
            Log::warning('Model relationship mismatch in PitchSnapshotController@deny', [
                'project_id' => $project->id, 'pitch_id' => $pitch->id, 'snapshot_id' => $snapshot->id,
            ]);
            abort(404, 'Resource relationship mismatch.');
        }

        try {
            // Debug log request data
            Log::debug('PitchSnapshotController@deny request data:', [
                'all_data' => $request->all(),
                'reason_field' => $request->input('reason'),
                'method' => $request->method(),
                'route' => optional($request->route())->getName(), // Use optional() for safety
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'snapshot_id' => $snapshot->id,
            ]);

            $this->authorize('denySubmission', $pitch);

            $validated = $request->validate([
                'reason' => 'required|string|min:10',
            ]);

            $this->pitchWorkflowService->denySubmittedPitch(
                $pitch,
                $snapshot->id,
                Auth::user(),
                $validated['reason']
            );

            // Redirect back to the manage project page
            return redirect()->route('projects.manage', $project)
                ->with('success', 'Pitch has been denied.');

        } catch (AuthorizationException $e) {
            Log::warning('Authorization failed in PitchSnapshotController@deny', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);

            return redirect()->back()->with('error', 'You are not authorized to deny this pitch.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed in PitchSnapshotController@deny', ['errors' => $e->errors(), 'request_data' => $request->all()]);

            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error denying pitch snapshot: '.$e->getMessage(), [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'snapshot_id' => $snapshot->id,
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'Failed to deny pitch: An unexpected error occurred.');
        }
    }

    /**
     * Request changes to a pitch snapshot using route model binding.
     */
    public function requestChanges(Request $request, Project $project, Pitch $pitch, PitchSnapshot $snapshot)
    {
        // Verify relationships
        if ($pitch->project_id !== $project->id || $snapshot->pitch_id !== $pitch->id) {
            Log::warning('Model relationship mismatch in PitchSnapshotController@requestChanges', [
                'project_id' => $project->id, 'pitch_id' => $pitch->id, 'snapshot_id' => $snapshot->id,
            ]);
            abort(404, 'Resource relationship mismatch.');
        }

        try {
            // Debug log request data
            Log::debug('PitchSnapshotController@requestChanges request data:', [
                'all_data' => $request->all(),
                'reason_field' => $request->input('reason'),
                'method' => $request->method(),
                'route' => optional($request->route())->getName(), // Use optional() for safety
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'snapshot_id' => $snapshot->id,
            ]);

            $this->authorize('requestRevisions', $pitch);

            $validated = $request->validate([
                'reason' => 'required|string|min:10',
            ]);

            $this->pitchWorkflowService->requestPitchRevisions(
                $pitch,
                $snapshot->id,
                Auth::user(),
                $validated['reason']
            );

            // Redirect back to the manage project page
            return redirect()->route('projects.manage', $project)
                ->with('success', 'Revisions have been requested.');

        } catch (AuthorizationException $e) {
            Log::warning('Authorization failed in PitchSnapshotController@requestChanges', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);

            return redirect()->back()->with('error', 'You are not authorized to request revisions for this pitch.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed in PitchSnapshotController@requestChanges', ['errors' => $e->errors(), 'request_data' => $request->all()]);

            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error requesting revisions for pitch snapshot: '.$e->getMessage(), [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'snapshot_id' => $snapshot->id,
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'Failed to request revisions: An unexpected error occurred.');
        }
    }
}

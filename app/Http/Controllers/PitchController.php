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
                return redirect()->route('pitches.show', $userPitch->id);
            }
        }

        return view('pitches.create', compact('project'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'agree_terms' => 'accepted', // This checks that the terms checkbox was checked
        ]);

        $pitch = new Pitch();
        $pitch->user_id = Auth::id(); // Assumes the user is logged in and you're using Laravel's authentication
        $pitch->project_id = $request->project_id;
        $pitch->status = 'pending'; // Assuming an initial status of 'pending'
        // Add other fields as necessary

        $pitch->save();

        return redirect()->route('pitches.show', $pitch->id)->with('success', 'Your pitch has been started successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $pitch = Pitch::with('project')->find($id);

        // Retrieve the authenticated user
        $user = auth()->user();

        // Check if the user is either the project owner or the pitch user
        if ($user->id === $pitch->user_id || $user->id === $pitch->project->user_id) {
            // Assuming you have a view called 'pitches.show' to display the pitch dashboard
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

        // Retrieve the snapshot data
        $snapshotData = $pitchSnapshot->snapshot_data;

        // Return a view to display the snapshot data
        return view('pitches.show-snapshot', compact('pitch', 'pitchSnapshot', 'snapshotData'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Project;
use App\Models\Pitch;

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
        // Assuming you have a view called 'pitches.show' to display the pitch dashboard
        return view('pitches.show', compact('pitch'));
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

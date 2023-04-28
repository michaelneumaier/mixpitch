<?php

namespace App\Http\Controllers;
use Storage;
use App\Models\Track;
use Illuminate\Http\Request;


class TrackController extends Controller
{
    public function upload(Request $request)
{
    $request->validate([
        'track' => 'required|mimes:mp3,wav|max:204800'
    ]);

    $path = $request->file('track')->store('tracks', 'public');

    // Save track information in the database.
    $track = new Track;
    $track->user_id = auth()->id();
    $track->title = $request->input('title', 'Untitled');
    $track->genre = $request->input('genre', '');
    $track->file_path = $path;
    $track->save();

    return redirect()->back()->with('message', 'Track uploaded successfully!');
}

public function index()
{
    $tracks = Track::all(); // Fetch all tracks from the database

    return view('tracks', compact('tracks')); // Pass the tracks to the view
}

public function createProject()
{
    return view('upload-project');
}

public function projects()
{
    $projects = Track::all();
    //$projects = Track::distinct()->select('title', 'user_id', 'created_at')->get();
    return view('projects.index', compact('projects'));
}


public function show($id)
{
    $track = Track::findOrFail($id);
    return view('tracks.show', compact('track'));
}


public function storeProject(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'tracks.*' => 'required|mimes:mp3,wav|max:20480',
    ]);

    $tracks = [];

    foreach ($request->file('tracks') as $track) {
        $path = $track->store('tracks', 'public');
        $filename = $track->getClientOriginalName();

        $tracks[] = [
            'title' => $request->input('title'),

            'genre' => 'unknown', // Replace this with an appropriate genre or add a genre input field to the form
            'file_path' => $path,
            'user_id' => auth()->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    Track::insert($tracks);

    return redirect()->route('tracks.index')->with('message', 'Project uploaded successfully.');
}

public function destroy($id)
{
    $track = Track::findOrFail($id);

    if (auth()->user()->id !== $track->user_id) {
        return redirect()->back()->withErrors(['You are not authorized to delete this project.']);
    }

    Storage::disk('public')->delete($track->file_path);
    $track->delete();

    return redirect()->route('projects.index')->with('message', 'Project deleted successfully.');
}




}

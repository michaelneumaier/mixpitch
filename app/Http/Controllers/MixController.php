<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mix;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class MixController extends Controller
{
    public function create($slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();
        return view('mixes.create', compact('project'));
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'mix_file' => 'required|mimes:mp3,wav|max:20480',
            'description' => 'nullable|string|max:500',
        ]);

        $mix_file_path = $request->file('mix_file')->store('mixes', 'public');

        $mix = new Mix([
            'mix_file_path' => $mix_file_path,
            'description' => $request->input('description'),
            'rating' => 0,
        ]);

        $mix->user()->associate(Auth::user());
        $mix->project()->associate($project);
        $mix->save();

        return redirect()->route('projects.show', ['project' => $project])
            ->with('success', 'Your mix has been submitted successfully!');
    }

    public function rate(Mix $mix, int $rating)
    {
//        $request->validate([
//            'rating' => 'required|integer|min:1|max:10',
//        ]);

        $mix->update(['rating' => $rating]);

        return back()->with('success', 'Your rating has been saved!');
    }
}

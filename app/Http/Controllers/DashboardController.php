<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //
    public function index()
    {
        $user = auth()->user();
        $projects = $user->projects;
        $pitches = $user->pitches()->with('project')->get();
        return view('dashboard', ['projects' => $projects, 'pitches' => $pitches]);
    }
}

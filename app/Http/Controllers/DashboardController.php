<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //
    public function index()
    {
        $projects = auth()->user()->projects;
        $pitches = auth()->user()->pitches;
        return view('dashboard', ['projects' => $projects, 'pitches' => $pitches]);
    }
}

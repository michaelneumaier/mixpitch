<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //
    public function index()
    {
        $projects = auth()->user()->projects;
        $mixes = auth()->user()->mixes;
        return view('dashboard', ['projects' => $projects, 'mixes' => $mixes]);
    }

}
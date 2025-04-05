<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    //
    public function index()
    {
        $user = auth()->user();
        
        // Get projects and ensure slugs are set
        $projects = $user->projects()->get();
        
        // Debug projects and their slugs
        foreach ($projects as $project) {
            // Check if the project has a slug and generate it if missing
            if (empty($project->slug) && !empty($project->name)) {
                // Generate a slug from the name
                $slug = Str::slug($project->name);
                
                // Check if this slug already exists to avoid duplicates
                $count = 1;
                $originalSlug = $slug;
                while (\App\Models\Project::where('slug', $slug)->where('id', '!=', $project->id)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }
                
                // Save the slug
                $project->slug = $slug;
                $project->save();
                
                \Log::info('Generated missing slug for project', [
                    'project_id' => $project->id,
                    'name' => $project->name,
                    'new_slug' => $slug
                ]);
            }
            
            \Log::debug('Project in dashboard: ' . json_encode([
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'getRouteKey' => $project->getRouteKey(),
                'getRouteKeyName' => $project->getRouteKeyName()
            ]));
        }
        
        $pitches = $user->pitches()->with('project')->get();
        return view('dashboard', ['projects' => $projects, 'pitches' => $pitches]);
    }
}

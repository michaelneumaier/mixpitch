<?php

namespace App\Helpers;

use App\Models\Pitch;

class RouteHelpers
{
    /**
     * Generate the URL for a pitch using the new URL pattern
     * This is a helper function to ease the transition from the old routes to the new routes
     *
     * @param Pitch $pitch The pitch model
     * @param array $params Additional route parameters
     * @return string The generated URL
     */
    public static function pitchUrl(Pitch $pitch, array $params = [])
    {
        // Check if pitch is valid
        if (!$pitch || !$pitch->id) {
            \Illuminate\Support\Facades\Log::error('Invalid pitch object in pitchUrl');
            throw new \Exception("Missing required parameter: pitch");
        }
        
        // Ensure project relationship is loaded
        if (!$pitch->relationLoaded('project')) {
            $pitch->load('project');
        }
        
        // If project is still null, log error and use project_id directly
        if (!$pitch->project) {
            \Illuminate\Support\Facades\Log::error('Project relationship is null for pitch', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id
            ]);
            
            // Find the project directly using the project_id
            $project = \App\Models\Project::find($pitch->project_id);
            
            if (!$project) {
                \Illuminate\Support\Facades\Log::error('Could not find project for pitch', [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id
                ]);
                // If project still can't be found, throw an exception to make the issue clear
                throw new \Exception("Missing required parameter for [Route: projects.pitches.show] [URI: projects/{project}/pitches/{pitch}] [Missing parameter: project]");
            }
            
            $baseParams = [
                'project' => $project,
                'pitch' => $pitch
            ];
        } else {
            $baseParams = [
                'project' => $pitch->project,
                'pitch' => $pitch
            ];
        }
        
        return route('projects.pitches.show', array_merge($baseParams, $params));
    }
    
    /**
     * Generate the URL for a pitch payment overview
     *
     * @param Pitch $pitch The pitch model
     * @return string The generated URL
     */
    public static function pitchPaymentUrl(Pitch $pitch)
    {
        // Check if pitch is valid
        if (!$pitch || !$pitch->id) {
            \Illuminate\Support\Facades\Log::error('Invalid pitch object in pitchPaymentUrl');
            throw new \Exception("Missing required parameter: pitch");
        }
        
        // Ensure project relationship is loaded
        if (!$pitch->relationLoaded('project')) {
            $pitch->load('project');
        }
        
        // If project is still null, log error and use project_id directly
        if (!$pitch->project) {
            \Illuminate\Support\Facades\Log::error('Project relationship is null for pitch in paymentUrl', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id
            ]);
            
            // Find the project directly using the project_id
            $project = \App\Models\Project::find($pitch->project_id);
            
            if (!$project) {
                \Illuminate\Support\Facades\Log::error('Could not find project for pitch in paymentUrl', [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id
                ]);
                throw new \Exception("Missing required parameter for [Route: projects.pitches.payment.overview] [URI: projects/{project}/pitches/{pitch}/payment/overview] [Missing parameter: project]");
            }
            
            return route('projects.pitches.payment.overview', [
                'project' => $project, 
                'pitch' => $pitch
            ]);
        }
        
        return route('projects.pitches.payment.overview', [
            'project' => $pitch->project, 
            'pitch' => $pitch
        ]);
    }
    
    /**
     * Generate the URL for a pitch payment receipt
     *
     * @param Pitch $pitch The pitch model
     * @return string The generated URL
     */
    public static function pitchReceiptUrl(Pitch $pitch)
    {
        // Check if pitch is valid
        if (!$pitch || !$pitch->id) {
            \Illuminate\Support\Facades\Log::error('Invalid pitch object in pitchReceiptUrl');
            throw new \Exception("Missing required parameter: pitch");
        }
        
        // Ensure project relationship is loaded
        if (!$pitch->relationLoaded('project')) {
            $pitch->load('project');
        }
        
        // If project is still null, log error and use project_id directly
        if (!$pitch->project) {
            \Illuminate\Support\Facades\Log::error('Project relationship is null for pitch in receiptUrl', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id
            ]);
            
            // Find the project directly using the project_id
            $project = \App\Models\Project::find($pitch->project_id);
            
            if (!$project) {
                \Illuminate\Support\Facades\Log::error('Could not find project for pitch in receiptUrl', [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id
                ]);
                throw new \Exception("Missing required parameter for [Route: projects.pitches.payment.receipt] [URI: projects/{project}/pitches/{pitch}/payment/receipt] [Missing parameter: project]");
            }
            
            return route('projects.pitches.payment.receipt', [
                'project' => $project, 
                'pitch' => $pitch
            ]);
        }
        
        return route('projects.pitches.payment.receipt', [
            'project' => $pitch->project, 
            'pitch' => $pitch
        ]);
    }
    
    /**
     * Generate the URL for editing a pitch
     *
     * @param Pitch $pitch The pitch model
     * @return string The generated URL
     */
    public static function pitchEditUrl(Pitch $pitch)
    {
        // Check if pitch is valid
        if (!$pitch || !$pitch->id) {
            \Illuminate\Support\Facades\Log::error('Invalid pitch object in pitchEditUrl');
            throw new \Exception("Missing required parameter: pitch");
        }
        
        // Ensure project relationship is loaded
        if (!$pitch->relationLoaded('project')) {
            $pitch->load('project');
        }
        
        // If project is still null, log error and use project_id directly
        if (!$pitch->project) {
            \Illuminate\Support\Facades\Log::error('Project relationship is null for pitch in editUrl', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id
            ]);
            
            // Find the project directly using the project_id
            $project = \App\Models\Project::find($pitch->project_id);
            
            if (!$project) {
                \Illuminate\Support\Facades\Log::error('Could not find project for pitch in editUrl', [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id
                ]);
                throw new \Exception("Missing required parameter for [Route: projects.pitches.edit] [URI: projects/{project}/pitches/{pitch}/edit] [Missing parameter: project]");
            }
            
            return route('projects.pitches.edit', [
                'project' => $project, 
                'pitch' => $pitch
            ]);
        }
        
        return route('projects.pitches.edit', [
            'project' => $pitch->project, 
            'pitch' => $pitch
        ]);
    }
} 
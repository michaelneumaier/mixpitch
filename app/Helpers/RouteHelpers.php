<?php

namespace App\Helpers;

use App\Models\Pitch;
use App\Models\Project;
use Illuminate\Support\Facades\Log;

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
            Log::error('Invalid pitch object in pitchUrl');
            throw new \Exception("Missing required parameter: pitch");
        }
        
        // Ensure project relationship is loaded
        if (!$pitch->relationLoaded('project')) {
            $pitch->load('project');
        }
        
        // If project is still null, log error and use project_id directly
        if (!$pitch->project && $pitch->project_id) {
            Log::error('Project relationship is null for pitch', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id
            ]);
            
            // Find the project directly using the project_id
            $project = \App\Models\Project::find($pitch->project_id);
            
            if (!$project) {
                Log::error('Could not find project for pitch', [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id
                ]);
                // If project still can't be found, throw an exception to make the issue clear
                throw new \Exception("Missing required parameter for [Route: projects.pitches.show] [URI: projects/{project}/pitches/{pitch}] [Missing parameter: project]");
            }
            
            return route('projects.pitches.show', array_merge([
                'project' => $project,
                'pitch' => $pitch
            ], $params));
        } elseif (!$pitch->project) {
            // If there's no project and no project_id, we can't generate the URL
            Log::error('No project or project_id found for pitch', [
                'pitch_id' => $pitch->id
            ]);
            throw new \Exception("Missing required parameter for [Route: projects.pitches.show] [URI: projects/{project}/pitches/{pitch}] [Missing parameter: project]");
        }
        
        // If we got here, we have a valid project relationship
        try {
            return route('projects.pitches.show', array_merge([
                'project' => $pitch->project,
                'pitch' => $pitch
            ], $params));
        } catch (\Exception $e) {
            Log::error('Error generating route in pitchUrl', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
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
            Log::error('Invalid pitch object in pitchPaymentUrl');
            throw new \Exception("Missing required parameter: pitch");
        }
        
        // Ensure project relationship is loaded
        if (!$pitch->relationLoaded('project')) {
            $pitch->load('project');
        }
        
        // If project is still null, log error and use project_id directly
        if (!$pitch->project && $pitch->project_id) {
            Log::error('Project relationship is null for pitch in paymentUrl', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id
            ]);
            
            // Find the project directly using the project_id
            $project = \App\Models\Project::find($pitch->project_id);
            
            if (!$project) {
                Log::error('Could not find project for pitch in paymentUrl', [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id
                ]);
                throw new \Exception("Missing required parameter for [Route: projects.pitches.payment.overview] [URI: projects/{project}/pitches/{pitch}/payment/overview] [Missing parameter: project]");
            }
            
            return route('projects.pitches.payment.overview', [
                'project' => $project, 
                'pitch' => $pitch
            ]);
        } elseif (!$pitch->project) {
            // If there's no project and no project_id, we can't generate the URL
            Log::error('No project or project_id found for pitch in paymentUrl', [
                'pitch_id' => $pitch->id
            ]);
            throw new \Exception("Missing required parameter for [Route: projects.pitches.payment.overview] [URI: projects/{project}/pitches/{pitch}/payment/overview] [Missing parameter: project]");
        }
        
        try {
            return route('projects.pitches.payment.overview', [
                'project' => $pitch->project, 
                'pitch' => $pitch
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating route in pitchPaymentUrl', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
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
            Log::error('Invalid pitch object in pitchReceiptUrl');
            throw new \Exception("Missing required parameter: pitch");
        }
        
        // Ensure project relationship is loaded
        if (!$pitch->relationLoaded('project')) {
            $pitch->load('project');
        }
        
        // If project is still null, log error and use project_id directly
        if (!$pitch->project && $pitch->project_id) {
            Log::error('Project relationship is null for pitch in receiptUrl', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id
            ]);
            
            // Find the project directly using the project_id
            $project = \App\Models\Project::find($pitch->project_id);
            
            if (!$project) {
                Log::error('Could not find project for pitch in receiptUrl', [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id
                ]);
                throw new \Exception("Missing required parameter for [Route: projects.pitches.payment.receipt] [URI: projects/{project}/pitches/{pitch}/payment/receipt] [Missing parameter: project]");
            }
            
            return route('projects.pitches.payment.receipt', [
                'project' => $project, 
                'pitch' => $pitch
            ]);
        } elseif (!$pitch->project) {
            // If there's no project and no project_id, we can't generate the URL
            Log::error('No project or project_id found for pitch in receiptUrl', [
                'pitch_id' => $pitch->id
            ]);
            throw new \Exception("Missing required parameter for [Route: projects.pitches.payment.receipt] [URI: projects/{project}/pitches/{pitch}/payment/receipt] [Missing parameter: project]");
        }
        
        try {
            return route('projects.pitches.payment.receipt', [
                'project' => $pitch->project, 
                'pitch' => $pitch
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating route in pitchReceiptUrl', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
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
            Log::error('Invalid pitch object in pitchEditUrl');
            throw new \Exception("Missing required parameter: pitch");
        }
        
        // Ensure project relationship is loaded
        if (!$pitch->relationLoaded('project')) {
            $pitch->load('project');
        }
        
        // If project is still null, log error and use project_id directly
        if (!$pitch->project && $pitch->project_id) {
            Log::error('Project relationship is null for pitch in editUrl', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id
            ]);
            
            // Find the project directly using the project_id
            $project = \App\Models\Project::find($pitch->project_id);
            
            if (!$project) {
                Log::error('Could not find project for pitch in editUrl', [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id
                ]);
                throw new \Exception("Missing required parameter for [Route: projects.pitches.edit] [URI: projects/{project}/pitches/{pitch}/edit] [Missing parameter: project]");
            }
            
            return route('projects.pitches.edit', [
                'project' => $project, 
                'pitch' => $pitch
            ]);
        } elseif (!$pitch->project) {
            // If there's no project and no project_id, we can't generate the URL
            Log::error('No project or project_id found for pitch in editUrl', [
                'pitch_id' => $pitch->id
            ]);
            throw new \Exception("Missing required parameter for [Route: projects.pitches.edit] [URI: projects/{project}/pitches/{pitch}/edit] [Missing parameter: project]");
        }
        
        try {
            return route('projects.pitches.edit', [
                'project' => $pitch->project, 
                'pitch' => $pitch
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating route in pitchEditUrl', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate the URL for the pitch payment overview page.
     *
     * @param Project $project The project model
     * @param Pitch $pitch The pitch model
     * @return string The generated URL
     * @throws \Exception If required parameters are missing or route generation fails.
     */
    public static function getPaymentOverviewUrl(Project $project, Pitch $pitch): string
    {
        if (!$project || !$project->slug) {
            Log::error('Invalid project object in getPaymentOverviewUrl', ['project_id' => $project?->id]);
            throw new \Exception("Missing required parameter: project");
        }
        if (!$pitch || !$pitch->slug) {
            Log::error('Invalid pitch object in getPaymentOverviewUrl', ['pitch_id' => $pitch?->id]);
            throw new \Exception("Missing required parameter: pitch");
        }

        try {
            return route('projects.pitches.payment.overview', [
                'project' => $project,
                'pitch' => $pitch
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating route in getPaymentOverviewUrl', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage()
            ]);
            throw $e; // Re-throw the exception
        }
    }
} 
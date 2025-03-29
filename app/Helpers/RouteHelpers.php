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
        $baseParams = [
            'project' => $pitch->project, 
            'pitch' => $pitch
        ];
        
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
        return route('projects.pitches.edit', [
            'project' => $pitch->project, 
            'pitch' => $pitch
        ]);
    }
} 
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Pitch;
use App\Exceptions\Pitch\InvalidStatusTransitionException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use App\Services\PitchWorkflowService;

class PitchService
{
    protected $pitchWorkflowService;
    
    public function __construct(PitchWorkflowService $pitchWorkflowService)
    {
        $this->pitchWorkflowService = $pitchWorkflowService;
    }
    
    /**
     * Update a pitch
     * 
     * @param Pitch $pitch
     * @param User $user
     * @param array $data
     * @return Pitch
     */
    public function updatePitch(Pitch $pitch, User $user, array $data)
    {
        if ($user->cannot('update', $pitch)) {
            throw new AuthorizationException('You are not authorized to update this pitch.');
        }
        
        $pitch->fill($data);
        $pitch->save();
        
        return $pitch;
    }
    
    /**
     * Change the status of a pitch
     * 
     * @param Pitch $pitch
     * @param User $user
     * @param string $newStatus
     * @param string|null $comment
     * @return Pitch
     */
    public function changePitchStatus(Pitch $pitch, User $user, string $newStatus, ?string $comment = null)
    {
        if ($user->cannot('update', $pitch)) {
            throw new AuthorizationException('You are not authorized to update this pitch status.');
        }
        
        // Determine direction based on current and target status
        $direction = $this->determineStatusChangeDirection($pitch->status, $newStatus);
        
        if ($direction) {
            $pitch->changeStatus($direction, $newStatus, $comment);
        } else {
            throw new InvalidStatusTransitionException(
                $pitch->status, 
                $newStatus, 
                'The transition between these statuses is not allowed.'
            );
        }
        
        return $pitch;
    }
    
    /**
     * Delete a pitch
     * 
     * @param Pitch $pitch
     * @param User $user
     * @return bool
     */
    public function deletePitch(Pitch $pitch, User $user)
    {
        if ($user->cannot('delete', $pitch)) {
            throw new AuthorizationException('You are not authorized to delete this pitch.');
        }
        
        // Add any logic needed before deleting (like file cleanup)
        return $pitch->delete();
    }
    
    /**
     * Determine the direction for a status change based on the pitch's status transitions
     * 
     * @param string $currentStatus
     * @param string $targetStatus
     * @return string|null 'forward', 'backward', or null if transition is not valid
     */
    private function determineStatusChangeDirection(string $currentStatus, string $targetStatus)
    {
        // Check forward transitions
        $forwardTransitions = Pitch::$transitions['forward'][$currentStatus] ?? null;
        
        if ($forwardTransitions) {
            if (is_array($forwardTransitions) && in_array($targetStatus, $forwardTransitions)) {
                return 'forward';
            } elseif ($forwardTransitions === $targetStatus) {
                return 'forward';
            }
        }
        
        // Check backward transitions
        $backwardTransitions = Pitch::$transitions['backward'][$currentStatus] ?? null;
        
        if ($backwardTransitions) {
            if (is_array($backwardTransitions) && in_array($targetStatus, $backwardTransitions)) {
                return 'backward';
            } elseif ($backwardTransitions === $targetStatus) {
                return 'backward';
            }
        }
        
        return null; // No valid transition found
    }
} 
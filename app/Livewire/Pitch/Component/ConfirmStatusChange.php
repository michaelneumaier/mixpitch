<?php

namespace App\Livewire\Pitch\Component;

use Livewire\Component;
use Masmerise\Toaster\Toaster;

/**
 * ConfirmStatusChange Component
 * 
 * Provides a confirmation dialog for pitch status changes to prevent accidental state transitions
 */
class ConfirmStatusChange extends Component
{
    public $pitch;
    public $showConfirmModal = false;
    public $pendingAction = null;
    public $confirmMessage = '';
    public $actionData = [];
    public $actionLabel = '';
    public $confirmButtonClass = '';
    
    protected $listeners = [
        'openConfirmDialog' => 'openConfirmationModal',
        'pitchStatusUpdated' => 'closeConfirmationModal'
    ];
    
    public function mount($pitch)
    {
        $this->pitch = $pitch;
    }
    
    /**
     * Open a confirmation modal for a status change action
     * 
     * @param string $action The action to confirm ('approve', 'deny', 'complete', 'cancel')
     * @param array $data Additional data needed for the action
     * @return void
     */
    public function openConfirmationModal($action, $data = [])
    {
        $this->pendingAction = $action;
        $this->actionData = $data;
        
        // Store the pitch ID if it's provided
        if (isset($data['pitch_id'])) {
            $this->actionData['pitch_id'] = $data['pitch_id'];
        }
        
        // Set confirmation messages and button styles based on the action
        switch ($action) {
            case 'approve':
                $this->confirmMessage = 'Are you sure you want to approve this pitch? This will mark the current snapshot as accepted.';
                $this->actionLabel = 'Approve';
                $this->confirmButtonClass = 'bg-green-600 hover:bg-green-700';
                break;
                
            case 'deny':
                $this->confirmMessage = 'Are you sure you want to deny this pitch? Please provide a reason for denial.';
                $this->actionLabel = 'Deny';
                $this->confirmButtonClass = 'bg-red-600 hover:bg-red-700';
                break;
                
            case 'requestChanges':
                $this->confirmMessage = 'What revisions would you like to request for this pitch? Please provide specific details to help the creator improve it.';
                $this->actionLabel = 'Request Revisions';
                $this->confirmButtonClass = 'bg-blue-600 hover:bg-blue-700';
                break;
                
            case 'complete':
                $this->confirmMessage = 'Are you sure you want to mark this pitch as completed? This action cannot be undone.';
                
                // Check if there are other approved pitches
                if (isset($data['hasOtherApprovedPitches']) && $data['hasOtherApprovedPitches']) {
                    $count = $data['otherApprovedPitchesCount'] ?? 0;
                    $this->confirmMessage = 'Are you sure you want to mark this pitch as completed? This will CLOSE ' . 
                        $count . ' other approved ' . ($count === 1 ? 'pitch' : 'pitches') . 
                        '. This action cannot be undone.';
                }
                
                $this->actionLabel = 'Complete';
                $this->confirmButtonClass = 'bg-success hover:bg-success/80';
                break;
                
            case 'cancel':
                $this->confirmMessage = 'Are you sure you want to cancel this pitch submission? This will revert the pitch to its previous state.';
                $this->actionLabel = 'Cancel';
                $this->confirmButtonClass = 'bg-gray-600 hover:bg-gray-700';
                break;
                
            default:
                $this->confirmMessage = 'Are you sure you want to proceed with this action?';
                $this->actionLabel = 'Confirm';
                $this->confirmButtonClass = 'bg-blue-600 hover:bg-blue-700';
        }
        
        $this->showConfirmModal = true;
    }
    
    /**
     * Close the confirmation modal
     * 
     * @return void
     */
    public function closeConfirmationModal()
    {
        $this->showConfirmModal = false;
        $this->pendingAction = null;
        $this->actionData = [];
    }
    
    /**
     * Confirm and execute the pending action
     * 
     * @return void
     */
    public function confirmAction()
    {
        if (!$this->pendingAction) {
            return;
        }
        
        // Validate inputs for actions that require them
        if (($this->pendingAction === 'deny' || $this->pendingAction === 'requestChanges') && empty($this->actionData['reason'])) {
            Toaster::error('Please provide a reason for ' . ($this->pendingAction === 'deny' ? 'denial' : 'requested changes') . '.');
            return;
        }
        
        // Execute the appropriate action based on the pending action
        switch ($this->pendingAction) {
            case 'approve':
                if (isset($this->actionData['snapshotId'])) {
                    $this->dispatch('confirmApproveSnapshot', $this->actionData['snapshotId']);
                }
                break;
                
            case 'deny':
                if (isset($this->actionData['snapshotId'])) {
                    // Make sure we're passing the reason correctly
                    $reason = $this->actionData['reason'] ?? '';
                    $this->dispatch('confirmDenySnapshot', $this->actionData['snapshotId'], $reason);
                }
                break;
                
            case 'requestChanges':
                if (isset($this->actionData['snapshotId'])) {
                    // Make sure we're passing the reason correctly
                    $reason = $this->actionData['reason'] ?? '';
                    $this->dispatch('confirmRequestChanges', $this->actionData['snapshotId'], $reason);
                }
                break;
                
            case 'complete':
                $feedback = $this->actionData['feedback'] ?? '';
                
                // Add logging for debugging
                \Log::info('ConfirmStatusChange: dispatching confirmCompletePitch', [
                    'feedback_length' => strlen($feedback),
                    'pitch_id' => $this->pitch->id
                ]);
                
                // Use a pitch-specific event name if we have a pitch ID
                if (isset($this->actionData['pitch_id'])) {
                    $pitchId = $this->actionData['pitch_id'];
                    $this->dispatch("confirmCompletePitch-{$pitchId}", $feedback);
                } else {
                    // Fallback to original pitch ID
                    $this->dispatch("confirmCompletePitch-{$this->pitch->id}", $feedback);
                }
                break;
                
            case 'cancel':
                $this->dispatch('confirmCancelSubmission')->to('pitch.component.update-pitch-status');
                break;
        }
        
        $this->closeConfirmationModal();
    }
    
    public function render()
    {
        return view('livewire.pitch.component.confirm-status-change');
    }
}

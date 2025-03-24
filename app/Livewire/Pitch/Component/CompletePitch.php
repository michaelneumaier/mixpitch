<?php

namespace App\Livewire\Pitch\Component;

use App\Exceptions\Pitch\InvalidStatusTransitionException;
use App\Exceptions\Pitch\UnauthorizedActionException;
use App\Exceptions\Pitch\SnapshotException;
use Livewire\Component;
use App\Models\Pitch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Masmerise\Toaster\Toaster;
use App\Services\NotificationService;
use App\Models\Project;
use App\Models\PitchFeedback;

class CompletePitch extends Component
{
    public $pitch;
    public $feedback = '';
    public $errors = [];
    public $hasOtherApprovedPitches = false;
    public $otherApprovedPitchesCount = 0;
    public $showCompletionModal = false;
    
    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;
        $this->checkForOtherApprovedPitches();
    }
    
    /**
     * Check if there are other approved pitches for this project
     */
    protected function checkForOtherApprovedPitches()
    {
        $this->otherApprovedPitchesCount = $this->pitch->project->pitches()
            ->where('status', Pitch::STATUS_APPROVED)
            ->where('id', '!=', $this->pitch->id)
            ->count();
            
        $this->hasOtherApprovedPitches = $this->otherApprovedPitchesCount > 0;
    }
    
    /**
     * Check if the user is authorized to complete the pitch
     *
     * @throws UnauthorizedActionException
     * @return bool
     */
    public function isAuthorized()
    {
        $isAuthorized = Auth::check() && 
               $this->pitch->project->user_id === Auth::id() && 
               $this->pitch->status === Pitch::STATUS_APPROVED;
               
        if (!$isAuthorized) {
            throw new UnauthorizedActionException(
                'complete',
                'You are not authorized to complete this pitch'
            );
        }
        
        return true;
    }
    
    /**
     * Open the completion modal
     */
    public function openCompletionModal()
    {
        try {
            // Log that the method was called
            \Log::info('openCompletionModal called', [
                'pitch_id' => $this->pitch->id,
                'current_modal_state' => $this->showCompletionModal
            ]);
            
            // Check if the user is authorized
            $this->isAuthorized();
            
            // Check if the pitch can be completed
            $this->pitch->canComplete();
            
            // Update our local properties based on what canComplete() found
            $this->checkForOtherApprovedPitches();
            
            // Show the modal directly - ensure we're setting this to true
            $this->showCompletionModal = true;
            
            // Log the final state
            \Log::info('Modal should be open now', [
                'modal_state' => $this->showCompletionModal
            ]);
            
        } catch (UnauthorizedActionException $e) {
            Toaster::error($e->getMessage());
            Log::error('Unauthorized attempt to complete pitch', [
                'pitch_id' => $this->pitch->id,
                'user_id' => Auth::id() ?? 'unauthenticated',
                'error' => $e->getMessage()
            ]);
        } catch (InvalidStatusTransitionException|SnapshotException $e) {
            Toaster::error($e->getMessage());
            Log::error('Invalid pitch completion attempt', [
                'pitch_id' => $this->pitch->id,
                'status' => $this->pitch->status,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            Toaster::error('An unexpected error occurred');
            Log::error('Error in pitch completion request', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Close the completion modal
     */
    public function closeCompletionModal()
    {
        $this->showCompletionModal = false;
    }
    
    /**
     * Complete a pitch directly (used by the button in the modal)
     */
    public function debugComplete()
    {
        \Log::info('CompletePitch::debugComplete called', [
            'pitch_id' => $this->pitch->id
        ]);
        
        try {
            // Call the actual completion method with the current feedback
            $result = $this->completePitch($this->feedback);
            
            // Close the modal when done
            $this->closeCompletionModal();
            
            if ($result) {
                // Set initial payment status for the pitch
                if ($this->pitch->project->budget == 0) {
                    // If it's a free project, mark it as not requiring payment
                    $this->pitch->payment_status = Pitch::PAYMENT_STATUS_NOT_REQUIRED;
                } else {
                    // Otherwise, mark it as pending payment
                    $this->pitch->payment_status = Pitch::PAYMENT_STATUS_PENDING;
                }
                $this->pitch->save();
                
                // For paid projects, dispatch an event to open the payment modal
                if ($this->pitch->project->budget > 0) {
                    $this->dispatch('openPaymentModal');
                }
                
                // Redirect back to the manage project page if successful
                return redirect()->route('projects.manage', $this->pitch->project);
            }
        } catch (\Exception $e) {
            Log::error('Error in debugComplete', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Close the modal and show error
            $this->closeCompletionModal();
            Toaster::error('An error occurred while completing the pitch: ' . $e->getMessage());
        }
    }
    
    /**
     * Complete a pitch
     *
     * @param string $feedback Optional feedback about the completed pitch
     * @return bool Whether completion was successful
     */
    public function completePitch($feedback = '')
    {
        \Log::info('Starting pitch completion process', [
            'pitch_id' => $this->pitch->id,
            'has_other_approved' => $this->hasOtherApprovedPitches,
            'other_approved_count' => $this->otherApprovedPitchesCount
        ]);
        
        try {
            // Step 1: Validate permissions and state
            $this->validateCompletionRequirements();
            
            // Step 2: Mark this pitch as completed (in its own transaction)
            $this->markAsCompleted($feedback);
            
            // Step 3: Close other active pitches (in its own transaction)
            $this->closeOtherPitches();
            
            // Step 4: Mark the project as completed (in its own transaction)
            $this->markProjectAsCompleted();
            
            // Step 5: Send notifications (non-critical, don't fail if this errors)
            $this->sendCompletionNotifications($feedback);
            
            // Success!
            $this->dispatch('pitchStatusUpdated');
            Toaster::success('Pitch has been completed successfully!');
            
            return true;
        } catch (UnauthorizedActionException $e) {
            Toaster::error($e->getMessage());
            Log::error('Unauthorized attempt to complete pitch', [
                'pitch_id' => $this->pitch->id,
                'user_id' => Auth::id() ?? 'unauthenticated',
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (InvalidStatusTransitionException|SnapshotException $e) {
            Toaster::error($e->getMessage());
            Log::error('Invalid pitch completion attempt', [
                'pitch_id' => $this->pitch->id,
                'status' => $this->pitch->status,
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (\Exception $e) {
            Toaster::error('An unexpected error occurred');
            Log::error('Error in pitch completion', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }
    
    /**
     * Validate that all requirements for pitch completion are met
     */
    protected function validateCompletionRequirements()
    {
        // Check if the user is authorized
        $this->isAuthorized();
        
        // Check if the pitch can be completed
        $this->pitch->canComplete();
        
        // Make sure we have the latest data
        $this->pitch->refresh();
        $this->checkForOtherApprovedPitches();
        
        \Log::info('Pitch completion requirements validated', [
            'pitch_id' => $this->pitch->id,
            'has_other_approved' => $this->hasOtherApprovedPitches,
            'other_approved_count' => $this->otherApprovedPitchesCount
        ]);
    }
    
    /**
     * Mark this pitch as completed (Step 2)
     */
    protected function markAsCompleted($feedback)
    {
        DB::beginTransaction();
        
        try {
            // Update pitch status
            $this->pitch->update([
                'status' => Pitch::STATUS_COMPLETED,
                'completed_at' => now(),
                'payment_status' => $this->isFreeProject() ? Pitch::PAYMENT_STATUS_PAID : Pitch::PAYMENT_STATUS_PENDING,
            ]);
            
            // If this is a free project, mark as paid immediately with no payment required
            if ($this->isFreeProject()) {
                $this->pitch->update([
                    'final_invoice_id' => 'free_project',
                    'payment_amount' => 0,
                    'payment_completed_at' => now(),
                ]);
            }
            
            // Process the feedback if it exists
            if ($feedback) {
                PitchFeedback::create([
                    'pitch_id' => $this->pitch->id,
                    'user_id' => auth()->id(),
                    'content' => $feedback,
                    'type' => 'completion',
                ]);
            }
            
            // Send notifications
            $notificationService = app(NotificationService::class);
            
            // Notify the pitch creator that their pitch was completed
            $notificationService->notifyPitchCompleted(
                $this->pitch,
                'Your pitch for ' . $this->pitch->project->name . ' has been marked as completed.'
            );
            
            // Notify project owner if pitch creator completed the pitch
            if (auth()->id() === $this->pitch->user_id && auth()->id() !== $this->pitch->project->user_id) {
                $notificationService->notifyPitchCompleted(
                    $this->pitch,
                    $this->pitch->user->name . ' marked their pitch for ' . $this->pitch->project->name . ' as completed.'
                );
            }
            
            DB::commit();
            
            // Session message
            session()->flash('success', 'The pitch has been marked as completed.');
            
            // Reload the page to show the completed state
            return redirect()->route('pitches.show', $this->pitch);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error marking pitch as completed: ' . $e->getMessage(), [
                'pitch_id' => $this->pitch->id,
                'exception' => $e,
            ]);
            
            // Show error message
            Toaster::error('There was an error completing the pitch. Please try again.');
        }
        
        $this->showCompletionModal = false;
    }
    
    protected function isFreeProject()
    {
        return (int) $this->pitch->project->budget === 0;
    }
    
    /**
     * Close other active pitches (Step 3)
     */
    protected function closeOtherPitches()
    {
        DB::beginTransaction();
        try {
            // Re-get the project to ensure it's fresh
            $project = Project::find($this->pitch->project_id);
            
            // Get IDs of pitches to be closed for logging
            $pitchesToClose = $project->pitches()
                ->where('id', '!=', $this->pitch->id)
                ->whereNotIn('status', [Pitch::STATUS_CLOSED, Pitch::STATUS_COMPLETED])
                ->pluck('id');
                
            \Log::info('About to close other pitches', [
                'pitch_being_completed' => $this->pitch->id,
                'pitches_to_close' => $pitchesToClose,
                'other_approved_count' => $this->otherApprovedPitchesCount
            ]);
            
            // Update the pitches directly in the database to avoid race conditions
            $updateResult = DB::table('pitches')
                ->where('project_id', $project->id)
                ->where('id', '!=', $this->pitch->id)
                ->whereNotIn('status', [Pitch::STATUS_CLOSED, Pitch::STATUS_COMPLETED])
                ->update(['status' => Pitch::STATUS_CLOSED]);
                
            \Log::info('Closed other pitches', [
                'update_result' => $updateResult
            ]);
            
            // Refresh to get updated data
            $project->refresh();
            
            // Process the closed pitches for events and snapshots
            foreach ($project->pitches()->where('id', '!=', $this->pitch->id)->get() as $otherPitch) {
                if ($otherPitch->status === Pitch::STATUS_CLOSED) {
                    // Create a status change event
                    $otherPitch->events()->create([
                        'event_type' => 'status_change',
                        'comment' => 'Pitch automatically closed because another pitch was completed',
                        'status' => Pitch::STATUS_CLOSED,
                        'created_by' => auth()->id(),
                    ]);
                    
                    // Find and decline pending snapshots
                    $pendingSnapshots = $otherPitch->snapshots()->where('status', 'pending')->get();
                    foreach ($pendingSnapshots as $pendingSnapshot) {
                        $pendingSnapshot->status = 'denied';
                        $pendingSnapshot->save();
                        
                        // Create an event for the declined snapshot
                        $otherPitch->events()->create([
                            'event_type' => 'snapshot_status_change',
                            'comment' => 'Snapshot automatically declined because the pitch was closed',
                            'status' => 'denied',
                            'snapshot_id' => $pendingSnapshot->id,
                            'created_by' => auth()->id(),
                        ]);
                    }
                }
            }
            
            DB::commit();
            \Log::info('Other pitches closed successfully', [
                'pitch_id' => $this->pitch->id,
                'closed_count' => $updateResult
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error closing other pitches', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage()
            ]);
            throw $e; // Re-throw to be handled by the calling method
        }
    }
    
    /**
     * Mark the project as completed (Step 4)
     */
    protected function markProjectAsCompleted()
    {
        DB::beginTransaction();
        try {
            // Re-get the project to ensure it's fresh
            $project = Project::find($this->pitch->project_id);
            
            // Mark as completed
            $project->markAsCompleted($this->pitch->id);
            
            DB::commit();
            \Log::info('Project marked as completed successfully', [
                'project_id' => $project->id,
                'pitch_id' => $this->pitch->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error marking project as completed', [
                'project_id' => $this->pitch->project_id,
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage()
            ]);
            throw $e; // Re-throw to be handled by the calling method
        }
    }
    
    /**
     * Send completion notifications (Step 5)
     */
    protected function sendCompletionNotifications($feedback)
    {
        try {
            $notificationService = app(NotificationService::class);
            $notificationService->notifyPitchCompleted($this->pitch, $feedback);
            
            \Log::info('Completion notifications sent', [
                'pitch_id' => $this->pitch->id
            ]);
        } catch (\Exception $e) {
            // Log notification error but don't fail the process
            Log::error('Failed to create pitch completion notification', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function render()
    {
        \Log::info('CompletePitch::render', [
            'pitch_id' => $this->pitch->id,
            'showCompletionModal' => $this->showCompletionModal,
            'hasOtherApprovedPitches' => $this->hasOtherApprovedPitches
        ]);
        
        return view('livewire.pitch.component.complete-pitch');
    }
}

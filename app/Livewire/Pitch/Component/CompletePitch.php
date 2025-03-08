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

class CompletePitch extends Component
{
    public $pitch;
    public $feedback = '';
    public $errors = [];
    
    protected $listeners = [
        'confirmCompletePitch' => 'completePitch'
    ];
    
    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;
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
     * Request completion of a pitch (opens confirmation dialog)
     */
    public function requestCompletion()
    {
        try {
            // Check if the user is authorized
            $this->isAuthorized();
            
            // Check if the pitch can be completed
            $this->pitch->canComplete();
            
            $this->dispatch('openConfirmDialog', 'complete', ['feedback' => $this->feedback]);
        } catch (UnauthorizedActionException $e) {
            session()->flash('error', $e->getMessage());
            Log::error('Unauthorized attempt to complete pitch', [
                'pitch_id' => $this->pitch->id,
                'user_id' => Auth::id() ?? 'unauthenticated',
                'error' => $e->getMessage()
            ]);
        } catch (InvalidStatusTransitionException|SnapshotException $e) {
            session()->flash('error', $e->getMessage());
            Log::error('Invalid pitch completion attempt', [
                'pitch_id' => $this->pitch->id,
                'status' => $this->pitch->status,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'An unexpected error occurred');
            Log::error('Error in pitch completion request', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Complete a pitch
     *
     * @param string $feedback Optional feedback about the completed pitch
     * @return void
     */
    public function completePitch($feedback = '')
    {
        try {
            // Check if the user is authorized
            $this->isAuthorized();
            
            // Check if the pitch can be completed
            $this->pitch->canComplete();
            
            // Begin a database transaction to ensure all changes are atomic
            DB::beginTransaction();
            
            // Save the feedback for future reference if provided
            if (!empty($feedback)) {
                $this->pitch->addComment('Completion feedback: ' . $feedback);
            }
            
            $oldStatus = $this->pitch->status;
            
            // Update the pitch status
            $this->pitch->changeStatus('forward', Pitch::STATUS_COMPLETED);
            
            // Find the latest approved snapshot and mark it as the final version
            $latestApprovedSnapshot = $this->pitch->snapshots()
                ->where('status', 'accepted')
                ->orderBy('created_at', 'desc')
                ->first();
                
            if ($latestApprovedSnapshot) {
                // Create a completion event that links to the snapshot
                $this->pitch->events()->create([
                    'event_type' => 'snapshot_status_change',
                    'comment' => 'Pitch has been completed with snapshot version ' . 
                        $latestApprovedSnapshot->snapshot_data['version'],
                    'status' => Pitch::STATUS_COMPLETED,
                    'snapshot_id' => $latestApprovedSnapshot->id,
                    'created_by' => auth()->id(),
                ]);
            }
            
            // Close all other active pitches for this project
            $project = $this->pitch->project;
            $project->pitches()
                ->where('id', '!=', $this->pitch->id)
                ->whereNotIn('status', [Pitch::STATUS_CLOSED, Pitch::STATUS_COMPLETED])
                ->update(['status' => Pitch::STATUS_CLOSED]);
            
            // Decline any pending snapshots for the closed pitches
            foreach ($project->pitches as $otherPitch) {
                if ($otherPitch->id !== $this->pitch->id && $otherPitch->status === Pitch::STATUS_CLOSED) {
                    // Create a status change event for each pitch that was closed
                    $otherPitch->events()->create([
                        'event_type' => 'status_change',
                        'comment' => 'Pitch automatically closed because another pitch was completed',
                        'status' => Pitch::STATUS_CLOSED,
                        'created_by' => auth()->id(),
                    ]);
                    
                    // Find any pending snapshots for this pitch and decline them
                    $pendingSnapshots = $otherPitch->snapshots()->where('status', 'pending')->get();
                    foreach ($pendingSnapshots as $pendingSnapshot) {
                        $pendingSnapshot->status = 'denied';
                        $pendingSnapshot->save();
                        
                        // Create an event for each declined snapshot
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
            
            // Mark the project as completed
            $project->markAsCompleted($this->pitch->id);
            
            // Send completion notification
            try {
                $notificationService = app(NotificationService::class);
                $notificationService->notifyPitchCompleted($this->pitch, $feedback);
            } catch (\Exception $e) {
                // Log notification error but don't fail the request
                Log::error('Failed to create pitch completion notification: ' . $e->getMessage());
            }
            
            // Commit all the database changes
            DB::commit();
            
            $this->dispatch('pitchStatusUpdated');
            session()->flash('message', 'Pitch has been completed successfully!');
            
            // Redirect back to the manage project page
            return redirect()->route('projects.manage', $this->pitch->project);
            
        } catch (UnauthorizedActionException $e) {
            DB::rollBack();
            session()->flash('error', $e->getMessage());
            Log::error('Unauthorized attempt to complete pitch', [
                'pitch_id' => $this->pitch->id,
                'user_id' => Auth::id() ?? 'unauthenticated',
                'error' => $e->getMessage()
            ]);
        } catch (InvalidStatusTransitionException|SnapshotException $e) {
            DB::rollBack();
            session()->flash('error', $e->getMessage());
            Log::error('Invalid pitch completion attempt', [
                'pitch_id' => $this->pitch->id,
                'status' => $this->pitch->status,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'An unexpected error occurred');
            Log::error('Error in pitch completion', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    public function render()
    {
        return view('livewire.pitch.component.complete-pitch');
    }
}

<?php

namespace App\Livewire\Pitch;

use App\Exceptions\Pitch\CompletionValidationException;
use App\Exceptions\Pitch\UnauthorizedActionException;
use App\Models\Pitch;
use App\Services\PitchCompletionService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CompletePitch extends Component
{
    public Pitch $pitch;

    public $feedback = '';

    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;
    }

    public function completePitch()
    {
        // Authorization check
        $this->authorize('complete', $this->pitch);

        try {
            // Get the service
            $completionService = app(PitchCompletionService::class);

            // Complete the pitch
            $completedPitch = $completionService->completePitch(
                $this->pitch,
                auth()->user(),
                $this->feedback
            );

            // Emit event for UI updates
            $this->dispatch('pitch-completed', [
                'pitchId' => $completedPitch->id,
                'isPaid' => $completedPitch->payment_status === Pitch::PAYMENT_STATUS_PENDING,
            ]);

            // Show success message
            session()->flash('success', 'Pitch marked as completed.');

            // Open payment modal for paid projects
            if ($completedPitch->payment_status === Pitch::PAYMENT_STATUS_PENDING) {
                $this->dispatch('openPaymentModal', [
                    'pitchId' => $completedPitch->id,
                    'amount' => $completedPitch->payment_amount ?? 0,
                ]);
            }

            // Refresh pitch data
            $this->pitch = $completedPitch;

        } catch (UnauthorizedActionException $e) {
            $this->addError('authorization', $e->getMessage());
            session()->flash('error', 'You are not authorized to complete this pitch.');
        } catch (CompletionValidationException $e) {
            $this->addError('completion', $e->getMessage());
            session()->flash('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error completing pitch', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->addError('unexpected', 'An unexpected error occurred. Please try again.');
            session()->flash('error', 'An unexpected error occurred while completing the pitch.');
        }
    }

    public function render()
    {
        return view('livewire.pitch.complete-pitch');
    }
}

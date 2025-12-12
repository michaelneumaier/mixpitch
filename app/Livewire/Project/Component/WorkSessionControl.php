<?php

namespace App\Livewire\Project\Component;

use App\Models\Pitch;
use App\Models\Project;
use App\Models\WorkSession;
use App\Services\WorkSessionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class WorkSessionControl extends Component
{
    public Project $project;

    public Pitch $pitch;

    public ?WorkSession $activeSession = null;

    public bool $isVisibleToClient = true;

    public bool $focusMode = false;

    public string $sessionNotes = '';

    public string $variant = 'embedded'; // 'embedded' or 'header'

    protected WorkSessionService $sessionService;

    public function boot(WorkSessionService $sessionService): void
    {
        $this->sessionService = $sessionService;
    }

    public function mount(Project $project, Pitch $pitch, string $variant = 'embedded'): void
    {
        $this->project = $project;
        $this->pitch = $pitch;
        $this->variant = $variant;
        $this->loadActiveSession();
    }

    public function loadActiveSession(): void
    {
        $this->activeSession = $this->sessionService->getActiveSession(
            $this->pitch,
            Auth::user()
        );

        if ($this->activeSession) {
            $this->isVisibleToClient = $this->activeSession->is_visible_to_client;
            $this->focusMode = $this->activeSession->focus_mode;
            $this->sessionNotes = $this->activeSession->notes ?? '';
        }
    }

    /**
     * Start a new work session
     */
    public function startSession(): void
    {
        try {
            $this->activeSession = $this->sessionService->startSession(
                $this->pitch,
                Auth::user(),
                $this->isVisibleToClient,
                $this->focusMode
            );

            Toaster::success('Work session started');
            $this->dispatch('session-started');

        } catch (\Exception $e) {
            Toaster::error('Failed to start session');
        }
    }

    /**
     * Pause the current session
     */
    public function pauseSession(): void
    {
        if (! $this->activeSession) {
            return;
        }

        try {
            $this->activeSession = $this->sessionService->pauseSession($this->activeSession);
            Toaster::success('Session paused');
            $this->dispatch('session-paused');

        } catch (\Exception $e) {
            Toaster::error('Failed to pause session');
        }
    }

    /**
     * Resume a paused session
     */
    public function resumeSession(): void
    {
        if (! $this->activeSession) {
            return;
        }

        try {
            $this->activeSession = $this->sessionService->resumeSession($this->activeSession);
            Toaster::success('Session resumed');
            $this->dispatch('session-resumed');

        } catch (\Exception $e) {
            Toaster::error('Failed to resume session');
        }
    }

    /**
     * End the current session
     */
    public function endSession(): void
    {
        if (! $this->activeSession) {
            return;
        }

        try {
            $this->sessionService->endSession($this->activeSession);
            $this->activeSession = null;
            $this->sessionNotes = '';
            Toaster::success('Session ended');
            $this->dispatch('session-ended');

        } catch (\Exception $e) {
            Toaster::error('Failed to end session');
        }
    }

    /**
     * Toggle focus mode
     */
    public function toggleFocusMode(): void
    {
        if (! $this->activeSession) {
            $this->focusMode = ! $this->focusMode;

            return;
        }

        try {
            $this->activeSession = $this->sessionService->toggleFocusMode($this->activeSession);
            $this->focusMode = $this->activeSession->focus_mode;

            $status = $this->focusMode ? 'enabled' : 'disabled';
            Toaster::success("Focus mode {$status}");

        } catch (\Exception $e) {
            Toaster::error('Failed to toggle focus mode');
        }
    }

    /**
     * Toggle visibility to client
     */
    public function toggleVisibility(): void
    {
        if (! $this->activeSession) {
            $this->isVisibleToClient = ! $this->isVisibleToClient;

            return;
        }

        try {
            $this->activeSession = $this->sessionService->toggleVisibility($this->activeSession);
            $this->isVisibleToClient = $this->activeSession->is_visible_to_client;

        } catch (\Exception $e) {
            Toaster::error('Failed to toggle visibility');
        }
    }

    /**
     * Save session notes
     */
    public function saveNotes(): void
    {
        if (! $this->activeSession) {
            return;
        }

        try {
            $this->activeSession = $this->sessionService->updateNotes(
                $this->activeSession,
                $this->sessionNotes
            );
            Toaster::success('Notes saved');
            $this->dispatch('session-notes-updated');

        } catch (\Exception $e) {
            Toaster::error('Failed to save notes');
        }
    }

    /**
     * Get the current session duration (for display)
     */
    public function getSessionDuration(): string
    {
        if (! $this->activeSession) {
            return '0m';
        }

        return $this->activeSession->getFormattedDuration();
    }

    /**
     * Get total work time for this pitch
     */
    public function getTotalWorkTime(): string
    {
        return $this->sessionService->getFormattedTotalWorkTime($this->pitch);
    }

    /**
     * Refresh session data from external events
     */
    #[On('refresh-work-session')]
    #[On('session-started')]
    #[On('session-paused')]
    #[On('session-resumed')]
    #[On('session-ended')]
    #[On('session-notes-updated')]
    public function refreshSession(): void
    {
        $this->loadActiveSession();
    }

    public function render()
    {
        return view('livewire.project.component.work-session-control', [
            'duration' => $this->getSessionDuration(),
            'totalTime' => $this->getTotalWorkTime(),
        ]);
    }
}

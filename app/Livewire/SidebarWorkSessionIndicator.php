<?php

namespace App\Livewire;

use App\Models\WorkSession;
use App\Services\WorkSessionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class SidebarWorkSessionIndicator extends Component
{
    public ?WorkSession $activeSession = null;

    public function mount(): void
    {
        $this->loadActiveSession();
    }

    #[On('session-started')]
    #[On('session-paused')]
    #[On('session-resumed')]
    #[On('session-ended')]
    public function loadActiveSession(): void
    {
        if (! Auth::check()) {
            $this->activeSession = null;

            return;
        }

        $this->activeSession = app(WorkSessionService::class)
            ->getUserActiveSession(Auth::user());
    }

    public function render()
    {
        return view('livewire.sidebar-work-session-indicator');
    }
}

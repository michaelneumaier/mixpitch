<?php

namespace App\Livewire;

use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ClientSelectionModal extends Component
{
    public ?int $selectedClientId = null;

    public array $clients = [];

    protected $listeners = [
        'openClientSelectionModal' => 'openModal',
    ];

    public function mount()
    {
        $this->loadClients();
    }

    protected function loadClients(): void
    {
        $this->clients = Client::where('user_id', Auth::id())
            ->orderByRaw('COALESCE(NULLIF(name, ""), email) asc')
            ->get(['id', 'name', 'email'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'label' => $c->name ? ($c->name.' — '.$c->email) : $c->email,
            ])->toArray();
    }

    public function openModal(): void
    {
        $this->selectedClientId = null;
        $this->dispatch('modal-show', name: 'client-selection');
    }

    public function updatedSelectedClientId($value): void
    {
        if ($value) {
            $this->createProjectWithClient((int) $value);
        }
    }

    public function createProjectWithClient(int $clientId)
    {
        $client = Client::where('user_id', Auth::id())->findOrFail($clientId);

        // Close modal
        $this->dispatch('modal-close', name: 'client-selection');

        // Redirect to create project with client pre-filled
        return redirect()->route('projects.create', [
            'workflow_type' => 'client_management',
            'client_email' => $client->email,
            'client_name' => $client->name,
        ]);
    }

    public function createProjectWithoutClient()
    {
        // Close modal
        $this->dispatch('modal-close', name: 'client-selection');

        // Redirect to create project without client pre-filled
        return redirect()->route('projects.create', [
            'workflow_type' => 'client_management',
        ]);
    }

    public function render()
    {
        return view('livewire.client-selection-modal');
    }
}

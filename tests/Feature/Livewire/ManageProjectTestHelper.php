<?php

namespace Tests\Feature\Livewire;

use Livewire\Component;
use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use App\Livewire\Forms\ProjectForm;

class ManageProjectTestHelper extends Component
{
    public Project $project;
    public ProjectForm $form;
    
    public $hasPreviewTrack = false;
    public $audioUrl;
    
    // Storage tracking vars
    public $storageUsedPercentage = 0;
    public $storageLimitMessage = '';
    public $storageRemaining = 0;
    
    // Simplified mount method for testing
    public function mount(Project $project)
    {
        try {
            // Still perform auth check
            $this->authorize('update', $project);
        } catch (AuthorizationException $e) {
            abort(403, 'You are not authorized to manage this project.');
        }

        $this->project = $project;
        
        // Initialize form with minimal setup
        $this->form = new ProjectForm($this, 'form');
        
        // Simple values instead of complex calculations
        $this->storageUsedPercentage = 0;
        $this->storageLimitMessage = '100MB available';
        $this->storageRemaining = 104857600; // 100MB
    }

    public function render()
    {
        return <<<'BLADE'
        <div>
            <h1>{{ $project->name }}</h1>
            <p>{{ $project->description }}</p>
        </div>
        BLADE;
    }
} 
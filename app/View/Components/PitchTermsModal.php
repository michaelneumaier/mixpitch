<?php

namespace App\View\Components;

use App\Models\Project;
use Illuminate\View\Component;

class PitchTermsModal extends Component
{
    /**
     * The project to create a pitch for.
     *
     * @var \App\Models\Project
     */
    public $project;

    /**
     * Create a new component instance.
     *
     * @param \App\Models\Project $project
     * @return void
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.pitch-terms-modal');
    }
}

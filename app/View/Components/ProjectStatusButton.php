<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ProjectStatusButton extends Component
{
    public $status;

    public $type;

    /**
     * Create a new component instance.
     */
    public function __construct($status, $type)
    {
        $this->status = $status;
        $this->type = $type;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.project-status-button');
    }
}

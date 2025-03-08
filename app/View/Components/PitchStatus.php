<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Models\Pitch;

class PitchStatus extends Component
{
    public $status;
    public $bgColor;
    public $textColor;

    /**
     * Create a new component instance.
     */
    public function __construct($status)
    {
        $this->status = $status;
        $this->setColors();
    }

    /**
     * Set colors based on the status.
     */
    private function setColors()
    {
        switch ($this->status) {
            case Pitch::STATUS_PENDING:
                $this->bgColor = 'bg-gray-200';
                $this->textColor = 'text-gray-900';
                break;
            case Pitch::STATUS_IN_PROGRESS:
                $this->bgColor = 'bg-blue-200';
                $this->textColor = 'text-blue-900';
                break;
            case Pitch::STATUS_READY_FOR_REVIEW:
            case Pitch::STATUS_PENDING_REVIEW:
                $this->bgColor = 'bg-yellow-200';
                $this->textColor = 'text-yellow-900';
                break;
            case Pitch::STATUS_APPROVED:
                $this->bgColor = 'bg-green-200';
                $this->textColor = 'text-green-900';
                break;
            case Pitch::STATUS_DENIED:
                $this->bgColor = 'bg-red-200';
                $this->textColor = 'text-red-900';
                break;
            case Pitch::STATUS_COMPLETED:
                $this->bgColor = 'bg-purple-200';
                $this->textColor = 'text-purple-900';
                break;
            case Pitch::STATUS_CLOSED:
                $this->bgColor = 'bg-gray-300';
                $this->textColor = 'text-gray-700';
                break;
            default:
                $this->bgColor = 'bg-gray-200';
                $this->textColor = 'text-gray-900';
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view(view: 'components.pitch-status');
    }
}

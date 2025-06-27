<?php

namespace App\View\Components;

use App\Models\User;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DateTime extends Component
{
    public function __construct(
        public Carbon $date,
        public ?string $format = null,
        public ?string $class = '',
        public bool $relative = false,
        public bool $tooltip = true,
        public ?User $user = null,
        public bool $convertToViewer = false
    ) {}

    public function render(): View|Closure|string
    {
        // Temporarily simplify to debug the issue
        $formattedDate = $this->date->format($this->format ?? 'M d, Y g:i A');
        $relativeDate = $this->date->diffForHumans();
        $isoDate = $this->date->toISOString();

        return view('components.datetime', [
            'formattedDate' => $formattedDate,
            'relativeDate' => $relativeDate,
            'isoDate' => $isoDate,
            'class' => $this->class,
            'tooltip' => $this->tooltip,
            'relative' => $this->relative,
            'date' => $this->date,
        ]);
    }
    
    public function getFormattedDate(): string
    {
        $service = app(TimezoneService::class);
        
        if ($this->convertToViewer) {
            $targetUser = auth()->user();
        } else {
            $targetUser = $this->user ?? auth()->user();
        }
        
        return $service->formatForUser($this->date, $targetUser, $this->format);
    }
    
    public function getRelativeDate(): string
    {
        $service = app(TimezoneService::class);
        
        if ($this->convertToViewer) {
            $targetUser = auth()->user();
        } else {
            $targetUser = $this->user ?? auth()->user();
        }
        
        $userDate = $service->convertToUserTimezone($this->date, $targetUser);
        return $userDate->diffForHumans();
    }
    
    public function getIsoDate(): string
    {
        $service = app(TimezoneService::class);
        
        if ($this->convertToViewer) {
            $targetUser = auth()->user();
        } else {
            $targetUser = $this->user ?? auth()->user();
        }
        
        return $service->convertToUserTimezone($this->date, $targetUser)->toISOString();
    }
} 
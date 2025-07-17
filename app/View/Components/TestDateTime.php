<?php

namespace App\View\Components;

use Carbon\Carbon;
use Illuminate\View\Component;

class TestDateTime extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public Carbon $date,
        public string $testValue = 'test-default'
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.test-datetime', [
            'computedValue' => 'computed-'.$this->testValue,
            'isoDate' => $this->date->toISOString(),
            'testValue' => $this->testValue,
        ]);
    }
}

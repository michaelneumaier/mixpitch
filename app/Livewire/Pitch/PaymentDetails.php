<?php

namespace App\Livewire\Pitch;

use App\Models\Pitch;
use Livewire\Component;

class PaymentDetails extends Component
{
    public Pitch $pitch;

    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;
    }

    public function render()
    {
        return view('livewire.pitch.payment-details');
    }
}

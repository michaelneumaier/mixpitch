<?php

namespace App\Livewire;

use App\Models\Mix;
use Livewire\Component;

class StarRating extends Component
{
    public int $rating;
    public $mix;

    public function mount($rating, $mix)
    {
        $this->rating = $rating;
        $this->mix = $mix;
    }

    public function setRating($rating)
    {
        $this->rating = $rating;
        $this->mix->update(rating: $this->rating);

        // You can save to the database here or do other tasks
        // For example:
        // Mix::find($this->mixId)->update(['rating' => $this->rating]);

        // Trigger an event or show a message if needed
        // $this->dispatch('ratingUpdated', $this->rating);
    }

    public function render()
    {
        return view('livewire.star-rating');
    }
}


@props(['rating' => 0])

@php
    $numericRating = is_numeric($rating) ? (int) round($rating) : 0; // Round to nearest whole number
    $fullStars = max(0, min(5, $numericRating)); // Ensure rating is between 0 and 5
    $emptyStars = 5 - $fullStars;
@endphp

@if($fullStars > 0)
<div class="flex items-center">
    <div class="rating rating-sm mr-1">
        @for ($i = 0; $i < $fullStars; $i++)
            <input type="radio" name="rating-{{ rand() }}" class="mask mask-star-2 bg-orange-400" disabled />
        @endfor
        @for ($i = 0; $i < $emptyStars; $i++)
             <input type="radio" name="rating-{{ rand() }}" class="mask mask-star-2 bg-gray-300" disabled />
        @endfor
    </div>
    <span class="text-xs text-gray-600">({{ $fullStars }} / 5)</span>
</div>
@else
    <span class="text-xs text-gray-500 italic">Not Rated</span>
@endif 
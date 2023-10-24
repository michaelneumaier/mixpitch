<div class="rating">
    @for ($i = 1; $i <= 10; $i++) <input type="radio" name="star" class="hidden" id="star-{{$i}}" value="{{$i}}"
        wire:model.live="rating" wire:click="setRating({{$i}})">
        <label for="star-{{$i}}"
            class="cursor-pointer text-2xl {{ $rating >= $i ? 'text-yellow-400' : 'text-gray-300' }}">&#9733;</label>
        @endfor
</div>
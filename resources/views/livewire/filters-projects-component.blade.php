<div>
    <div>
        <form wire:submit="render" class="space-y-4">
            <!-- Genre Dropdown -->
            <div class="">
                <div x-data="{ open: false }">
                    <span @click="open = !open"
                        class="btn btn-outline btn-wide block text-left w-full flex items-center justify-between">
                        Genre
                    </span>
                    <div x-show="open" id="genreCollapse" class="mt-2 space-y-1 pl-2">
                        @foreach(['Pop', 'Rock', 'Country', 'Hip Hop', 'Jazz'] as $genre)
                        <label class="block">
                            <input type="checkbox" wire:model.live="genres" value="{{ $genre }}"
                                class="mr-2 checkbox" />
                            {{ $genre }}
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Status Dropdown -->
            <div class="">
                <div x-data="{ open: false }">
                    <span @click="open = !open"
                        class="btn btn-outline btn-wide block text-left w-full flex items-center justify-between">
                        Status

                    </span>
                    <div x-show="open" id="statusCollapse" class="mt-2 space-y-1 pl-2">
                        @foreach(['open', 'review', 'closed'] as $status)
                        <label class="block">
                            <input type="checkbox" wire:model.live="statuses" value="{{ $status }}"
                                class="mr-2 checkbox" />
                            {{ ucfirst($status) }}
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Clear Filters Button -->
            <button type="button" class="btn btn-secondary mb-2" wire:click="clearFilters">
                Clear Filters
            </button>

        </form>
    </div>
</div>
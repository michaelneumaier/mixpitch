<div>
    <form wire:submit.prevent="render">
        <div class="form-group text-white bg-secondary p-3 mb-3">
            <div x-data="{ open: false }" style="position: relative;">
                <a href="#" @click="open = !open" role="button" aria-expanded="false"
                   aria-controls="genreCollapse" style="display: block;">
                    <label for="genre" style="width: 100%;">Genre: <i
                            x-bind:class="{'fas fa-caret-right': !open, 'fas fa-caret-down': open}"
                            style="position: absolute; right: 0;"></i></label>
                </a>
                <div x-show="open" id="genreCollapse">
                    <div class="checkbox">
                        @foreach(['Pop', 'Rock', 'Country', 'Hip Hop', 'Jazz'] as $genre)
                            <label>
                                <input type="checkbox" wire:model="genres" value="{{ $genre }}"/>
                                {{ $genre }}
                            </label><br/>
                        @endforeach
                    </div>
                </div>
            </div>


        </div>

        <div class="form-group text-white bg-secondary p-3 mb-3">
            <div x-data="{ open: false }" style="position: relative;">
                <a href="#" @click="open = !open" role="button" aria-expanded="false"
                   aria-controls="statusCollapse" style="display: block;">
                    <label for="status" style="width: 100%;">Status: <i
                            x-bind:class="{'fas fa-caret-right': !open, 'fas fa-caret-down': open}"
                            style="position: absolute; right: 0;"></i></label>
                </a>
                <div x-show="open" id="statusCollapse">
                    <div class="checkbox">
                        @foreach(['open', 'review', 'closed'] as $status)
                            <label>
                                <input type="checkbox" wire:model="statuses" value="{{ $status }}"/>
                                {{ ucfirst($status) }}
                            </label><br/>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>


        <button type="button" class="btn btn-secondary" wire:click="clearFilters">
            Clear Filters
        </button>

    </form>
</div>

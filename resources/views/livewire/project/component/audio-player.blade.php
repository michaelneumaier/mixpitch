<div x-data="{ playing: false }" class="flex">
    <div id="play-button-{{ $identifier }}" @click="playing = !playing" x-on:clear-track.window="playing = false"
        class="btn w-20 h-20 bg-primary hover:bg-primary-focus focus:outline-none rounded-full text-white flex items-center justify-center aspect-square">
        <i x-show="!playing" class="fas fa-play"></i>
        <i x-show="playing" class="fas fa-pause"></i>
    </div>
    <div wire:ignore id="{{ $identifier }}" class="flex-grow">
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const identifier = '{{ $identifier }}';
            const playButton = document.getElementById('play-button-' + identifier);
            const wavesurfer = WaveSurfer.create({
                container: '#' + identifier,
                waveColor: 'violet',
                progressColor: 'purple',
                height: 80,
            });
            window.wavesurfer = wavesurfer;
            Livewire.on('url-updated', (audioUrl) => {

                wavesurfer.load(audioUrl);
            });

            Livewire.on('clear-track', () => {
                wavesurfer.empty();
            });


            playButton.addEventListener('click', function () {
                wavesurfer.playPause();
            });
        });
    </script>
    @endpush
</div>
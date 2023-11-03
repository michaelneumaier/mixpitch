<div x-data="{ playing: false }" class="flex">
    @if($this->isPreviewTrack)
    <div id="play-button-{{ $identifier }}" @click="playing = !playing" x-on:clear-track.window="playing = false"
        class="btn w-20 h-20 bg-primary hover:bg-primary-focus focus:outline-none rounded-full text-white flex items-center justify-center aspect-square">
        <i x-show="!playing" class="fas fa-play"></i>
        <i x-show="playing" class="fas fa-pause"></i>
    </div>
    @else
    <div id="play-button-{{ $identifier }}" @click="playing = !playing" x-on:clear-track.window="playing = false"
        x-on:pause-all-tracks.window="playing = false"
        class="btn w-20 h-20 bg-primary hover:bg-primary-focus focus:outline-none rounded-full text-white flex items-center justify-center aspect-square">
        <i x-show="!playing" class="fas fa-play"></i>
        <i x-show="playing" class="fas fa-pause"></i>
    </div>
    @endif
    <div wire:ignore id="{{ $identifier }}" class="flex-grow">
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const identifier = '{{ $identifier }}';
            const isPreviewTrack = '{{ $isPreviewTrack }}';
            const playButton = document.getElementById('play-button-' + identifier);
            const wavesurfer = WaveSurfer.create({
                container: '#' + identifier,
                waveColor: 'violet',
                progressColor: 'purple',
                height: 80,
            });

            if ('{{$audioUrl}}' !== '') {
                wavesurfer.load('{{$audioUrl}}');
            }

            window.wavesurfer = wavesurfer;
            Livewire.on('url-updated', (audioUrl) => {

                wavesurfer.load(audioUrl);
            });

            Livewire.on('clear-track', () => {
                wavesurfer.empty();
            });

            Livewire.on('pause-all-tracks', () => {
                if (isPreviewTrack == false) {
                    wavesurfer.pause();
                }
            });



            playButton.addEventListener('click', function () {
                if (isPreviewTrack && !wavesurfer.isPlaying()) {
                    Livewire.dispatch('pause-all-tracks');
                }
                wavesurfer.playPause();
            });
        });
    </script>
    @endpush
</div>
<div x-data="{ playing: false }" class="{{ $mainDivClass }}">
    @if($this->isPreviewTrack)
    <div id="play-button-{{ $identifier }}" @click="playing = !playing" x-on:clear-track.window="playing = false"
        class="btn w-20 h-20 bg-primary hover:bg-primary-focus focus:outline-none rounded-full text-white flex items-center justify-center aspect-square">
        <i x-show="!playing" class="fas fa-play"></i>
        <i x-show="playing" class="fas fa-pause"></i>
    </div>
    <div wire:ignore id="{{ $identifier }}" class="flex-grow align-middle"></div>
    @elseif($this->isInCard)
    <div id="play-button-{{ $identifier }}" @click="playing = !playing" x-on:clear-track.window="playing = false"
        x-on:pause-all-tracks.window="if ($event.detail !== '{{$identifier}}') {playing=false}"
        class="bg-statusOpen/80 hover:bg-statusOpen backdrop-blur-sm p-3 focus:outline-none rounded-bl-xl rounded-tr-xl text-primary border-statusOpen border-l-4 border-b-4 flex items-center">
        <i x-show="!playing" class="fas fa-play"></i>
        <i x-show="playing" class="fas fa-pause"></i>
        <audio id="card-button-{{$identifier}}" src="{{$audioUrl}}" preload="none"></audio>
    </div>
    @else
    <div id="play-button-{{ $identifier }}" @click="playing = !playing" x-on:clear-track.window="playing = false"
        x-on:pause-all-tracks.window="playing = false"
        class="btn w-20 h-20 bg-primary hover:bg-primary-focus focus:outline-none rounded-full text-white flex items-center justify-center aspect-square">
        <i x-show="!playing" class="fas fa-play"></i>
        <i x-show="playing" class="fas fa-pause"></i>
    </div>
    <div wire:ignore id="{{ $identifier }}" class="flex-grow align-middle"></div>
    @endif

</div>

@script
<script>
    /*Disable media control keys */
    navigator.mediaSession.setActionHandler('play', function () { });
    navigator.mediaSession.setActionHandler('pause', function () { });
    navigator.mediaSession.setActionHandler('seekbackward', function () { });
    navigator.mediaSession.setActionHandler('seekforward', function () { });
    navigator.mediaSession.setActionHandler('previoustrack', function () { });
    navigator.mediaSession.setActionHandler('nexttrack', function () { });
    console.log('audioplayerinitialized {{$audioPlayerInitialized}}');
    Livewire.on('audio-player-rendered-{{ $identifier }}', function () {
        const identifier = '{{ $identifier }}';
        const isPreviewTrack = '{{ $isPreviewTrack }}';
        const isInCard = '{{ $isInCard }}';
        const playButton = document.getElementById('play-button-{{ $identifier }}');
        const cardPlayButton = document.getElementById('card-button-{{ $identifier }}');

        var wavesurfer;

        if (isInCard == true) {

        } else {
            wavesurfer = WaveSurfer.create({
                container: '#{{ $identifier }}',
                waveColor: 'violet',
                progressColor: 'blue',
                height: 80,
            });
            if ('{{$audioUrl}}' !== '') {
                wavesurfer.load('{{$audioUrl}}');
            }
            window.wavesurfer = wavesurfer;
        }

        Livewire.on('url-updated', (audioUrl) => {
            if (!isInCard) {

                wavesurfer.load(audioUrl);
                wavesurfer.seekTo(0);
            }
        });

        Livewire.on('clear-track', () => {
            if (!isInCard) {
                wavesurfer.empty();
            }

        });

        Livewire.on('pause-all-tracks', () => {
            if (isInCard) {
                cardPlayButton.pause();
            } else if (!isPreviewTrack) {
                wavesurfer.pause();
            }
        });



        playButton.addEventListener('click', function () {
            console.log('added playbutton click event listener');
            if (!isInCard) {
                if (isPreviewTrack && !wavesurfer.isPlaying()) {
                    Livewire.dispatch('pause-all-tracks');
                }
                wavesurfer.playPause();
            } else {
                console.log(cardPlayButton.readyState);
                if (cardPlayButton.paused) {
                    Livewire.dispatch('pause-all-tracks', identifier);
                    console.log('cardPlayButton is paused and clicked');
                    cardPlayButton.play();
                } else {
                    cardPlayButton.pause();
                }
            }
        });
    });
</script>
@endscript
<div id="tracks-component-root">
    @if($files->isEmpty())
    <div class="bg-white p-4 rounded shadow">
        <p class="font-bold text-lg">There are no tracks uploaded.</p>
    </div>
    @else
    <div x-data="{ show: false, ...initWaveSurfer() }">
        <button class="bg-white m-2 p-3 rounded shadow" @click="show = !show">
            Show {{ $files->count() }} {{ Str::plural('Track', $files->count()) }}
        </button>

        <div x-cloak x-init="init()" x-show="show">
            @foreach($files as $file)
            @php
            $currentIndex = $this->incrementAudioIndex();
            @endphp
            <div wire:ignore class="bg-white m-2 p-3 rounded shadow">
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">{{ basename($file->file_path) }}</span>
                    <span class="text-sm text-gray-500">{{ $file->formatted_size }}</span>
                </div>
                <div x-data="{ playing: false }" class="flex items-center">
                    <div wire:ignore id="waveform-{{ $currentIndex }}" class="flex-grow"></div>
                    <button id="play-button-{{ $currentIndex }}" @click="playing = !playing"
                        x-on:pause-all-tracks.window="playing = false"
                        class="btn w-20 h-20 bg-primary hover:bg-accent focus:bg-accent focus:outline-none rounded-full text-white flex items-center justify-center aspect-square">
                        <i x-show="!playing" class="fas fa-play"></i>
                        <i x-show="playing" class="fas fa-pause"></i>
                    </button>
                </div>
                <audio id="audio-file-{{ $currentIndex }}" src="{{ asset('storage/' . $file->file_path) }}"
                    preload="none"></audio>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    <script>
        function initWaveSurfer() {
            console.log("initWaveSurfer");
            return {
                // show: @entangle('showTracks').live,
                show: false,
                waveSurferInitialized: false,
                init() {
                    this.$watch('show', value => {
                        if (value && !this.waveSurferInitialized) {
                            var fakePeaks = [];
                            var length = 2000; // Adjust as needed for the length of your waveform display

                            for (let i = 0; i < length; i++) {
                                fakePeaks.push(Math.sin(i / 25) * Math.cos(i / 50));
                            }

                            console.log(value);
                            const audioFiles = document.querySelectorAll('audio[id^="audio-file-"]');

                            audioFiles.forEach((audioFile, index) => {
                                const waveformContainerId = `waveform-${index + 1}`;

                                const wavesurfer = WaveSurfer.create({
                                    container: `#${waveformContainerId}`,
                                    waveColor: 'green',
                                    progressColor: 'purple',
                                    height: 80,
                                    barWidth: 4,
                                    mediaType: 'audio',
                                    backend: 'MediaElement',  // This is recommended for pre-rendered peaks
                                    partialRender: true
                                });
                                wavesurfer.load('', fakePeaks, '2');
                                // wavesurfer.load(audioFile.src);

                                const playButton = document.getElementById(`play-button-${index + 1}`);
                                console.log(index);
                                Livewire.on('pause-all-tracks', () => {

                                    wavesurfer.pause();

                                });
                                playButton.addEventListener('click', function () {
                                    if (!wavesurfer.getCurrentTime()) { // This checks if the track hasn't been loaded
                                        wavesurfer.load(audioFile.src);
                                        wavesurfer.on('ready', function () {
                                            wavesurfer.play();
                                        });
                                    } else {
                                        wavesurfer.playPause();
                                    }
                                    // wavesurfer.playPause();
                                });
                            });
                            this.waveSurferInitialized = true;
                        }
                    });
                }
            }
        }
    </script>
</div>
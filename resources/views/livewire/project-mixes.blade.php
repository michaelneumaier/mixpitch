<div>@if(auth()->check() && auth()->user()->id == $project->user_id && $project->mixes->count() != 0)
    <div class="mt-5" wire:id="mixes-component-root">
        <h5 class="text-xl mb-3">Submitted Mixes</h5>
        @foreach($project->mixes as $mix)
        <div class="bg-white p-4 rounded shadow mb-3">
            <div class="flex items-center">
                <div id="waveform-{{$audioIndex}}" class="flex-grow"></div>
                <button id="play-button-{{$audioIndex}}" class="btn btn-primary w-24 ml-4">Play/Pause</button>
            </div>
            <audio id="audio-file-{{$audioIndex}}" src="{{ asset('storage/' . $mix->mix_file_path) }}"
                preload="none"></audio>
            <p class="mt-2"><strong>User:</strong> {{ $mix->user->name }}</p>
            <p><strong>Description:</strong> {{ $mix->description }}</p>
            <p><strong>Rating:</strong> <livewire:star-rating :rating="$mix->rating" :mix="$mix" />
            </p>
        </div>
        {{ $this->incrementAudioIndex() }}
        @endforeach
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const componentRoot = document.querySelector('[wire\\:id="tracks-component-root"]');

            if (!componentRoot) return;

            const audioFiles = componentRoot.querySelectorAll('audio[id^="audio-file-"]');
            //const audioFiles = document.querySelectorAll('audio[id^="audio-file-"]');

            audioFiles.forEach((audioFile, index) => {
                const waveformContainerId = `waveform-${index + 1}`;

                const wavesurfer = WaveSurfer.create({
                    container: `#${waveformContainerId}`,
                    waveColor: 'green',
                    progressColor: 'purple',
                    height: 80,
                    barWidth: 4
                });

                wavesurfer.load(audioFile.src);

                // Custom play/pause button
                const playButton = document.getElementById(`play-button-${index + 1}`);
                playButton.addEventListener('click', function () {
                    wavesurfer.playPause();
                    const alpineData = playButton.closest('[x-data]').__x.$data;
                    alpineData.playing = !wavesurfer.isPlaying();
                });
            });
        });

    </script>
    @endif
</div>
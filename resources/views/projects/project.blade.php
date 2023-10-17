@extends('layouts.app')

@section('content')

<div class="container mx-auto px-1">
    <div class="flex justify-center">
        <div class="w-full lg:w-2/3">
            <div class="border-transparent shadow-2xl shadow-base-300 rounded-lg overflow-hidden">

                <div class="relative bg-dark bg-opacity-75 p-4 flex flex-col justify-between items-start">

                    <!-- First Row -->
                    <div class="flex justify-between items-start w-full mb-2">
                        <!-- You might want to adjust the margin bottom (mb-2) as needed -->
                        <h3 class="text-4xl">
                            {{ $project->name }}
                            @if(auth()->check() && $project->isOwnedByUser(auth()->user()))
                            <a href="{{ route('projects.edit', $project) }}"
                                class="btn btn-info btn-sm bg-sky-400 ml-3">Edit</a>
                            @endif
                        </h3>
                        <livewire:status-button :status="$project->status" type="top-right" />
                    </div>

                    <!-- Second Row -->
                    <div class="flex justify-between items-start w-full">
                        <!-- Add your additional information here -->
                        <span> {{ $project->user->name }}</span>
                        <!-- You can add more elements here as needed -->
                    </div>
                </div>


                <div class="bg-dark bg-opacity-50 p-4">
                    <ul class="list-decimal list-outside pl-5 space-y-2">
                        @php $audioIndex = 0; @endphp
                        @foreach($project->files as $file)
                        @php
                        $audioIndex++;
                        $idCss = $audioIndex;
                        @endphp
                        <li>
                            <p class="mb-1">{{ basename($file->file_path) }}</p>
                            <div class="flex items-center">
                                <div id="waveform-{{$idCss}}" class="flex-grow"></div>
                                <button id="play-button-{{$idCss}}" class="btn btn-accent w-24 ml-4">Play/Pause</button>
                            </div>



                            <audio id="audio-file-{{$idCss}}" src="{{ asset('storage/' . $file->file_path) }}"
                                preload="none"></audio>

                        </li>
                        @endforeach

                        <li class="flex space-x-4 mt-3">
                            <a href="{{ route('projects.download', $project) }}" class="btn btn-primary">Download
                                All Files</a>
                            <a href="{{ route('mixes.create', $project) }}" class="btn btn-primary">Submit Mix</a>
                        </li>


                        @if(auth()->check() && auth()->user()->id == $project->user_id && $project->mixes->count() != 0)
                        <li>
                            <h5 class="text-xl mt-5">Submitted Mixes</h5>
                            <ul class="list-group mt-2 space-y-2">
                                @foreach($project->mixes as $mix)
                                @php $idCss++; @endphp
                                <li class="rounded-lg bg-white p-3 shadow">
                                    <div id="waveform-{{$idCss}}"></div>
                                    <audio id="audio-file-{{$idCss}}"
                                        src="{{ asset('storage/' . $mix->mix_file_path) }}" preload="none"></audio>
                                    <button id="play-button-{{$idCss}}" class="btn btn-primary mt-2">
                                        Play/Pause
                                    </button>

                                    <p class="mt-2"><strong>User:</strong> {{ $mix->user->name }}</p>
                                    <p><strong>Description:</strong> {{ $mix->description }}</p>
                                    <p><strong>Rating:</strong>
                                        <livewire:star-rating :rating="$mix->rating" :mix="$mix" />

                                    </p>
                                </li>
                                @endforeach
                            </ul>
                        </li>
                        @endif

                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@if(auth()->check() && auth()->user()->id == $project->user_id && $project->mixes->count() != 0)
<script>

    $(document).ready(function () {
        $(".star-rating input").change(function () {
            let rating = $(this).val();
            let mixId = $(this).parent().attr("data-mixid");

            $.ajax({
                url: `/mixes/${mixId}/rate`,
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    _method: "patch",
                    rating: rating
                },
                success: function () {
                    // Update the star-rating's data-rating attribute to the new rating
                    $(this).parent().attr("data-rating", rating);
                }.bind(this),
                error: function (xhr, status, error) {
                    console.error("Error updating rating: ", error);
                }
            });
        });

        // Render stars based on the initial rating value
        $(".star-rating").each(function () {
            let rating = $(this).attr("data-rating");
            $(this).find(`input[value="${rating}"]`).prop('checked', true);
        });
    });


</script>

@endif
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const audioFiles = document.querySelectorAll('audio[id^="audio-file-"]');

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
            });
        });
    });

</script>
@endsection
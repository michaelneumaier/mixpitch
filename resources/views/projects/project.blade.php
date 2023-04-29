@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-dark text-white">
                <div class="card-header">
                    <h3 class="display-3">{{ $project->name }}</h3>
                </div>
                <div class="card-body">
                    <p>Genre: {{ $project->genre }}</p>
                    <p>Created by: {{ $project->user->name }}</p>
                    <hr>
                    <h4>Files:</h4>
                    <ul>
                        @php
                        $audioIndex = 0;
                        @endphp
                        @foreach($project->files as $file)
                        @php
                        $audioIndex++;
                        $idCss = $audioIndex;
                        @endphp
                        <li class="mb-1">
                            <p>{{ basename($file->file_path) }}</p>
                            <div id="waveform-{{$idCss}}"></div>
                            <audio id="audio-file-{{$idCss}}" src="{{ asset('storage/' . $file->file_path) }}"
                                preload="none"></audio>

                            <button id="play-button-{{$idCss}}" class="btn btn-primary">Play/Pause</button>



                        </li>
                        @endforeach
                        <li>
                            <a href="{{ route('projects.download', $project->id) }}" class="btn btn-primary ">Download
                                All Files</a>
                            <a href="{{ route('mixes.create', $project->id) }}" class="btn btn-primary">Submit Mix</a>


                        </li>
                        <li>@if(auth()->check() && auth()->user()->id == $project->user_id && $project->mixes->count()
                            != 0)
                            <h5 class="display-6">Submitted Mixes</h5>
                            <ul class="list-group">
                                @foreach($project->mixes as $mix)
                                @php
                                $idCss++;
                                @endphp
                                <li class="list-group-item">
                                    <div id="waveform-{{$idCss}}"></div>
                                    <audio id="audio-file-{{$idCss}}"
                                        src="{{ asset('storage/' . $mix->mix_file_path) }}" preload="none"></audio>

                                    <button id="play-button-{{$idCss}}" class="btn btn-primary">Play/Pause</button>


                                    <br><strong>User:</strong> {{ $mix->user->name }}
                                    <br>
                                    <strong>Description:</strong> {{ $mix->description }}
                                    <br>
                                    <strong>Rating:</strong>
                                    <div class="star-rating" data-rating="{{ $mix->rating }}"
                                        data-mixid="{{ $mix->id}}">
                                        <span>☆</span>
                                        <span>☆</span>
                                        <span>☆</span>
                                        <span>☆</span>
                                        <span>☆</span>
                                        <span>☆</span>
                                        <span>☆</span>
                                        <span>☆</span>
                                        <span>☆</span>
                                        <span>☆</span>
                                    </div>


                                </li>
                                @endforeach
                            </ul>
                            @endif
                        </li>
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
        $(".star-rating span").click(function () {
            let rating = $(this).parent().children("span").length - $(this).index();
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

                    // Update the star display
                    $(this).parent().children("span").text("☆");
                    $(this).parent().children("span:lt(" + rating + ")").text("★");
                    $(this).parent().append($(this).parent().children("span").get().reverse());
                }.bind(this),
                error: function (xhr, status, error) {
                    console.error("Error updating rating: ", error);
                }
            });
        });

        // Render stars based on the initial rating value
        $(".star-rating").each(function () {
            let rating = $(this).attr("data-rating");
            $(this).children("span").text("☆");
            $(this).children("span:lt(" + (rating) + ")").text("★");
            $(this).append($(this).children("span").get().reverse());


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
                waveColor: 'violet',
                progressColor: 'purple',
                height: 80,
                barWidth: 2
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
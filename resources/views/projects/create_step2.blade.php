@extends('layouts.app')

@section('content')
<div class="container">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Upload Files for Project') }} - {{ $project->name }}</div>

                <div class="card-body">
                    <form action="{{ route('projects.storeStep2', $project->id) }}" method="post"
                        enctype="multipart/form-data" class="dropzone" id="dropzoneForm"
                        data-project-id="{{ $project->id }}">
                        @csrf
                        <div class="dz-message" data-dz-message>
                            <span>Drop files here or click to upload.</span>
                        </div>
                    </form>
                    <button id="finishedButton" class="btn bg-blue-500 btn-primary mt-3" type="button">Finished</button>

                </div>
            </div>

            <div class="mt-3">
                <a href="{{ route('projects.index') }}" class="btn btn-secondary">Back to Projects</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    Dropzone.autoDiscover = false;

    Dropzone.options.dropzoneForm = {
        url: "{{ route('projects.storeStep2', $project->id) }}",
        method: 'post',
        paramName: 'files',
        uploadMultiple: true,
        parallelUploads: 10,
        maxFilesize: 100,
        acceptedFiles: '.mp3,.wav',
        headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        },
    };

    const dropzone = new Dropzone('#dropzoneForm');
</script>
<script>
    // ...existing Dropzone script

    const finishedButton = document.getElementById('finishedButton');
    finishedButton.addEventListener('click', () => {
        let projectId = document.querySelector('#dropzoneForm').getAttribute('data-project-id');
        window.location.href = `/projects/${projectId}`;
    });
</script>
@endsection

<!-- @section('scripts')
<script>
    Dropzone.autoDiscover = false;

    const dropzone = new Dropzone('#dropzoneForm', {
        url: "{{ route('projects.storeStep2', $project->id) }}",
        method: 'post',
        paramName: 'files',
        uploadMultiple: true,
        parallelUploads: 10,
        maxFilesize: 20,
        acceptedFiles: '.mp3,.wav',
        headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        },
        init: function () {
            this.on("queuecomplete", function () {
                let projectId = document.querySelector('#dropzoneForm').getAttribute('data-project-id');
                window.location.href = `/projects/${projectId}`;
            });
        }
    });
</script>
@endsection -->
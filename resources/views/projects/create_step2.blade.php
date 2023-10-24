@extends('components.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>

    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold">{{ __('Upload Files for Project') }} - {{ $project->name }}</h2>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="{{ route('projects.storeStep2', $project) }}" method="post" enctype="multipart/form-data"
            class="dropzone" id="dropzoneForm" data-project-id="{{ $project->id }}">
            @csrf
            <div class="dz-message" data-dz-message>
                <span class="block text-gray-500">Drop files here or click to upload.</span>
            </div>
        </form>

        <div class="mt-4 flex justify-between">
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-secondary">Back to Edit Project</a>
            <a href="{{ route('projects.show', $project) }}" id="finishedButton" class="btn btn-primary">Finish
            </a>

        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    Dropzone.autoDiscover = false;

    Dropzone.options.dropzoneForm = {
        url: "{{ route('projects.storeStep2', $project) }}",
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

    // const finishedButton = document.getElementById('finishedButton');
    // finishedButton.addEventListener('click', () => {
    //     let projectId = document.querySelector('#dropzoneForm').getAttribute('data-project-id');
    //     window.location.href = `/projects/${projectId}`;
    // });
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
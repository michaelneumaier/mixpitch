@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card text-white">
                <div class="card-header">Edit Project</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('projects.update', $project->id) }}"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="name">Project Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $project->name }}"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="genre">Genre</label>
                            <input type="text" class="form-control" id="genre" name="genre"
                                value="{{ $project->genre }}" required>
                        </div>

                        <h5>Project Files</h5>
                        <ul class="list-group">
                            @foreach($project->files as $file)
                            <li class="list-group-item">
                                {{ basename($file->file_path) }}
                                <form
                                    action="{{ route('projects.deleteFile', ['project' => $project->id, 'file' => $file->id]) }}"
                                    method="POST" class="d-inline delete-file-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-danger float-right">Delete</button>
                                </form>

                                <!-- <form
                                    action="{{ route('projects.deleteFile', ['project' => $project->id, 'file' => $file->id]) }}"
                                    method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger float-right">Delete</button>
                                </form> -->
                            </li>
                            @endforeach
                        </ul>
                        <button type="submit" class="btn btn-primary mt-3">Update Project</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteFileButtons = document.querySelectorAll('.delete-file-form button');

        deleteFileButtons.forEach(button => {
            button.addEventListener('click', function () {
                event.preventDefault();
                button.closest('form').submit();
            });
        });
    });
</script>
@endsection
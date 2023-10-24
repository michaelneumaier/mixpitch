@extends('components.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
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
                            <select class="form-control" id="genre" name="genre" required>
                                <option value="">Select genre...</option>
                                <option value="Pop" {{ $project->genre == 'Pop' ? 'selected' : '' }}>Pop</option>
                                <option value="Rock" {{ $project->genre == 'Rock' ? 'selected' : '' }}>Rock</option>
                                <option value="Country" {{ $project->genre == 'Country' ? 'selected' : '' }}>Country
                                </option>
                                <option value="Hip Hop" {{ $project->genre == 'Hip Hop' ? 'selected' : '' }}>Hip Hop
                                </option>
                                <option value="Jazz" {{ $project->genre == 'Jazz' ? 'selected' : '' }}>Jazz</option>
                                <!-- add more genres as needed -->
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="">Status</option>
                                <option value="open" {{ $project->status == 'open' ? 'selected' : '' }}>Open</option>
                                <option value="review" {{ $project->status == 'review' ? 'selected' : '' }}>In Review
                                </option>
                                <option value="closed" {{ $project->status == 'closed' ? 'selected' : '' }}>Closed
                                </option>
                            </select>
                        </div>

                        <button type="submit" class="btn bg-blue-500 btn-primary mt-3">Update Project</button>
                    </form>

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
                                <button type="button"
                                    class="btn btn-sm bg-red-500 btn-danger float-right">Delete</button>
                            </form>
                        </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('projects.createStep2', $project->id) }}" class="btn btn-primary mt-3">Upload More
                        Files</a>
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
        const form = document.querySelector('form');
        form.addEventListener('submit', function (event) {
            console.log('Form submitted');
        });
        deleteFileButtons.forEach(button => {
            button.addEventListener('click', function () {
                event.preventDefault();
                button.closest('form').submit();
            });
        });
    });
</script>
@endsection
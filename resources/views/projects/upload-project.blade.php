@extends('layouts.app')

@section('content')
<link href="{{ asset('css/upload-page.css') }}" rel="stylesheet">

<div class="container container-upload">
    <div class="upload-text">Upload Project</div>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <form class="upload-form" action="{{ route('projects.store') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="name">Project Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter project name"
                        required>
                </div>
                <div class="form-group">
                    <label for="name">Genre</label>
                    <input type="text" class="form-control" id="genre" name="genre" placeholder="Enter genre" required>
                </div>
                <!-- <div class="form-group">
                    <label for="tracks">Upload Tracks</label>
                    <input type="file" class="form-control-file" id="files" name="files[]" multiple required>
                </div> -->
                <input type="submit" value="Upload Project">
            </form>
        </div>
    </div>
</div>
@endsection
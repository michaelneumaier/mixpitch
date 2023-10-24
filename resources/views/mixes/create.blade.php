@extends('components.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card text-white">
                <div class="card-header">Submit Mix for Project: {{ $project->name }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('mixes.store', ['project' => $project]) }}"
                        enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label for="mix_file">Mix File</label>
                            <input type="file" class="form-control-file" id="mix_file" name="mix_file" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit Mix</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
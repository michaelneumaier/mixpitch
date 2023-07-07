<div class="col-lg-3 col-md-6 mb-4">
    <a href="{{ route('projects.show', $project->id) }}" class="text-decoration-none">
        <div class="card">
            <div class="card-img-top position-relative">
                <img src="https://via.placeholder.com/150" alt="{{ $project->name }}"
                    class="img-fluid w-100 rounded-top">
                <div class="position-absolute top-0 end-0">
                    <livewire:status-button :status="$project->status" type="top-right" />
                </div>
            </div>
            <div class="card-body ">

                <h5 class="card-title mb-0">{{ $project->name }}</h5>
                <h6 class="card-text text-muted">{{ $project->genre }}</h6>
                <p class="card-text">Uploaded by: {{ $project->user->name }}</p>

            </div>
        </div>
    </a>
</div>
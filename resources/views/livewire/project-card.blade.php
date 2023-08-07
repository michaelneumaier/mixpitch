<div class="col-lg-3 col-md-6 mb-4">
    <a href="{{ route('projects.show', ['slug' => $project->slug]) }}" class="text-decoration-none">
        <div class="card">
            <div class="card-img-top position-relative">
                <div class="ratio ratio-1x1 rounded-top"
                     style="background-image: url('{{ $project->image_path ? asset('storage' . $project->image_path) : 'https://via.placeholder.com/150' }}'); background-size: cover; background-position: center;">
                </div>


                <div class="position-absolute top-0 end-0">
                    <livewire:status-button :status="$project->status" type="top-right"/>
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

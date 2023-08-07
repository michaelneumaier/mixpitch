<div class="container container-projects">
    <link href="{{ asset('css/projects-page.css') }}" rel="stylesheet">
    <div class="projects-text">Projects</div>

    <div class="row">
        <!-- Filters Column -->
        <div class="col-md-3">
            <div class="d-md-none" x-data="{ open: false }">
                <button class="btn btn-outline-secondary rounded-0 mb-3" style="width: 100%" type="button"
                        @click="open = !open">
                    Filters
                </button>
                <div x-show="open" class="d-md-block">
                    <div class="col-md-3">
                        @livewire('filters-projects-component')
                    </div>
                </div>
            </div>
            <div class="d-none d-md-block">

                @livewire('filters-projects-component')

            </div>
        </div>

        <!-- Projects Column -->
        <div class="col-md-9">
            <div class="row">
                @foreach($projects as $project)
                    @livewire('project-card', ['project' => $project], key($project->id))
                @endforeach
            </div>
            <div class="d-flex justify-content-center pagination-links">
                {{ $projects->links() }}
            </div>
        </div>
    </div>
</div>

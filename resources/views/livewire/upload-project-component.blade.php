<div>
    <link href="{{ asset('css/upload-page.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
    <!-- <form wire:submit.prevent="saveProject"> -->
    @if ($step === 1)

        <div class="container container-upload py-5">
            <div class="upload-text text-center mb-5">Upload Project</div>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <form class="upload-form" wire:submit.prevent="saveProject" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-4">
                            <label for="name" class="sr-only">Project Name</label> <!-- hidden label -->
                            <input type="text" wire:model="projectName"
                                   class="form-control bg-dark text-light input-lg-custom" id="name" name="name"
                                   placeholder="Project Name" required>

                        </div>
                        <div class="form-group mb-4">
                            <label for="genre" class="sr-only">Genre</label> <!-- hidden label -->
                            <select wire:model="projectGenre" class="form-control form-control-lg" id="genre"
                                    name="genre"
                                    required>
                                <option value="">Genre</option>
                                <option value="Pop">Pop</option>
                                <option value="Rock">Rock</option>
                                <option value="Country">Country</option>
                                <option value="Hip Hop">Hip Hop</option>
                                <option value="Jazz">Jazz</option>
                                <!-- add more genres as needed -->
                            </select>
                        </div>
                        <div class="form-group mb-4">
                            <label for="projectImage">Project Image</label>
                            <input wire:model="projectImage" type="file" class="form-control-file" id="projectImage"
                                   name="image">
                        </div>
                        <div class="form-group mb-4 text-center">
                            <button type="submit" class="btn btn-primary">Next</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @elseif ($step === 2)
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">{{ __('Upload Files for Project') }} - {{ $this->projectName }}</div>

                        <div class="card-body">
                            <form action="{{ route('projects.storeStep2', $this->projectId) }}" method="post"
                                  enctype="multipart/form-data" class="dropzone" id="dropzoneForm"
                                  data-project-id="{{ $this->projectId }}">
                                @csrf
                                <div class="dz-message" data-dz-message>
                                    <span>Drop files here or click to upload.</span>
                                </div>
                            </form>
                            <button id="finishedButton" class="btn bg-blue-500 btn-primary mt-3"
                                    type="button">Finish
                            </button>

                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('projects.index') }}" class="btn btn-secondary">Back to Projects</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- <div>
                <input type="file" wire:model="files" multiple>
                <button type="submit">Upload Project</button>
            </div> -->
        <script>
            Dropzone.autoDiscover = true;
            //let dropzoneFormTag = document.getElementById('dropzoneForm');

            var dropzoneOptions = Dropzone.options.dropzoneForm = {
                url: "{{ route('projects.storeStep2', $this->projectId) }}",
                method: 'post',
                paramName: 'files',
                uploadMultiple: true,
                parallelUploads: 10,
                maxFilesize: 100,
                acceptedFiles: '.mp3,.wav,.aif,.aiff,.flac',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
            };
            var dropzone = new Dropzone('#dropzoneForm', dropzoneOptions);

        </script>
        <script>
            // ...existing Dropzone script

            const finishedButton = document.getElementById('finishedButton');
            finishedButton.addEventListener('click', () => {
                let projectId = document.querySelector('#dropzoneForm').getAttribute('data-project-id');
                window.location.href = `/projects/{{ $projectSlug }}`;
            });
        </script>
    @endif
    <!-- </form> -->
</div>

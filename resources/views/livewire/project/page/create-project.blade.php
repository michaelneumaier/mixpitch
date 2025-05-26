<div class="container mx-auto p-3 sm:p-4 md:p-8">
    <style>
        /* Custom animations and transitions */
        .section-transition {
            transition: all 0.3s ease-in-out;
        }

        .section-header:hover {
            background-color: rgba(0, 0, 0, 0.03);
        }

        /* Input focus effects */
        .input-focus-effect:focus {
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
            transition: all 0.2s ease;
        }

        /* Enhanced validation styling */
        .validation-icon {
            transition: all 0.2s ease;
        }

        /* Improved image upload area */
        .image-upload-area {
            transition: all 0.2s ease;
            border: 2px dashed #d1d5db;
        }

        .image-upload-area:hover {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.05);
        }

        /* Responsive improvements */
        @media (max-width: 640px) {
            .form-section {
                padding: 1rem !important;
            }
            
            .section-header {
                min-height: 3.5rem;
                padding: 0.75rem 1rem !important;
            }
            
            .help-text {
                font-size: 0.875rem;
                line-height: 1.25rem;
            }
            
            .form-heading {
                font-size: 1.5rem;
                line-height: 2rem;
                padding-bottom: 0.75rem;
                margin-bottom: 1rem;
            }
            
            .form-container {
                padding: 1.25rem !important;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
        }
    </style>
    <div class="flex justify-center">
        <div class="w-full max-w-4xl bg-base-100 rounded-lg shadow-2xl shadow-base-300 p-4 sm:p-6 md:p-8 form-container">
            @if($isEdit)
            <h1 class="text-2xl sm:text-3xl font-bold mb-4 sm:mb-6 text-center sm:text-left border-b pb-3 sm:pb-4 border-base-200 form-heading">Edit Project</h1>
            @else
            <h1 class="text-2xl sm:text-3xl font-bold mb-4 sm:mb-6 text-center sm:text-left border-b pb-3 sm:pb-4 border-base-200 form-heading">Create Project
            </h1>
            @endif

            <div x-data="{ openSection: 'basic', showHelp: false }">
                <div class="mb-5 sm:mb-6 bg-blue-50 rounded-lg p-3 sm:p-4 border border-blue-100" x-show="showHelp">
                    <div class="flex justify-between items-start">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-1 mr-2 sm:mr-3"></i>
                            <div>
                                <h3 class="font-semibold text-blue-800 text-sm sm:text-base">Getting Started</h3>
                                <p class="text-xs sm:text-sm text-blue-700 mt-1 help-text">
                                    Create your project by filling out the details below. Required fields are marked
                                    with an asterisk (<span class="text-red-500">*</span>).
                                    Click on each section header to expand or collapse that section.
                                </p>
                            </div>
                        </div>
                        <button @click="showHelp = false" class="text-blue-400 hover:text-blue-600 p-1">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3 sm:mb-4 text-right">
                    <button @click="showHelp = !showHelp" class="text-xs sm:text-sm text-blue-500 hover:text-blue-700">
                        <i class="fas fa-question-circle mr-1"></i>
                        <span x-text="showHelp ? 'Hide help' : 'Show help'"></span>
                    </button>
                </div>

                <form wire:submit="save">

                    <!-- Basic Info Section -->
                    <div
                        class="bg-base-100 rounded-lg shadow-md border border-base-300 mb-5 sm:mb-6 overflow-hidden section-transition">
                        <div class="p-3 sm:p-4 cursor-pointer h-auto sm:h-14 flex items-center section-header"
                            @click="openSection = (openSection === 'basic' ? null : 'basic')">
                            <div class="w-full">
                                <h2 class="text-base sm:text-lg font-semibold flex items-center">
                                    <i class="fas fa-info-circle text-base sm:text-lg text-blue-500 mr-2 sm:mr-3"></i>Basic Info
                                    <span class="ml-1 sm:ml-2 text-2xs sm:text-xs text-red-500 font-normal">* required</span>
                                    @if($errors->hasAny(['form.name', 'form.artistName', 'form.projectType',
                                    'form.description', 'form.genre',
                                    'projectImage']))
                                    <span class="ml-auto text-red-500 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        <span class="text-2xs sm:text-xs font-medium">Contains errors</span>
                                    </span>
                                    @endif
                                    <i class="fas fa-chevron-down ml-auto transform transition-transform text-sm sm:text-base"
                                        :class="{'rotate-180': openSection === 'basic'}"></i>
                                </h2>
                            </div>
                        </div>
                        <div x-show="openSection === 'basic'" x-transition class="p-4 sm:p-6 form-section">

                            <div class="mb-4 sm:mb-6" x-data="{ touched: false }" x-init="if ($wire.isEdit) {touched = true;}">
                                <label for="name" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                    <!-- Required Icon (Asterisk) -->
                                    <span x-show="!touched"
                                        class="text-red-500 fas fa-asterisk text-2xs sm:text-xs align-top"></span>

                                    <!-- Invalid Icon (Red X) -->
                                    @if($errors->has('form.name'))
                                    <span x-show="touched" class="text-red-500 fas fa-times validation-icon"></span>
                                    @else
                                    <!-- Valid Icon (Checkmark) -->
                                    <span x-show="touched" class="text-green-500 fas fa-check validation-icon"></span>
                                    @endif
                                    Project Name:</label>
                                <div class="flex items-center">
                                    <input type="text" id="name" maxlength="80" wire:model.blur="form.name"
                                        @blur="touched = ($event.target.value !== '')"
                                        class="input input-bordered w-full input-focus-effect text-sm sm:text-base py-2 sm:py-2.5"
                                        placeholder="Enter your project name" />
                                </div>
                                @error('form.name')
                                <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div class="mb-4 sm:mb-6">
                                <label for="artist_name" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Artist
                                    Name
                                    (Optional):</label>
                                <input type="text" id="artist_name" maxlength="30" wire:model.blur="form.artistName"
                                    class="input input-bordered w-full input-focus-effect text-sm sm:text-base py-2 sm:py-2.5"
                                    placeholder="Enter artist name if different from your name">
                                @error('form.artistName')
                                <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div class="mb-4 sm:mb-6" x-data="{ touched: false }" x-init="if ($wire.isEdit) {touched = true;}">
                                <label for="project_type" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                    <!-- Required Icon (Asterisk) -->
                                    <span x-show="!touched"
                                        class="text-red-500 fas fa-asterisk text-2xs sm:text-xs align-top"></span>

                                    <!-- Invalid Icon (Red X) -->
                                    @if($errors->has('form.projectType'))
                                    <span x-show="touched" class="text-red-500 fas fa-times validation-icon"></span>
                                    @else
                                    <!-- Valid Icon (Checkmark) -->
                                    <span x-show="touched" class="text-green-500 fas fa-check validation-icon"></span>
                                    @endif

                                    Project Type:</label>
                                <select id="project_type" wire:model.blur="form.projectType"
                                    @blur="touched = ($event.target.value !== '')"
                                    class="select select-bordered w-full text-sm sm:text-base py-2 sm:py-2.5 h-auto">
                                    <option value="">Select a project type</option>
                                    <option value="single">Single</option>
                                    <option value="album">Album</option>
                                </select>
                                @error('form.projectType')
                                <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <!-- Workflow Type -->
                            <div class="mb-4 sm:mb-6" x-data="{ touched: false }" x-init="if ($wire.isEdit) {touched = true;}">
                                <label for="workflow_type" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                    <!-- Required Icon (Asterisk) -->
                                    <span x-show="!touched"
                                        class="text-red-500 fas fa-asterisk text-2xs sm:text-xs align-top"></span>

                                    <!-- Invalid Icon (Red X) -->
                                    @if($errors->has('workflow_type'))
                                    <span x-show="touched" class="text-red-500 fas fa-times validation-icon"></span>
                                    @else
                                    <!-- Valid Icon (Checkmark) -->
                                    <span x-show="touched" class="text-green-500 fas fa-check validation-icon"></span>
                                    @endif

                                    Workflow Type:</label>
                                <select id="workflow_type" wire:model.live="workflow_type"
                                    @blur="touched = ($event.target.value !== 'null')"
                                    class="select select-bordered w-full text-sm sm:text-base py-2 sm:py-2.5 h-auto">
                                    <option value="null" disabled>Select a workflow type</option>
                                    <option value="{{ \App\Models\Project::WORKFLOW_TYPE_STANDARD }}">Standard</option>
                                    <option value="{{ \App\Models\Project::WORKFLOW_TYPE_CONTEST }}">Contest</option>
                                    <option value="{{ \App\Models\Project::WORKFLOW_TYPE_DIRECT_HIRE }}">Direct Hire</option>
                                    <option value="{{ \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT }}">Client Management</option>
                                </select>
                                @error('workflow_type')
                                <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            {{-- Conditional Fields for Contest Type --}}
                            <div x-data="{ workflowType: @entangle('workflow_type') }" x-show="workflowType === '{{ \App\Models\Project::WORKFLOW_TYPE_CONTEST }}'" x-transition>
                                <hr class="my-4 border-base-300">
                                <h3 class="text-sm font-semibold text-gray-800 mb-3">Contest Details</h3>

                                <div class="mb-4 sm:mb-6">
                                    <label for="submissionDeadline" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                        Submission Deadline <span class="text-red-500">*</span>
                                    </label>
                                    <input type="datetime-local" id="submissionDeadline" wire:model.blur="submission_deadline"
                                           class="input input-bordered w-full input-focus-effect text-sm sm:text-base py-2 sm:py-2.5">
                                    @error('submission_deadline')
                                    <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                        <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                        <span>{{ $message }}</span>
                                    </div>
                                    @enderror
                                </div>

                                <div class="mb-4 sm:mb-6">
                                    <label for="judgingDeadline" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                        Judging Deadline (Optional)
                                    </label>
                                    <input type="datetime-local" id="judgingDeadline" wire:model.blur="judging_deadline"
                                           class="input input-bordered w-full input-focus-effect text-sm sm:text-base py-2 sm:py-2.5">
                                    @error('judging_deadline')
                                    <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                        <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                        <span>{{ $message }}</span>
                                    </div>
                                    @enderror
                                </div>

                                <div class="mb-4 sm:mb-6">
                                    <label for="prizeAmount" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                        Prize Amount ({{ \App\Models\Project::DEFAULT_CURRENCY }}) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex items-center">
                                        <span class="mr-2 text-gray-600 text-sm sm:text-base">{{ $prize_currency ?? \App\Models\Project::DEFAULT_CURRENCY }}</span>
                                        <input type="number" id="prizeAmount" wire:model.blur="prize_amount" min="0" step="0.01"
                                               class="input input-bordered w-full input-focus-effect text-sm sm:text-base py-2 sm:py-2.5">
                                    </div>
                                    @error('prize_amount')
                                    <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                        <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                        <span>{{ $message }}</span>
                                    </div>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-500">Enter the total prize amount for the winner.</p>
                                </div>
                                <hr class="my-4 border-base-300">
                            </div>

                            {{-- Conditional Fields for Direct Hire Type --}}
                            <div x-data="{ workflowType: @entangle('workflow_type') }" x-show="workflowType === '{{ \App\Models\Project::WORKFLOW_TYPE_DIRECT_HIRE }}'" x-transition>
                                <hr class="my-4 border-base-300">
                                <h3 class="text-sm font-semibold text-gray-800 mb-3">Direct Hire Details</h3>
                                <div class="mb-4 sm:mb-6">
                                    <label for="target_producer_search" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                        Target Producer <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="target_producer_search" wire:model.live.debounce.300ms="target_producer_query"
                                           class="input input-bordered w-full input-focus-effect text-sm sm:text-base py-2 sm:py-2.5 mb-2"
                                           placeholder="Search for producer by name...">

                                    @if(!empty($producers))
                                        <select id="target_producer_id" wire:model="target_producer_id"
                                                class="select select-bordered w-full text-sm sm:text-base py-2 sm:py-2.5 h-auto mt-1">
                                            <option value="">Select Producer</option>
                                            @foreach($producers as $producer)
                                                <option value="{{ $producer->id }}">{{ $producer->name }}</option>
                                            @endforeach
                                        </select>
                                    @elseif(strlen($target_producer_query) >= 2)
                                        <p class="text-xs text-gray-500 mt-1">No producers found matching "{{ $target_producer_query }}".</p>
                                    @endif

                                    @error('target_producer_id')
                                    <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                        <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                        <span>{{ $message }}</span>
                                    </div>
                                    @enderror
                                </div>
                                <hr class="my-4 border-base-300">
                            </div>

                            {{-- Conditional Fields for Client Management Type --}}
                            <div x-data="{ workflowType: @entangle('workflow_type') }" x-show="workflowType === '{{ \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT }}'" x-transition>
                                <hr class="my-4 border-base-300">
                                <h3 class="text-sm font-semibold text-gray-800 mb-3">Client Details</h3>
                                <div class="mb-4 sm:mb-6">
                                    <label for="client_email" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                        Client Email <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" id="client_email" wire:model.blur="client_email"
                                           class="input input-bordered w-full input-focus-effect text-sm sm:text-base py-2 sm:py-2.5">
                                    @error('client_email')
                                    <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                        <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                        <span>{{ $message }}</span>
                                    </div>
                                    @enderror
                                </div>

                                <div class="mb-4 sm:mb-6">
                                    <label for="client_name" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                        Client Name (Optional)
                                    </label>
                                    <input type="text" id="client_name" wire:model.blur="client_name"
                                           class="input input-bordered w-full input-focus-effect text-sm sm:text-base py-2 sm:py-2.5">
                                    @error('client_name')
                                    <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                        <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                        <span>{{ $message }}</span>
                                    </div>
                                    @enderror
                                </div>
                                <hr class="my-4 border-base-300">
                            </div>
                            {{-- End Conditional Fields --}}

                            <div class="mb-4 sm:mb-6" x-data="{ touched: false }" x-init="if ($wire.isEdit) {touched = true;}">
                                <label for="description" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                    <!-- Required Icon (Asterisk) -->
                                    <span x-show="!touched"
                                        class="text-red-500 fas fa-asterisk text-2xs sm:text-xs align-top"></span>

                                    <!-- Invalid Icon (Red X) -->
                                    @if($errors->has('form.description'))
                                    <span x-show="touched" class="text-red-500 fas fa-times validation-icon"></span>
                                    @else
                                    <!-- Valid Icon (Checkmark) -->
                                    <span x-show="touched" class="text-green-500 fas fa-check validation-icon"></span>
                                    @endif
                                    Description:</label>

                                <textarea id="description" rows="4" wire:model.blur="form.description"
                                    @blur="touched = ($event.target.value !== '')"
                                    class="textarea textarea-bordered w-full input-focus-effect text-sm sm:text-base py-2 sm:py-2.5"></textarea>

                                @error('form.description')
                                <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <!-- Genre -->
                            <div class="mb-4 sm:mb-6" x-data="{ touched: false }" x-init="if ($wire.isEdit) {touched = true;}">
                                <label for="genre" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                    <!-- Required Icon (Asterisk) -->
                                    <span x-show="!touched"
                                        class="text-red-500 fas fa-asterisk text-2xs sm:text-xs align-top"></span>

                                    <!-- Invalid Icon (Red X) -->
                                    @if($errors->has('form.genre'))
                                    <span x-show="touched" class="text-red-500 fas fa-times validation-icon"></span>
                                    @else
                                    <!-- Valid Icon (Checkmark) -->
                                    <span x-show="touched" class="text-green-500 fas fa-check validation-icon"></span>
                                    @endif
                                    Genre:</label>
                                <select id="genre" wire:model.blur="form.genre"
                                    @blur="touched = ($event.target.value !== 'null')"
                                    class="select select-bordered w-full text-sm sm:text-base py-2 sm:py-2.5 h-auto">
                                    <option value="null" disabled>Select a genre</option>
                                    <option value="Blues">Blues</option>
                                    <option value="Classical">Classical</option>
                                    <option value="Country">Country</option>
                                    <option value="Electronic">Electronic</option>
                                    <option value="Folk">Folk</option>
                                    <option value="Funk">Funk</option>
                                    <option value="Hip-Hop">Hip-Hop</option>
                                    <option value="Jazz">Jazz</option>
                                    <option value="Metal">Metal</option>
                                    <option value="Pop">Pop</option>
                                    <option value="Reggae">Reggae</option>
                                    <option value="Rock">Rock</option>
                                    <option value="Soul">Soul</option>
                                    <option value="R&B">R&B</option>
                                    <option value="Punk">Punk</option>
                                    <!-- ... Add more genres as needed ... -->
                                </select>
                                @error('form.genre')
                                <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div class="mb-4 sm:mb-6">
                                <label for="project_image" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                    Upload an image for the project:
                                </label>

                                @error('form.projectImage')
                                <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror

                                <!-- Image preview or placeholder -->
                                <div class="w-full max-w-xs mx-auto">
                                    @if ($form->projectImage)
                                    @if (is_object($form->projectImage) && method_exists($form->projectImage,
                                    'temporaryUrl'))
                                    <div class="relative">
                                        <img src="{{ $form->projectImage->temporaryUrl() }}"
                                            class="w-full h-auto max-h-48 sm:max-h-56 rounded-lg object-cover">
                                        <button type="button" wire:click="revertImage"
                                            class="absolute top-0 left-0 bg-red-600 rounded-tl-lg text-white w-8 h-8 sm:w-10 sm:h-10 text-sm p-1.5"
                                            title="Revert to original image">
                                            &times;
                                        </button>
                                    </div>
                                    @endif
                                    @elseif ($projectImage)
                                    <div class="relative">
                                        <img src="{{ $projectImage }}" class="w-full h-auto max-h-48 sm:max-h-56 rounded-lg object-cover">
                                        <button type="button" wire:click="revertImage"
                                            class="absolute top-0 left-0 bg-red-600 rounded-tl-lg text-white w-8 h-8 sm:w-10 sm:h-10 text-sm p-1.5"
                                            title="Revert to original image">
                                            &times;
                                        </button>
                                    </div>
                                    @else
                                    <!-- Placeholder -->
                                    <div
                                        class="flex items-center justify-center border-2 border-dashed rounded-lg h-36 sm:h-48 bg-gray-100 image-upload-area">
                                        <span class="text-gray-500 text-xs sm:text-sm">No image selected</span>
                                    </div>
                                    @endif
                                </div>

                                <!-- File input and label -->
                                <div class="w-full max-w-xs mx-auto mt-2">
                                    <input type="file" id="project_image" wire:model="form.projectImage"
                                        class="file-input hidden">
                                    <label for="project_image"
                                        class="cursor-pointer block text-center bg-primary text-white py-2 px-3 sm:px-4 rounded-lg shadow hover:bg-primary-focus w-full text-sm sm:text-base">
                                        <i class="fas fa-upload mr-1 sm:mr-2"></i> Choose Image
                                    </label>
                                </div>
                            </div>

                            <!-- Add more basic info fields here... -->
                        </div>
                    </div>

                    <!-- Collaboration Type Section -->
                    <div
                        class="bg-base-100 rounded-lg shadow-md border border-base-300 mb-5 sm:mb-6 overflow-hidden section-transition">
                        <div class="p-3 sm:p-4 cursor-pointer h-auto sm:h-14 flex items-center section-header"
                            @click="openSection = (openSection === 'collaboration' ? null : 'collaboration')">
                            <div class="w-full">
                                <h2 class="text-base sm:text-lg font-semibold flex items-center">
                                    <i class="fas fa-users text-base sm:text-lg text-blue-500 mr-2 sm:mr-3"></i>Collaboration Type
                                    @if($errors->has(['collaborationType']))
                                    <span class="ml-auto text-red-500 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        <span class="text-2xs sm:text-xs font-medium">Contains errors</span>
                                    </span>
                                    @endif
                                    <i class="fas fa-chevron-down ml-auto transform transition-transform text-sm sm:text-base"
                                        :class="{'rotate-180': openSection === 'collaboration'}"></i>
                                </h2>
                            </div>
                        </div>
                        <div x-show="openSection === 'collaboration'" x-transition class="p-4 sm:p-6 form-section">
                            <div class="mb-3 sm:mb-4">
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Choose a collaboration
                                    type:</label>

                                <div class="flex flex-col gap-2 sm:gap-4">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" wire:model.blur="form.collaborationTypeMixing"
                                            class="checkbox checkbox-sm sm:checkbox-md text-indigo-600">
                                        <span class="ml-2 text-sm sm:text-base">Mixing</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" wire:model.blur="form.collaborationTypeMastering"
                                            class="checkbox checkbox-sm sm:checkbox-md text-indigo-600">
                                        <span class="ml-2 text-sm sm:text-base">Mastering</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" wire:model.blur="form.collaborationTypeProduction"
                                            class="checkbox checkbox-sm sm:checkbox-md text-indigo-600">
                                        <span class="ml-2 text-sm sm:text-base">Production</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" wire:model.blur="form.collaborationTypeSongwriting"
                                            class="checkbox checkbox-sm sm:checkbox-md text-indigo-600">
                                        <span class="ml-2 text-sm sm:text-base">Songwriting</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" wire:model.blur="form.collaborationTypeVocalTuning"
                                            class="checkbox checkbox-sm sm:checkbox-md text-indigo-600">
                                        <span class="ml-2 text-sm sm:text-base">Vocal Tuning</span>
                                    </label>
                                </div>
                                @error('form.collaboationType')
                                <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>
                            <!-- Add more collaboration fields here if needed... -->
                        </div>
                    </div>

                    <!-- Budget Section -->
                    <div
                        class="bg-base-100 rounded-lg shadow-md border border-base-300 mb-5 sm:mb-6 overflow-hidden section-transition">
                        <div class="p-3 sm:p-4 cursor-pointer h-auto sm:h-14 flex items-center section-header"
                            @click="openSection = (openSection === 'budget' ? null : 'budget')">
                            <div class="w-full">
                                <h2 class="text-base sm:text-lg font-semibold flex items-center">
                                    <i class="fas fa-dollar-sign text-base sm:text-lg text-blue-500 mr-2 sm:mr-3"></i>Budget
                                    @if($errors->hasAny(['form.budgetType', 'form.budget']))
                                    <span class="ml-auto text-red-500 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        <span class="text-2xs sm:text-xs font-medium">Contains errors</span>
                                    </span>
                                    @endif
                                    <i class="fas fa-chevron-down ml-auto transform transition-transform text-sm sm:text-base"
                                        :class="{'rotate-180': openSection === 'budget'}"></i>
                                </h2>
                            </div>
                        </div>
                        <div x-show="openSection === 'budget'" x-transition class="p-4 sm:p-6 form-section">
                            <div x-data="{ 
                                budgetType: @entangle('form.budgetType').defer, 
                                budget: @entangle('form.budget').defer,
                                init() {
                                    if ($wire.form.budget > 0) {
                                        this.budget = $wire.form.budget;
                                        this.budgetType = 'paid';
                                        $wire.set('form.budgetType', 'paid');
                                    } else {
                                        this.budgetType = 'free';
                                        $wire.set('form.budgetType', 'free');
                                    }
                                    console.log(this.budgetType);
                                },

                                updateBudgetType(type) {
                                    this.budgetType = type;
                                    if (type === 'free') {
                                        this.budget = 0;
                                    } else if (this.budget === 0) {
                                        this.budget = 1;
                                    }
                                    $wire.set('form.budgetType', type);
                                    $wire.set('form.budget', this.budget);
                                }
                            }" x-init="if ($wire.isEdit) {init();}">
                                <div class="mb-4 sm:mb-6">
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Choose your project
                                        type:</label>
                                    <div class="flex flex-col sm:flex-row sm:space-x-4 space-y-2 sm:space-y-0">
                                        <button type="button" @click="updateBudgetType('free')"
                                            :class="{'bg-primary text-white': budgetType === 'free', 'bg-gray-100 hover:bg-gray-200': budgetType !== 'free'}"
                                            class="px-3 sm:px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition text-sm sm:text-base">
                                            Free Project
                                        </button>
                                        <button type="button" @click="updateBudgetType('paid')"
                                            :class="{'bg-primary text-white': budgetType === 'paid', 'bg-gray-100 hover:bg-gray-200': budgetType !== 'paid'}"
                                            class="px-3 sm:px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition text-sm sm:text-base">
                                            Paid Project
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-4 sm:mb-6 text-xs sm:text-sm text-gray-600">
                                    <p x-show="budgetType === 'free'" class="mb-2">
                                        <strong>Free Project:</strong> Great for hobby projects, collaborations, or
                                        building your portfolio. It may attract more contributors but might have less
                                        commitment.
                                    </p>
                                    <p x-show="budgetType === 'paid'" class="mb-2">
                                        <strong>Paid Project:</strong> Ideal for more serious endeavors. It can attract
                                        professional talent and ensure higher commitment, but may have fewer applicants.
                                    </p>
                                </div>

                                <div x-show="budgetType === 'paid'">
                                    <label for="budget" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                        Specify your budget:
                                    </label>
                                    <div class="flex items-center">
                                        <span class="mr-2 text-gray-600 text-sm sm:text-base">$</span>
                                        <input type="number" id="budget" name="budget" min="1" max="10000" step="1"
                                            x-model="budget" @input="$wire.set('form.budget', $event.target.value)"
                                            :disabled="budgetType === 'free'" class="input input-bordered w-full text-sm sm:text-base py-2 sm:py-2.5">
                                    </div>
                                    <p class="mt-2 text-xs sm:text-sm text-gray-500">Enter an amount between $1 and $10,000.</p>
                                </div>

                                <input type="hidden" name="budgetType" x-model="budgetType">

                                @error('form.budget')
                                <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                                @error('form.budgetType')
                                <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Project Deadline Section -->
                    <div
                        class="bg-base-100 rounded-lg shadow-md border border-base-300 mb-5 sm:mb-6 overflow-hidden section-transition">
                        <div class="p-3 sm:p-4 cursor-pointer h-auto sm:h-14 flex items-center section-header"
                            @click="openSection = (openSection === 'deadline' ? null : 'deadline')">
                            <div class="w-full">
                                <h2 class="text-base sm:text-lg font-semibold flex items-center">
                                    <i class="fas fa-calendar-alt text-base sm:text-lg text-blue-500 mr-2 sm:mr-3"></i>Project
                                    Deadline
                                    @if($errors->has(['form.deadline']))
                                    <span class="ml-auto text-red-500 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        <span class="text-2xs sm:text-xs font-medium">Contains errors</span>
                                    </span>
                                    @endif
                                    <i class="fas fa-chevron-down ml-auto transform transition-transform text-sm sm:text-base"
                                        :class="{'rotate-180': openSection === 'deadline'}"></i>
                                </h2>
                            </div>
                        </div>
                        <div x-show="openSection === 'deadline'" x-transition class="p-4 sm:p-6 form-section">
                            <div class="mb-3 sm:mb-4">
                                <label for="deadline" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Specify
                                    project
                                    completion
                                    deadline:</label>
                                <input type="date" id="deadline" wire:model.blur="form.deadline"
                                    class="input input-bordered w-full text-sm sm:text-base py-2 sm:py-2.5">
                                @error('form.deadline')
                                <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Additional Notes Section -->
                    <div
                        class="bg-base-100 rounded-lg shadow-md border border-base-300 mb-5 sm:mb-6 overflow-hidden section-transition">
                        <div class="p-3 sm:p-4 cursor-pointer h-auto sm:h-14 flex items-center section-header"
                            @click="openSection = (openSection === 'notes' ? null : 'notes')">
                            <div class="w-full">
                                <h2 class="text-base sm:text-lg font-semibold flex items-center">
                                    <i class="fas fa-sticky-note text-base sm:text-lg text-blue-500 mr-2 sm:mr-3"></i>Additional
                                    Notes
                                    @if($errors->hasAny(['form.notes']))
                                    <span class="ml-auto text-red-500 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        <span class="text-2xs sm:text-xs font-medium">Contains errors</span>
                                    </span>
                                    @endif
                                    <i class="fas fa-chevron-down ml-auto transform transition-transform text-sm sm:text-base"
                                        :class="{'rotate-180': openSection === 'notes'}"></i>
                                </h2>
                            </div>
                        </div>
                        <div x-show="openSection === 'notes'" x-transition class="p-4 sm:p-6 form-section">
                            <div class="mb-3 sm:mb-4">
                                <label for="notes" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Any additional
                                    details or
                                    comments:</label>
                                <textarea id="notes" rows="4" wire:model.blur="form.notes"
                                    class="textarea textarea-bordered w-full input-focus-effect text-sm sm:text-base py-2 sm:py-2.5"
                                    placeholder="Add any other important information about your project here..."></textarea>
                                @error('form.notes')
                                <div class="text-xs sm:text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Hidden fields for required values -->
                    <input type="hidden" wire:model="visibility" value="public">

                    <!-- Submit Button -->
                    <div class="mt-6 sm:mt-8 flex justify-center sm:justify-start">
                        @if($isEdit)
                        <button type="submit"
                            class="bg-warning/80 hover:bg-warning tracking-tight text-lg sm:text-xl text-center font-bold py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg shadow-accent hover:shadow-accent-focus whitespace-nowrap transition-all transform hover:scale-105 w-full sm:w-auto">
                            <i class="fas fa-save mr-1.5 sm:mr-2"></i> Save Project
                        </button>
                        @else
                        <button type="submit"
                            class="bg-warning/80 hover:bg-warning tracking-tight text-lg sm:text-xl text-center font-bold py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg shadow-accent hover:shadow-accent-focus whitespace-nowrap transition-all transform hover:scale-105 w-full sm:w-auto">
                            <i class="fas fa-plus-circle mr-1.5 sm:mr-2"></i> Create Project
                        </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="container mx-auto p-4 sm:p-6 md:p-8">
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
        }
    </style>
    <div class="flex justify-center">
        <div class="w-full max-w-4xl bg-base-100 rounded-lg shadow-2xl shadow-base-300 p-6 md:p-8">
            @if($isEdit)
            <h1 class="text-3xl font-bold mb-6 text-center sm:text-left border-b pb-4 border-base-200">Edit Project</h1>
            @else
            <h1 class="text-3xl font-bold mb-6 text-center sm:text-left border-b pb-4 border-base-200">Create Project
            </h1>
            @endif

            <div x-data="{ openSection: 'basic', showHelp: false }">
                <div class="mb-6 bg-blue-50 rounded-lg p-4 border border-blue-100" x-show="showHelp">
                    <div class="flex justify-between items-start">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                            <div>
                                <h3 class="font-semibold text-blue-800">Getting Started</h3>
                                <p class="text-sm text-blue-700 mt-1">
                                    Create your project by filling out the details below. Required fields are marked
                                    with an asterisk (<span class="text-red-500">*</span>).
                                    Click on each section header to expand or collapse that section.
                                </p>
                            </div>
                        </div>
                        <button @click="showHelp = false" class="text-blue-400 hover:text-blue-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4 text-right">
                    <button @click="showHelp = !showHelp" class="text-sm text-blue-500 hover:text-blue-700">
                        <i class="fas fa-question-circle mr-1"></i>
                        <span x-text="showHelp ? 'Hide help' : 'Show help'"></span>
                    </button>
                </div>

                <form wire:submit="save">

                    <!-- Basic Info Section -->
                    <div
                        class="bg-base-100 rounded-lg shadow-md border border-base-300 mb-6 overflow-hidden section-transition">
                        <div class="p-4 cursor-pointer h-14 flex items-center section-header"
                            @click="openSection = (openSection === 'basic' ? null : 'basic')">
                            <div class="w-full">
                                <h2 class="text-lg font-semibold flex items-center">
                                    <i class="fas fa-info-circle text-lg text-blue-500 mr-3"></i>Basic Info
                                    <span class="ml-2 text-xs text-red-500 font-normal">* required</span>
                                    @if($errors->hasAny(['form.name', 'form.artistName', 'form.projectType',
                                    'form.description', 'form.genre',
                                    'projectImage']))
                                    <span class="ml-auto text-red-500 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        <span class="text-xs font-medium">Contains errors</span>
                                    </span>
                                    @endif
                                    <i class="fas fa-chevron-down ml-auto transform transition-transform"
                                        :class="{'rotate-180': openSection === 'basic'}"></i>
                                </h2>
                            </div>
                        </div>
                        <div x-show="openSection === 'basic'" x-transition class="p-6 form-section">

                            <div class="mb-6" x-data="{ touched: false }" x-init="if ($wire.isEdit) {touched = true;}">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    <!-- Required Icon (Asterisk) -->
                                    <span x-show="!touched"
                                        class="text-red-500 fas fa-asterisk text-xs align-top"></span>

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
                                        class="input input-bordered w-full input-focus-effect"
                                        placeholder="Enter your project name" />
                                </div>
                                @error('form.name')
                                <div class="text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div class="mb-6">
                                <label for="artist_name" class="block text-sm font-medium text-gray-700 mb-2">Artist
                                    Name
                                    (Optional):</label>
                                <input type="text" id="artist_name" maxlength="30" wire:model.blur="form.artistName"
                                    class="input input-bordered w-full input-focus-effect"
                                    placeholder="Enter artist name if different from your name">
                                @error('form.artistName')
                                <div class="text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div class="mb-6" x-data="{ touched: false }" x-init="if ($wire.isEdit) {touched = true;}">
                                <label for="projectType" class="block text-sm font-medium text-gray-700 mb-2">
                                    <!-- Required Icon (Asterisk) -->
                                    <span x-show="!touched"
                                        class="text-red-500 fas fa-asterisk text-xs align-top"></span>

                                    <!-- Invalid Icon (Red X) -->
                                    @if($errors->has('form.projectType'))
                                    <span x-show="touched" class="text-red-500 fas fa-times validation-icon"></span>
                                    @else
                                    <!-- Valid Icon (Checkmark) -->
                                    <span x-show="touched" class="text-green-500 fas fa-check validation-icon"></span>
                                    @endif

                                    Project Type:</label>
                                <select id="projectType" wire:model.blur="form.projectType"
                                    @blur="touched = ($event.target.value !== 'null')"
                                    class="select select-bordered w-full">
                                    <option value="null" disabled>Select a project type</option>
                                    <option value="single">Single</option>
                                    <option value="album">Album</option>
                                    <option value="ep">EP</option>
                                    <option value="mixtape">Mixtape</option>
                                </select>
                                @error('form.projectType')
                                <div class="text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div class="mb-6" x-data="{ touched: false }" x-init="if ($wire.isEdit) {touched = true;}">
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                    <!-- Required Icon (Asterisk) -->
                                    <span x-show="!touched"
                                        class="text-red-500 fas fa-asterisk text-xs align-top"></span>

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
                                    class="textarea textarea-bordered w-full input-focus-effect"></textarea>

                                @error('form.description')
                                <div class="text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <!-- Genre -->
                            <div class="mb-6" x-data="{ touched: false }" x-init="if ($wire.isEdit) {touched = true;}">
                                <label for="genre" class="block text-sm font-medium text-gray-700 mb-2">
                                    <!-- Required Icon (Asterisk) -->
                                    <span x-show="!touched"
                                        class="text-red-500 fas fa-asterisk text-xs align-top"></span>

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
                                    class="select select-bordered w-full">
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
                                <div class="text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="project_image" class="block text-sm font-medium text-gray-700 mb-2">
                                    Upload an image for the project:
                                </label>

                                @error('form.projectImage')
                                <div class="text-sm text-error mt-1 flex items-start">
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
                                            class="w-full rounded-lg object-cover">
                                        <button type="button" wire:click="revertImage"
                                            class="absolute top-0 left-0 bg-red-600 rounded-tl-lg text-white w-10 h-10 text-sm p-1.5"
                                            title="Revert to original image">
                                            &times;
                                        </button>
                                    </div>
                                    @endif
                                    @elseif ($projectImage)
                                    <div class="relative">
                                        <img src="{{ $projectImage }}" class="w-full rounded-lg object-cover">
                                        <button type="button" wire:click="revertImage"
                                            class="absolute top-0 left-0 bg-red-600 rounded-tl-lg text-white w-10 h-10 text-sm p-1.5"
                                            title="Revert to original image">
                                            &times;
                                        </button>
                                    </div>
                                    @else
                                    <!-- Placeholder -->
                                    <div
                                        class="flex items-center justify-center border-2 border-dashed rounded-lg h-48 bg-gray-100 image-upload-area">
                                        <span class="text-gray-500">No image selected</span>
                                    </div>
                                    @endif
                                </div>

                                <!-- File input and label -->
                                <div class="w-full max-w-xs mx-auto mt-2">
                                    <input type="file" id="project_image" wire:model="form.projectImage"
                                        class="file-input hidden">
                                    <label for="project_image"
                                        class="cursor-pointer block text-center bg-primary text-white py-2 px-4 rounded-lg shadow hover:bg-primary-focus w-full">
                                        <i class="fas fa-upload"></i> Choose Image
                                    </label>
                                </div>
                            </div>

                            <!-- Add more basic info fields here... -->
                        </div>
                    </div>

                    <!-- Collaboration Type Section -->
                    <div
                        class="bg-base-100 rounded-lg shadow-md border border-base-300 mb-6 overflow-hidden section-transition">
                        <div class="p-4 cursor-pointer h-14 flex items-center section-header"
                            @click="openSection = (openSection === 'collaboration' ? null : 'collaboration')">
                            <div class="w-full">
                                <h2 class="text-lg font-semibold flex items-center">
                                    <i class="fas fa-users text-lg text-blue-500 mr-3"></i>Collaboration Type
                                    @if($errors->has(['collaborationType']))
                                    <span class="ml-auto text-red-500 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        <span class="text-xs font-medium">Contains errors</span>
                                    </span>
                                    @endif
                                    <i class="fas fa-chevron-down ml-auto transform transition-transform"
                                        :class="{'rotate-180': openSection === 'collaboration'}"></i>
                                </h2>
                            </div>
                        </div>
                        <div x-show="openSection === 'collaboration'" x-transition class="p-6 form-section">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Choose a collaboration
                                    type:</label>

                                <div class="flex flex-col">
                                    <label class="inline-flex items-center mb-4">
                                        <input type="checkbox" wire:model.blur="form.collaborationTypeMixing"
                                            class="checkbox text-indigo-600">
                                        <span class="ml-2">Mixing</span>
                                    </label>
                                    <label class="inline-flex items-center mb-4">
                                        <input type="checkbox" wire:model.blur="form.collaborationTypeMastering"
                                            class="checkbox text-indigo-600">
                                        <span class="ml-2">Mastering</span>
                                    </label>
                                    <label class="inline-flex items-center mb-4">
                                        <input type="checkbox" wire:model.blur="form.collaborationTypeProduction"
                                            class="checkbox text-indigo-600">
                                        <span class="ml-2">Production</span>
                                    </label>
                                    <label class="inline-flex items-center mb-4">
                                        <input type="checkbox" wire:model.blur="form.collaborationTypeSongwriting"
                                            class="checkbox text-indigo-600">
                                        <span class="ml-2">Songwriting</span>
                                    </label>
                                    <label class="inline-flex items-center mb-4">
                                        <input type="checkbox" wire:model.blur="form.collaborationTypeVocalTuning"
                                            class="checkbox text-indigo-600">
                                        <span class="ml-2">Vocal Tuning</span>
                                    </label>
                                </div>
                                @error('form.collaboationType')
                                <div class="text-sm text-error mt-1 flex items-start">
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
                        class="bg-base-100 rounded-lg shadow-md border border-base-300 mb-6 overflow-hidden section-transition">
                        <div class="p-4 cursor-pointer h-14 flex items-center section-header"
                            @click="openSection = (openSection === 'budget' ? null : 'budget')">
                            <div class="w-full">
                                <h2 class="text-lg font-semibold flex items-center">
                                    <i class="fas fa-dollar-sign text-lg text-blue-500 mr-3"></i>Budget
                                    @if($errors->hasAny(['form.budgetType', 'form.budget']))
                                    <span class="ml-auto text-red-500 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        <span class="text-xs font-medium">Contains errors</span>
                                    </span>
                                    @endif
                                    <i class="fas fa-chevron-down ml-auto transform transition-transform"
                                        :class="{'rotate-180': openSection === 'budget'}"></i>
                                </h2>
                            </div>
                        </div>
                        <div x-show="openSection === 'budget'" x-transition class="p-6 form-section">
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
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Choose your project
                                        type:</label>
                                    <div class="flex space-x-4">
                                        <button type="button" @click="updateBudgetType('free')"
                                            :class="{'bg-primary text-white': budgetType === 'free', 'bg-gray-100 hover:bg-gray-200': budgetType !== 'free'}"
                                            class="px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition">
                                            Free Project
                                        </button>
                                        <button type="button" @click="updateBudgetType('paid')"
                                            :class="{'bg-primary text-white': budgetType === 'paid', 'bg-gray-100 hover:bg-gray-200': budgetType !== 'paid'}"
                                            class="px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition">
                                            Paid Project
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-6 text-sm text-gray-600">
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
                                    <label for="budget" class="block text-sm font-medium text-gray-700 mb-2">
                                        Specify your budget:
                                    </label>
                                    <div class="flex items-center">
                                        <span class="mr-2 text-gray-600">$</span>
                                        <input type="number" id="budget" name="budget" min="1" max="10000" step="1"
                                            x-model="budget" @input="$wire.set('form.budget', $event.target.value)"
                                            :disabled="budgetType === 'free'" class="input input-bordered w-full">
                                    </div>
                                    <p class="mt-2 text-sm text-gray-500">Enter an amount between $1 and $10,000.</p>
                                </div>

                                <input type="hidden" name="budgetType" x-model="budgetType">

                                @error('form.budget')
                                <div class="text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                                @error('form.budgetType')
                                <div class="text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Project Deadline Section -->
                    <div
                        class="bg-base-100 rounded-lg shadow-md border border-base-300 mb-6 overflow-hidden section-transition">
                        <div class="p-4 cursor-pointer h-14 flex items-center section-header"
                            @click="openSection = (openSection === 'deadline' ? null : 'deadline')">
                            <div class="w-full">
                                <h2 class="text-lg font-semibold flex items-center">
                                    <i class="fas fa-calendar-alt text-lg text-blue-500 mr-3"></i>Project
                                    Deadline
                                    @if($errors->has(['form.deadline']))
                                    <span class="ml-auto text-red-500 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        <span class="text-xs font-medium">Contains errors</span>
                                    </span>
                                    @endif
                                    <i class="fas fa-chevron-down ml-auto transform transition-transform"
                                        :class="{'rotate-180': openSection === 'deadline'}"></i>
                                </h2>
                            </div>
                        </div>
                        <div x-show="openSection === 'deadline'" x-transition class="p-6 form-section">
                            <div class="mb-4">
                                <label for="deadline" class="block text-sm font-medium text-gray-700 mb-2">Specify
                                    project
                                    completion
                                    deadline:</label>
                                <input type="date" id="deadline" wire:model.blur="form.deadline"
                                    class="input input-bordered w-full">
                                @error('form.deadline')
                                <div class="text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Additional Notes Section -->
                    <div
                        class="bg-base-100 rounded-lg shadow-md border border-base-300 mb-6 overflow-hidden section-transition">
                        <div class="p-4 cursor-pointer h-14 flex items-center section-header"
                            @click="openSection = (openSection === 'notes' ? null : 'notes')">
                            <div class="w-full">
                                <h2 class="text-lg font-semibold flex items-center">
                                    <i class="fas fa-sticky-note text-lg text-blue-500 mr-3"></i>Additional
                                    Notes
                                    @if($errors->hasAny(['form.notes']))
                                    <span class="ml-auto text-red-500 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        <span class="text-xs font-medium">Contains errors</span>
                                    </span>
                                    @endif
                                    <i class="fas fa-chevron-down ml-auto transform transition-transform"
                                        :class="{'rotate-180': openSection === 'notes'}"></i>
                                </h2>
                            </div>
                        </div>
                        <div x-show="openSection === 'notes'" x-transition class="p-6 form-section">
                            <div class="mb-4">
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Any additional
                                    details or
                                    comments:</label>
                                <textarea id="notes" rows="4" wire:model.blur="form.notes"
                                    class="textarea textarea-bordered w-full input-focus-effect"
                                    placeholder="Add any other important information about your project here..."></textarea>
                                @error('form.notes')
                                <div class="text-sm text-error mt-1 flex items-start">
                                    <i class="fas fa-exclamation-circle mt-0.5 mr-1"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->

                    <div class="mt-8 flex justify-center sm:justify-start">
                        @if($isEdit)
                        <button type="submit"
                            class="bg-warning/80 hover:bg-warning tracking-tight text-xl text-center font-bold py-3 px-6 rounded-lg shadow-accent hover:shadow-accent-focus whitespace-nowrap transition-all transform hover:scale-105">
                            <i class="fas fa-save mr-2"></i> Save Project
                        </button>
                        @else
                        <button type="submit"
                            class="bg-warning/80 hover:bg-warning tracking-tight text-xl text-center font-bold py-3 px-6 rounded-lg shadow-accent hover:shadow-accent-focus whitespace-nowrap transition-all transform hover:scale-105">
                            <i class="fas fa-plus-circle mr-2"></i> Create Project
                        </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
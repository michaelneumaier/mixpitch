<div class="container mx-auto p-5">
    <div class="text-4xl text-center text-primary mb-6">Create Project</div>
    <!-- Form Wrapper with Alpine.js for Accordion functionality -->
    <div x-data="{ openSection: 'basic', budget: 0 }">

        <!-- Basic Info Section -->
        <div class="mb-4 cursor-pointer" @click="openSection = (openSection === 'basic' ? null : 'basic')">
            <h2 class="text-xl bg-base-200 p-3 flex items-center"><i
                    class="fas fa-info-circle mr-2 w-5 mr-2 text-center"></i>Basic Info</h2>
        </div>
        <div x-show="openSection === 'basic'" class="p-4 mb-5">
            <div class="mb-4">
                <label for="name" class="block label-text text-gray-700 mb-2">Project Name:</label>
                <input type="text" id="name" wire:model.live="name"
                    class="input input-bordered input-lg text-2xl w-full border rounded shadow-sm">
                @error('name') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="mb-4">
                <label for="artist_name" class="block label-text text-gray-700 mb-2">Artist Name (Optional):</label>
                <input type="text" id="artist_name" wire:model.live="artistName"
                    class="input input-bordered w-full px-3 py-2">
                @error('artistName') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="mb-4">
                <label for="project_type" class="block label-text text-gray-700 mb-2">Select project type:</label>
                <select id="project_type" wire:model.live="projectType" class="w-full px-3 py-2 border rounded shadow-sm">
                    <option value="single">Single</option>
                    <option value="album">Album</option>
                    <option value="ep">EP</option>
                    <option value="mixtape">Mixtape</option>
                </select>
                @error('projectType') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="mb-4">
                <label for="description" class="block label-text text-gray-700 mb-2">Description:</label>
                <textarea id="description" rows="4" wire:model.live="description"
                    class="textarea textarea-bordered w-full px-3 py-2"></textarea>
                @error('description') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>


            <!-- Genre -->
            <div class="mb-4">
                <label for="genre" class="block label-text text-gray-700 mb-2">Genre:</label>
                <select id="genre" wire:model.live="genre" class="w-full px-3 py-2 border rounded shadow-sm">
                    <option value="">Select Genre</option>
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
                @error('genre') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label for="project_image" class="block label-text text-gray-700 mb-2">Upload an image for the
                    project:</label>
                <input type="file" id="project_image" wire:model.live="projectImage" class="file-input">
                @error('projectImage') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- Add more basic info fields here... -->
        </div>


        <!-- Collaboration Type Section -->
        <div class="mb-4 cursor-pointer"
            @click="openSection = (openSection === 'collaboration' ? null : 'collaboration')">
            <h2 class="text-xl bg-base-200 p-3 flex items-center"><i
                    class="fas fa-users mr-2 w-5 mr-2 text-center"></i>Collaboration Type
            </h2>
        </div>
        <div x-show="openSection === 'collaboration'" class="p-4 mb-5">
            <div class="mb-1">
                <label class="block label-text mb-2">Choose a collaboration type:</label>

                <div class="flex flex-col">
                    <label class="inline-flex items-center mb-4">
                        <input type="checkbox" wire:model.live="collaborationType.mixing" class="checkbox text-indigo-600">
                        <span class="ml-2">Mixing</span>
                    </label>
                    <label class="inline-flex items-center mb-4">
                        <input type="checkbox" wire:model.live="collaborationType.mastering"
                            class="checkbox text-indigo-600">
                        <span class="ml-2">Mastering</span>
                    </label>
                    <label class="inline-flex items-center mb-4">
                        <input type="checkbox" wire:model.live="collaborationType.production"
                            class="checkbox text-indigo-600">
                        <span class="ml-2">Production</span>
                    </label>
                    <label class="inline-flex items-center mb-4">
                        <input type="checkbox" wire:model.live="collaborationType.songwriting"
                            class="checkbox text-indigo-600">
                        <span class="ml-2">Songwriting</span>
                    </label>
                    <label class="inline-flex items-center mb-4">
                        <input type="checkbox" wire:model.live="collaborationType.vocal_tuning"
                            class="checkbox text-indigo-600">
                        <span class="ml-2">Vocal Tuning</span>
                    </label>
                </div>

                @error('collaborationType') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>
            <!-- Add more collaboration fields here if needed... -->
        </div>

        <!-- Budget Section -->
        <div class="mb-4 cursor-pointer" @click="openSection = (openSection === 'budget' ? null : 'budget')">
            <h2 class="text-xl bg-base-200 p-3"><i class="fas fa-dollar-sign mr-2 w-5 mr-2 text-center"></i>Budget</h2>
        </div>
        <div x-show="openSection === 'budget'" class="p-4 mb-5">
            <div class="mb-4">
                <label for="budget_slider" class="block label-text text-gray-700 mb-2">Specify your budget:</label>

                <!-- Slider and Manual Input Flex Container -->
                <div class="flex items-center space-x-4">
                    <!-- Slider -->
                    <!-- <input type="range" id="budget_slider" min="0" max="1000" step="10" x-bind:value="budget"
                        x-on:input="budget = $event.target.value" class="slider flex-grow" wire:model.live="budget"> -->

                    <input type="range" id="budget_slider" min="0" max="1000" step="10" x-bind:value="budget"
                        x-on:input="budget = $event.target.value" wire:model.live="budget" class="h-full appearance-none flex items-center cursor-pointer bg-transparent z-30
        [&::-webkit-slider-thumb]:bg-blue-600 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:border-0 [&::-webkit-slider-thumb]:w-5 [&::-webkit-slider-thumb]:h-5 [&::-webkit-slider-thumb]:appearance-none
        [&::-moz-range-thumb]:bg-blue-600 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-0 [&::-moz-range-thumb]:w-2.5 [&::-moz-range-thumb]:h-2.5 [&::-moz-range-thumb]:appearance-none
        [&::-ms-thumb]:bg-blue-600 [&::-ms-thumb]:rounded-full [&::-ms-thumb]:border-0 [&::-ms-thumb]:w-2.5 [&::-ms-thumb]:h-2.5 [&::-ms-thumb]:appearance-none
        [&::-webkit-slider-runnable-track]:bg-neutral-200 [&::-webkit-slider-runnable-track]:rounded-full [&::-webkit-slider-runnable-track]:overflow-hidden [&::-moz-range-track]:bg-neutral-200 [&::-moz-range-track]:rounded-full [&::-ms-track]:bg-neutral-200 [&::-ms-track]:rounded-full
        [&::-moz-range-progress]:bg-blue-400 [&::-moz-range-progress]:rounded-full [&::-ms-fill-lower]:bg-blue-400 [&::-ms-fill-lower]:rounded-full [&::-webkit-slider-thumb]:shadow-[-999px_0px_0px_990px_#4e97ff]
    ">

                    <!-- Manual Input -->
                    <div class="flex-shrink-0 relative">
                        <!-- Overlay $ Symbol -->
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-gray-600">$</span>
                        </span>

                        <!-- Input with Added Padding -->
                        <input type="number" id="budget" min="0" max="1000" x-bind:value="budget"
                            x-on:input="budget = $event.target.value" placeholder="0"
                            class="px-3 py-2 pl-8 border rounded shadow-sm" wire:model.live="budget">
                    </div>

                </div>

                @error('budget') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>



        <!-- Project Deadline Section -->
        <div class="mb-4 cursor-pointer" @click="openSection = (openSection === 'deadline' ? null : 'deadline')">
            <h2 class="text-xl bg-base-200 p-3"><i class="fas fa-calendar-alt mr-2 w-5 mr-2 text-center"></i>Project
                Deadline
            </h2>
        </div>
        <div x-show="openSection === 'deadline'" class="p-4 mb-5">
            <div class="mb-4">
                <label for="deadline" class="block label-text text-gray-700 mb-2">Specify project completion
                    deadline:</label>
                <input type="date" id="deadline" wire:model.live="deadline" class="input input-bordered px-3 py-2">
                @error('deadline') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>
        <!-- Track Upload Section -->
        <div class="mb-4 cursor-pointer" @click="openSection = (openSection === 'trackUpload' ? null : 'trackUpload')">
            <h2 class="text-xl bg-base-200 p-3"><i class="fas fa-upload mr-2 w-5 mr-2 text-center"></i>Track Upload</h2>
        </div>
        <div x-show="openSection === 'trackUpload'" class="p-4 mb-5">
            <div class="mb-4">
                <label for="track" class="block label-text text-gray-700 mb-2">Upload your track:</label>
                <input type="file" id="track" wire:model.live="track" class="w-full px-3 py-2 border rounded shadow-sm">
                @error('track') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Additional Notes Section -->
        <div class="mb-4 cursor-pointer" @click="openSection = (openSection === 'notes' ? null : 'notes')">
            <h2 class="text-xl bg-base-200 p-3"><i class="fas fa-sticky-note mr-2 w-5 mr-2 text-center"></i>Additional
                Notes
            </h2>
        </div>
        <div x-show="openSection === 'notes'" class="p-4 mb-5">
            <div class="mb-4">
                <label for="notes" class="block label-text text-gray-700 mb-2">Any additional details or
                    comments:</label>
                <textarea id="notes" rows="4" wire:model.live="notes"
                    class="textarea textarea-bordered w-full px-3 py-2"></textarea>
                @error('notes') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Submit Button -->
        <div class="mt-4">
            <button wire:click="createProject" class="btn btn-primary">
                Create Project
            </button>
        </div>
    </div>

    <div x-data="{ showNotification: false, notificationMessage: '' }" x-init="() => {
                document.addEventListener('notify', event => {
                    console.log(event);
                    notificationMessage = event.detail.msg;
                    showNotification = true;
                    setTimeout(() => { showNotification = false }, 3000);
                });
            }">
        <div x-show="showNotification" class="fixed bottom-5 right-5 bg-green-500 p-3 rounded shadow-md">
            <p x-text="notificationMessage"></p>
        </div>
    </div>


</div>
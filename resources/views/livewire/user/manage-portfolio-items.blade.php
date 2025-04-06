<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Portfolio') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-xl sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">{{ __('Your Portfolio Items') }}</h2>
                    <button wire:click="$set('showForm', true)" class="inline-flex items-center px-4 py-2 bg-primary border border-transparent rounded-md font-medium text-sm text-white hover:bg-primary-focus focus:outline-none focus:border-primary-focus focus:ring focus:ring-primary-focus transition">
                        <i class="fas fa-plus mr-2"></i>
                        Add Item
                    </button>
                </div>

                @if(count($portfolioItems ?? []) > 0)
                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visibility</th>
                                <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="portfolio-items-container" wire:sortable="updateSort">
                            @foreach($portfolioItems as $item)
                            <tr wire:key="item-{{ $item->id }}" wire:sortable.item="{{ $item->id }}" wire:sortable.handle class="hover:bg-gray-50 border-b border-gray-200 last:border-b-0">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $item->title }}</div>
                                    @if($item->description)
                                        <div class="text-sm text-gray-500 truncate max-w-xs">{{ $item->description }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($item->item_type === 'audio_upload') bg-blue-100 text-blue-800
                                        @elseif($item->item_type === 'external_link') bg-green-100 text-green-800
                                        @else bg-purple-100 text-purple-800 @endif">
                                        {{ ucfirst(str_replace('_', ' ', $item->item_type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($item->is_public) bg-green-100 text-green-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ $item->is_public ? 'Public' : 'Private' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="editItem({{ $item->id }})" class="text-primary hover:text-primary-focus mr-3">
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </button>
                                    <button wire:click="deleteItem({{ $item->id }})" class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash-alt mr-1"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-sm text-gray-600 italic">
                    <i class="fas fa-arrows-alt mr-1"></i> Drag items to reorder them on your profile
                </p>
                @else
                <div class="bg-gray-50 rounded-lg p-8 text-center">
                    <div class="text-gray-400 mb-3">
                        <i class="fas fa-images text-5xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">No portfolio items yet</h3>
                    <p class="text-gray-500 mb-4">Add your first item to showcase your work</p>
                    <button wire:click="$set('showForm', true)" class="inline-flex items-center px-4 py-2 bg-primary border border-transparent rounded-md font-medium text-sm text-white hover:bg-primary-focus focus:outline-none transition">
                        <i class="fas fa-plus mr-2"></i> Add Your First Item
                    </button>
                </div>
                @endif

                @if($showForm)
                <div class="mt-8 border-t border-gray-200 pt-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">{{ $editingItemId ? 'Edit' : 'Add' }} Portfolio Item</h3>
                    
                    <div class="bg-gray-50 rounded-lg p-6 shadow-sm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <x-label for="itemType" :value="__('Item Type')" class="font-medium" />
                                <select id="itemType" wire:model.live="itemType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                                    <option value="audio_upload">Audio Upload</option>
                                    <option value="external_link">External Link</option>
                                    <option value="mixpitch_project_link">Mixpitch Project Link</option>
                                </select>
                                <x-livewire-error class="mt-2" :messages="$errors->get('itemType')" />
                            </div>

                            <div>
                                <x-label for="title" :value="__('Title')" class="font-medium" />
                                <x-input id="title" type="text" class="mt-1 block w-full" wire:model="title" />
                                <x-livewire-error class="mt-2" :messages="$errors->get('title')" />
                            </div>
                        </div>

                        <div class="mb-6">
                            <x-label for="description" :value="__('Description (Optional)')" class="font-medium" />
                            <textarea id="description" wire:model="description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" rows="3"></textarea>
                            <x-livewire-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        @if($itemType === 'audio_upload')
                        <div class="mb-6">
                            <x-label for="audioFile" :value="__('Audio File (MP3, WAV - Max 100MB)')" class="font-medium" />
                            @if($editingItemId && $existingFilePath)
                                <div class="mt-1 mb-3 p-3 bg-blue-50 border border-blue-200 rounded-md text-blue-700 text-sm flex items-start">
                                    <i class="fas fa-music mt-0.5 mr-2"></i>
                                    <div>
                                        <div class="font-medium">Current file: {{ basename($existingFilePath) }}</div>
                                        <div class="text-xs text-blue-600">Upload a new file to replace the current one or leave empty to keep the current file</div>
                                    </div>
                                </div>
                            @elseif(!$editingItemId)
                                <div class="mt-1 text-sm {{ $audioFile ? 'text-green-600' : 'text-red-600' }}">
                                    <i class="fas {{ $audioFile ? 'fa-check-circle' : 'fa-exclamation-circle' }} mr-1"></i> 
                                    {{ $audioFile ? 'File selected' : 'An audio file is required' }}
                                </div>
                            @endif
                            <div class="mt-1 flex items-center">
                                <label for="audioFile" class="flex-1 cursor-pointer">
                                    <div class="px-4 py-3 border {{ $audioFile ? 'border-green-300 bg-green-50' : 'border-gray-300 border-dashed' }} rounded-md text-center hover:border-primary hover:bg-gray-50 transition">
                                        <i class="fas {{ $audioFile ? 'fa-check mr-1 text-green-500' : 'fa-music mr-1' }}"></i>
                                        <span class="{{ $audioFile ? 'text-green-600 font-medium' : 'text-gray-600' }}">
                                            {{ $audioFile ? $audioFile->getClientOriginalName() : 'Click to select audio file' }}
                                        </span>
                                        <input id="audioFile" type="file" wire:model.live="audioFile" class="hidden" accept=".mp3,.wav">
                                    </div>
                                </label>
                            </div>
                            <div wire:loading wire:target="audioFile" class="mt-2 text-sm text-primary flex items-center">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Uploading...
                            </div>
                            <x-livewire-error class="mt-2" :messages="$errors->get('audioFile')" />
                        </div>
                        @endif

                        @if($itemType === 'external_link')
                        <div class="mb-6">
                            <x-label for="externalUrl" :value="__('External URL')" class="font-medium" />
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                    <i class="fas fa-link"></i>
                                </span>
                                <x-input id="externalUrl" type="url" class="rounded-l-none flex-1" wire:model="externalUrl" placeholder="https://..." />
                            </div>
                            <x-livewire-error class="mt-2" :messages="$errors->get('externalUrl')" />
                        </div>
                        @endif

                        @if($itemType === 'mixpitch_project_link')
                        <div class="mb-6">
                            <x-label for="linkedProjectId" :value="__('Mixpitch Project')" class="font-medium" />
                            <select id="linkedProjectId" wire:model="linkedProjectId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                                <option value="">Select a project</option>
                                @foreach($availableProjects ?? [] as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                            <div class="mt-1 text-sm text-gray-600">
                                <i class="fas fa-info-circle mr-1"></i>
                                Select a Mixpitch project you were involved with
                            </div>
                            <x-livewire-error class="mt-2" :messages="$errors->get('linkedProjectId')" />
                        </div>
                        @endif

                        <div class="mb-6">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="isPublic" type="checkbox" wire:model="isPublic" class="focus:ring-primary h-4 w-4 text-primary border-gray-300 rounded">
                                </div>
                                <label for="isPublic" class="ml-3 text-sm">
                                    <span class="font-medium text-gray-700">Show publicly on profile</span>
                                    <p class="text-gray-500">When enabled, this item will be visible to anyone who views your profile</p>
                                </label>
                            </div>
                            <x-livewire-error class="mt-2" :messages="$errors->get('isPublic')" />
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                            <button wire:click="resetForm" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-medium text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:border-gray-400 focus:ring focus:ring-gray-200 focus:ring-opacity-50 transition">
                                Cancel
                            </button>
                            <button wire:click="saveItem" class="inline-flex items-center px-4 py-2 bg-primary border border-transparent rounded-md font-medium text-sm text-white hover:bg-primary-focus focus:outline-none focus:border-primary-focus focus:ring focus:ring-primary-focus focus:ring-opacity-50 transition">
                                <i class="fas fa-save mr-2"></i> Save Item
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- SortableJS for drag-and-drop reordering -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('livewire:initialized', function () {
            const initSortable = function() {
                const container = document.getElementById('portfolio-items-container');
                if (container) {
                    new Sortable(container, {
                        animation: 150,
                        ghostClass: 'bg-gray-100',
                        handle: '[wire\\:sortable\\.handle]',
                        onEnd: function() {
                            // Format data for the wire:sortable attribute structure
                            const newOrder = Array.from(container.querySelectorAll('[wire\\:sortable\\.item]'))
                                .map((el, index) => ({
                                    order: index + 1,
                                    value: el.getAttribute('wire:sortable.item')
                                }));
                            
                            // Send the new order to the Livewire component
                            const componentId = container.closest('[wire\\:id]').getAttribute('wire:id');
                            Livewire.find(componentId).updateSort(newOrder);
                        }
                    });
                }
            };
            
            // Initialize sortable and reinitialize after Livewire updates
            initSortable();
            Livewire.hook('morph.updated', () => {
                initSortable();
            });
        });
    </script>
</div>

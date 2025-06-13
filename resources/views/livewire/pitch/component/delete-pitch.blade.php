<div>
    {{-- Do your work, then step back. --}}
    <!-- Danger Zone -->
    <div class="mt-8 border border-red-200 rounded-lg p-6 bg-red-50">
        <h4 class="text-xl font-semibold mb-4 text-red-800 flex items-center">
            <i class="fas fa-exclamation-triangle mr-2"></i>Danger Zone
        </h4>
        <p class="mb-4 text-gray-700">
            Deleting your pitch will permanently remove all associated files, comments, feedback, and history. This action cannot be undone.
        </p>
        <div class="flex justify-end">
            <button wire:click="confirmDelete" class="btn btn-error">
                <i class="fas fa-trash-alt mr-2"></i>Delete Pitch
            </button>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    @if($showDeleteConfirmation)
    <div class="fixed inset-0 z-50 overflow-y-auto"
         x-data="{}"
         x-show="true"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            
            <!-- Modal Panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Delete Pitch</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    This action will permanently delete your pitch and all associated data. This cannot be undone.
                                </p>
                                <div class="mt-4">
                                    <label for="delete-confirm" class="block text-sm font-medium text-gray-700">Type "delete" to confirm</label>
                                    <input type="text" 
                                           id="delete-confirm" 
                                           wire:model.live="deleteConfirmInput" 
                                           x-init="$nextTick(() => $el.focus())"
                                           class="input input-bordered w-full mt-1"
                                           placeholder="Type 'delete' to confirm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" wire:click="deletePitch" 
                            class="btn btn-error ml-3">
                        Delete Permanently
                    </button>
                    <button type="button" wire:click="cancelDelete" 
                            class="btn btn-ghost">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

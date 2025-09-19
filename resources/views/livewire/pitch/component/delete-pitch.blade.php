<!-- Delete Confirmation Modal -->
<div>
@if($showDeleteConfirmation)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data="{}" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="cancelDelete"></div>
        
        <!-- Modal panel -->
        <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-700 p-6">
            <div class="space-y-6">
                    <!-- Modal Header -->
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-xl bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 flex items-center justify-center">
                                <flux:icon name="exclamation-triangle" class="w-6 h-6 text-red-600 dark:text-red-400" />
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <flux:heading size="lg" class="text-red-900 dark:text-red-100 mb-2">
                                Delete Pitch
                            </flux:heading>
                            <p class="text-sm text-red-700 dark:text-red-300">
                                This action will permanently delete your pitch and all associated data. This cannot be undone.
                            </p>
                        </div>
                    </div>

                    <!-- Confirmation Input -->
                    <div class="space-y-3">
                        <flux:field>
                            <flux:label class="text-slate-900 dark:text-slate-100">Type "delete" to confirm</flux:label>
                            <flux:input 
                                wire:model.live="deleteConfirmInput" 
                                placeholder="Type 'delete' to confirm"
                                x-init="$nextTick(() => $el.focus())"
                                class="w-full" 
                            />
                        </flux:field>
                        
                        @if($deleteConfirmInput && $deleteConfirmInput !== 'delete')
                            <p class="text-sm text-red-600 dark:text-red-400">
                                Please type "delete" exactly to confirm deletion.
                            </p>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-3">
                        <flux:button wire:click="cancelDelete" variant="ghost">
                            Cancel
                        </flux:button>
                        <flux:button 
                            wire:click="deletePitch" 
                            variant="danger"
                            class="{{ $deleteConfirmInput !== 'delete' ? 'opacity-50 cursor-not-allowed' : '' }}"
                        >
                            Delete Permanently
                        </flux:button>
                    </div>
            </div>
        </div>
    </div>
@endif
</div>
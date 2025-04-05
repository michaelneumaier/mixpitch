<div>
    <!-- Button to open completion modal -->
    @if($pitch->status === \App\Models\Pitch::STATUS_APPROVED && auth()->id() === $pitch->project->user_id)
    <div class="{{ request()->routeIs('projects.manage') ? '' : 'mt-4' }}">
        <button type="button" wire:click="openCompletionModal"
            class="{{ request()->routeIs('projects.manage') ? 'btn btn-sm bg-success hover:bg-success/80 text-white font-semibold' : 'w-full bg-success hover:bg-success/80 font-bold py-3 px-4 rounded-md shadow-md transition-colors flex items-center justify-center' }}">
            <i class="fas fa-check-circle mr-2"></i> Complete Pitch
        </button>
    </div>
    @endif

    <!-- Completion Modal (direct approach) -->
    <div x-data="{ show: false }" x-init="$watch('$wire.showCompletionModal', value => show = value)" x-show="show"
        x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <!-- Modal Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

        <!-- Modal Content -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-base-100 rounded-lg shadow-xl max-w-lg w-full mx-auto"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95">

                <!-- Modal Header -->
                <div
                    class="bg-success/10 rounded-t-lg py-4 px-6 flex justify-between items-center border-b border-success/20">
                    <h3 class="text-xl font-bold text-success flex items-center">
                        <i class="fas fa-check-circle mr-2"></i> Complete Pitch
                    </h3>
                    <button type="button" wire:click="closeCompletionModal"
                        class="text-gray-500 hover:text-gray-700 transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <p class="mb-4 text-gray-700">You are about to mark this pitch as completed. This action will:</p>
                    <ul class="mb-4 space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-check text-success mt-1 mr-2"></i>
                            <span>Change the pitch status to "Completed"</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-success mt-1 mr-2"></i>
                            <span>Mark the project as "Completed"</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-success mt-1 mr-2"></i>
                            <span>Notify the pitch creator</span>
                        </li>
                        @if($hasOtherApprovedPitches)
                        <li class="flex items-start text-amber-600">
                            <i class="fas fa-exclamation-triangle mt-1 mr-2"></i>
                            <span>Close {{ $otherApprovedPitchesCount }} other approved {{ $otherApprovedPitchesCount ==
                                1 ? 'pitch' : 'pitches' }}</span>
                        </li>
                        @endif
                        <li class="flex items-start text-amber-600">
                            <i class="fas fa-exclamation-triangle mt-1 mr-2"></i>
                            <span>This action cannot be undone</span>
                        </li>
                    </ul>

                    @if($hasOtherApprovedPitches)
                    <div class="p-3 mb-4 bg-amber-100 border border-amber-300 rounded-md">
                        <div class="flex items-center mb-1">
                            <i class="fas fa-info-circle text-amber-600 mr-2"></i>
                            <span class="font-medium">Multiple Approved Pitches Detected</span>
                        </div>
                        <p class="text-sm text-amber-800">
                            There {{ $otherApprovedPitchesCount == 1 ? 'is' : 'are' }} {{ $otherApprovedPitchesCount }}
                            other approved {{ $otherApprovedPitchesCount == 1 ? 'pitch' : 'pitches' }} for this project.
                            By completing this pitch, you are choosing it as the final version, and all other approved
                            pitches will be automatically closed.
                        </p>
                    </div>
                    @endif

                    <div class="mb-4">
                        <label for="feedback" class="block text-sm font-medium text-gray-700 mb-1">Completion Feedback
                            (optional)</label>
                        <textarea id="feedback" wire:model="feedback" rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-success focus:border-success"
                            placeholder="Add any final feedback or notes about the completed work..."></textarea>
                        @error('feedback') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Rating Input --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rate the Producer's Work (Required)</label>
                        <div class="rating rating-lg">
                            <input type="radio" wire:model="rating" value="1" class="mask mask-star-2 bg-orange-400" />
                            <input type="radio" wire:model="rating" value="2" class="mask mask-star-2 bg-orange-400" />
                            <input type="radio" wire:model="rating" value="3" class="mask mask-star-2 bg-orange-400" />
                            <input type="radio" wire:model="rating" value="4" class="mask mask-star-2 bg-orange-400" />
                            <input type="radio" wire:model="rating" value="5" class="mask mask-star-2 bg-orange-400" />
                        </div>
                         @error('rating') <span class="block text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 rounded-b-lg py-4 px-6 flex justify-end space-x-3">
                    <button type="button" wire:click="closeCompletionModal"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors shadow-sm">
                        Cancel
                    </button>
                    <button type="button" wire:click="debugComplete" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-success text-white rounded-md hover:bg-success/80 transition-colors shadow-sm flex items-center">
                        <span wire:loading wire:target="debugComplete" class="loading loading-spinner loading-xs mr-2"></span>
                        <i wire:loading.remove wire:target="debugComplete" class="fas fa-check-circle mr-2"></i> Complete Pitch
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<div>
@if($showModal)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm overflow-y-auto"
     x-data="{ show: @entangle('showModal') }"
     x-show="show"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    
    <!-- Modal Content -->
    <div class="relative m-4 w-full max-w-lg transform overflow-hidden rounded-2xl border border-white/30 bg-gradient-to-br from-white/95 to-green-50/90 shadow-2xl backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95">
        
        <!-- Background Effects -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -right-20 -top-20 h-40 w-40 rounded-full bg-gradient-to-br from-green-400/20 to-emerald-600/20 blur-3xl"></div>
            <div class="absolute -bottom-20 -left-20 h-40 w-40 rounded-full bg-gradient-to-tr from-emerald-400/20 to-green-600/20 blur-3xl"></div>
        </div>
        
        <!-- Modal Header -->
        <div class="relative border-b border-white/20 bg-gradient-to-r from-green-500/10 via-emerald-500/10 to-green-500/10 p-6 backdrop-blur-sm">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-emerald-600">
                        <i class="fas fa-check-circle text-lg text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-green-800">Complete Pitch</h3>
                        <p class="text-sm text-green-600">{{ $projectTitle }}</p>
                    </div>
                </div>
                <button wire:click="closeModal" 
                        class="rounded-xl bg-gradient-to-r from-gray-100 to-gray-200 p-2 text-gray-600 transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:from-gray-200 hover:to-gray-300 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <!-- Modal Body -->
        <div class="relative p-6">
            <div class="mb-6">
                <p class="mb-4 text-gray-700 font-medium">You are about to mark this pitch as completed. This action will:</p>
                <ul class="space-y-2 mb-4">
                    <li class="flex items-start">
                        <div class="mr-3 mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-emerald-600">
                            <i class="fas fa-check text-xs text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-green-800">Change the pitch status to "Completed"</span>
                    </li>
                    <li class="flex items-start">
                        <div class="mr-3 mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-emerald-600">
                            <i class="fas fa-check text-xs text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-green-800">Mark the project as "Completed"</span>
                    </li>
                    <li class="flex items-start">
                        <div class="mr-3 mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-emerald-600">
                            <i class="fas fa-check text-xs text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-green-800">Notify the pitch creator</span>
                    </li>
                    @if($hasOtherApprovedPitches)
                    <li class="flex items-start">
                        <div class="mr-3 mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-600">
                            <i class="fas fa-exclamation-triangle text-xs text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-amber-800">Close {{ $otherApprovedPitchesCount }} other approved {{ $otherApprovedPitchesCount == 1 ? 'pitch' : 'pitches' }}</span>
                    </li>
                    @endif
                    <li class="flex items-start">
                        <div class="mr-3 mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-600">
                            <i class="fas fa-exclamation-triangle text-xs text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-amber-800">This action cannot be undone</span>
                    </li>
                </ul>
            </div>

            @if($hasOtherApprovedPitches)
            <div class="mb-6 rounded-xl border border-amber-200/50 bg-gradient-to-r from-amber-50/80 to-orange-50/80 p-4 backdrop-blur-sm">
                <div class="mb-2 flex items-center">
                    <div class="mr-3 flex h-6 w-6 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-orange-600">
                        <i class="fas fa-info-circle text-xs text-white"></i>
                    </div>
                    <span class="font-bold text-amber-800">Multiple Approved Pitches Detected</span>
                </div>
                <p class="text-sm text-amber-800">
                    There {{ $otherApprovedPitchesCount == 1 ? 'is' : 'are' }} {{ $otherApprovedPitchesCount }}
                    other approved {{ $otherApprovedPitchesCount == 1 ? 'pitch' : 'pitches' }} for this project.
                    By completing this pitch, you are choosing it as the final version, and all other approved
                    pitches will be automatically closed.
                </p>
            </div>
            @endif

            <!-- Rating Section -->
            <div class="mb-6">
                <label class="mb-2 block text-sm font-bold text-gray-800">Rate the Producer's Work (Required)</label>
                <div class="flex items-center space-x-1">
                    @for($i = 1; $i <= 5; $i++)
                    <button type="button" 
                            wire:click="$set('rating', {{ $i }})"
                            class="h-8 w-8 rounded-full transition-[transform,colors] duration-200 hover:scale-110 {{ $rating >= $i ? 'text-amber-400' : 'text-gray-300' }}">
                        <i class="fas fa-star text-lg"></i>
                    </button>
                    @endfor
                </div>
                @error('rating') 
                <span class="mt-1 block text-xs text-red-600 font-medium">{{ $message }}</span> 
                @enderror
            </div>

            <!-- Feedback Section -->
            <div class="mb-6">
                <label for="feedback" class="mb-2 block text-sm font-bold text-gray-800">
                    Completion Feedback (Optional)
                </label>
                <textarea 
                    id="feedback" 
                    wire:model="feedback" 
                    rows="4"
                    class="w-full rounded-xl border border-gray-300 bg-white/80 px-4 py-3 shadow-sm backdrop-blur-sm transition-[colors,shadow] duration-200 focus:border-green-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-green-500/20"
                    placeholder="Add any final feedback or notes about the completed work..."></textarea>
                @error('feedback') 
                <span class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</span> 
                @enderror
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="relative border-t border-white/20 bg-gradient-to-r from-gray-50/80 to-white/80 p-6 backdrop-blur-sm">
            <div class="flex justify-end space-x-3">
                <button wire:click="closeModal"
                        class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-gray-100 to-gray-200 px-4 py-2.5 font-medium text-gray-700 transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:from-gray-200 hover:to-gray-300 hover:shadow-lg">
                    Cancel
                </button>
                <button wire:click="completePitch" 
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-2.5 font-semibold text-white transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:from-green-700 hover:to-emerald-700 hover:shadow-lg disabled:opacity-75 disabled:cursor-not-allowed">
                    <span wire:loading wire:target="completePitch">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing...
                    </span>
                    <span wire:loading.remove wire:target="completePitch">
                        <i class="fas fa-check-circle mr-2"></i>Complete Pitch
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endif
</div>
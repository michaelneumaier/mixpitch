<div>
    <div class="p-4 bg-white rounded-lg shadow-md">
        <h3 class="text-lg font-semibold mb-4">Complete Pitch</h3>
        
        @if ($pitch->status === \App\Models\Pitch::STATUS_APPROVED)
            <div class="mb-4">
                <p class="text-gray-700 mb-2">
                    Marking a pitch as complete confirms that the work has been satisfactorily delivered.
                    @if ($pitch->project->budget > 0)
                        You will be prompted to process payment after completion.
                    @endif
                </p>
            </div>
            
            <div class="mb-4">
                <label for="feedback" class="block text-sm font-medium text-gray-700 mb-1">
                    Feedback (Optional)
                </label>
                <textarea
                    id="feedback"
                    wire:model="feedback"
                    rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    placeholder="Add any feedback about the completed work"
                ></textarea>
            </div>
            
            @error('completion')
                <div class="text-red-500 text-sm mb-4">{{ $message }}</div>
            @enderror
            
            <div class="flex justify-end">
                <button
                    wire:click="completePitch"
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                >
                    Mark as Complete
                </button>
            </div>
        @else
            <div class="text-amber-600 mb-4">
                This pitch cannot be completed because it is not in the approved status.
            </div>
        @endif
    </div>
</div> 
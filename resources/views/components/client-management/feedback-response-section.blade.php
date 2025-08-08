@props(['component'])

<div class="bg-amber-50 border border-amber-200 rounded-xl p-6 shadow-md">
    <h4 class="text-lg font-semibold text-amber-800 mb-3 flex items-center">
        <i class="fas fa-reply text-amber-600 mr-2"></i>Respond to Client Feedback
    </h4>
    <div class="mb-4">
        <label class="block text-sm font-medium text-amber-700 mb-2">
            Your Response to Client Feedback
        </label>
        <textarea wire:model.lazy="responseToFeedback" 
                  rows="4"
                  class="w-full px-3 py-2 border border-amber-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                  placeholder="Explain what changes you've made in response to the feedback..."></textarea>
        @error('responseToFeedback') 
            <span class="text-red-500 text-xs mt-1">{{ $message }}</span> 
        @enderror
    </div>
</div>
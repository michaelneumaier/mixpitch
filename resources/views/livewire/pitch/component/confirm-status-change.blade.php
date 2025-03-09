<div>
    {{-- If your happiness depends on money, you will never be happy with yourself. --}}
    @if ($showConfirmModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center" x-data="{}"
        x-init="$nextTick(() => { $el.focus() })" tabindex="0" @keydown.escape.window="$wire.closeConfirmationModal()">
        <!-- Modal Backdrop with dark overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

        <!-- Modal Content -->
        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 md:mx-0 z-10 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Confirm Action</h3>
            </div>

            <div class="p-6">
                <p class="text-gray-700 mb-4">{{ $confirmMessage }}</p>

                @if ($pendingAction === 'deny')
                <div class="mb-4">
                    <label for="denyReason" class="block text-sm font-medium text-gray-700 mb-1">Reason for
                        denial</label>
                    <textarea id="denyReason" wire:model.defer="actionData.reason"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        rows="3" placeholder="Please explain why you are denying this pitch..."></textarea>
                </div>
                @endif

                @if ($pendingAction === 'requestChanges')
                <div class="mb-4">
                    <label for="changesRequested" class="block text-sm font-medium text-gray-700 mb-1">Requested
                        Changes</label>
                    <textarea id="changesRequested" wire:model.defer="actionData.reason"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        rows="3"
                        placeholder="Please specify what changes you'd like to see in this pitch..."></textarea>
                </div>
                @endif

                @if ($pendingAction === 'complete')
                <div class="mb-4">
                    <label for="completionFeedback" class="block text-sm font-medium text-gray-700 mb-1">Completion
                        Feedback (optional)</label>
                    <textarea id="completionFeedback" wire:model.defer="actionData.feedback"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        rows="3" placeholder="Add any feedback about the completed pitch..."></textarea>
                </div>

                @if(isset($actionData['hasOtherApprovedPitches']) && $actionData['hasOtherApprovedPitches'])
                <div class="p-3 mb-4 bg-amber-100 border border-amber-300 rounded-md">
                    <div class="flex items-center mb-1">
                        <i class="fas fa-exclamation-triangle text-amber-600 mr-2"></i>
                        <span class="font-medium">Multiple Approved Pitches</span>
                    </div>
                    <p class="text-sm text-amber-800">
                        By completing this pitch, you are selecting it as the final version. The other
                        {{ $actionData['otherApprovedPitchesCount'] ?? '0' }} approved
                        {{ ($actionData['otherApprovedPitchesCount'] ?? 0) == 1 ? 'pitch' : 'pitches' }}
                        will be automatically closed. This cannot be undone.
                    </p>
                </div>
                @endif
                @endif
            </div>

            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                <button type="button" wire:click="closeConfirmationModal"
                    class="py-2 px-4 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </button>

                <button type="button" wire:click="confirmAction"
                    class="py-2 px-4 border border-transparent rounded-md text-sm font-medium text-white {{ $confirmButtonClass }} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    {{ $actionLabel }}
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
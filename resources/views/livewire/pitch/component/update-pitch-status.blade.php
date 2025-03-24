<!-- resources/views/livewire/pitch/component/update-pitch-status.blade.php -->
<div>
    <!-- <div class="flex items-center justify-center mb-2">
        <div
            class="px-3 py-1 rounded-full {{ 
            $status === \App\Models\Pitch::STATUS_PENDING ? 'bg-yellow-100 text-yellow-800' : 
            ($status === \App\Models\Pitch::STATUS_IN_PROGRESS ? 'bg-blue-100 text-blue-800' : 
            ($status === \App\Models\Pitch::STATUS_PENDING_REVIEW ? 'bg-purple-100 text-purple-800' : 
            ($status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW ? 'bg-indigo-100 text-indigo-800' : 
            ($status === \App\Models\Pitch::STATUS_APPROVED ? 'bg-green-100 text-green-800' : 
            ($status === \App\Models\Pitch::STATUS_DENIED ? 'bg-red-100 text-red-800' : 
            ($status === \App\Models\Pitch::STATUS_COMPLETED ? 'bg-success/20 text-success' : 
            ($status === \App\Models\Pitch::STATUS_CLOSED ? 'bg-gray-100 text-gray-800' : 'bg-gray-100 text-gray-800'))))))) }}">
            <span class="font-medium text-sm">{{ $pitch->readable_status }}</span>
        </div>
    </div> -->
    @if (session()->has('error'))
    <div class="alert alert-danger p-2 text-sm text-red-700 bg-red-100 rounded mb-2">
        {{ session('error') }}
    </div>
    @endif
    <div class="flex flex-wrap gap-2 z-20 relative">
        @if($pitch->is_inactive)
        <div class="flex items-center justify-center w-full text-gray-500 text-sm italic">
            <i class="fas fa-info-circle mr-2"></i>This pitch is inactive because another pitch has been completed
        </div>
        @elseif($status === \App\Models\Pitch::STATUS_CLOSED)
        <div class="flex items-center justify-center w-full text-gray-500 text-sm italic">
            <i class="fas fa-info-circle mr-2"></i>This pitch has been closed because another pitch was selected
        </div>
        @elseif ($status === \App\Models\Pitch::STATUS_PENDING)
        <button wire:click="changeStatus('forward', '{{ \App\Models\Pitch::STATUS_IN_PROGRESS }}')"
            class="btn btn-sm shadow-sm bg-accent hover:bg-accent-focus border-0 text-black flex items-center justify-center transition-colors z-30 relative">
            <i class="fas fa-check mr-2"></i>Allow Access
        </button>
        @elseif ($status === \App\Models\Pitch::STATUS_IN_PROGRESS)
        <button wire:click="changeStatus('backward', '{{ \App\Models\Pitch::STATUS_PENDING }}')"
            class="btn btn-sm shadow-sm bg-warning hover:bg-warning/80 border-0 text-black flex items-center justify-center transition-colors z-30 relative">
            <i class="fas fa-times mr-2"></i>Remove Access
        </button>
        @elseif ($status === \App\Models\Pitch::STATUS_PENDING_REVIEW)
        <button wire:click="changeStatus('backward', '{{ \App\Models\Pitch::STATUS_PENDING }}')"
            class="btn btn-sm shadow-sm bg-warning hover:bg-warning/80 border-0 text-black flex items-center justify-center transition-colors z-30 relative">
            <i class="fas fa-times mr-2"></i>Remove Access
        </button>
        @elseif ($status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
        <button wire:click="reviewPitch()"
            class="btn btn-sm shadow-sm bg-green-500 hover:bg-green-700 border-0 text-white flex items-center justify-center transition-colors z-30 relative">
            <i class="fas fa-list mr-2"></i>Review
        </button>

        @if($pitch->current_snapshot_id)
        <button wire:click="requestSnapshotApproval({{ $pitch->current_snapshot_id }})"
            class="btn btn-sm shadow-sm bg-success hover:bg-success/80 border-0 text-white flex items-center justify-center transition-colors z-30 relative">
            <i class="fas fa-check mr-2"></i>Approve
        </button>

        <button wire:click="requestSnapshotDenial({{ $pitch->current_snapshot_id }})"
            class="btn btn-sm shadow-sm bg-error hover:bg-error/80 border-0 text-white flex items-center justify-center transition-colors z-30 relative">
            <i class="fas fa-times mr-2"></i>Deny
        </button>

        <button wire:click="requestRevisions({{ $pitch->current_snapshot_id }})"
            class="btn btn-sm shadow-sm bg-info hover:bg-info/80 border-0 text-white flex items-center justify-center transition-colors z-30 relative">
            <i class="fas fa-edit mr-2"></i>Request Revisions
        </button>
        @endif

        @elseif ($status === \App\Models\Pitch::STATUS_APPROVED)
        <button wire:click="changeStatus('backward', '{{ \App\Models\Pitch::STATUS_READY_FOR_REVIEW }}')"
            class="btn btn-sm shadow-sm bg-info hover:bg-info/80 border-0 text-white flex items-center justify-center transition-colors z-30 relative">
            <i class="fas fa-undo mr-2"></i>Return to Review
        </button>
        @elseif ($status === \App\Models\Pitch::STATUS_DENIED)
        <button wire:click="returnToReadyForReview"
            class="btn btn-sm shadow-sm bg-accent hover:bg-accent-focus border-0 text-black flex items-center justify-center transition-colors z-30 relative">
            <i class="fas fa-undo mr-2"></i>Return to Review
        </button>
        @elseif ($status === \App\Models\Pitch::STATUS_COMPLETED)
        @if($pitch->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_PAID)
        <button wire:click="changeStatus('backward', '{{ \App\Models\Pitch::STATUS_APPROVED }}')"
            class="btn btn-sm shadow-sm bg-warning hover:bg-warning/80 border-0 text-black flex items-center justify-center transition-colors z-30 relative">
            <i class="fas fa-undo mr-2"></i>Return to Approved
        </button>
        @endif
        @endif
    </div>

    {{-- Include the confirm dialog component --}}
    <livewire:pitch.component.confirm-status-change :pitch="$pitch" />

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // For Livewire component bindings
            window.addEventListener('openConfirmDialog', event => {
                const { action, params } = event.detail;
                
                if (action === 'deny') {
                    // Open deny dialog
                    const { snapshotId } = params;
                    Livewire.dispatch('openModal', 'pitch.component.confirm-status-change', {
                        action: 'deny',
                        snapshotId: snapshotId
                    });
                } else if (action === 'approve') {
                    // Open approve dialog
                    const { snapshotId } = params;
                    Livewire.dispatch('openModal', 'pitch.component.confirm-status-change', {
                        action: 'approve',
                        snapshotId: snapshotId
                    });
                } else if (action === 'cancelSubmission') {
                    // Open cancel submission dialog
                    Livewire.dispatch('openModal', 'pitch.component.confirm-status-change', {
                        action: 'cancelSubmission',
                    });
                } else if (action === 'requestRevisions') {
                    // Open request revisions dialog
                    const { snapshotId } = params;
                    Livewire.dispatch('openModal', 'pitch.component.confirm-status-change', {
                        action: 'requestRevisions',
                        snapshotId: snapshotId
                    });
                }
            });
        });
    </script>
</div>
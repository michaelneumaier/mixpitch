<!-- resources/views/livewire/pitch/component/update-pitch-status.blade.php -->
<div>
    <div>
        <span class="text-base">{{ $pitch->readable_status }}</span>
    </div>
    @if (session()->has('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif
    <div class="flex">
        @if ($status === \App\Models\Pitch::STATUS_PENDING)
        <button wire:click="changeStatus('forward', '{{ \App\Models\Pitch::STATUS_IN_PROGRESS }}')"
            class="btn btn-primary bg-accent hover:bg-accent-focus border-accent text-black flex-grow flex items-center justify-center">
            <i class="fas fa-check mr-2"></i>Allow Access
        </button>
        @elseif ($status === \App\Models\Pitch::STATUS_IN_PROGRESS)
        <button wire:click="changeStatus('backward', '{{ \App\Models\Pitch::STATUS_PENDING }}')"
            class="btn btn-primary bg-warning hover:bg-warning/80 border-warning text-black flex-grow flex items-center justify-center">
            <i class="fas fa-times mr-2"></i>Remove Access
        </button>
        @elseif ($status === \App\Models\Pitch::STATUS_PENDING_REVIEW)
        <button wire:click="changeStatus('backward', '{{ \App\Models\Pitch::STATUS_PENDING }}')"
            class="btn btn-primary bg-warning hover:bg-warning/80 border-warning text-black flex-grow flex items-center justify-center">
            <i class="fas fa-times mr-2"></i>Remove Access
        </button>
        @elseif ($status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
        <button wire:click="reviewPitch()"
            class="btn btn-primary bg-green-500 hover:bg-green-700 text-white flex-grow flex items-center justify-center">
            <i class="fas fa-list mr-2"></i>Review
        </button>
        <!-- <button wire:click="changeStatus('forward', '{{ \App\Models\Pitch::STATUS_APPROVED }}')"
            class="btn btn-primary bg-green-500 hover:bg-green-700 text-white flex-grow flex items-center justify-center">
            <i class="fas fa-check mr-2"></i>Approve
        </button>
        <button wire:click="changeStatus('backward', '{{ \App\Models\Pitch::STATUS_DENIED }}')"
            class="btn btn-primary bg-red-500 hover:bg-red-700 text-white flex-grow flex items-center justify-center">
            <i class="fas fa-times mr-2"></i>Deny
        </button>
        <button wire:click="changeStatus('backward', '{{ \App\Models\Pitch::STATUS_PENDING_REVIEW }}')"
            class="btn btn-primary bg-yellow-500 hover:bg-yellow-700 text-white flex-grow flex items-center justify-center">
            <i class="fas fa-comments mr-2"></i>Send for Review
        </button> -->
        @elseif ($status === \App\Models\Pitch::STATUS_DENIED)
        <button wire:click="changeStatus('backward', '{{ \App\Models\Pitch::STATUS_IN_PROGRESS }}')"
            class="btn btn-primary bg-accent hover:bg-accent-focus border-accent text-black flex-grow flex items-center justify-center">
            <i class="fas fa-check mr-2"></i>Allow Access
        </button>
        @endif
    </div>
</div>
<!-- resources/views/livewire/pitch/component/update-pitch-status.blade.php -->
<div>
    <div>
        <h3>{{ $pitch->readable_status }}</h3>
    </div>
    @if (session()->has('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif
    <div class="flex">
        @if ($status === \App\Models\Pitch::STATUS_PENDING)
        <button wire:click="changeStatus('forward')"
            class="btn btn-primary bg-accent hover:bg-accent-focus border-accent text-black flex-grow flex items-center justify-center">
            <i class="fas fa-check mr-2"></i>Allow Access
        </button>
        @elseif ($status === \App\Models\Pitch::STATUS_IN_PROGRESS)
        <button wire:click="changeStatus('backward')"
            class="btn btn-primary bg-warning hover:bg-warning/80 border-warning text-black flex-grow flex items-center justify-center">
            <i class="fas fa-times mr-2"></i>Remove Access
        </button>
        @endif
    </div>
</div>
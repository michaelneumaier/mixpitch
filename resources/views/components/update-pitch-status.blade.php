<div>
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
            <form method="POST" action="{{ route('projects.pitches.change-status', ['project' => $pitch->project, 'pitch' => $pitch]) }}">
                @csrf
                <input type="hidden" name="direction" value="forward">
                <input type="hidden" name="newStatus" value="{{ \App\Models\Pitch::STATUS_IN_PROGRESS }}">
                <button type="submit" class="btn btn-sm shadow-sm bg-accent hover:bg-accent-focus border-0 text-black flex items-center justify-center transition-colors z-30 relative">
                    <i class="fas fa-check mr-2"></i>Allow Access
                </button>
            </form>
        @elseif ($status === \App\Models\Pitch::STATUS_IN_PROGRESS)
            <form method="POST" action="{{ route('projects.pitches.change-status', ['project' => $pitch->project, 'pitch' => $pitch]) }}">
                @csrf
                <input type="hidden" name="direction" value="backward">
                <input type="hidden" name="newStatus" value="{{ \App\Models\Pitch::STATUS_PENDING }}">
                <button type="submit" class="btn btn-sm shadow-sm bg-warning hover:bg-warning/80 border-0 text-black flex items-center justify-center transition-colors z-30 relative">
                    <i class="fas fa-times mr-2"></i>Remove Access
                </button>
            </form>
        @elseif ($status === \App\Models\Pitch::STATUS_PENDING_REVIEW)
            <form method="POST" action="{{ route('projects.pitches.change-status', ['project' => $pitch->project, 'pitch' => $pitch]) }}">
                @csrf
                <input type="hidden" name="direction" value="backward">
                <input type="hidden" name="newStatus" value="{{ \App\Models\Pitch::STATUS_PENDING }}">
                <button type="submit" class="btn btn-sm shadow-sm bg-warning hover:bg-warning/80 border-0 text-black flex items-center justify-center transition-colors z-30 relative">
                    <i class="fas fa-times mr-2"></i>Remove Access
                </button>
            </form>
        @elseif ($status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
            <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $pitch->current_snapshot_id]) }}"
                class="btn btn-sm shadow-sm bg-green-500 hover:bg-green-700 border-0 text-white flex items-center justify-center transition-colors z-30 relative">
                <i class="fas fa-list mr-2"></i>Review
            </a>

            @if($pitch->current_snapshot_id)
            <button type="button"
                onclick="openApproveModal({{ $pitch->current_snapshot_id }}, '{{ addslashes(route('projects.pitches.approve-snapshot', ['project' => $pitch->project, 'pitch' => $pitch, 'snapshot' => $pitch->current_snapshot_id])) }}')"
                class="btn btn-sm shadow-sm bg-success hover:bg-success/80 border-0 text-white flex items-center justify-center transition-colors z-30 relative">
                <i class="fas fa-check mr-2"></i>Approve
            </button>

            <button type="button"
                onclick="openDenyModal({{ $pitch->current_snapshot_id }}, '{{ addslashes(route('projects.pitches.deny-snapshot', ['project' => $pitch->project, 'pitch' => $pitch, 'snapshot' => $pitch->current_snapshot_id])) }}')"
                class="btn btn-sm shadow-sm bg-error hover:bg-error/80 border-0 text-white flex items-center justify-center transition-colors z-30 relative">
                <i class="fas fa-times mr-2"></i>Deny
            </button>

            <button type="button"
                onclick="openRevisionsModal({{ $pitch->current_snapshot_id }}, '{{ addslashes(route('projects.pitches.request-changes', ['project' => $pitch->project, 'pitch' => $pitch, 'snapshot' => $pitch->current_snapshot_id])) }}')"
                class="btn btn-sm shadow-sm bg-info hover:bg-info/80 border-0 text-white flex items-center justify-center transition-colors z-30 relative">
                <i class="fas fa-edit mr-2"></i>Request Revisions
            </button>
            @endif

        @elseif ($status === \App\Models\Pitch::STATUS_APPROVED)
            <form method="POST" action="{{ route('projects.pitches.change-status', ['project' => $pitch->project, 'pitch' => $pitch]) }}">
                @csrf
                <input type="hidden" name="direction" value="backward">
                <input type="hidden" name="newStatus" value="{{ \App\Models\Pitch::STATUS_READY_FOR_REVIEW }}">
                <button type="submit" class="btn btn-sm shadow-sm bg-info hover:bg-info/80 border-0 text-white flex items-center justify-center transition-colors z-30 relative">
                    <i class="fas fa-undo mr-2"></i>Return to Review
                </button>
            </form>
        @elseif ($status === \App\Models\Pitch::STATUS_REVISIONS_REQUESTED)
            <form method="POST" action="{{ route('projects.pitches.change-status', ['project' => $pitch->project, 'pitch' => $pitch]) }}">
                @csrf
                <input type="hidden" name="direction" value="backward">
                <input type="hidden" name="newStatus" value="{{ \App\Models\Pitch::STATUS_READY_FOR_REVIEW }}">
                <button type="submit" class="btn btn-sm shadow-sm bg-info hover:bg-info/80 border-0 text-white flex items-center justify-center transition-colors z-30 relative">
                    <i class="fas fa-undo mr-2"></i>Return to Review
                </button>
            </form>
        @elseif ($status === \App\Models\Pitch::STATUS_DENIED)
            <form method="POST" action="{{ route('projects.pitches.change-status', ['project' => $pitch->project, 'pitch' => $pitch]) }}">
                @csrf
                <input type="hidden" name="direction" value="backward">
                <input type="hidden" name="newStatus" value="{{ \App\Models\Pitch::STATUS_READY_FOR_REVIEW }}">
                <button type="submit" class="btn btn-sm shadow-sm bg-accent hover:bg-accent-focus border-0 text-black flex items-center justify-center transition-colors z-30 relative">
                    <i class="fas fa-undo mr-2"></i>Return to Review
                </button>
            </form>
        @elseif ($status === \App\Models\Pitch::STATUS_COMPLETED)
            @if($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PENDING || $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_FAILED)
            <form method="POST" action="{{ route('projects.pitches.return-to-approved', ['project' => $pitch->project, 'pitch' => $pitch]) }}">
                @csrf
                <button type="submit" class="btn btn-sm shadow-sm bg-warning hover:bg-warning/80 border-0 text-black flex items-center justify-center transition-colors z-30 relative">
                    <i class="fas fa-undo mr-2"></i>Return to Approved
                </button>
            </form>
            @elseif($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID || $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING)
            <div class="text-gray-500 text-sm italic flex items-center">
                <i class="fas fa-lock mr-2"></i>Payment processed - Status locked
            </div>
            @endif
        @endif
    </div>

    <!-- Include the shared modals component -->
    <x-pitch-action-modals />
</div>
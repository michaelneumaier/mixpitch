<div class="flex flex-col sm:flex-row sm:flex-wrap gap-2 sm:gap-2 z-20 relative">
    @if($pitch->is_inactive)
        <div class="flex items-center justify-center w-full text-gray-500 text-sm italic bg-gradient-to-r from-gray-50/80 to-gray-100/80 backdrop-blur-sm border border-gray-200/50 rounded-xl p-3">
            <i class="fas fa-info-circle mr-2"></i>This pitch is inactive because another pitch has been completed
        </div>
    @elseif($status === \App\Models\Pitch::STATUS_CLOSED)
        <div class="flex items-center justify-center w-full text-gray-500 text-sm italic bg-gradient-to-r from-gray-50/80 to-gray-100/80 backdrop-blur-sm border border-gray-200/50 rounded-xl p-3">
            <i class="fas fa-info-circle mr-2"></i>This pitch has been closed because another pitch was selected
        </div>
    @elseif ($status === \App\Models\Pitch::STATUS_PENDING)
        <form method="POST" action="{{ route('projects.pitches.change-status', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]) }}">
            @csrf
            <input type="hidden" name="direction" value="forward">
            <input type="hidden" name="newStatus" value="{{ \App\Models\Pitch::STATUS_IN_PROGRESS }}">
            <flux:button 
                type="submit" 
                icon="check" 
                variant="primary" 
                color="green" 
                size="sm"
                class="min-h-[44px] sm:min-h-[32px] w-full sm:w-auto"
            >
                Allow Access
            </flux:button>
        </form>
    @elseif ($status === \App\Models\Pitch::STATUS_IN_PROGRESS)
        <form method="POST" action="{{ route('projects.pitches.change-status', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]) }}">
            @csrf
            <input type="hidden" name="direction" value="backward">
            <input type="hidden" name="newStatus" value="{{ \App\Models\Pitch::STATUS_PENDING }}">
            <flux:button 
                type="submit" 
                icon="x-mark" 
                variant="primary" 
                color="amber" 
                size="sm"
                class="min-h-[44px] sm:min-h-[32px] w-full sm:w-auto"
            >
                Remove Access
            </flux:button>
        </form>
    @elseif ($status === \App\Models\Pitch::STATUS_PENDING_REVIEW)
        <form method="POST" action="{{ route('projects.pitches.change-status', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]) }}">
            @csrf
            <input type="hidden" name="direction" value="backward">
            <input type="hidden" name="newStatus" value="{{ \App\Models\Pitch::STATUS_PENDING }}">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg z-30 relative">
                <i class="fas fa-times mr-2"></i>Remove Access
            </button>
        </form>
    @elseif ($status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
        <div class="flex items-center gap-2">
            <!-- Primary Action: Review -->
            <flux:button 
                href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $pitch->current_snapshot_id]) }}"
                wire:navigate
                icon="list-bullet" 
                variant="primary" 
                size="sm"
                class="z-30 relative"
            >
                Review
            </flux:button>

            @if($pitch->current_snapshot_id)
                <!-- Flux UI Dropdown for Additional Actions -->
                <flux:dropdown position="bottom" align="end">
                    <flux:button 
                        icon:trailing="chevron-down" 
                        icon="cog-6-tooth"
                        variant="filled" 
                        size="sm"
                        class="z-30 relative"
                    >
                        Actions
                    </flux:button>
                    
                    <flux:menu>
                        <flux:menu.item 
                            onclick="openApproveModal({{ $pitch->current_snapshot_id }}, '{{ addslashes(route('projects.pitches.approve-snapshot', ['project' => $pitch->project, 'pitch' => $pitch, 'snapshot' => $pitch->current_snapshot_id])) }}')"
                            icon="check"
                            class="text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20"
                        >
                            Approve
                        </flux:menu.item>

                        <flux:menu.item 
                            onclick="openDenyModal({{ $pitch->current_snapshot_id }}, '{{ addslashes(route('projects.pitches.deny-snapshot', ['project' => $pitch->project, 'pitch' => $pitch, 'snapshot' => $pitch->current_snapshot_id])) }}')"
                            icon="x-mark"
                            class="text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                        >
                            Deny
                        </flux:menu.item>

                        <flux:menu.item 
                            onclick="openRevisionsModal({{ $pitch->current_snapshot_id }}, '{{ addslashes(route('projects.pitches.request-changes', ['project' => $pitch->project, 'pitch' => $pitch, 'snapshot' => $pitch->current_snapshot_id])) }}')"
                            icon="pencil"
                            class="text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                        >
                            Request Revisions
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endif
        </div>
    @elseif ($status === \App\Models\Pitch::STATUS_APPROVED)
        <form method="POST" action="{{ route('projects.pitches.change-status', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]) }}">
            @csrf
            <input type="hidden" name="direction" value="backward">
            <input type="hidden" name="newStatus" value="{{ \App\Models\Pitch::STATUS_READY_FOR_REVIEW }}">
            <flux:button 
                type="submit" 
                icon="arrow-uturn-left" 
                variant="primary" 
                color="blue" 
                size="sm"
                class="min-h-[44px] sm:min-h-[32px] w-full sm:w-auto"
            >
                Return to Review
            </flux:button>
        </form>
    @elseif ($status === \App\Models\Pitch::STATUS_REVISIONS_REQUESTED)
        <form method="POST" action="{{ route('projects.pitches.change-status', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]) }}">
            @csrf
            <input type="hidden" name="direction" value="backward">
            <input type="hidden" name="newStatus" value="{{ \App\Models\Pitch::STATUS_READY_FOR_REVIEW }}">
            <flux:button 
                type="submit" 
                icon="arrow-uturn-left" 
                variant="primary" 
                color="blue" 
                size="sm"
                class="min-h-[44px] sm:min-h-[32px] w-full sm:w-auto"
            >
                Return to Review
            </flux:button>
        </form>
    @elseif ($status === \App\Models\Pitch::STATUS_DENIED)
        <form method="POST" action="{{ route('projects.pitches.change-status', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]) }}">
            @csrf
            <input type="hidden" name="direction" value="backward">
            <input type="hidden" name="newStatus" value="{{ \App\Models\Pitch::STATUS_READY_FOR_REVIEW }}">
            <flux:button 
                type="submit" 
                icon="arrow-uturn-left" 
                variant="primary" 
                color="green" 
                size="sm"
                class="min-h-[44px] sm:min-h-[32px] w-full sm:w-auto"
            >
                Return to Review
            </flux:button>
        </form>
    @elseif ($status === \App\Models\Pitch::STATUS_COMPLETED)
        @if($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PENDING || $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_FAILED)
            <form method="POST" action="{{ route('projects.pitches.return-to-approved', ['project' => $pitch->project, 'pitch' => $pitch]) }}">
                @csrf
                <flux:button 
                    type="submit" 
                    icon="arrow-uturn-left" 
                    variant="primary" 
                    color="amber" 
                    size="sm"
                    class="min-h-[44px] sm:min-h-[32px] w-full sm:w-auto"
                >
                    Return to Approved
                </flux:button>
            </form>
        @elseif($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID || $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING)
            <div class="flex items-center text-gray-500 text-sm italic bg-gradient-to-r from-gray-50/80 to-gray-100/80 backdrop-blur-sm border border-gray-200/50 rounded-xl p-3">
                <i class="fas fa-lock mr-2"></i>Payment processed - Status locked
            </div>
        @endif
    @endif
</div>
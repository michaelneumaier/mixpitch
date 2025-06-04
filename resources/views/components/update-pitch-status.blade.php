<div class="flex flex-wrap gap-2 z-20 relative">
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
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg z-30 relative">
                <i class="fas fa-check mr-2"></i>Allow Access
            </button>
        </form>
    @elseif ($status === \App\Models\Pitch::STATUS_IN_PROGRESS)
        <form method="POST" action="{{ route('projects.pitches.change-status', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]) }}">
            @csrf
            <input type="hidden" name="direction" value="backward">
            <input type="hidden" name="newStatus" value="{{ \App\Models\Pitch::STATUS_PENDING }}">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg z-30 relative">
                <i class="fas fa-times mr-2"></i>Remove Access
            </button>
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
            <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $pitch->current_snapshot_id]) }}"
                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg z-30 relative">
                <i class="fas fa-list mr-2"></i>Review
            </a>

            @if($pitch->current_snapshot_id)
                <!-- Dropdown Menu for Additional Actions -->
                <div class="relative inline-block text-left" x-data="{ open: false }">
                    <button type="button" 
                        @click="open = !open"
                        @click.away="open = false"
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg z-30 relative">
                        <i class="fas fa-cog mr-2"></i>Actions
                        <i class="fas fa-chevron-down ml-2 text-xs"></i>
                    </button>

                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-white/95 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-lg z-30 overflow-hidden">
                        
                        <button type="button"
                            onclick="openApproveModal({{ $pitch->current_snapshot_id }}, '{{ addslashes(route('projects.pitches.approve-snapshot', ['project' => $pitch->project, 'pitch' => $pitch, 'snapshot' => $pitch->current_snapshot_id])) }}')"
                            class="w-full flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition-colors duration-150">
                            <i class="fas fa-check mr-3 text-green-600"></i>Approve
                        </button>

                        <button type="button"
                            onclick="openDenyModal({{ $pitch->current_snapshot_id }}, '{{ addslashes(route('projects.pitches.deny-snapshot', ['project' => $pitch->project, 'pitch' => $pitch, 'snapshot' => $pitch->current_snapshot_id])) }}')"
                            class="w-full flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors duration-150">
                            <i class="fas fa-times mr-3 text-red-600"></i>Deny
                        </button>

                        <button type="button"
                            onclick="openRevisionsModal({{ $pitch->current_snapshot_id }}, '{{ addslashes(route('projects.pitches.request-changes', ['project' => $pitch->project, 'pitch' => $pitch, 'snapshot' => $pitch->current_snapshot_id])) }}')"
                            class="w-full flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors duration-150">
                            <i class="fas fa-edit mr-3 text-blue-600"></i>Request Revisions
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @elseif ($status === \App\Models\Pitch::STATUS_APPROVED)
        <form method="POST" action="{{ route('projects.pitches.change-status', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]) }}">
            @csrf
            <input type="hidden" name="direction" value="backward">
            <input type="hidden" name="newStatus" value="{{ \App\Models\Pitch::STATUS_READY_FOR_REVIEW }}">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg z-30 relative">
                <i class="fas fa-undo mr-2"></i>Return to Review
            </button>
        </form>
    @elseif ($status === \App\Models\Pitch::STATUS_REVISIONS_REQUESTED)
        <form method="POST" action="{{ route('projects.pitches.change-status', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]) }}">
            @csrf
            <input type="hidden" name="direction" value="backward">
            <input type="hidden" name="newStatus" value="{{ \App\Models\Pitch::STATUS_READY_FOR_REVIEW }}">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg z-30 relative">
                <i class="fas fa-undo mr-2"></i>Return to Review
            </button>
        </form>
    @elseif ($status === \App\Models\Pitch::STATUS_DENIED)
        <form method="POST" action="{{ route('projects.pitches.change-status', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]) }}">
            @csrf
            <input type="hidden" name="direction" value="backward">
            <input type="hidden" name="newStatus" value="{{ \App\Models\Pitch::STATUS_READY_FOR_REVIEW }}">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg z-30 relative">
                <i class="fas fa-undo mr-2"></i>Return to Review
            </button>
        </form>
    @elseif ($status === \App\Models\Pitch::STATUS_COMPLETED)
        @if($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PENDING || $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_FAILED)
            <form method="POST" action="{{ route('projects.pitches.return-to-approved', ['project' => $pitch->project, 'pitch' => $pitch]) }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg z-30 relative">
                    <i class="fas fa-undo mr-2"></i>Return to Approved
                </button>
            </form>
        @elseif($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID || $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING)
            <div class="flex items-center text-gray-500 text-sm italic bg-gradient-to-r from-gray-50/80 to-gray-100/80 backdrop-blur-sm border border-gray-200/50 rounded-xl p-3">
                <i class="fas fa-lock mr-2"></i>Payment processed - Status locked
            </div>
        @endif
    @endif
</div>
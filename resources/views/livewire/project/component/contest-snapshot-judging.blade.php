{{-- Contest Snapshot Judging Component --}}
<div>
@if($project->isContest())
    <flux:card class="bg-gradient-to-br from-orange-50/90 to-amber-50/90 dark:from-orange-950/90 dark:to-amber-950/90 border border-orange-200/50 dark:border-orange-800/50 mb-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <flux:icon name="scale" variant="solid" class="text-orange-600 dark:text-orange-400 h-8 w-8" />
                <div>
                    <flux:heading size="lg" class="text-orange-900 dark:text-orange-100">Contest Entry Judging</flux:heading>
                    <flux:subheading class="text-orange-600 dark:text-orange-400">Review and place this contest entry</flux:subheading>
                </div>
            </div>
            
            {{-- Current Placement Badge --}}
            @if($currentPlacement)
                <div class="flex items-center px-4 py-2 border rounded-xl {{ $this->getPlacementBadgeClass() }}">
                    <span class="text-xl mr-2">{{ $this->getPlacementIcon() }}</span>
                    <span class="font-semibold">{{ $pitch->getPlacementLabel() }}</span>
                </div>
            @else
                <flux:badge color="zinc" size="sm" icon="question-mark-circle">Not Placed</flux:badge>
            @endif
        </div>

        {{-- Contest Entry Info --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <flux:text size="sm" class="font-medium text-orange-700 dark:text-orange-300 mb-2">Contestant</flux:text>
                <div class="flex items-center gap-3">
                    <flux:avatar 
                        name="{{ $pitch->user->name }}" 
                        size="sm"
                        class="flex-shrink-0"
                    />
                    <div>
                        <flux:text size="sm" class="font-medium text-gray-900 dark:text-gray-100">{{ $pitch->user->name }}</flux:text>
                        <flux:text size="xs" class="text-gray-500 dark:text-gray-400">{{ $pitch->user->email }}</flux:text>
                    </div>
                </div>
            </div>
            
            <div>
                <flux:text size="sm" class="font-medium text-orange-700 dark:text-orange-300 mb-2">Submission Date</flux:text>
                @if($pitch->submitted_at)
                    <flux:text size="sm" class="text-gray-900 dark:text-gray-100">{{ $pitch->submitted_at->format('M j, Y g:i A') }}</flux:text>
                    <flux:text size="xs" class="text-gray-500 dark:text-gray-400">{{ $pitch->submitted_at->diffForHumans() }}</flux:text>
                @else
                    <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Not submitted</flux:text>
                @endif
            </div>
        </div>

        {{-- Judging Controls --}}
        @if($canJudge && !$isFinalized)
            <flux:separator class="my-6" />
            
            <flux:field>
                <flux:label>Contest Placement</flux:label>
                <flux:select wire:change="updatePlacement($event.target.value)">
                    @foreach($availablePlacements as $value => $label)
                        <option 
                            value="{{ $value }}" 
                            {{ $currentPlacement === $value ? 'selected' : '' }}
                            {{ strpos($label, 'Already Chosen') !== false ? 'disabled' : '' }}
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </flux:select>
            </flux:field>
            
            <div class="mt-3 flex items-center">
                <flux:icon name="light-bulb" class="mr-2 h-4 w-4 text-orange-500" />
                <flux:text size="xs" class="text-gray-500 dark:text-gray-400">
                    You can change placements anytime before finalizing the contest judging.
                </flux:text>
            </div>
        @elseif($isFinalized)
            {{-- Show finalized message --}}
            <flux:separator class="my-6" />
            
            <flux:callout color="green" icon="check-circle">
                <flux:callout.heading>Contest Judging Finalized</flux:callout.heading>
                <flux:callout.text>
                    @if($project->judging_finalized_at)
                        Completed on {{ $project->judging_finalized_at->format('M j, Y g:i A') }}
                    @endif
                </flux:callout.text>
            </flux:callout>
        @elseif(!$canJudge)
            {{-- Show message for non-judges --}}
            <flux:separator class="my-6" />
            
            <flux:callout color="blue" icon="information-circle">
                <flux:callout.heading>Contest Entry</flux:callout.heading>
                <flux:callout.text>
                    This entry is part of the contest judging process
                </flux:callout.text>
            </flux:callout>
        @endif
    </flux:card>
@endif
</div>

<div>
    <flux:card class="mb-4">
        <!-- Header -->
        <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <flux:icon.flag variant="solid"
                class="{{ $workflowColors['icon'] }} h-6 w-6" />
            <div>
                <flux:heading size="base"
                    class="{{ $workflowColors['text_primary'] }}">Milestone Payments
                </flux:heading>
                <flux:text size="xs"
                    class="{{ $workflowColors['text_muted'] }}">
                    Split payments & budget
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Budget Header Section -->
    <div class="mb-4 rounded-lg border-2 border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-900/20">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <flux:text size="sm" class="mb-1 font-medium text-purple-700 dark:text-purple-300">
                    Total Budget
                </flux:text>
                @if (!$showBudgetEditForm)
                    <div class="flex items-baseline gap-2">
                        <flux:heading size="2xl" class="font-bold text-purple-900 dark:text-purple-100">
                            ${{ number_format($pitch->payment_amount ?? $project->budget ?? 0, 2) }}
                        </flux:heading>
                    </div>
                @else
                    <!-- Budget Edit Form -->
                    <div class="mt-2 flex items-center gap-2">
                        <div class="flex-1">
                            <flux:input
                                type="number"
                                step="0.01"
                                wire:model.defer="editableBudget"
                                placeholder="0.00"
                                size="sm"
                            />
                        </div>
                        <div class="flex gap-1">
                            <flux:button wire:click="saveBudget" variant="primary" color="green" size="xs">
                                <flux:icon.check class="h-3 w-3" />
                            </flux:button>
                            <flux:button wire:click="cancelBudgetEdit" variant="ghost" size="xs">
                                <flux:icon.x-mark class="h-3 w-3" />
                            </flux:button>
                        </div>
                    </div>
                    @error('editableBudget')
                        <flux:text size="xs" class="mt-1 text-red-600 dark:text-red-400">
                            {{ $message }}
                        </flux:text>
                    @enderror
                @endif
            </div>
            @if (!$showBudgetEditForm)
                <flux:button wire:click="toggleBudgetEdit" variant="ghost" size="xs">
                    <flux:icon.pencil class="h-4 w-4" />
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Allocation Tracker -->
    @php
        $allocationStatus = $this->allocationStatus;
    @endphp
    <div class="mb-4 rounded-lg border-2 p-3 {{ $allocationStatus['color']['border'] }} {{ $allocationStatus['color']['bg'] }}">
        <div class="mb-2 flex items-center justify-between">
            <flux:text size="sm" class="font-medium {{ $allocationStatus['color']['text'] }}">
                Allocation Status
            </flux:text>
            <flux:text size="sm" class="font-semibold {{ $allocationStatus['color']['text'] }}">
                ${{ number_format($allocationStatus['allocated'], 2) }} / ${{ number_format($allocationStatus['budget'], 2) }}
            </flux:text>
        </div>

        <!-- Progress Bar -->
        <div class="mb-2 h-2 w-full overflow-hidden rounded-full bg-white/50 dark:bg-gray-800/50">
            <div class="h-2 rounded-full transition-all duration-300 {{ $allocationStatus['color']['bar'] }}"
                style="width: {{ min($allocationStatus['percentage'], 100) }}%"></div>
        </div>

        <div class="flex items-center justify-between">
            <flux:text size="xs" class="{{ $allocationStatus['color']['text'] }}">
                {{ $allocationStatus['message'] }}
            </flux:text>
            @if ($allocationStatus['status'] === 'perfect')
                <flux:icon.check-circle class="h-4 w-4 {{ $allocationStatus['color']['icon'] }}" />
            @elseif ($allocationStatus['status'] === 'under')
                <flux:icon.exclamation-triangle class="h-4 w-4 {{ $allocationStatus['color']['icon'] }}" />
            @elseif ($allocationStatus['status'] === 'over')
                <flux:icon.exclamation-circle class="h-4 w-4 {{ $allocationStatus['color']['icon'] }}" />
            @endif
        </div>
    </div>

    <!-- Revision Policy Section -->
    <div class="mb-4 rounded-lg border-2 border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon.arrow-path class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                    <flux:text size="sm" class="font-medium text-blue-700 dark:text-blue-300">
                        Revision Policy
                    </flux:text>
                </div>

                @if (!$showRevisionSettingsForm)
                    <div class="space-y-1">
                        <flux:text size="sm" class="text-blue-900 dark:text-blue-100">
                            <strong>{{ $pitch->included_revisions ?? 2 }}</strong> revisions included
                        </flux:text>
                        @if ($pitch->additional_revision_price > 0)
                            <flux:text size="sm" class="text-blue-900 dark:text-blue-100">
                                Additional revisions: <strong>${{ number_format($pitch->additional_revision_price, 2) }}</strong> each
                            </flux:text>
                        @endif
                        @if ($pitch->revision_scope_guidelines)
                            <flux:text size="xs" class="text-blue-700 dark:text-blue-300 mt-1">
                                {{ $pitch->revision_scope_guidelines }}
                            </flux:text>
                        @endif
                    </div>
                @else
                    <!-- Revision Settings Edit Form -->
                    <div class="mt-2 space-y-3">
                        <div>
                            <flux:label size="sm">Included Revisions</flux:label>
                            <flux:input
                                type="number"
                                wire:model.defer="editableIncludedRevisions"
                                placeholder="2"
                                size="sm"
                                min="0"
                                max="10"
                            />
                            @error('editableIncludedRevisions')
                                <flux:text size="xs" class="mt-1 text-red-600 dark:text-red-400">
                                    {{ $message }}
                                </flux:text>
                            @enderror
                        </div>

                        <div>
                            <flux:label size="sm">Additional Revision Price</flux:label>
                            <flux:input
                                type="number"
                                step="0.01"
                                wire:model.defer="editableAdditionalRevisionPrice"
                                placeholder="0.00"
                                size="sm"
                            />
                            @error('editableAdditionalRevisionPrice')
                                <flux:text size="xs" class="mt-1 text-red-600 dark:text-red-400">
                                    {{ $message }}
                                </flux:text>
                            @enderror
                        </div>

                        <div>
                            <flux:label size="sm">Scope Guidelines (Optional)</flux:label>
                            <flux:textarea
                                wire:model.defer="editableRevisionScopeGuidelines"
                                placeholder="Define what counts as a revision vs. new work..."
                                size="sm"
                                rows="2"
                            />
                            @error('editableRevisionScopeGuidelines')
                                <flux:text size="xs" class="mt-1 text-red-600 dark:text-red-400">
                                    {{ $message }}
                                </flux:text>
                            @enderror
                        </div>

                        <div class="flex gap-2">
                            <flux:button wire:click="saveRevisionSettings" variant="primary" color="blue" size="xs">
                                <flux:icon.check class="h-3 w-3" />
                                Save
                            </flux:button>
                            <flux:button wire:click="cancelRevisionSettings" variant="ghost" size="xs">
                                <flux:icon.x-mark class="h-3 w-3" />
                                Cancel
                            </flux:button>
                        </div>
                    </div>
                @endif
            </div>
            @if (!$showRevisionSettingsForm)
                <flux:button wire:click="toggleRevisionSettings" variant="ghost" size="xs">
                    <flux:icon.pencil class="h-4 w-4" />
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mb-4 flex flex-wrap gap-2">
        <flux:button wire:click="beginAddMilestone" variant="primary" size="sm" icon="plus">
            Add Milestone
        </flux:button>
        <flux:button wire:click="openSplitBudgetModal" variant="outline" size="sm" icon="scissors">
            Split Budget
        </flux:button>
    </div>

    <!-- Milestones List -->
    @if ($this->milestones->count())
        <div class="max-h-64 space-y-2 overflow-y-auto">
            @foreach ($this->milestones as $m)
                <div
                    class="{{ $workflowColors['accent_bg'] }} rounded-lg p-3 text-sm">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <div
                                class="{{ $workflowColors['text_primary'] }} truncate font-medium">
                                {{ $m->name }}
                            </div>
                            <div class="mt-1 flex items-center gap-2">
                                <span
                                    class="{{ $workflowColors['text_secondary'] }} font-medium">
                                    ${{ number_format($m->amount, 2) }}
                                </span>
                                @if ($m->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                    <flux:badge variant="success" size="xs">
                                        Paid
                                    </flux:badge>
                                @elseif ($m->status === 'approved')
                                    <flux:badge variant="primary" size="xs">
                                        Approved</flux:badge>
                                @else
                                    <flux:badge variant="outline" size="xs">
                                        Pending</flux:badge>
                                @endif
                            </div>
                        </div>
                        <flux:button
                            wire:click="beginEditMilestone({{ $m->id }})"
                            variant="ghost" size="xs">
                            <flux:icon.pencil class="h-3 w-3" />
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="py-4 text-center">
            <flux:text size="sm"
                class="{{ $workflowColors['text_muted'] }}">
                No milestones yet
            </flux:text>
        </div>
    @endif
    </flux:card>

    <!-- Add Milestone Modal -->
    <flux:modal wire:model="showAddMilestoneModal" class="max-w-lg">
        <div class="space-y-4">
        <div class="flex items-center gap-3">
            <flux:icon.plus class="h-6 w-6 {{ $workflowColors['icon'] }}" />
            <flux:heading size="lg">Add Milestone</flux:heading>
        </div>

        <!-- Remaining Budget Helper -->
        <div class="rounded-lg border p-3 text-center
            {{ $this->remainingBudgetForForm > 0 ? 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20' : 'border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/20' }}">
            <flux:text size="sm" class="{{ $this->remainingBudgetForForm > 0 ? 'text-blue-800 dark:text-blue-200' : 'text-gray-700 dark:text-gray-300' }}">
                <span class="font-medium">Budget Available:</span>
                ${{ number_format($this->remainingBudgetForForm, 2) }}
            </flux:text>
        </div>

        <div class="space-y-3">
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input type="text" wire:model.defer="milestoneName"
                    placeholder="e.g., Initial Deposit" />
                <flux:error name="milestoneName" />
            </flux:field>

            <flux:field>
                <div class="flex items-center justify-between">
                    <flux:label>Amount</flux:label>
                    @if ($this->remainingBudgetForForm > 0)
                        <flux:button wire:click="$set('milestoneAmount', {{ $this->remainingBudgetForForm }})"
                            variant="ghost" size="xs">
                            <flux:icon.calculator class="h-3 w-3 mr-1" />
                            Use Remaining
                        </flux:button>
                    @endif
                </div>
                <flux:input type="number" step="0.01"
                    wire:model.defer="milestoneAmount" placeholder="0.00" />
                @if ($milestoneAmount && $this->allocationStatus['budget'] > 0)
                    <flux:text size="xs" class="{{ $workflowColors['text_muted'] }} mt-1">
                        {{ number_format(($milestoneAmount / $this->allocationStatus['budget']) * 100, 1) }}% of total budget
                    </flux:text>
                @endif
                <flux:error name="milestoneAmount" />
            </flux:field>

            <flux:field>
                <flux:label>Description (Optional)</flux:label>
                <flux:textarea rows="2"
                    wire:model.defer="milestoneDescription"
                    placeholder="Optional details..." />
                <flux:error name="milestoneDescription" />
            </flux:field>
        </div>

        <div class="flex items-center justify-end gap-2 pt-4">
            <flux:button wire:click="cancelMilestoneForm" variant="ghost">
                Cancel
            </flux:button>
            <flux:button wire:click="saveMilestone" variant="primary" icon="check">
                Create Milestone
            </flux:button>
        </div>
        </div>
    </flux:modal>

    <!-- Edit Milestone Modal -->
    <flux:modal wire:model="showEditMilestoneModal" class="max-w-lg">
        <div class="space-y-4">
        <div class="flex items-center gap-3">
            <flux:icon.pencil class="h-6 w-6 {{ $workflowColors['icon'] }}" />
            <flux:heading size="lg">Edit Milestone</flux:heading>
        </div>

        <!-- Remaining Budget Helper -->
        <div class="rounded-lg border p-3 text-center
            {{ $this->remainingBudgetForForm > 0 ? 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20' : 'border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/20' }}">
            <flux:text size="sm" class="{{ $this->remainingBudgetForForm > 0 ? 'text-blue-800 dark:text-blue-200' : 'text-gray-700 dark:text-gray-300' }}">
                <span class="font-medium">Budget Available:</span>
                ${{ number_format($this->remainingBudgetForForm, 2) }}
            </flux:text>
        </div>

        <div class="space-y-3">
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input type="text" wire:model.defer="milestoneName"
                    placeholder="e.g., Initial Deposit" />
                <flux:error name="milestoneName" />
            </flux:field>

            <flux:field>
                <div class="flex items-center justify-between">
                    <flux:label>Amount</flux:label>
                    @if ($editingMilestoneId && $this->remainingBudgetForForm > 0)
                        <flux:button wire:click="$set('milestoneAmount', {{ $this->remainingBudgetForForm }})"
                            variant="ghost" size="xs">
                            <flux:icon.calculator class="h-3 w-3 mr-1" />
                            Use Remaining
                        </flux:button>
                    @endif
                </div>
                <flux:input type="number" step="0.01"
                    wire:model.defer="milestoneAmount" placeholder="0.00" />
                @if ($milestoneAmount && $this->allocationStatus['budget'] > 0)
                    <flux:text size="xs" class="{{ $workflowColors['text_muted'] }} mt-1">
                        {{ number_format(($milestoneAmount / $this->allocationStatus['budget']) * 100, 1) }}% of total budget
                    </flux:text>
                @endif
                <flux:error name="milestoneAmount" />
            </flux:field>

            <flux:field>
                <flux:label>Description (Optional)</flux:label>
                <flux:textarea rows="2"
                    wire:model.defer="milestoneDescription"
                    placeholder="Optional details..." />
                <flux:error name="milestoneDescription" />
            </flux:field>
        </div>

        <div class="flex items-center justify-between gap-2 pt-4">
            @if ($editingMilestoneId)
                @php
                    $editingMilestone = $pitch->milestones()->find($editingMilestoneId);
                @endphp
                @if ($editingMilestone && $editingMilestone->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_PAID)
                    <flux:button wire:click="deleteMilestone({{ $editingMilestoneId }})"
                        variant="danger" icon="trash">
                        Delete Milestone
                    </flux:button>
                @else
                    <div></div>
                @endif
            @else
                <div></div>
            @endif

            <div class="flex gap-2">
                <flux:button wire:click="cancelMilestoneForm" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="saveMilestone" variant="primary" icon="check">
                    Save Changes
                </flux:button>
            </div>
        </div>
        </div>
    </flux:modal>

    <!-- Split Budget Modal -->
    <flux:modal wire:model="showSplitBudgetModal" class="max-w-2xl">
        <div class="space-y-4">
        <div class="flex items-center gap-3">
            <flux:icon.scissors class="h-6 w-6 {{ $workflowColors['icon'] }}" />
            <flux:heading size="lg">Split Budget into Milestones</flux:heading>
        </div>

        <!-- Template Selection -->
        <flux:field>
            <flux:label>Split Template</flux:label>
            <div class="space-y-3">
                <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg border-2 transition-colors
                    {{ $splitTemplate === 'equal' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-purple-300' }}">
                    <input type="radio" wire:model.live="splitTemplate" value="equal"
                        class="mt-1 text-purple-600 focus:ring-purple-500">
                    <div class="flex-1">
                        <flux:text size="sm" class="font-medium {{ $workflowColors['text_primary'] }}">
                            Equal Split
                        </flux:text>
                        <flux:text size="xs" class="{{ $workflowColors['text_muted'] }}">
                            Divide budget evenly across milestones
                        </flux:text>
                    </div>
                </label>

                <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg border-2 transition-colors
                    {{ $splitTemplate === 'deposit_structure' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-purple-300' }}">
                    <input type="radio" wire:model.live="splitTemplate" value="deposit_structure"
                        class="mt-1 text-purple-600 focus:ring-purple-500">
                    <div class="flex-1">
                        <flux:text size="sm" class="font-medium {{ $workflowColors['text_primary'] }}">
                            Deposit Structure
                        </flux:text>
                        <flux:text size="xs" class="{{ $workflowColors['text_muted'] }}">
                            30% deposit / 40% progress / 30% final
                        </flux:text>
                    </div>
                </label>

                <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg border-2 transition-colors
                    {{ $splitTemplate === 'percentage' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-purple-300' }}">
                    <input type="radio" wire:model.live="splitTemplate" value="percentage"
                        class="mt-1 text-purple-600 focus:ring-purple-500">
                    <div class="flex-1">
                        <flux:text size="sm" class="font-medium {{ $workflowColors['text_primary'] }}">
                            Custom Percentages
                        </flux:text>
                        <flux:text size="xs" class="{{ $workflowColors['text_muted'] }}">
                            Define your own percentage split
                        </flux:text>
                    </div>
                </label>
            </div>
        </flux:field>

        <!-- Equal Split Options -->
        @if ($splitTemplate === 'equal')
            <flux:field>
                <flux:label>Number of Milestones</flux:label>
                <flux:input type="number" min="2" max="20"
                    wire:model.defer="splitCount" />
                <flux:error name="splitCount" />
            </flux:field>
        @endif

        <!-- Percentage Split Options -->
        @if ($splitTemplate === 'percentage')
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:label>Percentages (must sum to 100%)</flux:label>
                    <flux:button wire:click="addPercentageInput" variant="ghost" size="xs">
                        <flux:icon.plus class="h-3 w-3" />
                    </flux:button>
                </div>
                @foreach ($percentageSplit as $index => $percentage)
                    <div class="flex items-center gap-2">
                        <flux:text size="sm" class="w-28 {{ $workflowColors['text_secondary'] }}">
                            Milestone {{ $index + 1 }}:
                        </flux:text>
                        <flux:input type="number" step="0.1" min="0" max="100"
                            wire:model.defer="percentageSplit.{{ $index }}"
                            placeholder="0.0" class="flex-1" />
                        <flux:text size="sm" class="{{ $workflowColors['text_muted'] }}">%</flux:text>
                        @if (count($percentageSplit) > 2)
                            <flux:button wire:click="removePercentageInput({{ $index }})"
                                variant="ghost" size="xs">
                                <flux:icon.trash class="h-3 w-3 text-red-500" />
                            </flux:button>
                        @endif
                    </div>
                @endforeach
                @if (count($percentageSplit) === 0)
                    <div class="text-center py-4">
                        <flux:button wire:click="addPercentageInput" variant="outline" size="sm">
                            Add First Percentage
                        </flux:button>
                    </div>
                @endif
                @if (count($percentageSplit) > 0)
                    <div class="rounded-lg border p-3 text-center
                        {{ abs($this->percentageTotal - 100) < 0.01 ? 'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-900/20' : 'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/20' }}">
                        <flux:text size="sm" class="font-medium
                            {{ abs($this->percentageTotal - 100) < 0.01 ? 'text-green-800 dark:text-green-200' : 'text-amber-800 dark:text-amber-200' }}">
                            Total: {{ number_format($this->percentageTotal, 1) }}%
                            @if (abs($this->percentageTotal - 100) < 0.01)
                                <flux:icon.check-circle class="ml-1 inline h-4 w-4" />
                            @endif
                        </flux:text>
                    </div>
                @endif
                <flux:error name="percentageSplit" />
            </div>
        @endif

        <!-- Deposit Structure Preview -->
        @if ($splitTemplate === 'deposit_structure')
            @php
                $budget = $pitch->payment_amount ?? $project->budget ?? 0;
            @endphp
            <div class="rounded-lg border bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 p-4 {{ $workflowColors['accent_border'] }}">
                <flux:text size="sm" class="mb-3 font-semibold {{ $workflowColors['text_primary'] }}">
                    Preview:
                </flux:text>
                <div class="space-y-2">
                    <div class="flex items-center justify-between p-2 rounded bg-white/50 dark:bg-gray-800/50">
                        <flux:text size="sm" class="font-medium">Initial Deposit</flux:text>
                        <flux:text size="sm" class="text-purple-700 dark:text-purple-300">
                            ${{ number_format($budget * 0.30, 2) }} <span class="text-xs">(30%)</span>
                        </flux:text>
                    </div>
                    <div class="flex items-center justify-between p-2 rounded bg-white/50 dark:bg-gray-800/50">
                        <flux:text size="sm" class="font-medium">Progress Payment</flux:text>
                        <flux:text size="sm" class="text-purple-700 dark:text-purple-300">
                            ${{ number_format($budget * 0.40, 2) }} <span class="text-xs">(40%)</span>
                        </flux:text>
                    </div>
                    <div class="flex items-center justify-between p-2 rounded bg-white/50 dark:bg-gray-800/50">
                        <flux:text size="sm" class="font-medium">Final Payment</flux:text>
                        <flux:text size="sm" class="text-purple-700 dark:text-purple-300">
                            ${{ number_format($budget * 0.30, 2) }} <span class="text-xs">(30%)</span>
                        </flux:text>
                    </div>
                </div>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-2 pt-4">
            <flux:button wire:click="closeSplitBudgetModal" variant="ghost">
                Cancel
            </flux:button>
            <flux:button wire:click="splitBudgetIntoMilestones"
                variant="primary" icon="check">
                Create Milestones
            </flux:button>
        </div>
        </div>
    </flux:modal>
</div>

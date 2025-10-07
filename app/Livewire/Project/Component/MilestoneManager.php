<?php

namespace App\Livewire\Project\Component;

use App\Models\Pitch;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class MilestoneManager extends Component
{
    // Injected from parent
    public Pitch $pitch;

    public Project $project;

    public array $workflowColors;

    // Milestone form state
    public bool $showAddMilestoneModal = false;

    public bool $showEditMilestoneModal = false;

    public ?int $editingMilestoneId = null;

    public string $milestoneName = '';

    public ?string $milestoneDescription = null;

    public ?float $milestoneAmount = null;

    public ?int $milestoneSortOrder = null;

    // Split helper state
    public bool $showSplitBudgetModal = false;

    public int $splitCount = 2;

    public string $splitTemplate = 'equal';

    public array $percentageSplit = [];

    // Budget editing state
    public bool $showBudgetEditForm = false;

    public ?float $editableBudget = null;

    // Revision settings state
    public bool $showRevisionSettingsForm = false;

    public ?int $editableIncludedRevisions = null;

    public ?float $editableAdditionalRevisionPrice = null;

    public ?string $editableRevisionScopeGuidelines = null;

    protected $rules = [
        'milestoneName' => 'nullable|string|max:255',
        'milestoneDescription' => 'nullable|string|max:2000',
        'milestoneAmount' => 'nullable|numeric|min:0',
        'milestoneSortOrder' => 'nullable|integer|min:0',
        'editableBudget' => 'nullable|numeric|min:0|max:1000000',
        'splitTemplate' => 'nullable|string|in:equal,percentage,deposit_structure',
        'percentageSplit' => 'nullable|array',
        'percentageSplit.*' => 'nullable|numeric|min:0|max:100',
        'editableIncludedRevisions' => 'nullable|integer|min:0|max:10',
        'editableAdditionalRevisionPrice' => 'nullable|numeric|min:0',
        'editableRevisionScopeGuidelines' => 'nullable|string|max:2000',
    ];

    public function mount(Pitch $pitch, Project $project, array $workflowColors): void
    {
        $this->pitch = $pitch;
        $this->project = $project;
        $this->workflowColors = $workflowColors;
    }

    // ----- Budget Management -----
    private function getBaseClientBudget(): float
    {
        // Prefer explicit client payment amount on pitch; fallback to project budget
        $paymentAmount = (float) ($this->pitch->payment_amount ?? 0);
        if ($paymentAmount > 0) {
            return $paymentAmount;
        }

        return (float) ($this->project->budget ?? 0);
    }

    public function toggleBudgetEdit(): void
    {
        $this->authorize('update', $this->project);

        if ($this->showBudgetEditForm) {
            // Cancel edit
            $this->cancelBudgetEdit();
        } else {
            // Begin edit
            $this->editableBudget = $this->getBaseClientBudget();
            $this->showBudgetEditForm = true;
        }
    }

    public function saveBudget(): void
    {
        $this->authorize('update', $this->project);
        $this->validate([
            'editableBudget' => 'required|numeric|min:0|max:1000000',
        ]);

        try {
            DB::transaction(function () {
                $oldBudget = $this->getBaseClientBudget();
                $milestones = $this->pitch->milestones;
                $milestoneCount = $milestones->count();

                // Update pitch payment_amount (primary source of truth for milestones)
                $this->pitch->update([
                    'payment_amount' => $this->editableBudget,
                ]);

                // Optionally sync to project budget to keep consistent
                $this->project->update([
                    'budget' => $this->editableBudget,
                ]);

                // Handle milestone synchronization based on count and budget
                if ($milestoneCount === 1) {
                    $milestone = $milestones->first();

                    // If only one milestone exists, sync its amount with the new budget
                    if ($this->editableBudget > 0) {
                        $milestone->update(['amount' => $this->editableBudget]);
                        Log::info('Updated single milestone amount to match new budget', [
                            'pitch_id' => $this->pitch->id,
                            'milestone_id' => $milestone->id,
                            'old_amount' => $oldBudget,
                            'new_amount' => $this->editableBudget,
                        ]);
                    } else {
                        // Budget changed to $0, check if milestone is paid before deleting
                        if ($milestone->payment_status === Pitch::PAYMENT_STATUS_PAID) {
                            throw new \Exception('Cannot change budget to $0 when the milestone has already been paid. Payment has been received for this milestone.');
                        }

                        // Safe to delete unpaid milestone
                        $milestone->delete();
                        Log::info('Deleted single milestone as budget changed to $0', [
                            'pitch_id' => $this->pitch->id,
                            'milestone_id' => $milestone->id,
                        ]);
                    }
                } elseif ($milestoneCount === 0 && $this->editableBudget > 0) {
                    // No milestones exist but budget is set, create one
                    $this->pitch->milestones()->create([
                        'name' => 'Project Payment',
                        'description' => 'Full payment for project deliverables',
                        'amount' => $this->editableBudget,
                        'sort_order' => 1,
                        'status' => 'pending',
                        'payment_status' => null,
                    ]);
                    Log::info('Created milestone as budget was set with no existing milestones', [
                        'pitch_id' => $this->pitch->id,
                        'amount' => $this->editableBudget,
                    ]);
                }
                // If more than 1 milestone exists, don't auto-sync - producer manages them independently

                // Create audit event
                $this->pitch->events()->create([
                    'event_type' => 'budget_updated',
                    'comment' => 'Total budget updated to $'.number_format($this->editableBudget, 2),
                    'status' => $this->pitch->status,
                    'created_by' => auth()->id(),
                    'metadata' => [
                        'old_amount' => $oldBudget,
                        'new_amount' => $this->editableBudget,
                        'milestone_sync' => $milestoneCount === 1 ? 'synced' : ($milestoneCount === 0 && $this->editableBudget > 0 ? 'created' : 'no_change'),
                    ],
                ]);
            });

            $this->pitch->refresh();
            $this->project->refresh();
            $this->showBudgetEditForm = false;
            $this->editableBudget = null;

            Toaster::success('Budget updated successfully');
            $this->dispatch('budgetUpdated', budgetAmount: $this->getBaseClientBudget());
            $this->dispatch('milestonesUpdated'); // Refresh milestone display
        } catch (\Exception $e) {
            Log::error('Failed to update budget', [
                'pitch_id' => $this->pitch->id,
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update budget. Please try again.');
        }
    }

    public function cancelBudgetEdit(): void
    {
        $this->showBudgetEditForm = false;
        $this->editableBudget = null;
        $this->resetErrorBag('editableBudget');
    }

    // ----- Revision Settings Management -----
    public function toggleRevisionSettings(): void
    {
        $this->authorize('update', $this->project);

        if ($this->showRevisionSettingsForm) {
            // Cancel edit
            $this->cancelRevisionSettings();
        } else {
            // Begin edit - load current values
            $this->editableIncludedRevisions = $this->pitch->included_revisions ?? 2;
            $this->editableAdditionalRevisionPrice = $this->pitch->additional_revision_price;
            $this->editableRevisionScopeGuidelines = $this->pitch->revision_scope_guidelines;
            $this->showRevisionSettingsForm = true;
        }
    }

    public function saveRevisionSettings(): void
    {
        $this->authorize('update', $this->project);
        $this->validate([
            'editableIncludedRevisions' => 'required|integer|min:0|max:10',
            'editableAdditionalRevisionPrice' => 'nullable|numeric|min:0',
            'editableRevisionScopeGuidelines' => 'nullable|string|max:2000',
        ]);

        try {
            DB::transaction(function () {
                $oldIncludedRevisions = $this->pitch->included_revisions ?? 0;
                $oldAdditionalPrice = $this->pitch->additional_revision_price ?? 0;

                $this->pitch->update([
                    'included_revisions' => $this->editableIncludedRevisions,
                    'additional_revision_price' => $this->editableAdditionalRevisionPrice,
                    'revision_scope_guidelines' => $this->editableRevisionScopeGuidelines,
                ]);

                // Clean up unpaid revision milestones if price changed from > 0 to $0
                if ($oldAdditionalPrice > 0 && ($this->editableAdditionalRevisionPrice ?? 0) == 0) {
                    $deletedCount = $this->pitch->milestones()
                        ->where('is_revision_milestone', true)
                        ->whereNull('payment_status')
                        ->delete();

                    if ($deletedCount > 0) {
                        Log::info('Deleted unpaid revision milestones after price changed to $0', [
                            'pitch_id' => $this->pitch->id,
                            'deleted_count' => $deletedCount,
                        ]);
                    }
                }

                // Create audit event
                $this->pitch->events()->create([
                    'event_type' => 'revision_policy_updated',
                    'comment' => sprintf(
                        'Revision policy updated: %d included revisions, $%s per additional revision',
                        $this->editableIncludedRevisions,
                        number_format($this->editableAdditionalRevisionPrice ?? 0, 2)
                    ),
                    'status' => $this->pitch->status,
                    'created_by' => auth()->id(),
                    'metadata' => [
                        'old_included_revisions' => $oldIncludedRevisions,
                        'new_included_revisions' => $this->editableIncludedRevisions,
                        'old_additional_price' => $oldAdditionalPrice,
                        'new_additional_price' => $this->editableAdditionalRevisionPrice,
                    ],
                ]);
            });

            $this->pitch->refresh();
            $this->showRevisionSettingsForm = false;
            $this->resetRevisionSettingsForm();

            Toaster::success('Revision policy updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update revision settings', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update revision settings. Please try again.');
        }
    }

    public function cancelRevisionSettings(): void
    {
        $this->showRevisionSettingsForm = false;
        $this->resetRevisionSettingsForm();
    }

    private function resetRevisionSettingsForm(): void
    {
        $this->editableIncludedRevisions = null;
        $this->editableAdditionalRevisionPrice = null;
        $this->editableRevisionScopeGuidelines = null;
        $this->resetErrorBag(['editableIncludedRevisions', 'editableAdditionalRevisionPrice', 'editableRevisionScopeGuidelines']);
    }

    // ----- Allocation Status -----
    public function getAllocationStatusProperty(): array
    {
        $budget = $this->getBaseClientBudget();
        $allocated = (float) $this->pitch->milestones()->sum('amount');
        $remaining = $budget - $allocated;
        $percentage = $budget > 0 ? ($allocated / $budget * 100) : 0;

        // Determine status (with small tolerance for floating point precision)
        $status = 'perfect';
        if (abs($budget - $allocated) > 0.01) {
            $status = $allocated < $budget ? 'under' : 'over';
        }

        return [
            'budget' => $budget,
            'allocated' => $allocated,
            'remaining' => $remaining,
            'percentage' => min($percentage, 100), // Cap at 100% for display
            'status' => $status,
            'color' => $this->getAllocationStatusColor($status),
            'message' => $this->getAllocationStatusMessage($status, $remaining),
        ];
    }

    private function getAllocationStatusColor(string $status): array
    {
        return match ($status) {
            'perfect' => [
                'bg' => 'bg-green-100 dark:bg-green-900/20',
                'border' => 'border-green-300 dark:border-green-700',
                'text' => 'text-green-800 dark:text-green-200',
                'icon' => 'text-green-600 dark:text-green-400',
                'bar' => 'bg-green-500',
            ],
            'under' => [
                'bg' => 'bg-amber-100 dark:bg-amber-900/20',
                'border' => 'border-amber-300 dark:border-amber-700',
                'text' => 'text-amber-800 dark:text-amber-200',
                'icon' => 'text-amber-600 dark:text-amber-400',
                'bar' => 'bg-amber-500',
            ],
            'over' => [
                'bg' => 'bg-red-100 dark:bg-red-900/20',
                'border' => 'border-red-300 dark:border-red-700',
                'text' => 'text-red-800 dark:text-red-200',
                'icon' => 'text-red-600 dark:text-red-400',
                'bar' => 'bg-red-500',
            ],
            default => [
                'bg' => 'bg-gray-100 dark:bg-gray-900/20',
                'border' => 'border-gray-300 dark:border-gray-700',
                'text' => 'text-gray-800 dark:text-gray-200',
                'icon' => 'text-gray-600 dark:text-gray-400',
                'bar' => 'bg-gray-500',
            ],
        };
    }

    private function getAllocationStatusMessage(string $status, float $remaining): string
    {
        return match ($status) {
            'perfect' => 'All budget allocated to milestones',
            'under' => '$'.number_format(abs($remaining), 2).' remaining to allocate',
            'over' => '$'.number_format(abs($remaining), 2).' over budget',
            default => '',
        };
    }

    public function getRemainingBudgetForFormProperty(): float
    {
        $allocationStatus = $this->allocationStatus;
        $remainingBudget = $allocationStatus['remaining'];

        // If editing, add back the current milestone's amount to remaining
        if ($this->editingMilestoneId) {
            $editingMilestone = $this->pitch->milestones()->find($this->editingMilestoneId);
            $remainingBudget += $editingMilestone ? $editingMilestone->amount : 0;
        }

        return $remainingBudget;
    }

    public function getPercentageTotalProperty(): float
    {
        return array_sum($this->percentageSplit);
    }

    public function getMilestonesProperty()
    {
        return $this->pitch->milestones()->orderBy('sort_order')->get();
    }

    // ----- Milestone CRUD -----
    public function beginAddMilestone(): void
    {
        $this->authorize('update', $this->project);
        $this->resetMilestoneForm();
        $this->showAddMilestoneModal = true;
    }

    public function beginEditMilestone(int $milestoneId): void
    {
        $this->authorize('update', $this->project);
        $milestone = $this->pitch->milestones()->findOrFail($milestoneId);
        $this->editingMilestoneId = $milestone->id;
        $this->milestoneName = $milestone->name;
        $this->milestoneDescription = $milestone->description;
        $this->milestoneAmount = (float) $milestone->amount;
        $this->milestoneSortOrder = $milestone->sort_order;
        $this->showEditMilestoneModal = true;
    }

    public function cancelMilestoneForm(): void
    {
        $this->resetMilestoneForm();
        $this->showAddMilestoneModal = false;
        $this->showEditMilestoneModal = false;
    }

    public function saveMilestone(): void
    {
        $this->authorize('update', $this->project);
        $this->validate([
            'milestoneName' => 'required|string|max:255',
            'milestoneDescription' => 'nullable|string|max:2000',
            'milestoneAmount' => 'required|numeric|min:0',
            'milestoneSortOrder' => 'nullable|integer|min:0',
        ]);

        if ($this->editingMilestoneId) {
            $milestone = $this->pitch->milestones()->findOrFail($this->editingMilestoneId);

            // Prevent changing amount on paid milestones
            if ($milestone->payment_status === Pitch::PAYMENT_STATUS_PAID
                && $this->milestoneAmount !== null
                && (float) $this->milestoneAmount !== (float) $milestone->amount) {
                Toaster::error('Amount cannot be changed for a paid milestone.');

                return;
            }

            $updatePayload = [
                'name' => $this->milestoneName,
                'description' => $this->milestoneDescription,
                'sort_order' => $this->milestoneSortOrder,
            ];
            // Only update amount if not paid
            if ($milestone->payment_status !== Pitch::PAYMENT_STATUS_PAID) {
                $updatePayload['amount'] = $this->milestoneAmount;
            }

            $milestone->update($updatePayload);
            Toaster::success('Milestone updated');
        } else {
            $this->pitch->milestones()->create([
                'name' => $this->milestoneName,
                'description' => $this->milestoneDescription,
                'amount' => $this->milestoneAmount ?? 0,
                'sort_order' => $this->milestoneSortOrder,
                'status' => 'pending',
                'payment_status' => null,
            ]);
            Toaster::success('Milestone created');
        }

        $this->pitch->refresh();
        $this->dispatch('milestonesUpdated');
        $this->cancelMilestoneForm(); // This will close the modal
    }

    public function deleteMilestone(int $milestoneId): void
    {
        $this->authorize('update', $this->project);
        $milestone = $this->pitch->milestones()->findOrFail($milestoneId);
        // Prevent deleting paid milestones
        if ($milestone->payment_status === Pitch::PAYMENT_STATUS_PAID) {
            Toaster::error('Cannot delete a paid milestone');

            return;
        }

        // Prevent deleting the last milestone when budget is set
        $milestoneCount = $this->pitch->milestones()->count();
        $budget = $this->getBaseClientBudget();

        if ($milestoneCount === 1 && $budget > 0) {
            Toaster::error('Cannot delete the only milestone when budget is set. Change budget to $0 first.');

            return;
        }

        $milestone->delete();

        // Reset form state if we were editing this milestone
        if ($this->editingMilestoneId === $milestoneId) {
            $this->cancelMilestoneForm();
        }

        Toaster::success('Milestone deleted');
        $this->dispatch('milestonesUpdated');
        $this->pitch->refresh();
    }

    private function resetMilestoneForm(): void
    {
        $this->editingMilestoneId = null;
        $this->milestoneName = '';
        $this->milestoneDescription = null;
        $this->milestoneAmount = null;
        $this->milestoneSortOrder = null;
    }

    public function reorderMilestones(array $orderedIds): void
    {
        $this->authorize('update', $this->project);
        foreach ($orderedIds as $index => $id) {
            $milestone = $this->pitch->milestones()->find($id);
            if ($milestone) {
                $milestone->update(['sort_order' => $index + 1]);
            }
        }
        $this->pitch->refresh();
        Toaster::success('Milestones reordered');
    }

    // ----- Split Budget Helper -----
    public function openSplitBudgetModal(): void
    {
        $this->authorize('update', $this->project);

        // Reset form state
        $this->splitTemplate = 'equal';
        $this->splitCount = 2;
        $this->percentageSplit = [];

        $this->showSplitBudgetModal = true;
    }

    public function closeSplitBudgetModal(): void
    {
        $this->showSplitBudgetModal = false;
    }

    public function splitBudgetIntoMilestones(): void
    {
        $this->authorize('update', $this->project);

        $budget = $this->getBaseClientBudget();
        if ($budget <= 0) {
            Toaster::error('Project budget not set or zero.');

            return;
        }

        try {
            DB::transaction(function () use ($budget) {
                if ($this->splitTemplate === 'equal') {
                    $this->createEqualMilestones($budget);
                } elseif ($this->splitTemplate === 'percentage') {
                    $this->createPercentageMilestones($budget);
                } elseif ($this->splitTemplate === 'deposit_structure') {
                    $this->createDepositStructureMilestones($budget);
                }
            });

            $this->pitch->refresh();
            $this->dispatch('milestonesUpdated');
            Toaster::success('Milestones created successfully');
            $this->closeSplitBudgetModal();
        } catch (\Exception $e) {
            Log::error('Failed to create milestones', [
                'pitch_id' => $this->pitch->id,
                'template' => $this->splitTemplate,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to create milestones: '.$e->getMessage());
        }
    }

    private function createEqualMilestones(float $budget): void
    {
        $this->validate([
            'splitCount' => 'required|integer|min:2|max:20',
        ]);

        // Calculate equal parts, last milestone gets the remainder cents
        $cents = (int) round($budget * 100);
        $base = intdiv($cents, $this->splitCount);
        $remainder = $cents % $this->splitCount;

        $maxSortOrder = $this->pitch->milestones()->max('sort_order') ?? 0;

        for ($i = 1; $i <= $this->splitCount; $i++) {
            $amountCents = $base + ($i === $this->splitCount ? $remainder : 0);
            $this->pitch->milestones()->create([
                'name' => 'Milestone '.$i,
                'description' => null,
                'amount' => $amountCents / 100,
                'sort_order' => $maxSortOrder + $i,
                'status' => 'pending',
                'payment_status' => null,
            ]);
        }
    }

    private function createPercentageMilestones(float $budget): void
    {
        $this->validate([
            'percentageSplit' => 'required|array|min:2',
            'percentageSplit.*' => 'required|numeric|min:0|max:100',
        ]);

        // Validate that percentages sum to 100
        $totalPercentage = array_sum($this->percentageSplit);
        if (abs($totalPercentage - 100) > 0.01) {
            throw new \Exception('Percentages must sum to 100%. Current total: '.number_format($totalPercentage, 2).'%');
        }

        $maxSortOrder = $this->pitch->milestones()->max('sort_order') ?? 0;
        $cents = (int) round($budget * 100);
        $allocatedCents = 0;

        foreach ($this->percentageSplit as $index => $percentage) {
            $isLast = $index === count($this->percentageSplit) - 1;

            if ($isLast) {
                // Last milestone gets the remaining cents to avoid rounding errors
                $amountCents = $cents - $allocatedCents;
            } else {
                $amountCents = (int) round($cents * ($percentage / 100));
                $allocatedCents += $amountCents;
            }

            $this->pitch->milestones()->create([
                'name' => 'Milestone '.($index + 1),
                'description' => number_format($percentage, 1).'% of total budget',
                'amount' => $amountCents / 100,
                'sort_order' => $maxSortOrder + $index + 1,
                'status' => 'pending',
                'payment_status' => null,
            ]);
        }
    }

    private function createDepositStructureMilestones(float $budget): void
    {
        // Common deposit structure: 30% deposit, 40% progress, 30% final
        $structure = [
            ['name' => 'Initial Deposit', 'percentage' => 30],
            ['name' => 'Progress Payment', 'percentage' => 40],
            ['name' => 'Final Payment', 'percentage' => 30],
        ];

        $maxSortOrder = $this->pitch->milestones()->max('sort_order') ?? 0;
        $cents = (int) round($budget * 100);
        $allocatedCents = 0;

        foreach ($structure as $index => $milestone) {
            $isLast = $index === count($structure) - 1;

            if ($isLast) {
                $amountCents = $cents - $allocatedCents;
            } else {
                $amountCents = (int) round($cents * ($milestone['percentage'] / 100));
                $allocatedCents += $amountCents;
            }

            $this->pitch->milestones()->create([
                'name' => $milestone['name'],
                'description' => $milestone['percentage'].'% of total budget',
                'amount' => $amountCents / 100,
                'sort_order' => $maxSortOrder + $index + 1,
                'status' => 'pending',
                'payment_status' => null,
            ]);
        }
    }

    public function addPercentageInput(): void
    {
        $this->percentageSplit[] = 0;
    }

    public function removePercentageInput(int $index): void
    {
        unset($this->percentageSplit[$index]);
        $this->percentageSplit = array_values($this->percentageSplit); // Re-index array
    }

    public function render()
    {
        return view('livewire.project.component.milestone-manager');
    }
}

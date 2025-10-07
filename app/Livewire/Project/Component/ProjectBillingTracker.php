<?php

namespace App\Livewire\Project\Component;

use App\Models\Pitch;
use App\Models\Project;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class ProjectBillingTracker extends Component
{
    // Injected from parent
    public Pitch $pitch;

    public Project $project;

    public array $workflowColors;

    // Manual payment modal state
    public bool $showManualPaymentModal = false;

    public ?int $selectedMilestoneId = null;

    public ?string $manualPaymentNote = null;

    public bool $confirmManualPayment = false;

    // Invoice viewing state
    public ?string $viewingInvoiceId = null;

    public ?object $invoiceDetails = null;

    public bool $showInvoiceModal = false;

    // Note expansion state
    public array $expandedNotes = [];

    protected $rules = [
        'manualPaymentNote' => 'nullable|string|max:1000',
        'confirmManualPayment' => 'required|accepted',
    ];

    protected $listeners = [
        'milestonesUpdated' => '$refresh',
    ];

    public function mount(Pitch $pitch, Project $project, array $workflowColors): void
    {
        $this->pitch = $pitch;
        $this->project = $project;
        $this->workflowColors = $workflowColors;
    }

    // ========== COMPUTED PROPERTIES ==========

    /**
     * Get all milestones sorted by sort_order
     */
    public function getMilestonesProperty()
    {
        return $this->pitch->milestones()->orderBy('sort_order')->get();
    }

    /**
     * Get payment summary statistics
     */
    public function getPaymentSummaryProperty(): array
    {
        $milestones = $this->milestones;
        $totalBudget = (float) ($this->pitch->payment_amount ?? $this->project->budget ?? 0);
        $totalMilestones = $milestones->count();

        // Calculate paid amounts
        $paidMilestones = $milestones->where('payment_status', Pitch::PAYMENT_STATUS_PAID);
        $paidCount = $paidMilestones->count();
        $paidAmount = $paidMilestones->sum('amount');

        // Calculate pending/processing amounts
        $pendingMilestones = $milestones->whereIn('payment_status', [null, Pitch::PAYMENT_STATUS_PENDING]);
        $pendingAmount = $pendingMilestones->sum('amount');

        $processingMilestones = $milestones->where('payment_status', Pitch::PAYMENT_STATUS_PROCESSING);
        $processingAmount = $processingMilestones->sum('amount');

        // Outstanding = pending + processing
        $outstandingAmount = $pendingAmount + $processingAmount;

        // Calculate percentages
        $paidPercentage = $totalBudget > 0 ? ($paidAmount / $totalBudget * 100) : 0;
        $completionPercentage = $totalMilestones > 0 ? ($paidCount / $totalMilestones * 100) : 0;

        return [
            'total_budget' => $totalBudget,
            'total_milestones' => $totalMilestones,
            'paid_count' => $paidCount,
            'paid_amount' => $paidAmount,
            'pending_amount' => $pendingAmount,
            'processing_amount' => $processingAmount,
            'outstanding_amount' => $outstandingAmount,
            'paid_percentage' => min($paidPercentage, 100),
            'completion_percentage' => $completionPercentage,
            'all_paid' => $paidCount === $totalMilestones && $totalMilestones > 0,
        ];
    }

    /**
     * Get payment timeline from pitch events
     */
    public function getPaymentTimelineProperty()
    {
        return $this->pitch->events()
            ->whereIn('event_type', [
                'milestone_paid',
                'milestone_manually_marked_paid',
                'budget_updated',
                'revision_policy_updated',
            ])
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get transactions related to this pitch
     */
    public function getTransactionsProperty()
    {
        return $this->pitch->transactions()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    // ========== MANUAL PAYMENT ACTIONS ==========

    public function openManualPaymentModal(int $milestoneId): void
    {
        $this->authorize('update', $this->project);

        $milestone = $this->pitch->milestones()->findOrFail($milestoneId);

        // Prevent marking already paid milestones
        if ($milestone->payment_status === Pitch::PAYMENT_STATUS_PAID) {
            Toaster::error('This milestone is already marked as paid.');

            return;
        }

        $this->selectedMilestoneId = $milestoneId;
        $this->manualPaymentNote = null;
        $this->confirmManualPayment = false;
        $this->showManualPaymentModal = true;
    }

    public function closeManualPaymentModal(): void
    {
        $this->showManualPaymentModal = false;
        $this->selectedMilestoneId = null;
        $this->manualPaymentNote = null;
        $this->confirmManualPayment = false;
        $this->resetErrorBag();
    }

    public function markMilestoneAsPaidManually(): void
    {
        $this->authorize('update', $this->project);

        $this->validate([
            'manualPaymentNote' => 'nullable|string|max:1000',
            'confirmManualPayment' => 'required|accepted',
        ]);

        $milestone = $this->pitch->milestones()->findOrFail($this->selectedMilestoneId);

        // Double-check payment status
        if ($milestone->payment_status === Pitch::PAYMENT_STATUS_PAID) {
            Toaster::error('This milestone is already marked as paid.');
            $this->closeManualPaymentModal();

            return;
        }

        try {
            DB::transaction(function () use ($milestone) {
                // Generate manual payment ID
                $manualPaymentId = 'MANUAL_'.time().'_'.uniqid();

                // Update milestone payment status
                $milestone->update([
                    'payment_status' => Pitch::PAYMENT_STATUS_PAID,
                    'payment_completed_at' => now(),
                    'stripe_invoice_id' => $manualPaymentId,
                ]);

                // Schedule payout for the producer
                $payoutService = app(\App\Services\PayoutProcessingService::class);
                $payoutSchedule = $payoutService->schedulePayoutForMilestone($milestone, $manualPaymentId, true);

                // Create audit event
                $this->pitch->events()->create([
                    'event_type' => 'milestone_manually_marked_paid',
                    'comment' => sprintf(
                        'Milestone "%s" manually marked as paid. Note: %s',
                        $milestone->name,
                        $this->manualPaymentNote ?? 'No note provided'
                    ),
                    'status' => $this->pitch->status,
                    'created_by' => auth()->id(),
                    'metadata' => [
                        'milestone_id' => $milestone->id,
                        'milestone_name' => $milestone->name,
                        'amount' => number_format((float) $milestone->amount, 0, '.', ''),
                        'manual_payment' => true,
                        'note' => $this->manualPaymentNote,
                        'marked_by_user_id' => auth()->id(),
                        'marked_at' => now()->toIso8601String(),
                        'payout_schedule_id' => $payoutSchedule->id,
                    ],
                ]);

                Log::info('Payout scheduled for manual milestone payment', [
                    'milestone_id' => $milestone->id,
                    'payout_schedule_id' => $payoutSchedule->id,
                    'net_amount' => $payoutSchedule->net_amount,
                ]);
            });

            $this->pitch->refresh();
            $this->closeManualPaymentModal();

            Toaster::success('Milestone successfully marked as paid');
            $this->dispatch('milestonesUpdated');
        } catch (\Exception $e) {
            Log::error('Failed to manually mark milestone as paid', [
                'milestone_id' => $this->selectedMilestoneId,
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to mark milestone as paid. Please try again.');
        }
    }

    // ========== NOTE EXPANSION ==========

    public function toggleNoteExpansion(int $milestoneId): void
    {
        if (isset($this->expandedNotes[$milestoneId])) {
            unset($this->expandedNotes[$milestoneId]);
        } else {
            $this->expandedNotes[$milestoneId] = true;
        }
    }

    public function getManualPaymentNote(\App\Models\PitchMilestone $milestone): ?string
    {
        // Only fetch note for manual payments
        if (! str_starts_with($milestone->stripe_invoice_id ?? '', 'MANUAL_')) {
            return null;
        }

        // Find the event where this milestone was manually marked as paid
        $event = $this->pitch->events()
            ->where('event_type', 'milestone_manually_marked_paid')
            ->where(function ($query) use ($milestone) {
                $query->whereJsonContains('metadata->milestone_id', $milestone->id)
                    ->orWhereJsonContains('metadata->milestone_id', (string) $milestone->id);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        return $event?->metadata['note'] ?? null;
    }

    // ========== INVOICE VIEWING ==========

    public function viewInvoiceDetails(string $invoiceId): void
    {
        $this->authorize('update', $this->project);

        try {
            // Check if this is a manual payment invoice ID
            if (str_starts_with($invoiceId, 'MANUAL_')) {
                Toaster::info('This milestone was manually marked as paid. No Stripe invoice available.');

                return;
            }

            $invoiceService = app(InvoiceService::class);
            $invoice = $invoiceService->getInvoice($invoiceId);

            if ($invoice) {
                $this->invoiceDetails = $invoice;
                $this->viewingInvoiceId = $invoiceId;
                $this->showInvoiceModal = true;
            } else {
                Toaster::error('Invoice details could not be retrieved.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch invoice details', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to load invoice details.');
        }
    }

    public function closeInvoiceModal(): void
    {
        $this->showInvoiceModal = false;
        $this->invoiceDetails = null;
        $this->viewingInvoiceId = null;
    }

    public function render()
    {
        return view('livewire.project.component.project-billing-tracker');
    }
}

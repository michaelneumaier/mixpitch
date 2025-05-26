# Implementation Plan: Advanced Project Types

## Introduction

This document provides a detailed, step-by-step guide for implementing the advanced project types (Standard, Contest, Direct Hire, Client Management, Service Packages) outlined in `ADVANCED_PROJECT_TYPES_PROGRESS.md`. The goal is to integrate these types into the existing MixPitch application cohesively, minimizing future refactoring and ensuring features work together seamlessly.

The implementation is broken down into phases, starting with foundational changes and then addressing each project type individually.

**Cross-Cutting Considerations (Read Before Starting):**

*   **Payment & Invoicing:** This plan outlines *where* payment integration is needed (Contests, Client Management, Service Packages). However, the *how* requires careful design within `InvoiceService` and the chosen Payment Gateway integration. Consider:
    *   Adapting `InvoiceService` to handle different contexts (Pitches, Orders, Contest Prizes).
    *   Consistent and robust payment gateway webhook handling (idempotency, errors, retries).
    *   Clear definition of refund processes (especially for Service Packages).
*   **State Management:** `Pitch` and `Order` models will have complex lifecycles. Consider evaluating state machine libraries (e.g., `spatie/laravel-model-states`) early on. This can make status transitions more explicit, validated, and easier to manage than complex conditional logic within services.
*   **Error Handling & Recovery:** Beyond logging critical errors (observer failures, payment issues), define recovery paths. Will failures require manual admin intervention? Can operations be retried safely? How are users informed of processing failures?
*   **Currency:** If supporting multiple currencies beyond storing the code, define the strategy for handling exchange rates, display formatting, and payment processing.

## Phase 1: Foundational Changes

These changes establish the core infrastructure needed to support multiple project types.

**1. Database Schema Modifications:**

*   **Create Migration:** Generate a new migration (e.g., `add_project_type_and_related_fields_to_projects`).
    ```bash
    php artisan make:migration add_project_type_and_related_fields_to_projects --table=projects
    ```
*   **Modify `projects` Table (`up` method):**
    *   Add `project_type` column: This will store the type of project. Using an ENUM is often preferred for clarity and constraint, but a string is also feasible. Define the allowed types clearly.
        ```php
        $table->string('workflow_type')->default('standard')->after('user_id'); // Or use ENUM if DB supports easily
        $table->index('workflow_type');
        ```
    *   Add `target_producer_id` for Direct Hire:
        ```php
        $table->foreignId('target_producer_id')->nullable()->constrained('users')->onDelete('set null')->after('workflow_type');
        ```
    *   Add `client_email` and `client_name` for Client Management:
        ```php
        $table->string('client_email')->nullable()->after('target_producer_id');
        $table->string('client_name')->nullable()->after('client_email');
        $table->index('client_email'); // Index for potential lookups
        ```
    *   **Add Contest Prize Fields:** Add fields to store prize details for Contest projects.
        ```php
        $table->decimal('prize_amount', 10, 2)->nullable()->after('client_name'); // Prize for the winner
        $table->string('prize_currency', 3)->nullable()->after('prize_amount');
        // Optional: Add fields for runner-up prizes if needed
        // Note: If supporting multiple tiered prizes (1st, 2nd, 3rd) with different amounts becomes necessary,
        // this schema might need revision (e.g., separate related table for prize tiers).
        ```
    *   **Add Contest Deadline Fields:** Add fields for managing contest timelines.
        ```php
        $table->timestamp('submission_deadline')->nullable()->after('prize_currency'); // Deadline for producers to submit entries
        $table->timestamp('judging_deadline')->nullable()->after('submission_deadline'); // Optional: Deadline for owner to select winner(s)
        ```
        *(Note: If adding the default directly works for existing rows in your Laravel/DB version, the separate update might be redundant, but explicit update is safer).*
*   **Modify `projects` Table (`down` method):** Add corresponding `dropColumn` statements.
    ```php
    $table->dropForeign(['target_producer_id']);
    // Add new columns to drop
    $table->dropColumn(['workflow_type', 'target_producer_id', 'client_email', 'client_name', 'prize_amount', 'prize_currency']);
    ```
*   **Modify `pitches` Table (Optional but Recommended):** Consider adding an index to `project_id` if not already present, as it will be queried frequently.
    ```bash
    php artisan make:migration add_index_to_project_id_on_pitches_table --table=pitches
    ```
    ```php
    // In up() method
    $table->index('project_id');
    // In down() method
    $table->dropIndex(['project_id']);
    ```
*   **Run Migration:**
    ```bash
    php artisan migrate
    ```

**2. Model Updates (`app/Models/Project.php`):**

*   **Add Constants for Types:** Define constants for easy reference and to avoid magic strings.
    ```php
    // Add within the Project class
    const WORKFLOW_TYPE_STANDARD = 'standard';
    const WORKFLOW_TYPE_CONTEST = 'contest';
    const WORKFLOW_TYPE_DIRECT_HIRE = 'direct_hire';
    const WORKFLOW_TYPE_CLIENT_MANAGEMENT = 'client_management';
    // Service Packages handled via separate models/tables

    // Default Currency (if needed globally)
    const DEFAULT_CURRENCY = 'USD';
    ```
*   **Update `$fillable`:** Add the new database columns.
    ```php
    protected $fillable = [
        // ... existing fillable fields ...
        'workflow_type',
        'target_producer_id',
        'client_email',
        'client_name',
        'prize_amount',
        'prize_currency',
        // ... other fields ...
    ];
    ```
*   **Add Relationships:** Define the relationship for Direct Hire.
    ```php
    public function targetProducer()
    {
        return $this->belongsTo(User::class, 'target_producer_id');
    }
    ```
*   **Add Helper Methods:** Create boolean helpers for easy type checking.
    ```php
    public function isStandard(): bool
    {
        return $this->workflow_type === self::WORKFLOW_TYPE_STANDARD;
    }

    public function isContest(): bool
    {
        return $this->workflow_type === self::WORKFLOW_TYPE_CONTEST;
    }

    public function isDirectHire(): bool
    {
        return $this->workflow_type === self::WORKFLOW_TYPE_DIRECT_HIRE;
    }

    public function isClientManagement(): bool
    {
        return $this->workflow_type === self::WORKFLOW_TYPE_CLIENT_MANAGEMENT;
    }

    // Add helper to get all types (useful for dropdowns)
    public static function getWorkflowTypes(): array
    {
        return [
            self::WORKFLOW_TYPE_STANDARD,
            self::WORKFLOW_TYPE_CONTEST,
            self::WORKFLOW_TYPE_DIRECT_HIRE,
            self::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
        ];
    }

    // Add helper to get human-readable type names
    public function getReadableWorkflowTypeAttribute(): string
    {
        switch ($this->workflow_type) {
            case self::WORKFLOW_TYPE_STANDARD: return 'Standard';
            case self::WORKFLOW_TYPE_CONTEST: return 'Contest';
            case self::WORKFLOW_TYPE_DIRECT_HIRE: return 'Direct Hire';
            case self::WORKFLOW_TYPE_CLIENT_MANAGEMENT: return 'Client Management';
            default: return 'Unknown';
        }
    }
    ```
*   **Update Casts (Optional):** If desired, cast `target_producer_id` to integer and `prize_amount` to decimal/float.
    ```php
    protected $casts = [
        // ... existing casts ...
        'target_producer_id' => 'integer',
        'prize_amount' => 'decimal:2', // Or 'float'
    ];
    ```

**3. Model Updates (`app/Models/Pitch.php`):**

*   **Add Constants for New Statuses:** Define constants for statuses needed by Contest, Direct Hire, and Client Management types.
    ```php
    // Add within the Pitch class

    // Contest Statuses
    const STATUS_CONTEST_ENTRY = 'contest_entry'; // Initial state for contest submissions
    const STATUS_CONTEST_WINNER = 'contest_winner';
    const STATUS_CONTEST_RUNNER_UP = 'contest_runner_up'; // Potentially multiple
    const STATUS_CONTEST_NOT_SELECTED = 'contest_not_selected';

    // Direct Hire Status (Optional - for explicit acceptance flow)
    const STATUS_AWAITING_ACCEPTANCE = 'awaiting_acceptance';

    // Client Management Status (Optional - for explicit client feedback loop)
    const STATUS_CLIENT_REVISIONS_REQUESTED = 'client_revisions_requested';

    // Add new statuses to getReadableStatusAttribute() and getStatusDescriptionAttribute() methods.
    // Add new statuses to the getStatuses() method if it exists.
    ```
*   **Add `rank` field (Contests - Optional):** If implementing ranking beyond just "winner".
    *   Add migration: `php artisan make:migration add_rank_to_pitches_table --table=pitches`
    *   Add column: `$table->integer('rank')->nullable()->after('status');`
    *   Add to `$fillable` in `Pitch.php`.

**4. Update Core Services (Initial Guarding):**

*   **`app/Services/PitchWorkflowService.php`:**
    *   Inject `Project` model type hints where appropriate if not already done.
    *   Start adding basic guards in methods that *only* apply to the standard flow. This prevents accidental misuse before specific type logic is added.
    *   **Example in `createPitch`:**
        ```php
        // ... beginning of method ...
        // Add check: Direct Hire and Client Management projects should not allow public pitch creation.
        if ($project->isDirectHire() || $project->isClientManagement()) {
            throw new PitchCreationException('Pitches cannot be publicly submitted for this project type.');
        }
        // Contests might use this initially, but with different status logic (handled later).
        // ... rest of method ...
        ```
    *   **Example in `approveInitialPitch`:**
        ```php
        // ... beginning of method ...
        // Add check: Only Standard projects require initial pitch approval.
        if (!$pitch->project->isStandard()) {
             throw new UnauthorizedActionException('Initial pitch approval is not applicable for this workflow type.');
        }
        // ... rest of method ...
        ```
    *   Review other methods (`submitPitchForReview`, `approveSubmittedPitch`, etc.) and add similar high-level guards if a method is *clearly* incompatible with *all* non-standard types. More specific logic will be added per-phase.

**5. Update Policies (Initial Guarding):**

*   **`app/Policies/PitchPolicy.php`:**
    *   Inject `Project` model type hints if needed.
    *   Add checks similar to the service layer to restrict actions based on `workflow_type` at the authorization level.
    *   **Example in `create` (if it exists, maps to `createPitch`):**
        ```php
        public function create(User $user, Project $project)
        {
            // Deny public creation for Direct Hire / Client Management
            if ($project->isDirectHire() || $project->isClientManagement()) {
                return false;
            }
            // Add existing logic (e.g., project open, user hasn't pitched)
            return $project->isOpenForPitches() && !$project->userPitch($user->id);
        }
        ```
    *   **Example in `approveInitial`:**
        ```php
        public function approveInitial(User $user, Pitch $pitch)
        {
            // Only project owner can approve, and only for Standard projects
            return $user->id === $pitch->project->user_id && $pitch->project->isStandard();
        }
        ```
    *   Review other policy methods (`update`, `submitForReview`, `approveSubmission`, `requestRevisions`, `complete`, etc.) and add `workflow_type` checks where appropriate.

**6. Project Creation UI:**

*   **`app/Livewire/CreateProject.php` & corresponding view:**
    *   Add a new public property: `public string $workflow_type = Project::WORKFLOW_TYPE_STANDARD;`
    *   Add a selection input (e.g., dropdown or radio buttons) to the form:
        ```html
        <!-- Example: resources/views/livewire/create-project.blade.php -->
        <div>
            <label for="workflow_type">Workflow Type</label>
            <select wire:model.live="workflow_type" id="workflow_type">
                <option value="{{ \App\Models\Project::WORKFLOW_TYPE_STANDARD }}">Standard</option>
                <option value="{{ \App\Models\Project::WORKFLOW_TYPE_CONTEST }}">Contest</option>
                <option value="{{ \App\Models\Project::WORKFLOW_TYPE_DIRECT_HIRE }}">Direct Hire</option>
                <option value="{{ \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT }}">Client Management</option>
            </select>
            @error('workflow_type') <span class="error">{{ $message }}</span> @enderror
        </div>

        <!-- Add conditional fields based on workflow_type later -->
        ```
    *   Add validation rule for `workflow_type` in the component's rules.
        ```php
        protected $rules = [
            // ... existing rules ...
            'workflow_type' => ['required', Rule::in(Project::getWorkflowTypes())],
            // ...
        ];
        ```
    *   Ensure `workflow_type` is saved when the project is created in the `save` or relevant method.

**7. Configuration Setup:**

*   **Create/Update Config File (e.g., `config/mixpitch.php`):** Add configuration values that might be needed across different project types or features.
    ```php
    // config/mixpitch.php
    return [
        'client_portal_link_expiry_days' => env('CLIENT_PORTAL_LINK_EXPIRY_DAYS', 7),
        // Add other config values as needed (e.g., default currency, feature flags)
    ];
    ```
*   **Environment Variables:** Add corresponding variables to your `.env` file (e.g., `CLIENT_PORTAL_LINK_EXPIRY_DAYS=7`).
*   **Usage:** Access these values using the `config()` helper (e.g., `config('mixpitch.client_portal_link_expiry_days')`). Define these early to avoid hardcoding values.

This phase lays the groundwork. The application should still function for "Standard" projects, but the necessary fields and basic checks are in place for introducing the other types.

## Phase 2: Standard Project Formalization

This phase ensures the existing ("standard") workflow is explicitly recognized and continues to function correctly after the Phase 1 changes. Most of the work here is verification.

**1. Verification:**

*   **Project Creation:** Verify that new projects created through the UI *without* explicitly selecting a type default to `workflow_type = 'standard'` in the database (due to the DB default or component default).
*   **Existing Workflows:** Test the end-to-end standard project workflow:
    *   Producer submits a pitch (`STATUS_PENDING`).
    *   Owner approves the initial pitch (`PENDING` -> `IN_PROGRESS`).
    *   Producer uploads files and submits for review (`IN_PROGRESS` -> `READY_FOR_REVIEW`, Snapshot created).
    *   Owner requests revisions (`READY_FOR_REVIEW` -> `REVISIONS_REQUESTED`).
    *   Producer resubmits (`REVISIONS_REQUESTED` -> `READY_FOR_REVIEW`, new Snapshot created).
    *   Owner approves submission (`READY_FOR_REVIEW` -> `APPROVED`).
    *   Owner completes the pitch (`APPROVED` -> `COMPLETED`, payment flow initiated, other pitches potentially closed - check `PitchCompletionService`).
    *   Verify all relevant notifications are sent at each stage.
*   **Policy Checks:** Ensure the policy guards added in Phase 1 correctly allow standard actions (e.g., `approveInitial` should return `true` for a standard project owner).
*   **Service Logic:** Confirm that the service methods (`approveInitialPitch`, `submitPitchForReview`, etc.) execute correctly for standard projects and that the guards added in Phase 1 do *not* incorrectly block standard workflow steps.

**2. Code Adjustments (If Necessary):**

*   **`app/Services/PitchWorkflowService.php`:** If any methods currently lack explicit checks for the standard workflow where they *should* only apply to standard (e.g., `approveInitialPitch`), ensure the check `if (!$pitch->project->isStandard())` exists.
*   **`app/Policies/PitchPolicy.php`:** Similarly, ensure methods governing standard-only actions (like `approveInitial`) explicitly check `&& $pitch->project->isStandard()` alongside user role checks.
*   **`app/Services/PitchCompletionService.php`:** This service likely handles the `completePitch` logic. Review it to ensure it correctly identifies the winning pitch and closes others *for standard projects*. It will need modification for Contests later.
    *   **Payout Trigger:** Explicitly define how completing a Standard pitch triggers the payout process. Does `completePitch` automatically update `payment_status` and interact with `InvoiceService` or a payout service? Is there an escrow mechanism? Clarify the sequence: Completion -> Invoice Finalization -> Payment Status Update -> Fund Release Trigger.
*   **UI Components (`app/Livewire/*`, `resources/views/*`):** At this stage, UI should function as before for standard projects. No conditional rendering specific to `isStandard()` is strictly needed yet, as other types aren't fully implemented. However, ensure no UI elements are *broken* by the Phase 1 changes.

This phase confirms the baseline functionality before layering on more complex types.

## Phase 3: Contest Implementation

This phase introduces the "Contest" project type, where multiple producers submit entries, and the owner selects winners without a revision cycle.

**1. Model Updates (`app/Models/Pitch.php`):**

*   **Implement Status Helpers (Recommended):** Add helpers for contest-specific statuses.
    ```php
    public function isContestEntry(): bool
    {
        return $this->status === self::STATUS_CONTEST_ENTRY;
    }

    public function isContestWinner(): bool
    {
        return $this->status === self::STATUS_CONTEST_WINNER;
    }
    // Add similar helpers for RUNNER_UP, NOT_SELECTED
    ```
*   **Update `getReadableStatusAttribute` / `getStatusDescriptionAttribute`:** Add cases for the new `STATUS_CONTEST_*` constants.

**2. Workflow Service (`app/Services/PitchWorkflowService.php`):**

*   **Modify `createPitch`:**
    *   Check if the project is a contest.
    *   **Deadline Check:** If contest, check if `project->submission_deadline` has passed. If so, throw a `PitchCreationException`. *Enforce this strictly.*
    *   If contest and open, set the initial status to `STATUS_CONTEST_ENTRY` instead of `STATUS_PENDING`.
    *   Update the initial event comment accordingly.
    *   Ensure the `notifyPitchSubmitted` notification makes sense for a contest entry or create a specific `notifyContestEntrySubmitted`.
    ```php
    // Inside createPitch method, before saving:
    if ($project->isContest()) {
        $pitch->status = Pitch::STATUS_CONTEST_ENTRY;
        $initialComment = 'Contest entry submitted.';
        // Consider using a different notification method here
        // $this->notificationService->notifyContestEntrySubmitted($pitch);
    } else {
        $pitch->status = Pitch::STATUS_PENDING;
        $initialComment = 'Pitch created and pending project owner approval.';
        // Existing notification
        $this->notificationService->notifyPitchSubmitted($pitch);
    }
    // ... set event comment using $initialComment ...
    ```
*   **Guard Standard Actions:** Add specific checks at the beginning of methods that are *disabled* for contests.
    ```php
    // Example in approveInitialPitch:
    // (Already guarded by !$project->isStandard() in Phase 1/2)

    // Example in submitPitchForReview:
    if ($pitch->project->isContest()) {
        throw new UnauthorizedActionException('Standard review submission is not applicable for contests.');
    }

    // Example in approveSubmittedPitch:
    if ($pitch->project->isContest()) {
        throw new UnauthorizedActionException('Standard pitch approval is not applicable for contests.');
    }

    // Example in requestPitchRevisions:
    if ($pitch->project->isContest()) {
        throw new UnauthorizedActionException('Revisions cannot be requested for contest entries.');
    }

    // Add similar guards to denySubmittedPitch, cancelPitchSubmission, etc.
    ```
*   **New Contest Methods:** Implement the core logic for managing contest winners and entries.
    ```php
    /**
     * Select a pitch as the contest winner.
     */
    public function selectContestWinner(Pitch $pitch, User $projectOwner): Pitch
    {
        $project = $pitch->project;
        // Authorization & Validation
        if ($projectOwner->id !== $project->user_id || !$project->isContest()) {
            throw new UnauthorizedActionException('select contest winner');
        }
        if ($pitch->status !== Pitch::STATUS_CONTEST_ENTRY) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_CONTEST_WINNER, 'Only contest entries can be selected as winners.');
        }
        // Ensure no other winner already selected for this project? (Optional Check)
        // if ($project->pitches()->where('status', Pitch::STATUS_CONTEST_WINNER)->exists()) {
        //     throw new \LogicException('A winner has already been selected for this contest.');
        // }

        return DB::transaction(function () use ($pitch, $projectOwner, $project) {
            $pitch->status = Pitch::STATUS_CONTEST_WINNER;
            $pitch->rank = 1; // Explicitly set rank 1 for the winner
            $pitch->approved_at = now(); // Use approved_at to signify selection time
            // Set payment details based on Project's prize settings
            $pitch->payment_amount = $project->prize_amount;
            $pitch->payment_status = Pitch::PAYMENT_STATUS_PENDING; // Assume payment processing starts now
            $pitch->save();

            // Create event
            $pitch->events()->create([
                'event_type' => 'contest_winner_selected',
                'comment' => 'Selected as contest winner.',
                'status' => $pitch->status,
                'created_by' => $projectOwner->id,
            ]);

            // Notify winner
            $this->notificationService->notifyContestWinnerSelected($pitch);

            // Close other entries for this project
            $this->closeOtherContestEntries($pitch);

            // Trigger payment process for the contest prize
            if ($project->prize_amount > 0) {
                try {
                    $invoiceService = app(InvoiceService::class);
                    // Note: InvoiceService needs adjustment to handle contest prize payouts
                    // It might need info about the Payer (Project Owner) and Payee (Winning Producer)
                    // The amount comes from the project settings.
                    $invoice = $invoiceService->createInvoiceForContestPrize(
                        $project,
                        $pitch->user, // Winner
                        $project->prize_amount,
                        $project->prize_currency ?? Project::DEFAULT_CURRENCY
                    );
                    $pitch->final_invoice_id = $invoice->id;
                    // Mark as paid immediately? Or wait for webhook? Depends on InvoiceService logic.
                    // Payout Trigger: Define when the actual fund transfer happens.
                    // Is it initiated here? Or does InvoiceService handle it based on invoice status?
                    // Clarify if payment_status should be PROCESSING or PAID after this step.
                    $pitch->payment_status = Pitch::PAYMENT_STATUS_PROCESSING; // Example: Mark as processing
                    $pitch->save();
                    // Potentially trigger invoice sending/payment processing here if not automatic
                    // $invoiceService->processInvoicePayment($invoice);
                } catch (\Exception $e) {
                    Log::error('Failed to create invoice for contest winner', [
                        'pitch_id' => $pitch->id,
                        'project_id' => $project->id,
                        'error' => $e->getMessage()
                    ]);
                    // How to handle invoice failure? Should it rollback the winner selection?
                    // Maybe just log and require manual intervention for payment.
                    // Or throw the exception to rollback the transaction.
                    // User Feedback: Ensure this critical failure is communicated (e.g., flash message to owner,
                    // potentially prevent UI update or show an error state) if the transaction doesn't rollback.
                    throw new \App\Exceptions\Payment\InvoiceCreationException('Failed to create prize invoice: ' . $e->getMessage());
                }
            } else {
                // No prize money, mark payment as not required
                $pitch->payment_status = Pitch::PAYMENT_STATUS_NOT_REQUIRED;
                $pitch->save();
            }

            return $pitch;
        });
    }

    /**
     * Select a pitch as a contest runner-up (optional).
     */
    public function selectContestRunnerUp(Pitch $pitch, User $projectOwner, int $rank): Pitch
    {
        if ($rank <= 1) {
             throw new \InvalidArgumentException('Runner-up rank must be greater than 1.');
        }
        // Authorization & Validation (similar to selectContestWinner)
        if ($projectOwner->id !== $pitch->project->user_id || !$pitch->project->isContest()) {
            throw new UnauthorizedActionException('select contest runner-up');
        }
         if ($pitch->status !== Pitch::STATUS_CONTEST_ENTRY) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_CONTEST_RUNNER_UP, 'Only contest entries can be selected as runner-ups.');
        }

        return DB::transaction(function () use ($pitch, $projectOwner, $rank) {
            $pitch->status = Pitch::STATUS_CONTEST_RUNNER_UP;
            $pitch->rank = $rank;
            $pitch->save();

            // Create event
            $pitch->events()->create([
                'event_type' => 'contest_runner_up_selected',
                'comment' => "Selected as contest runner-up (Rank: {$rank}).",
                'status' => $pitch->status,
                'created_by' => $projectOwner->id,
            ]);

            // Notify runner-up
            $this->notificationService->notifyContestRunnerUpSelected($pitch);

            return $pitch;
        });
    }

    /**
     * Update status of non-winning/non-runner-up entries.
     */
    protected function closeOtherContestEntries(Pitch $winningPitch): void
    {
        $projectPitches = Pitch::where('project_id', $winningPitch->project_id)
                                ->where('id', '!=', $winningPitch->id)
                                ->where('status', Pitch::STATUS_CONTEST_ENTRY) // Only close those still pending selection
                                ->get();

        foreach ($projectPitches as $pitch) {
            // Avoid double-closing if runner-ups were selected before winner
            if (!in_array($pitch->status, [Pitch::STATUS_CONTEST_RUNNER_UP, Pitch::STATUS_CONTEST_NOT_SELECTED])) {
                $pitch->status = Pitch::STATUS_CONTEST_NOT_SELECTED;
                $pitch->closed_at = now(); // Use closed_at or a different field?
                $pitch->saveQuietly(); // Avoid triggering individual update events if not desired

                // Optionally create event for each closed pitch
                $pitch->events()->create([
                    'event_type' => 'contest_entry_not_selected',
                    'comment' => 'Entry was not selected.',
                    'status' => $pitch->status,
                    'created_by' => $winningPitch->project->user_id, // System/Owner action
                ]);

                // Notify producer
                $this->notificationService->notifyContestEntryNotSelected($pitch);
            }
        }
    }
    ```
*   **Review `PitchCompletionService`:** Ensure the standard completion logic (which might close other pitches) is *not* triggered for contests. The `closeOtherContestEntries` handles this specifically. *Verify standard completion doesn't interfere with `contest_winner` status or payment.*

**3. Authorization (`app/Policies/PitchPolicy.php`):**

*   **Modify Standard Actions:** Ensure policy methods for standard actions return `false` for contest pitches.
    ```php
    // Example: submitForReview
    public function submitForReview(User $user, Pitch $pitch)
    {
        if ($pitch->project->isContest()) {
            return false;
        }
        // Existing standard logic: return $user->id === $pitch->user_id && ($pitch->status === Pitch::STATUS_IN_PROGRESS || ...);
    }
    // Apply similar logic to: approveInitial, approveSubmission, requestRevisions, denySubmission, completePitch (or remove check if completion has different meaning)
    ```
*   **Add New Contest Policies:** Create policies for the new actions.
    ```php
    public function selectWinner(User $user, Pitch $pitch)
    {
        // Only project owner can select winner for their contest pitch
        return $user->id === $pitch->project->user_id && $pitch->project->isContest();
    }

    public function selectRunnerUp(User $user, Pitch $pitch)
    {
        // Only project owner can select runner-up for their contest pitch
        return $user->id === $pitch->project->user_id && $pitch->project->isContest();
    }
    ```

**4. Controller / Route Adjustments:**

*   Review `PitchController`, `PitchSnapshotController`, etc. Ensure actions disabled by policy/service guards don't cause errors or are appropriately handled (e.g., redirect back with error).
*   Snapshot creation (`submitPitchForReview`) is disabled for contests, so `PitchSnapshotController` actions should effectively be blocked for contest pitches via policy.
*   New routes/controller actions might be needed if the selection logic resides in a controller instead of just Livewire actions.

**5. Frontend/UI (Livewire & Views):**

*   **Project Creation (`CreateProject`):** Already handled in Phase 1. *Needs update: Add fields for setting optional `submission_deadline`, `judging_deadline`, and `prize_amount` when `workflow_type` is `contest`.*
*   **Project Management (`ManageProject`):**
    *   Use `@if($project->isContest())` to show contest-specific views/sections.
    *   Display contest deadlines (`submission_deadline`, `judging_deadline`).
    *   Display prize amount.
    *   Display list of `STATUS_CONTEST_ENTRY` pitches.
    *   **Submission Logic:** Prevent owners from selecting winners before the `submission_deadline` (if set). Consider UI state during judging period.
    *   Hide standard action buttons (Approve Initial, etc.).
*   **Pitch View (`ManagePitch` Livewire Component - assumed name):**
    *   Use `@if($pitch->project->isContest())`.
    *   Simplify the view for contest entrants: show entry status (`Contest Entry`, `Winner`, `Runner-Up`, `Not Selected`), submitted files.
    *   Hide standard workflow elements (Submit for Review, Revision History, Snapshots).
    *   Show rank if applicable.
*   **Pitch Files (`PitchFiles` Livewire Component - assumed name):**
    *   Ensure file uploads work for `STATUS_CONTEST_ENTRY` pitches.
    *   Consider if file management needs disabling after winner selection.

**6. Notifications (`app/Services/NotificationService.php`):**

*   Implement the new notification methods called from `PitchWorkflowService`:
    *   `notifyContestEntrySubmitted` (Optional alternative to `notifyPitchSubmitted`)
    *   `notifyContestWinnerSelected`
    *   `notifyContestRunnerUpSelected`
    *   `notifyContestEntryNotSelected`
*   Ensure notifications target the correct users (producer for entry status, owner potentially for new entries).

**7. Testing:**

*   Write unit tests for the new `PitchWorkflowService` methods (`selectContestWinner`, `selectContestRunnerUp`, `closeOtherContestEntries`).
*   Write tests for the modified service methods (`createPitch`) ensuring correct status setting and deadline enforcement.
*   Test the new `PitchPolicy` methods.
*   Write feature tests covering the full contest lifecycle: creation with deadlines/prizes, multiple entries before deadline, attempts to submit after deadline, owner selecting winner (respecting deadlines), other entries closing, notifications, UI displays correctly for owner and entrants.
*   **Consider Contest Disputes:** Might disputes arise (e.g., winner claims non-payment, entrant disputes winner selection)? If so, how would these be handled? Link to the general Dispute Resolution definition (Phase 6/7).

## Phase 4: Direct Hire Implementation

This phase allows project owners to select a specific producer during project creation, bypassing the public pitch submission and initial approval steps.

**1. Database & Model Updates:**

*   Database column `target_producer_id` added in Phase 1.
*   Model relationship `targetProducer()` added in Phase 1.
*   Model helper `isDirectHire()` added in Phase 1.
*   Model constant `STATUS_AWAITING_ACCEPTANCE` added in Phase 1 (if choosing explicit acceptance flow).

**2. Project Creation & Pitch Initiation:**

*   **`app/Livewire/CreateProject.php` & View:**
    *   Conditionally show a producer search input when `workflow_type` is `direct_hire`.
        ```html
        <!-- Example: resources/views/livewire/create-project.blade.php -->
        <div x-data="{ workflowType: @entangle('workflow_type') }">
            <!-- ... workflow type select ... -->

            <div x-show="workflowType === '{{ \App\Models\Project::WORKFLOW_TYPE_DIRECT_HIRE }}'">
                <label for="target_producer_id">Target Producer</label>
                <!-- Implement producer search/selection component -->
                <input type="text" wire:model="target_producer_query" placeholder="Search producers...">
                <select wire:model="target_producer_id" id="target_producer_id">
                    <option value="">Select Producer</option>
                    {{-- Populate with search results --}}
                    @foreach($producers as $producer)
                        <option value="{{ $producer->id }}">{{ $producer->name }}</option>
                    @endforeach
                </select>
                @error('target_producer_id') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>
        ```
    *   Add `public $target_producer_id = null;` and potentially properties for search (`public $target_producer_query = ''; public $producers = [];`).
    *   Implement search logic (e.g., in `updatedTargetProducerQuery` method) to populate `$producers`.
    *   Add validation rule for `target_producer_id`: `'required_if:workflow_type,direct_hire'`, `'exists:users,id'`.
    *   Ensure `target_producer_id` is saved with the project.
*   **Automatic Pitch Creation (using an Observer is recommended):**
    *   Create an observer: `php artisan make:observer ProjectObserver --model=Project`
    *   Register the observer in `app/Providers/EventServiceProvider.php`.
    *   Implement the `created` method in `app/Observers/ProjectObserver.php`:
        ```php
        namespace App\Observers;

        use App\Models\Project;
        use App\Models\Pitch;
        use App\Services\NotificationService;
        use Illuminate\Support\Facades\Log;

        class ProjectObserver
        {
            /**
             * Handle the Project "created" event.
             */
            public function created(Project $project): void
            {
                if ($project->isDirectHire() && $project->target_producer_id) {
                    // **Decision Point:** Choose the desired flow here.
                    // It is STRONGLY recommended to decide upfront whether to use
                    // explicit acceptance (STATUS_AWAITING_ACCEPTANCE) or implicit (STATUS_IN_PROGRESS).
                    // The implicit flow is simpler but gives the producer less agency.
                    $initialStatus = Pitch::STATUS_IN_PROGRESS; // Implicit (Default)
                    // $initialStatus = Pitch::STATUS_AWAITING_ACCEPTANCE; // Explicit

                    Log::info('Creating automatic pitch for Direct Hire project', ['project_id' => $project->id, 'target_producer_id' => $project->target_producer_id, 'initial_status' => $initialStatus]);
                    try {
                        $pitch = Pitch::create([
                            'project_id' => $project->id,
                            'user_id' => $project->target_producer_id, // Assign to the target producer
                            'title' => $project->title . ' - Direct Offer', // Or derive differently
                            'description' => $project->description, // Or derive differently
                            'status' => $initialStatus,
                            'terms_agreed' => true, // Assume terms agreed implicitly by owner creating direct hire?
                        ]);

                        // Create initial event
                        $pitch->events()->create([
                            'event_type' => 'direct_hire_initiated',
                            'comment' => 'Direct hire project initiated for producer.',
                            'status' => $pitch->status,
                            'created_by' => $project->user_id, // Project owner initiated
                        ]);

                        // Notify the target producer based on the chosen flow
                        $notificationService = app(NotificationService::class);
                        if ($initialStatus === Pitch::STATUS_AWAITING_ACCEPTANCE) {
                            $notificationService->notifyDirectHireOffer($pitch); // Requires implementation
                        } else {
                            $notificationService->notifyDirectHireAssignment($pitch); // Requires implementation
                        }

                    } catch (\Exception $e) {
                        Log::error('Failed to create automatic pitch for Direct Hire project', [
                            'project_id' => $project->id,
                            'target_producer_id' => $project->target_producer_id,
                            'error' => $e->getMessage()
                        ]);
                        // Consider how to handle this failure - notify admin?
                    }
                }
            }
            // ... other observer methods ...
        }
        ```
    *   *(Self-Correction): Ensure the public `createPitch` service method and policy block this type (already done in Phase 1/3).*

**3. Producer Acceptance/Rejection (Implement only if using `STATUS_AWAITING_ACCEPTANCE` flow):**

*   **Note:** This entire section is only necessary if the **Explicit Acceptance Flow** (`STATUS_AWAITING_ACCEPTANCE`) was chosen in the ProjectObserver.
*   **UI (Producer Dashboard / Pitches List):**
    *   Display pitches with status `STATUS_AWAITING_ACCEPTANCE` differently (e.g., in a separate section or with clear indicators).
    *   Show "Accept Offer" / "Reject Offer" buttons.
*   **Backend Actions (`app/Services/PitchWorkflowService.php`):**
    *   Implement `acceptDirectHire` method:
        ```php
        public function acceptDirectHire(Pitch $pitch, User $producer): Pitch
        {
            // Authorization & Validation
            if ($producer->id !== $pitch->user_id || !$pitch->project->isDirectHire()) {
                throw new UnauthorizedActionException('accept direct hire offer');
            }
            if ($pitch->status !== Pitch::STATUS_AWAITING_ACCEPTANCE) {
                throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_IN_PROGRESS, 'Offer must be awaiting acceptance.');
            }

            return DB::transaction(function () use ($pitch, $producer) {
                $pitch->status = Pitch::STATUS_IN_PROGRESS;
                // Maybe set terms_agreed = true here if not done at creation
                $pitch->save();

                // Create event
                $pitch->events()->create([
                    'event_type' => 'direct_hire_accepted',
                    'comment' => 'Producer accepted the direct hire offer.',
                    'status' => $pitch->status,
                    'created_by' => $producer->id,
                ]);

                // Notify project owner
                // $this->notificationService->notifyDirectHireAccepted($pitch); // Requires new method

                return $pitch;
            });
        }
        ```
    *   Implement `rejectDirectHire` method:
        ```php
        public function rejectDirectHire(Pitch $pitch, User $producer): Pitch
        {
            // Authorization & Validation (similar to accept)
            if ($producer->id !== $pitch->user_id || !$pitch->project->isDirectHire()) {
                throw new UnauthorizedActionException('reject direct hire offer');
            }
             if ($pitch->status !== Pitch::STATUS_AWAITING_ACCEPTANCE) {
                throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_DENIED, 'Offer must be awaiting acceptance.');
            }

            return DB::transaction(function () use ($pitch, $producer) {
                // Decide on final status: DENIED or CLOSED?
                $pitch->status = Pitch::STATUS_DENIED; // Or Pitch::STATUS_CLOSED
                $pitch->denied_at = now(); // Or closed_at
                $pitch->save();

                // Create event
                $pitch->events()->create([
                    'event_type' => 'direct_hire_rejected',
                    'comment' => 'Producer rejected the direct hire offer.',
                    'status' => $pitch->status,
                    'created_by' => $producer->id,
                ]);

                // Notify project owner
                // $this->notificationService->notifyDirectHireRejected($pitch); // Requires new method

                // **Decision:** What happens to the Project? Mark as failed? Allow owner to re-assign (complex)?
                // Simple approach: Project remains, but pitch is denied/closed.
                // **Recommendation:** Define this behavior clearly. The simplest approach is often best initially:
                // Mark the pitch as denied/closed. The project owner must then create a *new* Direct Hire project
                // if they wish to invite someone else. Avoid logic for re-assigning within the same project initially.

                return $pitch;
            });
        }
        ```
*   **Livewire / Controller Actions:** Add component methods (e.g., in `ManagePitch` or a dashboard component) that call these service methods after authorization.
*   **Policy (`app/Policies/PitchPolicy.php`):** Add `acceptDirectHire` and `rejectDirectHire` policy methods checking the user is the assigned producer and status is correct.

**4. Leveraging Standard Workflow Components:**

*   **Service Guarding (`app/Services/PitchWorkflowService.php`):**
    *   Ensure `createPitch` and `approveInitialPitch` are blocked for this type (already done).
    *   Standard methods used *after* initiation (`submitPitchForReview`, `approveSubmittedPitch`, `requestPitchRevisions`, `denySubmittedPitch`, completion service) should *already* work correctly, provided the *policies* allow the actions.
    *   **Completion & Payout:** Verify that the standard completion process (likely via `PitchCompletionService`, reviewed in Phase 2) correctly handles Direct Hire projects upon the owner marking the pitch as complete. The payout trigger defined for Standard projects should apply here as well.
*   **Policy Adjustments (`app/Policies/PitchPolicy.php`):** This is crucial.
    *   Update policies for actions that occur *after* the pitch is `IN_PROGRESS`.
    *   These actions should be allowed if the user is EITHER the project owner OR the assigned target producer.
    *   **Example: `submitForReview`**
        ```php
        public function submitForReview(User $user, Pitch $pitch)
        {
            if ($pitch->project->isContest()) { return false; }

            // Allow if user is the assigned producer (which is pitch->user_id for direct hire)
            // and status is correct.
            $isTargetProducer = $user->id === $pitch->user_id;
            $isCorrectStatus = in_array($pitch->status, [Pitch::STATUS_IN_PROGRESS, Pitch::STATUS_REVISIONS_REQUESTED]);

            return $isTargetProducer && $isCorrectStatus;
        }
        ```
    *   **Example: `approveSubmission` / `requestRevisions` / `denySubmission`**
        ```php
        public function approveSubmission(User $user, Pitch $pitch)
        {
            // Only project owner can approve any submission type
            $isOwner = $user->id === $pitch->project->user_id;
            $isCorrectStatus = $pitch->status === Pitch::STATUS_READY_FOR_REVIEW;

            // No specific type check needed here if owner is always the approver
            return $isOwner && $isCorrectStatus;
        }
        // Apply similar logic (owner-centric) for requestRevisions, denySubmission.
        ```
    *   **Example: `completePitch` (or similar in `ProjectPolicy` if project-level)**
        ```php
        // In PitchPolicy or ProjectPolicy depending on where completion is authorized
        public function complete(User $user, Pitch $pitch) // Or complete(User $user, Project $project)
        {
            // Only project owner can mark as complete
            $isOwner = $user->id === $pitch->project->user_id;
            $isCorrectStatus = $pitch->status === Pitch::STATUS_APPROVED;

            return $isOwner && $isCorrectStatus;
        }
        ```
    *   **Review all relevant PitchPolicy methods:** (`update`, `delete`, `view`, `uploadFile`, `deleteFile`, etc.) Ensure they correctly account for the project owner OR the assigned producer (`$pitch->user_id`) as appropriate for Direct Hire projects.

**5. Notifications (`app/Services/NotificationService.php`):**

*   Implement new notification methods:
    *   `notifyDirectHireOffer` (to producer, if explicit acceptance)
    *   `notifyDirectHireAssignment` (to producer, if implicit acceptance)
    *   `notifyDirectHireAccepted` (to owner, if explicit acceptance)
    *   `notifyDirectHireRejected` (to owner, if explicit acceptance)
*   Verify that standard notifications (review ready, revisions requested, approved, etc.) trigger correctly for the owner and the assigned producer once the pitch is `IN_PROGRESS`.

**6. Frontend/UI:**

*   **Project Creation:** Updated in Step 2.
*   **Producer Dashboard:** Add "Direct Hire Offers" section if using explicit acceptance.
*   **Owner Project Management (`ManageProject`):**
    *   Use `@if($project->isDirectHire())`.
    *   Show target producer information.
    *   Hide elements related to managing multiple applicants/pitches (e.g., applicant list, approve initial pitch buttons).
    *   Show standard pitch controls (Review, Request Revisions, Complete, etc.) targeting the single, automatically created pitch *once it is active* (`IN_PROGRESS` or beyond).
*   **Producer Pitch Management (`ManagePitch`):**
    *   Use `@if($pitch->project->isDirectHire())`.
    *   If explicit acceptance, show Accept/Reject buttons when status is `AWAITING_ACCEPTANCE`.
    *   Once `IN_PROGRESS`, show standard producer controls (Upload Files, Submit for Review) based on policy.
*   **Project Browsing:** Modify project listing queries/views (`ProjectsComponent`?) to hide Direct Hire projects from users who are neither the owner nor the assigned target producer.

**7. Testing:**

*   Unit tests for `acceptDirectHire`, `rejectDirectHire` (if implemented).
*   Unit tests for `ProjectObserver::created` logic.
*   Update tests for existing `PitchPolicy` methods to verify correct permissions for owner and target producer in Direct Hire context.
*   Feature tests covering: Direct Hire project creation, pitch auto-creation, notification to producer, acceptance/rejection flow (if applicable), standard review cycle by owner/producer, completion, access control (hiding projects from others).

## Phase 5: Client Management Implementation

This phase enables producers to create projects to manage work for external clients, using MixPitch for collaboration and payment via a secure client portal.

**1. Database & Model Updates:**

*   Database columns `client_email`, `client_name` added in Phase 1.
*   Model helper `isClientManagement()` added in Phase 1.
*   Model constant `STATUS_CLIENT_REVISIONS_REQUESTED` added in Phase 1 (if implementing explicit client feedback loop).

**2. Project Creation & Pitch Initiation:**

*   **`app/Livewire/CreateProject.php` & View:**
    *   Conditionally show `client_email` (required) and `client_name` (optional) inputs when `workflow_type` is `client_management`.
        ```html
        <!-- Example: resources/views/livewire/create-project.blade.php -->
        <div x-data="{ workflowType: @entangle('workflow_type') }">
            <!-- ... workflow type select ... -->

            <div x-show="workflowType === '{{ \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT }}'">
                <div>
                    <label for="client_email">Client Email</label>
                    <input type="email" wire:model="client_email" id="client_email" required>
                    @error('client_email') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="client_name">Client Name (Optional)</label>
                    <input type="text" wire:model="client_name" id="client_name">
                    @error('client_name') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
        ```
    *   Add public properties: `public ?string $client_email = null;`, `public ?string $client_name = null;`
    *   Add validation rules: `'client_email' => 'required_if:workflow_type,client_management|email|nullable', 'client_name' => 'nullable|string|max:255'`.
    *   Ensure `client_email` and `client_name` are saved with the project.
*   **Automatic Pitch Creation & Client Invitation (via `ProjectObserver::created`):**
    *   Extend the `created` method in `app/Observers/ProjectObserver.php`:
        ```php
        // Inside ProjectObserver::created method
        } elseif ($project->isClientManagement() && $project->client_email) {
            Log::info('Creating automatic pitch and inviting client for Client Management project', ['project_id' => $project->id, 'client_email' => $project->client_email]);
            try {
                $pitch = Pitch::create([
                    'project_id' => $project->id,
                    'user_id' => $project->user_id, // Pitch belongs to the *producer* managing the project
                    'title' => $project->title, // Default from project
                    'description' => $project->description,
                    'status' => Pitch::STATUS_IN_PROGRESS, // Start directly in progress
                    'terms_agreed' => true, // Assume terms agreed between producer and client externally initially
                ]);

                // Create initial event
                $pitch->events()->create([
                    'event_type' => 'client_project_created',
                    'comment' => 'Client management project created by producer.',
                    'status' => $pitch->status,
                    'created_by' => $project->user_id, // Producer action
                ]);

                // Generate Signed URL for Client Portal
                $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'client.portal.view', // Route name for the portal
                    now()->addDays(config('app.client_portal_link_expiry_days', 7)), // Configurable expiry
                    ['project' => $project->id] // Pass project ID
                );

                // Notify the external client via email
                $notificationService = app(NotificationService::class);
                // $notificationService->notifyClientProjectInvite($project, $signedUrl); // Requires new method

            } catch (\Exception $e) {
                Log::error('Failed to create automatic pitch/invite client for Client Management project', [
                    'project_id' => $project->id,
                    'client_email' => $project->client_email,
                    'error' => $e->getMessage()
                ]);
            }
        }
        ```
    *   *(Self-Correction): Ensure public `createPitch` is blocked (Phase 1/3). Ensure `approveInitialPitch` is blocked.* Add guard to `approveInitialPitch` in `PitchWorkflowService`: `if ($pitch->project->isClientManagement()) { throw ... }`.

**3. Client Interaction Mechanism (Secure Portal):**

*   **Routing (`routes/web.php`):** Define routes for the client portal, protected by signed middleware.
    ```php
    use App\Http\Controllers\ClientPortalController;

    Route::get('client-portal/project/{project}', [ClientPortalController::class, 'show'])
        ->name('client.portal.view')
        ->middleware('signed');

    // Add routes for client actions (POST requests)
    Route::post('client-portal/project/{project}/comments', [ClientPortalController::class, 'storeComment'])
        ->name('client.portal.comments.store')
        ->middleware('signed');

    Route::post('client-portal/project/{project}/approve', [ClientPortalController::class, 'approvePitch'])
        ->name('client.portal.approve')
        ->middleware('signed');

    Route::post('client-portal/project/{project}/request-revisions', [ClientPortalController::class, 'requestRevisions'])
        ->name('client.portal.revisions')
        ->middleware('signed');
    ```
*   **New Controller (`app/Http/Controllers/ClientPortalController.php`):**
    *   Create controller: `php artisan make:controller ClientPortalController`
    *   Implement methods corresponding to the routes.
        ```php
        namespace App\Http\Controllers;

        use App\Models\Project;
        use App\Models\Pitch; // Assuming one pitch per client project
        use App\Services\PitchWorkflowService;
        use Illuminate\Http\Request;
        use Illuminate\Support\Facades\Log;
        use Illuminate\Support\Facades\URL;

        class ClientPortalController extends Controller
        {
            // Show the main portal view
            public function show(Project $project, Request $request)
            {
                // Basic validation: Ensure it's a client management project
                if (!$project->isClientManagement()) {
                    abort(404);
                }

                // Retrieve the single pitch associated with this project
                // Eager load necessary relationships for the view
                $pitch = $project->pitches()->with(['user', 'files', 'events.user'])->first();

                if (!$pitch) {
                    Log::error('Client portal accessed but no pitch found', ['project_id' => $project->id]);
                    abort(404); // Or show an error view
                }

                // Pass project, pitch, and maybe a way to regenerate the signed URL for actions
                return view('client_portal.show', [
                    'project' => $project,
                    'pitch' => $pitch,
                ]);
            }

            // Store a comment from the client
            public function storeComment(Project $project, Request $request)
            {
                if (!$project->isClientManagement()) abort(403);
                $pitch = $project->pitches()->firstOrFail();

                $request->validate(['comment' => 'required|string|max:5000']);

                // Add comment via PitchEvent or dedicated comment model
                $pitch->events()->create([
                    'event_type' => 'client_comment',
                    'comment' => $request->input('comment'),
                    'status' => $pitch->status,
                    'created_by' => null, // Indicate client origin
                    'metadata' => ['client_email' => $project->client_email] // Store identifier
                ]);

                // Notify producer
                // app(NotificationService::class)->notifyProducerClientCommented($pitch, $request->input('comment'));

                return back()->with('success', 'Comment added.');
            }

            // Client approves the pitch submission
            public function approvePitch(Project $project, Request $request, PitchWorkflowService $workflowService)
            {
                if (!$project->isClientManagement()) abort(403);
                $pitch = $project->pitches()->firstOrFail();

                try {
                    // The service method needs to handle authorization (status check)
                    $workflowService->clientApprovePitch($pitch, $project->client_email);
                    return back()->with('success', 'Pitch approved.');
                } catch (\Exception $e) {
                    Log::error('Client failed to approve pitch', ['project_id' => $project->id, 'pitch_id' => $pitch->id, 'error' => $e->getMessage()]);
                    return back()->withErrors(['approval' => 'Could not approve pitch at this time. ' . $e->getMessage()]);
                }
            }

            // Client requests revisions
            public function requestRevisions(Project $project, Request $request, PitchWorkflowService $workflowService)
            {
                 if (!$project->isClientManagement()) abort(403);
                 $pitch = $project->pitches()->firstOrFail();

                 $request->validate(['feedback' => 'required|string|max:5000']);

                 try {
                     // Service method handles authorization (status check)
                     $workflowService->clientRequestRevisions($pitch, $request->input('feedback'), $project->client_email);
                     return back()->with('success', 'Revisions requested.');
                 } catch (\Exception $e) {
                    Log::error('Client failed to request revisions', ['project_id' => $project->id, 'pitch_id' => $pitch->id, 'error' => $e->getMessage()]);
                    return back()->withErrors(['revisions' => 'Could not request revisions at this time. ' . $e->getMessage()]);
                 }
            }
        }
        ```
*   **Client Portal View (`resources/views/client_portal/show.blade.php`):**
    *   Create a new Blade view for the client portal.
    *   Display project details (`$project->title`, `$project->description`).
    *   Display producer info (`$pitch->user->name`).
    *   Display pitch status (`$pitch->readable_status`).
    *   Integrate file display (potentially reuse `PitchFiles` component logic, adapted for read-only or client-specific interaction).
        *   **File Permissions Note:** Clearly define what clients can do with files. Can they only download? Can they upload reference files? Can they delete files they uploaded? This needs corresponding backend logic/policy enforcement in the `ClientPortalController` file handling methods (if any are added beyond viewing/downloading).
    *   Display comment history (`$pitch->events` filtered for comments).
    *   Add a form for clients to submit comments (posting to `client.portal.comments.store`).
    *   Conditionally show "Approve" and "Request Revisions" buttons when `$pitch->status` is `STATUS_READY_FOR_REVIEW`.
        ```blade
        @if($pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
            <form action="{{ route('client.portal.approve', ['project' => $project->id, 'signature' => request('signature'), 'expires' => request('expires')]) }}" method="POST">
                @csrf
                <button type="submit">Approve Submission</button>
            </form>

            <form action="{{ route('client.portal.revisions', ['project' => $project->id, 'signature' => request('signature'), 'expires' => request('expires')]) }}" method="POST">
                @csrf
                <div>
                    <label for="feedback">Request Revisions (Feedback Required):</label>
                    <textarea name="feedback" id="feedback" required></textarea>
                </div>
                <button type="submit">Request Revisions</button>
            </form>
        @endif
        ```
    *   Handle expired/invalid links gracefully (Laravel's `signed` middleware does this, customize the error view if needed).
*   **Producer "Resend Invite" Functionality:**
    *   Add a button in the producer's `ManageProject` view for client management projects.
    *   This button triggers a Livewire action (`resendClientInvite`).
    *   Implement `resendClientInvite` in `ManageProject.php`: Regenerate the signed URL (`URL::temporarySignedRoute(...)`) and call a notification service method (`notifyClientProjectInvite`) again.
*   **Security:** Ensure standard web security practices (input validation, output encoding, CSRF protection, etc.) are applied to the Client Portal routes and views. Log client actions thoroughly.

**4. Workflow Modifications (`app/Services/PitchWorkflowService.php`):**

*   **Adapt `submitPitchForReview`:**
    *   If the project is `clientManagement`, trigger a specific notification to the client including the signed URL.
        ```php
        // Inside submitPitchForReview, after saving snapshot and pitch status
        if ($pitch->project->isClientManagement()) {
            $signedUrl = URL::temporarySignedRoute(...); // Regenerate URL
            // $this->notificationService->notifyClientReviewReady($pitch, $signedUrl); // Requires new method
        } else {
            // Existing notification for standard projects
            $this->notificationService->notifyOwnerReviewReady($pitch);
        }
        ```
*   **New Method: `clientApprovePitch`:**
    ```php
    public function clientApprovePitch(Pitch $pitch, string $clientIdentifier): Pitch
    {
        // Validation (ensure it's client mgmt, correct status)
        if (!$pitch->project->isClientManagement()) {
             throw new UnauthorizedActionException('Client approval only for client management projects.');
        }
        if ($pitch->status !== Pitch::STATUS_READY_FOR_REVIEW) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_APPROVED, 'Pitch must be ready for review for client approval.');
        }
        // Optional: Verify $clientIdentifier matches $pitch->project->client_email if needed

        return DB::transaction(function () use ($pitch, $clientIdentifier) {
            $pitch->status = Pitch::STATUS_APPROVED;
            // Maybe update snapshot status if applicable?
            $pitch->approved_at = now();
            $pitch->save();

            // Create event
            $pitch->events()->create([
                'event_type' => 'client_approved',
                'comment' => 'Client approved the submission.',
                'status' => $pitch->status,
                'created_by' => null, // Client action
                'metadata' => ['client_email' => $clientIdentifier]
            ]);

            // Notify producer
            // $this->notificationService->notifyProducerClientApproved($pitch);

            return $pitch;
        });
    }
    ```
*   **New Method: `clientRequestRevisions`:**
    ```php
    public function clientRequestRevisions(Pitch $pitch, string $feedback, string $clientIdentifier): Pitch
    {
        // Validation
        if (!$pitch->project->isClientManagement()) {
             throw new UnauthorizedActionException('Client revisions only for client management projects.');
        }
         if ($pitch->status !== Pitch::STATUS_READY_FOR_REVIEW) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, 'Pitch must be ready for review to request client revisions.');
        }

        return DB::transaction(function () use ($pitch, $feedback, $clientIdentifier) {
            $pitch->status = Pitch::STATUS_CLIENT_REVISIONS_REQUESTED; // Use the new status
            $pitch->revisions_requested_at = now();
            $pitch->save();

            // Create event with feedback
            $pitch->events()->create([
                'event_type' => 'client_revisions_requested',
                'comment' => $feedback, // Store client feedback here
                'status' => $pitch->status,
                'created_by' => null, // Client action
                'metadata' => ['client_email' => $clientIdentifier]
            ]);

            // Notify producer
            // $this->notificationService->notifyProducerClientRevisionsRequested($pitch, $feedback);

            return $pitch;
        });
    }
    ```
*   **Producer Workflow After Client Revisions:** When the producer works on revisions (`STATUS_CLIENT_REVISIONS_REQUESTED`), they use the standard `submitPitchForReview` again, which will transition back to `STATUS_READY_FOR_REVIEW` and notify the client again.

**5. Policy Updates (`app/Policies/PitchPolicy.php`):**

*   Most standard producer actions (`submitForReview`, `uploadFile`, etc.) should work correctly if they check `$user->id === $pitch->user_id` (since the producer owns the pitch). Verify these checks.
*   Client actions are authorized via the `signed` middleware and controller checks, not typically via PitchPolicy since the client isn't a logged-in `User` in the standard sense.
*   Ensure standard owner actions (`approveInitial`, `approveSubmission`, etc.) are blocked for client management projects. Add checks like `!$pitch->project->isClientManagement()` where appropriate.

**6. Notifications (`app/Services/NotificationService.php`):**

*   Implement new notification methods:
    *   `notifyClientProjectInvite` (to `project->client_email`, includes signed URL)
    *   `notifyClientReviewReady` (to `project->client_email`, includes signed URL)
    *   `notifyProducerClientCommented` (to producer, `$pitch->user`)
    *   `notifyProducerClientApproved` (to producer)
    *   `notifyProducerClientRevisionsRequested` (to producer, includes feedback)
*   Adapt completion/invoice notifications (`PitchCompletionService`?) to potentially notify the client email as well.

**7. Frontend/UI (Producer Views):**

*   **Project Creation:** Updated in Step 2.
*   **Producer Views (`ManageProject`/`ManagePitch`):**
    *   Use `@if($project->isClientManagement())` or `@if($pitch->project->isClientManagement())`.
    *   Show client info (`client_email`, `client_name`).
    *   Change "Submit for Review" button text to "Submit for Client Review".
    *   Display client comments distinctly (e.g., identified by `client_email` in metadata).
    *   Show pitch status reflecting client actions (`STATUS_CLIENT_REVISIONS_REQUESTED`, `STATUS_APPROVED` when approved by client).
    *   Add "Resend Client Invite" button triggering the Livewire action.

**8. Testing:**

*   Unit tests for new `PitchWorkflowService` methods (`clientApprovePitch`, `clientRequestRevisions`).
*   Unit tests for `ProjectObserver` logic for client management projects.
*   Test signed URL generation and validation (including expiration).
*   Feature tests covering: Project creation by producer -> Invite sent -> Client accesses portal (valid/expired links) -> Client comments -> Producer submits -> Client receives notification -> Client approves -> Producer notified -> Producer completes project. Test revision loop.
*   Test "Resend Invite" functionality.

**9. Payment Flow Definition:**

*   **Decision Point:** Clearly define how the producer gets paid for Client Management projects.
    *   **Option A (Internal Payment):** The client pays the producer *through* MixPitch. This requires:
        *   Adding payment amount/details fields to the `Project` or `Pitch` for client projects.
        *   Integrating payment gateway buttons/forms into the `ClientPortalController` / `client_portal.show` view.
        *   Triggering `InvoiceService` upon client approval or project completion.
        *   Handling payouts to the producer.
    *   **Option B (External Payment):** MixPitch is used only for collaboration/delivery; payment is handled externally between producer and client. This is simpler for MixPitch implementation but offers less value/integration.
    *   **Recommendation:** Choose one path explicitly. If choosing Option A, detail the integration steps similar to Service Package payments (Phase 6). If Option B, ensure the UI doesn't imply internal payment.

## Phase 6: Service Packages Implementation

This phase introduces a system for producers to define, sell, and manage fixed-scope service packages through a dedicated order workflow, separate from projects/pitches.

**1. Database Schema (New Tables):**

*   **Create `service_packages` Table:**
    ```bash
    php artisan make:migration create_service_packages_table
    ```
    ```php
    // In up() method:
    Schema::create('service_packages', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Producer owning the package
        $table->string('title');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->decimal('price', 10, 2);
        $table->string('currency', 3)->default('USD');
        $table->text('deliverables')->nullable(); // What the client receives
        $table->unsignedInteger('revisions_included')->default(0);
        $table->unsignedInteger('estimated_delivery_days')->nullable();
        $table->text('requirements_prompt')->nullable(); // Instructions for the client
        $table->boolean('is_published')->default(false);
        // Consider: $table->string('category')->nullable(); $table->index('category');
        $table->timestamps();
        $table->softDeletes(); // Optional: allow soft deletion
    });
    ```
*   **Create `orders` Table:**
    ```bash
    php artisan make:migration create_orders_table
    ```
    ```php
    // In up() method:
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('service_package_id')->constrained()->onDelete('cascade');
        $table->foreignId('client_user_id')->constrained('users')->onDelete('cascade'); // Buyer
        $table->foreignId('producer_user_id')->constrained('users')->onDelete('cascade'); // Seller (denormalized for easier lookup)
        $table->string('status'); // e.g., pending_requirements, in_progress, completed
        $table->decimal('price', 10, 2); // Price at time of order
        $table->string('currency', 3);
        $table->string('payment_status')->default('pending'); // e.g., pending, paid, failed, refunded
        $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
        $table->text('requirements_submitted')->nullable(); // Client's input
        $table->unsignedInteger('revision_count')->default(0);
        $table->timestamp('completed_at')->nullable();
        $table->timestamp('cancelled_at')->nullable(); // If implementing cancellations
        $table->text('cancellation_reason')->nullable();
        $table->timestamps();

        $table->index('status');
        $table->index('client_user_id');
        $table->index('producer_user_id');
        $table->index('payment_status');
    });
    ```
*   **Create `order_files` Table:**
    ```bash
    php artisan make:migration create_order_files_table
    ```
    ```php
    // In up() method:
    Schema::create('order_files', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Uploader (client or producer)
        $table->string('file_path');
        $table->string('file_name');
        $table->string('mime_type')->nullable();
        $table->unsignedBigInteger('size')->nullable();
        $table->string('disk')->default('public'); // Or your default storage disk
        $table->string('type')->default('general'); // e.g., requirement, delivery, reference
        $table->timestamps();

        $table->index('type');
    });
    ```
*   **Create `order_events` Table:**
    ```bash
    php artisan make:migration create_order_events_table
    ```
    ```php
    // In up() method:
    Schema::create('order_events', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // User performing action (null for system)
        $table->string('event_type'); // e.g., order_created, delivery_submitted, status_change
        $table->text('comment')->nullable();
        $table->string('status_from')->nullable();
        $table->string('status_to')->nullable();
        $table->json('metadata')->nullable(); // For extra context
        $table->timestamps();

        $table->index('event_type');
    });
    ```
*   **Run Migrations:** `php artisan migrate`

**2. Model Creation:**

*   Create models for the new tables:
    ```bash
    php artisan make:model ServicePackage -mfs # Creates model, migration, factory, seeder
    php artisan make:model Order -mfs
    php artisan make:model OrderFile -mfs
    php artisan make:model OrderEvent -mfs
    ```
    *(Migration files are already created above, adjust generated files if needed)*
*   **`app/Models/ServicePackage.php`:**
    *   Use `Sluggable` trait if desired.
    *   Define relationships: `user()` (belongsTo Producer), `orders()` (hasMany).
    *   Add `$fillable`, `$casts` (e.g., price to decimal, revisions/days to integer).
    *   Add scope: `scopePublished($query) { return $query->where('is_published', true); }`
*   **`app/Models/Order.php`:**
    *   Define Status Constants: `STATUS_PENDING_PAYMENT`, `STATUS_PENDING_REQUIREMENTS`, `STATUS_IN_PROGRESS`, `STATUS_NEEDS_CLARIFICATION`, `STATUS_READY_FOR_REVIEW`, `STATUS_REVISIONS_REQUESTED`, `STATUS_COMPLETED`, `STATUS_CANCELLED`, `STATUS_DISPUTED`.
        *   **Note:** Add `STATUS_DISPUTED` for handling conflicts later.
    *   Define Payment Status Constants (reuse from Pitch? `PAYMENT_STATUS_PENDING`, `PAYMENT_STATUS_PAID`, etc.).
        *   **Recommendation:** Define distinct constants for Order payment statuses (e.g., `ORDER_PAYMENT_PENDING`, `ORDER_PAYMENT_PAID`, `ORDER_PAYMENT_FAILED`, `ORDER_PAYMENT_REFUNDED`) even if they initially mirror Pitch statuses. This provides flexibility if the order payment lifecycle diverges later.
        ```php
        // Example within Order model
        const PAYMENT_STATUS_PENDING = 'pending';
        const PAYMENT_STATUS_PAID = 'paid';
        const PAYMENT_STATUS_FAILED = 'failed';
        const PAYMENT_STATUS_REFUNDED = 'refunded';
        ```
    *   Add `$fillable`, `$casts` (price to decimal, counts to integer, dates).
    *   Define relationships: `servicePackage()` (belongsTo), `client()` (belongsTo User), `producer()` (belongsTo User), `files()` (hasMany OrderFile), `events()` (hasMany OrderEvent), `invoice()` (belongsTo Invoice).
    *   Add helper methods: `isCompleted()`, `isCancelled()`, `canRequestRevision()`, `getReadableStatusAttribute()`.
*   **`app/Models/OrderFile.php`:**
    *   Define File Type constants: `TYPE_REQUIREMENT`, `TYPE_DELIVERY`, `TYPE_REFERENCE`.
    *   Add `$fillable`, `$casts`.
    *   Define relationships: `order()` (belongsTo), `uploader()` (belongsTo User).
*   **`app/Models/OrderEvent.php`:**
    *   Define Event Type constants.
    *   Add `$fillable`, `$casts` (metadata to array/object).
    *   Define relationships: `order()` (belongsTo), `user()` (belongsTo).

**3. Producer Features (Service Package Management):**

*   **Routing (`routes/web.php`):** Define resource routes for producers managing their packages, likely namespaced/prefixed and middleware-protected.
    ```php
    use App\Http\Controllers\Producer\ServicePackageController;

    Route::middleware(['auth', 'verified']) // Add producer role middleware if exists
        ->prefix('producer/services')
        ->name('producer.services.')
        ->group(function () {
            Route::get('/', [ServicePackageController::class, 'index'])->name('index');
            Route::get('/create', [ServicePackageController::class, 'create'])->name('create');
            Route::post('/', [ServicePackageController::class, 'store'])->name('store');
            Route::get('/{servicePackage}/edit', [ServicePackageController::class, 'edit'])->name('edit');
            Route::put('/{servicePackage}', [ServicePackageController::class, 'update'])->name('update');
            Route::delete('/{servicePackage}', [ServicePackageController::class, 'destroy'])->name('destroy');
            Route::patch('/{servicePackage}/toggle-publish', [ServicePackageController::class, 'togglePublish'])->name('togglePublish');
        });
    ```
*   **New Controller (`app/Http/Controllers/Producer/ServicePackageController.php`):**
    *   Create controller: `php artisan make:controller Producer/ServicePackageController --model=ServicePackage --resource` (adjust namespace if needed)
    *   Implement standard CRUD methods (`index`, `create`, `store`, `edit`, `update`, `destroy`).
    *   Use authorization (Policies or checks) to ensure only the owner can manage their packages.
    *   Implement `togglePublish` method.
    *   Use Form Requests for validation (`StoreServicePackageRequest`, `UpdateServicePackageRequest`).
*   **New Policy (`app/Policies/ServicePackagePolicy.php`):**
    *   Create policy: `php artisan make:policy ServicePackagePolicy --model=ServicePackage`
    *   Implement methods: `viewAny`, `view`, `create`, `update`, `delete`, `togglePublish`. Ensure only the owner (`$user->id === $servicePackage->user_id`) can perform management actions.
*   **UI (New Livewire Components / Blade Views):**
    *   Create views for listing (`index`), creating (`create`), and editing (`edit`) service packages under `resources/views/producer/services/`.
    *   Use Livewire components for forms if preferred.

**4. Client Features (Discovery & Purchase):**

*   **Routing (`routes/web.php`):**
    *   Route to view a specific package (e.g., using slug): `Route::get('/services/{servicePackage:slug}', [App\Http\Controllers\ServicePackageController::class, 'show'])->name('services.show');`
    *   Route for the purchase initiation/checkout page: `Route::get('/services/{servicePackage:slug}/order', [App\Http\Controllers\OrderController::class, 'create'])->name('orders.create')->middleware('auth');`
    *   Route to handle the order submission (payment + order creation): `Route::post('/orders', [App\Http\Controllers\OrderController::class, 'store'])->name('orders.store')->middleware('auth');`
    *   *(Optional)* Route for a marketplace browsing page.
*   **New Controller (`app/Http/Controllers/ServicePackageController.php` - Public):**
    *   Implement `show` method to display a published service package details.
*   **New Controller (`app/Http{Controllers/OrderController.php`):**
    *   Create controller: `php artisan make:controller OrderController`
    *   Implement `create` method: Show the order confirmation/requirements page. Pass the `ServicePackage`. Requires user to be logged in.
    *   Implement `store` method:
        *   Validate input (package ID, requirements text).
        *   Retrieve `ServicePackage`.
        *   **(Upfront Payment Flow):**
            *   Initiate payment via Payment Gateway Service (e.g., Stripe Checkout Session).
            *   On successful payment webhook/callback:
                *   **Critical Point:** Handle potential race conditions or failures carefully here.
                *   Create `Order` record (status `PENDING_REQUIREMENTS` or `IN_PROGRESS` if requirements collected at checkout).
                *   Copy price/details from `ServicePackage` to `Order`.
                *   Create `Invoice` via `InvoiceService` and link to order.
                *   Update `Order` `payment_status` to `PAID`.
                *   Save submitted requirements to `Order`.
                *   Create initial `OrderEvent`.
                *   Notify producer and client.
                *   Redirect client to the order view page.
                *   **Error Handling/Feedback:** Implement robust logging. If any step fails after payment is confirmed (e.g., DB error creating order), ensure there's a clear notification to admin and potentially a way to inform the user or reconcile manually.
        *   **(Alternative Flow - Pay After Requirements):** Create `Order` first (status `PENDING_PAYMENT`, payment_status `PENDING`), collect requirements, then redirect to payment.
*   **UI (New Views):**
    *   Service package detail view (`resources/views/services/show.blade.php`).
    *   Order creation/checkout view (`resources/views/orders/create.blade.php`) - include requirements form.
    *   Display published packages on producer profiles.

**5. Order Management & Workflow:**

*   **Routing (`routes/web.php`):** Routes for viewing and managing orders.
    ```php
    use App\Http\Controllers\OrderController; // Already used

    Route::middleware(['auth', 'verified'])->prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index'); // List orders for current user (client or producer)
        Route::get('/{order}', [OrderController::class, 'show'])->name('show'); // View specific order
        // Add POST/PATCH routes for actions (handled by OrderWorkflowService via controller methods/Livewire actions)
        Route::post('/{order}/start-work', [OrderController::class, 'startWork'])->name('startWork');
        Route::post('/{order}/request-clarification', [OrderController::class, 'requestClarification'])->name('requestClarification');
        Route::post('/{order}/submit-delivery', [OrderController::class, 'submitDelivery'])->name('submitDelivery');
        Route::post('/{order}/accept-delivery', [OrderController::class, 'acceptDelivery'])->name('acceptDelivery');
        Route::post('/{order}/request-revision', [OrderController::class, 'requestRevision'])->name('requestRevision');
        Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
        // Route for file uploads related to an order
        Route::post('/{order}/files', [OrderController::class, 'uploadFile'])->name('files.upload');
    });
    ```
*   **Controller (`app/Http{Controllers/OrderController.php`):**
    *   Implement `index`: List orders, filtering based on user role (client sees their purchases, producer sees their sales).
    *   Implement `show`: Display the detailed order view. Authorize using `OrderPolicy`.
    *   Implement action methods (`startWork`, `requestClarification`, `submitDelivery`, etc.):
        *   Authorize the action using `OrderPolicy`.
        *   Call the corresponding method in `OrderWorkflowService`.
        *   Handle validation (e.g., required feedback for clarification/revision).
        *   Redirect back to order view with success/error message.
    *   Implement `uploadFile`: Handle file uploads, associate with order using `OrderFile` model, authorize via policy.
*   **New Service (`app/Services/OrderWorkflowService.php`):**
    *   Create service: `OrderWorkflowService`
    *   Inject dependencies (e.g., `NotificationService`, maybe `FileManagementService`).
    *   Implement methods for each state transition:
        *   `startWork(Order $order, User $producer)`: `PENDING_REQUIREMENTS` -> `IN_PROGRESS`.
        *   `requestClarification(Order $order, User $producer, string $details)`: `PENDING_REQUIREMENTS` -> `NEEDS_CLARIFICATION`.
        *   `submitRequirements(Order $order, User $client, string $requirements)`: (If requirements submitted after payment) `PENDING_PAYMENT` -> `PENDING_REQUIREMENTS` or handles update if `NEEDS_CLARIFICATION`.
        *   `submitDelivery(Order $order, User $producer, array $filePaths, ?string $message)`: `IN_PROGRESS` or `REVISIONS_REQUESTED` -> `READY_FOR_REVIEW`. Creates `OrderFile` records (type `DELIVERY`).
        *   `acceptDelivery(Order $order, User $client)`: `READY_FOR_REVIEW` -> `COMPLETED`.
            *   **Payout Trigger:** Define the payout mechanism here. Does accepting delivery automatically mark the linked invoice for payout or trigger fund release? Update `payment_status` accordingly.
        *   `requestRevision(Order $order, User $client, string $feedback)`: `READY_FOR_REVIEW` -> `REVISIONS_REQUESTED`. Checks revision limit, increments count.
        *   `cancelOrder(Order $order, User $canceller, string $reason)`: Transition to `CANCELLED`. Implement refund logic via Payment Gateway Service based on status/policy.
            *   Consider transitioning to `STATUS_DISPUTED` if cancellation is contested or needs review.
    *   Each method should: perform authorization checks (user role, status), update order status, create `OrderEvent`, trigger notifications.
*   **New Policy (`app/Policies/OrderPolicy.php`):**
    *   Create policy: `php artisan make:policy OrderPolicy --model=Order`
    *   Implement methods: `view`, `update`, `startWork`, `requestClarification`, `submitDelivery`, `acceptDelivery`, `requestRevision`, `cancel`, `uploadFile`. Define logic based on user role (`client_user_id`, `producer_user_id`) and order `status`.
*   **UI (New - Order View):**
    *   Create view (`resources/views/orders/show.blade.php`) or Livewire component.
    *   Display order details, package info, status, requirements, payment status.
    *   List associated `OrderFile` records (categorized by type: Requirements, Delivery, Reference).
    *   Display `OrderEvent` history (log).
    *   Show contextual action buttons based on `OrderPolicy` checks (e.g., Producer sees "Start Work", Client sees "Accept Delivery" / "Request Revision").
    *   Include file upload component.
    *   Display revision count vs included.

**6. Notifications (`app/Services/NotificationService.php`):**

*   Implement new methods for order events:
    *   `notifyProducerOrderReceived`, `notifyClientOrderConfirmation`, `notifyClientClarificationNeeded`, `notifyProducerRequirementsSubmitted` (if needed), `notifyClientWorkStarted`, `notifyClientDeliveryReady`, `notifyProducerDeliveryAccepted`, `notifyProducerRevisionRequested`, `notifyClientOrderCompleted`, `notifyOrderCancelled`.

**7. Payment Integration (`InvoiceService`, Payment Gateway Service):**

*   Ensure `InvoiceService` can create invoices linked to `Order` (not just `Pitch`).
*   Integrate payment gateway service for checkout and potentially for handling refunds during cancellation.

**8. Testing:**

*   Unit tests for `ServicePackage` CRUD, `OrderWorkflowService` state transitions, `OrderPolicy` checks, revision logic, cancellation/refund logic.
*   Feature tests covering: Producer creates/publishes package -> Client views package -> Client orders/pays/submits requirements -> Producer starts work -> Producer delivers -> Client accepts. Test clarification loop, revision loop, cancellations.
*   Test "Resend Invite" functionality. 

**9. Dispute Resolution Workflow:**

*   **Define Process:** While `STATUS_DISPUTED` exists, the process needs definition.
    *   **Initiation:** How does a client or producer initiate a dispute? (e.g., Button in order view triggering a state change and notification).
    *   **Mediation:** Who handles the dispute? (e.g., Admin review).
    *   **Information Gathering:** What information is needed? (e.g., Dispute reason, evidence from both parties).
    *   **Possible Outcomes:** Define potential resolutions (e.g., Order cancellation with full/partial refund, order continuation, adjustment of deliverables/price).
    *   **Workflow Impact:** How does entering `STATUS_DISPUTED` affect other actions (e.g., pausing deadlines, blocking further payments/payouts)?
    *   **Implementation:** This may involve new admin interfaces, modifications to `OrderWorkflowService`, and potentially new event types/notifications.
    *   **Note:** A basic implementation might simply involve flagging the order for admin review, with the full workflow built out later.

## Phase 7: Conclusion and Future Considerations

Upon completion of these phases, the application will support the defined advanced project types alongside the original standard workflow. Key considerations moving forward include:

*   **Thorough Testing:** Continue rigorous testing across all project types, focusing on edge cases and interactions between different user roles and statuses.
*   **User Documentation:** Create clear documentation and guides for both project owners/clients and producers on how to use the different project types and their specific workflows.
*   **User Dashboards & Aggregated Views:** Review and potentially refactor user dashboards (`/dashboard`, profile pages, etc.) to provide a cohesive overview of all work types (Standard Projects, Contest Entries, Direct Hires, Client Projects, Service Orders) relevant to the user.
*   **Search & Discovery Updates:** Update public project/service browsing pages and search functionality to:
    *   Allow filtering by `workflow_type` where applicable.
    *   Correctly include/exclude specific project types (like Direct Hire) based on user context.
    *   Integrate searchable Service Packages into discovery mechanisms.
*   **Admin Tools & Oversight MVP:** Define the Minimum Viable Product for admin capabilities needed *at launch*. This should likely include:
    *   Viewing all project/order types and their details.
    *   Checking payment/invoice statuses across types.
    *   Basic dispute viewing/flagging.
    *   Ability to manually intervene in critical error scenarios (e.g., failed observer actions, stuck statuses) identified during development.
    *   **Admin Alerting:** Implement robust alerting for administrators for critical failures identified in earlier phases (e.g., payment processing errors post-confirmation, observer failures).
    *   **Admin Intervention:** Determine the minimum level of admin intervention needed *at launch*. Can admins view all project/order types? Can they manually adjust statuses or payment states in emergencies? Delaying all admin tools might be risky, especially with payment flows.
*   **User Roles & Permissions:** The current plan assumes Owner/Client vs. Producer roles. If future needs involve teams or more granular client permissions, review if the foundational User/Role/Policy structure allows for this extension or if it requires significant refactoring later.
*   **File Management Strategy:** Formalize the application's file storage strategy (e.g., local vs. S3, backup policy, potential security scanning for uploads, cleanup of orphaned files) if not already well-defined. Ensure consistency in handling and access control across `PitchFile`, `OrderFile`, etc.
*   **API Strategy:** Consider if a public or internal API will be needed for these features in the future (e.g., mobile app, integrations). Design services with potential API usage in mind (e.g., clear service contracts, separation of concerns) even if API routes/controllers aren't built initially.
*   **File Management Strategy:** Formalize the application's file storage strategy (e.g., local vs. S3, backup policy, potential security scanning for uploads, cleanup of orphaned files) if not already well-defined.
*   **Onboarding & Feature Discovery:** Plan how to introduce these new types to existing users. Consider UI elements like tooltips, banners, help icons linking to documentation, or brief explanations within the project creation flow.
*   **Code Refinement & Consolidation:** After the initial implementation and real-world usage, revisit the codebase, particularly:
    *   **`PitchWorkflowService` / `OrderWorkflowService`:** Look for opportunities to abstract common logic or patterns, potentially using state machines or more sophisticated design patterns if complexity warrants it.
    *   **Policies (`PitchPolicy`, `OrderPolicy`, `ServicePackagePolicy`):** Review for clarity and potential simplification. Ensure consistent authorization logic.
    *   **UI Components:** Consolidate duplicated UI logic using shared Blade components or Livewire includes where appropriate, while still allowing for type-specific variations.
    *   **Notifications:** Ensure notification logic is robust, configurable (user preferences), and avoids sending redundant messages.
*   **Monitoring & Feedback:** Monitor system performance and gather user feedback to identify areas for improvement or refinement in the workflows.
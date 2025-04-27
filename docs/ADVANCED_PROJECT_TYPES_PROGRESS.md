# Advanced Project Types Progress

This document tracks the progress of implementing the advanced project types based on the detailed steps in `IMPLEMENTATION_PLAN_ADVANCED_PROJECT_TYPES.md`.

## Status Key
- [ ] 🔴 **Not Started** - Work has not yet begun
- [ ] 🟡 **In Progress** - Currently being worked on
- [ ] 🟢 **Completed** - Task finished and verified
- [ ] ⚫ **Blocked** - Cannot proceed due to dependencies or issues

*Check the box next to the status key above as work progresses on an item.*

---

## Phase 1: Foundational Changes
*Goal: Establish core infrastructure for multiple project types.*

- **Assignee:** TBD
- **Overall Status:** 🔴 Not Started

### Checklist
- [ ] Create Migration: `add_project_type_and_related_fields_to_projects`
- [ ] Modify `projects` Table (`up`): Add `project_type`, `target_producer_id`, `client_email`, `client_name`, `prize_amount`, `prize_currency`, `submission_deadline`, `judging_deadline` columns and indices.
- [ ] Modify `projects` Table (`down`): Add corresponding `dropColumn` and `dropForeign`.
- [ ] Modify `pitches` Table (Optional): Add index to `project_id`.
- [ ] Run Migration(s).
- [ ] Update `app/Models/Project.php`:
    - [ ] Add `TYPE_*` Constants.
    - [ ] Add `DEFAULT_CURRENCY` Constant.
    - [ ] Update `$fillable` array.
    - [ ] Add `targetProducer()` relationship.
    - [ ] Add `isStandard()`, `isContest()`, `isDirectHire()`, `isClientManagement()` helpers.
    - [ ] Add `getProjectTypes()` static helper.
    - [ ] Add `getReadableProjectTypeAttribute()` helper.
    - [ ] Update `$casts` (Optional: `target_producer_id`, `prize_amount`).
- [ ] Update `app/Models/Pitch.php`:
    - [ ] Add `STATUS_CONTEST_*` constants.
    - [ ] Add `STATUS_AWAITING_ACCEPTANCE` constant (Direct Hire - Explicit Flow).
    - [ ] Add `STATUS_CLIENT_REVISIONS_REQUESTED` constant (Client Mgmt - Explicit Flow).
    - [ ] Update `getReadableStatusAttribute`, `getStatusDescriptionAttribute`, `getStatuses` methods.
    - [ ] Add `rank` column migration & add to `$fillable` (Contests - Optional).
- [ ] Update `app/Services/PitchWorkflowService.php` (Initial Guarding):
    - [ ] Inject `Project` type hints.
    - [ ] Add initial guards to `createPitch` (block Direct Hire, Client Mgmt).
    - [ ] Add initial guards to `approveInitialPitch` (require Standard).
    - [ ] Review/guard other methods incompatible with *all* non-standard types.
- [ ] Update `app/Policies/PitchPolicy.php` (Initial Guarding):
    - [ ] Inject `Project` type hints.
    - [ ] Add `project_type` guards to `create` policy method.
    - [ ] Add `project_type` guards to `approveInitial` policy method.
    - [ ] Review/guard other policy methods.
- [ ] Update Project Creation UI (`app/Livewire/CreateProject.php` & View):
    - [ ] Add `project_type` public property.
    - [ ] Add `project_type` select input to form.
    - [ ] Add `project_type` validation rule.
    - [ ] Ensure `project_type` is saved on creation.
- [ ] Configuration Setup:
    - [ ] Create/Update `config/mixpitch.php` (e.g., `client_portal_link_expiry_days`).
    - [ ] Add corresponding `.env` variables.

---

## Phase 2: Standard Project Formalization
*Goal: Verify and solidify the existing workflow as the "Standard" type.*

- **Assignee:** TBD
- **Overall Status:** 🔴 Not Started

### Checklist
- [ ] **Verification:**
    - [ ] Verify new projects default to `project_type = 'standard'`.
    - [ ] Test end-to-end standard workflow (Submit -> Approve Initial -> Submit Review -> Revisions -> Resubmit -> Approve Submission -> Complete).
    - [ ] Verify standard notifications are sent correctly.
    - [ ] Verify Phase 1 policy guards allow standard actions.
    - [ ] Verify Phase 1 service guards allow standard actions.
- [ ] **Code Adjustments (If Necessary):**
    - [ ] Ensure `PitchWorkflowService` methods check `!$pitch->project->isStandard()` where applicable.
    - [ ] Ensure `PitchPolicy` methods check `&& $pitch->project->isStandard()` where applicable (e.g., `approveInitial`).
    - [ ] Review `PitchCompletionService`: Ensure standard completion logic works, closes other standard pitches correctly.
    - [ ] Define/clarify Standard Pitch Payout Trigger sequence in `PitchCompletionService`.
    - [ ] Ensure UI components are not broken by Phase 1 changes.

---

## Phase 3: Contest Implementation
*Goal: Introduce Contest project type with entry submission and winner selection.*

- **Assignee:** TBD
- **Overall Status:** 🔴 Not Started

### Checklist
- [ ] **Model Updates (`app/Models/Pitch.php`):**
    - [ ] Implement `isContestEntry()`, `isContestWinner()` status helpers.
    - [ ] Update `getReadableStatusAttribute`/`getStatusDescriptionAttribute` for `STATUS_CONTEST_*`.
- [ ] **Workflow Service (`app/Services/PitchWorkflowService.php`):**
    - [ ] Modify `createPitch`: Check `isContest()`, enforce `submission_deadline`, set status to `STATUS_CONTEST_ENTRY`, update event comment, trigger `notifyContestEntrySubmitted` (or adapt existing).
    - [ ] Guard Standard Actions: Add `if ($pitch->project->isContest())` checks to `submitPitchForReview`, `approveSubmittedPitch`, `requestPitchRevisions`, `denySubmittedPitch`, `cancelPitchSubmission`, etc.
    - [ ] Implement `selectContestWinner`: Authorization, validation, set status/rank, set payment details (`prize_amount`, `payment_status`), create event, notify winner, call `closeOtherContestEntries`.
    - [ ] Integrate `InvoiceService::createInvoiceForContestPrize` call within `selectContestWinner` transaction (handle errors). Define payout trigger.
    - [ ] Implement `selectContestRunnerUp` (Optional): Authorization, validation, set status/rank, create event, notify runner-up.
    - [ ] Implement `closeOtherContestEntries`: Find other `CONTEST_ENTRY` pitches, update status to `CONTEST_NOT_SELECTED`, set `closed_at`, create events, notify producers.
    - [ ] Review `PitchCompletionService`: Ensure standard completion doesn't interfere.
- [ ] **Authorization (`app/Policies/PitchPolicy.php`):**
    - [ ] Modify standard action policies (`submitForReview`, etc.) to return `false` for contests.
    - [ ] Add `selectWinner` policy method.
    - [ ] Add `selectRunnerUp` policy method.
- [ ] **Controller / Route Adjustments:**
    - [ ] Review controllers (`PitchController`, `PitchSnapshotController`) for correct handling of blocked actions.
    - [ ] Ensure Snapshot routes/actions are blocked for contests.
- [ ] **Frontend/UI:**
    - [ ] Update `CreateProject`: Add conditional fields for `submission_deadline`, `judging_deadline`, `prize_amount`.
    - [ ] Update `ManageProject`: Use `@if($project->isContest())`, display deadlines/prize, list entries, implement deadline logic for winner selection, hide standard buttons.
    - [ ] Update `ManagePitch`: Use `@if($pitch->project->isContest())`, simplify view for entrants, show status/rank, hide standard elements.
    - [ ] Update `PitchFiles`: Ensure uploads work for `CONTEST_ENTRY`, consider disabling after selection.
- [ ] **Notifications (`app/Services/NotificationService.php`):**
    - [ ] Implement `notifyContestEntrySubmitted` (Optional).
    - [ ] Implement `notifyContestWinnerSelected`.
    - [ ] Implement `notifyContestRunnerUpSelected`.
    - [ ] Implement `notifyContestEntryNotSelected`.
- [ ] **Testing:**
    - [ ] Unit tests for new/modified `PitchWorkflowService` methods.
    - [ ] Unit tests for `createPitch` deadline enforcement.
    - [ ] Unit tests for new `PitchPolicy` methods.
    - [ ] Feature tests for contest lifecycle (create, enter, deadline, select winner, close others, notifications, UI).
    - [ ] Define Contest Dispute handling process (link to Phase 7/Dispute Resolution).

---

## Phase 4: Direct Hire Implementation
*Goal: Allow owners to directly assign a project to a specific producer.*

- **Assignee:** TBD
- **Overall Status:** 🔴 Not Started

### Checklist
- [ ] **Project Creation & Pitch Initiation:**
    - [ ] Update `CreateProject` UI: Conditionally show producer search input for `direct_hire`, implement search logic.
    - [ ] Add `target_producer_id` property and validation rules (`required_if`, `exists`).
    - [ ] Ensure `target_producer_id` is saved.
    - [ ] Create `ProjectObserver`.
    - [ ] Register `ProjectObserver`.
    - [ ] Implement `ProjectObserver::created`: Check `isDirectHire()`, create single `Pitch` assigned to `target_producer_id`, decide/set initial status (`STATUS_IN_PROGRESS` or `STATUS_AWAITING_ACCEPTANCE`), create initial event, trigger notification (`notifyDirectHireOffer` or `notifyDirectHireAssignment`). Handle errors.
- [ ] **Producer Acceptance/Rejection (Explicit Flow - `STATUS_AWAITING_ACCEPTANCE` Only):**
    - [ ] Update Producer Dashboard UI: Display offers, show Accept/Reject buttons.
    - [ ] Implement `PitchWorkflowService::acceptDirectHire`: Authorization, validation, update status to `IN_PROGRESS`, create event, notify owner (`notifyDirectHireAccepted`).
    - [ ] Implement `PitchWorkflowService::rejectDirectHire`: Authorization, validation, update status to `DENIED`/`CLOSED`, create event, notify owner (`notifyDirectHireRejected`). Define project state after rejection.
    - [ ] Add Livewire/Controller actions to call accept/reject service methods.
    - [ ] Add `PitchPolicy::acceptDirectHire` and `rejectDirectHire` methods.
- [ ] **Leveraging Standard Workflow Components:**
    - [ ] Verify `PitchWorkflowService` blocks `createPitch`, `approveInitialPitch` (Phase 1/3).
    - [ ] Verify standard `PitchCompletionService` works for Direct Hire and triggers standard payout.
    - [ ] Update `PitchPolicy` methods (`submitForReview`, `approveSubmission`, `requestRevisions`, `denySubmission`, `complete`, `view`, `uploadFile`, etc.): Ensure actions allow EITHER owner OR assigned producer (`$pitch->user_id`) as appropriate after `IN_PROGRESS`.
- [ ] **Notifications (`app/Services/NotificationService.php`):**
    - [ ] Implement `notifyDirectHireOffer` (Explicit Flow).
    - [ ] Implement `notifyDirectHireAssignment` (Implicit Flow).
    - [ ] Implement `notifyDirectHireAccepted` (Explicit Flow).
    - [ ] Implement `notifyDirectHireRejected` (Explicit Flow).
    - [ ] Verify standard notifications trigger correctly for owner/producer post-`IN_PROGRESS`.
- [ ] **Frontend/UI:**
    - [ ] Update `CreateProject` (Done in Step 1).
    - [ ] Update Producer Dashboard (Done in Step 2 - Explicit Flow).
    - [ ] Update `ManageProject`: Use `@if($project->isDirectHire())`, show target producer, hide applicant controls, show standard pitch controls post-`IN_PROGRESS`.
    - [ ] Update `ManagePitch`: Use `@if($pitch->project->isDirectHire())`, show Accept/Reject if applicable, show standard producer controls post-`IN_PROGRESS`.
    - [ ] Update Project Browsing: Hide Direct Hire projects unless owner or target producer.
- [ ] **Testing:**
    - [ ] Unit tests for `acceptDirectHire`, `rejectDirectHire` (if applicable).
    - [ ] Unit tests for `ProjectObserver::created`.
    - [ ] Update existing `PitchPolicy` tests for Direct Hire permissions.
    - [ ] Feature tests for Direct Hire lifecycle (create, auto-pitch, notify, accept/reject, review cycle, complete, access control).

---

## Phase 5: Client Management Implementation
*Goal: Enable producers to manage projects for external clients via a secure portal.*

- **Assignee:** TBD
- **Overall Status:** 🔴 Not Started

### Checklist
- [ ] **Project Creation & Pitch Initiation:**
    - [ ] Update `CreateProject` UI: Conditionally show `client_email` (required) and `client_name` inputs.
    - [ ] Add `client_email`, `client_name` properties and validation rules.
    - [ ] Ensure client details are saved.
    - [ ] Extend `ProjectObserver::created`: Check `isClientManagement()`, create single `Pitch` assigned to producer (`project->user_id`), set status `IN_PROGRESS`, create initial event, generate signed URL (`client.portal.view`), trigger `notifyClientProjectInvite`. Handle errors.
    - [ ] Ensure `approveInitialPitch` is blocked for this type in service/policy.
- [ ] **Client Interaction Mechanism (Secure Portal):**
    - [ ] Define Routes (`routes/web.php`): `client.portal.view` (GET), `client.portal.comments.store` (POST), `client.portal.approve` (POST), `client.portal.revisions` (POST) - all using `signed` middleware.
    - [ ] Create `ClientPortalController`.
    - [ ] Implement `ClientPortalController::show`: Validate `isClientManagement`, fetch pitch, return `client_portal.show` view.
    - [ ] Implement `ClientPortalController::storeComment`: Validate input, create `PitchEvent` (type `client_comment`, null user, store email in metadata), notify producer.
    - [ ] Implement `ClientPortalController::approvePitch`: Call `PitchWorkflowService::clientApprovePitch`. Handle exceptions.
    - [ ] Implement `ClientPortalController::requestRevisions`: Validate feedback, call `PitchWorkflowService::clientRequestRevisions`. Handle exceptions.
    - [ ] Create Client Portal View (`resources/views/client_portal/show.blade.php`): Display project/producer info, pitch status, files (define permissions), comments, comment form. Conditionally show Approve/Request Revision forms (checking status `READY_FOR_REVIEW`). Handle expired/invalid links.
    - [ ] Implement Producer "Resend Invite" button/action in `ManageProject` (regenerates URL, calls `notifyClientProjectInvite`).
    - [ ] Ensure security practices (validation, encoding, CSRF) in portal.
- [ ] **Workflow Modifications (`app/Services/PitchWorkflowService.php`):**
    - [ ] Adapt `submitPitchForReview`: If `clientManagement`, generate signed URL, trigger `notifyClientReviewReady`.
    - [ ] Implement `clientApprovePitch`: Validate type/status, update status to `APPROVED`, create event, notify producer (`notifyProducerClientApproved`).
    - [ ] Implement `clientRequestRevisions`: Validate type/status, update status to `CLIENT_REVISIONS_REQUESTED`, create event with feedback, notify producer (`notifyProducerClientRevisionsRequested`).
    - [ ] Verify producer uses standard `submitPitchForReview` after client revisions.
- [ ] **Policy Updates (`app/Policies/PitchPolicy.php`):**
    - [ ] Verify standard producer actions check `$user->id === $pitch->user_id`.
    - [ ] Ensure standard owner actions (`approveInitial`, `approveSubmission`, etc.) are blocked.
    - [ ] Confirm client actions are authorized via signed middleware, not PitchPolicy.
- [ ] **Notifications (`app/Services/NotificationService.php`):**
    - [ ] Implement `notifyClientProjectInvite`.
    - [ ] Implement `notifyClientReviewReady`.
    - [ ] Implement `notifyProducerClientCommented`.
    - [ ] Implement `notifyProducerClientApproved`.
    - [ ] Implement `notifyProducerClientRevisionsRequested`.
    - [ ] Adapt completion/invoice notifications for client email.
- [ ] **Frontend/UI (Producer Views):**
    - [ ] Update `CreateProject` (Done in Step 1).
    - [ ] Update `ManageProject`/`ManagePitch`: Use `@if($project->isClientManagement())`, show client info, update "Submit" button text, display client comments, show client-related statuses, add "Resend Invite" button.
- [ ] **Testing:**
    - [ ] Unit tests for new `PitchWorkflowService` methods.
    - [ ] Unit tests for `ProjectObserver` client mgmt logic.
    - [ ] Test signed URL generation/validation/expiration.
    - [ ] Feature tests for client mgmt lifecycle (create -> invite -> portal access -> comments -> producer submit -> client review/approve/revisions -> producer complete). Test resend invite.
- [ ] **Payment Flow Definition:**
    - [ ] Decide: Option A (Internal Payment) or Option B (External Payment).
    - [ ] If Option A: Detail implementation steps (add payment fields, integrate gateway in portal, update InvoiceService, handle payouts).

---

## Phase 6: Service Packages Implementation
*Goal: Enable producers to sell fixed-scope services via a separate order workflow.*

- **Assignee:** TBD
- **Overall Status:** 🔴 Not Started

### Checklist
- [ ] **Database Schema (New Tables):**
    - [ ] Create `service_packages` table migration & schema.
    - [ ] Create `orders` table migration & schema.
    - [ ] Create `order_files` table migration & schema.
    - [ ] Create `order_events` table migration & schema.
    - [ ] Run Migrations.
- [ ] **Model Creation:**
    - [ ] Create `ServicePackage` model (`-mfs`). Implement relationships (`user`, `orders`), `$fillable`, `$casts`, `Sluggable` (opt.), `scopePublished`.
    - [ ] Create `Order` model (`-mfs`). Define `STATUS_*` constants. Define `PAYMENT_STATUS_*` constants. Add `$fillable`, `$casts`. Define relationships (`servicePackage`, `client`, `producer`, `files`, `events`, `invoice`). Add helpers (`isCompleted`, `isCancelled`, `canRequestRevision`, `getReadableStatusAttribute`).
    - [ ] Create `OrderFile` model (`-mfs`). Define `TYPE_*` constants. Add `$fillable`, `$casts`. Define relationships (`order`, `uploader`).
    - [ ] Create `OrderEvent` model (`-mfs`). Define event type constants. Add `$fillable`, `$casts`. Define relationships (`order`, `user`).
- [ ] **Producer Features (Service Package Management):**
    - [ ] Define Routes (`producer.services.*` resource routes + `togglePublish`).
    - [ ] Create `Producer/ServicePackageController`. Implement CRUD methods, `togglePublish`. Use Form Requests.
    - [ ] Create `ServicePackagePolicy`. Implement `viewAny`, `view`, `create`, `update`, `delete`, `togglePublish` (owner checks).
    - [ ] Create UI (Livewire/Blade Views) for producer service management (`index`, `create`, `edit`).
- [ ] **Client Features (Discovery & Purchase):**
    - [ ] Define Routes: `services.show` (GET), `orders.create` (GET, auth), `orders.store` (POST, auth).
    - [ ] Create `ServicePackageController` (public). Implement `show` method.
    - [ ] Create `OrderController`.
    - [ ] Implement `OrderController::create`: Show order/requirements page.
    - [ ] Implement `OrderController::store` (Upfront Payment): Validate -> Get Package -> Initiate Payment -> Handle Webhook -> Create Order (`PENDING_REQUIREMENTS`/`IN_PROGRESS`) -> Copy Price -> Create/Link Invoice -> Update Order Payment Status (`PAID`) -> Save Requirements -> Create Event -> Notify -> Redirect. Implement robust error handling post-payment.
    - [ ] Create UI (Views): Package detail (`services.show`), Order create/checkout (`orders.create`).
    - [ ] Update Producer Profile UI to display published packages.
- [ ] **Order Management & Workflow:**
    - [ ] Define Routes (`orders.*` index, show + action routes: startWork, requestClarification, submitDelivery, acceptDelivery, requestRevision, cancel, files.upload).
    - [ ] Extend `OrderController`: Implement `index` (filter by role), `show` (authorize). Implement action methods (authorize -> call service -> redirect). Implement `uploadFile`.
    - [ ] Create `OrderWorkflowService`. Inject dependencies.
    - [ ] Implement `OrderWorkflowService` methods: `startWork`, `requestClarification`, `submitRequirements` (if needed), `submitDelivery` (create `OrderFile`), `acceptDelivery` (trigger payout), `requestRevision` (check limit, increment), `cancelOrder` (handle refund logic). Each method: authorize, update status, create event, notify.
    - [ ] Create `OrderPolicy`. Implement `view`, `update`, `startWork`, etc. based on role/status.
    - [ ] Create Order View UI (`orders.show` - Livewire/Blade): Display details, status, requirements, files (categorized), events, contextual actions (based on policy), file upload, revision count.
- [ ] **Notifications (`app/Services/NotificationService.php`):**
    - [ ] Implement new order notification methods (e.g., `notifyProducerOrderReceived`, `notifyClientDeliveryReady`, `notifyProducerRevisionRequested`, etc.).
- [ ] **Payment Integration (`InvoiceService`, Payment Gateway):**
    - [ ] Update `InvoiceService` to link invoices to `Order`.
    - [ ] Integrate Payment Gateway for checkout and refunds (in `cancelOrder`).
- [ ] **Testing:**
    - [ ] Unit tests for `ServicePackage` CRUD, `OrderWorkflowService` transitions, `OrderPolicy` checks, revision/cancellation logic.
    - [ ] Feature tests for Service Package lifecycle (create -> publish -> discover -> order -> pay -> requirements -> deliver -> accept/revise -> complete). Test clarification, revision limits, cancellation/refunds.
- [ ] **Dispute Resolution Workflow:**
    - [ ] Define Dispute Process: Initiation, Mediation, Information Gathering, Outcomes, Workflow Impact.
    - [ ] Implement MVP: Flag order (`STATUS_DISPUTED`), notify admin.

---

## Phase 7: Conclusion and Future Considerations
*Goal: Finalize integration, plan next steps.*

- **Assignee:** TBD
- **Overall Status:** 🔴 Not Started

### Checklist
- [ ] **Thorough Testing:** Conduct comprehensive end-to-end testing across all project types.
- [ ] **User Documentation:** Create guides for owners/clients and producers for each project type.
- [ ] **User Dashboards & Aggregated Views:** Refactor dashboards to show all relevant work types.
- [ ] **Search & Discovery Updates:** Update browsing/search to filter by type, handle access control, include Service Packages.
- [ ] **Admin Tools & Oversight MVP:**
    - [ ] Implement Admin Views for all project/order types.
    - [ ] Implement Admin check for payment/invoice statuses.
    - [ ] Implement Basic Admin dispute viewing/flagging.
    - [ ] Define/Implement minimal manual intervention capabilities for critical errors.
    - [ ] Implement Admin Alerting for critical failures.
- [ ] **Review User Roles & Permissions:** Assess if current structure supports future needs (teams, etc.).
- [ ] **Formalize File Management Strategy:** Define storage, backups, security scanning, cleanup. Ensure consistency.
- [ ] **Consider API Strategy:** Design services with potential future API usage in mind.
- [ ] **Plan Onboarding & Feature Discovery:** Develop strategy to introduce new types to users (UI hints, docs).
- [ ] **Code Refinement & Consolidation:** Schedule post-launch review of services, policies, UI components, notifications.
- [ ] **Setup Monitoring & Feedback:** Implement performance monitoring and plan for user feedback collection.

---

## Notes & Decisions

- [DATE] - Document updated to checklist format based on implementation plan. 
# Advanced Project Types Progress

This document tracks the progress of implementing the advanced project types based on the detailed steps in `IMPLEMENTATION_PLAN_ADVANCED_PROJECT_TYPES.md`.

## Status Key
- [ ] 🔴 **Not Started** - Work has not yet begun
- [ ] 🟡 **In Progress** - Currently being worked on
- [x] 🟢 **Completed** - Task finished and verified
- [ ] ⚫ **Blocked** - Cannot proceed due to dependencies or issues

*Check the box next to the status key above as work progresses on an item.*

---

## Phase 1: Foundational Changes
*Goal: Establish core infrastructure for multiple project types.*

- **Assignee:** TBD
- **Overall Status:** 🟢 Completed

### Checklist
- [x] 🟢 Create Migration: `add_project_type_and_related_fields_to_projects`
- [x] 🟢 Modify `projects` Table (`up`): Add `workflow_type`, `target_producer_id`, `client_email`, `client_name`, `prize_amount`, `prize_currency`, `submission_deadline`, `judging_deadline` columns and indices.
- [x] 🟢 Modify `projects` Table (`down`): Add corresponding `dropColumn` and `dropForeign`.
- [x] 🟢 Modify `pitches` Table (Optional): Add index to `project_id`.
- [x] 🟢 Run Migration(s).
- [x] 🟢 Update `app/Models/Project.php`:
    - [x] 🟢 Add `TYPE_*` Constants.
    - [x] 🟢 Add `DEFAULT_CURRENCY` Constant.
    - [x] 🟢 Update `$fillable` array.
    - [x] 🟢 Add `targetProducer()` relationship.
    - [x] 🟢 Add `isStandard()`, `isContest()`, `isDirectHire()`, `isClientManagement()` helpers.
    - [x] 🟢 Add `getProjectTypes()` static helper.
    - [x] 🟢 Add `getReadableProjectTypeAttribute()` helper.
    - [x] 🟢 Update `$casts` (Optional: `target_producer_id`, `prize_amount`).
- [x] 🟢 Update `app/Models/Pitch.php`:
    - [x] 🟢 Add `STATUS_CONTEST_*` constants.
    - [x] 🟢 Add `STATUS_AWAITING_ACCEPTANCE` constant (Direct Hire - Explicit Flow).
    - [x] 🟢 Add `STATUS_CLIENT_REVISIONS_REQUESTED` constant (Client Mgmt - Explicit Flow).
    - [x] 🟢 Update `getReadableStatusAttribute`, `getStatusDescriptionAttribute`, `getStatuses` methods.
    - [x] 🟢 Add `rank` column migration & add to `$fillable` (Contests - Optional).
- [x] 🟢 Update `app/Services/PitchWorkflowService.php` (Initial Guarding):
    - [ ] Inject `Project` type hints.
    - [x] 🟢 Add initial guards to `createPitch` (block Direct Hire, Client Mgmt).
    - [x] 🟢 Add initial guards to `approveInitialPitch` (require Standard).
    - [ ] Review/guard other methods incompatible with *all* non-standard types.
- [x] 🟢 Update `app/Policies/PitchPolicy.php` (Initial Guarding):
    - [x] 🟢 Inject `Project` type hints.
    - [x] 🟢 Add `project_type` guards to `create` policy method.
    - [x] 🟢 Add `project_type` guards to `approveInitial` policy method.
    - [ ] Review/guard other policy methods.
- [x] 🟢 Update Project Creation UI (`app/Livewire/CreateProject.php` & View):
    - [x] 🟢 Add `workflow_type` public property.
    - [x] 🟢 Add `workflow_type` select input to form.
    - [x] 🟢 Add `workflow_type` validation rule.
    - [x] 🟢 Ensure `workflow_type` is saved on creation.
- [x] 🟢 Configuration Setup:
    - [x] 🟢 Create/Update `config/mixpitch.php` (e.g., `client_portal_link_expiry_days`).
    - [x] Add corresponding `.env` variables.

---

## Phase 2: Standard Project Formalization
*Goal: Verify and solidify the existing workflow as the "Standard" type.*

- **Assignee:** TBD
- **Overall Status:** 🟢 Completed

### Checklist
- [x] 🟢 **Verification:**
    - [x] 🟢 Verify new projects default to `workflow_type = 'standard'`.
    - [ ] 🟢 Test end-to-end standard workflow (Submit -> Approve Initial -> Submit Review -> Revisions -> Resubmit -> Approve Submission -> Complete). (Manual Test Pending)
    - [ ] 🟢 Verify standard notifications are sent correctly. (Manual Test Pending)
    - [x] 🟢 Verify Phase 1 policy guards allow standard actions.
    - [x] 🟢 Verify Phase 1 service guards allow standard actions.
- [x] 🟢 **Code Adjustments (If Necessary):**
    - [x] 🟢 Ensure `PitchWorkflowService` methods check `!$pitch->project->isStandard()` where applicable. (Checked `approveInitialPitch`)
    - [x] 🟢 Ensure `PitchPolicy` methods check `&& $pitch->project->isStandard()` where applicable (e.g., `approveInitial`). (Checked `approveInitial`)
    - [x] 🟢 Review `PitchCompletionService`: Ensure standard completion logic works, closes other standard pitches correctly.
    - [x] 🟢 Define/clarify Standard Pitch Payout Trigger sequence in `PitchCompletionService`. (Clarified: Sets status to Pending, external process needed)
    - [x] 🟢 Ensure UI components are not broken by Phase 1 changes. (CreateProject UI updated)

---

## Phase 3: Contest Implementation
*Goal: Introduce Contest project type with entry submission and winner selection.*

- **Assignee:** TBD
- **Overall Status:** 🟢 Completed

### Checklist
- [x] 🟢 **Model Updates (`app/Models/Pitch.php`):**
    - [x] 🟢 Implement `isContestEntry()`, `isContestWinner()` status helpers.
    - [x] 🟢 Update `getReadableStatusAttribute`/`getStatusDescriptionAttribute` for `STATUS_CONTEST_*`.
- [x] 🟢 **Workflow Service (`app/Services/PitchWorkflowService.php`):**
    - [x] 🟢 Modify `createPitch`: Check `isContest()`, enforce `submission_deadline`, set status to `STATUS_CONTEST_ENTRY`, update event comment, trigger `notifyContestEntrySubmitted` (or adapt existing).
    - [x] 🟢 Guard Standard Actions: Add `if ($pitch->project->isContest())` checks to `submitPitchForReview`, `approveSubmittedPitch`, `requestPitchRevisions`, `denySubmittedPitch`, `cancelPitchSubmission`, etc.
    - [x] 🟢 Implement `selectContestWinner`: Authorization, validation, set status/rank, set payment details (`prize_amount`, `payment_status`), create event, notify winner, call `closeOtherContestEntries`.
    - [x] 🟢 Integrate `InvoiceService::createInvoiceForContestPrize` call within `selectContestWinner` transaction (handle errors). Define payout trigger.
    - [x] 🟢 Implement `selectContestRunnerUp` (Optional): Authorization, validation, set status/rank, create event, notify runner-up.
    - [x] 🟢 Implement `closeOtherContestEntries`: Find other `CONTEST_ENTRY` pitches, update status to `CONTEST_NOT_SELECTED`, set `closed_at`, create events, notify producers.
    - [x] 🟢 Review `PitchCompletionService`: Ensure standard completion doesn't interfere.
- [x] 🟢 **Authorization (`app/Policies/PitchPolicy.php`):**
    - [x] 🟢 Modify standard action policies (`submitForReview`, etc.) to return `false` for contests.
    - [x] 🟢 Add `selectWinner` policy method.
    - [x] 🟢 Add `selectRunnerUp` policy method.
- [x] 🟢 **Controller / Route Adjustments:**
    - [x] 🟢 Review controllers (`PitchController`, `PitchSnapshotController`) for correct handling of blocked actions.
    - [x] 🟢 Ensure Snapshot routes/actions are blocked for contests.
- [x] 🟢 **Frontend/UI:**
    - [x] 🟢 Update `CreateProject`: Add conditional fields for `submission_deadline`, `judging_deadline`, `prize_amount`.
    - [x] 🟢 Update `ManageProject`: Use `@if($project->isContest())`, display deadlines/prize, list entries, implement deadline logic for winner selection, hide standard buttons.
    - [x] 🟢 Update `ManagePitch`: Use `@if($pitch->project->isContest())`, simplify view for entrants, show status/rank, hide standard elements.
    - [x] 🟢 Update `PitchFiles`: Ensure uploads work for `CONTEST_ENTRY`, consider disabling after selection.
- [x] 🟢 **Notifications (`app/Services/NotificationService.php`):**
    - [x] 🟢 Implement `notifyContestEntrySubmitted` (Optional).
    - [x] 🟢 Implement `notifyContestWinnerSelected`.
    - [x] 🟢 Implement `notifyContestRunnerUpSelected`.
    - [x] 🟢 Implement `notifyContestEntryNotSelected`.
- [x] 🟢 **Testing:**
    - [x] 🟢 Unit tests for new/modified `PitchWorkflowService` methods.
    - [x] 🟢 Unit tests for `createPitch` deadline enforcement.
    - [x] 🟢 Unit tests for new `PitchPolicy` methods.
    - [x] 🟢 Feature tests for contest lifecycle (create, enter, deadline, select winner, close others, notifications, UI).
- [x] 🟢 Define Contest Dispute handling process (link to Phase 7/Dispute Resolution).

---

## Phase 4: Direct Hire Implementation
*Goal: Allow owners to directly assign a project to a specific producer.*

- **Assignee:** TBD
- **Overall Status:** 🟢 Completed

### Checklist
- [x] 🟢 **Project Creation & Pitch Initiation:**
    - [x] 🟢 Update `CreateProject` UI: Conditionally show producer search input for `workflow_type = direct_hire`, implement search logic.
    - [x] 🟢 Add `target_producer_id` property and validation rules (`required_if`, `exists`).
    - [x] 🟢 Ensure `target_producer_id` is saved.
    - [x] 🟢 Create `ProjectObserver`.
    - [x] 🟢 Register `ProjectObserver`.
    - [x] 🟢 Implement `ProjectObserver::created`: Check `isDirectHire()`, create single `Pitch` assigned to `target_producer_id`, decide/set initial status (`STATUS_IN_PROGRESS` - Implicit Flow Chosen), create initial event, trigger notification (`notifyDirectHireAssignment`). Handle errors.
- [ ] 🟡 **Producer Acceptance/Rejection (Explicit Flow - `STATUS_AWAITING_ACCEPTANCE` Only):**
    - [ ] 🟡 Update Producer Dashboard UI: Display offers, show Accept/Reject buttons.
    - [ ] 🟡 Implement `PitchWorkflowService::acceptDirectHire`: Authorization, validation, update status to `IN_PROGRESS`, create event, notify owner (`notifyDirectHireAccepted`).
    - [ ] 🟡 Implement `PitchWorkflowService::rejectDirectHire`: Authorization, validation, update status to `DENIED`/`CLOSED`, create event, notify owner (`notifyDirectHireRejected`). Define project state after rejection.
    - [ ] 🟡 Add Livewire/Controller actions to call accept/reject service methods.
    - [ ] 🟡 Add `PitchPolicy::acceptDirectHire` and `rejectDirectHire` methods.
- [x] 🟢 **Leveraging Standard Workflow Components:**
    - [x] 🟢 Verify `PitchWorkflowService` blocks `createPitch`, `approveInitialPitch` (Phase 1/3).
    - [x] 🟢 Verify standard `PitchCompletionService` works for Direct Hire and triggers standard payout.
    - [x] 🟢 Update `PitchPolicy` methods (`submitForReview`, `approveSubmission`, `requestRevisions`, `denySubmission`, `complete`, `view`, `uploadFile`, etc.): Ensure actions allow EITHER owner OR assigned producer (`$pitch->user_id`) as appropriate after `IN_PROGRESS`.
- [x] 🟢 **Notifications (`app/Services/NotificationService.php`):**
    - [ ] 🟡 Implement `notifyDirectHireOffer` (Explicit Flow).
    - [x] 🟢 Implement `notifyDirectHireAssignment` (Implicit Flow).
    - [ ] 🟡 Implement `notifyDirectHireAccepted` (Explicit Flow).
    - [ ] 🟡 Implement `notifyDirectHireRejected` (Explicit Flow).
    - [x] 🟢 Verify standard notifications trigger correctly for owner/producer post-`IN_PROGRESS`.
- [x] 🟢 **Frontend/UI:**
    - [x] 🟢 Update `CreateProject` (Done in Step 1).
    - [ ] 🟡 Update Producer Dashboard (Done in Step 2 - Explicit Flow).
    - [x] 🟢 Update `ManageProject`: Use `@if($project->isDirectHire())`, show target producer, hide applicant controls, show standard pitch controls post-`IN_PROGRESS`.
    - [x] 🟢 Update `ManagePitch`: Use `@if($pitch->project->isDirectHire())`, show Accept/Reject if applicable, show standard producer controls post-`IN_PROGRESS`.
    - [x] 🟢 Update Project Browsing: Hide Direct Hire projects unless owner or target producer.
- [x] 🟢 **Testing:**
    - [ ] 🟡 Unit tests for `acceptDirectHire`, `rejectDirectHire` (if applicable).
    - [x] 🟢 Unit tests for `ProjectObserver::created`.
    - [x] 🟢 Update existing `PitchPolicy` tests for Direct Hire permissions.
    - [x] 🟢 Feature tests for Direct Hire lifecycle (create, auto-pitch, notify, accept/reject, review cycle, complete, access control).

---

## Phase 5: Client Management Implementation
*Goal: Enable producers to manage projects for external clients via a secure portal.*

- **Assignee:** TBD
- **Overall Status:** 🟢 Completed

### Checklist
- [x] 🟢 **Project Creation & Pitch Initiation:**
    - [x] 🟢 Update `CreateProject` UI: Conditionally show `client_email` (required) and `client_name` inputs for `workflow_type = client_management`.
    - [x] 🟢 Add `client_email`, `client_name` properties and validation rules.
    - [x] 🟢 Ensure client details are saved.
    - [x] 🟢 Extend `ProjectObserver::created`: Check `isClientManagement()`, create single `Pitch` assigned to producer (`project->user_id`), set status `IN_PROGRESS`, create initial event, generate signed URL (`client.portal.view`), trigger `notifyClientProjectInvite`. Handle errors.
    - [x] 🟢 Ensure `approveInitialPitch` is blocked for this type in service/policy.
- [ ] 🟡 **Client Interaction Mechanism (Secure Portal):**
    - [x] 🟢 Define Routes (`routes/web.php`): `client.portal.view` (GET), `client.portal.comments.store` (POST), `client.portal.approve` (POST), `client.portal.revisions` (POST) - all using `signed` middleware.
    - [x] 🟢 Create `ClientPortalController`.
    - [x] 🟢 Implement `ClientPortalController::show`: Validate `isClientManagement`, fetch pitch, return `client_portal.show` view.
    - [x] 🟢 Implement `ClientPortalController::storeComment`: Validate input, create `PitchEvent` (type `client_comment`, null user, store email in metadata), notify producer.
    - [x] 🟢 Implement `ClientPortalController::approvePitch`: Call `PitchWorkflowService::clientApprovePitch`. Handle exceptions.
    - [x] 🟢 Implement `ClientPortalController::requestRevisions`: Validate feedback, call `PitchWorkflowService::clientRequestRevisions`. Handle exceptions.
    - [x] 🟢 Create Client Portal View (`resources/views/client_portal/show.blade.php`): Display project/producer info, pitch status, files (define permissions), comments, comment form. Conditionally show Approve/Request Revision forms (checking status `READY_FOR_REVIEW`). Handle expired/invalid links.
        - **View Implementation Plan:**
        - **Layout & Basic Info:**
            - `[ ] 🟡` Verify base HTML structure, title, meta tags, and asset linking (@vite).
            - `[x] 🟢` Display project title, producer name, client name/email.
            - `[x] 🟢` Display current pitch status (`readable_status`) and description (`status_description`).
            - `[x] 🟢` Display project brief (`project->description`) conditionally.
        - **Flash Messages:**
            - `[x] 🟢` Display session `success` messages.
            - `[x] 🟢` Display session `errors` (validation/general).
            - `[x] 🟢` *Testing:* Uncomment flash message assertions in `ClientPortalTest.php`.
        - **File Display & Download:**
            - `[x] 🟢` Iterate through `$pitch->files`.
            - `[x] 🟢` Display file name and size.
            - `[x] 🟢` **Implement Secure Download Links:**
                - `[x] 🟢` Create route `client.portal.download_file`.
                - `[x] 🟢` Apply `signed` middleware to the route.
                - `[x] 🟢` Create `ClientPortalController::downloadFile` method.
                - `[x] 🟢` Add authorization in controller: Verify file belongs to the pitch of the project in the signed URL.
                - `[x] 🟢` Implement secure file streaming response in the controller.
                - `[x] 🟢` Update view to use `route('client.portal.download_file', ...)` with necessary params.
            - `[x] 🟢` Handle "No files" case.
        - **Communication Log (Events):**
            - `[x] 🟢` Filter `$pitch->events` for relevant types.
            - `[x] 🟢` Display event `comment` (using `{{ }}` for safety).
            - `[x] 🟢` Display author (Client email, Producer name, or System).
            - `[x] 🟢` Display event timestamp (`created_at`).
            - `[x] 🟢` Display associated event `status`.
            - `[x] 🟢` Style client vs. producer/system events differently.
            - `[x] 🟢` Handle "No activity" case.
        - **Action Forms (Approve/Revise):**
            - `[x] 🟢` Confirm forms shown only when status is `READY_FOR_REVIEW`.
            - `[x] 🟢` Verify forms POST to correct signed routes, passing `signature` and `expires`.
            - `[x] 🟢` Ensure `@csrf` is present.
            - `[x] 🟢` Confirm `feedback` textarea has `required`.
            - `[x] 🟢` Confirm display of `@error('feedback')`.
        - **Comment Form:**
            - `[x] 🟢` Verify form POSTs to correct signed route, passing `signature` and `expires`.
            - `[x] 🟢` Ensure `@csrf` is present.
            - `[x] 🟢` Confirm `comment` textarea has `required`.
            - `[x] 🟢` Confirm display of `@error('comment')`.
        - **Styling & Responsiveness:**
            - `[ ] 🟡` Review layout, spacing, and responsiveness using Tailwind.
            - `[ ] 🟡` Ensure interactive elements are clear and usable.
        - **Security:**
            - `[x] 🟢` Verify user-generated content uses `{{ }}` escaping.
            - `[x] 🟢` Confirm critical actions rely on server-side checks.
    - [x] 🟢 Implement Producer "Resend Invite" button/action in `ManageProject` (regenerates URL, calls `notifyClientProjectInvite`).
    - [ ] 🟡 Ensure security practices (validation, encoding, CSRF) in portal.
- [x] 🟢 **Workflow Modifications (`app/Services/PitchWorkflowService.php`):**
    - [x] 🟢 Adapt `submitPitchForReview`: If `clientManagement`, generate signed URL, trigger `notifyClientReviewReady`.
    - [x] 🟢 Implement `clientApprovePitch`: Validate type/status, update status to `APPROVED`, create event, notify producer (`notifyProducerClientApproved`).
    - [x] 🟢 Implement `clientRequestRevisions`: Validate type/status, update status to `CLIENT_REVISIONS_REQUESTED`, create event with feedback, notify producer (`notifyProducerClientRevisionsRequested`).
    - [x] 🟢 Verify producer uses standard `submitPitchForReview` after client revisions.
- [x] 🟢 **Policy Updates (`app/Policies/PitchPolicy.php`):**
    - [x] 🟢 Verify standard producer actions check `$user->id === $pitch->user_id`.
    - [x] 🟢 Ensure standard owner actions (`approveInitial`, `approveSubmission`, etc.) are blocked.
    - [x] 🟢 Confirm client actions are authorized via signed middleware, not PitchPolicy.
- [ ] 🟡 **Notifications (`app/Services/NotificationService.php`):**
    - [x] 🟢 Implement `notifyClientProjectInvite`.
    - [x] 🟢 Implement `notifyClientReviewReady`.
    - [x] 🟢 Implement `notifyProducerClientCommented`.
    - [x] 🟢 Implement `notifyProducerClientApproved`.
    - [x] 🟢 Implement `notifyProducerClientRevisionsRequested`.
    - [x] 🟢 Adapt completion/invoice notifications for client email.
- [x] 🟢 **Frontend/UI (Producer Views):**
    - [x] 🟢 Update `CreateProject` (Done in Step 1).
    - [x] 🟢 Update `ManageProject`/`ManagePitch`: Use `@if($project->isClientManagement())`, show client info, update "Submit" button text, display client comments, show client-related statuses, add "Resend Invite" button.
- [x] 🟢 **Testing:**
    - [x] �� Unit tests for new `PitchWorkflowService` methods (e.g., `clientApprovePitch`, `clientRequestRevisions`).
    - [x] 🟢 Unit tests for `ProjectObserver` client mgmt logic.
    - [x] 🟢 Test signed URL generation/validation/expiration (Covered by Feature Tests).
    - [x] 🟢 Feature tests for client mgmt lifecycle (Initial parts completed):
        - [x] 🟢 Create Project -> Invite Notification (Mocked).
        - [x] 🟢 Client Portal Access (GET Requests - Valid, Invalid, Expired, Auth Checks).
        - [x] 🟢 Client Portal Actions (POST Requests - Comments, Approve, Revisions - Success & Validation/Auth Failures).
        - [x] 🟢 Test invalid status for revision request (`client_cannot_request_revisions_in_invalid_status`).
        - [x] 🟢 Test Producer Submit -> Client Review notification flow. (Unit test confirmed logic works, passes on persistent DB).
        - [x] 🟢 Test Producer Complete flow.
        - [x] 🟢 Test Resend Invite functionality.
    - [x] 🟢 Uncomment flash message assertions in feature tests once view is implemented.
- [ ] 🔴 **Payment Flow Definition:**
    - [ ] 🔴 Decide: Option A (Internal Payment) or Option B (External Payment).
    - [ ] 🔴 If Option A: Detail implementation steps (add payment fields, integrate gateway in portal, update InvoiceService, handle payouts).

+- [x] 🟡 **Payment Flow Definition (Option A - Pay on Approval - Chosen):**
    - **Implementation Steps:**
        - [x] 🟢 **Step 1: Update Project Creation Flow:**
            - [x] 🟢 Modify `CreateProject` component: Add conditional `payment_amount` input.
            - [x] 🟢 Modify `ProjectObserver::created`: Save `payment_amount` to the auto-created Pitch, set initial `payment_status`.
        - [x] 🟢 **Step 2: Initiate Checkout in Client Portal (`ClientPortalController::approvePitch`):**
            - [x] 🟢 Check if payment is required (`payment_amount` > 0, status != PAID).
            - [x] 🟢 If yes: Use Cashier (`producer->checkout()`) for one-time charge, pass `pitch_id` in metadata, configure success/cancel URLs.
            - [x] 🟢 If yes: Return Stripe Checkout redirect.
            - [x] 🟢 If no: Call `PitchWorkflowService::clientApprovePitch` directly.
        - [x] 🟢 **Step 3: Handle Stripe Webhook (`Billing\\WebhookController::handleCheckoutSessionCompleted`):**
            - [x] 🟢 Retrieve `pitch_id` from metadata.
            - [x] 🟢 Find `Pitch`.
            - [x] 🟢 Verify idempotency (not already paid/approved).
            - [x] 🟢 Call `PitchWorkflowService::clientApprovePitch`.
            - [x] 🟢 Update `Pitch` `payment_status` to PAID, set `payment_completed_at`.
            - [x] 🟢 Create/Update `Invoice` via `InvoiceService`, link to `Pitch`, mark as paid.
            - [x] 🟢 Return 200 OK.
        - [x] 🟢 **Step 4: Update `PitchWorkflowService::clientApprovePitch`:**
            - [x] 🟢 Ensure method is idempotent.
            - [x] 🟢 Focus on status update, event creation, notification. Remove payment status logic.
        - [x] 🟢 **Step 5: Update UI:**
            - [x] 🟢 Client Portal: Update "Approve" button flow, add redirect feedback.
            - [x] 🟢 Producer Views: Display payment details.
        - [x] 🟢 **Step 6: Testing:**
            - [x] 🟢 Unit tests for Observer, Controller, Webhook handler.
            - [x] 🟢 Feature tests for payment flow.

---

## Phase 6: Service Packages & Order Management
*Goal: Implement service packages and a dedicated order workflow.*

- **Assignee:** TBD
- **Overall Status:** 🟡 In Progress

### Checklist
- [x] 🟢 **Service Package Model & Migrations:**
    - [x] 🟢 Create `service_packages` table migration (user_id, title, slug, description, price, currency, revisions, delivery_time, status, requirements_prompt, is_published). Add indices/foreign keys.
    - [x] 🟢 Create `app/Models/ServicePackage.php`: Add fillable, relationships (user, orders), constants (STATUS_*), helpers (isActive, isPublished), scope (published). Add `sluggable` behavior.
    - [x] 🟢 Run Migration.
- [x] 🟢 **Service Package CRUD (Producer):**
    - [x] 🟢 Create `ServicePackageController` (resourceful).
    - [x] 🟢 Implement `index`, `create`, `store`, `edit`, `update`, `destroy` methods with authorization (Policy).
    - [x] 🟢 Create `ServicePackagePolicy` (viewAny, view, create, update, delete).
    - [x] 🟢 Create Views: `producer.services.index`, `create`, `edit`, `_form`. Use Tailwind/Blade components.
    - [x] 🟢 Define Routes in `routes/web.php` under `producer/services` prefix, using `Route::resource`.
    - [x] 🟢 Add links/navigation for producers to manage services.
- [x] 🟢 **Public Service Package Display:**
    - [x] 🟢 Create `PublicServicePackageController` (`index`, `show`).
    - [x] 🟢 Implement `index`: List published packages (paginated).
    - [x] 🟢 Implement `show`: Display single package details. (Show Route currently commented out).
    - [x] 🟢 Create Views: `public.services.index`, `show`.
    - [x] 🟢 Define Routes in `routes/web.php`.
    - [x] 🟢 Add "Services" link to main navigation.
- [x] 🟢 **Order Model & Migrations:**
    - [x] 🟢 Create `orders` table migration (service_package_id, client_user_id, producer_user_id, invoice_id, status, price, currency, requirements_submitted, revision_count, delivered_at, completed_at, cancelled_at, payment_status). Add indices/foreign keys.
    - [x] 🟢 Create `app/Models/Order.php`: Add fillable, relationships (servicePackage, client, producer, invoice, events, files), constants (STATUS_*, PAYMENT_STATUS_*, EVENT_*), helpers (readable_status, readable_payment_status).
    - [x] 🟢 Create `order_events` table migration (order_id, user_id, event_type, comment, status_from, status_to, metadata). Add indices/foreign keys.
    - [x] 🟢 Create `app/Models/OrderEvent.php`: Add fillable, relationships (order, user). Add `EVENT_*` constants matching Order model.
    - [x] 🟢 Create `order_files` table migration (order_id, uploader_user_id, file_path, file_name, mime_type, size, type [requirement/delivery/revision]). Add indices/foreign keys.
    - [x] 🟢 Create `app/Models/OrderFile.php`: Add fillable, relationships (order, uploader), constants (TYPE_*). Add `formatted_size` accessor.
    - [x] 🟢 Run Migrations.
- [x] 🟢 **Invoice Integration:**
    - [x] 🟢 Create `invoices` table migration (user_id, order_id, stripe_invoice_id, status, amount, currency, due_date, paid_at, pdf_url).
    - [x] 🟢 Create `app/Models/Invoice.php`: Add fillable, relationships (user, order), constants (STATUS_*).
    - [x] 🟢 Create `InvoiceService`: Implement `createInvoiceForOrder`.
    - [x] 🟢 Run Migration.
- [x] 🟢 **Order Placement Flow:**
    - [x] 🟢 Create `OrderController`.
    - [x] 🟢 Implement `OrderController::store`: Validate package, authorize, create `Order` (status PENDING_PAYMENT), call `InvoiceService::createInvoiceForOrder`, update `order->invoice_id`, initiate Stripe Checkout session (using `client->checkout`), include metadata (order_id, invoice_id), set success/cancel URLs. Handle `IncompletePayment`. Handle producer Stripe account check/fee calculation.
    - [x] 🟢 Add route `POST /orders/{package}`.
    - [x] 🟢 Add "Order Now" button to `public.services.show` and `public.services.index`.
    - [x] 🟢 Handle Stripe Webhook (`WebhookController::handleInvoicePaymentSucceeded`): Find Order via invoice_id, update Order `status` to `PENDING_REQUIREMENTS`, update `payment_status` to `PAID`, create `OrderEvent` (PAYMENT_RECEIVED), notify client/producer.
- [x] 🟢 **Order Workflow & Management:**
    - [x] 🟢 Create `OrderPolicy`: Implement `viewAny`, `view`. Initially block `create`, `update`, `delete`. Register policy.
    - [x] 🟢 Implement `OrderController::index`: Fetch orders where user is client OR producer, paginate, return `orders.index` view.
    - [x] 🟢 Implement `OrderController::show`: Authorize view, load relationships, return `orders.show` view.
    - [x] 🟢 Create `orders.index` view: List orders (ID, Service, Role, Status, Total, Date, View Link).
    - [x] 🟢 Create `orders.show` view: Display order details, files, activity log. Add placeholders for workflow actions.
    - [x] 🟢 Create `OrderWorkflowService`.
    - **Submit Requirements (Client):**
        - [x] 🟢 Implement `OrderWorkflowService::submitRequirements`: Authorize client, check status `PENDING_REQUIREMENTS`, DB transaction, update Order `requirements_submitted` & status to `IN_PROGRESS`, create `OrderEvent`, log, (TODO: notify producer). Handle exceptions.
        - [x] 🟢 Add `OrderPolicy::submitRequirements`.
        - [x] 🟢 Add route `POST /orders/{order}/requirements`.
        - [x] 🟢 Implement `OrderController::submitRequirements`: Authorize, validate input, call service, redirect with message/error.
        - [x] 🟢 Update `orders.show` view: Add requirements form (`<textarea>`) visible only if `@can('submitRequirements', $order)`. Display submitted requirements.
    - **Deliver Order (Producer):**
        - [x] 🟢 Implement file uploads for delivery (`OrderFile::TYPE_DELIVERY`). Consider using Livewire or dedicated upload controller. Integrate with `OrderFile` model creation. Secure storage.
        - [x] 🟢 Implement `OrderWorkflowService::deliverOrder`: Authorize producer, check status `IN_PROGRESS` or `REVISIONS_REQUESTED`, DB transaction, update Order status to `READY_FOR_REVIEW`, create `OrderEvent`, associate uploaded files, (TODO: notify client). Handle exceptions.
        - [x] 🟢 Add `OrderPolicy::deliverOrder`.
        - [x] 🟢 Add route `POST /orders/{order}/deliver`.
        - [x] 🟢 Implement `OrderController::deliverOrder`: Authorize, validate input (files, message), call service, redirect.
        - [x] 🟢 Update `orders.show` view: Add delivery form (file upload, message) visible if `@can('deliverOrder', $order)`. Display delivery details/files.
    - **Request Revisions (Client):**
        - [x] 🟢 Implement `OrderWorkflowService::requestRevision`: Authorize client, check status `READY_FOR_REVIEW`, check revision limits, DB transaction, update Order status to `REVISIONS_REQUESTED`, increment `revision_count`, create `OrderEvent` with feedback, (TODO: notify producer). Handle exceptions.
        - [x] 🟢 Add `OrderPolicy::requestRevision`.
        - [x] 🟢 Add route `POST /orders/{order}/request-revision`.
        - [x] 🟢 Implement `OrderController::requestRevision`: Authorize, validate feedback, call service, redirect.
        - [x] 🟢 Update `orders.show` view: Add revision request form (`<textarea>`) visible if `@can('requestRevision', $order)`.
    - **Accept Delivery (Client):**
        - [x] 🟢 Implement `OrderWorkflowService::acceptDelivery`: Authorize client, check status `READY_FOR_REVIEW`, DB transaction, update Order status to `COMPLETED`, set `completed_at`, create `OrderEvent`, (TODO: trigger payout process/notify producer/admin). Handle exceptions.
        - [x] 🟢 Add `OrderPolicy::acceptDelivery`.
        - [x] 🟢 Add route `POST /orders/{order}/accept-delivery`.
        - [x] 🟢 Implement `OrderController::acceptDelivery`: Authorize, call service, redirect.
        - [x] 🟢 Update `orders.show` view: Add "Accept Delivery" button visible if `@can('acceptDelivery', $order)`.
    - [x] 🟢 **Order Cancellation:**
        - [x] 🟢 Define cancellation rules (who, when, refunds?).
        - [x] 🟢 Implement `OrderWorkflowService::cancelOrder`.
        - [x] 🟢 Add `OrderPolicy::cancelOrder`.
        - [x] 🟢 Add route/controller action.
        - [x] 🟢 Add UI element.
    - [x] 🟢 **Order Communication/Messages:**
        - [x] 🟢 Implement simple message posting within `orders.show` (e.g., adding `OrderEvent::EVENT_MESSAGE`).
        - [x] 🟢 Update controller/service/view.
- [x] 🟢 Implement notification classes for order events (placeholders created, need full implementation):
    - [x] 🟢 `OrderRequirementsSubmitted`
    - [x] 🟢 `OrderDelivered`
    - [x] 🟢 `RevisionRequested`
    - [x] 🟢 `OrderCompleted` (Note: Implemented as `DeliveryAccepted`)
    - [x] 🟢 `OrderCancelled`
    - [ ] 🟡 `OrderPaymentConfirmed` (Handled by Webhook)
    - [ ] 🟡 `ProducerOrderReceived` (Handled by Webhook)
    - [x] 🟢 `NewOrderMessage` (Implicitly added for messaging)
- [x] 🟢 Ensure calls to notification methods are integrated within `OrderWorkflowService`.
- [x] 🟢 **Testing:**
    - [x] 🟢 Unit tests for `OrderWorkflowService` methods:
        - [x] 🟢 Test `submitRequirements`: Verify status changes, event creation, error handling
        - [x] 🟢 Test `deliverOrder`: Verify file handling, status changes, event creation
        - [x] 🟢 Test `requestRevision`: Verify revision limit enforcement, status changes
        - [x] 🟢 Test `acceptDelivery`: Verify completion process, date setting
        - [x] 🟢 Test `cancelOrder`: Verify cancellation logic and refund handling
    - [x] 🟢 Unit tests for `OrderPolicy` methods:
        - [x] 🟢 Test authorization for all policy methods across different user roles
        - [x] 🟢 Test order status constraints on policy methods
    - [x] 🟢 Feature tests for the complete order lifecycle:
        - [x] 🟢 Test `OrderWorkflowTest::client_can_place_order_for_service_package`
        - [x] 🟢 Test `OrderWorkflowTest::webhook_handles_successful_payment_for_order`
        - [x] 🟢 Test `OrderWorkflowTest::client_can_submit_requirements`
        - [x] 🟢 Test `OrderWorkflowTest::producer_can_deliver_order`
        - [x] 🟢 Test `OrderWorkflowTest::client_can_request_revision`
        - [x] 🟢 Test `OrderWorkflowTest::client_cannot_exceed_revision_limit`
        - [x] 🟢 Test `OrderWorkflowTest::client_can_accept_delivery`
        - [x] 🟢 Test `OrderWorkflowTest::client_can_download_order_files`
        - [x] 🟢 Test `OrderWorkflowTest::producer_can_download_order_files`
        - [x] 🟢 Test `OrderWorkflowTest::unauthorized_users_cannot_download_files`

---

## Phase 7: Conclusion and Future Considerations
*Goal: Finalize integration, plan next steps, and ensure long-term maintainability and usability.*

- **Assignee:** TBD
- **Overall Status:** 🟡 In Progress

### Checklist
- [ ] 🟡 **Thorough Testing:** Conduct comprehensive end-to-end testing across all project types to ensure stability, correctness, and robustness before full launch.
    - **Testing Plan Structure:** (Ensuring methodical testing)
        - [x] 🟢 **Create Test Matrix:** A spreadsheet or document mapping features/workflows against project types and test environments.
            - [x] 🟢 Generate matrix covering all project types (Standard, Contest, Direct Hire, Client Management, Service Packages).
            - [x] 🟢 Define test environments (Local Dev, Shared Staging, Production Smoke Tests).
            - [x] 🟢 Assign testers (Specific developers for features, QA for E2E, Stakeholders for UAT).
            - [x] 🟢 Establish testing schedule with deadlines.
        - [x] 🟢 **Automated vs. Manual Testing:** Balancing efficiency and coverage.
            - [x] 🟢 Review existing automated tests (Unit, Feature) for coverage gaps identified during development.
            - [x] 🟢 Identify scenarios requiring manual testing (e.g., complex UI interactions, exploratory testing, specific email/notification checks).
            - [x] 🟢 Prioritize automation for critical paths, regression prevention, and repeatable workflows (e.g., payment success, status changes).
            - [x] 🟢 Automate cross-project type scenarios where feasible (e.g., user dashboard displaying mixed items).

    - **Test Scenarios by Project Type:** (Verifying specific workflows)
        - [ ] 🟡 **Standard Project Testing:** Ensure the original core workflow remains solid.
            - [ ] 🟡 **Complete End-to-End Workflow:** Manually simulate or write dedicated feature tests for the full lifecycle as both producer and owner.
                - [x] 🟢 Create project (verify defaults correctly to `standard`). (Verified Phase 2)
                - [x] 🟢 Submit pitch as producer.
                - [x] 🟢 Approve initial pitch as owner.
                - [x] 🟢 Submit for review.
                - [x] 🟢 Test request revisions flow (owner requests, producer resubmits).
                - [x] 🟢 Test resubmission flow (after revisions).
                - [x] 🟢 Approve final submission.
                - [x] 🟢 Complete project (including payout trigger verification).
                - [x] 🟢 Verify notifications and emails at each key stage.
            - [ ] 🟡 **Edge Cases:** Test less common but important scenarios.
                - [x] 🟢 Test denial flows (initial denial, submission denial).
                - [x] 🟢 Test cancellation mid-workflow (owner/producer cancels at different stages).
                - [ ] 🟡 Test with large file uploads (verify size limits; performance/timeouts require manual testing; component validation test skipped - needs review).

        - [ ] 🟡 **Contest Project Testing:** Verify contest-specific logic.
            - [x] 🟢 **Complete End-to-End Workflow:** (Mostly covered by Feature Tests; confirm notification content/delivery).
                - [x] 🟢 Create contest with deadlines and prize.
                - [x] 🟢 Submit multiple contest entries from different producers.
                - [x] 🟢 Verify submission deadline enforcement (cannot submit after deadline).
                - [x] 🟢 Select winner and runner-up (verify status changes, rank assignment).
                - [x] 🟢 Verify other entries marked as not selected.
                - [x] 🟢 Verify prize amount is set for invoice/payment.
                - [ ] 🟡 Verify notifications to winner, runner-up, non-selected, and owner.
            - [ ] 🟡 **Edge Cases:**
                - [ ] 🔴 Test deadline extension (if feature exists or is added).
                - [ ] 🔴 Test contest with no entries (verify owner view, completion/cancellation options).
                - [ ] 🔴 Test winner selection at exact deadline (potential timing issues).
                - [ ] 🔴 Test cancellation mid-contest (before/after entries, refund logic if applicable).

        - [ ] 🟡 **Direct Hire Project Testing:** Verify the streamlined direct assignment flow.
            - [x] 🟢 **Complete End-to-End Workflow:** (Mostly covered by Feature Tests).
                - [x] 🟢 Create direct hire project with target producer.
                - [x] 🟢 Verify automatic pitch creation (status `IN_PROGRESS` or `AWAITING_ACCEPTANCE`).
                - [x] 🟢 Verify notification to producer (assignment or offer).
                - [ ] 🟡 Test standard review cycle (producer submits, owner reviews/approves/requests revisions).
                - [x] 🟢 Complete project (owner completes, payout triggered).
                - [x] 🟢 Verify payment process.
            - [ ] 🟡 **Edge Cases:**
                - [ ] 🔴 Test with non-existent producer ID during creation (validation should prevent this).
                - [x] 🟢 Test project visibility rules (only owner/producer should see).
                - [ ] 🔴 Test cancellation mid-workflow (by owner or producer, consequences?).

        - [ ] 🟡 **Client Management Project Testing:** Verify the secure client portal and workflow.
            - [x] 🟢 **Complete End-to-End Workflow:** (Mostly covered by Feature Tests - ClientPortalTest, ClientPaymentFlowTest).
                - [x] 🟢 Create client management project with client details.
                - [x] 🟢 Verify automatic pitch creation (assigned to producer).
                - [x] 🟢 Verify client invitation email with secure link (content, link validity).
                - [x] 🟢 Test client portal access (logged-out state, valid link).
                - [x] 🟢 Test client commenting functionality.
                - [x] 🟢 Test producer submission workflow -> client notified.
                - [x] 🟢 Test client revision request workflow -> producer notified.
                - [x] 🟢 Test client approval workflow -> producer notified.
                - [x] 🟢 Test client payment flow (Stripe checkout initiated from portal, webhook handled correctly).
                - [x] 🟢 Complete project (producer completes after client approval).
                - [ ] 🟡 Verify notifications throughout lifecycle (invite, review ready, comment, approved, revisions, completion).
            - [ ] 🟡 **Edge Cases:**
                - [x] 🟢 Test expired/invalid client links (should show appropriate error).
                - [x] 🟢 Test resend invite functionality (new link generated, previous invalidated?).
                - [ ] 🟡 Test file upload/download security (can client only download delivery files? Test signed URL security).
                - [ ] 🔴 Test with invalid client email during creation (validation).
                - [x] 🟢 Test client link security (tampering with signature/project ID).

        - [ ] 🟡 **Service Packages Testing:** Verify the distinct order-based workflow.
            - [x] 🟢 **Service Package Management (Producer):** (Likely covered by CRUD/Policy tests).
                - [x] 🟢 Create/Edit/Update service packages.
                - [x] 🟢 Publish/unpublish packages (verify visibility).
                - [ ] 🟡 Test package browsing/search by clients (public view).
            - [x] 🟢 **Order Workflow (Client & Producer):** (Mostly covered by OrderWorkflowTest).
                - [ ] 🟡 Place order as client (verify checkout initiated).
                - [x] 🟢 Test payment flow (webhook updates order status, invoice created/paid).
                - [x] 🟢 Submit requirements (client submits, producer notified).
                - [x] 🟢 Deliver order as producer (upload files, submit, client notified).
                - [x] 🟢 Request revisions (client requests within limits, producer notified).
                - [x] 🟢 Accept delivery (client accepts, order completes, payout triggered).
                - [ ] 🟡 Test messaging system (events logged correctly, visible to both parties).
                - [x] 🟢 Test file upload/download security (client/producer access appropriate files).
                - [ ] 🔴 Test cancellation flows (by client/producer, refund logic).
            - [ ] 🟡 **Edge Cases:**
                - [x] 🟢 Test order with maximum revision limit (cannot request more).
                - [ ] 🔴 Test with very large/small pricing (validation, display formatting).
                - [ ] 🟡 Test order visibility permissions (client/producer see only their orders).

    - [ ] 🔴 **Cross-Project Type Scenarios:** Test interactions and unified views.
        - [ ] 🔴 Test user having multiple project/order types simultaneously (dashboard view correctness).
        - [ ] 🔴 Test dashboard/discovery views with mixed project types (no data bleed, correct filtering).
        - [ ] 🔴 Test notifications aggregation for users with multiple roles/tasks (avoid overwhelming users).
        - [ ] 🔴 Test search functionality across mixed project types (if applicable).

    - [ ] 🔴 **Performance & Security Testing:** Ensure stability and safety under load.
        - [ ] 🔴 Load test key workflows (project creation, pitch submission, order placement, file upload) with multiple concurrent users.
        - [ ] 🔴 Security scan/penetration test for all new endpoints, especially client portal and payment flows.
        - [ ] 🔴 Test file upload security (validation, potential exploits) across all project types.
        - [ ] 🔴 Test payment flow security (preventing duplicate payments, securing webhook endpoints, validating amounts).

- [ ] 🔴 **User Documentation:** Create comprehensive help guides to aid user adoption and reduce support load.
    - [ ] 🔴 **Owner/Client Guides:** Focus on "how-to" for users initiating or receiving work.
        - [ ] 🔴 Guide for Standard Projects (Submitting requirements, Reviewing pitches/submissions, Completing).
        - [ ] 🔴 Guide for Contests (Setting up contests effectively, understanding deadlines, Fair winner selection process, Prize handling).
        - [ ] 🔴 Guide for Direct Hire (How to find and select producers, The direct assignment workflow).
        - [ ] 🔴 Guide for Client Management Portal (Accessing via link, Understanding the interface, Providing feedback/approval).
        - [ ] 🔴 Guide for Service Packages (Finding services, Placing orders, Submitting requirements, Review/revision process).
    - [ ] 🔴 **Producer Guides:** Focus on "how-to" for users performing the work.
        - [ ] 🔴 Guide for Standard Projects (Finding projects, Submitting effective pitches, The review/revision cycle).
        - [ ] 🔴 Guide for Contests (Understanding rules, Submitting entries, What happens after submission).
        - [ ] 🔴 Guide for Direct Hire (Accepting/Rejecting offers - if applicable, Managing the workflow).
        - [ ] 🔴 Guide for Client Management (Setting up projects, Best practices for client communication via portal, Managing delivery).
        - [ ] 🔴 Guide for Service Packages (Creating compelling packages, Managing orders, Delivering work, Handling revisions).
    - [ ] 🔴 **Format & Location:** Decide on format (e.g., searchable knowledge base, step-by-step tutorials) and location (e.g., /help section).

- [ ] 🔴 **User Dashboards & Aggregated Views:** Refactor dashboards for a cohesive, unified user experience across all work types.
    - [x] 🟢 Identify Existing Dashboard Components (`DashboardController`, `dashboard` view).
    - [x] 🟢 Define Data Requirements (Producer vs. Owner/Client views).
    - [x] 🟢 Refactor Backend Data Fetching (`DashboardController::index`) to gather all relevant work types (Projects, Pitches, Orders, Services) based on user role.
    - [x] 🟢 Update Frontend View (`dashboard.blade.php`) to display a unified list of work items, conditionally rendering details based on type.
    - [x] 🟢 Implement client-side or server-side filtering/sorting options on the dashboard.
    - [ ] 🔴 Implement Activity Feed Component (Optional but Recommended) querying `PitchEvent` and `OrderEvent`.
- [ ] 🔴 **Search & Discovery Updates:** Enhance search and browsing capabilities to include the new types effectively.
    - [x] 🟢 Update Project Browsing (`ProjectController::index` or component): Exclude private types (Direct Hire, Client Mgmt) from public view, add filtering by type (Standard, Contest).
    - [x] 🟢 Update Project Browsing UI: Add filter/sort controls.
    - [x] 🟢 Enhance Service Package Marketplace (`PublicServicePackageController::index`): Add filtering (category, price, delivery) and search.
    - [x] 🟢 Update Service Package Marketplace UI: Add filter/search form.
    - [x] 🟢 Update Producer Profile (`UserProfileController::show`): Fetch and display published `ServicePackages` (Done in previous step, verify integration).
    - [x] 🟢 Update Producer Profile UI (`user-profile.show.blade.php`): Add section to display Service Packages (Done in previous step, verify integration).
    - [x] 🟢 Implement `public.services.show` route, controller, and view.
    - [ ] 🔴 Update Global Search (If applicable): Extend logic to include `ServicePackage` results, ensuring permissions are respected.

- [ ] 🔴 **Admin Tools & Oversight MVP:** Implement essential admin capabilities for monitoring, support, and emergency intervention.
    - [ ] 🔴 Admin Views: Create unified backend views for Admins to list/search/view details of all Projects, Pitches, Service Packages, and Orders, regardless of type.
    - [ ] 🔴 Payment/Invoice Oversight: Allow Admins to easily check payment status, find related invoices, and identify/investigate payment failures across Contests, Client Mgmt, and Orders.
    - [ ] 🔴 Dispute Management (Basic): Implement a mechanism for users to flag an Order/Pitch for dispute (e.g., button changing status to `DISPUTED`). Add an Admin view to list disputed items for review. (Full resolution workflow might be post-MVP).
    - [ ] 🔴 Manual Intervention (Critical): Define specific scenarios (e.g., confirmed payment but order creation failed, webhook permanently failing) and provide Admins secure, logged actions to manually adjust status or link related records to resolve critical issues. Avoid overly broad "edit anything" capabilities initially.
    - [ ] 🔴 Admin Alerting: Set up automated monitoring (e.g., using Laravel Telescope, Sentry, or custom checks) to notify Admins of critical system failures (e.g., observer errors, queue failures impacting orders, high rate of payment errors).

- [ ] 🔴 **Review User Roles & Permissions:** Assess if the current Owner/Client vs. Producer structure is sufficient and scalable.
    - [ ] 🔴 Evaluate if the simple distinction covers all necessary authorization scenarios encountered during development.
    - [ ] 🔴 Consider potential future needs (e.g., Team accounts with shared access, Clients needing multiple logins, Admin roles with varying permissions) and determine if the current structure (e.g., basic roles, policies) would require major refactoring later. Document findings.

- [ ] 🔴 **Formalize File Management Strategy:** Define and document storage, backup, security, and cleanup procedures for consistency and cost-effectiveness.
    - [ ] 🔴 Confirm storage solution (e.g., AWS S3 bucket, local disk) and ensure consistent configuration across the app.
    - [ ] 🔴 Define and implement a backup policy for user-uploaded files.
    - [ ] 🔴 Implement server-side security scanning for uploads (e.g., ClamAV) to mitigate malware risks (if required by risk assessment).
    - [ ] 🔴 Develop a strategy/scheduled task for cleaning up orphaned/unused files (e.g., files related to deleted projects/orders, temporary uploads).
    - [ ] 🔴 Ensure consistent and secure access control logic for downloading files associated with `PitchFile` and `OrderFile` (using signed URLs, policy checks).

- [ ] 🔴 **Consider API Strategy:** Design internal services with potential future API usage in mind, even if not building a public API immediately.
    - [ ] 🔴 Review core services (`PitchWorkflowService`, `OrderWorkflowService`, etc.) for clear public methods, well-defined inputs/outputs, and minimal direct dependency on web-specific constructs (like full Request objects where possible). This facilitates future wrapping in API controllers.
    - [ ] 🔴 Maintain separation of concerns (e.g., business logic in services, data transformation in resources, web handling in controllers) to simplify API development later.

- [ ] 🔴 **Plan Onboarding & Feature Discovery:** Develop a strategy to introduce the new project/order types to users smoothly.
    - [ ] 🔴 Implement UI hints (tooltips explaining types in project creation, contextual help icons linking to docs) to guide users.
    - [ ] 🔴 Create in-app announcements (e.g., a dismissible banner) or a "What's New" section highlighting the new capabilities upon first login after launch.
    - [ ] 🔴 Link prominently to the relevant User Documentation sections from within the interface (e.g., from project creation, dashboards).

- [ ] 🔴 **Code Refinement & Consolidation:** Schedule a post-launch technical review to address tech debt and improve maintainability.
    - [ ] 🔴 Review `PitchWorkflowService` and `OrderWorkflowService`: Identify complex conditional logic that might benefit from refactoring (e.g., using state pattern/machines, strategy pattern). Look for duplicated logic that can be extracted.
    - [ ] 🔴 Review Policies (`PitchPolicy`, `OrderPolicy`, etc.): Ensure consistency in how checks are performed and simplify complex authorization rules if possible.
    - [ ] 🔴 Consolidate UI components: Identify repeated Blade or Livewire structures/logic across different views (e.g., file lists, event logs) and extract them into reusable components.
    - [ ] 🔴 Review Notification implementation: Check for consistency, potential for user preferences (channel, frequency), and use of queues for reliability.

- [ ] 🔴 **Setup Monitoring & Feedback:** Implement ongoing checks and establish channels for user input to enable continuous improvement.
    - [ ] 🔴 Implement application performance monitoring (APM - e.g., New Relic, Datadog, Telescope) covering the new controllers, services, and background jobs.
    - [ ] 🔴 Set up robust logging and error tracking (e.g., Sentry, Flare) for new application areas.
    - [ ] 🔴 Establish clear channels for collecting user feedback specifically on the new project types (e.g., feedback form, surveys, support channel analysis).

---

## Notes & Decisions

- [DATE] - Document updated to checklist format based on implementation plan. 

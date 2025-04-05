# MixPitch Refactoring - Next Steps Plan

**Date:** 2024-07-27

## Introduction

This document outlines the immediate next steps for the MixPitch refactoring effort. It builds upon the verification performed against the `REFACTORING_GUIDE.md`, `REFACTORING_PLAN.md`, and `REFACTORING_PROGRESS.md` (phases 1-6). The primary goal is to address the identified deviations and fully align the codebase with the intended "thin controller, focused model, fat service" architecture for the core Project and Pitch features.

Completing these steps will improve maintainability, reduce complexity in models and controllers, strengthen authorization enforcement, and ensure the codebase is consistent with the planned design.

## Detailed Next Steps

### Step 1: Refactor `Pitch` Model (`app/Models/Pitch.php`)

*   **Objective:** Ensure the `Pitch` model strictly adheres to its intended role of data representation and relationships, removing business workflow and complex validation logic.
*   **Rationale:** The verification found significant business logic remaining in the model, contradicting the refactoring plan.
*   **Actions:**
    1.  **Identify & Remove Status Validation Methods:** Locate and remove methods like `canApprove`, `canDeny`, `canRequestRevisions`, `canComplete`, `canSubmitForReview`, `canCancelSubmission`.
        *   *Responsibility Transfer:* Authorization logic belongs in `PitchPolicy.php`. Pre-condition checks (e.g., "is status X?") can occur in the Service layer *before* attempting an operation.
    2.  **Identify & Remove Workflow Methods:** Locate and remove methods containing core workflow logic, such as `completePitch` (logic belongs in `PitchCompletionService`), `changeSnapshotStatus`, `changeStatus` (logic belongs in `PitchWorkflowService`), and `deleteSnapshot` (logic belongs in `PitchWorkflowService` or potentially `FileManagementService` if tightly coupled with file deletion).
    3.  **Review Remaining Methods:** Scrutinize all other methods. Ensure they are simple data accessors/mutators (e.g., `readableStatusAttribute`), relationship definitions (`user`, `project`, `files`, `snapshots`, `currentSnapshot`, `events`), query scopes, or basic helpers directly tied to the model's data (e.g., `isOwner`, storage checks like `hasStorageCapacity`, `incrementStorageUsed`, `decrementStorageUsed`).
    4.  **Verify Core Properties:** Ensure constants (statuses, payment statuses, limits), `$fillable`, `$dates`, `$casts`, `$attributes`, and `sluggable` configuration remain correct.

### Step 2: Refactor `PitchController` (`app/Http/Controllers/PitchController.php`)

*   **Objective:** Ensure the `PitchController` is "thin" and only handles HTTP request/response concerns, delegating all business logic.
*   **Rationale:** Verification showed the controller still contains workflow methods that belong in services.
*   **Actions:**
    1.  **Identify & Remove Workflow Logic:** Locate methods like `submitRevisions`, `updateStatus`, `changeStatus`, `changePitchStatus` (and any similar methods handling business processes). Remove the core logic from these controller methods.
    2.  **Ensure Service Methods Exist:** Verify that corresponding methods to handle the removed logic exist (or create them) in `PitchWorkflowService.php`.
    3.  **Refactor Controller Actions:** Update the controller methods (if they need to remain for non-Livewire routes):
        *   Implement authorization checks using Policies (e.g., `$this->authorize(...)`) or rely on Form Request authorization.
        *   Use Form Requests for validation.
        *   Call the appropriate method in `PitchWorkflowService` (or other relevant service).
        *   Handle exceptions returned by the service.
        *   Return the appropriate HTTP response (redirect, view, JSON).
    4.  **Verify Dependency Injection:** Ensure `PitchWorkflowService` (and others like `PitchService` if its role is distinct and necessary) are correctly injected.

### Step 3: Verify `ProjectManagementService`

*   **Objective:** Confirm the existence, location, naming, and content alignment of the `ProjectManagementService`.
*   **Rationale:** The service file was not found in the expected `app/Services` root during the initial directory listing.
*   **Actions:**
    1.  **Locate File:** Search for `ProjectManagementService.php` within `app/Services/` and its subdirectories (e.g., `app/Services/Project/`).
    2.  **Verify Name/Content:**
        *   If found but named differently (e.g., `ProjectService.php`), review its public methods (`createProject`, `updateProject`, `publishProject`, `unpublishProject`, `completeProject`) against the definitions in `REFACTORING_GUIDE.md` Step 2.A. If it serves the same purpose, consider renaming it to `ProjectManagementService.php` for consistency with the documentation.
        *   If it exists but its content doesn't match the guide, update it.
        *   If it's genuinely missing, create `app/Services/ProjectManagementService.php` based on the guide.
    3.  **Confirm Usage:** Ensure `ProjectController` and relevant Livewire components (e.g., `CreateProject`, `ManageProject`) are correctly injecting and using this service (with the correct name/namespace).

### Step 4: Review and Implement Policies

*   **Objective:** Ensure comprehensive and correct authorization using Laravel Policies for Pitch and File operations.
*   **Rationale:** Policy implementation is critical for security and centralizing authorization logic removed from models/controllers. The absence of `PitchFilePolicy` needs addressing.
*   **Actions:**
    1.  **Review `PitchPolicy.php`:**
        *   Verify the existence and correctness of *all* required policy methods: `view`, `createPitch`, `update`, `approveInitial`, `approveSubmission`, `denySubmission`, `requestRevisions`, `cancelSubmission`, `submitForReview`, `complete`.
        *   Verify the existence and correctness of file-related methods: `uploadFile`, `deleteFile`, `downloadFile` (assuming they reside here if `PitchFilePolicy` is not created).
        *   Ensure the logic within each method accurately checks user roles (project owner vs. pitch creator), pitch status, project status, and any other relevant conditions.
    2.  **Create/Implement `PitchFilePolicy.php` (Recommended):**
        *   Create `app/Policies/PitchFilePolicy.php`.
        *   Implement authorization logic for `view`, `uploadFile`, `deleteFile`, `downloadFile` specific to `PitchFile` context (checking ownership via pitch, pitch status, project owner access, etc.).
        *   Register the policy in `AuthServiceProvider.php`.
        *   Update `FileManagementService` and relevant Livewire components/controllers to use `$this->authorize(...)` with the `PitchFile` model and policy. This provides better separation of concerns for file permissions.
    3.  **Review `ProjectPolicy.php`:** Briefly re-confirm methods like `update`, `delete`, `publish`, `unpublish`, `uploadFile`, `deleteFile`, `downloadFile` exist and are correct.

### Step 5: Review Key Livewire Components

*   **Objective:** Ensure Livewire components are lean, handling UI state and user interaction while delegating business logic to services.
*   **Rationale:** With models and controllers still containing some logic, Livewire components might be doing the same.
*   **Actions:** Perform a code review of:
    1.  **`ManagePitch.php`:** Check methods handling pitch details updates (if any), cancellation (`cancelSubmission`), review submission (`submitForReview`), and file operations (`uploadFiles`, `deletePitchFile`, `getDownloadUrl`). Ensure logic delegates to `PitchWorkflowService` and `FileManagementService` after performing authorization checks (`$this->authorize(...)`). Remove direct model state manipulation or complex validation. Verify exception handling and user feedback (Toaster).
    2.  **`UpdatePitchStatus.php`:** Check methods handling status changes (`approveSnapshot`, `denySnapshot`, `requestRevisions`). Ensure they authorize, call `PitchWorkflowService`, handle exceptions, update UI state/feedback correctly.
    3.  **`CompletePitch.php`:** Check the `completePitch` method. Ensure it authorizes, calls `PitchCompletionService::completePitch`, handles exceptions, dispatches events (especially `openPaymentModal`), and manages UI state.

### Step 6: Verify Payment Processing Implementation

*   **Objective:** Confirm the end-to-end payment flow aligns with the refactored service-oriented approach (Guide Step 8).
*   **Rationale:** This involves interactions between multiple services, controllers, and external APIs (Stripe).
*   **Actions:** Perform a code review of:
    1.  **`InvoiceService.php`:** Verify Stripe exception handling and transaction usage (if applicable for local DB updates). Check metadata consistency (`pitch_id`).
    2.  **`PitchWorkflowService.php`:** Confirm `markPitchAsPaid` and `markPitchPaymentFailed` methods correctly update the `Pitch` model state, log events, and trigger notifications (`NotificationService`).
    3.  **`PitchPaymentController.php`:** Ensure it uses Form Requests, correctly calls `InvoiceService` methods (`createPitchInvoice`, `processInvoicePayment`), handles success/failure by calling the appropriate `PitchWorkflowService` methods (`markPitchAsPaid`/`markPitchPaymentFailed`), and returns user-friendly responses.
    4.  **`WebhookController` (e.g., `app/Http/Controllers/Billing/WebhookController.php`):** Verify that the `handleInvoicePaymentSucceeded` and `handleInvoicePaymentFailed` methods correctly parse Stripe payloads, extract `pitch_id` from metadata, fetch the `Pitch`, call the corresponding `PitchWorkflowService` methods, handle potential errors gracefully, and manage idempotency (don't re-process already handled events).

### Step 7: Review and Update Tests

*   **Objective:** Ensure the existing test suite accurately validates the *target* refactored architecture.
*   **Rationale:** Tests might be passing against the current, partially refactored state. They need to be updated to reflect the intended logic placement (in services/policies).
*   **Actions:**
    1.  **Review Unit Tests:** Check tests for `PitchWorkflowService`, `PitchCompletionService`, `FileManagementService`, etc. Ensure they correctly mock dependencies (like the `Pitch` model, now simpler) and test the service logic thoroughly.
    2.  **Review Feature Tests:** Check tests like `PitchCreationTest`, `PitchStatusUpdateTest`, `FileManagementTest`, `PitchSubmissionTest`, `PitchCompletionTest`, `PaymentProcessingTest`. Ensure they interact with the refactored (thin) controllers and Livewire components. Assertions should focus on the outcome of service layer operations (database state changes, events dispatched, notifications sent, redirects) rather than internal controller/model logic.
    3.  **Review/Add Policy Tests (`PitchPolicyTest`):** Ensure tests cover all policy methods defined/verified in Step 4, testing various user roles and resource states. Add tests for `PitchFilePolicy` if created.
    4.  **Run Full Suite:** Execute `php artisan test` after completing the refactoring steps to catch any regressions.

## Conclusion

Executing these steps will bring the Project and Pitch features significantly closer to the architecture defined in the refactoring plan. This involves shifting logic from Models (`Pitch`) and Controllers (`PitchController`) into Services and Policies, verifying the `ProjectManagementService`, reviewing Livewire components, confirming the payment flow, and ensuring the test suite accurately reflects these changes. This focused effort will yield a more maintainable, robust, and secure codebase. 
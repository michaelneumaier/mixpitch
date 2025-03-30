# MixPitch Project & Pitch Refactoring Plan

This document outlines a comprehensive refactoring plan for the Project and Pitch features of the MixPitch application. The goal is to improve code structure, enhance security, ensure UI consistency, address potential edge cases, and follow best practices.

## Table of Contents

1.  [High-Level Goals](#high-level-goals)
2.  [Code Structure & Organization](#code-structure--organization)
    *   [Models](#models)
    *   [Controllers](#controllers)
    *   [Services](#services)
    *   [Livewire Components](#livewire-components)
    *   [Validation](#validation)
    *   [State Management](#state-management)
3.  [Security Enhancements](#security-enhancements)
    *   [Authorization](#authorization)
    *   [File Uploads](#file-uploads)
    *   [Input Sanitization](#input-sanitization)
4.  [UI/UX Consistency & Improvements](#uiux-consistency--improvements)
    *   [Component Unification](#component-unification)
    *   [Status Indicators & Actions](#status-indicators--actions)
    *   [Error Handling & Feedback](#error-handling--feedback)
    *   [File Management UI](#file-management-ui)
    *   [Responsive Design](#responsive-design)
5.  [Edge Case Handling](#edge-case-handling)
    *   [Race Conditions](#race-conditions)
    *   [Storage Limits](#storage-limits)
    *   [Payment Failures](#payment-failures)
    *   [Concurrent Actions](#concurrent-actions)
6.  [Testing](#testing)
    *   [Unit Tests](#unit-tests)
    *   [Feature Tests](#feature-tests)
    *   [Browser Tests](#browser-tests)
7.  [Database & Performance](#database--performance)
    *   [Query Optimization](#query-optimization)
    *   [Indexing](#indexing)
    *   [Background Jobs](#background-jobs)
8.  [Documentation](#documentation)

---

## 1. High-Level Goals

*   **Improve Maintainability:** Make the codebase easier to understand, modify, and extend.
*   **Enhance Robustness:** Reduce bugs and handle errors gracefully.
*   **Strengthen Security:** Protect against common web vulnerabilities.
*   **Standardize UI/UX:** Provide a consistent and intuitive user experience across Project and Pitch features.
*   **Optimize Performance:** Ensure efficient database queries and component loading.

---

## 2. Code Structure & Organization

**Goal:** Implement a clean, maintainable, and scalable architecture by clearly defining the responsibilities of Controllers, Services, and Models, ensuring they work synergistically.

**Core Principles:**

*   **Thin Controllers:** Controllers should be lean, focusing solely on HTTP-related tasks.
*   **Fat Services:** Service classes will encapsulate the core business logic and orchestrate interactions.
*   **Focused Models:** Models represent data and relationships, containing minimal business logic directly tied to data representation or retrieval.
*   **Clear Data Flow:** Utilize Form Requests and potentially Data Transfer Objects (DTOs) for structured data transfer.
*   **Robust Error Handling:** Implement consistent exception handling across layers.
*   **Atomic Operations:** Ensure data integrity using database transactions managed by the Service layer.

### Detailed Refactoring Guide: Models, Controllers & Services Synergy

This section provides a more granular approach to refactoring these core layers:

**A. Controller Layer (`app/Http/Controllers/`)**

1.  **Primary Responsibilities:**
    *   Receive HTTP requests.
    *   Parse request data (route parameters, query strings, request body).
    *   Trigger validation and authorization using Form Requests.
    *   Delegate business logic execution to appropriate Service classes by calling service methods.
    *   Receive results (or exceptions) from the Service layer.
    *   Format the HTTP response (e.g., return a view, JSON response, redirect).
2.  **Refactoring Steps:**
    *   **Identify Business Logic:** Go through each method in `ProjectController`, `PitchController`, etc. Identify any code that is *not* directly related to handling the HTTP request/response (e.g., complex status update logic, file processing steps, notification dispatching, multi-model interactions).
    *   **Extract to Services:** Move the identified business logic into new or existing Service classes (e.g., `ProjectManagementService`, `PitchWorkflowService`, `FileManagementService`). The controller method will then become much simpler: validate -> call service -> return response.
    *   **Implement Form Requests:** Create specific Form Request classes (e.g., `StoreProjectRequest`, `UpdatePitchStatusRequest`, `UploadPitchFileRequest`) for each controller action that involves data submission or modification.
        *   Define validation rules within the `rules()` method.
        *   Define authorization logic within the `authorize()` method (checking ownership via Policies).
        *   Type-hint these Form Requests in controller methods for automatic validation and authorization before the method body executes.
    *   **Dependency Injection:** Inject necessary Service classes into controller constructors or methods.
    *   **Example (Conceptual `PitchController::store`)**:
        ```php
        // Before Refactor (Simplified)
        public function store(Request $request, Project $project) {
            $request->validate([...]); // Validation in controller
            if (!auth()->user()->can('createPitch', $project)) { // Auth in controller
                 abort(403);
            }
            // ... Complex logic to check limits, create pitch, set status, create event ...
            $pitch = new Pitch([...]);
            // ... more logic ...
            $pitch->save();
            // ... create event ...
            // ... dispatch notification ...
            return redirect(...);
        }

        // After Refactor
        use App\\Http\\Requests\\CreatePitchRequest;
        use App\\Services\\PitchWorkflowService;

        public function store(CreatePitchRequest $request, Project $project, PitchWorkflowService $pitchWorkflowService) {
            // Validation & Auth handled by CreatePitchRequest

            try {
                $pitch = $pitchWorkflowService->createPitch(
                    $project,
                    auth()->user(),
                    $request->validated() // Pass validated data (or potentially a DTO)
                );
                return redirect()->route('projects.pitches.show', [$project, $pitch])
                                 ->with('success', 'Pitch created...');
            } catch (\\App\\Exceptions\\PitchCreationException $e) {
                // Service throws specific exception
                return back()->with('error', $e->getMessage())->withInput();
            } catch (\\Exception $e) {
                Log::error('Pitch creation failed unexpectedly', ['error' => $e]);
                return back()->with('error', 'An unexpected error occurred.')->withInput();
            }
        }
        ```

**B. Service Layer (`app/Services/`)**

1.  **Primary Responsibilities:**
    *   Encapsulate core application business logic and workflows (e.g., completing a pitch, processing a payment, handling status transitions).
    *   Orchestrate interactions between different Models.
    *   Manage database transactions for operations spanning multiple steps or models.
    *   Dispatch events and queue jobs.
    *   Perform complex calculations or data transformations.
    *   Interact with external APIs (potentially via dedicated client classes injected into the service).
    *   Throw specific exceptions for business rule violations or failures.
2.  **Refactoring Steps:**
    *   **Create Domain Services:** Define services based on application domains (e.g., `PitchWorkflowService`, `ProjectManagementService`, `FileManagementService`, `InvoiceService`, `NotificationService`).
    *   **Implement Business Logic:** Implement the logic extracted from controllers. Methods should be well-defined, accepting necessary data (primitive types, DTOs, or specific Models) and returning results or throwing exceptions.
    *   **Model Interaction:** Services interact with Models to fetch and persist data. Use Eloquent methods, query scopes, etc.
    *   **Transaction Management:** Wrap any operation that modifies multiple database records or involves critical state changes in `DB::transaction(function () { ... });` to ensure atomicity. This is crucial for actions like `CompletePitch`.
    *   **Dependency Injection:** Inject necessary Models (though often fetched within methods), other Services, or utility classes.
    *   **Return Values:** Return meaningful results. This could be the updated Model, a boolean success flag, a DTO, or void if the operation is command-based. Avoid returning raw responses from external services directly; process them first.
    *   **Custom Exceptions:** Define custom, specific exceptions (e.g., `InsufficientStorageException`, `InvalidPitchStatusTransitionException`, `PaymentProcessingFailedException`) and throw them when errors occur. This allows controllers to catch specific errors and provide tailored user feedback.

**C. Model Layer (`app/Models/`)**

1.  **Primary Responsibilities:**
    *   Represent database tables and their relationships (using Eloquent relationships: `belongsTo`, `hasMany`, etc.).
    *   Define data structure (`$fillable`, `$casts`, `$dates`).
    *   Define simple data accessors and mutators (`Attribute` syntax in newer Laravel versions).
    *   Define query scopes for reusable query constraints (e.g., `scopeOpen($query)`, `scopeCompleted($query)`).
    *   Handle simple, model-specific validation logic if tightly coupled to the data itself (e.g., a method like `hasAvailableStorage()` could remain, but the *orchestration* of checking it during an upload belongs in a Service).
    *   Define constants for statuses or types.
2.  **Refactoring Steps:**
    *   **Remove Business Logic:** Scrutinize models like `Project` and `Pitch`. Move complex workflow logic (like the multi-step process in `syncStatusWithPitches` or the validation checks like `canApprove` that depend on external state or multiple models) into appropriate Service classes.
    *   **Focus on Data:** Ensure models primarily focus on representing data and relationships.
    *   **Leverage Eloquent Features:** Use `$casts` for data type handling, accessors/mutators for simple data formatting, and query scopes to keep data retrieval logic clean and reusable in Services.
    *   **Consolidate Status/Type Definitions:** Keep status constants (`STATUS_OPEN`, `PAYMENT_STATUS_PAID`, etc.) clearly defined in the relevant models.
    *   **Events:** Use Eloquent model events (`creating`, `updating`, `deleting`) for simple, atomic actions directly related to the model's lifecycle (e.g., clearing a cache, setting a default value), but avoid complex logic or external interactions here – use Observers or Service-level event dispatching for those.

**D. Data Transfer Objects (DTOs) - Optional but Recommended**

*   **Purpose:** For complex operations, consider using DTOs to pass structured data between layers (e.g., from Form Request to Service, from Service method arguments).
*   **Benefits:** Improves type safety, makes method signatures clearer, decouples layers (Services don't necessarily need the full `Request` object), and simplifies testing. Libraries like `spatie/laravel-data` can facilitate this.
*   **Example:** Instead of `$pitchWorkflowService->createPitch(..., $request->validated())`, you could have `$pitchWorkflowService->createPitch(..., CreatePitchData::fromRequest($request))`.

**E. Error Handling & Exceptions**

*   **Service Exceptions:** Services should throw specific, custom exceptions for business rule failures.
*   **Controller Catching:** Controllers catch these specific exceptions (and generic `\\Exception`) to return appropriate HTTP responses/user feedback.
*   **Global Handler:** Laravel's global exception handler (`app/Exceptions/Handler.php`) can be used to catch unhandled exceptions, log them, and return standardized error responses (e.g., for API routes).

By applying these principles, the Models, Services, and Controllers will have clearly defined roles, leading to a more organized, testable, secure, and maintainable codebase where each layer complements the others effectively.

### Models (`app/Models/`)

*   **`Project.php`:**
    *   **Status Logic:** The `syncStatusWithPitches` logic seems complex and potentially inefficient, iterating over all pitches. Consider using database triggers or events to update the project status incrementally when a pitch status changes, or optimize the query.
    *   **Storage Calculation:** Refactor storage calculation logic (`hasStorageCapacity`, `updateStorageUsed`) for clarity and potentially move to a dedicated service or trait if shared with `Pitch`. Ensure calculations are atomic, especially if uploads can happen concurrently.
    *   **Publish/Unpublish:** The logic seems sound, but ensure events are fired for these actions if needed elsewhere (e.g., notifications).
*   **`Pitch.php`:**
    *   **Status Transitions:** The defined transitions (`$transitions`) are a good start, but the validation logic (`canApprove`, `canDeny`, etc.) seems spread between the model and potentially controllers/Livewire components. Consolidate all transition validation logic strictly within the `Pitch` model using dedicated methods or potentially a state machine library (like `laravel-state-machine` if not already fully utilized) for better enforcement and clarity.
    *   **Snapshot Management:** Methods like `createSnapshot` should be robust, ensuring atomicity and correct versioning. Consider moving complex snapshot logic to `PitchSnapshot` or a dedicated `SnapshotService`.
    *   **Payment Status:** Ensure payment status updates are tightly controlled and transactional, likely handled via the `InvoiceService` callbacks or events.
    *   **File Management Logic:** Move file count/size limits and validation logic (`canManageFiles`, `isFileSizeAllowed`, `hasStorageCapacity`) into the model or a shared trait/service to centralize these rules.
*   **`PitchSnapshot.php`:**
    *   Enhance this model to potentially hold more logic related to snapshot state transitions (pending, accepted, denied, etc.) if applicable, rather than just being a data container.
*   **`PitchFile.php` / `ProjectFile.php`:**
    *   Ensure consistent handling of file storage (S3 paths, temporary URLs) and security (permissions, access control). Consider a unified `File` model or trait if logic is heavily duplicated.

### Controllers (`app/Http/Controllers/`)

*   **Fat Controllers:** `ProjectController` and `PitchController` appear quite large.
    *   **Refactor to Services:** Extract business logic (e.g., pitch creation validation, status transition orchestration, file processing, notification triggering) into dedicated service classes (e.g., `ProjectManagementService`, `PitchWorkflowService`, `FileManagementService`). Controllers should primarily handle HTTP request/response concerns, input validation (using Form Requests), authorization, and delegating to services.
    *   **Form Requests:** Implement dedicated Form Requests (e.g., `StoreProjectRequest`, `UpdatePitchStatusRequest`, `CreatePitchRequest`) to handle validation and authorization logic, cleaning up the controller methods.
*   **`PitchStatusController.php`:** This seems redundant if status logic is primarily handled within Livewire components (`UpdatePitchStatus`). Analyze its necessity. If it handles specific API endpoints or non-Livewire interactions, keep it, but ensure logic isn't duplicated. Otherwise, consider removing it and consolidating logic into Livewire components and services.
*   **Consistency:** Ensure consistent use of route model binding, authorization checks (`$this->authorize`), and response types (redirects, JSON).

### Services (`app/Services/`)

*   **`InvoiceService.php`:** Seems well-defined for handling Stripe interactions. Ensure error handling is robust and transactions are used where necessary (e.g., linking invoice creation to pitch completion).
*   **`NotificationService.php`:** Appears large. Review if it can be broken down by notification type or domain (e.g., `PitchNotificationService`, `ProjectNotificationService`). Ensure notifications are queued for performance.
*   **New Services:** Introduce new services as mentioned above (`ProjectManagementService`, `PitchWorkflowService`, `FileManagementService`, `SnapshotService`) to encapsulate specific domains of business logic.

### Livewire Components (`app/Livewire/`)

*   **`ManagePitch.php`:** This component is very large and handles multiple concerns (file uploads, status display, event listing, snapshot listing).
    *   **Decomposition:** Break it down into smaller, nested components:
        *   `PitchDetails` (displaying core pitch info)
        *   `PitchFileManager` (handling file uploads, listing, deletion - replacing the non-existent `PitchFiles`)
        *   `PitchTimeline` (displaying events/history)
        *   `PitchSnapshotList` (displaying snapshots)
    *   **File Upload Logic:** Extract the complex sequential file upload logic (queueing, progress tracking, individual uploads) into a reusable trait or a dedicated Livewire component/JavaScript controller for better separation and potential reuse. Ensure robust error handling for failed uploads.
*   **`UpdatePitchStatus.php`:** Handles status changes initiated by the project owner. Ensure all necessary authorization and validation checks (`canApprove`, `canDeny` etc.) are performed by calling the consolidated logic in the `Pitch` model or `PitchWorkflowService` before attempting the change. Use transactions.
*   **`CompletePitch.php`:** Handles the multi-step completion process. Ensure atomicity using database transactions covering pitch status update, other pitch closing, project status update, and potentially invoice creation trigger. Refactor the steps (`validateCompletionRequirements`, `markAsCompleted`, `closeOtherPitches`, etc.) into a dedicated `PitchCompletionService` called by the Livewire component.
*   **State Synchronization:** Ensure Livewire components correctly refresh or react to state changes triggered elsewhere (e.g., background jobs updating file processing status, notifications). Use Livewire's event system (`$dispatch`, `$listen`) effectively.

### Validation

*   **Centralize Rules:** Define validation rules primarily in Form Requests for controller actions and within Livewire components (using `$rules` property or methods) for component actions. Avoid duplicating validation logic across controllers and components.
*   **Custom Rules:** Create custom validation rules for complex scenarios (e.g., `EnsureSufficientStorageRule`, `ValidPitchStatusTransitionRule`).

### State Management

*   **Single Source of Truth:** Models (`Project`, `Pitch`, `PitchSnapshot`) should be the single source of truth for status.
*   **Transitions:** Enforce status transitions rigorously, ideally through the model or a dedicated state machine implementation. Log all state transitions for auditing.

---

## 3. Security Enhancements

### Authorization

*   **Policies:** Ensure comprehensive use of Laravel Policies (`ProjectPolicy`, `PitchPolicy`, `PitchFilePolicy`, `SnapshotPolicy`) for all actions (view, create, update, delete, status changes, file access). Apply policies consistently in Controllers (via `$this->authorize` or middleware) and Livewire components (via `authorize()` method calls).
*   **Ownership & Roles:** Double-check all authorization logic correctly verifies ownership (project owner, pitch creator) and potentially roles if applicable. Pay close attention to actions in `ManagePitch`, `UpdatePitchStatus`, and `CompletePitch`.
*   **Route/Model Binding:** Leverage route model binding with implicit policy checks where possible.

### File Uploads

*   **Validation:** Rigorously validate file types (MIME types, not just extensions), size (both individual and total project/pitch limits), and potentially scan for malware before storing. Use Laravel's built-in validation rules (`file`, `mimes`, `max`).
*   **Storage:** Store user-uploaded files in non-public directories (S3 seems correctly used). Never trust the client-provided filename; generate unique filenames.
*   **Access Control:** Use signed URLs (like the temporary S3 URLs already implemented) with short expiry times for accessing private files (`PitchFile`, `ProjectFile`). Ensure the controller generating these URLs performs authorization checks.
*   **Rate Limiting:** Consider rate limiting file uploads to prevent abuse.

### Input Sanitization

*   **Cross-Site Scripting (XSS):** Ensure all user-provided input displayed in views (descriptions, comments, feedback, filenames) is properly escaped using Blade's `{{ }}` syntax or equivalent functions. Be extra careful with `wire:model` data if rendered directly without escaping.
*   **SQL Injection:** Use Eloquent and parameterized queries (which Laravel does by default) to prevent SQL injection. Avoid raw SQL queries with user input.

---

## 4. UI/UX Consistency & Improvements

### Component Unification

*   **Design System:** Establish or enforce a consistent design system using Tailwind CSS utility classes and potentially Blade/Livewire component abstractions for common elements (buttons, modals, cards, forms, status badges).
*   **Layouts:** Use consistent page layouts (`layouts.app` or similar) across all project and pitch views.
*   **`ManageProject.php` vs `pitches/show.blade.php`:** The workflow mentions project owners are redirected to `ManageProject` while pitch owners view `pitches/show` (likely powered by `ManagePitch`). Ensure the UI elements and information displayed are consistent where appropriate, while tailoring actions to the user role.

### Status Indicators & Actions

*   **Clarity:** Use clear and consistent visual indicators (badges, colors, icons) for `Project` and `Pitch` statuses across the application (dashboards, project lists, pitch views).
*   **Action Buttons:** Display action buttons (Approve, Deny, Request Revisions, Submit, Complete, Upload) conditionally based on the current status *and* user authorization. Disable buttons for actions that are not currently possible. Provide tooltips explaining why an action might be disabled.

### Error Handling & Feedback

*   **User-Friendly Messages:** Replace generic error messages with specific, user-friendly feedback. Use toaster notifications (`Masmerise\Toaster`) consistently for success, error, warning, and info messages.
*   **Validation Errors:** Display validation errors clearly next to the relevant form fields in Livewire components and standard forms.
*   **Loading States:** Implement clear loading indicators (spinners, disabled buttons) in Livewire components during background operations (file uploads, status changes, data loading) to provide feedback to the user.

### File Management UI

*   **Refine `ManagePitch`:** Improve the file upload experience:
    *   Clear progress indicators for individual files and the overall batch.
    *   Ability to cancel uploads in progress.
    *   Clear error messages for failed uploads (size limits, type errors, server errors).
    *   Consistent display of existing files with actions (download, delete - if allowed by status/permissions).
    *   Visual representation of storage usage (percentage bar).
*   **Preview:** Enhance audio file previews (`PitchFilePlayer`) for usability.

### Responsive Design

*   Ensure all project and pitch management views are fully responsive and usable on various screen sizes, leveraging Tailwind's responsive utilities.

---

## 5. Edge Case Handling

### Race Conditions

*   **Status Changes:** Multiple users (e.g., project owner approving while producer cancels submission) could interact concurrently. Use database locking (optimistic or pessimistic) or atomic operations when changing statuses or critical data (e.g., `CompletePitch` process).
*   **File Uploads & Storage:** Multiple file uploads happening concurrently could lead to incorrect storage limit calculations if not handled atomically. Use database transactions or atomic increments for storage usage counters.

### Storage Limits

*   Ensure checks against `MAX_STORAGE_BYTES` (Project & Pitch) and `MAX_FILE_SIZE_BYTES` are performed *before* attempting the upload and storage calculation updates are atomic. Provide clear feedback when limits are exceeded.

### Payment Failures

*   The `InvoiceService` needs robust handling for failed Stripe payments (`processInvoicePayment`). Ensure the `Pitch` payment status is correctly updated to `failed` and the user is clearly notified. Define the process for retrying payments. What happens if `completePitch` succeeds but the subsequent payment fails? The pitch is completed but unpaid. Define this flow.

### Concurrent Actions

*   **Completing a Pitch:** What happens if the project owner tries to complete Pitch A while simultaneously approving Pitch B for the same project? The `CompletePitch` logic closes other pitches. Ensure this process is atomic and handles potential conflicts gracefully. Database transactions are crucial here.

---

## 6. Testing

### Unit Tests

*   Write unit tests for:
    *   Model methods (status transitions, validation logic, calculations).
    *   Service class logic (invoice creation, notification logic, workflow steps).
    *   Custom Validation Rules.
    *   Helper functions.

### Feature Tests

*   Write feature tests covering the entire lifecycle of Projects and Pitches:
    *   Project creation, publishing, completion.
    *   Pitch application, approval, submission, review cycles (approve, deny, revisions).
    *   File uploads and downloads with permission checks.
    *   Pitch completion and payment flow (including failures).
    *   Authorization checks for all key actions.
    *   Edge cases (storage limits, concurrent actions - if possible to simulate).

### Browser Tests (Laravel Dusk)

*   Consider adding Dusk tests for critical Livewire interactions:
    *   File upload process in `ManagePitch`.
    *   Status update confirmations in `UpdatePitchStatus`.
    *   Completion modal flow in `CompletePitch`.

---

## 7. Database & Performance

### Query Optimization

*   **N+1 Problem:** Review relationships and loops (e.g., `Project::syncStatusWithPitches`, rendering lists of projects/pitches) for potential N+1 query problems. Use eager loading (`with()`) extensively.
*   **Complex Queries:** Analyze complex queries generated by Eloquent, especially those involving multiple joins or conditions. Optimize if necessary.

### Indexing

*   Ensure database columns frequently used in `WHERE` clauses, `JOIN` conditions, or `ORDER BY` statements are properly indexed (e.g., `projects.status`, `pitches.status`, `pitches.project_id`, `pitches.user_id`, `pitch_files.pitch_id`, `pitch_snapshots.pitch_id`, `users.stripe_id`).

### Background Jobs

*   Use Laravel Queues for time-consuming tasks:
    *   Sending notifications (`NotificationService`).
    *   Processing uploaded files (e.g., `GenerateAudioWaveform` job, potentially malware scanning).
    *   Generating downloadable ZIP archives (if applicable).

---

## 8. Documentation

*   **Update `WORKFLOW.md`:** Keep the workflow documentation (`WORKFLOW.md`) updated to reflect any changes made during the refactoring process.
*   **Code Comments:** Add clear PHPDoc blocks and inline comments where necessary to explain complex logic, especially within refactored services and models.
*   **README:** Update the main project README with any setup changes or new dependencies.

---

This plan provides a structured approach. It should be implemented incrementally, focusing on the highest impact areas first (e.g., security, controller refactoring, critical Livewire components). Each step should include thorough testing.




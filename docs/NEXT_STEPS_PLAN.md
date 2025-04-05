# Mixpitch Development: Next Steps Plan

## Introduction

The Mixpitch project currently has a functional Minimum Viable Product (MVP) covering the core workflow: Clients can post Projects, Producers can submit Pitches, and payments can be processed for completed work. This plan outlines the next phases to evolve Mixpitch into a more robust, feature-rich, and polished platform.

The goal is to move beyond the basic transaction and foster a richer ecosystem for client-producer collaboration.

## Phase 1: Stabilization & Core Refinements (Detailed)

This phase focuses on solidifying the existing foundation, addressing technical debt identified during the initial analysis, improving the usability of core workflows, and removing defunct components.

1.  **Address Technical Debt & Refactor Core Systems:**
    *   **Role Management Consolidation:**
        *   *Investigate:* Confirm current implementation uses both a simple `role` column in `User` model and the `Spatie\Permission\Traits\HasRoles` trait. The `User::hasRole()` method currently uses the simple column but contains commented code for Spatie.
        *   *Decide:* Choose a single, definitive role management system (Spatie or simple column). The simple column approach is currently used consistently in scopes like `scopeClients()` and `scopeProducers()`.
        *   *Refactor:* Based on the decision, remove the unused system (trait/method overrides or Spatie package). If keeping the simple column approach, remove Spatie trait imports and update any Filament configurations. If adopting Spatie fully, refactor all role checks (`$user->role === $role`) and scopes to use the package.
    *   **Notification System Unification:**
        *   *Audit:* Confirm that most functionality uses the custom `NotificationService` (DB + Echo), with a single default Laravel notification (`PitchSubmittedNotification`) still present.
        *   *Refactor:* Migrate any remaining `$user->notify()` calls (like in `PitchSubmittedNotification`) to use `NotificationService`. Integrate `EmailService` calls within `NotificationService` for email alerts. Remove the unused `App\Notifications\Pitch\PitchSubmittedNotification` class.
    *   **`PitchService` Removal:**
        *   *Audit:* The `PitchService` currently exists with methods for basic pitch operations but appears to be mostly superseded by `PitchWorkflowService`.
        *   *Refactor:* Migrate any essential logic from `PitchService` to either `PitchWorkflowService` or relevant Controllers/Livewire components, ensuring proper validation and authorization.
        *   *Remove:* Delete `app/Services/PitchService.php` and remove any service provider bindings once refactoring is complete.
    *   **Storage & Security Hardening:**
        *   *`MixController` Removal:* Remove the confirmed `MixController.php` and its related routes. Update any views or components that might reference them.
        *   *SES Webhook Verification:* Implement AWS SNS signature verification in `SesWebhookController::verifyRequest()` using appropriate libraries/SDK methods. Add necessary configuration.
        *   *Lambda SSL Verification:* Remove `verify => false` from HTTP client options in `GenerateAudioWaveform` job. Ensure the Lambda endpoint has valid SSL and handle potential connection errors.
    *   **Policy Audit & Adjustment:**
        *   *Review `ProjectPolicy`:* Adjust `delete`, `restore`, `forceDelete` permissions (likely owner/admin only). Clarify and adjust `download` permissions (consider allowing pitch creators access to necessary project files).
        *   *Review `PitchFilePolicy`:* Evaluate the `uploadFile` method and make appropriate changes. Clarify `deleteFile` permissions (who can delete, and under which pitch statuses).
    *   **`Track` Model Removal:**
        *   *Audit:* Confirmed the existence of `App\Models\Track` model and related migration.
        *   *Remove:* Delete `app/Models/Track.php`, its migration (`2023_04_07_005817_create_tracks_table.php`), and any other related code like `TrackController.php`. Create a new migration to drop the `tracks` table.

2.  **Refactor File Upload Component:**
    *   *Analyze:* Document the Alpine.js file upload implementation patterns in `resources/views/livewire/pitch/component/manage-pitch.blade.php` and `resources/views/livewire/project/page/manage-project.blade.php`. Both views contain complex JavaScript for file handling, queue management, and interaction with Livewire.
    *   *Design:* Create a reusable solution that leverages the existing `FileManagementService` for backend operations. Options include:
        * A dedicated Livewire component (`<livewire:file-uploader>`) that encapsulates state and logic
        * An Alpine.js component with a matching Blade component (`<x-file-uploader>`) 
        * Shared JavaScript module that can be imported by both components
    *   *Implement & Integrate:* Build the chosen reusable component with consistent error handling, progress indicators, and S3 integration. Ensure proper interaction with storage limits and validation rules.

3.  **Enhance Pitch Workflow Usability:**
    *   *UI/UX Review & Enhancement:* Analyze existing status display implementations in `resources/views/livewire/pitch/component/manage-pitch.blade.php` and create a standardized component. The current implementation uses numerous conditionals for status styling that could be simplified.
    *   *Internal Notes Feature:* Add a nullable `internal_notes` text field to the `pitches` table via migration. Update the `Pitch` model's `$fillable` property. Add a private input field in the `manage-pitch.blade.php` view for producers, with appropriate visibility controls.
    *   *Error Handling:* Standardize how errors are presented to users during workflow actions. Review error handling in `PitchWorkflowService` (which has 800+ lines) and ensure all exceptions produce user-friendly messages. Implement consistent use of flash messages or Toaster notifications.

4.  **Improve Feedback Tools (`PitchFilePlayer`):**
    *   *Comment Enhancements:* The current `PitchFilePlayer` component already has a robust comment system with timestamp-based positioning on the waveform. Choose one of these enhancements:
        *   *Option A (Rich Text):* Upgrade the simple textarea in the comment form to a WYSIWYG editor. Trix or TipTap would integrate well with the existing Alpine.js implementation.
        *   *Option B (@-Mentions):* Implement user mention functionality in comments with auto-complete dropdown for relevant users (project owner and pitch creator).
        *   *Option C (Visual Markers):* Add ability to categorize comments with different marker types/colors (e.g., "question", "feedback", "issue").
    *   *Rating System Implementation:* The `PitchEvent` model already includes a `rating` field but it appears unused. Define a clear implementation plan: 
        * Add a rating UI component in appropriate views (likely in pitch completion flow)
        * Implement the backend logic to store and retrieve ratings
        * Create a display component for showing ratings in project/pitch listings and details

5.  **Leverage Existing Tests:**
    *   *Review Coverage:* The project already has substantial test coverage with feature tests like `PitchPolicyTest`, `ProjectManagementTest`, `FileManagementTest` and unit tests for services including `PitchWorkflowServiceTest`, `FileManagementServiceTest`, and `InvoiceServiceTest`.
    *   *Extend Tests:* Add specific tests for any new components created during this phase. Ensure the file upload refactoring doesn't break existing functionality by extending `FileManagementTest`. Add tests for the internal notes feature and any enhancements to the feedback tools.

## Phase 2: Feature Expansion - Discovery & Communication

With a stable core, this phase focuses on helping users connect and communicate more effectively.

1.  **Enhanced User Profiles:**
    *   **Producer Portfolios:** Allow producers to showcase past work (audio examples, descriptions, potentially links to completed public Mixpitch projects).
    *   **Client History:** Display a client's project history and potentially reviews received from producers.
    *   **Skills/Genre Tagging:** Improve tagging and display of user skills, genres, and potentially equipment for better filtering.
2.  **Search & Discovery:**
    *   **Producer Search:** Implement functionality for clients to browse/search producer profiles based on skills, genres, ratings, keywords, etc.
    *   **Project Search:** Implement advanced filtering and searching for producers to find relevant open projects.
3.  **Direct Messaging:**
    *   Implement a basic direct messaging system allowing one-on-one communication between users (e.g., client/producer discussing a pitch revision, pre-pitch questions).
4.  **Improved Notifications:**
    *   Expand notification coverage for new events (e.g., new messages, profile views if desired).
    *   Implement user preferences for notification delivery (in-app, email).

## Phase 3: Advanced Features & Monetization

This phase introduces more complex features and explores potential monetization avenues.

1.  **Advanced Project Types:**
    *   **Contests:** Introduce a project type where multiple producers submit pitches, and the client picks a winner (requires distinct workflow).
    *   **Direct Hire:** Allow clients to bypass the open pitch process and directly offer a project to a specific producer.
    *   **Service Packages:** Enable producers to define and sell pre-defined service packages (e.g., "Single Track Mastering", "Vocal Tuning").
2.  **Monetization Strategy Implementation:**
    *   **Commission System:** Implement the platform's commission fee on completed transactions.
    *   **Subscription Tiers:** Define and implement subscription plans for clients and/or producers, unlocking features like more active pitches, enhanced profile visibility, lower commission rates, advanced search filters, etc.
    *   **Featured Listings:** Option for users to pay to feature their profiles or projects.
3.  **Formalized Escrow/Disputes:**
    *   Implement a more robust payment escrow system to hold funds until project milestones are met or completion is confirmed.
    *   Develop tools and processes for admin-mediated dispute resolution.
4.  **Collaboration Enhancements:**
    *   Consider features like file versioning within projects/pitches.
    *   Explore integrations with cloud storage providers (Dropbox, Google Drive) for file handling.

## Phase 4: Long-Term & Polish

This phase focuses on long-term growth, integrations, and refining the user experience.

1.  **Deeper Integrations:**
    *   Explore potential integrations with Digital Audio Workstations (DAWs) or other music production tools.
    *   API for third-party access (potential for advanced users or partners).
2.  **Community Features:**
    *   Forums, groups, or other community-building features.
    *   Public showcase of successfully completed projects (with user permission).
3.  **Advanced Analytics & Reporting:**
    *   Provide more detailed analytics for users (e.g., pitch success rates for producers, spending trends for clients).
    *   Enhance admin reporting capabilities.
4.  **Mobile Experience:**
    *   Develop dedicated mobile applications or ensure a highly polished responsive web experience.
5.  **Ongoing Optimization:**
    *   Performance tuning (database, caching, frontend loading).
    *   Continuous security auditing and updates.
    *   Accessibility (a11y) improvements.

## Cross-Cutting Concerns (Ongoing Throughout Phases)

*   **Testing:** Continuously expand test coverage (Unit, Feature, Integration, potentially Browser tests) as new features are added and refactoring occurs.
*   **Documentation:** Maintain and update internal documentation (`docs/`) and potentially create user-facing help guides.
*   **Security:** Regularly review dependencies, implement security best practices, conduct security audits.
*   **Admin Panel:** Incrementally enhance the Filament admin panel to support new features (e.g., managing disputes, viewing subscription data, moderation tools).
*   **UI/UX:** Continuously refine the user interface and experience based on feedback and usability testing. 
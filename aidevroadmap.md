# MixPitch Development Roadmap

This roadmap outlines the necessary improvements and features for the MixPitch platform, organized by priority. The focus is on completing and enhancing the pitch workflow system to ensure a robust, error-free user experience.

## High Priority (Critical Path)

These items address core functionality issues and should be completed first to ensure the basic system operates correctly.

### 1. Status Transition and Validation

- [Done] **Status Transition Validation**
  - **Context**: The `Pitch` model (`app/Models/Pitch.php`) has several `canX()` methods that need to be consistently applied.
  - **Implementation Details**:
    - Review and enhance validation in `canSubmitForReview()`, `canCancelSubmission()`, `canApprove()`, `canDeny()`, and `canComplete()` methods
    - Add missing validations in `UpdatePitchStatus.php` component (esp. in `changeStatus()` calls)
    - Fix inconsistent status transition validation in `ManagePitch.php` and `PitchController.php`
    - Standardize error messages for invalid transitions
  - **Key Files**: 
    - `app/Models/Pitch.php` (update validation methods)
    - `app/Livewire/Pitch/Component/UpdatePitchStatus.php` (ensure validations are called)
    - `app/Http/Controllers/PitchController.php` (add missing validations)

- [Done] **Project-Pitch Status Synchronization**
  - **Context**: Currently, project status doesn't automatically update when pitches change status.
  - **Implementation Details**:
    - Enhance `Project` model (`app/Models/Project.php`) with status update methods
    - Add observer pattern or event listeners in `CompletePitch.php` to update project status
    - Implement `syncStatusWithPitches()` method in Project model
    - Add validation in `ProjectController.php` to prevent completed projects from accepting new pitches
  - **Key Files**:
    - `app/Models/Project.php` (add status sync methods)
    - `app/Livewire/Pitch/Component/CompletePitch.php` (trigger project status updates)
    - `app/Http/Controllers/ProjectController.php` (add validation for completed projects)

### 2. Error Handling and Edge Case Management

- [Done] **Consistent Error Handling**
  - **Context**: Multi-step operations like status changes lack transaction safety.
  - **Implementation Details**:
    - Wrap critical operations in database transactions in `UpdatePitchStatus.php` and `CompletePitch.php`
    - Create custom exception classes in `app/Exceptions/` for pitch workflow errors
    - Update ExceptionHandler to handle these custom exceptions with appropriate responses
    - Add recovery methods for failed operations in `PitchController.php`
  - **Key Files**:
    - `app/Livewire/Pitch/Component/UpdatePitchStatus.php` (add transactions)
    - `app/Livewire/Pitch/Component/CompletePitch.php` (add transactions)
    - `app/Exceptions/Handler.php` (update exception handling)
    - `app/Http/Controllers/PitchController.php` (add recovery logic)

- [ ] **Multiple Approved Pitches Handling**
  - **Context**: When multiple pitches are approved, only one can be completed, but the UI doesn't reflect this.
  - **Implementation Details**:
    - Update `ManageProject.php` component to show warnings when multiple pitches are approved
    - Enhance `resources/views/livewire/manage-project.blade.php` with visual indicators for multiple approved pitches
    - Add confirmation dialog in `CompletePitch.php` asking which approved pitch to complete
    - Update `canComplete()` method in Pitch model to check for other approved pitches
  - **Key Files**:
    - `app/Livewire/ManageProject.php` (add detection for multiple approved pitches)
    - `resources/views/livewire/manage-project.blade.php` (update UI for warnings)
    - `app/Livewire/Pitch/Component/CompletePitch.php` (enhance completion flow)
    - `app/Models/Pitch.php` (update `canComplete()` method)

### 3. Snapshot Management

- [ ] **Snapshot Data Integrity**
  - **Context**: Snapshot data (`file_ids` array) may reference files that no longer exist.
  - **Implementation Details**:
    - Add data validation when creating snapshots in `PitchController@createSnapshot`
    - Implement integrity checks in `show-snapshot.blade.php` view
    - Create validation method `validateSnapshotData()` in Snapshot model
    - Add recovery for missing files in `PitchController@showSnapshot`
  - **Key Files**:
    - `app/Http/Controllers/PitchController.php` (validate snapshot creation)
    - `resources/views/livewire/pitch/snapshot/show-snapshot.blade.php` (add integrity checks)
    - `app/Models/Snapshot.php` (add validation methods)
    - `app/Services/PitchService.php` (create new service for snapshot management)

- [ ] **Snapshot Navigation**
  - **Context**: Current UI lacks clear navigation between snapshot versions.
  - **Implementation Details**:
    - Enhance `show-snapshot.blade.php` with version navigation controls
    - Add breadcrumb component for snapshot history in `resources/views/components/`
    - Implement version comparison view in `PitchController.php`
    - Update `ManagePitch.php` to show snapshot version history
  - **Key Files**:
    - `resources/views/livewire/pitch/snapshot/show-snapshot.blade.php` (update UI)
    - `resources/views/components/snapshot-breadcrumb.blade.php` (create new component)
    - `app/Http/Controllers/PitchController.php` (add comparison methods)
    - `app/Livewire/Pitch/Component/ManagePitch.php` (enhance version display)

## Medium Priority (User Experience Improvements)

These items enhance the existing functionality and improve the overall user experience.

### 4. Notification System Enhancements

- [ ] **Notification Completeness**
  - **Context**: The current notification system doesn't capture all important state changes, especially for pitch completion and closing of other pitches.
  - **Implementation Details**:
    - Enhance `NotificationService.php` to handle batch notifications during pitch completion
    - Add notification methods for closed/inactive pitches when another pitch is completed
    - Create notification preference settings in User model
    - Implement user-configurable notification preferences in profile settings
  - **Key Files**: 
    - `app/Services/NotificationService.php` (add missing notification types)
    - `app/Models/User.php` (add notification preferences)
    - `app/Livewire/Pitch/Component/CompletePitch.php` (add notifications for closed pitches)
    - `resources/views/profile/edit.blade.php` (add notification settings UI)

- [ ] **Notification Cleanup**
  - **Context**: When pitches or projects are deleted, their associated notifications remain in the database.
  - **Implementation Details**:
    - Add cascade deletion for notifications in pitch/project models
    - Implement notification expiration/archiving mechanism
    - Create batch cleanup job for old notifications
    - Add notification group functionality for related events
  - **Key Files**:
    - `app/Models/Pitch.php` (add cascading deletes for notifications)
    - `app/Models/Project.php` (add cascading deletes for notifications)
    - `app/Console/Commands/CleanupNotifications.php` (create new command)
    - `app/Jobs/NotificationCleanupJob.php` (create new job)

### 5. User Permissions and Role Management

- [ ] **Role-based Access Control**
  - **Context**: The current permission system lacks granularity and clear role definitions beyond simple project owner vs. pitch creator.
  - **Implementation Details**:
    - Create `RoleService.php` to centralize permission checks
    - Define clear permission matrices for different actions
    - Refactor controllers and components to use the permission service
    - Add role-specific UI elements and action buttons
  - **Key Files**:
    - `app/Services/RoleService.php` (create new service)
    - `app/Providers/AuthServiceProvider.php` (define policies)
    - `app/Policies/PitchPolicy.php` (create or update policy)
    - `app/Policies/ProjectPolicy.php` (create or update policy)

- [ ] **Multi-user Collaboration**
  - **Context**: When multiple users work on different pitches for the same project, there's limited visibility into others' activities.
  - **Implementation Details**:
    - Add activity indicators on manage-project page
    - Implement "currently active" status for users viewing the same project
    - Create project activity feed component
    - Add conflict detection when multiple approved pitches exist
  - **Key Files**:
    - `app/Livewire/ManageProject.php` (add active users display)
    - `resources/views/livewire/manage-project.blade.php` (update UI)
    - `app/Livewire/Components/ProjectActivityFeed.php` (create new component)
    - `resources/views/livewire/components/project-activity-feed.blade.php` (create view)

### 6. User Feedback and Guidance

- [ ] **Guided User Flows**
  - **Context**: New users lack clear guidance on complex workflows like creating and submitting pitches.
  - **Implementation Details**:
    - Implement step-by-step wizard for pitch creation process
    - Add tooltips and contextual help throughout the UI
    - Create "Getting Started" guides for project owners and pitch creators
    - Enhance status indicators with clearer visual cues
  - **Key Files**:
    - `resources/views/components/wizard-step.blade.php` (create new component)
    - `app/Livewire/Pitch/CreatePitchWizard.php` (create new component)
    - `resources/js/tooltips.js` (create or enhance)
    - `resources/views/help/getting-started.blade.php` (create new view)

- [ ] **Feedback Loop Completion**
  - **Context**: After pitch completion, there's no formal feedback mechanism for project owners or pitch creators.
  - **Implementation Details**:
    - Create post-completion survey component for project owners
    - Implement rating system for completed pitches
    - Add satisfaction metrics to user profiles
    - Build reporting dashboard for overall platform metrics
  - **Key Files**:
    - `app/Livewire/Pitch/Component/PitchFeedbackForm.php` (create new component)
    - `resources/views/livewire/pitch/component/pitch-feedback-form.blade.php` (create view)
    - `app/Models/PitchRating.php` (create new model)
    - `database/migrations/create_pitch_ratings_table.php` (create new migration)

## Low Priority (Nice-to-Have Improvements)

These items enhance the system but are not critical for core functionality.

### 7. Performance Optimization

- [ ] **Query Optimization**
  - Optimize database queries, especially for history and snapshots
  - Implement caching for frequently accessed data
  - Add pagination for projects with many pitches

- [ ] **Asset Management**
  - Optimize file storage and retrieval
  - Implement file compression options
  - Add better preview capabilities for different file types

### 8. Code Refactoring

- [ ] **Eliminate Duplication**
  - Consolidate duplicate logic across controllers and components
  - Standardize naming conventions throughout the codebase
  - Create shared service classes for common operations

- [ ] **Technical Debt Reduction**
  - Add comprehensive documentation
  - Improve test coverage
  - Streamline complex methods

### 9. Advanced Features

- [ ] **Snapshot Comparison**
  - Add side-by-side comparison of different snapshots
  - Implement versioning system similar to git diffs
  - Create visual representation of changes between versions

- [ ] **Project Analytics**
  - Add analytics dashboard for project owners
  - Implement metrics for pitch progress and activity
  - Create insights for common patterns in successful pitches

## Immediate Focus Areas (Next Steps)

Based on current system state, these are the most important next actions:

1. Fix validation in pitch status transitions to ensure consistent state
2. Implement comprehensive error handling for all controllers
3. Enhance snapshot navigation UI between different versions
4. Complete notification integration for all state changes
5. Add transaction safety to multi-step operations
6. Implement proper project status synchronization with pitch status

## Long-term Vision

The ultimate goal is to create a seamless platform where:

1. Project owners can easily create, manage, and complete projects
2. Pitch creators have a clear understanding of requirements and feedback
3. The system maintains data integrity through all possible state transitions
4. Users receive appropriate notifications at every important step
5. The platform provides valuable analytics and insights for all parties

This roadmap will be updated as items are completed and new priorities emerge.

# Livewire Components (`app/Livewire/`)

## Index

*   **Project Management:**
    *   [`CreateProject`](#createproject-analysis)
    *   [`ManageProject`](#manageproject-analysis)
    *   [`ProjectForm`](#projectform-form-object-analysis) (Form Object)
*   **Pitch Management:**
    *   [`Pitch\Component\ManagePitch`](#pitchcomponentmanagepitch-analysis)
    *   [`Pitch\Snapshot\ShowSnapshot`](#pitchsnapshotshowsnapshot-analysis)
    *   [`Pitch\Component\UpdatePitchStatus`](#pitchcomponentupdatepitchstatus-analysis)
    *   [`Pitch\Component\ConfirmStatusChange`](#pitchcomponentconfirmstatuschange-analysis)
    *   [`Pitch\Component\CompletePitch`](#pitchcomponentcompletepitch-analysis)
    *   [`Pitch\Component\PitchHistory`](#pitchcomponentpitchhistory-analysis)
    *   [`Pitch\Component\FeedbackConversation`](#pitchcomponentfeedbackconversation-analysis)
    *   [`Pitch\Component\DeletePitch`](#pitchcomponentdeletepitch-analysis)
*   **File Players:**
    *   [`PitchFilePlayer`](#pitchfileplayer-analysis)
    *   [`SnapshotFilePlayer`](#snapshotfileplayer-analysis)
    *   [`AudioPlayer`](#audioplayer-analysis)
*   **User Profile:**
    *   [`UserProfileEdit`](#userprofileedit-analysis)
*   **Notifications:**
    *   [`NotificationList`](#notificationlist-analysis)
    *   [`NotificationCount`](#notificationcount-analysis)

*(Analysis pending...)* 

### `CreateProject` Analysis

**Status:** Complete.

**File:** `app/Livewire/CreateProject.php`

Handles both creation and editing of projects, serving as the interactive frontend logic.

*   **Purpose:** Provides the UI logic for the project creation/edit form.
*   **Dependencies:** `Project` model, `ProjectForm` form object, `ProjectManagementService`, `WithFileUploads`, `Toaster`, `Log`.
*   **State:** Manages the `Project` model (`$project`), `ProjectForm` (`$form`), edit mode flag (`$isEdit`), temporary file uploads (`$projectImage`, `$track`), display URLs (`$audioUrl`), etc.
*   **Mount:** Initializes form for create or loads existing project data into the form for edit, performs authorization.
*   **Form Handling:** Uses the `ProjectForm` object (`$form`) for data binding and validation. Includes logic to map collaboration checkboxes to the database array.
*   **File Handling:** Uses `WithFileUploads`. Manages temporary previews and passes uploaded files to the service on save. Preview track logic is marked with **TODOs** for refactoring using `FileManagementService`.
*   **Save Logic (`save()`):** Validates the form, authorizes, transforms data, calls `ProjectManagementService` (`createProject` or `updateProject`), handles exceptions, displays toast notifications, and redirects.
*   **Rendering:** Returns `livewire.project.page.create-project` view.

**Interaction:** This component renders the project form view. It interacts with the user for input, manages file uploads temporarily, validates using `ProjectForm`, and delegates the actual database/S3 operations to `ProjectManagementService` upon submission.

**View Analysis (`resources/views/livewire/project/page/create-project.blade.php`):**
*   **Structure:** A single form (`wire:submit="save"`) divided into collapsible sections (Basic Info, Collaboration, Budget, Deadline, Notes) using Alpine.js accordion (`x-data`, `@click`, `x-show`). Includes a collapsible help section.
*   **Styling:** Uses Tailwind/DaisyUI with custom styles for transitions and responsiveness.
*   **Input Binding:** Fields primarily use `wire:model.blur="form.propertyName"` to bind to the `$form` object.
*   **Validation:** Displays errors using `@error`. Uses Alpine (`x-data`, `@blur`) to show/hide required/valid/invalid icons next to labels based on interaction state.
*   **Image Upload:** Shows preview using `temporaryUrl()`. File input uses `wire:model="form.projectImage"`. Revert button uses `wire:click="revertImage"`.
*   **Budget Section:** Uses Alpine (`x-data`, `@entangle`, `@click`) to toggle between 'Free'/'Paid', conditionally show the numeric input, and update Livewire state via `$wire.set()`.
*   **Submit Button:** Text changes based on `$isEdit`.
*   **Interaction Summary:** Uses Livewire for form submission, data binding, and validation. Uses Alpine extensively for UI interactivity (accordion, help text, validation indicators, budget selection).

*(Further Livewire analysis pending...)*

### `ProjectForm` (Form Object) Analysis

**Status:** Complete.

**File:** `app/Livewire/Forms/ProjectForm.php`

Livewire v3 Form Object encapsulating properties and validation for the project form.

*   **Purpose:** Defines the structure and validation rules for the project form data.
*   **Inheritance:** Extends `Livewire\Form`.
*   **Properties:** Defines public properties matching form fields (e.g., `name`, `artistName`, `projectType`, `description`, `genre`, `projectImage`, `collaborationType*`, `budgetType`, `budget`, `deadline`, `track`, `notes`).
*   **Validation:** Uses `#[Rule]` attributes on properties to define comprehensive validation rules (required, types, lengths, enums, file types/sizes, dates, numeric ranges).

**Interaction:** Instantiated within the `CreateProject` component (`$this->form`). Used by `CreateProject` to manage form state and trigger validation (`$this->form->validate()`). Centralizes form validation logic.

*(Further Livewire analysis pending...)*

### `ManageProject` Analysis

**Status:** Complete.

**File:** `app/Livewire/ManageProject.php`

Comprehensive component for the project owner's management interface.

*   **Purpose:** Allows viewing/editing project details, managing files (upload, delete, download), managing preview track, publishing/unpublishing, and viewing related pitch info.
*   **Dependencies:** `Project`, `ProjectFile` models, `ProjectForm`, `ProjectManagementService`, `FileManagementService`, `WithFileUploads`, `Toaster`, `Log`, `Auth`.
*   **State:** Manages `$project`, `$form`, file queue state (`tempUploadedFiles`, `$isProcessingQueue`, `$uploadProgress`), storage info, preview track state, etc.
*   **Mount:** Authorizes (`update` policy), loads project data into form, sets initial state.
*   **Project Updates (`updateProjectDetails()`):** Authorizes, validates form, transforms data, delegates to `ProjectManagementService`, shows toaster.
*   **Publish/Unpublish:** Authorizes, calls methods on `Project` model, shows toaster, dispatches event.
*   **Preview Track (`togglePreviewTrack()`, `clearPreviewTrack()`):** Authorizes, delegates to `FileManagementService`, updates component state, dispatches event, shows toaster.
*   **File Management:**
    *   **Upload Queue:** Manages a queue (`tempUploadedFiles`). Uses JS for actual S3 uploads (`trigger-upload` event) and receives success/failure events back (`uploadSuccess`, `uploadFailed` actions).
    *   **Deletion (`deleteFile()`):** Authorizes (`deleteFile` policy), delegates to `FileManagementService`, updates storage info, shows toaster, dispatches event.
    *   **Download (`getDownloadUrl()`):** Authorizes (`download` policy), gets URL from `FileManagementService`, dispatches `openUrl` event for JS.
*   **Pitch Info:** Includes helpers to get counts/lists of approved/completed pitches for display.
*   **Rendering:** Returns `livewire.project.page.manage-project` view with project and pitch data.

**Interaction:** Provides the main interface for project owners. Uses services for backend logic, policies for authorization, manages complex state for file uploads, and interacts with the frontend via dispatched events and toaster notifications.

**View Analysis (`resources/views/livewire/project/page/manage-project.blade.php`):**
*   **Structure:** Multi-section layout including Project Header (image, details, metadata), Project Status/Publish controls, Pitches list, Tracks list (with upload), and Danger Zone (delete project).
*   **Styling:** Uses Tailwind/DaisyUI with extensive custom responsive styles.
*   **Project Header:** Displays core project data, owner info (`<x-user-link>`), includes `<x-project-status-button>`, shows Edit link. Uses Alpine.js for image lightbox. Conditionally includes `<livewire:audio-player>` for preview track.
*   **Project Status:** Buttons trigger `wire:click="publish"` / `wire:click="unpublish"`. Shows conditional warnings.
*   **Pitches List:** Iterates sorted pitches. Shows submitter (`<x-user-link>`), status, snapshots. Includes `<livewire:pitch.component.complete-pitch>`, payment/receipt links, and `<x-update-pitch-status>` component.
*   **Tracks Section:**
    *   Displays storage usage bar.
    *   **Upload UI:** File input triggers JS. Displays queued files (`$tempUploadedFiles`) and progress (`$uploadProgress`). Upload button triggers `wire:click="queueFilesForUpload"`.
    *   **Upload Logic (JS/Alpine):** Complex inline JS within `x-data` listens for file selection, manages JS `uploadQueue`, sends metadata to Livewire (`@this.set`). Listens for Livewire event (`trigger-upload`) and uploads file via `fetch` to `/project/upload-file`. Calls Livewire methods (`@this.uploadSuccess`/`uploadFailed`) on completion.
    *   **File List:** Iterates `$project->files`. Shows name, date, size. Includes "Set preview" button (`wire:click="togglePreviewTrack"`), download link, and delete button (`@click="openDeleteModal"`).
*   **Danger Zone:** Delete Project button uses Alpine (`x-data`, `x-show`, `@click`) to open a confirmation modal containing a standard form POSTing to `projects.destroy`.
*   **Interaction Summary:** Mix of Livewire actions (`wire:click`), complex JS/Alpine for file uploads, Alpine for modals, standard form POST for project deletion. Includes various Blade and Livewire components.

*(Further Livewire analysis pending...)*

### `Pitch\Component\ManagePitch` Analysis

**Status:** Complete.

**File:** `app/Livewire/Pitch/Component/ManagePitch.php`

Comprehensive component for producers to manage their pitch submissions.

*   **Purpose:** View pitch details, manage files (upload, delete, download, notes), view history/feedback, submit for review, cancel submission, delete pitch.
*   **Dependencies:** Models (`Pitch`, `PitchFile`, `Snapshot`, etc.), Services (`PitchWorkflowService`, `FileManagementService`, `PitchService`), Livewire (`Component`, `WithFileUploads`, `WithPagination`, `AuthorizesRequests`), `Toaster`, `Log`, `Auth`.
*   **State:** Manages `$pitch`, related models (`project`, `snapshots`, `events`), file queue state, storage info, feedback messages, comment/rating input, modal visibility.
*   **Mount:** Loads pitch and relations, initializes state, fetches latest feedback.
*   **File Management:** Similar to `ManageProject` - uses JS (`trigger-pitch-upload`) for uploads, receives events (`uploadSuccess`/`uploadFailed`). Delegates delete/download to `FileManagementService` with authorization.
*   **Workflow Actions:**
    *   `submitForReview()`: Authorizes, delegates to `PitchWorkflowService`, handles exceptions, redirects.
    *   `cancelPitchSubmission()`: Authorizes, delegates to `PitchWorkflowService`, refreshes state.
    *   `deletePitch()`: Uses modal confirmation. Authorizes, delegates to `PitchService` (**needs review - cleanup**), redirects.
*   **Other Actions:** `submitComment()`, `deleteComment()`, `submitRating()` (legacy?), `saveNote()`. Interact directly with models or `PitchFile`.
*   **Feedback:** `getLatestStatusFeedback()` helper parses latest revision/denial reason from `PitchEvent`s.
*   **Rendering:** Returns `livewire.pitch.component.manage-pitch` view with pitch data, paginated files/events, snapshots.

**Interaction:** The primary interface for producers working on a pitch. Delegates core workflow and file operations to services, handles authorization, manages complex UI state (especially file uploads), and displays relevant history and feedback.

**View Analysis (`resources/views/livewire/pitch/component/manage-pitch.blade.php`):**
*   **Structure:** Displays status alerts, submission history (snapshots list), file management (upload UI, file list), and conditional action buttons (submit/resubmit, cancel).
*   **Styling:** Tailwind/DaisyUI, conditional styles based on pitch/snapshot status.
*   **Status/Feedback:** Shows status-specific alert boxes with descriptions and feedback (`$statusFeedbackMessage`). Includes response textarea (`wire:model.lazy="responseToFeedback"`) when revisions are requested.
*   **Snapshots List:** Iterates snapshots, linking to detail page. Includes delete button (`wire:click="deleteSnapshot"`, `wire:confirm`).
*   **File Management:**
    *   Displays storage usage bar.
    *   **Upload UI:** File input, queue display (`$tempUploadedFiles`), progress (`$uploadProgress`), upload button (`wire:click="startUploadProcess"`).
    *   **Upload Logic (JS/Alpine):** Uses the same complex inline JS/Alpine pattern as `manage-project` for file selection, queue management, metadata sync (`@this.set`), `fetch` uploads (to `route('pitch.uploadFile')`), and calling Livewire success/fail methods.
    *   **File List:** Iterates `$pitch->files`, shows type-based icon, name, size. Includes download (`wire:click="downloadFile"`) and delete (triggers Alpine modal) buttons.
    *   **Delete File Modal:** Handled via Alpine (`x-data`, `x-show`), confirms via `$wire.deleteSelectedFile`.
*   **Actions:** Submit/Resubmit button (`wire:click="submitForReview"`, `wire:confirm`) requires terms checkbox (`wire:model.defer`). Cancel button (`wire:click="cancelPitchSubmission"`, `wire:confirm`). Includes loading states (`wire:loading`).
*   **Interaction Summary:** Mix of Livewire actions (`wire:click`, `wire:confirm`), complex JS/Alpine for file uploads, Alpine for delete modal.

*(Further Livewire analysis pending...)*

### `Pitch\Snapshot\ShowSnapshot` Analysis

**Status:** Complete.

**File:** `app/Livewire/Pitch/Snapshot/ShowSnapshot.php`

Displays a specific version (snapshot) of a pitch, focusing on its files and related feedback.

*   **Purpose:** Renders the view for a single pitch snapshot, including its files (implicitly) and a structured conversation thread of feedback/responses for that version.
*   **Dependencies:** Models (`Pitch`, `Project`, `PitchSnapshot`, `User`, `PitchEvent`), Livewire (`Component`), Facades (`Auth`, `Log`).
*   **State:** Holds the parent `$pitch`, the specific `$pitchSnapshot`, and its `$snapshotData`.
*   **Mount:** Handles slug-based route model binding (`/projects/{project}/pitches/{pitch}/snapshots/{snapshot}`). Verifies model relationships. Authorizes access for pitch creator or project owner.
*   **Conversation Logic:**
    *   `getConversationThread()`: Builds the feedback thread for the current snapshot by:
        1.  Adding any `response_to_feedback` stored within the current `$snapshotData`.
        2.  Calling `getCurrentSnapshotFeedback()` to find and add feedback (revision/denial) directed *at* the current snapshot from `PitchEvent` records.
        3.  Sorting the resulting items by date.
    *   `getCurrentSnapshotFeedback()`: Queries `PitchEvent` for revision requests or denial events linked to the current snapshot ID. Extracts the feedback message from event metadata or comment.
*   **Rendering:** Returns `livewire.pitch.snapshot.show-snapshot` view, passing the constructed `$conversationThread`.

**Interaction:** Provides the view layer for reviewing a specific pitch version. It fetches the relevant snapshot and constructs a focused conversation history related *only* to that snapshot's review cycle.

**View Analysis (`resources/views/livewire/pitch/snapshot/show-snapshot.blade.php`):**
*   **Structure:** Header with project/pitch/user info. Status bar (conditional buttons or text). Optional snapshot navigation. Feedback/Response conversation thread. Pitch files list. Back buttons.
*   **Styling:** Tailwind/DaisyUI.
*   **Header:** Displays project image (Alpine lightbox), pitch owner (`<x-user-link>`), project link, snapshot date.
*   **Status Bar:** Conditionally shows Approve/Revisions/Deny buttons (for owner, if pending) which trigger JS functions (`openApproveModal`, etc.). Otherwise shows status text.
*   **Snapshot Navigation:** Renders Previous/Next/Latest links if applicable.
*   **Feedback Thread:** Iterates `$conversationThread` displaying styled feedback/response items.
*   **Pitch Files:** Loops through file IDs, finds models, and renders `<livewire:snapshot-file-player>` for each, passing the file model.
*   **Modals:** Includes `<x-pitch-action-modals />` (which contains the JS-driven modals and logic).
*   **Interaction Summary:** Primarily display-focused. Owner actions rely on the external JS/modal pattern. Embeds multiple Livewire file player components. Uses Alpine for lightbox.

*(Further Livewire analysis pending...)*

### `PitchFilePlayer` Analysis

**Status:** Complete.

**File:** `app/Livewire/PitchFilePlayer.php`

Handles the display and interaction for a single audio pitch file, including playback and timestamped comments.

*   **Purpose:** Render an interactive audio player with waveform, comments, replies, and comment management.
*   **Dependencies:** Models (`PitchFile`, `PitchFileComment`), `NotificationService`, Livewire (`Component`, `On`), Facades (`Auth`, `Log`).
*   **State:** Manages `$file`, loaded `$comments`, `$commentMarkers`, playback state (`$isPlaying`), comment form state (`$newComment`, `$commentTimestamp`, `$showAddCommentForm`), reply form state (`$replyToCommentId`, `$showReplyForm`, `$replyText`), delete confirmation state.
*   **Mount:** Loads file, comments (with nested replies), calculates initial markers.
*   **Waveform Interaction:** Listens for JS events (`waveformReady`, `playbackStarted`, `playbackPaused`), dispatches events to JS (`seekToPosition`, `pausePlayback`). Calculates marker positions based on `$duration`.
*   **Comments & Replies:**
    *   `addComment()`: Creates top-level comment, notifies, reloads.
    *   `submitReply()`: Creates nested comment (using original parent timestamp), notifies, reloads.
    *   `toggleResolveComment()`: Toggles `resolved` status (author/pitch owner only).
    *   `deleteComment()`: Deletes comment and all nested replies (author/pitch owner only).
*   **Rendering:** Returns `livewire.pitch-file-player` view.

**Interaction:**
*   Acts as the backend logic for the `pitch-file-player.blade.php` view.
*   Communicates bidirectionally with a frontend JavaScript audio player component.
*   Loads and saves `PitchFileComment` data.
*   Triggers notifications via `NotificationService` when comments/replies are added.
*   Listens for a `refresh` event to reload its state.

**View Analysis (`resources/views/livewire/pitch-file-player.blade.php`):**
*   **Structure:** Displays file header, waveform/timeline areas, playback controls, add comment form, and threaded comments section.
*   **Styling:** Uses Tailwind/DaisyUI.
*   **Waveform:** Uses divs (`#waveform`, `#waveform-timeline`) populated by Wavesurfer.js via inline JavaScript in `@push('scripts')`. Loads pre-generated peaks if available.
*   **Comment Markers:** Renders markers absolutely positioned over the waveform based on timestamp/duration. Uses Alpine.js for hover tooltips. Clicking marker dispatches JS event (`comment-marker-clicked`).
*   **Playback Controls:** Uses Alpine.js for play/pause button state, displays current/total time updated by JS.
*   **Add Comment Form:** Conditionally shown using Alpine (`x-show`, `@entangle`), binds to `$wire.newComment`. Button gets current time from JS (`wavesurfer.getCurrentTime()`) before calling `$wire.toggleCommentForm`. Form submitted via `wire:click`.
*   **Comments Display:** Loops through `$comments` and `replies`. Displays user info, timestamp (clickable with `@click="$wire.seekTo(...)"`), and text. Includes buttons for Reply (`@click="$wire.toggleReplyForm(...)"`), Resolve (`@click="$wire.toggleResolveComment(...)"`), and Delete (`@click="$wire.confirmDelete(...)"`).
*   **Reply Form:** Conditionally shown per comment/reply using `$wire.showReplyForm`, binds to `$wire.replyText`, submitted via `wire:click`.
*   **Delete Modal:** Uses Alpine (`x-data`, `x-show`, `@entangle`) for display, confirms via `wire:click`.
*   **JS <-> Livewire:** Heavy interaction via inline JS: initializing Wavesurfer, handling its events, dispatching events to Livewire (`ready`, `playbackStarted`, etc.), listening for Livewire events (`seekToPosition`, etc.), accessing Livewire component via `Livewire.find(...)`, `$wire`.

*(Further Livewire analysis pending...)*

### `UserProfileEdit` Analysis

**Status:** Complete.

**File:** `app/Livewire/UserProfileEdit.php`

Handles the user profile editing form and logic.

*   **Purpose:** Allows authenticated users to update their profile information, including text fields, lists (skills, equipment, specialties), social links, and profile photo.
*   **Dependencies:** `User` model (via `Auth`), `WithFileUploads`, `Log`, `Toaster`.
*   **State:** Holds properties for all editable profile fields, including arrays for lists and temporary state for photo upload and new list items.
*   **Validation:** Defines comprehensive rules using `rules()` method, including uniqueness checks, array validation, image validation, and a custom rule for tip jar links.
*   **Mount:** Loads current user data into component properties.
*   **List Management:** Includes `add*`/`remove*` methods for dynamically managing skills, equipment, and specialties lists.
*   **Profile Completion:** Calculates and stores a profile completion percentage.
*   **Save Logic (`save()`):** Validates data, cleans array/URL inputs, handles username locking, updates profile photo via `User::updateProfilePhoto()`, saves user model, shows toaster, redirects.
*   **Rendering:** Returns `livewire.user-profile-edit` view.

**Interaction:** Provides the interactive form for profile editing. Handles validation, dynamic list updates, photo uploads, and saves data to the authenticated user model.

**View Analysis (`resources/views/livewire/user-profile-edit.blade.php`):**
*   **Structure:** Multi-section form (`wire:submit.prevent="save"`) for Photo, Basic Info, Skills/Equipment/Specialties, Social Media.
*   **Styling:** Tailwind/DaisyUI, sectioned layout.
*   **Feedback:** Displays standard session flash messages and uses Alpine to listen for `profile-updated` Livewire event to show timed messages.
*   **Photo Upload:** Uses Livewire file upload (`wire:model="profilePhoto"`) with temporary preview (`temporaryUrl()`) and loading indicator.
*   **Basic Info:** Standard inputs bound with `wire:model`. Username input conditionally disabled.
*   **Dynamic Lists (Skills, etc.):** Displays items as badges with remove buttons (`wire:click="removeSkill(...)"`). Includes input (`wire:model="newSkill"`, `wire:keydown.enter.prevent`) and add button (`wire:click="addSkill"`) for adding items.
*   **Social Media:** Inputs bound with `wire:model="social_links.platform"`, styled with prepended platform URL text.
*   **Save Button:** Triggers `wire:submit`.
*   **Interaction Summary:** Uses Livewire for form submission, data binding (including nested array for social links), file uploads, and dynamic list management. Uses Alpine for displaying event-based feedback.

*(Further Livewire analysis pending...)*

### `NotificationList` Analysis

**Status:** Complete.

**File:** `app/Livewire/NotificationList.php`

Displays a list of recent user notifications, handles marking as read, and listens for real-time updates.

*   **Purpose:** Renders the notification dropdown list.
*   **Dependencies:** `Notification` model, `Auth`.
*   **State:** Holds `$notifications`, `$hasUnread` flag, `$showDropdown` toggle, `$notificationLimit`.
*   **Listeners:** Listens for `notificationRead` event (local refresh) and `echo-private:notifications.{Auth::id()},NotificationCreated` (via Echo, triggers `refreshNotifications`).
*   **Core Methods:** `loadNotifications()`, `markAsRead()`, `markAllAsRead()`, `loadMoreNotifications()`, `refreshNotifications()`.
*   **Rendering:** Returns `livewire.notification-list` view.

**Interaction:** Displays notifications, allows marking read (dispatching `notificationRead`), and updates in real-time via Laravel Echo when new notifications are created.

**View Analysis (`resources/views/livewire/notification-list.blade.php`):**
*   **Structure:** Alpine-controlled dropdown (`x-data`, `@entangle`, `x-show`, `@click.away`). Button includes `<livewire:notification-count />`. Dropdown lists notifications with type-specific icons, description, time. Includes "Mark all read" and "Load more" buttons.
*   **Styling:** Tailwind/DaisyUI. Differentiates read/unread items.
*   **Actions:**
    *   Mark All Read: `wire:click="markAllAsRead"`.
    *   Mark One Read / Navigate: `<a>` tag with `href` and `wire:click="markAsRead(...)"`.
    *   Load More: `wire:click="loadMoreNotifications"`.
*   **Interaction Summary:** Uses Alpine for dropdown visibility. Uses Livewire for data loading, actions (mark read, load more), and real-time updates via Echo listener in the component class.

*(Further Livewire analysis pending...)*

### `NotificationCount` Analysis

**Status:** Complete.

**File:** `app/Livewire/NotificationCount.php`

Displays the count of unread notifications.

*   **Purpose:** Shows the unread notification count badge.
*   **Dependencies:** `Notification` model, `Auth`.
*   **State:** Holds `$count`.
*   **Listeners:** Listens for `notificationRead` event (local refresh) and `echo-private:notifications.{Auth::id()},NotificationCreated` (via Echo, triggers refresh).
*   **Core Methods:** `mount()`, `updateCount()` (queries DB for unread count).
*   **Rendering:** Returns `livewire.notification-count` view.

**Interaction:** Provides the unread count. Refreshes itself when notifications are marked read (via `notificationRead` event from `NotificationList`) or when new notifications arrive via Echo.

**View Analysis (`resources/views/livewire/notification-count.blade.php`):**
*   **Structure:** Displays a Font Awesome bell icon (`fas fa-bell`).
*   **Conditional Badge:** If `$count > 0`, overlays a red badge with `animate-ping`. Badge shows `$count` (capped at '99+').
*   **Styling:** Tailwind CSS.
*   **Interaction Summary:** Purely displays the count provided by the Livewire component.

*(Further Livewire analysis pending...)*

### `Pitch\Component\UpdatePitchStatus` Analysis

**Status:** Complete.

**File:** `app/Livewire/Pitch/Component/UpdatePitchStatus.php`

Handles the UI and actions for the *project owner* reviewing a pitch submission.

*   **Purpose:** Provides buttons/logic for Approve, Deny, Request Revisions actions on a pitch snapshot.
*   **Dependencies:** Models (`Pitch`, `Snapshot`), `PitchWorkflowService`, `Toaster`, `Log`, `Auth`, `AuthorizesRequests`.
*   **State:** Holds `$pitch`, `$status`, feedback/reason inputs (`$denyReason`, `$revisionFeedback`), target snapshot ID (`$currentSnapshotIdToActOn`).
*   **Interaction with Modals:** Uses dispatched events (`openConfirmDialog`, `confirm*`) to interact with JS/AlpineJS confirmation modals for collecting reasons/feedback.
*   **Workflow Actions (Delegated):**
    *   Listens for modal confirmation events (`confirmApproveSnapshot`, etc.).
    *   Authorizes the action using policies (`approveSubmission`, `denySubmission`, `requestRevisions`).
    *   Calls the corresponding method on `PitchWorkflowService` to execute the action.
    *   Handles exceptions, shows Toasters, dispatches update events (`pitchStatusUpdated`, `snapshot-status-updated`).
*   **Other Actions:** Includes `approveInitialPitch` (for legacy `PENDING` status), `cancelSubmission` (likely misplaced, as it's a producer action), and `changeStatus`/`returnToReadyForReview` (potential overlap with `PitchWorkflowService`).
*   **Rendering:** Returns `livewire.pitch.component.update-pitch-status` view.

**Interaction:** Acts as the control panel for project owner review actions. Relies on JS modals for confirmation/input and delegates core logic to `PitchWorkflowService`. Contains some potentially redundant/misplaced status change logic.

**View Analysis (`resources/views/livewire/pitch/component/update-pitch-status.blade.php`):**
*   **Structure:** Renders buttons based on the current pitch status. Includes `<livewire:pitch.component.confirm-status-change />` for modals.
*   **Styling:** Tailwind/DaisyUI button styles.
*   **Actions:** Uses `wire:click` for all status change actions (e.g., `changeStatus`, `requestSnapshotApproval`, `requestRevisions`, `returnToReadyForReview`), correctly triggering methods in the Livewire component.
*   **Modal Interaction:** Component methods dispatch `openConfirmDialog` browser event. Inline JS listens for this event and then dispatches `Livewire.dispatch('openModal', ...)` to activate the embedded `ConfirmStatusChange` modal component.
*   **Interaction Summary:** Properly uses Livewire actions for triggering logic. Modal interaction is handled via Livewire -> JS -> Livewire events.

*(Further Livewire analysis pending...)*

### `Pitch\Component\FeedbackConversation` Analysis

**Status:** Complete.

**File:** `app/Livewire/Pitch/Component/FeedbackConversation.php`

Displays the feedback conversation history for a pitch's review cycles.

*   **Purpose:** Renders a chronological view combining project owner feedback (revisions, denials) and producer responses.
*   **Dependencies:** Models (`Pitch`, `PitchSnapshot`, `PitchEvent`), Livewire (`Component`), `Log`.
*   **State:** Holds the `$pitch` model.
*   **Conversation Logic (`getConversationItemsProperty`)**: Computed property that:
    *   Fetches relevant snapshots.
    *   Iterates snapshots, extracting producer `response_to_feedback`
# Livewire Component Classes (`app/Livewire/`)

This document provides an analysis of the Livewire PHP component classes found in the `app/Livewire/` directory.

## `ManageProject.php`

*   **Purpose:** Acts as the primary component for managing an existing `Project`. It handles editing project details, managing associated project files (uploading, deleting, setting preview track), publishing/unpublishing the project, and displaying storage usage.
*   **Dependencies:**
    *   Models: `Project`, `ProjectFile`
    *   Livewire Features: `Component`, `WithFileUploads`
    *   Facades: `Storage`, `Toaster`, `Auth`, `Log`
    *   Services: `ProjectManagementService`, `FileManagementService`
    *   Forms: `ProjectForm` (from `App\Livewire\Forms\ProjectForm`)
    *   Exceptions: `AuthorizationException`, `FileUploadException`, `StorageLimitException`, `FileDeletionException`
*   **Key Public Properties:**
    *   `$project`: The `Project` model being managed.
    *   `$form`: An instance of `ProjectForm` holding the editable project data.
    *   `$hasPreviewTrack`, `$audioUrl`: Manage the project's preview track state and URL.
    *   `$isUploading`, `$tempUploadedFiles`, `$fileSizes`, etc.: Manage the visual file upload queue.
    *   `$isProcessingQueue`, `$uploadingFileKey`, `$uploadProgress`, etc.: Manage sequential file upload state.
    *   `$storageUsedPercentage`, `$storageLimitMessage`, `$storageRemaining`: Display storage usage information.
    *   `$showDeleteModal`: Controls visibility of a project deletion confirmation.
*   **Key Methods:**
    *   `mount()`: Initialization, authorization, loads project data into the component and `$form` object.
    *   `publish() / unpublish()`: Handles project status changes via the `Project` model.
    *   `togglePreviewTrack() / clearPreviewTrack()`: Manages the preview track using `FileManagementService`.
    *   `updatedTempUploadedFiles() / queueFilesForUpload() / processNextFileInQueue() / uploadSuccess() / uploadFailed() / finishUploadProcess()`: Handle the file upload lifecycle (selection, queuing, processing, feedback).
    *   `removeUploadedFile() / deleteFile()`: Manage files in the queue or persisted files using `FileManagementService`.
    *   `getDownloadUrl()`: Generates download links via `FileManagementService`.
    *   `updateProjectDetails()`: Updates the project using `ProjectManagementService` and data from `$form`.
    *   `render()`: Renders the corresponding Blade view.
*   **Interaction Model:** Data binding via `$form`, user actions trigger methods (`wire:click`), uses Livewire file uploads, delegates logic to Services, provides feedback via Toaster, dispatches browser events.
*   **Summary:** A comprehensive manager for a single project, centralizing editing, file handling, and status changes. Relies heavily on Service classes and a Livewire Form Object.

## `CreateProject.php`

*   **Purpose:** Handles both the creation of new projects and the editing of existing projects.
*   **Status:** **Potentially Deprecated/Redundant.** Functionality largely overlaps with `ManageProject.php`, especially for editing. File/image handling seems less refined than in `ManageProject.php`. The component structure (`$project` + `$originalProject` + `$form`) is more complex.
*   **Dependencies:**
    *   Models: `Project`
    *   Livewire Features: `Component`, `WithFileUploads`, `Rule`, `On`
    *   Facades: `Storage`, `Log`, `Toaster`
    *   Services: `ProjectManagementService`
    *   Forms: `ProjectForm`
    *   Exceptions: `ProjectCreationException`, `ProjectUpdateException`, `AuthorizationException`
*   **Key Public Properties:**
    *   `$project`: The `Project` model being created/edited.
    *   `$originalProject`: Stores original project data in edit mode.
    *   `$form`: Instance of `ProjectForm`.
    *   `$isEdit`: Flag for create vs. edit mode.
    *   `$projectImage`, `$track`, `$audioUrl`, `$deletePreviewTrack`: Manage image and preview track display and uploads/deletions.
*   **Key Methods:**
    *   `mount()`: Initializes component for create or edit mode, populates form if editing.
    *   `revertImage() / clearTrack() / updatedTrack()`: Handle basic image/track upload interactions and display.
    *   `save()`: Validates form, transforms data (collaboration types), calls `ProjectManagementService` to create/update, handles image/track upload/deletion, provides feedback, redirects.
    *   `render()`: Renders the associated Blade view.
*   **Interaction Model:** Form binding, file uploads via Livewire, delegates core logic to `ProjectManagementService`, uses Toaster for feedback, dispatches events.
*   **Comparison with `ManageProject.php`:** Shares `ProjectForm` and `ProjectManagementService`. `ManageProject` is edit-only with better file handling. `CreateProject` handles both create/edit but with simpler file logic. Potential for refactoring/redundancy.

## `EmailTestForm.php`

*   **Purpose:** Provides a form for sending test emails using specified templates and variables. Likely for development/admin use.
*   **Dependencies:**
    *   Models: `EmailTest`
    *   Services: `EmailService`
    *   Livewire Features: `Component`
*   **Key Public Properties:** `$email`, `$subject`, `$template`, `$variables` (array), `$variableKey`, `$variableValue` (for adding variables), `$status`, `$message` (for feedback).
*   **Validation:** Basic rules for email, subject, template.
*   **Key Methods:**
    *   `addVariable() / removeVariable()`: Manage the `$variables` array.
    *   `sendTest()`: Validates input, creates an `EmailTest` log record, calls `EmailService->sendTestEmail()`, updates the log record with the result, sets status/message properties for feedback.
    *   `render()`: Renders the associated Blade view.
*   **Interaction Model:** Form binding, buttons for managing variables and sending, displays feedback, interacts with `EmailService` and `EmailTest` model.
*   **Summary:** Utility component for testing email sending, allowing specification of recipient, subject, template, and variables, with logging and feedback.

## `NotificationList.php`

*   **Purpose:** Fetches and displays a list of user notifications, handles read status updates, pagination (load more), and real-time updates.
*   **Dependencies:**
    *   Models: `Notification`
    *   Facades: `Auth`
    *   Livewire Features: `Component`
*   **Key Public Properties:** `$notifications` (collection), `$hasUnread` (boolean), `$showDropdown` (boolean), `$notificationLimit` (integer).
*   **Listeners:** `notificationRead` (refreshes), `echo-private:notifications.{userId},NotificationCreated` (calls `refreshNotifications`).
*   **Key Methods:**
    *   `mount()`: Loads initial notifications.
    *   `loadNotifications()`: Fetches notifications based on limit, updates `$hasUnread`.
    *   `updatedShowDropdown()`: Reloads notifications when dropdown visibility changes.
    *   `markAsRead($id)`: Marks a single notification as read, dispatches event.
    *   `markAllAsRead()`: Marks all unread notifications as read, reloads, dispatches event.
    *   `getListeners()`: Dynamically sets listeners (includes Echo listener only if authenticated).
    *   `refreshNotifications()`: Reloads notifications (triggered by Echo).
    *   `loadMoreNotifications()`: Increases limit and reloads.
    *   `render()`: Renders the associated Blade view.
*   **Interaction Model:** Loads notifications initially and on show. Handles user actions for marking read and loading more. Updates in real-time via Echo. Dispatches `notificationRead` event.
*   **Summary:** Manages the notification list display, read states, pagination, and real-time updates.

## `ProfileEditForm.php`

*   **Purpose:** Allows editing a subset of user profile information (username, bio, website, location, social links).
*   **Status:** **Likely Deprecated/Redundant.** Covers much less functionality than `UserProfileEdit.php`. Handles social links differently (extracts username, reconstructs URL). Uses session flash instead of Toaster. Lacks file uploads, dynamic lists, completion logic.
*   **Dependencies:**
    *   Models: `User`
    *   Facades: `Auth`, `Log`
    *   Livewire Features: `Component`
*   **Key Public Properties:** `$user`, `$username`, `$bio`, `$website`, `$location`, `$social_links` (array of usernames/handles).
*   **Validation:** Defined within `updateProfile`, covers the limited set of fields.
*   **Key Methods:**
    *   `mount()`: Loads user, populates properties, extracts social usernames using `getSocialUsername()`.
    *   `getSocialUsername()`: Helper to extract username/handle from a social URL.
    *   `updateProfile()`: Validates, formats website URL, reconstructs full social URLs, updates `User` model, sets flash messages.
    *   `render()`: Renders the associated Blade view.
*   **Interaction Model:** Form binding, submission triggers update, feedback via session flash.
*   **Summary:** Simpler profile edit form, likely superseded by `UserProfileEdit.php`.

## `SnapshotFilePlayer.php`

*   **Purpose:** Displays an audio player (controlled by JS) for a `PitchFile` ("snapshot") and its timestamped comments. Allows seeking via comments.
*   **Dependencies:**
    *   Models: `PitchFile`, `PitchFileComment`
    *   Livewire Features: `Component`, `On`
*   **Key Public Properties:** `$file` (PitchFile model), `$comments` (collection), `$currentTimestamp`, `$duration` (unused in PHP?), `$showDownloadButton` (boolean).
*   **Listeners:** `waveformReady` (calls `onWaveformReady`), `refresh`.
*   **Key Methods:**
    *   `mount()`: Initializes with file, loads comments.
    *   `loadComments()`: Fetches associated comments with user, ordered by timestamp.
    *   `onWaveformReady()`: Currently just reloads comments.
    *   `seekTo($timestamp)`: Dispatches `seekToPosition` browser event for frontend JS player control.
    *   `render()`: Renders the associated Blade view.
*   **Interaction Model:** Loads file/comments. Comment clicks trigger `seekTo`, dispatching event to JS. Relies on frontend JS for player initialization and control.
*   **Summary:** Displays a PitchFile's comments alongside a player, facilitating seeking by dispatching events to frontend JavaScript.

## `ProjectListItem.php`

*   **Purpose:** Displays a single project entry in a list. Formats project data (types, genre) and manages description visibility.
*   **Dependencies:**
    *   Livewire Features: `Component`
    *   (Implicit) Models: `Project`
*   **Key Public Properties:** `$project` (Project model), `$showFullDescription` (boolean), `$formattedCollaborationTypes` (array), `$formattedProjectType` (string), `$formattedGenre` (string).
*   **Key Methods:**
    *   `mount()`: Initializes with project, calls formatters.
    *   `formatProjectType()`, `formatGenre()`, `formatCollaborationTypes()`: Prepare raw data for display using `formatTypeString()`.
    *   `formatTypeString()`: Helper to replace underscores and capitalize words.
    *   `toggleDescription()`: Toggles `$showFullDescription`.
    *   `viewProject()`: Redirects to the project's detail page.
    *   `render()`: Renders the associated Blade view.
*   **Interaction Model:** Displays formatted data. User can toggle description visibility (`wire:click`) and navigate to project details (`wire:click`).
*   **Summary:** Presentation component for a single list item, handling data formatting and description toggling.

## `ProjectsComponent.php`

*   **Purpose:** Main component for displaying projects. Handles fetching, filtering (genre, status, type, search), sorting, pagination, and view mode (list/card).
*   **Dependencies:**
    *   Models: `Project`
    *   Livewire Features: `Component`, `WithPagination`, `On`
*   **Key Public Properties:** `$genres`, `$statuses`, `$projectTypes` (filter arrays), `$search` (string), `$sortBy` (string), `$perPage` (int), `$viewMode` (string).
*   **Query String:** Syncs filters, sort, search, and view mode state with URL.
*   **Listeners:** `filters-updated` (calls `applyFilters`).
*   **Key Methods:**
    *   `render()`: Builds query based on filters/sort, fetches paginated projects (excluding unpublished), renders view.
    *   `updated...()` hooks: Reset pagination when filters/sort change.
    *   `applyFilters()`: Updates filter properties from event data.
    *   `clearFilters()`: Resets all filters and sorting.
    *   `loadMore()`: Increases `$perPage` (for potential "load more" functionality).
    *   `removeGenre()`, `removeStatus()`, `removeProjectType()`: Removes individual filter items.
    *   `toggleViewMode()`: Switches between 'list' and 'card' views.
*   **Interaction Model:** Displays projects based on state (synced with URL). Filters/sort updated via inputs or events. Search triggers filtering. Individual filters can be removed. View mode toggles. Standard pagination links. Potential "load more" interaction.
*   **Summary:** Central component for browsing projects with filtering, sorting, pagination, search, and view mode options, syncing state with the URL.

## `FiltersProjectsComponent.php`

*   **Purpose:** Manages the state of selected project filters (genres, statuses, types) and communicates changes via events. Designed to work with `ProjectsComponent`.
*   **Dependencies:**
    *   Livewire Features: `Component`, `On`
*   **Key Public Properties:** `$genres`, `$statuses`, `$projectTypes` (filter arrays, likely bound to UI inputs).
*   **Listeners:** `filters-updated` (calls `updateFilters` to sync own state).
*   **Key Methods:**
    *   `render()`: Renders the filter UI view.
    *   `dispatchFiltersUpdated()`: Dispatches `filters-updated` event with current filter state.
    *   `updated...()` hooks: Call `dispatchFiltersUpdated` when filter properties change.
    *   `updateFilters()`: Updates component's state based on received `filters-updated` event data (for synchronization).
    *   `clearFilters()`: Resets filter properties and dispatches update.
*   **Interaction Model:** UI elements bind to properties. Changes dispatch `filters-updated` event (consumed by `ProjectsComponent`). Listens for the same event to potentially sync its own UI if filters change externally. Provides clear filters functionality.
*   **Summary:** State manager for filter UI, communicating changes to `ProjectsComponent` via events and listening for updates to maintain synchronization.

## `NotificationCount.php`

*   **Purpose:** Fetches and displays the count of unread notifications for the user. Updates via events/Echo.
*   **Dependencies:**
    *   Models: `Notification`
    *   Facades: `Auth`
    *   Livewire Features: `Component`
*   **Key Public Properties:** `$count` (integer).
*   **Listeners:** `notificationRead` ($refresh), `echo:notifications,NotificationCreated` ($refresh) - *Note: Uses public Echo channel.*
*   **Key Methods:**
    *   `mount()`: Loads initial count.
    *   `updateCount()`: Queries DB for unread notification count for the auth user.
    *   `getListeners()`: Defines listeners.
    *   `render()`: Updates count, renders view.
*   **Interaction Model:** Fetches count on mount/render. Refreshes count on `notificationRead` event or new notification via Echo. Displays the count.
*   **Summary:** Simple component to display the unread notification count, kept updated via events and Echo.

## `AudioPlayer.php`

*   **Purpose:** Acts as a Livewire controller/wrapper for a frontend JS audio player (likely WaveSurfer). Manages audio URL state and communicates with JS via events.
*   **Dependencies:**
    *   Livewire Features: `Component`, `On`
*   **Key Public Properties:** `$audioUrl` (string), `$identifier` (unique string), `$isPreviewTrack` (boolean), `$isInCard` (boolean), `$mainDivClass` (CSS classes for visibility), `$audioPlayerInitialized` (flag).
*   **Listeners:** `audioUrlUpdated` (calls `audioUrlUpdated` method), `track-clear-button` (calls `clearTrack` method).
*   **Key Methods:**
    *   `mount()`: Initializes properties, generates identifier, sets initial visibility.
    *   `audioUrlUpdated()`: Updates URL, makes player visible, dispatches `clear-track` and `url-updated` events for JS.
    *   `clearTrack()`: Hides player, dispatches `clear-track` event for JS.
    *   `render()`: Dispatches `audio-player-rendered-{identifier}` event on first render for JS initialization. Renders view (`livewire.project.component.audio-player`).
*   **Interaction Model:** Manages URL state. Communicates with frontend JS via dispatched browser events (`audio-player-rendered-*`, `url-updated`, `clear-track`) for player initialization, URL loading, and cleanup. Listens for events from parent components to update URL or clear track.
*   **Summary:** Livewire controller for a JS audio player, managing state and coordinating with the frontend via browser events.

## `ProjectCard.php`

*   **Purpose:** Displays a single project in a card format and handles navigation on click.
*   **Dependencies:**
    *   Livewire Features: `Component`
    *   (Implicit) Models: `Project`
*   **Key Public Properties:** `$project` (Project model), `$isDashboardView` (boolean, purpose unclear from PHP).
*   **Key Methods:**
    *   `cardClickRoute()`: Redirects to the project detail page (`projects.show`). Likely triggered by `wire:click` on the card.
    *   `render()`: Renders the associated Blade view, passing properties.
*   **Interaction Model:** Receives project data. Card click triggers navigation via `cardClickRoute()`.
*   **Summary:** Simple presentation component for a project card, handling data display and navigation.

## `AuthDropdown.php`

*   **Purpose:** Provides a dropdown UI for user login and registration, and handles logout.
*   **Dependencies:**
    *   Models: `User`
    *   Facades: `Auth`, `Hash`
    *   Livewire Features: `Component`
*   **Key Public Properties:** `$isOpen` (boolean), `$tab` ('login'/'register'), `$loginForm` (array), `$registerForm` (array).
*   **Listeners:** `outsideClick` (calls `closeDropdown`).
*   **Key Methods:**
    *   `closeDropdown()`: Hides dropdown.
    *   `switchTab()`: Changes active form, resets form data.
    *   `toggleOpen()`: Toggles dropdown visibility, sets active tab.
    *   `hydrated()`: Dispatches event for JS to set up outside click listener.
    *   `logoutForm()`: Logs user out, redirects back.
    *   `submitLoginForm()`: Validates, attempts login via `Auth::attempt()`, handles success/failure.
    *   `submitRegisterForm()`: Validates, creates user via `User::create()`, logs in via `Auth::login()`, redirects.
    *   `render()`: Renders the associated Blade view.
*   **Interaction Model:** Dropdown toggled by navbar clicks. Tabs switch forms. Forms bind to properties, submission triggers login/register logic. Validation errors shown. Success leads to login and redirect. Logout button available when logged in. Outside click closes dropdown (via JS).
*   **Summary:** Self-contained login/registration/logout dropdown component for navigation.

## `UploadProjectComponent.php`

*   **Purpose:** Basic two-step project creation (details then Dropzone upload area).
*   **Status:** **Highly Likely Deprecated/Unused.** Superseded by `CreateProject`/`ManageProject`. Very basic fields/validation, relies on client-side Dropzone for uploads, not integrated Livewire file handling.
*   **Dependencies:**
    *   Models: `Project`
    *   Livewire Features: `Component`, `WithFileUploads`
*   **Key Public Properties:** `$step` (int), `$projectName`, `$projectGenre`, `$projectDescription`, `$projectImage` (step 1 form), `$files` (unused?), `$projectId`, `$projectSlug` (set after save).
*   **Key Methods:**
    *   `render()`: Renders view.
    *   `saveProject()`: Validates step 1, creates `Project`, handles basic image upload (`store()`), saves, advances to step 2.
*   **Interaction Model:** Step 1 form submits to `saveProject`. Step 2 displays Dropzone configured with `$projectId` (uploads handled client-side).
*   **Summary:** Basic, likely deprecated project creation component using Dropzone for uploads. Superseded by newer components.

## `StatusButton.php`

*   **Purpose:** Passes status and type data to its view for displaying a styled status indicator.
*   **Status:** **Functionally Redundant.** Duplicates the functionality of the Blade component `resources/views/components/project-status-button.blade.php`. Prefer the Blade component.
*   **Dependencies:**
    *   Livewire Features: `Component`
*   **Key Public Properties:** `$status` (string), `$type` (string, e.g., 'inline').
*   **Key Methods:**
    *   `render()`: Renders view, passing properties.
*   **Interaction Model:** Purely presentational, passes data to view.
*   **Summary:** Minimal component, redundant due to existing Blade component.

## `StarRating.php`

*   **Purpose:** Interactive star rating input that updates the `rating` on an associated `Mix` model.
*   **Status:** **Potentially Deprecated/Unused.** Tied specifically to the `Mix` model, which appears to be an unused concept.
*   **Dependencies:**
    *   Models: `Mix`
    *   Livewire Features: `Component`
*   **Key Public Properties:** `$rating` (int), `$mix` (Mix model).
*   **Key Methods:**
    *   `mount()`: Initializes with rating and Mix model.
    *   `setRating()`: Updates `$rating` property and persists the change to the `$mix` model via `$mix->update()`.
    *   `render()`: Renders view.
*   **Interaction Model:** Clicking a star in the view triggers `setRating()`, updating state and the database.
*   **Summary:** Star rating component specifically for `Mix` models, likely unused if `Mix` is deprecated.

## `ProjectTracks.php`

*   **Purpose:** Displays a list of project files (`ProjectFile`) with audio players. Manages an index for unique element IDs.
*   **Status:** **Likely Deprecated/Unused.** View uses older, potentially broken player JS. File management seems centralized in `ManageProject.php` now.
*   **Dependencies:**
    *   Models: `Project`, `ProjectFile`
    *   Livewire Features: `Component`
*   **Key Public Properties:** `$project` (Project model), `$files` (collection), `$audioIndex` (int counter), `$showTracks` (boolean for Alpine toggle).
*   **Key Methods:**
    *   `mount()`: Initializes with project, loads files, resets index.
    *   `incrementAudioIndex()`: Increments index (used in view loop).
    *   `render()`: Renders view.
*   **Interaction Model:** Passes data to view. View uses Alpine for toggle, calls `incrementAudioIndex` in loop. Player logic is in view JS.
*   **Summary:** Simple component for displaying project files using older player logic. Likely deprecated.

## `ProjectMixes.php`

*   **Purpose:** Passes Project model to view for displaying "mixes". Manages an index for unique element IDs.
*   **Status:** **Likely Deprecated/Unused.** Relies on the `Mix` concept/relationship, which seems outdated. View also appeared deprecated.
*   **Dependencies:**
    *   Models: `Project`, `Mix` (implicitly via view)
    *   Livewire Features: `Component`
*   **Key Public Properties:** `$project` (Project model), `$audioIndex` (int counter).
*   **Key Methods:**
    *   `mount()`: Initializes with project.
    *   `incrementAudioIndex()`: Increments index (used in view loop).
    *   `render()`: Renders view (which accesses `$project->mixes`).
*   **Interaction Model:** Passes project to view. View loops through mixes, likely calls `incrementAudioIndex`. Player/rating logic handled in view.
*   **Summary:** Minimal component for displaying "mixes", reliant on the likely deprecated Mix concept.

## Pitch Subdirectory (`app/Livewire/Pitch/`)

### `CompletePitch.php` (in Pitch/)

*   **Purpose:** Handles the action of marking a `Pitch` as complete, including optional feedback and triggering potential payment flows.
*   **Dependencies:**
    *   Models: `Pitch`
    *   Services: `PitchCompletionService`
    *   Exceptions: `CompletionValidationException`, `UnauthorizedActionException`
    *   Facades: `Log`, `Auth`
    *   Livewire Features: `Component`
*   **Key Public Properties:** `$pitch` (Pitch model), `$feedback` (string).
*   **Key Methods:**
    *   `mount()`: Initializes with Pitch model.
    *   `completePitch()`: Authorizes, calls `PitchCompletionService->completePitch()`, dispatches `pitch-completed` and potentially `openPaymentModal` events, flashes messages, handles exceptions.
    *   `render()`: Renders view.
*   **Interaction Model:** Button click triggers `completePitch`. Delegates logic to service. Provides feedback via flash messages. Dispatches events for UI updates/payment modal.
*   **Summary:** Component orchestrating the pitch completion process, handling authorization, feedback, service delegation, and event dispatching. *Note: Likely superseded by the version in `Component/`.*

### `PaymentDetails.php` (in Pitch/)

*   **Purpose:** Passes a `Pitch` model to its view for displaying payment-related details.
*   **Dependencies:**
    *   Models: `Pitch`
    *   Livewire Features: `Component`
*   **Key Public Properties:** `$pitch` (Pitch model).
*   **Key Methods:**
    *   `mount()`: Initializes with Pitch model.
    *   `render()`: Renders view, passing pitch property.
*   **Interaction Model:** Purely presentational. Passes Pitch model to the view.
*   **Summary:** Minimal presentation component for displaying pitch payment details.

### Component Subdirectory (`app/Livewire/Pitch/Component/`)

#### `CompletePitch.php` (in Component/)

*   **Purpose:** Manages the workflow for completing an *approved* pitch, including modal confirmation, checks for other approved pitches, feedback collection, and triggering completion logic via service.
*   **Status:** Appears to be the **active version**, likely replacing the simpler `CompletePitch.php` in the parent directory.
*   **Dependencies:** Models (`Pitch`, `Project`, `PitchFeedback`), Services (`PitchCompletionService`, `NotificationService`, `PitchWorkflowService`), Exceptions (various Pitch-related, `AuthorizationException`), Facades (`Auth`, `Log`, `DB`, `Toaster`), Helpers (`RouteHelpers`), Livewire (`Component`).
*   **Key Public Properties:** `$pitch`, `$feedback`, `$finalComments` (feedback strings), `$hasOtherApprovedPitches`, `$otherApprovedPitchesCount` (flags/counts), `$showCompletionModal` (boolean), `$rating` (int/null), `$hasCompletedPitch` (boolean).
*   **Key Methods:**
    *   `mount()`: Initializes state, checks for other approved pitches.
    *   `checkForOtherApprovedPitches()`: DB query for count.
    *   `isAuthorized()`: Manual authorization check (owner, status).
    *   `openCompletionModal()`: Authorizes, checks competing pitches, shows modal.
    *   `closeCompletionModal()`: Hides modal.
    *   `debugComplete()`: (Likely intended name `completePitch`) Authorizes (`$this->authorize`), calls `PitchCompletionService`, handles feedback/events/redirect, manages exceptions.
    *   `render()`: Renders view for the component/modal.
*   **Interaction Model:** Button triggers `openCompletionModal`. Modal confirmation triggers `debugComplete`. Service handles logic. Feedback via Toaster. Events (`pitchStatusUpdated`, `openPaymentModal`) dispatched.
*   **Summary:** The main component for the pitch completion workflow, including modal confirmation and service delegation.

#### `ManagePitch.php`

*   **Purpose:** Primary component for managing an existing `Pitch`. Handles file management (upload queue, delete, download), status transitions (submit, cancel), feedback display, history/snapshots, and pitch deletion.
*   **Dependencies:** Models (Pitch, Project, File, Snapshot, Event, etc.), Services (PitchWorkflow, FileManagement, PitchService, Notification), Exceptions (various), Facades (Auth, Storage, Log, Toaster), Traits (WithFileUploads, WithPagination, AuthorizesRequests), Jobs (GenerateAudioWaveform), Helpers (RouteHelpers), Livewire (Component, On).
*   **Key Public Properties (Highlights):** `$pitch` (Pitch model), `$project`, file upload queue/state properties (`$tempUploadedFiles`, `$isProcessingQueue`, `$uploadProgress`, etc.), storage tracking (`$storageUsedPercentage`, etc.), `$responseToFeedback`, `$statusFeedbackMessage`, `$snapshots`, `$events`, modal/deletion state (`$showDeleteModal`, `$showDeletePitchModal`).
*   **Key Methods (Highlights):**
    *   `mount()`: Initializes, loads relations, resets state, fetches feedback.
    *   `render()`: Fetches paginated files/events, renders view.
    *   File Upload Methods: Manage queue, progress, interaction with services/JS.
    *   File Management Methods: Delete (with modal), download, save note via `FileManagementService`.
    *   Status Transition Methods (`submitForReview`, `cancelPitchSubmission`): Use `PitchWorkflowService` for status changes.
    *   Pitch Deletion Methods (`confirmDeletePitch`, `deletePitch`): Use `PitchService` with modal confirmation.
    *   `getLatestStatusFeedback()`: Retrieves revision/denial feedback.
    *   `updateStorageInfo()`: Refreshes storage usage.
*   **Interaction Model:** Central UI for pitch management. Queued file uploads. File/Pitch deletion via modals. Status changes via buttons using services. Displays history, feedback. Feedback via Toaster.
*   **Summary:** Comprehensive component for managing an individual pitch's lifecycle, files, status, and history, mirroring `ManageProject.php`'s structure.

#### `FeedbackConversation.php`

*   **Purpose:** Assembles and displays the chronological feedback conversation thread for a pitch (revision requests, denials, responses, completion feedback).
*   **Dependencies:** Models (`Pitch`, `PitchSnapshot`, `PitchEvent`), Facades (`Log`), Livewire (`Component`).
*   **Key Public Properties:** `$pitch` (Pitch model).
*   **Computed Properties:** `conversationItems` (core logic: queries snapshots/events, extracts/formats feedback/responses, sorts by date).
*   **Key Methods:**
    *   `mount()`: Initializes with Pitch model.
    *   `getSnapshotFeedback()`: Helper to query PitchEvent and parse feedback content for a given snapshot.
    *   `render()`: Renders view (which uses `conversationItems`).
*   **Interaction Model:** Presentational. Computes conversation history from DB records. Does not handle submitting new feedback.
*   **Summary:** Displays the feedback history for a pitch by querying and formatting data from related snapshots and events.

#### `ConfirmStatusChange.php`

*   **Purpose:** Provides a reusable confirmation modal dialog for various pitch status changes (approve, deny, revisions, complete, cancel).
*   **Dependencies:** Facades (`Log`, `Toaster`), Livewire (`Component`).
*   **Key Public Properties:** `$pitch` (context), `$showConfirmModal` (boolean), `$pendingAction` (string), `$confirmMessage` (string), `$actionData` (array for details like reason/feedback), `$actionLabel` (string), `$confirmButtonClass` (string).
*   **Listeners:** `openConfirmDialog` (calls `openConfirmationModal`), `pitchStatusUpdated` (calls `closeConfirmationModal`).
*   **Key Methods:**
    *   `mount()`: Initializes with pitch.
    *   `openConfirmationModal()`: Sets state based on action, sets message/button style, shows modal.
    *   `closeConfirmationModal()`: Hides modal, resets state.
    *   `confirmAction()`: Validates required input (reason), dispatches action-specific event (e.g., `confirmApproveSnapshot`, `confirmCompletePitch-{id}`) with data to the responsible component, closes modal.
    *   `render()`: Renders modal view.
*   **Interaction Model:** Activated by `openConfirmDialog` event. Displays modal with dynamic text/style. Confirmation button triggers `confirmAction`, which dispatches event to delegate execution. Closes on confirmation or `pitchStatusUpdated` event.
*   **Summary:** Reusable confirmation modal for critical pitch actions, delegating execution via events.

#### `UpdatePitchStatus.php`

*   **Purpose:** Manages pitch status transitions (approve, deny, revisions, cancel). Initiates confirmation via modal and executes confirmed actions via `PitchWorkflowService`.
*   **Dependencies:** Models (Pitch, Snapshot, Event), Services (PitchWorkflow, Notification), Exceptions (various), Facades (Auth, DB, Log, Toaster), Traits (AuthorizesRequests), Livewire (Component).
*   **Key Public Properties:** `$pitch`, `$status`, `$hasCompletedPitch`, `$denyReason`, `$revisionFeedback`, `$currentSnapshotIdToActOn`.
*   **Listeners:** `confirmApproveSnapshot` => `approveSnapshot`, `confirmDenySnapshot` => `denySnapshot`, `confirmCancelSubmission` => `cancelSubmission`, `confirmRequestRevisions` => `requestRevisionsAction`, `snapshot-status-updated` => `$refresh`.
*   **Key Methods:**
    *   `mount()`: Initializes state.
    *   `reviewPitch()`: Redirects to snapshot review page.
    *   `request...()` methods: Triggered by UI buttons, dispatch `openConfirmDialog` to modal.
    *   Action methods (`approveSnapshot`, `denySnapshot`, `requestRevisionsAction`, `cancelSubmission`, `approveInitialPitch`): Triggered by listeners after confirmation. Authorize, call `PitchWorkflowService`, handle feedback (Toaster), dispatch `pitchStatusUpdated`, refresh state.
    *   `render()`: Renders view with action buttons.
*   **Interaction Model:** Buttons trigger `request...` methods -> dispatch to modal -> user confirms -> modal dispatches `confirm...` event -> listeners trigger action methods -> call service -> update UI/state.
*   **Summary:** Orchestrates pitch status changes, handling confirmation flow initiation and executing actions via `PitchWorkflowService` after confirmation.

#### `DeletePitch.php`

*   **Purpose:** Provides a confirmation modal (requiring user to type "delete") before initiating pitch deletion via redirect.
*   **Dependencies:** Facades (`Toaster`), Models (`Pitch`), Livewire (`Component`).
*   **Key Public Properties:** `$pitch`, `$showDeleteConfirmation` (boolean), `$deleteConfirmInput` (string).
*   **Key Methods:**
    *   `mount()`: Initializes with pitch.
    *   `confirmDelete()`: Shows modal.
    *   `cancelDelete()`: Hides modal, resets input.
    *   `deletePitch()`: Validates confirmation input. If correct, **redirects** to `projects.pitches.destroyConfirmed` route (deletion handled by controller).
    *   `render()`: Renders view with button and modal.
*   **Interaction Model:** Button shows modal -> user types "delete" -> confirm button triggers validation & redirect to deletion route.
*   **Summary:** Handles the UI confirmation step for pitch deletion, redirecting to a controller route for the actual deletion logic.

#### `PitchHistory.php`

*   **Purpose:** Fetches and displays the event history for a pitch. Provides helpers for icons and styling.
*   **Dependencies:** Models (`Pitch`, `PitchEvent`), Livewire (`Component`).
*   **Key Public Properties:** `$pitch`, `$events` (collection), `$showHistory` (boolean).
*   **Key Methods:**
    *   `mount()`: Initializes with pitch, loads events.
    *   `loadEvents()`: Fetches pitch events with user/snapshot relations, ordered descending.
    *   `toggleHistory()`: Toggles visibility, reloads events if showing.
    *   `getEventIcon()`: Returns simple icon name (less used?).
    *   `getIconPath()`: Returns SVG path data for event type icon.
    *   `getEventClass()`: Returns Tailwind text color class based on event type/status.
    *   `render()`: Renders view.
*   **Interaction Model:** Button toggles history visibility. View iterates through `$events`, using helpers for display styling.
*   **Summary:** Presentation component for displaying pitch event history with icon/color helpers.

*(End of `Pitch/Component/` analysis.)*

### Snapshot Subdirectory (`app/Livewire/Pitch/Snapshot/`)

#### `ShowSnapshot.php`

*   **Purpose:** Displays a specific historical `PitchSnapshot`, including its files (via view) and a focused feedback conversation relevant *only* to this snapshot.
*   **Dependencies:** Models (Pitch, Project, Snapshot, Event, User), Facades (Auth, Log), Livewire (Component).
*   **Key Public Properties:** `$pitch`, `$pitchSnapshot`, `$snapshotData`.
*   **Key Methods:**
    *   `mount()`: Handles slug-based route model binding (Project, Pitch, Snapshot), verifies relationships, authorizes user (pitch or project owner).
    *   `render()`: Calls `getConversationThread()`, renders view.
    *   `getConversationThread()`: Builds array of feedback/response items relevant *only* to the current snapshot (response *in* snapshot, feedback *about* snapshot). Sorts by date.
    *   `getCurrentSnapshotFeedback()`: Helper to find feedback (revision/denial event) specifically associated with the current snapshot.
*   **Interaction Model:** Loads snapshot based on route. Authorizes. Computes focused conversation thread. View displays snapshot files and conversation. Primarily presentational.
*   **Summary:** Displays a specific pitch snapshot and its immediately relevant feedback/response thread.

*(End of `Pitch/` subdirectory analysis. `Forms/` pending.)*

## Forms Subdirectory (`app/Livewire/Forms/`)

### `ProjectForm.php`

*   **Purpose:** Defines the properties and validation rules for Project creation/editing forms using a Livewire Form Object.
*   **Type:** Livewire Form Object (`extends Form`).
*   **Dependencies:** Livewire (`Form`, `Rule`).
*   **Properties & Validation Rules:** Defines rules for `$name`, `$artistName`, `$projectType`, `$description`, `$genre`, `$projectImage`, collaboration type booleans, `$budgetType`, `$budget`, `$deadline`, `$track` (single audio file), `$notes`.
*   **Usage:** Instantiated in components (`ManageProject`, `CreateProject`), bound to inputs (`wire:model="form.property"`), validated via `$form->validate()`, populated via `$form->fill()`, data retrieved via `$form->all()`.
*   **Summary:** Encapsulates Project form state and validation logic for use in Livewire components.

*(End of `app/Livewire/` analysis.)* 
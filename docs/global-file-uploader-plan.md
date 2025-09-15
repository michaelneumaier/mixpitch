# Global File Uploader (Persistent, PWA-aware) — Architecture & Implementation Plan

## Goals
- Provide a universal, persistent file uploader that lives at the bottom of the UI (similar to the global audio player) and persists during navigation (PWA-friendly).
- Support drag-and-drop and manual selection from any page-specific upload entry point while uploading from a single global UI/queue.
- Automatically start uploads on selection/drag-drop (no separate confirmation step).
- Fully support multipart uploads to S3 with resume/retry for partially uploaded files.
- Be reusable across contexts: projects, pitches, client portals, and future models.
- Match site styling using Flux UI and align with the global audio player pattern.

## Current State Summary (Findings)
- Uploader implementation:
  - Livewire component `App\Livewire\UppyFileUploader` renders `resources/views/livewire/uppy-file-uploader.blade.php`.
  - Uses `window.createUppy` from `resources/js/uppy-config.js` and `@uppy/aws-s3` (multipart endpoints are custom).
  - Adds S3 multipart calls to `/s3/multipart` for create, GET sign-part, POST complete, and DELETE abort.
  - Backend controller `App\Http\Controllers\CustomUppyS3MultipartController` overrides create to add dynamic folders based on `modelType` and `modelId`. Other routes are provided by the package.
  - After completion, Livewire method `handleUploadSuccess` creates file records via `FileManagementService` for either `Project` or `Pitch` (authorization enforced via policies).
  - Frontend currently sets Uppy meta per component instance: `{ modelId, modelType, context }`.

- PWA and persistence patterns:
  - Service worker is registered in `resources/views/components/pwa-meta.blade.php` and configured in `public/sw.js`.
  - Persistent bottom UI pattern is implemented by the global audio player using `@persist('global-audio-player') @livewire('global-audio-player')` within `resources/views/components/layouts/app-sidebar.blade.php`.
  - Layout reserves bottom space via CSS variable `--global-audio-player-offset` applied to `.main-content` padding.

- Styling system:
  - Flux UI components are used broadly (buttons, menus, cards, etc.).
  - Global audio player uses a sticky bottom bar with mini/full modes and transitions.

## Proposed Architecture

### 1) Global Persistent Uploader Component
- Create `App\Livewire\GlobalFileUploader` with Blade view `resources/views/livewire/global-file-uploader.blade.php`.
- Mount in layout: `resources/views/components/layouts/app-sidebar.blade.php` right next to global audio player using `@persist('global-file-uploader') @livewire('global-file-uploader')`.
- Behavior:
  - Always present when authenticated; hidden until files are added.
  - Renders a sticky bottom bar (mini view) summarizing current queue (count, progress, failures). Expand to full view to see a detailed queue.
  - Updates a shared body CSS var (e.g., `--global-bottom-offset`) to avoid overlapping with the audio player. Offset should be the sum of visible bars.
  - Uses Flux UI (`flux:card`, `flux:button`, `flux:icon`, optional `flux:table`) and mirrors global audio player styles/transitions for consistency.

### 2) JS Upload Manager (Singleton)
- Add `resources/js/global-upload-manager.js` that:
  - Initializes a singleton `window.GlobalUploader` with one Uppy instance for the entire app.
  - Configures Uppy with:
    - `autoProceed: true` (start immediately)
    - `allowMultipleUploads: true`
    - `@uppy/aws-s3` (multipart) with existing endpoints
    - `@uppy/status-bar` and `@uppy/progress-bar` mounted to the global component’s DOM targets
    - `@uppy/store-localstorage` to persist Uppy state across navigation reloads
    - Optionally `@uppy/golden-retriever` to restore files on crash or accidental reloads (subject to browser constraints)
  - Exposes API methods:
    - `setActiveTarget({ modelType, modelId, context })`
    - `openFileDialog(meta)` — opens file picker and assigns the provided meta to selected files
    - `addFiles(files, meta)` — programmatically add File objects and attach per-file meta
    - `resumeAll()` / `pauseAll()` / `cancelAll()` controls
  - Emits Livewire-friendly events:
    - On complete: sends batched `uploadData` to `GlobalFileUploader` Livewire for record creation (re-using `handleUploadSuccess` logic via a shared trait or service).
    - On error: dispatches browser events for Toaster notifications.
  - Handles offline/online events via `window.addEventListener('online'|'offline')` and pauses/resumes accordingly with user prompts.

### 3) Page-Level Delegation Hooks
- Update `resources/views/components/file-management/upload-section.blade.php` (and similar spots) to delegate to the global uploader:
  - Replace in-place Uppy drag/drop with a thin delegation layer:
    - A small card with instructions and a button: “Upload files” → calls `GlobalUploader.openFileDialog({ modelType, modelId, context })`.
    - Make the card a drop target: on dragenter, call `GlobalUploader.setActiveTarget(meta)`; on drop, forward `DataTransfer.files` to `GlobalUploader.addFiles(files, meta)`.
  - Keep Google Drive modal integration as-is; when the modal completes, it already dispatches `filesUploaded` → the global uploader component will listen and toast/refresh as today.

### 4) Backend Reuse
- Continue using `CustomUppyS3MultipartController` for create; allow package-provided routes for sign-part, complete, and abort.
- Confirm those remaining multipart routes are protected by `auth` (if not, add explicit route overrides mirroring the package routes and apply `auth`).
- No schema changes needed. The `FileManagementService` continues to create `ProjectFile`/`PitchFile` based on `modelType`.

### 5) Resume & Retry Strategy
- Enable `@uppy/store-localstorage` to persist Uppy’s internal state (uploadId, key, parts) for `@uppy/aws-s3` multipart.
- On app load, GlobalUploadManager restores state and:
  - If files are mid-upload and online: offer to resume automatically (default: resume).
  - If offline: show queued state and wait for reconnection.
- Provide clear UI controls: Resume All, Pause All, Retry Failed, Clear Completed.

### 6) UI/UX Details (Flux UI)
- Mini bar (bottom sticky):
  - Left: title “Uploads” with a concise progress indicator (e.g., 3 files • 2 uploading • 1 done • 0 failed).
  - Middle: aggregate progress bar.
  - Right: actions (Expand, Pause/Resume All, Cancel All).
- Full view:
  - Queue table or list with per-file rows: name, size, destination (project/pitch), status, speed, ETA, actions (pause/resume/retry/cancel), and progress bar.
  - Footer actions: Clear completed, Retry failed, Close.
- Styling should reflect the global audio player’s rounded, blurred, semi-opaque background and transitions. Respect dark mode.
- Uploader sits above the audio player: when both are visible, anchor the uploader with `bottom` equal to the audio player’s height, and set its z-index above the audio player. Coordinate offsets so content padding equals the sum of both bars.

### 7) Accessibility & Mobile
- Keyboard navigable, aria-live regions for status updates.
- Larger touch targets in mini bar on mobile; ensure expand/collapse interactions are straightforward.
- No global drag overlay initially; limit drag/drop targets to registered upload sections for now.

### 8) Telemetry & Notifications
- Re-use Toaster for success/error notifications. Batch messages to avoid noise during multi-file uploads.
- Log critical errors with enough context to debug upload sessions.

## Implementation Steps

1) Create Global Livewire Component
- `php artisan make:livewire GlobalFileUploader`
- Blade view renders mini and full views using Flux; includes container targets for Uppy StatusBar/ProgressBar.
- Alpine store: `{ isVisible, isExpanded, total, uploading, failed, completed, aggregateProgress }`.
- Event listeners: respond to JS events (upload progress, completed, failed) and to `filesUploaded` for external sources.
- Use shared service logic from `UppyFileUploader::handleUploadSuccess` (extract to a small `HandlesUploadedFiles` trait or call the service directly).

2) Add Global Upload Manager JS
- Create `resources/js/global-upload-manager.js` and import in `resources/js/app.js`.
- Initialize singleton `window.GlobalUploader` once, wire to the Livewire component DOM nodes on first render.
- Use `@uppy/store-localstorage` (and optionally `@uppy/golden-retriever`).
- Configure `AwsS3` using the same endpoints and metadata.
- Emit batch completion to Livewire with `uploadData` shaped like the existing `UppyFileUploader`.

3) Mount in Layout
- In `resources/views/components/layouts/app-sidebar.blade.php`, near the global audio player:
  - `@auth @persist('global-file-uploader') @livewire('global-file-uploader') @endpersist @endauth`
- Update bottom offset logic: manage combined offset when both mini bars are visible.

4) Delegate Existing Upload Sections
- Update `resources/views/components/file-management/upload-section.blade.php`:
  - Button → `x-on:click="window.GlobalUploader?.openFileDialog({ modelType: '{{ get_class($model) }}', modelId: {{ $model->id }}, context: 'projects' | 'pitches' | 'global' })"`
  - Wrapper `div` becomes a drop target; on dragenter, `GlobalUploader.setActiveTarget(meta)`; on drop, pass files.
  - Keep existing Google Drive upload modal; unify toasts and refresh behavior.

5) Auth Protect Multipart Endpoints (If Needed)
- Verify sign/complete/abort routes are guarded; if not, declare explicit routes in `routes/web.php` with `auth` middleware and point to `CustomUppyS3MultipartController`.

6) Styling & Polish
- Mirror global audio player transitions and surface styles.
- Ensure dark mode parity.

7) Testing
- Feature tests (Pest) for backend parts:
  - Auth required on multipart endpoints (if we added overrides).
  - `handleUploadSuccess` creates correct records for projects/pitches based on metadata.
- Livewire tests:
  - Dispatch mocked `uploadData` to `GlobalFileUploader` and assert DB changes + emitted events.
- Manual QA checklist:
  - Drag/drop and selection from project and pitch contexts route to correct destination.
  - Auto-start uploads; progress visible; pause/resume/cancel works.
  - Navigation during upload: uploads persist; UI remains visible.
  - Simulate reload/crash: uploads can be resumed.
  - Works on mobile; dark mode visuals align; uploader sits above audio player without overlap.

## Rollout Plan
- Implement component and manager behind a feature flag (optional) for staged rollout.
- Start with projects and pitches; iterate for client portals once validated.
- Add analytics/logging for failure rates and resume success rates.

## Decisions
- Global drag overlay: Not in scope initially; focus on registered upload sections.
- Priority stacking: Audio player remains bottom-most; uploader sits above it with higher z-index and coordinated offsets.
- Mixed-destination queues: Allowed; ensure correct per-file metadata and record creation.

## Acceptance Criteria
- A persistent bottom uploader appears when files are queued and persists during navigation.
- Uploads start immediately upon selection/drag-drop; no extra confirmation needed.
- Uploads to S3 multipart can be paused/resumed; partial uploads can be resumed after reload.
- Files are saved to correct models and visible in their respective pages after upload completes.
- The UI matches Flux styling and coexists well with the global audio player without overlapping content.

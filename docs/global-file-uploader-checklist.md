# Global File Uploader — Implementation Checklist

- [x] Create Livewire component `GlobalFileUploader`
  - [x] Generate class and view via Artisan
  - [x] Render sticky mini bar and expandable full view (Flux UI)
  - [x] Provide DOM targets for Uppy StatusBar and ProgressBar
  - [x] Alpine state: `isVisible`, `isExpanded`, `aggregateProgress`, counters
  - [x] Listen for JS events (progress, complete, error) and update UI
  - [x] Expose Livewire action to process completed uploads (re-use service logic)

- [x] Extract upload processing logic for reuse
  - [x] Factor service call flow from `UppyFileUploader::handleUploadSuccess` into a trait or dedicated method
  - [x] Ensure support for mixed-destination files via per-file `meta` (`modelType`, `modelId`, `context`)

- [ ] Build `resources/js/global-upload-manager.js`
  - [x] Initialize singleton `window.GlobalUploader`
  - [x] Configure Uppy with `autoProceed: true`, `allowMultipleUploads: true`
  - [x] Configure `AwsS3` multipart using existing endpoints and `meta`
  - [x] Mount `StatusBar` and `ProgressBar` to global uploader DOM
  - [ ] Add `@uppy/store-localstorage` (and consider `golden-retriever`)
  - [x] Implement API: `setActiveTarget`, `openFileDialog`, `addFiles`, `resumeAll`, `pauseAll`, `cancelAll`
  - [x] Emit batch completion to Livewire with `uploadData`
  - [ ] Handle offline/online resume behavior

- [x] Mount in layout
  - [x] Add `@persist('global-file-uploader') @livewire('global-file-uploader')` in `app-sidebar.blade.php`
  - [x] Coordinate offsets: uploader above audio player; update CSS vars to avoid overlap
  - [x] Ensure z-index places uploader above audio player but both remain clickable

- [x] Delegate page upload sections
  - [x] Update `resources/views/components/file-management/upload-section.blade.php` to delegate to `GlobalUploader`
  - [x] Button triggers `openFileDialog` with `{ modelType, modelId, context }`
  - [x] Make the card a drop target; forward dropped files via `addFiles`
  - [x] Keep Google Drive modal; ensure `filesUploaded` still refreshes lists

- [x] Backend route verification
  - [x] Ensure multipart sign/complete/abort routes are `auth`-guarded
  - [x] Add explicit overrides if needed

- [ ] Styling & UX polish
  - [ ] Match global audio player aesthetics (rounded, blurred, transitions)
  - [ ] Dark mode parity
  - [ ] Accessible labels, aria-live for status, keyboard navigation

- [ ] Tests (Pest + Livewire)
  - [ ] Feature: multipart routes auth (if overridden)
  - [ ] Service logic: correct record creation for projects/pitches
  - [ ] Livewire: process mocked `uploadData`, assert DB + events

- [ ] QA
  - [ ] Drag/drop and selection from project and pitch contexts
  - [ ] Auto-start uploads, per-file progress, pause/resume/cancel
  - [ ] Navigation persistence and resume after reload
  - [ ] Mobile behavior, dark mode, stacking above audio player

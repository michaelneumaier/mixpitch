### MixPitch MVP Delivery Plan

This plan turns the current codebase into a production‑ready MVP by hardening critical flows, closing edge cases, aligning configurations, and setting clear acceptance criteria. It’s structured by priority: P0 (now), P1 (soon), P2 (later).

---

## Scope and goals
- Ensure producers can run client projects end‑to‑end: upload deliverables, submit for client review, handle revisions, and complete.
- Ensure clients can securely review and approve via white‑label portal.
- Enforce upload limits/quotas and keep storage accounting correct.
- Provide reliable audio waveform generation; keep watermarking optional for MVP.
- Keep billing/subscription gates functional; basic admin observability.

---

## P0 — Must‑do to reach MVP

### 1) Uploads via Uppy S3 multipart
- Summary: Enforce limits and authorization consistently on presign, process files idempotently, and handle errors.
- Relevant code:
  - Uppy UI: `resources/views/livewire/uppy-file-uploader.blade.php`
  - Livewire: `app/Livewire/UppyFileUploader.php`
  - S3 presign: `routes/web.php` (/s3/multipart), `app/Http/Controllers/CustomUppyS3MultipartController.php`
  - Upload validation: `app/Http/Middleware/ValidateUploadSettings.php`
  - File creation: `app/Services/FileManagementService.php`
- Tasks:
  - Add upload validation middleware on presign endpoints to enforce per‑context limits and auth.
    - Register middleware alias (if not yet): in `app/Http/Kernel.php` e.g. `'validate_upload' => \App\Http\Middleware\ValidateUploadSettings::class`.
    - Apply to `Route::post('/s3/multipart', ...)` and related `GET/POST/DELETE` presign routes with `->middleware(['auth', 'validate_upload:auto'])`.
  - Preflight capacity checks on presign create:
    - In `CustomUppyS3MultipartController@createMultipartUpload`: read metadata (`modelType`, `modelId`, planned size), fetch user and context limits via `FileUploadSetting` and `UserStorageService`, reject if `planned_size > remaining`.
  - Idempotency for createFromS3:
    - In `FileManagementService::createProjectFileFromS3` and `createPitchFileFromS3`, first check if a record with the same `storage_path`/`file_path` (S3 key) already exists; short‑circuit to avoid duplicates on retries.
  - Failure handling:
    - Ensure `abortMultipartUpload` endpoint works (already in UI). Add logging and S3 cleanup on errors; consider a scheduled job to abort stale multipart uploads (P1).
- Edge cases:
  - Resume/retry: Uppy retries are configured; server should tolerate repeated presign requests and duplicate completes.
  - Unauthorized uploads: `ValidateUploadSettings` already calls policies; ensure routes include `auth` middleware.
- Acceptance criteria:
  - Presign rejects too‑large files and unauthorized contexts with 4xx, without side effects.
  - Successful upload creates `ProjectFile` or `PitchFile` once (idempotent), with correct metadata and audit logs.
  - Aborted/failed uploads don’t leave dangling file records; multipart abort works.

### 2) Storage limits and quotas
- Summary: Accurately track user storage; block uploads when over capacity; decrement on delete.
- Relevant code:
  - `app/Services/UserStorageService.php`
  - `app/Services/FileManagementService.php`
  - `app/Models/User.php`, `app/Models/Project.php`
- Tasks:
  - Implement `UserStorageService::incrementUserStorage(User $user, int $bytes)`:
    - Use a DB transaction to verify `remaining >= bytes` then `increment('total_storage_used', $bytes)`.
    - Throw `\App\Exceptions\StorageLimitException` if over capacity.
  - Ensure `FileManagementService` calls `incrementUserStorage` on create and `decrementUserStorage` on delete for both project and pitch files.
  - Add presign preflight (above) and post‑create guard: if capacity changed between presign and finalize, handle gracefully with error + UI toast.
- Acceptance criteria:
  - Storage used increases on each uploaded file and decreases on deletion; totals match UI.
  - Attempts to exceed quota are blocked before upload and at finalize with clear error.

### 3) Client management workflow (submit, recall, approve/revise)
- Summary: End‑to‑end flow for producer submission and client decisions with appropriate status transitions and emails.
- Relevant code:
  - Manage page: `resources/views/livewire/project/manage-client-project.blade.php`
  - Component: `app/Livewire/Project/ManageClientProject.php` (methods to implement/verify)
  - Status rules: `app/Models/Pitch.php` (`$transitions`), `app/Http/Controllers/PitchController.php`
  - Client portal: `app/Http/Controllers/ClientPortalController.php`, `resources/views/client_portal/show.blade.php`
  - Emails: `app/Services/EmailService.php`, `resources/views/emails/client/*`
- Tasks:
  - Submission constraints:
    - Block `submitForReview()` if no producer files (UI already warns; enforce server‑side).
    - Transition to `ready_for_review`; generate signed client portal URL; queue review email (`sendClientReviewReadyEmail`).
    - If watermarking is enabled (optional), queue processing prior to notifying.
  - Recall submission:
    - Allow `recallSubmission()` only before client approval; transition back to `in_progress`.
  - Client decisions:
    - In portal, implement actions for approve and request revisions; store revision notes; transition accordingly (`approved`, `client_revisions_requested` or `revisions_requested`).
  - Resubmission logic:
    - Expose `canResubmit` when files were updated after previous submission; re‑notify client on resubmission.
- Acceptance criteria:
  - Producer cannot submit without files; can recall until client approves; resubmission works when files change.
  - Client receives signed link and can approve or request revisions; states update in producer UI and portal.
  - Emails are audited with metadata; branding applied via `BrandingResolver`.

### 4) Audio processing (waveforms; watermarking optional)
- Summary: Pick one waveform backend, ensure jobs run and UI tolerates delays; watermarking stays optional for MVP.
- Relevant code:
  - Job: `app/Jobs/GenerateAudioWaveform.php`
  - Cloudflare Worker: `cloudflare-workers/waveform-generator/*`
  - AWS Lambda (alt): `functions/generate_waveform.py` and related config
  - Player views: e.g., `resources/views/livewire/pitch-file-player.blade.php`
- Tasks:
  - Choose backend and set config/env:
    - If Cloudflare: set `WAVEFORM_SERVICE_URL` (or similar) and ensure CORS.
    - If AWS Lambda: set `services.aws.lambda_audio_processor_url`.
  - Ensure queue workers are running; set retry/backoff for transient errors; log failures with excerpts.
  - Watermarking:
    - Default OFF; if kept: add a queue job to process unprocessed audio on submit; gate client access to clean originals until approved.
- Acceptance criteria:
  - New audio shows waveform data within ~60s; failures are logged; UI remains functional if missing.
  - With watermarking OFF, clients can stream/download originals upon submission; with ON, only watermarked previews pre‑approval.

### 5) Pitch transitions guardrails
- Summary: Keep `Pitch::$transitions` as source of truth; block backwards moves after approval.
- Relevant code:
  - `app/Models/Pitch.php`, `app/Http/Controllers/PitchController.php`
- Tasks:
  - Enforce a hard block on backward transitions once status ≥ `approved` (unless explicitly allowed to `completed → approved`).
  - Ensure `recallSubmission()` maps to an allowed backward transition.
- Acceptance criteria:
  - Invalid transitions throw `InvalidStatusTransitionException` and are surfaced with user‑friendly messages.

### 6) Emails/notifications & branding
- Summary: Ensure client invite/review emails are branded and audited; suppression respected.
- Relevant code:
  - `app/Services/EmailService.php`, `app/Services/NotificationService.php`, `resources/views/emails/client/*`, `app/Services/BrandingResolver.php`
- Tasks:
  - Confirm client invite and review emails set producer branding; include signed portal URL in audit metadata.
  - Ensure suppression checks apply to these sends; log failures.
- Acceptance criteria:
  - Emails render with producer name/logo where available; audits show `client_portal_url` and status.

### 7) Roles/permissions consistency
- Summary: For MVP, use `users.role` constants for checks; avoid mixing Spatie roles in new code paths.
- Relevant code:
  - `app/Models/User.php` (`hasRole` override), policies, and Filament pages.
- Tasks:
  - Use `User::ROLE_*` checks in new flows; keep Spatie `HasRoles` trait but avoid introducing new Spatie role dependencies.
- Acceptance criteria:
  - Actions gated reliably for client/producer/admin; no ambiguous role checks.

### 8) Admin toggles and visibility (minimal)
- Summary: Small set of admin controls and metrics to support operations.
- Relevant code:
  - Filament Pages/Resources under `app/Filament/*`.
- Tasks:
  - Add a Filament setting for waveform backend selection (Cloudflare/AWS) and display current queue status.
  - Add a simple “Recent Uploads” table and “Email failures” list.
- Acceptance criteria:
  - Admin can change waveform backend and see a basic operational view.

---

## P1 — Soon after MVP

### A) Client comments and per‑file approvals
- Use the new migrations to store `client_approved_at` and enhance the portal to show unresolved comments per file.
- Display unresolved counts in `resources/views/components/client-management/workflow-status.blade.php` (already pulling from `pitch_file_comments`).
- Acceptance: Producer sees unresolved counts; client can approve per file; pitch completes when all required files approved (or whole‑pitch approval configured).

### B) Milestones and partial payments (feature‑flagged)
- Keep schema (`project_milestones`), implement whole‑project single milestone first; expose UI in manage page.
- Stripe Checkout per milestone; webhook marks as paid; unlock deliverables.

### C) Multipart cleanup job
- Background job to abort stale multipart uploads and delete temp parts.

---

## P2 — Later scale items
- Consolidate roles/permissions (either fully Spatie or column‑based; avoid duality).
- Audio fingerprinting for duplicate detection.
- Expanded analytics on client workflow funnels and turnaround time.

---

## Testing & QA matrix

### Uploads
- Feature tests for `/s3/multipart` create/sign/complete endpoints with and without auth; over‑size rejections; context validation (`validate_upload:auto`).
- Livewire tests for `UppyFileUploader::handleUploadSuccess` to assert file records created and storage incremented; deletion decrements.

### Client workflow
- Tests for `submitForReview` (blocked w/o files; success transitions; email queued), `recallSubmission` (only pre‑approval), client approve/request revisions transitions.
- Signed URL middleware (`signed_or_client`) checks: valid link opens; expired link denies with reissue path.

### Audio
- Job test for `GenerateAudioWaveform` happy path and failure path; ensure file updated with `waveform_peaks`/`duration`.

### Transitions
- Unit tests for allowed/denied transitions based on `Pitch::$transitions`.

### Emails/branding
- Snapshot test for email markdown HTML with branding.

---

## Rollout checklist
- [ ] Configure waveform backend env (Cloudflare or AWS Lambda) and queue worker.
- [ ] Register and apply `validate_upload` middleware to S3 multipart routes.
- [ ] Implement `incrementUserStorage`; verify decrement on delete.
- [ ] Wire submission/recall backend actions in `ManageClientProject` component/service; enable review email.
- [ ] Verify subscription gates (`subscription:create_project`, `subscription:create_pitch`).
- [ ] Smoke test client portal signed links and expiry.
- [ ] Admin: set waveform toggle and verify simple dashboards load.

---

## Implementation notes (quick references)
- Presign routes: see `routes/web.php` entries for `/s3/multipart` and add `validate_upload:auto` middleware.
- CSRF: `/s3/multipart*` exempt in `app/Http/Middleware/VerifyCsrfToken.php`.
- Uppy config surfaces model context via blade: `resources/views/livewire/uppy-file-uploader.blade.php`.
- Storage math APIs: `app/Services/UserStorageService.php`, `User::getStorageLimit()`, `Project::getStorageLimit()`.
- Status transitions and exceptions: `app/Models/Pitch.php`, `app/Exceptions/Pitch/*`.
- Emails & audits: `app/Services/EmailService.php`, `resources/views/emails/client/project_invite.blade.php`.

---

## Ownership (suggested)
- Uploads & storage: Backend dev + infra support
- Client workflow & emails: Full‑stack dev
- Audio processing: Backend dev
- Admin/Filament: Full‑stack dev
- QA: Test engineer











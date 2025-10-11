# Auto Versioning Implementation Plan

## Pitch Snapshot–Centered Versioning

Versioning is centered on Pitch Snapshots, which already model iteration over time.

- Leverage `pitch_snapshots` as the unit of versioning (each snapshot = one submission iteration).
- Derive per-file version labels by normalizing filenames across the snapshot history for a pitch.
- Avoid introducing new tables initially; compute per-file version indices on the fly (cache later if needed).
- Enhance downloads to use versioned filenames in the snapshot context.
- Provide a snapshot timeline UI with per-file grouping and A/B comparison of files from two snapshots.

### Scope & Principles

- Focus on `Pitch` workflows where multiple deliveries occur. `ProjectFile` is out of scope.
- Use existing `PitchSnapshot` model and `PitchWorkflowService` snapshot creation (increments a `version` and records `file_ids`).
- Keep uploads simple via the Global File Uploader; versioning happens when a snapshot is created, not at upload time.

### Data Model

- Reuse existing tables: `pitch_snapshots`, `pitch_files`.
- No new tables required. Optional: store lightweight audio metadata on `PitchFile` (duration, sample rate) to enrich comparisons; otherwise compute transiently.

### Services

- SnapshotVersioningService (helper):
  - `normalizeBaseName(string $filename): string` — strip common versioning suffixes (V01, final, mix, master, trailing digits, etc.).
  - `groupFilesByBaseName(Pitch $pitch): Collection` — over snapshot history.
  - `perFileVersionIndex(PitchFile $file, PitchSnapshot $snapshot): int` — 1-based index of occurrences of a base name up to this snapshot.
  - `downloadFilenameFor(PitchFile $file, PitchSnapshot $snapshot): string` — `<base>_V{snapshot.version}.<ext>` or `<base>_V{per-file-index}.<ext>`.

- AudioMetadataService (optional, non-blocking):
  - Download S3 object to a temp file, probe with `ffprobe/ffmpeg`, store minimal metadata on `PitchFile`. Fail gracefully if binaries aren’t available.

### Upload & Snapshot Flow

- Global File Uploader keeps sending files to `createPitchFileFromS3`. No version assigned at upload.
- Upon submission, `PitchWorkflowService` creates a new `PitchSnapshot` and records current `pitch->files` in `snapshot_data.file_ids`. This becomes the new version.
- Notes UX: map submission notes to `snapshot_data.response_to_feedback` rather than per-file notes.

### UI/UX

- Snapshot timeline:
  - For each snapshot, display files grouped by normalized `base_name`.
  - Label files with derived per-file version label and/or snapshot version number.
  - Actions: Play, Download (uses versioned filename), Compare with another snapshot.

- A/B comparison:
  - Select two snapshots. For each `base_name` present in both, show side-by-side `UniversalAudioPlayer` instances.
  - Provide a "Sync Playback" action using existing global audio events; optional timed A/B switching (Phase 2).

### Downloads

- Add an optional `$downloadFilename` parameter to `FileManagementService::getTemporaryDownloadUrl()`.
- In snapshot-driven downloads, use `SnapshotVersioningService::downloadFilenameFor(...)` to set `Content-Disposition` to a clean versioned name.

### Testing (Pest)

- Derivation tests: three snapshots with `Mix.wav`, `Mix_V2.wav`, `Mix_final.wav` normalize to one series, labeled V01/V02/V03.
- Download naming: in snapshot context, Content-Disposition uses the versioned filename.
- Comparison: selecting two snapshots with matching `base_name` renders the correct audio sources and supports playback sync.

### Implementation Steps

1. Add `SnapshotVersioningService` (helper methods above).
2. Add optional `$downloadFilename` to `FileManagementService::getTemporaryDownloadUrl()` and utilize it from snapshot views.
3. Update snapshot views to show grouped files with derived labels and actions.
4. Implement comparison UI reusing `UniversalAudioPlayer` and global player events.
5. Optional: add async audio metadata extraction for richer display.
























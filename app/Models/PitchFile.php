<?php

// app/Models/PitchFile.php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PitchFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pitch_id',
        'file_path',
        'storage_path',
        'file_name',
        'original_file_name',
        'mime_type',
        'note',
        'user_id',
        'size',
        'uuid',
        'waveform_peaks',
        'waveform_processed',
        'waveform_processed_at',
        'duration',
        // Client approval fields
        'client_approval_status',
        'client_approved_at',
        'client_approval_note',
        // Audio processing fields
        'audio_processed',
        'audio_processed_at',
        'processed_file_path',
        'processed_file_name',
        'processed_file_size',
        'is_transcoded',
        'is_watermarked',
        'processed_format',
        'processed_bitrate',
        'processing_metadata',
        'processing_error',
        // Revision tracking fields
        'revision_round',
        'superseded_by_revision',
        // Version management fields
        'included_in_working_version',
        // File versioning fields
        'parent_file_id',
        'file_version_number',
    ];

    protected $casts = [
        'waveform_processed' => 'boolean',
        'waveform_processed_at' => 'datetime',
        'duration' => 'float',
        'audio_processed' => 'boolean',
        'audio_processed_at' => 'datetime',
        'is_transcoded' => 'boolean',
        'is_watermarked' => 'boolean',
        'processing_metadata' => 'array',
        'client_approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'superseded_by_revision' => 'boolean',
        'revision_round' => 'integer',
        'included_in_working_version' => 'boolean',
        'parent_file_id' => 'integer',
        'file_version_number' => 'integer',
    ];

    /**
     * Get the display name for the file (original filename if available, otherwise file_name)
     */
    public function getFileNameAttribute($value)
    {
        return $this->original_file_name ?: $value;
    }

    /**
     * Boot the model and generate UUID on creation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });

        // Cascade soft deletes to versions
        static::deleting(function ($model) {
            if ($model->isForceDeleting()) {
                return; // Don't cascade on force delete
            }

            // If this is a root file, soft delete all versions
            if ($model->parent_file_id === null) {
                $model->versions()->each(function ($version) {
                    $version->delete();
                });
            }
        });
    }

    /**
     * Get the route key for the model (use UUID instead of ID)
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Format bytes to human-readable format
     *
     * @param  int  $bytes
     * @param  int  $precision
     * @return string
     */
    public function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Get the full file path including storage path
     * Now returns a signed URL with a short expiration
     * This is used for display purposes like audio players
     *
     * @return string|null
     */
    public function getFullFilePathAttribute()
    {
        $user = auth()->user();

        return $this->getStreamingUrl($user);
    }

    /**
     * Get a signed URL for downloading the file (accessor)
     * This uses a longer expiration time and appropriate headers for downloading
     *
     * @param  int  $expirationMinutes  Minutes until URL expires (default: 60)
     * @return string|null
     */
    public function getSignedUrlAttribute($expirationMinutes = 60)
    {
        return $this->getSignedUrl($expirationMinutes);
    }

    /**
     * Get a signed URL for downloading the file (method)
     * This uses a longer expiration time and appropriate headers for downloading
     *
     * @param  int  $expirationMinutes  Minutes until URL expires (default: 60)
     * @return string|null
     */
    public function getSignedUrl($expirationMinutes = 60)
    {
        try {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('s3');
            if (method_exists($disk, 'temporaryUrl')) {
                return $disk->temporaryUrl(
                    $this->file_path,
                    now()->addMinutes($expirationMinutes),
                    [
                        'ResponseContentDisposition' => 'attachment; filename="'.$this->file_name.'"',
                    ]
                );
            }

            return null;
        } catch (Exception $e) {
            Log::error('Error generating signed download URL for pitch file', [
                'file_id' => $this->id,
                'file_path' => $this->file_path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get the formatted file size
     *
     * @return string
     */
    public function getFormattedSizeAttribute()
    {
        // If size is stored in the database, use that
        if (isset($this->attributes['size']) && $this->attributes['size']) {
            return $this->formatBytes($this->attributes['size']);
        }

        // Otherwise try to get the size from storage, only if file_path exists
        if ($this->file_path) {
            try {
                $size = Storage::disk('s3')->size($this->file_path);

                return $this->formatBytes($size);
            } catch (Exception $e) {
                // Log error or handle as needed
                Log::warning("Could not get size for file path: {$this->file_path}", ['error' => $e->getMessage()]);

                return '-';
            }
        }

        return '-'; // Return default if no size attribute and no file_path
    }

    /**
     * Get waveform peaks data as PHP array
     *
     * @return array|null
     */
    public function getWaveformPeaksArrayAttribute()
    {
        if (! $this->waveform_peaks) {
            return null;
        }

        return json_decode($this->waveform_peaks, true);
    }

    public function pitch()
    {
        return $this->belongsTo(Pitch::class);
    }

    public function name()
    {
        $pathInfo = pathinfo($this->file_name);

        return $pathInfo['filename'];
    }

    public function extension()
    {
        $pathInfo = pathinfo($this->file_name);

        return $pathInfo['extension'];
    }

    /**
     * Get all comments for this pitch file (using new polymorphic relationship)
     */
    public function comments()
    {
        return $this->morphMany(FileComment::class, 'commentable');
    }

    /**
     * Legacy relationship for backwards compatibility
     *
     * @deprecated Use comments() instead
     */
    public function pitchFileComments()
    {
        return $this->hasMany(PitchFileComment::class);
    }

    /**
     * Get the user who uploaded this file
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent file (for file versions)
     * Returns null for root/original files
     */
    public function parent()
    {
        return $this->belongsTo(PitchFile::class, 'parent_file_id');
    }

    /**
     * Get all child versions of this file
     * Only meaningful for root files (where parent_file_id is null)
     */
    public function versions()
    {
        return $this->hasMany(PitchFile::class, 'parent_file_id')->orderBy('file_version_number');
    }

    /**
     * Get the formatted processed file size
     *
     * @return string
     */
    public function getFormattedProcessedSizeAttribute()
    {
        if (! $this->processed_file_size) {
            return '-';
        }

        return $this->formatBytes($this->processed_file_size);
    }

    /**
     * Check if the file should be watermarked based on project workflow
     */
    public function shouldBeWatermarked(): bool
    {
        $project = $this->pitch->project;
        $workflowConfig = config('audio.watermarking.workflows', []);

        // Check if watermarking is enabled for this workflow type
        if ($project->isStandard()) {
            return $workflowConfig['standard'] ?? true;
        } elseif ($project->isContest()) {
            return $workflowConfig['contest'] ?? false;
        } elseif ($project->isDirectHire()) {
            return $workflowConfig['direct_hire'] ?? false;
        } elseif ($project->isClientManagement()) {
            $configValue = $workflowConfig['client_management'] ?? false;

            // Handle user-controlled watermarking for client management
            if ($configValue === 'user_controlled') {
                return $this->pitch->watermarking_enabled ?? false;
            }

            return $configValue;
        }

        return false;
    }

    /**
     * Get the appropriate streaming URL based on user permissions
     */
    public function getStreamingUrl(?User $user = null, ?Project $project = null, int $expiration = 7200): ?string
    {
        if ($this->shouldServeWatermarked($user, $project)) {
            $processedUrl = $this->getProcessedFileUrl($expiration);

            // Fallback to original if no processed version available
            return $processedUrl ?: $this->getOriginalFileUrl($expiration);
        }

        return $this->getOriginalFileUrl($expiration);
    }

    /**
     * Determine if watermarked version should be served to user
     *
     * @param  User|null  $user  The authenticated user (or null for client portal access)
     * @param  Project|null  $project  The project context (for client portal access without authentication)
     */
    public function shouldServeWatermarked(?User $user = null, ?Project $project = null, ?PitchSnapshot $snapshot = null): bool
    {
        // If file is not processed or watermarked, serve original
        if (! $this->audio_processed || ! $this->is_watermarked) {
            return false;
        }

        // Check if user/client can access original file
        return ! $this->canAccessOriginalFile($user, $project, $snapshot);
    }

    /**
     * Check if user can access the original (non-watermarked) file
     *
     * @param  User|null  $user  The authenticated user (or null for client portal signed URL access)
     * @param  Project|null  $project  The project context (for client portal access without authentication)
     * @param  mixed  $snapshot  The snapshot context (PitchSnapshot model or virtual snapshot, for revision-based access control)
     */
    public function canAccessOriginalFile(?User $user = null, ?Project $project = null, mixed $snapshot = null): bool
    {
        // For authenticated users, use existing logic
        if ($user) {
            // Pitch owner can always access original
            if ($user->id === $this->pitch->user_id) {
                return true;
            }

            // Project owner can access original if fully paid either via full payment or all milestones paid
            if ($user->id === $this->pitch->project->user_id) {
                $fullyPaid = $this->pitch->isAcceptedCompletedAndPaid();
                if (! $fullyPaid) {
                    // Allow originals when all milestones with positive amounts are paid
                    // Note: Must check for both != 'paid' AND NULL since unpaid milestones have null payment_status
                    $unpaidMilestones = $this->pitch->milestones()
                        ->where('amount', '>', 0)
                        ->where(function ($query) {
                            $query->where('payment_status', '!=', Pitch::PAYMENT_STATUS_PAID)
                                ->orWhereNull('payment_status');
                        })
                        ->count();
                    $fullyPaid = $unpaidMilestones === 0 && $this->pitch->milestones()->exists();
                }

                return $fullyPaid;
            }

            return false;
        }

        // For client portal access with snapshot context (revision-based access control)
        if ($project && $project->isClientManagement() && $snapshot) {
            return $this->canAccessSnapshotFile($snapshot, $project);
        }

        // For client portal access without snapshot (legacy/fallback)
        if ($project && $project->isClientManagement()) {
            // Verify this file belongs to the project's pitch
            if ($this->pitch->project_id !== $project->id) {
                return false;
            }

            // Check if project is fully paid
            $fullyPaid = $this->pitch->isAcceptedCompletedAndPaid();

            if (! $fullyPaid) {
                // Check if all milestones with positive amounts are paid
                // Note: Must check for both != 'paid' AND NULL since unpaid milestones have null payment_status
                $unpaidMilestones = $this->pitch->milestones()
                    ->where('amount', '>', 0)
                    ->where(function ($query) {
                        $query->where('payment_status', '!=', Pitch::PAYMENT_STATUS_PAID)
                            ->orWhereNull('payment_status');
                    })
                    ->count();
                $milestonesExist = $this->pitch->milestones()->exists();

                $fullyPaid = $unpaidMilestones === 0 && $milestonesExist;
            }

            return $fullyPaid;
        }

        // No user and no project context - deny access to original
        return false;
    }

    /**
     * Check if this file can be accessed within a specific snapshot context.
     * Implements FILE-LEVEL revision-based access control: each file is accessible
     * if all milestones up to and including the FILE's revision round are paid.
     *
     * This allows granular access where files from paid revision rounds remain
     * accessible even when viewing a snapshot that contains unpaid files.
     *
     * @param  mixed  $snapshot  The snapshot being viewed (PitchSnapshot model or virtual snapshot)
     * @param  Project  $project  The project context
     */
    private function canAccessSnapshotFile(mixed $snapshot, Project $project): bool
    {
        // Verify this file belongs to the project's pitch
        if ($this->pitch->project_id !== $project->id) {
            return false;
        }

        // Verify this file belongs to this snapshot
        $snapshotFileIds = $snapshot->snapshot_data['file_ids'] ?? [];
        if (! in_array($this->id, $snapshotFileIds)) {
            return false;
        }

        // Get THIS FILE's revision round (not the snapshot's!)
        // Files without revision_round are treated as initial (round 0)
        $fileRevisionRound = $this->revision_round ?? 0;

        // For round 0 (initial submission), only check initial milestones
        if ($fileRevisionRound === 0) {
            $unpaidInitialMilestones = $this->pitch->milestones()
                ->where('amount', '>', 0)
                ->whereNull('revision_round_number')
                ->where('is_revision_milestone', false)
                ->where(function ($query) {
                    $query->where('payment_status', '!=', Pitch::PAYMENT_STATUS_PAID)
                        ->orWhereNull('payment_status');
                })
                ->count();

            return $unpaidInitialMilestones === 0 && $this->pitch->milestones()->exists();
        }

        // For revision rounds, check initial milestones + revision milestones up to THIS FILE's round
        $unpaidRevisionMilestones = $this->pitch->milestones()
            ->where('amount', '>', 0)
            ->where('revision_round_number', '<=', $fileRevisionRound)
            ->where(function ($query) {
                $query->where('payment_status', '!=', Pitch::PAYMENT_STATUS_PAID)
                    ->orWhereNull('payment_status');
            })
            ->count();

        $unpaidInitialMilestones = $this->pitch->milestones()
            ->where('amount', '>', 0)
            ->whereNull('revision_round_number')
            ->where('is_revision_milestone', false)
            ->where(function ($query) {
                $query->where('payment_status', '!=', Pitch::PAYMENT_STATUS_PAID)
                    ->orWhereNull('payment_status');
            })
            ->count();

        $totalUnpaid = $unpaidRevisionMilestones + $unpaidInitialMilestones;

        return $totalUnpaid === 0 && $this->pitch->milestones()->exists();
    }

    /**
     * Determine the revision round number for a given snapshot.
     * This is used for snapshot-based access control to determine which milestones
     * must be paid to access files in this snapshot.
     *
     * Revision rounds:
     * - V1: Round 0 (initial submission, covered by initial milestones)
     * - V2: Round 1 (first revision, covered if included_revisions >= 1)
     * - V3: Round 2 (second revision, covered if included_revisions >= 2)
     *
     * @param  PitchSnapshot  $snapshot  The snapshot to check
     */
    private function getRevisionRoundForSnapshot(PitchSnapshot $snapshot): int
    {
        // First, check if there's a milestone directly linked to this snapshot
        $milestone = $snapshot->milestone;
        if ($milestone && $milestone->revision_round_number) {
            return $milestone->revision_round_number;
        }

        // Fallback: Calculate based on snapshot version
        // V1 is the initial submission (round 0)
        if ($snapshot->version == 1) {
            return 0;
        }

        // V2+ are revisions: revision number = version - 1
        // V2 = revision 1, V3 = revision 2, etc.
        return $snapshot->version - 1;
    }

    /**
     * Get original file URL with appropriate expiration
     */
    public function getOriginalFileUrl(int $expirationMinutes = 30): ?string
    {
        try {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('s3');
            if (method_exists($disk, 'temporaryUrl')) {
                return $disk->temporaryUrl(
                    $this->file_path,
                    now()->addMinutes($expirationMinutes)
                );
            }

            return null;
        } catch (Exception $e) {
            Log::error('Error generating original file URL', [
                'file_id' => $this->id,
                'file_path' => $this->file_path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get processed file URL with appropriate expiration
     */
    public function getProcessedFileUrl(int $expirationMinutes = 30): ?string
    {
        if (! $this->processed_file_path) {
            return null;
        }

        try {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('s3');
            if (method_exists($disk, 'temporaryUrl')) {
                return $disk->temporaryUrl(
                    $this->processed_file_path,
                    now()->addMinutes($expirationMinutes)
                );
            }

            return null;
        } catch (Exception $e) {
            Log::error('Error generating processed file URL', [
                'file_id' => $this->id,
                'processed_file_path' => $this->processed_file_path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get download URL based on user permissions
     */
    public function getDownloadUrl(?User $user = null, int $expiration = 900): ?string
    {
        if ($this->shouldServeWatermarked($user)) {
            return $this->getProcessedFileUrl($expiration);
        }

        return $this->getOriginalFileUrl($expiration);
    }

    /**
     * Check if file is an audio file that can be processed
     */
    public function isAudioFile(): bool
    {
        if (! $this->mime_type) {
            return false;
        }

        $audioMimes = [
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
            'audio/ogg',
            'audio/aac',
            'audio/m4a',
            'audio/flac',
            'audio/x-wav',
            'audio/x-flac',
        ];

        return in_array($this->mime_type, $audioMimes);
    }

    /**
     * Check if file is a video file
     */
    public function isVideoFile(): bool
    {
        if (! $this->mime_type) {
            return false;
        }

        $videoMimes = [
            'video/mp4',
            'video/mpeg',
            'video/quicktime',
            'video/x-msvideo',
            'video/avi',
            'video/webm',
            'video/ogg',
            'video/3gpp',
            'video/x-flv',
            'video/x-ms-wmv',
        ];

        return in_array($this->mime_type, $videoMimes);
    }

    /**
     * Check if file is an image file
     */
    public function isImageFile(): bool
    {
        if (! $this->mime_type) {
            return false;
        }

        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if file needs processing
     */
    public function needsProcessing(): bool
    {
        return $this->isAudioFile() &&
               $this->shouldBeWatermarked() &&
               ! $this->audio_processed;
    }

    /**
     * Mark file as processed and store results
     */
    public function markAsProcessed(array $processedData): void
    {
        $this->update([
            'audio_processed' => true,
            'audio_processed_at' => now(),
            'processed_file_path' => $processedData['output_path'] ?? null,
            'processed_file_name' => $processedData['output_filename'] ?? null,
            'processed_file_size' => $processedData['output_size'] ?? null,
            'is_transcoded' => $processedData['transcoded'] ?? false,
            'is_watermarked' => $processedData['watermarked'] ?? false,
            'processed_format' => $processedData['format'] ?? null,
            'processed_bitrate' => $processedData['bitrate'] ?? null,
            'processing_metadata' => $processedData['metadata'] ?? null,
            'processing_error' => null, // Clear any previous errors
        ]);
    }

    /**
     * Mark file as failed processing
     */
    public function markAsProcessingFailed(string $error): void
    {
        $this->update([
            'audio_processed' => false,
            'processing_error' => $error,
        ]);
    }

    /**
     * Scope query to only include files in the working version
     */
    public function scopeInWorkingVersion($query)
    {
        return $query->where('included_in_working_version', true);
    }

    /**
     * Scope query to only include files excluded from the working version
     * Shows single files OR latest version of file families where all are excluded
     */
    public function scopeExcludedFromWorkingVersion($query)
    {
        // Part 1: Single files (no versions) that are excluded
        $singleExcludedFiles = $query->getModel()->newQuery()
            ->select('pitch_files.*')
            ->where('included_in_working_version', false)
            ->whereNull('parent_file_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('pitch_files as children')
                    ->whereColumn('children.parent_file_id', 'pitch_files.id');
            });

        // Part 2: Latest version of file families where ALL versions (including root) are excluded
        $latestVersionsAllExcluded = $query->getModel()->newQuery()
            ->whereNotNull('parent_file_id')
            ->where('included_in_working_version', false)
            ->whereIn('id', function ($subquery) {
                $subquery->selectRaw('id')
                    ->from('pitch_files as pf1')
                    ->whereNotNull('pf1.parent_file_id')
                    // Get highest version number for each family (excluding deleted versions)
                    ->whereRaw('pf1.file_version_number = (
                        SELECT MAX(pf2.file_version_number)
                        FROM pitch_files pf2
                        WHERE pf2.parent_file_id = pf1.parent_file_id
                        AND pf2.deleted_at IS NULL
                    )')
                    // Only include if root file is also excluded
                    ->whereExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('pitch_files as root')
                            ->whereColumn('root.id', 'pf1.parent_file_id')
                            ->where('root.included_in_working_version', false);
                    })
                    // Only include if no sibling version is included
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('pitch_files as siblings')
                            ->whereColumn('siblings.parent_file_id', 'pf1.parent_file_id')
                            ->where('siblings.included_in_working_version', true);
                    });
            });

        return $query->fromSub(
            $singleExcludedFiles->union($latestVersionsAllExcluded),
            'pitch_files'
        );
    }

    /**
     * Exclude this file from the working version (keeps file but removes from next snapshot)
     */
    public function excludeFromWorkingVersion(): bool
    {
        return $this->update(['included_in_working_version' => false]);
    }

    /**
     * Include this file in the working version (adds back to next snapshot)
     */
    public function includeInWorkingVersion(): bool
    {
        return $this->update(['included_in_working_version' => true]);
    }

    /**
     * Check if this file is included in the working version
     */
    public function isInWorkingVersion(): bool
    {
        return $this->included_in_working_version === true;
    }

    /**
     * Check if this file is excluded from the working version
     */
    public function isExcludedFromWorkingVersion(): bool
    {
        return $this->included_in_working_version === false;
    }

    /**
     * Get the root file (self if already root, or parent if this is a version)
     */
    public function getRootFile(): PitchFile
    {
        if ($this->parent_file_id) {
            return $this->parent()->withTrashed()->first() ?? $this;
        }

        return $this;
    }

    /**
     * Check if this file has multiple versions
     */
    public function hasMultipleVersions(): bool
    {
        $root = $this->getRootFile();

        // Check if this root has any child versions, or if this file itself is a version
        return $root->versions()->count() > 0 || $this->parent_file_id !== null;
    }

    /**
     * Get all versions of this file including the root
     * Returns collection sorted by version number (descending - latest first)
     */
    public function getAllVersionsWithSelf(): Collection
    {
        $root = $this->getRootFile();
        $versions = $root->versions()->withTrashed()->get();

        // Include root file and sort descending (latest first)
        return collect([$root])->merge($versions)->sortByDesc('file_version_number');
    }

    /**
     * Get version label for display (e.g., "V2", "V3")
     * Returns null if file doesn't have multiple versions
     */
    public function getVersionLabel(): ?string
    {
        if (! $this->hasMultipleVersions()) {
            return null; // Don't show "V1" for single-version files
        }

        return 'V'.$this->file_version_number;
    }

    /**
     * Check if this is the latest version in its family
     */
    public function isLatestVersion(): bool
    {
        $root = $this->getRootFile();
        $latestVersion = $root->versions()->withTrashed()->max('file_version_number') ?? 1;

        return $this->file_version_number >= $latestVersion;
    }

    /**
     * Check if this file version was included in any snapshot
     * Used to determine if version should be soft-deleted (in snapshot) or hard-deleted (never submitted)
     */
    public function wasInSnapshot(): bool
    {
        if (! $this->pitch) {
            return false;
        }

        $snapshots = $this->pitch->snapshots;

        foreach ($snapshots as $snapshot) {
            $fileIds = $snapshot->snapshot_data['file_ids'] ?? [];
            if (in_array($this->id, $fileIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scope to get only latest versions of each file family
     * Returns either the root file (if no versions) or the latest version child
     */
    public function scopeLatestVersions($query)
    {
        // Get root files that have no versions (single files)
        $rootsWithoutVersions = $query->getModel()->newQuery()
            ->select('pitch_files.*')
            ->whereNull('parent_file_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('pitch_files as children')
                    ->whereColumn('children.parent_file_id', 'pitch_files.id');
            });

        // Get latest versions of files that have versions
        $latestVersions = $query->getModel()->newQuery()
            ->whereNotNull('parent_file_id')
            ->whereIn('id', function ($subquery) {
                $subquery->selectRaw('id')
                    ->from('pitch_files as pf1')
                    ->whereNotNull('pf1.parent_file_id')
                    ->whereRaw('pf1.file_version_number = (
                        SELECT MAX(pf2.file_version_number)
                        FROM pitch_files pf2
                        WHERE pf2.parent_file_id = pf1.parent_file_id
                    )');
            });

        return $query->fromSub(
            $rootsWithoutVersions->union($latestVersions),
            'pitch_files'
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseTemplate extends Model
{
    use HasFactory;

    /**
     * License categories
     */
    const CATEGORY_MUSIC = 'music';

    const CATEGORY_SOUND_DESIGN = 'sound-design';

    const CATEGORY_MIXING = 'mixing';

    const CATEGORY_MASTERING = 'mastering';

    const CATEGORY_GENERAL = 'general';

    /**
     * Use cases for license templates
     */
    const USE_CASE_COLLABORATION = 'collaboration';

    const USE_CASE_SYNC = 'sync';

    const USE_CASE_SAMPLES = 'samples';

    const USE_CASE_REMIX = 'remix';

    const USE_CASE_COMMERCIAL = 'commercial';

    protected $fillable = [
        'user_id',
        'name',
        'content',
        'is_default',
        'is_active',
        'terms',
        'category',
        'description',
        'usage_stats',
        // New fields
        'legal_metadata',
        'license_version',
        'last_legal_review',
        'use_case',
        'industry_tags',
        'parent_template_id',
        'is_system_template',
        'is_public',
        'average_project_value',
        'usage_analytics',
        'approval_status',
        'approved_by',
        'approved_at',
        // Marketplace publishing fields
        'marketplace_title',
        'marketplace_description',
        'submission_notes',
        'submitted_for_approval_at',
        'rejection_reason',
        'marketplace_featured',
        'view_count',
        'fork_count',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'terms' => 'array',
        'usage_stats' => 'array',
        'legal_metadata' => 'array',
        'last_legal_review' => 'datetime',
        'industry_tags' => 'array',
        'is_system_template' => 'boolean',
        'is_public' => 'boolean',
        'average_project_value' => 'decimal:2',
        'usage_analytics' => 'array',
        'approved_at' => 'datetime',
        'submitted_for_approval_at' => 'datetime',
        'marketplace_featured' => 'boolean',
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Get the user who owns this template
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent template if this is forked
     */
    public function parentTemplate(): BelongsTo
    {
        return $this->belongsTo(LicenseTemplate::class, 'parent_template_id');
    }

    /**
     * Get child templates (forks)
     */
    public function childTemplates(): HasMany
    {
        return $this->hasMany(LicenseTemplate::class, 'parent_template_id');
    }

    /**
     * Get the user who approved this template
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get projects using this template
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get license signatures for this template
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(LicenseSignature::class);
    }

    // ========== STATIC METHODS ==========

    /**
     * Get standard terms structure
     */
    public static function getStandardTermsStructure(): array
    {
        return [
            // Core Rights
            'commercial_use' => false,
            'non_commercial_use' => true,
            'modification_allowed' => true,
            'distribution_allowed' => false,
            'sublicensing_allowed' => false,

            // Attribution & Credit
            'attribution_required' => false,
            'credit_placement' => null, // 'liner_notes', 'track_title', 'description'
            'credit_format' => null,

            // Usage Restrictions
            'standalone_distribution_prohibited' => true,
            'sample_library_creation_prohibited' => true,
            'ai_training_prohibited' => true,
            'sync_licensing_allowed' => false,
            'broadcast_allowed' => false,
            'streaming_allowed' => true,

            // Territory & Duration
            'territory' => 'worldwide',
            'duration' => 'perpetual', // 'limited', 'perpetual'
            'expiration_date' => null,

            // Revenue Sharing
            'revenue_sharing_enabled' => false,
            'revenue_percentage' => 0,
            'minimum_payout' => 0,

            // Platform Specific
            'platform_exclusive' => false,
            'white_label_allowed' => false,
            'resale_restrictions' => [],
        ];
    }

    /**
     * Get default templates for free users (presets)
     */
    public static function getDefaultPresets(): array
    {
        return [
            [
                'name' => 'Basic Collaboration',
                'category' => self::CATEGORY_GENERAL,
                'use_case' => self::USE_CASE_COLLABORATION,
                'content' => "You are granted a non-exclusive license to use this work for personal and commercial projects.\n\nTerms:\n- You may not resell or redistribute the original files\n- Credit is appreciated but not required\n- This license is perpetual and non-transferable",
                'terms' => [
                    'commercial_use' => true,
                    'attribution_required' => false,
                    'resale_allowed' => false,
                    'modification_allowed' => true,
                ],
                'description' => 'Standard license for most music collaboration projects',
                'is_system_template' => true,
            ],
            [
                'name' => 'Sync Ready License',
                'category' => self::CATEGORY_MUSIC,
                'use_case' => self::USE_CASE_SYNC,
                'content' => "You are granted an extended license to use this work in any medium including broadcast, film, and online content.\n\nTerms:\n- Unlimited commercial use\n- No attribution required\n- Cannot be resold as standalone audio\n- Perfect for sync licensing and media production",
                'terms' => [
                    'commercial_use' => true,
                    'attribution_required' => false,
                    'resale_allowed' => false,
                    'modification_allowed' => true,
                    'broadcast_allowed' => true,
                    'sync_licensing_allowed' => true,
                ],
                'description' => 'Comprehensive license for sync, media and broadcast use',
                'industry_tags' => ['film', 'tv', 'advertising'],
                'is_system_template' => true,
            ],
            [
                'name' => 'Commercial Attribution',
                'category' => self::CATEGORY_GENERAL,
                'use_case' => self::USE_CASE_COMMERCIAL,
                'content' => "Full commercial license allowing unlimited use in commercial projects.\n\nTerms:\n- Unlimited commercial use\n- Attribution required in credits\n- Cannot be resold as original work\n- Modifications allowed for your projects",
                'terms' => [
                    'commercial_use' => true,
                    'attribution_required' => true,
                    'resale_allowed' => false,
                    'modification_allowed' => true,
                ],
                'description' => 'Commercial license with attribution requirement',
                'is_system_template' => true,
            ],
        ];
    }

    /**
     * Create default templates for a user
     */
    public static function createDefaultTemplatesForUser(User $user): void
    {
        $presets = self::getDefaultPresets();

        foreach ($presets as $index => $preset) {
            self::create(array_merge($preset, [
                'user_id' => $user->id,
                'is_default' => $index === 0, // First template is default
                'usage_stats' => ['created' => now()->toISOString(), 'times_used' => 0],
                'legal_metadata' => ['jurisdiction' => 'US', 'created_version' => '1.0'],
            ]));
        }
    }

    /**
     * Get available categories
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_MUSIC => 'Music Production',
            self::CATEGORY_SOUND_DESIGN => 'Sound Design',
            self::CATEGORY_MIXING => 'Mixing & Engineering',
            self::CATEGORY_MASTERING => 'Mastering',
            self::CATEGORY_GENERAL => 'General Purpose',
        ];
    }

    /**
     * Get available use cases
     */
    public static function getUseCases(): array
    {
        return [
            self::USE_CASE_COLLABORATION => 'Music Collaboration',
            self::USE_CASE_SYNC => 'Sync Licensing',
            self::USE_CASE_SAMPLES => 'Sample Licensing',
            self::USE_CASE_REMIX => 'Remix & Edit',
            self::USE_CASE_COMMERCIAL => 'Commercial Use',
        ];
    }

    /**
     * Check if user can create more templates
     */
    public static function canUserCreate(User $user): bool
    {
        $maxTemplates = $user->getMaxLicenseTemplates();

        // Unlimited for Pro users
        if ($maxTemplates === null) {
            return true;
        }

        $currentCount = $user->licenseTemplates()->count();

        return $currentCount < $maxTemplates;
    }

    // ========== INSTANCE METHODS ==========

    /**
     * Set this template as the user's default
     */
    public function setAsDefault(): void
    {
        // Remove default status from other templates
        $this->user->licenseTemplates()->update(['is_default' => false]);

        // Set this as default
        $this->update(['is_default' => true]);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $stats = $this->usage_stats ?? ['times_used' => 0];
        $stats['times_used'] = ($stats['times_used'] ?? 0) + 1;
        $stats['last_used'] = now()->toISOString();

        $this->update(['usage_stats' => $stats]);
    }

    /**
     * Get usage count
     */
    public function getUsageCount(): int
    {
        return $this->usage_stats['times_used'] ?? 0;
    }

    /**
     * Get formatted category name
     */
    public function getCategoryNameAttribute(): string
    {
        $categories = self::getCategories();

        return $categories[$this->category] ?? 'Unknown';
    }

    /**
     * Get formatted use case name
     */
    public function getUseCaseNameAttribute(): string
    {
        $useCases = self::getUseCases();

        return $useCases[$this->use_case] ?? 'General';
    }

    /**
     * Check if template is approved
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Check if template is a fork
     */
    public function isFork(): bool
    {
        return $this->parent_template_id !== null;
    }

    /**
     * Create a fork of this template for another user
     */
    public function createFork(User $user, array $overrides = []): self
    {
        // Increment fork count
        $this->increment('fork_count');

        return self::create(array_merge([
            'user_id' => $user->id,
            'name' => $this->name.' (Fork)',
            'content' => $this->content,
            'description' => $this->description,
            'category' => $this->category,
            'use_case' => $this->use_case,
            'terms' => $this->terms,
            'industry_tags' => $this->industry_tags,
            'parent_template_id' => $this->id,
            'is_default' => false,
            'is_active' => true,
            'usage_stats' => ['created' => now()->toISOString(), 'times_used' => 0, 'forked_from' => $this->id],
        ], $overrides));
    }

    /**
     * Submit template to marketplace for approval
     */
    public function submitToMarketplace(array $marketplaceData): void
    {
        $this->update([
            'marketplace_title' => $marketplaceData['marketplace_title'] ?? $this->name,
            'marketplace_description' => $marketplaceData['marketplace_description'] ?? $this->description,
            'submission_notes' => $marketplaceData['submission_notes'] ?? null,
            'approval_status' => 'pending',
            'submitted_for_approval_at' => now(),
        ]);
    }

    /**
     * Approve template for marketplace
     */
    public function approveForMarketplace(User $approver): void
    {
        $this->update([
            'approval_status' => 'approved',
            'is_public' => true,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject template from marketplace
     */
    public function rejectFromMarketplace(User $rejector, string $reason): void
    {
        $this->update([
            'approval_status' => 'rejected',
            'is_public' => false,
            'approved_by' => $rejector->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Increment view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Check if template is pending approval
     */
    public function isPendingApproval(): bool
    {
        return $this->approval_status === 'pending';
    }

    /**
     * Check if template is rejected
     */
    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    /**
     * Check if template can be published to marketplace
     */
    public function canBePublishedToMarketplace(): bool
    {
        // Cannot publish if already public or pending
        if ($this->is_public || $this->isPendingApproval()) {
            return false;
        }

        // Cannot publish system templates
        if ($this->is_system_template) {
            return false;
        }

        // Must have valid content
        if (empty($this->content) || strlen($this->content) < 50) {
            return false;
        }

        return true;
    }

    /**
     * Get marketplace display title
     */
    public function getMarketplaceTitleDisplayAttribute(): string
    {
        return $this->marketplace_title ?: $this->name;
    }

    /**
     * Get marketplace display description
     */
    public function getMarketplaceDescriptionDisplayAttribute(): string
    {
        return $this->marketplace_description ?: $this->description;
    }

    /**
     * Generate license content with project context
     */
    public function generateLicenseContent(?Project $project = null): string
    {
        $content = $this->content;

        if ($project) {
            // Replace placeholders with project-specific information
            $replacements = [
                '[PROJECT_NAME]' => $project->name,
                '[PROJECT_OWNER]' => $project->user->name,
                '[DATE]' => now()->format('F j, Y'),
                '[PROJECT_TYPE]' => $project->project_type,
            ];

            $content = str_replace(array_keys($replacements), array_values($replacements), $content);
        }

        return $content;
    }

    // ========== SCOPES ==========

    /**
     * Scope to only active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only default templates
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to only approved templates
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    /**
     * Scope to only public templates
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope by use case
     */
    public function scopeByUseCase($query, string $useCase)
    {
        return $query->where('use_case', $useCase);
    }

    /**
     * Scope ordered by usage
     */
    public function scopeOrderByUsage($query, string $direction = 'desc')
    {
        return $query->orderByRaw("JSON_EXTRACT(usage_stats, '$.times_used') {$direction}");
    }

    /**
     * Scope for marketplace templates
     */
    public function scopeMarketplace($query)
    {
        return $query->where('is_public', true)
            ->where('approval_status', 'approved')
            ->where('is_active', true);
    }

    /**
     * Scope for pending approval templates
     */
    public function scopePendingApproval($query)
    {
        return $query->where('approval_status', 'pending');
    }

    /**
     * Scope for rejected templates
     */
    public function scopeRejected($query)
    {
        return $query->where('approval_status', 'rejected');
    }

    /**
     * Scope for featured marketplace templates
     */
    public function scopeFeatured($query)
    {
        return $query->where('marketplace_featured', true);
    }

    /**
     * Scope ordered by popularity (fork count)
     */
    public function scopeOrderByPopularity($query, string $direction = 'desc')
    {
        return $query->orderBy('fork_count', $direction);
    }

    /**
     * Scope ordered by views
     */
    public function scopeOrderByViews($query, string $direction = 'desc')
    {
        return $query->orderBy('view_count', $direction);
    }

    /**
     * Scope for search by title or description
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('marketplace_title', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%")
                ->orWhere('marketplace_description', 'LIKE', "%{$search}%");
        });
    }
}

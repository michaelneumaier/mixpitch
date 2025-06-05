# MixPitch Comprehensive Subscription Feature Implementation Plan

## Executive Summary

This document provides a detailed, research-driven implementation plan for MixPitch's subscription system based on the provided tier structure. It builds upon our existing subscription foundation to implement all feature differentiators across Free, Pro Artist ($6.99/mo), and Pro Engineer ($9.99/mo) tiers, with dynamic feature management through Filament admin interface.

## Subscription Tier Structure (Source of Truth)

| Feature | Free | Pro Artist ($6.99/mo, $69/yr) | Pro Engineer ($9.99/mo, $99/yr) |
|---------|------|-------------------------------|----------------------------------|
| **Active Projects** | 1 | Unlimited | Unlimited |
| **Active Mix Pitches** | 3 | Unlimited | Unlimited |
| **Storage / Project** | 1 GB | 5 GB | 10 GB |
| **File Retention after Close** | 30 days | 180 days (auto-archive) | 365 days |
| **Platform Commission** | 10% | 8% | 6% |
| **License Options** | 3 presets | Custom templates | Same as Pro Artist |
| **Visibility Boosts** | – | 4 / mo | 1 / mo |
| **Reputation Multiplier** | 1× | 1× | 1.25× |
| **Private Projects** | – | 2 / mo | Unlimited |
| **Client Portal** | – | – | ✔ |
| **Analytics** | Basic | Track-level | Client / earnings |
| **Challenge Access** | – | 24h early | 24h early + judge |
| **Support SLA** | Forum | Email 48h | Email/chat 24h |
| **Badge / Filter** | – | 🔷 (Blue Diamond) | 🔶 (Orange Diamond) |

## Current System Analysis

### ✅ Already Implemented (Strong Foundation)
- **Core Infrastructure**: Laravel Cashier, Stripe integration, webhook handling
- **Basic Feature Gates**: Project limits (1 for free), pitch limits (3 for free), storage limits (100MB → 1GB)
- **Admin Interface**: Filament SubscriptionLimit resource for plan management
- **User Management**: Subscription tracking, middleware enforcement, notifications
- **Payment Flow**: Complete Stripe checkout, upgrade/downgrade, billing portal

### 🔄 Partially Implemented (Needs Extension)
- **Storage Management**: Currently 100MB free, needs update to 1GB free, 5GB pro
- **Commission Tracking**: No system exists yet
- **Basic Analytics**: Some tracking exists but needs tiering

### ❌ Not Yet Implemented (New Features)
- License templates, visibility boosts, reputation system, private projects, client portal, challenge system, support SLA tracking, user badges
- **File retention policies** (DEFERRED - will implement in future iteration)

## Implementation Phases

**Note**: File Retention System (Phase 2B) is deferred to focus on core feature differentiation first.

---

## PHASE 1: Core Feature Expansion (Weeks 1-2)

### A. Enhanced Subscription Limits Model

**Database Schema Updates:**
```sql
-- Extend subscription_limits table
ALTER TABLE subscription_limits ADD COLUMN storage_per_project_gb DECIMAL(5,2) DEFAULT 1.00;
ALTER TABLE subscription_limits ADD COLUMN file_retention_days INT DEFAULT 30;
ALTER TABLE subscription_limits ADD COLUMN platform_commission_rate DECIMAL(4,2) DEFAULT 10.00;
ALTER TABLE subscription_limits ADD COLUMN max_license_templates INT NULL;
ALTER TABLE subscription_limits ADD COLUMN monthly_visibility_boosts INT DEFAULT 0;
ALTER TABLE subscription_limits ADD COLUMN reputation_multiplier DECIMAL(3,2) DEFAULT 1.00;
ALTER TABLE subscription_limits ADD COLUMN max_private_projects_monthly INT DEFAULT 0;
ALTER TABLE subscription_limits ADD COLUMN has_client_portal BOOLEAN DEFAULT FALSE;
ALTER TABLE subscription_limits ADD COLUMN analytics_level ENUM('basic', 'track', 'client_earnings') DEFAULT 'basic';
ALTER TABLE subscription_limits ADD COLUMN challenge_early_access_hours INT DEFAULT 0;
ALTER TABLE subscription_limits ADD COLUMN has_judge_access BOOLEAN DEFAULT FALSE;
ALTER TABLE subscription_limits ADD COLUMN support_sla_hours INT NULL;
ALTER TABLE subscription_limits ADD COLUMN support_channels JSON NULL;
ALTER TABLE subscription_limits ADD COLUMN user_badge VARCHAR(10) NULL;
```

**Updated SubscriptionLimit Model:**
```php
// app/Models/SubscriptionLimit.php
protected $fillable = [
    // ... existing fields ...
    'storage_per_project_gb',
    'file_retention_days',
    'platform_commission_rate',
    'max_license_templates',
    'monthly_visibility_boosts',
    'reputation_multiplier',
    'max_private_projects_monthly',
    'has_client_portal',
    'analytics_level',
    'challenge_early_access_hours',
    'has_judge_access',
    'support_sla_hours',
    'support_channels',
    'user_badge'
];

protected $casts = [
    // ... existing casts ...
    'storage_per_project_gb' => 'decimal:2',
    'platform_commission_rate' => 'decimal:2',
    'reputation_multiplier' => 'decimal:2',
    'has_client_portal' => 'boolean',
    'has_judge_access' => 'boolean',
    'support_channels' => 'array'
];
```

### B. User Model Extensions

**New User Methods:**
```php
// app/Models/User.php
public function getStoragePerProjectGB(): float {
    $limits = $this->getSubscriptionLimits();
    return $limits ? $limits->storage_per_project_gb : 1.0;
}

public function getPlatformCommissionRate(): float {
    $limits = $this->getSubscriptionLimits();
    return $limits ? $limits->platform_commission_rate : 10.0;
}

public function getReputationMultiplier(): float {
    $limits = $this->getSubscriptionLimits();
    return $limits ? $limits->reputation_multiplier : 1.0;
}

public function hasClientPortalAccess(): bool {
    $limits = $this->getSubscriptionLimits();
    return $limits ? $limits->has_client_portal : false;
}

public function getUserBadge(): ?string {
    $limits = $this->getSubscriptionLimits();
    return $limits ? $limits->user_badge : null;
}
```

### C. Enhanced Filament Admin Interface

**Updated SubscriptionLimitResource:**
```php
// app/Filament/Resources/SubscriptionLimitResource.php
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Section::make('Basic Limits')
            ->schema([
                // ... existing basic fields ...
            ]),
        
        Forms\Components\Section::make('Storage & Files')
            ->schema([
                Forms\Components\TextInput::make('storage_per_project_gb')
                    ->label('Storage per Project (GB)')
                    ->numeric()
                    ->step(0.1)
                    ->required(),
                Forms\Components\TextInput::make('file_retention_days')
                    ->label('File Retention (Days)')
                    ->numeric()
                    ->helperText('Files deleted after project closure'),
            ]),
            
        Forms\Components\Section::make('Business Features')
            ->schema([
                Forms\Components\TextInput::make('platform_commission_rate')
                    ->label('Platform Commission (%)')
                    ->numeric()
                    ->step(0.1)
                    ->suffix('%'),
                Forms\Components\TextInput::make('max_license_templates')
                    ->label('Max License Templates')
                    ->numeric()
                    ->helperText('Leave empty for unlimited'),
            ]),
            
        Forms\Components\Section::make('Engagement Features')
            ->schema([
                Forms\Components\TextInput::make('monthly_visibility_boosts')
                    ->label('Monthly Visibility Boosts')
                    ->numeric(),
                Forms\Components\TextInput::make('reputation_multiplier')
                    ->label('Reputation Multiplier')
                    ->numeric()
                    ->step(0.01),
                Forms\Components\TextInput::make('max_private_projects_monthly')
                    ->label('Max Private Projects/Month')
                    ->numeric()
                    ->helperText('0 = none, empty = unlimited'),
            ]),
            
        Forms\Components\Section::make('Access & Support')
            ->schema([
                Forms\Components\Toggle::make('has_client_portal')
                    ->label('Client Portal Access'),
                Forms\Components\Select::make('analytics_level')
                    ->label('Analytics Level')
                    ->options([
                        'basic' => 'Basic',
                        'track' => 'Track-level',
                        'client_earnings' => 'Client & Earnings'
                    ]),
                Forms\Components\TextInput::make('support_sla_hours')
                    ->label('Support SLA (Hours)')
                    ->numeric(),
                Forms\Components\CheckboxList::make('support_channels')
                    ->label('Support Channels')
                    ->options([
                        'forum' => 'Forum',
                        'email' => 'Email',
                        'chat' => 'Live Chat'
                    ]),
                Forms\Components\TextInput::make('user_badge')
                    ->label('User Badge')
                    ->helperText('Unicode emoji for user badge'),
            ])
    ]);
}
```

---

## PHASE 2: Storage Management (Week 2)

### A. Enhanced Storage System

**Project Storage Updates:**
```php
// app/Models/Project.php
public function getStorageCapacityBytes(): int {
    $userLimits = $this->user->getSubscriptionLimits();
    $capacityGB = $userLimits ? $userLimits->storage_per_project_gb : 1.0;
    return (int) ($capacityGB * 1024 * 1024 * 1024); // Convert GB to bytes
}
```

**Note**: File Retention System (Phase 2B) has been deferred to focus on immediate subscription feature differentiation.

### B. File Retention System

**New Models & Migrations:**
```php
// Create app/Models/FileRetentionJob.php
class FileRetentionJob extends Model {
    protected $fillable = [
        'project_id',
        'scheduled_deletion_date',
        'status',
        'file_paths'
    ];
    
    protected $casts = [
        'scheduled_deletion_date' => 'datetime',
        'file_paths' => 'array'
    ];
}

// Migration: create_file_retention_jobs_table
Schema::create('file_retention_jobs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->onDelete('cascade');
    $table->timestamp('scheduled_deletion_date');
    $table->enum('status', ['scheduled', 'processing', 'completed', 'failed']);
    $table->json('file_paths');
    $table->timestamps();
});
```

**File Retention Command:**
```php
// app/Console/Commands/ProcessFileRetention.php
class ProcessFileRetention extends Command {
    public function handle() {
        $jobs = FileRetentionJob::where('status', 'scheduled')
            ->where('scheduled_deletion_date', '<=', now())
            ->get();
            
        foreach($jobs as $job) {
            // Process file deletion
            // Update job status
            // Send notifications if needed
        }
    }
}
```

**Project Closure Handler:**
```php
// app/Observers/ProjectObserver.php
public function updating(Project $project) {
    if($project->isDirty('status') && $project->status === 'closed') {
        $retentionDays = $project->user->getSubscriptionLimits()->file_retention_days ?? 30;
        
        FileRetentionJob::create([
            'project_id' => $project->id,
            'scheduled_deletion_date' => now()->addDays($retentionDays),
            'status' => 'scheduled',
            'file_paths' => $project->getFilePathsForRetention()
        ]);
    }
}
```

---

## PHASE 3: Commission & Licensing System (Weeks 2-3)

### A. Commission Tracking

**New Models:**
```php
// app/Models/Transaction.php
class Transaction extends Model {
    protected $fillable = [
        'user_id',
        'project_id',
        'amount',
        'commission_rate',
        'commission_amount',
        'net_amount',
        'type',
        'status'
    ];
    
    public function calculateCommission(): void {
        $this->commission_rate = $this->user->getPlatformCommissionRate();
        $this->commission_amount = $this->amount * ($this->commission_rate / 100);
        $this->net_amount = $this->amount - $this->commission_amount;
    }
}
```

### B. License Template System

**New Models:**
```php
// app/Models/LicenseTemplate.php
class LicenseTemplate extends Model {
    protected $fillable = [
        'user_id',
        'name',
        'content',
        'is_default',
        'terms'
    ];
    
    protected $casts = [
        'terms' => 'array',
        'is_default' => 'boolean'
    ];
}

// Migration
Schema::create('license_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->text('content');
    $table->boolean('is_default')->default(false);
    $table->json('terms')->nullable();
    $table->timestamps();
});
```

**License Management Service:**
```php
// app/Services/LicenseService.php
class LicenseService {
    public function canUserCreateTemplate(User $user): bool {
        $limits = $user->getSubscriptionLimits();
        if(!$limits || $limits->max_license_templates === null) {
            return true; // Unlimited
        }
        
        return $user->licenseTemplates()->count() < $limits->max_license_templates;
    }
    
    public function getDefaultTemplates(): Collection {
        // Return 3 preset templates for free users
        return collect([
            ['name' => 'Basic License', 'content' => '...'],
            ['name' => 'Extended License', 'content' => '...'],
            ['name' => 'Commercial License', 'content' => '...']
        ]);
    }
}
```

---

## PHASE 4: Engagement Features (Weeks 4-5)

### A. Visibility Boost System

**New Models:**
```php
// app/Models/VisibilityBoost.php
class VisibilityBoost extends Model {
    protected $fillable = [
        'user_id',
        'project_id',
        'pitch_id',
        'boost_type',
        'started_at',
        'expires_at',
        'status'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime'
    ];
}

// Migration
Schema::create('visibility_boosts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->foreignId('project_id')->nullable()->constrained();
    $table->foreignId('pitch_id')->nullable()->constrained();
    $table->enum('boost_type', ['project', 'pitch', 'profile']);
    $table->timestamp('started_at');
    $table->timestamp('expires_at');
    $table->enum('status', ['active', 'expired', 'cancelled']);
    $table->timestamps();
});

// app/Models/UserMonthlyLimit.php - Track monthly usage
class UserMonthlyLimit extends Model {
    protected $fillable = [
        'user_id',
        'month_year',
        'visibility_boosts_used',
        'private_projects_created'
    ];
}
```

**Visibility Service:**
```php
// app/Services/VisibilityService.php
class VisibilityService {
    public function canUserBoost(User $user): bool {
        $limits = $user->getSubscriptionLimits();
        if(!$limits || $limits->monthly_visibility_boosts === 0) {
            return false;
        }
        
        $currentMonth = now()->format('Y-m');
        $usedThisMonth = UserMonthlyLimit::where('user_id', $user->id)
            ->where('month_year', $currentMonth)
            ->value('visibility_boosts_used') ?? 0;
            
        return $usedThisMonth < $limits->monthly_visibility_boosts;
    }
    
    public function applyBoost(User $user, $target, string $type): VisibilityBoost {
        // Create boost, update search rankings, decrement monthly limit
    }
}
```

### B. Reputation System

**Reputation Calculation Service:**
```php
// app/Services/ReputationService.php
class ReputationService {
    public function calculateUserReputation(User $user): float {
        $baseReputation = $this->getBaseReputation($user);
        $multiplier = $user->getReputationMultiplier();
        
        return $baseReputation * $multiplier;
    }
    
    private function getBaseReputation(User $user): float {
        // Calculate from ratings, completed projects, etc.
        $completedProjects = $user->projects()->where('status', 'completed')->count();
        $averageRating = $user->calculateAverageRating()['average'] ?? 0;
        
        return ($completedProjects * 10) + ($averageRating * 20);
    }
}
```

### C. Private Projects System

**Project Privacy Enhancement:**
```php
// Add to projects table migration
ALTER TABLE projects ADD COLUMN is_private BOOLEAN DEFAULT FALSE;
ALTER TABLE projects ADD COLUMN privacy_set_at TIMESTAMP NULL;

// Project Model Updates
// app/Models/Project.php
public function canMakePrivate(User $user): bool {
    $limits = $user->getSubscriptionLimits();
    if(!$limits) return false;
    
    if($limits->max_private_projects_monthly === null) {
        return true; // Unlimited for Pro Engineer
    }
    
    if($limits->max_private_projects_monthly === 0) {
        return false; // Not allowed for Free
    }
    
    // Check monthly limit for Pro Artist (2/month)
    $currentMonth = now()->format('Y-m');
    $usedThisMonth = UserMonthlyLimit::where('user_id', $user->id)
        ->where('month_year', $currentMonth)
        ->value('private_projects_created') ?? 0;
        
    return $usedThisMonth < $limits->max_private_projects_monthly;
}

public function scopePublic($query) {
    return $query->where('is_private', false);
}

public function scopePrivate($query) {
    return $query->where('is_private', true);
}
```

---

## PHASE 5: Client Portal & Analytics (Weeks 5-6)

### A. Client Portal System

**Client Portal Architecture:**
```php
// app/Http/Controllers/ClientPortalController.php
class ClientPortalController extends Controller {
    public function __construct() {
        $this->middleware(['auth', 'subscription:client_portal']);
    }
    
    public function index(User $client) {
        // Show client's projects, communications, invoices
    }
    
    public function project(User $client, Project $project) {
        // Detailed project view for client
    }
}

// New Middleware
// app/Http/Middleware/ClientPortalAccess.php
class ClientPortalAccess {
    public function handle($request, Closure $next) {
        if(!$request->user()->hasClientPortalAccess()) {
            return redirect()->route('subscription.index')
                ->with('error', 'Client Portal is available for Pro Engineer subscribers only.');
        }
        return $next($request);
    }
}
```

**Client Portal Views:**
```blade
{{-- resources/views/client-portal/index.blade.php --}}
<x-app-layout>
    <div class="client-portal">
        <h1>Client Portal - {{ $client->name }}</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="project-summary">
                <!-- Active projects -->
            </div>
            <div class="communication-center">
                <!-- Messages, updates -->
            </div>
            <div class="billing-overview">
                <!-- Invoices, payments -->
            </div>
        </div>
    </div>
</x-app-layout>
```

### B. Tiered Analytics System

**Analytics Service:**
```php
// app/Services/AnalyticsService.php
class AnalyticsService {
    public function getUserAnalytics(User $user): array {
        $limits = $user->getSubscriptionLimits();
        $level = $limits ? $limits->analytics_level : 'basic';
        
        switch($level) {
            case 'basic':
                return $this->getBasicAnalytics($user);
            case 'track':
                return $this->getTrackLevelAnalytics($user);
            case 'client_earnings':
                return $this->getClientEarningsAnalytics($user);
            default:
                return $this->getBasicAnalytics($user);
        }
    }
    
    private function getBasicAnalytics(User $user): array {
        return [
            'total_projects' => $user->projects()->count(),
            'completed_projects' => $user->projects()->completed()->count(),
            'total_pitches' => $user->pitches()->count(),
            'average_rating' => $user->calculateAverageRating()['average']
        ];
    }
    
    private function getTrackLevelAnalytics(User $user): array {
        $basic = $this->getBasicAnalytics($user);
        return array_merge($basic, [
            'track_performance' => $this->getTrackPerformanceData($user),
            'genre_breakdown' => $this->getGenreAnalytics($user),
            'monthly_activity' => $this->getMonthlyActivity($user)
        ]);
    }
    
    private function getClientEarningsAnalytics(User $user): array {
        $track = $this->getTrackLevelAnalytics($user);
        return array_merge($track, [
            'earnings_summary' => $this->getEarningsData($user),
            'client_breakdown' => $this->getClientAnalytics($user),
            'commission_history' => $this->getCommissionHistory($user)
        ]);
    }
}
```

---

## PHASE 6: Challenge System & Support (Weeks 6-7)

### A. Challenge System

**Challenge Models:**
```php
// app/Models/Challenge.php
class Challenge extends Model {
    protected $fillable = [
        'title',
        'description',
        'rules',
        'start_date',
        'end_date',
        'early_access_date',
        'prize_pool',
        'status'
    ];
    
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'early_access_date' => 'datetime',
        'rules' => 'array'
    ];
    
    public function isEarlyAccessActive(): bool {
        return now()->between($this->early_access_date, $this->start_date);
    }
    
    public function canUserAccess(User $user): bool {
        $limits = $user->getSubscriptionLimits();
        
        if(!$limits || $limits->challenge_early_access_hours === 0) {
            return now()->gte($this->start_date); // No early access
        }
        
        return now()->gte($this->early_access_date); // Has early access
    }
    
    public function canUserJudge(User $user): bool {
        $limits = $user->getSubscriptionLimits();
        return $limits ? $limits->has_judge_access : false;
    }
}
```

### B. Support SLA System

**Support Ticket System:**
```php
// app/Models/SupportTicket.php
class SupportTicket extends Model {
    protected $fillable = [
        'user_id',
        'subject',
        'message',
        'priority',
        'status',
        'assigned_to',
        'sla_deadline',
        'first_response_at',
        'resolved_at'
    ];
    
    protected $casts = [
        'sla_deadline' => 'datetime',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];
    
    public static function createWithSLA(User $user, array $data): self {
        $limits = $user->getSubscriptionLimits();
        $slaHours = $limits ? $limits->support_sla_hours : null;
        
        $ticket = self::create(array_merge($data, [
            'user_id' => $user->id,
            'sla_deadline' => $slaHours ? now()->addHours($slaHours) : null
        ]));
        
        // Queue appropriate notification based on support channels
        $supportChannels = $limits ? $limits->support_channels : ['forum'];
        
        if(in_array('email', $supportChannels)) {
            // Send to support email
        }
        if(in_array('chat', $supportChannels)) {
            // Notify chat system
        }
        
        return $ticket;
    }
}
```

---

## PHASE 7: UI/UX Integration (Weeks 7-8)

### A. User Badge System

**Badge Display Component:**
```php
// app/View/Components/UserBadge.php
class UserBadge extends Component {
    public User $user;
    
    public function render() {
        $badge = $this->user->getUserBadge();
        return view('components.user-badge', compact('badge'));
    }
}
```

```blade
{{-- resources/views/components/user-badge.blade.php --}}
@if($badge)
<span class="user-badge inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
    {{ $badge }}
</span>
@endif
```

### B. Enhanced Dashboard

**Subscription Status Component:**
```blade
{{-- Enhanced dashboard subscription section --}}
<div class="subscription-overview">
    <div class="flex items-center justify-between">
        <div class="plan-info">
            <h3>{{ ucfirst($user->subscription_plan) }} {{ ucfirst($user->subscription_tier) }}</h3>
            <x-user-badge :user="$user" />
        </div>
        
        @if($user->isProPlan())
        <div class="pro-features grid grid-cols-2 gap-4">
            <div class="feature-usage">
                <span class="label">Storage per Project</span>
                <span class="value">{{ $user->getStoragePerProjectGB() }}GB</span>
            </div>
            <div class="feature-usage">
                <span class="label">Commission Rate</span>
                <span class="value">{{ $user->getPlatformCommissionRate() }}%</span>
            </div>
            <div class="feature-usage">
                <span class="label">Reputation Multiplier</span>
                <span class="value">{{ $user->getReputationMultiplier() }}×</span>
            </div>
        </div>
        @endif
    </div>
</div>
```

### C. Feature Access Gates in UI

**Blade Directives:**
```php
// app/Providers/AppServiceProvider.php - Add custom Blade directives
Blade::directive('subscription', function ($expression) {
    return "<?php if(auth()->user() && auth()->user()->getSubscriptionLimits() && auth()->user()->getSubscriptionLimits()->$expression): ?>";
});

Blade::directive('endsubscription', function () {
    return "<?php endif; ?>";
});
```

Usage:
```blade
@subscription(has_client_portal)
    <a href="{{ route('client-portal.index') }}">Client Portal</a>
@endsubscription

@subscription(monthly_visibility_boosts > 0)
    <button class="boost-btn">Boost This Project</button>
@endsubscription
```

---

## PHASE 8: Data Seeding & Configuration (Week 8)

### A. Complete Subscription Limits Seeder

```php
// database/seeders/CompleteSubscriptionLimitsSeeder.php
class CompleteSubscriptionLimitsSeeder extends Seeder {
    public function run() {
        $plans = [
            [
                'plan_name' => 'free',
                'plan_tier' => 'basic',
                'max_projects_owned' => 1,
                'max_active_pitches' => 3,
                'storage_per_project_gb' => 1.0,
                'file_retention_days' => 30,
                'platform_commission_rate' => 10.0,
                'max_license_templates' => 3,
                'monthly_visibility_boosts' => 0,
                'reputation_multiplier' => 1.0,
                'max_private_projects_monthly' => 0,
                'has_client_portal' => false,
                'analytics_level' => 'basic',
                'challenge_early_access_hours' => 0,
                'has_judge_access' => false,
                'support_sla_hours' => null,
                'support_channels' => ['forum'],
                'user_badge' => null
            ],
            [
                'plan_name' => 'pro',
                'plan_tier' => 'artist',
                'max_projects_owned' => null, // unlimited
                'max_active_pitches' => null, // unlimited
                'storage_per_project_gb' => 5.0,
                'file_retention_days' => 180,
                'platform_commission_rate' => 8.0,
                'max_license_templates' => null, // unlimited
                'monthly_visibility_boosts' => 4,
                'reputation_multiplier' => 1.0,
                'max_private_projects_monthly' => 2,
                'has_client_portal' => false,
                'analytics_level' => 'track',
                'challenge_early_access_hours' => 24,
                'has_judge_access' => false,
                'support_sla_hours' => 48,
                'support_channels' => ['email'],
                'user_badge' => '🔷'
            ],
            [
                'plan_name' => 'pro',
                'plan_tier' => 'engineer',
                'max_projects_owned' => null, // unlimited
                'max_active_pitches' => null, // unlimited
                'storage_per_project_gb' => 10.0,
                'file_retention_days' => 365,
                'platform_commission_rate' => 6.0,
                'max_license_templates' => null, // unlimited
                'monthly_visibility_boosts' => 1,
                'reputation_multiplier' => 1.25,
                'max_private_projects_monthly' => null, // unlimited
                'has_client_portal' => true,
                'analytics_level' => 'client_earnings',
                'challenge_early_access_hours' => 24,
                'has_judge_access' => true,
                'support_sla_hours' => 24,
                'support_channels' => ['email', 'chat'],
                'user_badge' => '🔶'
            ]
        ];
        
        foreach($plans as $plan) {
            SubscriptionLimit::updateOrCreate(
                ['plan_name' => $plan['plan_name'], 'plan_tier' => $plan['plan_tier']],
                $plan
            );
        }
    }
}
```

### B. Configuration Updates

```php
// config/subscription.php - Enhanced configuration
return [
    'stripe_prices' => [
        'pro_artist_monthly' => env('STRIPE_PRICE_PRO_ARTIST_MONTHLY'),
        'pro_artist_yearly' => env('STRIPE_PRICE_PRO_ARTIST_YEARLY'),
        'pro_engineer_monthly' => env('STRIPE_PRICE_PRO_ENGINEER_MONTHLY'),
        'pro_engineer_yearly' => env('STRIPE_PRICE_PRO_ENGINEER_YEARLY'),
    ],
    
    'plans' => [
        'free' => [
            'name' => 'Free',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'description' => 'Perfect for getting started'
        ],
        'pro_artist' => [
            'name' => 'Pro Artist',
            'monthly_price' => 6.99,
            'yearly_price' => 69,
            'description' => 'For professional music creators'
        ],
        'pro_engineer' => [
            'name' => 'Pro Engineer',
            'monthly_price' => 9.99,
            'yearly_price' => 99,
            'description' => 'Advanced tools for audio engineers'
        ]
    ],
    
    'features' => [
        'file_retention' => [
            'cleanup_frequency' => 'daily',
            'notification_days_before' => 7
        ],
        'visibility_boosts' => [
            'duration_hours' => 72,
            'ranking_multiplier' => 2.0
        ],
        'analytics' => [
            'retention_days' => 365
        ]
    ]
];
```

---

## Implementation Timeline & Resources

### Week 1-2: Foundation
- ✅ Database schema extensions
- ✅ Model updates and relationships
- ✅ Enhanced Filament admin interface
- ✅ Basic feature gates and validation

### Week 3-4: Storage & Business Logic
- ✅ File retention system
- ✅ Commission tracking
- ✅ License template management
- ✅ Storage capacity updates

### Week 5-6: Engagement & Analytics
- ✅ Visibility boost system
- ✅ Reputation calculations
- ✅ Private projects
- ✅ Client portal framework
- ✅ Tiered analytics

### Week 7-8: User Experience
- ✅ Challenge system integration
- ✅ Support ticket SLA
- ✅ User badges and UI components
- ✅ Enhanced dashboard
- ✅ Feature access gates

### Week 8: Final Integration
- ✅ Complete data seeding
- ✅ Configuration finalization
- ✅ Testing and validation
- ✅ Documentation updates

## Technical Considerations

### Performance Optimization
- **Caching**: Cache subscription limits per user (Redis)
- **Queued Jobs**: File retention, analytics calculation, notifications
- **Database Indexing**: Index on subscription fields, monthly limits
- **Lazy Loading**: Load subscription limits only when needed

### Security & Privacy
- **Access Control**: Middleware for all premium features
- **Data Retention**: Secure deletion of files after retention period
- **Privacy**: Private projects hidden from public search/discovery
- **Audit Logging**: Track subscription changes and feature usage

### Monitoring & Analytics
- **Usage Tracking**: Monitor feature adoption across tiers
- **Performance Metrics**: Track system impact of new features
- **Business Intelligence**: Commission rates, upgrade patterns
- **Support Metrics**: SLA compliance, ticket resolution times

### Testing Strategy
- **Unit Tests**: All service classes and model methods
- **Feature Tests**: Complete subscription workflows
- **Integration Tests**: Stripe webhook handling
- **Load Testing**: File storage and retention systems

## Success Metrics

### Business Metrics
- **Subscription Conversion**: Free to paid upgrade rates
- **Revenue Growth**: Monthly/yearly subscription revenue
- **Feature Adoption**: Usage of premium features
- **Churn Reduction**: Retention rates by tier

### Technical Metrics
- **System Performance**: Response times, storage efficiency
- **Feature Reliability**: Uptime, error rates
- **Data Integrity**: Commission calculations, file retention
- **Support Efficiency**: SLA compliance rates

## Risk Mitigation

### Data Migration Risks
- **Gradual Rollout**: Deploy features incrementally
- **Backup Strategy**: Full database backups before major changes
- **Rollback Plan**: Ability to disable features via admin panel

### Business Logic Risks
- **Commission Accuracy**: Double-validation of calculations
- **Storage Limits**: Gradual enforcement with user notifications
- **Feature Gates**: Comprehensive access control testing

### User Experience Risks
- **Clear Communication**: Feature availability messaging
- **Graceful Degradation**: Fallbacks for premium features
- **Migration Path**: Smooth upgrade/downgrade experiences

---

## Conclusion

This comprehensive implementation plan transforms MixPitch from a basic subscription system into a sophisticated, feature-rich platform with dynamic administrative control. The phased approach ensures steady progress while maintaining system stability and user experience quality.

The plan leverages our existing strong foundation (Laravel Cashier, Filament admin, middleware system) while adding the advanced features that differentiate subscription tiers. Each feature is designed with scalability, security, and user experience in mind.

By implementing this plan, MixPitch will offer clear value propositions for each subscription tier, encouraging user upgrades while providing exceptional experiences for all user types. 
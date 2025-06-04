# MixPitch Subscription System Implementation Plan

## Executive Summary

This document outlines a comprehensive implementation plan for subscription features in MixPitch. The plan leverages the existing Laravel Cashier integration and builds upon the current project/pitch system to create tiered subscription plans with feature restrictions for free users.

## Current State Analysis

### Existing Infrastructure
- ✅ Laravel Cashier (Billable trait) already implemented in User model
- ✅ Stripe integration with webhook handling
- ✅ Basic billing controller and routes structure
- ✅ Filament admin panel structure
- ✅ Project and Pitch models with storage tracking
- ✅ Role-based system (client/producer roles)

### Current Limitations
- ❌ No subscription plan enforcement
- ❌ No project upload limits for free users
- ❌ No pitch count restrictions
- ❌ No subscription management UI
- ❌ No admin subscription management tools

## Subscription Plans Architecture

### Plan Structure
Based on the pricing page analysis, we'll implement these plans:

1. **Basic (Free)**
   - Upload: 1 project
   - Work on: Up to 3 projects (as producer)
   - Open pitches: 3 maximum
   - Storage per project: 100MB (current default)
   - Features: Basic collaboration tools, community support

2. **Pro Artist ($9/month)**
   - Upload: Unlimited projects
   - Work on: 2 prioritized projects at a time
   - Open pitches: Unlimited
   - Storage per project: 500MB
   - Features: Advanced collaboration tools, priority support, custom portfolio

3. **Pro Engineer ($9/month)**
   - Upload: Work on unlimited projects (as producer)
   - Pitches: 5 prioritized pitches per month
   - Open pitches: Unlimited
   - Storage per project: 500MB
   - Features: Advanced collaboration tools, priority support, custom portfolio

## Implementation Phase 1: Core Subscription System

### 1. Database Schema Extensions

#### New Migration: Add Subscription Plan Fields
```sql
-- Add to users table
ALTER TABLE users ADD COLUMN subscription_plan VARCHAR(50) DEFAULT 'free';
ALTER TABLE users ADD COLUMN subscription_tier VARCHAR(50) DEFAULT 'basic';
ALTER TABLE users ADD COLUMN plan_started_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN monthly_pitch_count INT DEFAULT 0;
ALTER TABLE users ADD COLUMN monthly_pitch_reset_date DATE NULL;

-- Add to projects table
ALTER TABLE projects ADD COLUMN is_prioritized BOOLEAN DEFAULT FALSE;
ALTER TABLE projects ADD COLUMN priority_order INT NULL;

-- Create subscription_limits table
CREATE TABLE subscription_limits (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    plan_name VARCHAR(50) NOT NULL,
    plan_tier VARCHAR(50) NOT NULL,
    max_projects_owned INT NULL, -- NULL = unlimited
    max_active_pitches INT NULL, -- NULL = unlimited
    max_monthly_pitches INT NULL, -- For Pro Engineer
    storage_per_project_mb INT DEFAULT 100,
    priority_support BOOLEAN DEFAULT FALSE,
    custom_portfolio BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_plan_tier (plan_name, plan_tier)
);
```

#### Seed Default Subscription Limits
```sql
INSERT INTO subscription_limits (plan_name, plan_tier, max_projects_owned, max_active_pitches, max_monthly_pitches, storage_per_project_mb, priority_support, custom_portfolio) VALUES
('free', 'basic', 1, 3, NULL, 100, FALSE, FALSE),
('pro', 'artist', NULL, NULL, NULL, 500, TRUE, TRUE),
('pro', 'engineer', NULL, NULL, 5, 500, TRUE, TRUE);
```

### 2. Model Enhancements

#### User Model Extensions
```php
// Add to User model
use Laravel\Cashier\Billable;

// Add constants
const PLAN_FREE = 'free';
const PLAN_PRO = 'pro';
const TIER_BASIC = 'basic';
const TIER_ARTIST = 'artist';
const TIER_ENGINEER = 'engineer';

// Add fillable fields
protected $fillable = [
    // ... existing fields
    'subscription_plan',
    'subscription_tier',
    'plan_started_at',
    'monthly_pitch_count',
    'monthly_pitch_reset_date',
];

// Add casts
protected $casts = [
    // ... existing casts
    'plan_started_at' => 'datetime',
    'monthly_pitch_reset_date' => 'date',
];

// New methods
public function isFreePlan(): bool
{
    return $this->subscription_plan === self::PLAN_FREE;
}

public function isProPlan(): bool
{
    return $this->subscription_plan === self::PLAN_PRO;
}

public function getSubscriptionLimits()
{
    return SubscriptionLimit::where('plan_name', $this->subscription_plan)
        ->where('plan_tier', $this->subscription_tier)
        ->first();
}

public function canCreateProject(): bool
{
    $limits = $this->getSubscriptionLimits();
    if (!$limits || $limits->max_projects_owned === null) {
        return true; // Unlimited
    }
    
    return $this->projects()->count() < $limits->max_projects_owned;
}

public function canCreatePitch(Project $project): bool
{
    $limits = $this->getSubscriptionLimits();
    if (!$limits || $limits->max_active_pitches === null) {
        return true; // Unlimited
    }
    
    $activePitches = $this->pitches()
        ->whereIn('status', [
            Pitch::STATUS_PENDING,
            Pitch::STATUS_IN_PROGRESS,
            Pitch::STATUS_READY_FOR_REVIEW,
            Pitch::STATUS_PENDING_REVIEW,
        ])
        ->count();
        
    return $activePitches < $limits->max_active_pitches;
}

public function canCreateMonthlyPitch(): bool
{
    $limits = $this->getSubscriptionLimits();
    if (!$limits || $limits->max_monthly_pitches === null) {
        return true; // Unlimited
    }
    
    // Reset monthly count if needed
    $this->resetMonthlyPitchCountIfNeeded();
    
    return $this->monthly_pitch_count < $limits->max_monthly_pitches;
}

public function incrementMonthlyPitchCount(): void
{
    $this->resetMonthlyPitchCountIfNeeded();
    $this->increment('monthly_pitch_count');
}

private function resetMonthlyPitchCountIfNeeded(): void
{
    $today = now()->toDateString();
    
    if (!$this->monthly_pitch_reset_date || $this->monthly_pitch_reset_date->isPast()) {
        $this->update([
            'monthly_pitch_count' => 0,
            'monthly_pitch_reset_date' => now()->addMonth()->startOfMonth()->toDateString()
        ]);
    }
}

public function getProjectStorageLimit(): int
{
    $limits = $this->getSubscriptionLimits();
    return $limits ? $limits->storage_per_project_mb * 1024 * 1024 : Project::MAX_STORAGE_BYTES;
}
```

#### New SubscriptionLimit Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionLimit extends Model
{
    protected $fillable = [
        'plan_name',
        'plan_tier',
        'max_projects_owned',
        'max_active_pitches',
        'max_monthly_pitches',
        'storage_per_project_mb',
        'priority_support',
        'custom_portfolio',
    ];

    protected $casts = [
        'max_projects_owned' => 'integer',
        'max_active_pitches' => 'integer',
        'max_monthly_pitches' => 'integer',
        'storage_per_project_mb' => 'integer',
        'priority_support' => 'boolean',
        'custom_portfolio' => 'boolean',
    ];

    public static function getPlanLimits(string $planName, string $planTier): ?self
    {
        return self::where('plan_name', $planName)
            ->where('plan_tier', $planTier)
            ->first();
    }
}
```

### 3. Middleware for Subscription Checks

#### SubscriptionCheck Middleware
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionCheck
{
    public function handle(Request $request, Closure $next, string $feature)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        switch ($feature) {
            case 'create_project':
                if (!$user->canCreateProject()) {
                    return redirect()->route('pricing')
                        ->with('error', 'You have reached your project limit. Upgrade to Pro to create unlimited projects.');
                }
                break;
                
            case 'create_pitch':
                $project = $request->route('project');
                if ($project && !$user->canCreatePitch($project)) {
                    return redirect()->back()
                        ->with('error', 'You have reached your active pitch limit. Upgrade to Pro for unlimited pitches.');
                }
                break;
        }

        return $next($request);
    }
}
```

## Implementation Phase 2: Feature Enforcement

### 1. Project Creation Limits

#### Update CreateProject Livewire Component
```php
// In app/Livewire/CreateProject.php

public function mount()
{
    // ... existing code
    
    // Check if user can create projects
    if (!Auth::user()->canCreateProject()) {
        session()->flash('error', 'You have reached your project limit. Upgrade to Pro to create unlimited projects.');
        return redirect()->route('pricing');
    }
}

public function createProject()
{
    // Add check before project creation
    if (!Auth::user()->canCreateProject()) {
        $this->addError('general', 'You have reached your project limit. Please upgrade to Pro.');
        return;
    }
    
    // ... existing creation logic
}
```

#### Update Project Routes
```php
// In routes/web.php
Route::middleware(['subscription:create_project'])->group(function () {
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
});
```

### 2. Pitch Creation Limits

#### Update Pitch Creation Logic
```php
// In relevant pitch creation components/controllers

public function createPitch(Project $project)
{
    $user = Auth::user();
    
    if (!$user->canCreatePitch($project)) {
        throw new \Exception('You have reached your active pitch limit. Upgrade to Pro for unlimited pitches.');
    }
    
    if (!$user->canCreateMonthlyPitch()) {
        throw new \Exception('You have reached your monthly pitch limit. Upgrade to Pro for unlimited pitches.');
    }
    
    // Create pitch
    $pitch = Pitch::create([...]);
    
    // Increment monthly count for Pro Engineer users
    if ($user->subscription_tier === User::TIER_ENGINEER) {
        $user->incrementMonthlyPitchCount();
    }
    
    return $pitch;
}
```

### 3. Storage Limit Enforcement

#### Update Project Model
```php
// In app/Models/Project.php

public function hasStorageCapacity($additionalBytes = 0)
{
    $userStorageLimit = $this->user->getProjectStorageLimit();
    return ($this->total_storage_used + $additionalBytes) <= $userStorageLimit;
}

public function getStorageLimitMessage()
{
    $userStorageLimit = $this->user->getProjectStorageLimit();
    $used = Number::fileSize($this->total_storage_used, precision: 2);
    $total = Number::fileSize($userStorageLimit, precision: 2);
    $remaining = Number::fileSize($userStorageLimit - $this->total_storage_used, precision: 2);
    
    return "Using $used of $total ($remaining available)";
}
```

## Implementation Phase 3: Subscription Management UI

### 1. Subscription Dashboard

#### Create SubscriptionController
```php
<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionLimit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $limits = $user->getSubscriptionLimits();
        
        $usage = [
            'projects_count' => $user->projects()->count(),
            'active_pitches_count' => $user->pitches()->whereIn('status', [
                Pitch::STATUS_PENDING,
                Pitch::STATUS_IN_PROGRESS,
                Pitch::STATUS_READY_FOR_REVIEW,
                Pitch::STATUS_PENDING_REVIEW,
            ])->count(),
            'monthly_pitches_used' => $user->monthly_pitch_count,
        ];
        
        return view('subscription.index', compact('user', 'limits', 'usage'));
    }
    
    public function upgrade(Request $request)
    {
        $plan = $request->input('plan'); // 'pro'
        $tier = $request->input('tier'); // 'artist' or 'engineer'
        
        $priceId = $this->getPriceIdForPlan($plan, $tier);
        
        return $request->user()
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('subscription.success'),
                'cancel_url' => route('subscription.index'),
            ]);
    }
    
    private function getPriceIdForPlan(string $plan, string $tier): string
    {
        $priceIds = [
            'pro.artist' => config('subscription.stripe_prices.pro_artist'),
            'pro.engineer' => config('subscription.stripe_prices.pro_engineer'),
        ];
        
        return $priceIds["$plan.$tier"] ?? throw new \Exception('Invalid plan/tier combination');
    }
}
```

### 2. Subscription Views

#### Create subscription/index.blade.php
```php
@extends('components.layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="px-6 py-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Subscription Management</h1>
            
            <!-- Current Plan -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">Current Plan</h2>
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium">
                                {{ ucfirst($user->subscription_plan) }} 
                                @if($user->subscription_tier !== 'basic')
                                    - {{ ucfirst($user->subscription_tier) }}
                                @endif
                            </h3>
                            <p class="text-gray-600">
                                @if($user->isFreePlan())
                                    Free plan with basic features
                                @else
                                    Active since {{ $user->plan_started_at->format('M j, Y') }}
                                @endif
                            </p>
                        </div>
                        @if($user->isFreePlan())
                            <a href="{{ route('pricing') }}" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
                                Upgrade Now
                            </a>
                        @else
                            <a href="{{ route('billing.portal') }}" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700">
                                Manage Billing
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Usage Stats -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">Current Usage</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Projects Created -->
                    <div class="bg-blue-50 rounded-lg p-6">
                        <h3 class="text-lg font-medium text-blue-900 mb-2">Projects Created</h3>
                        <div class="text-3xl font-bold text-blue-600 mb-2">
                            {{ $usage['projects_count'] }}
                            @if($limits && $limits->max_projects_owned)
                                / {{ $limits->max_projects_owned }}
                            @endif
                        </div>
                        <p class="text-blue-700">
                            @if($limits && $limits->max_projects_owned)
                                {{ $limits->max_projects_owned - $usage['projects_count'] }} remaining
                            @else
                                Unlimited
                            @endif
                        </p>
                    </div>
                    
                    <!-- Active Pitches -->
                    <div class="bg-green-50 rounded-lg p-6">
                        <h3 class="text-lg font-medium text-green-900 mb-2">Active Pitches</h3>
                        <div class="text-3xl font-bold text-green-600 mb-2">
                            {{ $usage['active_pitches_count'] }}
                            @if($limits && $limits->max_active_pitches)
                                / {{ $limits->max_active_pitches }}
                            @endif
                        </div>
                        <p class="text-green-700">
                            @if($limits && $limits->max_active_pitches)
                                {{ $limits->max_active_pitches - $usage['active_pitches_count'] }} remaining
                            @else
                                Unlimited
                            @endif
                        </p>
                    </div>
                    
                    <!-- Monthly Pitches (if applicable) -->
                    @if($limits && $limits->max_monthly_pitches)
                    <div class="bg-purple-50 rounded-lg p-6">
                        <h3 class="text-lg font-medium text-purple-900 mb-2">Monthly Pitches</h3>
                        <div class="text-3xl font-bold text-purple-600 mb-2">
                            {{ $usage['monthly_pitches_used'] }} / {{ $limits->max_monthly_pitches }}
                        </div>
                        <p class="text-purple-700">
                            {{ $limits->max_monthly_pitches - $usage['monthly_pitches_used'] }} remaining this month
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

## Implementation Phase 4: Webhook Handling

### 1. Update WebhookController
```php
// In app/Http/Controllers/Billing/WebhookController.php

public function handleCustomerSubscriptionCreated($payload)
{
    $subscription = $payload['data']['object'];
    $user = User::where('stripe_id', $subscription['customer'])->first();
    
    if ($user) {
        // Determine plan and tier from subscription metadata or price ID
        $planInfo = $this->determinePlanFromStripeSubscription($subscription);
        
        $user->update([
            'subscription_plan' => $planInfo['plan'],
            'subscription_tier' => $planInfo['tier'],
            'plan_started_at' => now(),
            'monthly_pitch_count' => 0,
            'monthly_pitch_reset_date' => now()->addMonth()->startOfMonth(),
        ]);
        
        Log::info('User subscription activated', [
            'user_id' => $user->id,
            'plan' => $planInfo['plan'],
            'tier' => $planInfo['tier']
        ]);
    }
    
    return $this->successMethod();
}

public function handleCustomerSubscriptionDeleted($payload)
{
    $subscription = $payload['data']['object'];
    $user = User::where('stripe_id', $subscription['customer'])->first();
    
    if ($user) {
        $user->update([
            'subscription_plan' => User::PLAN_FREE,
            'subscription_tier' => User::TIER_BASIC,
            'plan_started_at' => null,
            'monthly_pitch_count' => 0,
            'monthly_pitch_reset_date' => null,
        ]);
        
        Log::info('User subscription cancelled', ['user_id' => $user->id]);
    }
    
    return $this->successMethod();
}

private function determinePlanFromStripeSubscription($subscription): array
{
    $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
    
    $priceMapping = [
        config('subscription.stripe_prices.pro_artist') => ['plan' => 'pro', 'tier' => 'artist'],
        config('subscription.stripe_prices.pro_engineer') => ['plan' => 'pro', 'tier' => 'engineer'],
    ];
    
    return $priceMapping[$priceId] ?? ['plan' => 'free', 'tier' => 'basic'];
}
```

## Implementation Phase 5: Filament Admin Integration

### 1. Create SubscriptionResource
```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Subscriptions';
    protected static ?string $navigationGroup = 'Billing';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subscription_plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'free' => 'gray',
                        'pro' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('subscription_tier')
                    ->badge(),
                Tables\Columns\TextColumn::make('plan_started_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('projects_count')
                    ->counts('projects')
                    ->sortable(),
                Tables\Columns\TextColumn::make('active_pitches_count')
                    ->getStateUsing(function (User $record) {
                        return $record->pitches()->whereIn('status', [
                            'pending', 'in_progress', 'ready_for_review', 'pending_review'
                        ])->count();
                    }),
                Tables\Columns\TextColumn::make('monthly_pitch_count')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subscription_plan')
                    ->options([
                        'free' => 'Free',
                        'pro' => 'Pro',
                    ]),
                Tables\Filters\SelectFilter::make('subscription_tier')
                    ->options([
                        'basic' => 'Basic',
                        'artist' => 'Artist',
                        'engineer' => 'Engineer',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('manage_subscription')
                    ->icon('heroicon-o-cog')
                    ->url(fn (User $record): string => route('filament.admin.resources.subscriptions.manage', $record))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'manage' => Pages\ManageSubscription::route('/{record}/manage'),
        ];
    }
}
```

### 2. Create ManageSubscription Page
```php
<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use App\Models\User;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Illuminate\Contracts\Support\Htmlable;

class ManageSubscription extends Page
{
    protected static string $resource = SubscriptionResource::class;
    protected static string $view = 'filament.resources.subscription-resource.pages.manage-subscription';

    public User $record;

    public function getTitle(): string | Htmlable
    {
        return "Manage Subscription: {$this->record->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('upgrade_user')
                ->label('Upgrade to Pro')
                ->icon('heroicon-o-arrow-up')
                ->visible(fn () => $this->record->isFreePlan())
                ->form([
                    \Filament\Forms\Components\Select::make('tier')
                        ->label('Pro Tier')
                        ->options([
                            'artist' => 'Pro Artist',
                            'engineer' => 'Pro Engineer',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'subscription_plan' => 'pro',
                        'subscription_tier' => $data['tier'],
                        'plan_started_at' => now(),
                    ]);
                    
                    $this->notify('success', 'User upgraded to Pro successfully');
                }),
                
            Actions\Action::make('downgrade_user')
                ->label('Downgrade to Free')
                ->icon('heroicon-o-arrow-down')
                ->color('danger')
                ->visible(fn () => $this->record->isProPlan())
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'subscription_plan' => 'free',
                        'subscription_tier' => 'basic',
                        'plan_started_at' => null,
                    ]);
                    
                    $this->notify('success', 'User downgraded to Free successfully');
                }),
        ];
    }

    public function getSubscriptionData(): array
    {
        $user = $this->record;
        $limits = $user->getSubscriptionLimits();
        
        return [
            'current_plan' => [
                'plan' => $user->subscription_plan,
                'tier' => $user->subscription_tier,
                'started_at' => $user->plan_started_at,
            ],
            'usage' => [
                'projects_count' => $user->projects()->count(),
                'active_pitches_count' => $user->pitches()->whereIn('status', [
                    'pending', 'in_progress', 'ready_for_review', 'pending_review'
                ])->count(),
                'monthly_pitches_used' => $user->monthly_pitch_count,
            ],
            'limits' => $limits ? [
                'max_projects_owned' => $limits->max_projects_owned,
                'max_active_pitches' => $limits->max_active_pitches,
                'max_monthly_pitches' => $limits->max_monthly_pitches,
                'storage_per_project_mb' => $limits->storage_per_project_mb,
            ] : null,
            'stripe_data' => $this->getStripeSubscriptionData(),
        ];
    }

    private function getStripeSubscriptionData(): ?array
    {
        if (!$this->record->stripe_id) {
            return null;
        }

        try {
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $subscriptions = $stripe->subscriptions->all([
                'customer' => $this->record->stripe_id,
                'status' => 'active',
                'limit' => 1,
            ]);

            return $subscriptions->data[0] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
```

## Implementation Phase 6: Enhanced Features

### 1. Additional Simple Features for Subscriptions

#### Enhanced Storage Limits
- **Free**: 100MB per project
- **Pro**: 500MB per project + priority upload speeds

#### Priority Features
- **Free**: Standard support (community forum)
- **Pro**: Priority email support with faster response times

#### Portfolio Enhancements
- **Free**: Basic portfolio page
- **Pro**: Custom portfolio themes, advanced analytics, custom domain support

#### File Upload Enhancements
- **Free**: Basic file types (MP3, WAV, PDF)
- **Pro**: Extended file types + batch upload capabilities

### 2. Usage Analytics and Insights

#### Create SubscriptionAnalyticsService
```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use Carbon\Carbon;

class SubscriptionAnalyticsService
{
    public function getUserUsageInsights(User $user): array
    {
        $limits = $user->getSubscriptionLimits();
        
        return [
            'projects' => [
                'current' => $user->projects()->count(),
                'limit' => $limits?->max_projects_owned,
                'percentage' => $this->calculateUsagePercentage(
                    $user->projects()->count(),
                    $limits?->max_projects_owned
                ),
            ],
            'active_pitches' => [
                'current' => $user->pitches()->whereIn('status', [
                    Pitch::STATUS_PENDING,
                    Pitch::STATUS_IN_PROGRESS,
                    Pitch::STATUS_READY_FOR_REVIEW,
                ])->count(),
                'limit' => $limits?->max_active_pitches,
                'percentage' => $this->calculateUsagePercentage(
                    $user->pitches()->whereIn('status', [
                        Pitch::STATUS_PENDING,
                        Pitch::STATUS_IN_PROGRESS,
                        Pitch::STATUS_READY_FOR_REVIEW,
                    ])->count(),
                    $limits?->max_active_pitches
                ),
            ],
            'monthly_pitches' => [
                'current' => $user->monthly_pitch_count,
                'limit' => $limits?->max_monthly_pitches,
                'percentage' => $this->calculateUsagePercentage(
                    $user->monthly_pitch_count,
                    $limits?->max_monthly_pitches
                ),
                'reset_date' => $user->monthly_pitch_reset_date,
            ],
            'storage' => $this->getStorageUsageByProject($user),
        ];
    }
    
    private function calculateUsagePercentage(?int $current, ?int $limit): ?float
    {
        if (!$limit || $limit === 0) {
            return null; // Unlimited
        }
        
        return min(100, ($current / $limit) * 100);
    }
    
    private function getStorageUsageByProject(User $user): array
    {
        $projects = $user->projects()->with('files', 'pitches.files')->get();
        $userStorageLimit = $user->getProjectStorageLimit();
        
        return $projects->map(function ($project) use ($userStorageLimit) {
            $totalUsed = $project->getCombinedStorageUsed();
            
            return [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'storage_used' => $totalUsed,
                'storage_limit' => $userStorageLimit,
                'percentage' => ($totalUsed / $userStorageLimit) * 100,
                'formatted_used' => \Illuminate\Support\Number::fileSize($totalUsed),
                'formatted_limit' => \Illuminate\Support\Number::fileSize($userStorageLimit),
            ];
        })->toArray();
    }
}
```

## Implementation Phase 7: Testing Strategy

### 1. Unit Tests for Subscription Logic
```php
<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\SubscriptionLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function free_user_has_project_limit()
    {
        // Create subscription limits
        SubscriptionLimit::create([
            'plan_name' => 'free',
            'plan_tier' => 'basic',
            'max_projects_owned' => 1,
            'max_active_pitches' => 3,
        ]);

        $user = User::factory()->create([
            'subscription_plan' => 'free',
            'subscription_tier' => 'basic',
        ]);

        $this->assertTrue($user->canCreateProject());

        // Create a project
        \App\Models\Project::factory()->create(['user_id' => $user->id]);

        $this->assertFalse($user->canCreateProject());
    }

    /** @test */
    public function pro_user_has_unlimited_projects()
    {
        SubscriptionLimit::create([
            'plan_name' => 'pro',
            'plan_tier' => 'artist',
            'max_projects_owned' => null, // unlimited
            'max_active_pitches' => null,
        ]);

        $user = User::factory()->create([
            'subscription_plan' => 'pro',
            'subscription_tier' => 'artist',
        ]);

        $this->assertTrue($user->canCreateProject());

        // Create multiple projects
        \App\Models\Project::factory()->count(5)->create(['user_id' => $user->id]);

        $this->assertTrue($user->canCreateProject());
    }

    /** @test */
    public function engineer_has_monthly_pitch_limit()
    {
        SubscriptionLimit::create([
            'plan_name' => 'pro',
            'plan_tier' => 'engineer',
            'max_projects_owned' => null,
            'max_active_pitches' => null,
            'max_monthly_pitches' => 5,
        ]);

        $user = User::factory()->create([
            'subscription_plan' => 'pro',
            'subscription_tier' => 'engineer',
            'monthly_pitch_count' => 4,
            'monthly_pitch_reset_date' => now()->addDays(15),
        ]);

        $this->assertTrue($user->canCreateMonthlyPitch());

        $user->incrementMonthlyPitchCount();

        $this->assertFalse($user->canCreateMonthlyPitch());
    }
}
```

### 2. Feature Tests for Subscription Workflows
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SubscriptionLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubscriptionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function free_user_is_redirected_when_project_limit_reached()
    {
        SubscriptionLimit::create([
            'plan_name' => 'free',
            'plan_tier' => 'basic',
            'max_projects_owned' => 1,
        ]);

        $user = User::factory()->create([
            'subscription_plan' => 'free',
            'subscription_tier' => 'basic',
        ]);

        // Create a project to reach limit
        \App\Models\Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get(route('projects.create'));

        $response->assertRedirect(route('pricing'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function subscription_upgrade_flow_works()
    {
        $user = User::factory()->create([
            'subscription_plan' => 'free',
            'subscription_tier' => 'basic',
        ]);

        // Mock successful subscription
        $user->update([
            'subscription_plan' => 'pro',
            'subscription_tier' => 'artist',
            'plan_started_at' => now(),
        ]);

        $this->assertTrue($user->isProPlan());
        $this->assertEquals('artist', $user->subscription_tier);
    }
}
```

## Deployment Checklist

### Pre-Deployment
- [ ] Create Stripe products and prices in dashboard
- [ ] Update config/subscription.php with Stripe price IDs
- [ ] Run database migrations
- [ ] Seed subscription limits
- [ ] Update existing users to have default free plan
- [ ] Test webhook endpoints

### Post-Deployment
- [ ] Monitor webhook processing
- [ ] Verify subscription limits are enforced
- [ ] Test upgrade/downgrade flows
- [ ] Monitor user feedback
- [ ] Set up analytics tracking

## Configuration Requirements

### Config/subscription.php
```php
<?php

return [
    'stripe_prices' => [
        'pro_artist' => env('STRIPE_PRICE_PRO_ARTIST'),
        'pro_engineer' => env('STRIPE_PRICE_PRO_ENGINEER'),
    ],
    
    'trial_days' => 14,
    
    'features' => [
        'free' => [
            'max_projects' => 1,
            'max_active_pitches' => 3,
            'storage_per_project_mb' => 100,
        ],
        'pro_artist' => [
            'max_projects' => null, // unlimited
            'max_active_pitches' => null,
            'storage_per_project_mb' => 500,
        ],
        'pro_engineer' => [
            'max_projects' => null,
            'max_active_pitches' => null,
            'max_monthly_pitches' => 5,
            'storage_per_project_mb' => 500,
        ],
    ],
];
```

## Future Enhancements

### Advanced Features (Post-MVP)
1. **Team Collaboration**: Allow Pro users to invite collaborators
2. **Analytics Dashboard**: Detailed usage and performance metrics
3. **Custom Branding**: White-label portfolios for Pro users
4. **API Access**: Rate-limited API for Pro users
5. **Advanced File Management**: Version control, automated backups
6. **Priority Queue**: Faster processing for Pro user uploads

### Enterprise Features
1. **Multi-user accounts**: Organization-level subscriptions
2. **Advanced permissions**: Role-based access within teams
3. **Custom integrations**: Slack, Discord notifications
4. **Dedicated support**: Phone/video support for enterprise

This implementation plan provides a solid foundation for subscription features while leveraging the existing infrastructure and maintaining the current user experience for free users. 
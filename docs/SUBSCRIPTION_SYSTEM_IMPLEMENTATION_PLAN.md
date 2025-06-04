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
- ❌ No feature restrictions based on subscription
- ❌ No admin interface for managing subscription limits
- ❌ No subscription upgrade/downgrade flow

## Implementation Progress

### ✅ PHASE 1: CORE SUBSCRIPTION SYSTEM (COMPLETED)

#### Database Schema Extensions
- ✅ **Users table extensions**: Added subscription_plan, subscription_tier, plan_started_at, monthly_pitch_count, monthly_pitch_reset_date
- ✅ **SubscriptionLimit model**: Created table to store plan features and limits
- ✅ **Project priority fields**: Added is_prioritized and priority_order for Pro features

#### Models and Business Logic
- ✅ **SubscriptionLimit model**: Manages plan features and limits
- ✅ **User model enhancements**: Added subscription-related methods:
  - `isFreePlan()` / `isProPlan()`
  - `getSubscriptionLimits()`
  - `canCreateProject()`
  - `canCreatePitch()`
  - `canCreateMonthlyPitch()`
  - `getProjectStorageLimit()`
- ✅ **Subscription constants**: Added plan and tier constants to User model

#### Middleware and Route Protection
- ✅ **SubscriptionCheck middleware**: Enforces subscription limits
- ✅ **Route protection**: Ready to be applied to project/pitch creation routes

#### Admin Interface (Filament)
- ✅ **SubscriptionLimit resource**: Full CRUD interface for managing plan limits
- ✅ **User resource enhancements**: Added subscription fields and filters
- ✅ **Navigation grouping**: Organized under "Subscriptions" group

#### Controllers and Routes
- ✅ **SubscriptionController**: Handles subscription management, upgrades, success/cancel pages
- ✅ **Route structure**: `/subscription/*` routes for user subscription management
- ✅ **Configuration**: `config/subscription.php` for plan features and Stripe price IDs

#### Webhook Integration
- ✅ **Enhanced WebhookController**: Handles subscription lifecycle events
- ✅ **Automatic plan updates**: Updates user subscription status from Stripe webhooks
- ✅ **Price ID mapping**: Maps Stripe prices to internal plan/tier combinations

#### Data Seeding
- ✅ **SubscriptionLimitsSeeder**: Populates default plan limits
- ✅ **User migration**: Updated existing users to have default free plan

### 🔄 PHASE 2: FEATURE ENFORCEMENT (NEXT)

#### Route Middleware Application
- ❌ Apply `subscription:create_project` middleware to project creation routes
- ❌ Apply `subscription:create_pitch` middleware to pitch creation routes
- ❌ Update project upload controllers to check storage limits

#### UI/UX Enhancements
- ❌ Add subscription status indicators to dashboard
- ❌ Create upgrade prompts when limits are reached
- ❌ Add usage meters (projects used, pitches active, storage used)
- ❌ Update pricing page with actual subscription integration

#### Storage Enforcement
- ❌ Implement project storage limit checking in file upload handlers
- ❌ Add storage usage tracking and display
- ❌ Prevent uploads when storage limit exceeded

### 🔄 PHASE 3: USER EXPERIENCE (PENDING)

#### Subscription Management Views
- ❌ Create subscription dashboard (`resources/views/subscription/index.blade.php`)
- ❌ Create upgrade success page (`resources/views/subscription/success.blade.php`)
- ❌ Add subscription status to user profile
- ❌ Create billing history integration

#### Upgrade Flow
- ❌ Implement Stripe Checkout integration for upgrades
- ❌ Add plan comparison and selection interface
- ❌ Handle subscription cancellation and downgrade logic

#### Notifications and Communication
- ❌ Email notifications for subscription changes
- ❌ Limit reached notifications
- ❌ Billing failure notifications

### 🔄 PHASE 4: ADVANCED FEATURES (PENDING)

#### Pro-Only Features
- ❌ Project prioritization system for Pro users
- ❌ Custom portfolio layouts for Pro users
- ❌ Priority support ticket system
- ❌ Advanced analytics for Pro users

#### Monthly Limits (Pro Engineer)
- ❌ Monthly pitch limit enforcement
- ❌ Monthly reset automation
- ❌ Usage tracking and reporting

## Subscription Plans Structure

### Free Plan (Basic Tier)
- **Projects**: 1 maximum
- **Active Pitches**: 3 maximum
- **Storage**: 100MB per project
- **Support**: Basic (community)
- **Portfolio**: Standard layout only

### Pro Artist Plan
- **Projects**: Unlimited
- **Active Pitches**: Unlimited
- **Storage**: 500MB per project
- **Support**: Priority support
- **Portfolio**: Custom layouts
- **Features**: Project prioritization

### Pro Engineer Plan
- **Projects**: Unlimited
- **Active Pitches**: Unlimited
- **Monthly Pitches**: 5 per month (for receiving work)
- **Storage**: 500MB per project
- **Support**: Priority support
- **Portfolio**: Custom layouts

## Technical Implementation Details

### Database Schema

```sql
-- Users table additions
ALTER TABLE users ADD COLUMN subscription_plan VARCHAR(50) DEFAULT 'free';
ALTER TABLE users ADD COLUMN subscription_tier VARCHAR(50) DEFAULT 'basic';
ALTER TABLE users ADD COLUMN plan_started_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN monthly_pitch_count INT DEFAULT 0;
ALTER TABLE users ADD COLUMN monthly_pitch_reset_date DATE NULL;

-- Subscription limits table
CREATE TABLE subscription_limits (
    id BIGINT PRIMARY KEY,
    plan_name VARCHAR(50),
    plan_tier VARCHAR(50),
    max_projects_owned INT NULL, -- NULL = unlimited
    max_active_pitches INT NULL, -- NULL = unlimited
    max_monthly_pitches INT NULL, -- For Pro Engineer
    storage_per_project_mb INT DEFAULT 100,
    priority_support BOOLEAN DEFAULT FALSE,
    custom_portfolio BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(plan_name, plan_tier)
);

-- Projects table additions
ALTER TABLE projects ADD COLUMN is_prioritized BOOLEAN DEFAULT FALSE;
ALTER TABLE projects ADD COLUMN priority_order INT NULL;
```

### Configuration

```php
// config/subscription.php
return [
    'stripe_prices' => [
        'pro_artist' => env('STRIPE_PRICE_PRO_ARTIST'),
        'pro_engineer' => env('STRIPE_PRICE_PRO_ENGINEER'),
    ],
    'plans' => [
        'free' => ['name' => 'Free', 'price' => 0],
        'pro_artist' => ['name' => 'Pro Artist', 'price' => 29],
        'pro_engineer' => ['name' => 'Pro Engineer', 'price' => 19],
    ],
];
```

### Middleware Usage

```php
// Apply to routes
Route::middleware(['auth', 'subscription:create_project'])->group(function () {
    Route::get('/projects/create', [ProjectController::class, 'create']);
    Route::post('/projects', [ProjectController::class, 'store']);
});

Route::middleware(['auth', 'subscription:create_pitch'])->group(function () {
    Route::get('/projects/{project}/pitches/create', [PitchController::class, 'create']);
    Route::post('/projects/{project}/pitches', [PitchController::class, 'store']);
});
```

## Next Steps

1. **Apply middleware to routes** - Protect project and pitch creation with subscription checks
2. **Create subscription views** - Build user-facing subscription management interface
3. **Implement storage enforcement** - Add file upload size checking
4. **Add upgrade prompts** - Guide users to upgrade when they hit limits
5. **Test subscription flow** - End-to-end testing of upgrade/downgrade process

## Environment Variables Required

```env
STRIPE_PRICE_PRO_ARTIST=price_1234567890
STRIPE_PRICE_PRO_ENGINEER=price_0987654321
```

## Testing Checklist

- ✅ Database migrations run successfully
- ✅ Subscription limits are seeded correctly
- ✅ User subscription methods work correctly
- ✅ Filament admin interface displays subscription data
- ❌ Middleware blocks creation when limits exceeded
- ❌ Webhook updates user subscription status
- ❌ Stripe checkout creates subscriptions
- ❌ Subscription cancellation works properly

## Monitoring and Analytics

Future considerations for tracking:
- Subscription conversion rates
- Feature usage by plan type
- Churn analysis
- Revenue metrics
- Support ticket volume by plan type

---

**Status**: Phase 1 Complete ✅ | Phase 2 In Progress 🔄
**Last Updated**: December 2024
**Next Review**: After Phase 2 completion 
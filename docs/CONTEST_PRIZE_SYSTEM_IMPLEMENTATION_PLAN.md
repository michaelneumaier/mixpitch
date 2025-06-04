# Contest Prize System Implementation Plan

## Overview

This document outlines the comprehensive implementation plan for a robust, multi-tiered prize system for contest projects. The current system only supports a single `prize_amount` field, but the new system will support individual prizes for 1st, 2nd, 3rd place, and runner-ups with both cash and non-cash prize types.

## Current System Analysis

### Current Implementation Issues
1. **Redundant Parameters**: Project budget/deadline and contest submission/judging deadlines overlap
2. **Single Prize Field**: Only `prize_amount` exists for 1st place
3. **Limited Prize Types**: Only cash prizes are supported
4. **No Multi-Tier Prizes**: No support for different prizes per placement

### Current Database Schema
```sql
-- projects table
prize_amount DECIMAL(10,2) NULL
prize_currency VARCHAR(3) NULL
submission_deadline DATETIME NULL
judging_deadline DATETIME NULL
budget DECIMAL(10,2) NULL
deadline DATETIME NULL
```

## Phase 1: Interface Streamlining & Database Redesign

### 1.1 Project Configuration UI Changes

**Problem**: Redundancy between Project parameters and Contest parameters
**Solution**: Hide standard project budget/deadline for contests, use contest-specific parameters

#### Changes to CreateProject.php
```php
// Remove budget/deadline display for contests
if ($this->workflow_type === Project::WORKFLOW_TYPE_CONTEST) {
    // Use submission_deadline as the main deadline
    // Don't show standard budget fields
    // Auto-populate: 
    // - deadline = submission_deadline
    // - budget = sum of all cash prizes
}
```

### 1.2 Database Schema Redesign

#### New `contest_prizes` Table
```sql
CREATE TABLE contest_prizes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    placement ENUM('1st', '2nd', '3rd', 'runner_up') NOT NULL,
    prize_type ENUM('cash', 'other') NOT NULL,
    
    -- Cash prize fields
    cash_amount DECIMAL(10,2) NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    
    -- Other prize fields
    prize_title VARCHAR(255) NULL,
    prize_description TEXT NULL,
    prize_value_estimate DECIMAL(10,2) NULL, -- Optional estimated value
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_placement (project_id, placement)
);
```

#### Migration Strategy
```php
// Migration: create_contest_prizes_table.php
// 1. Create new table
// 2. Migrate existing prize_amount data to contest_prizes
// 3. Keep old columns for backward compatibility initially
// 4. Remove old columns in future migration after full testing
```

### 1.3 Models & Relationships

#### New ContestPrize Model
```php
<?php

namespace App\Models;

class ContestPrize extends Model
{
    const PLACEMENT_FIRST = '1st';
    const PLACEMENT_SECOND = '2nd';
    const PLACEMENT_THIRD = '3rd';
    const PLACEMENT_RUNNER_UP = 'runner_up';
    
    const TYPE_CASH = 'cash';
    const TYPE_OTHER = 'other';
    
    protected $fillable = [
        'project_id', 'placement', 'prize_type',
        'cash_amount', 'currency', 'prize_title', 
        'prize_description', 'prize_value_estimate'
    ];
    
    protected $casts = [
        'cash_amount' => 'decimal:2',
        'prize_value_estimate' => 'decimal:2'
    ];
    
    // Relationships
    public function project() { return $this->belongsTo(Project::class); }
    
    // Helper methods
    public function isCashPrize(): bool { return $this->prize_type === self::TYPE_CASH; }
    public function getDisplayValue(): string { /* Format for display */ }
    public function getTotalCashValue(): float { /* For budget calculations */ }
}
```

#### Enhanced Project Model
```php
// Add to Project.php
public function contestPrizes()
{
    return $this->hasMany(ContestPrize::class);
}

public function getTotalPrizeBudget(): float
{
    return $this->contestPrizes()
        ->where('prize_type', ContestPrize::TYPE_CASH)
        ->sum('cash_amount');
}

public function getPrizeForPlacement(string $placement): ?ContestPrize
{
    return $this->contestPrizes()
        ->where('placement', $placement)
        ->first();
}
```

## Phase 2: Prize Configuration UI

### 2.1 Contest Creation Interface

#### Prize Configuration Component
```php
// New Livewire Component: ContestPrizeConfigurator
class ContestPrizeConfigurator extends Component
{
    public $prizes = [
        '1st' => ['type' => 'none', 'cash_amount' => null, 'currency' => 'USD', 'title' => '', 'description' => ''],
        '2nd' => ['type' => 'none', 'cash_amount' => null, 'currency' => 'USD', 'title' => '', 'description' => ''],
        '3rd' => ['type' => 'none', 'cash_amount' => null, 'currency' => 'USD', 'title' => '', 'description' => ''],
        'runner_up' => ['type' => 'none', 'cash_amount' => null, 'currency' => 'USD', 'title' => '', 'description' => '']
    ];
    
    public function setPrizeType($placement, $type) { /* Handle type changes */ }
    public function validatePrizes() { /* Validation logic */ }
    public function getTotalCashPrizes() { /* Calculate total */ }
}
```

#### UI Design Specifications
```blade
{{-- Contest Prize Configuration UI --}}
<div class="bg-gradient-to-br from-yellow-50 to-amber-50 border border-amber-200 rounded-xl p-6">
    <h3 class="text-xl font-bold text-amber-800 mb-6 flex items-center">
        <i class="fas fa-trophy text-amber-600 mr-3"></i>
        Prize Configuration
    </h3>
    
    @foreach(['1st', '2nd', '3rd', 'runner_up'] as $placement)
        <div class="prize-tier mb-6 p-4 bg-white rounded-lg border">
            <div class="flex items-center justify-between mb-4">
                <h4 class="font-semibold">
                    {{ $placement === 'runner_up' ? 'Runner-ups' : $placement . ' Place' }}
                    @if($placement === '1st') 🥇 @elseif($placement === '2nd') 🥈 @elseif($placement === '3rd') 🥉 @else 🏅 @endif
                </h4>
                <select wire:model="prizes.{{ $placement }}.type" class="prize-type-selector">
                    <option value="none">No Prize</option>
                    <option value="cash">Cash Prize</option>
                    <option value="other">Other Prize</option>
                </select>
            </div>
            
            @if($prizes[$placement]['type'] === 'cash')
                {{-- Cash prize configuration --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label>Amount</label>
                        <div class="flex">
                            <select wire:model="prizes.{{ $placement }}.currency" class="currency-selector">
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                            </select>
                            <input type="number" wire:model="prizes.{{ $placement }}.cash_amount" 
                                   placeholder="0.00" class="amount-input">
                        </div>
                    </div>
                </div>
            @elseif($prizes[$placement]['type'] === 'other')
                {{-- Other prize configuration --}}
                <div class="space-y-4">
                    <div>
                        <label>Prize Title</label>
                        <input type="text" wire:model="prizes.{{ $placement }}.title" 
                               placeholder="e.g., Software License, T-shirt, etc.">
                    </div>
                    <div>
                        <label>Description</label>
                        <textarea wire:model="prizes.{{ $placement }}.description" 
                                  placeholder="Describe the prize details..."></textarea>
                    </div>
                    <div>
                        <label>Estimated Value (Optional)</label>
                        <input type="number" wire:model="prizes.{{ $placement }}.value_estimate" 
                               placeholder="For reference only">
                    </div>
                </div>
            @endif
        </div>
    @endforeach
    
    {{-- Prize Summary --}}
    <div class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg">
        <h4 class="font-semibold mb-2">Prize Summary</h4>
        <div class="text-sm space-y-1">
            <div>Total Cash Prizes: ${{ number_format($this->getTotalCashPrizes(), 2) }}</div>
            <div>Non-Cash Prizes: {{ $this->getNonCashPrizesCount() }}</div>
        </div>
    </div>
</div>
```

### 2.2 Edit Project Interface

#### Enhanced CreateProject Component
```php
// Modified CreateProject.php for contest editing
if ($this->workflow_type === Project::WORKFLOW_TYPE_CONTEST) {
    // Load existing prizes
    $this->loadExistingPrizes();
    
    // Auto-populate main project fields from contest settings
    $totalCash = $this->getTotalCashPrizes();
    if ($totalCash > 0) {
        $this->form->budgetType = 'paid';
        $this->form->budget = $totalCash;
    }
    
    if ($this->submission_deadline) {
        $this->form->deadline = $this->submission_deadline;
    }
}
```

## Phase 3: Prize Distribution System

### 3.1 Winner Notification Enhancement

#### Enhanced NotificationService
```php
// Enhanced contest winner notifications
public function notifyContestWinnerWithPrize(Pitch $pitch, ContestPrize $prize): ?Notification
{
    $data = [
        'project_name' => $pitch->project->name,
        'placement' => $prize->placement,
        'prize_type' => $prize->prize_type,
        'prize_details' => $prize->getDisplayValue(),
    ];
    
    if ($prize->isCashPrize()) {
        $data['cash_amount'] = $prize->cash_amount;
        $data['currency'] = $prize->currency;
        $data['requires_payment_setup'] = true;
    } else {
        $data['prize_title'] = $prize->prize_title;
        $data['prize_description'] = $prize->prize_description;
        $data['requires_coordination'] = true;
    }
    
    return $this->createNotification($pitch->user, NotificationType::CONTEST_WINNER_PRIZE, $pitch, $data);
}
```

### 3.2 Prize Distribution Workflow

#### PrizeDistributionService
```php
<?php

namespace App\Services;

class PrizeDistributionService
{
    public function distributePrize(ContestPrize $prize, User $winner): PrizeDistribution
    {
        $distribution = PrizeDistribution::create([
            'contest_prize_id' => $prize->id,
            'winner_user_id' => $winner->id,
            'status' => PrizeDistribution::STATUS_PENDING,
            'distribution_method' => $prize->isCashPrize() ? 'payment' : 'coordination'
        ]);
        
        if ($prize->isCashPrize()) {
            return $this->handleCashPrizeDistribution($distribution, $prize);
        } else {
            return $this->handleOtherPrizeDistribution($distribution, $prize);
        }
    }
    
    private function handleCashPrizeDistribution(PrizeDistribution $distribution, ContestPrize $prize)
    {
        // Create invoice using existing InvoiceService
        $invoice = app(InvoiceService::class)->createInvoiceForContestPrize(
            $prize->project,
            $distribution->winner,
            $prize->cash_amount,
            $prize->currency
        );
        
        $distribution->update([
            'invoice_id' => $invoice->id,
            'status' => PrizeDistribution::STATUS_PAYMENT_PROCESSING
        ]);
        
        return $distribution;
    }
    
    private function handleOtherPrizeDistribution(PrizeDistribution $distribution, ContestPrize $prize)
    {
        // Create coordination record and notify project owner
        $distribution->update([
            'status' => PrizeDistribution::STATUS_COORDINATION_REQUIRED,
            'coordination_notes' => "Prize: {$prize->prize_title}\nDescription: {$prize->prize_description}"
        ]);
        
        // Notify project owner about coordination needed
        app(NotificationService::class)->notifyProjectOwnerPrizeCoordinationNeeded($distribution);
        
        return $distribution;
    }
}
```

### 3.3 Prize Distribution Tracking

#### PrizeDistribution Model
```php
<?php

namespace App\Models;

class PrizeDistribution extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_PAYMENT_PROCESSING = 'payment_processing';
    const STATUS_COORDINATION_REQUIRED = 'coordination_required';
    const STATUS_DISTRIBUTED = 'distributed';
    const STATUS_FAILED = 'failed';
    
    protected $fillable = [
        'contest_prize_id', 'winner_user_id', 'status',
        'distribution_method', 'invoice_id', 'coordination_notes',
        'distributed_at', 'failed_reason'
    ];
    
    protected $casts = [
        'distributed_at' => 'datetime'
    ];
    
    public function contestPrize() { return $this->belongsTo(ContestPrize::class); }
    public function winner() { return $this->belongsTo(User::class, 'winner_user_id'); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
}
```

## Phase 4: Enhanced Contest Judging

### 4.1 Updated ContestJudgingService

```php
// Enhanced finalization to handle multiple prize types
protected function updatePitchStatuses(Project $project): void
{
    $contestResult = $project->contestResult;
    
    // Handle each placement type
    $placements = [
        '1st' => $contestResult->first_place_pitch_id,
        '2nd' => $contestResult->second_place_pitch_id,
        '3rd' => $contestResult->third_place_pitch_id,
    ];
    
    foreach ($placements as $placement => $pitchId) {
        if (!$pitchId) continue;
        
        $pitch = Pitch::find($pitchId);
        $prize = $project->getPrizeForPlacement($placement);
        
        if ($prize) {
            // Distribute the prize
            app(PrizeDistributionService::class)->distributePrize($prize, $pitch->user);
        }
        
        // Update pitch status
        $pitch->update([
            'status' => Pitch::STATUS_CONTEST_WINNER,
            'placement_finalized_at' => now()
        ]);
        
        // Send appropriate notification
        if ($prize) {
            app(NotificationService::class)->notifyContestWinnerWithPrize($pitch, $prize);
        } else {
            app(NotificationService::class)->notifyContestWinnerNoPrize($pitch);
        }
    }
    
    // Handle runner-ups
    if (!empty($contestResult->runner_up_pitch_ids)) {
        $runnerUpPrize = $project->getPrizeForPlacement('runner_up');
        
        foreach ($contestResult->runner_up_pitch_ids as $pitchId) {
            $pitch = Pitch::find($pitchId);
            
            if ($runnerUpPrize) {
                app(PrizeDistributionService::class)->distributePrize($runnerUpPrize, $pitch->user);
                app(NotificationService::class)->notifyContestWinnerWithPrize($pitch, $runnerUpPrize);
            } else {
                app(NotificationService::class)->notifyContestRunnerUpNoPrize($pitch);
            }
            
            $pitch->update([
                'status' => Pitch::STATUS_CONTEST_RUNNER_UP,
                'placement_finalized_at' => now()
            ]);
        }
    }
}
```

## Phase 5: User Experience Enhancements

### 5.1 Contest Results Display

#### Enhanced Results Page
```blade
{{-- Enhanced contest results with prize display --}}
@foreach($placements as $placement => $pitch)
    @if($pitch)
        @php $prize = $project->getPrizeForPlacement($placement) @endphp
        <div class="winner-card">
            <div class="placement-badge">{{ $placement }} Place</div>
            <div class="winner-info">
                <h3>{{ $pitch->user->name }}</h3>
                @if($prize)
                    <div class="prize-info">
                        @if($prize->isCashPrize())
                            <div class="cash-prize">
                                <i class="fas fa-dollar-sign"></i>
                                {{ $prize->currency }} {{ number_format($prize->cash_amount, 2) }}
                            </div>
                        @else
                            <div class="other-prize">
                                <i class="fas fa-gift"></i>
                                {{ $prize->prize_title }}
                                @if($prize->prize_description)
                                    <p class="prize-description">{{ $prize->prize_description }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    <div class="no-prize">Recognition Prize</div>
                @endif
            </div>
        </div>
    @endif
@endforeach
```

### 5.2 Contestant Prize Preview

#### Contest Entry Page Enhancement
```blade
{{-- Show prizes to potential contestants --}}
<div class="contest-prizes-preview">
    <h3>Contest Prizes</h3>
    @foreach(['1st', '2nd', '3rd', 'runner_up'] as $placement)
        @php $prize = $project->getPrizeForPlacement($placement) @endphp
        @if($prize)
            <div class="prize-tier">
                <div class="placement">{{ $placement === 'runner_up' ? 'Runner-ups' : $placement . ' Place' }}</div>
                <div class="prize-details">
                    @if($prize->isCashPrize())
                        <strong>{{ $prize->currency }} {{ number_format($prize->cash_amount, 2) }}</strong>
                    @else
                        <strong>{{ $prize->prize_title }}</strong>
                        @if($prize->prize_description)
                            <p>{{ $prize->prize_description }}</p>
                        @endif
                    @endif
                </div>
            </div>
        @endif
    @endforeach
</div>
```

### 5.3 Prize Management Dashboard

#### For Contest Owners
```php
// New Livewire Component: PrizeManagementDashboard
class PrizeManagementDashboard extends Component
{
    public Project $project;
    
    public function render()
    {
        $distributions = PrizeDistribution::whereHas('contestPrize', function($q) {
            $q->where('project_id', $this->project->id);
        })->with(['contestPrize', 'winner', 'invoice'])->get();
        
        return view('livewire.prize-management-dashboard', compact('distributions'));
    }
    
    public function markPrizeDistributed($distributionId)
    {
        $distribution = PrizeDistribution::find($distributionId);
        $distribution->update([
            'status' => PrizeDistribution::STATUS_DISTRIBUTED,
            'distributed_at' => now()
        ]);
        
        // Notify winner that prize has been distributed
        app(NotificationService::class)->notifyPrizeDistributed($distribution);
    }
}
```

## Phase 6: Analytics & Reporting

### 6.1 Enhanced Contest Analytics

#### Prize Analytics
```php
// Add to ContestJudgingController::analytics
$prizeAnalytics = [
    'total_cash_prizes' => $project->getTotalPrizeBudget(),
    'prize_distribution_status' => PrizeDistribution::whereHas('contestPrize', function($q) use ($project) {
        $q->where('project_id', $project->id);
    })->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
    'prizes_by_type' => $project->contestPrizes()->groupBy('prize_type')->selectRaw('prize_type, count(*) as count')->pluck('count', 'prize_type')
];
```

## Phase 7: Data Migration & Backward Compatibility

### 7.1 Migration Strategy

```php
// Migration to preserve existing data
public function up()
{
    // 1. Create new contest_prizes table
    // 2. Migrate existing prize_amount data
    DB::statement("
        INSERT INTO contest_prizes (project_id, placement, prize_type, cash_amount, currency)
        SELECT id, '1st', 'cash', prize_amount, COALESCE(prize_currency, 'USD')
        FROM projects 
        WHERE workflow_type = 'contest' AND prize_amount > 0
    ");
    
    // 3. Update existing project budgets
    DB::statement("
        UPDATE projects p
        SET budget = (
            SELECT SUM(cash_amount) 
            FROM contest_prizes cp 
            WHERE cp.project_id = p.id AND cp.prize_type = 'cash'
        )
        WHERE workflow_type = 'contest'
    ");
}
```

### 7.2 Backward Compatibility Layer

```php
// Add to Project model for transition period
public function getPrizeAmountAttribute()
{
    // Legacy accessor - returns 1st place cash prize
    $firstPrize = $this->getPrizeForPlacement('1st');
    return $firstPrize && $firstPrize->isCashPrize() ? $firstPrize->cash_amount : 0;
}
```

## Phase 8: Testing Strategy

### 8.1 Comprehensive Test Suite

```php
// Feature tests for prize system
class ContestPrizeSystemTest extends TestCase
{
    /** @test */
    public function contest_owner_can_configure_mixed_prizes()
    {
        // Test: $100 1st, Software 2nd, T-shirt 3rd, No runner-up prize
    }
    
    /** @test */  
    public function contest_finalization_distributes_all_prizes()
    {
        // Test: Cash prizes create invoices, other prizes create coordination records
    }
    
    /** @test */
    public function prize_analytics_display_correctly()
    {
        // Test: Analytics show correct prize distribution and values
    }
    
    /** @test */
    public function existing_contests_migrate_correctly()
    {
        // Test: Old prize_amount becomes 1st place cash prize
    }
}
```

## Phase 9: Documentation & Training

### 9.1 User Documentation

1. **Contest Creator Guide**: How to set up multi-tier prizes
2. **Prize Distribution Guide**: Managing cash vs non-cash prizes  
3. **Winner Guide**: What to expect when winning different prize types
4. **Admin Guide**: Managing prize distribution and troubleshooting

### 9.2 Technical Documentation  

1. **API Documentation**: New endpoints and data structures
2. **Database Schema**: Complete ERD with relationships
3. **Service Documentation**: Prize distribution workflows
4. **Migration Guide**: Updating existing contests

## Implementation Timeline

### Week 1-2: Foundation
- Database schema design and migration
- Basic ContestPrize model and relationships
- Migration of existing data

### Week 3-4: Core Prize System
- Prize configuration UI components
- Enhanced project creation/editing
- Basic prize distribution service

### Week 5-6: Advanced Features  
- Prize distribution workflows
- Enhanced notifications
- Prize management dashboard

### Week 7-8: User Experience
- Contest results enhancement
- Analytics integration
- Mobile responsive design

### Week 9-10: Testing & Optimization
- Comprehensive test suite
- Performance optimization
- User acceptance testing

### Week 11-12: Documentation & Deployment
- Complete documentation
- Production deployment
- User training and support

## Risk Mitigation

### Technical Risks
1. **Data Migration Complexity**: Extensive testing with production data snapshots
2. **Payment Integration**: Robust error handling and rollback procedures
3. **Performance Impact**: Database indexing and query optimization

### User Experience Risks
1. **Interface Complexity**: Progressive disclosure and intuitive defaults
2. **Learning Curve**: Comprehensive documentation and examples
3. **Backward Compatibility**: Gradual migration and fallback options

## Success Metrics

### Quantitative Metrics
- 100% successful migration of existing contest data
- <2 second page load times for prize configuration
- >95% successful prize distribution rate
- Zero data loss incidents

### Qualitative Metrics  
- Positive user feedback on prize configuration UX
- Successful handling of complex prize scenarios
- Reduced support tickets about prize-related issues
- Enhanced contest participation rates

## Conclusion

This implementation plan provides a comprehensive roadmap for transforming the current single-prize contest system into a robust, multi-tiered prize platform that supports both cash and non-cash prizes across all placement levels. The phased approach ensures minimal disruption to existing functionality while delivering powerful new capabilities that will significantly enhance the contest experience for both creators and participants. 
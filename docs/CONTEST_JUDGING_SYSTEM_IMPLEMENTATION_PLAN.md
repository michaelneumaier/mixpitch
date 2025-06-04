# Contest Judging System Implementation Plan

## Overview
This document outlines the complete overhaul of the contest workflow system to replace the current basic winner selection with a comprehensive judging, ranking, and results system.

## Current State Analysis

### What Works Currently:
- Contest entry submission with snapshots ✅
- Basic winner/runner-up selection ✅ 
- Contest-specific storage limits and components ✅
- Contest workflow status tracking ✅

### Current Limitations:
- Uses standard pitch workflow components (Approve/Deny/Request Revisions) ❌
- No proper judging interface for ranking entries ❌
- No finalization process or restrictions ❌
- No comprehensive public results display ❌
- Limited notification system ❌

## Requirements Summary

### **Ranking System:**
- **1st, 2nd, 3rd Place**: Exclusive selections (only one entry per position)
- **Runner-ups**: Unlimited entries in this category
- **Default**: No selection required (judges can leave entries unranked)

### **Timeline Management:**
- **Submission Deadline**: Hard cutoff for contest entries
- **Judging Deadline**: Social obligation, not system-enforced
- **Finalization**: Only allowed after submission deadline passes
- **Re-judging**: Disabled after finalization (admin override for emergencies)

### **Public Results:**
- Display winners, runner-ups, and participants
- Show submissions for winners/runner-ups (with toggle option)
- List participants who didn't place

### **Judging Interface:**
- **Project Management Page**: Dropdown-based selection for each entry
- **Snapshot Page**: Individual entry judging interface
- **Dynamic Options**: Remove selected positions from other dropdowns
- **Real-time Updates**: Reflect changes across interfaces

### **Notifications:**
- All participants notified when results finalized
- Special winner notifications with specific placement
- Contest owner notifications

---

## Implementation Plan

### **Phase 1: Database Schema Updates**

#### **1.1 Projects Table Updates**
```sql
-- Add judging and results configuration
ALTER TABLE projects ADD COLUMN judging_finalized_at TIMESTAMP NULL;
ALTER TABLE projects ADD COLUMN show_submissions_publicly BOOLEAN DEFAULT TRUE;
ALTER TABLE projects ADD COLUMN judging_notes TEXT NULL;
```

#### **1.2 Pitches Table Updates**
```sql
-- Update rank field and add placement tracking
ALTER TABLE pitches MODIFY COLUMN rank ENUM('1st', '2nd', '3rd', 'runner-up') NULL;
ALTER TABLE pitches ADD COLUMN judging_notes TEXT NULL;
ALTER TABLE pitches ADD COLUMN placement_finalized_at TIMESTAMP NULL;
```

#### **1.3 New Contest Results Table**
```sql
CREATE TABLE contest_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    first_place_pitch_id BIGINT UNSIGNED NULL,
    second_place_pitch_id BIGINT UNSIGNED NULL,
    third_place_pitch_id BIGINT UNSIGNED NULL,
    runner_up_pitch_ids JSON NULL, -- Array of pitch IDs
    finalized_at TIMESTAMP NULL,
    finalized_by BIGINT UNSIGNED NULL,
    show_submissions_publicly BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (first_place_pitch_id) REFERENCES pitches(id) ON DELETE SET NULL,
    FOREIGN KEY (second_place_pitch_id) REFERENCES pitches(id) ON DELETE SET NULL,
    FOREIGN KEY (third_place_pitch_id) REFERENCES pitches(id) ON DELETE SET NULL,
    FOREIGN KEY (finalized_by) REFERENCES users(id) ON DELETE SET NULL,
    
    UNIQUE KEY unique_project_result (project_id)
);
```

---

### **Phase 2: Model Updates**

#### **2.1 Project Model (`app/Models/Project.php`)**
```php
// Add relationship and methods
public function contestResult()
{
    return $this->hasOne(ContestResult::class);
}

public function isJudgingFinalized(): bool
{
    return !is_null($this->judging_finalized_at);
}

public function canFinalizeJudging(): bool
{
    return $this->isContest() && 
           $this->submission_deadline && 
           $this->submission_deadline->isPast() && 
           !$this->isJudgingFinalized();
}

public function getContestEntries()
{
    return $this->pitches()
        ->whereIn('status', [
            Pitch::STATUS_CONTEST_ENTRY,
            Pitch::STATUS_CONTEST_WINNER,
            Pitch::STATUS_CONTEST_RUNNER_UP,
            Pitch::STATUS_CONTEST_NOT_SELECTED
        ])
        ->with(['user', 'currentSnapshot'])
        ->orderBy('created_at', 'asc')
        ->get();
}
```

#### **2.2 New ContestResult Model (`app/Models/ContestResult.php`)**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContestResult extends Model
{
    protected $fillable = [
        'project_id',
        'first_place_pitch_id',
        'second_place_pitch_id',
        'third_place_pitch_id',
        'runner_up_pitch_ids',
        'finalized_at',
        'finalized_by',
        'show_submissions_publicly'
    ];

    protected $casts = [
        'runner_up_pitch_ids' => 'array',
        'finalized_at' => 'datetime',
        'show_submissions_publicly' => 'boolean'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function firstPlace()
    {
        return $this->belongsTo(Pitch::class, 'first_place_pitch_id');
    }

    public function secondPlace()
    {
        return $this->belongsTo(Pitch::class, 'second_place_pitch_id');
    }

    public function thirdPlace()
    {
        return $this->belongsTo(Pitch::class, 'third_place_pitch_id');
    }

    public function runnerUps()
    {
        if (empty($this->runner_up_pitch_ids)) {
            return collect();
        }
        
        return Pitch::whereIn('id', $this->runner_up_pitch_ids)->get();
    }

    public function finalizedBy()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function hasPlacement(int $pitchId): ?string
    {
        if ($this->first_place_pitch_id === $pitchId) return '1st';
        if ($this->second_place_pitch_id === $pitchId) return '2nd';
        if ($this->third_place_pitch_id === $pitchId) return '3rd';
        if (in_array($pitchId, $this->runner_up_pitch_ids ?? [])) return 'runner-up';
        return null;
    }
}
```

#### **2.3 Pitch Model Updates (`app/Models/Pitch.php`)**
```php
// Add new rank constants
const RANK_FIRST = '1st';
const RANK_SECOND = '2nd';
const RANK_THIRD = '3rd';
const RANK_RUNNER_UP = 'runner-up';

// Update fillable array
protected $fillable = [
    // ... existing fields ...
    'judging_notes',
    'placement_finalized_at'
];

// Add helper methods
public function isPlaced(): bool
{
    return !is_null($this->rank);
}

public function getPlacementLabel(): ?string
{
    return match($this->rank) {
        self::RANK_FIRST => '1st Place',
        self::RANK_SECOND => '2nd Place', 
        self::RANK_THIRD => '3rd Place',
        self::RANK_RUNNER_UP => 'Runner-up',
        default => null
    };
}
```

---

### **Phase 3: Service Layer Updates**

#### **3.1 New ContestJudgingService (`app/Services/ContestJudgingService.php`)**
```php
<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Pitch;
use App\Models\ContestResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContestJudgingService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Set placement for a contest entry
     */
    public function setPlacement(Pitch $pitch, ?string $placement, User $judge): void
    {
        if (!$this->canJudge($pitch->project, $judge)) {
            throw new \Exception('Cannot judge this contest');
        }

        DB::transaction(function() use ($pitch, $placement, $judge) {
            $result = $this->getOrCreateContestResult($pitch->project);
            
            // Remove current placement if exists
            $this->clearCurrentPlacement($result, $pitch->id);
            
            // Set new placement
            if ($placement) {
                $this->assignPlacement($result, $pitch, $placement, $judge);
            }
            
            $result->save();
        });
    }

    /**
     * Finalize contest judging and notify participants
     */
    public function finalizeJudging(Project $project, User $judge): void
    {
        if (!$project->canFinalizeJudging()) {
            throw new \Exception('Cannot finalize judging at this time');
        }

        DB::transaction(function() use ($project, $judge) {
            $result = $this->getOrCreateContestResult($project);
            $result->finalized_at = now();
            $result->finalized_by = $judge->id;
            $result->save();

            // Update project
            $project->judging_finalized_at = now();
            $project->save();

            // Update pitch statuses
            $this->updatePitchStatuses($project);
            
            // Send notifications
            $this->notifyParticipants($project);
        });
    }

    private function updatePitchStatuses(Project $project): void
    {
        $result = $project->contestResult;
        
        // Update winners and runner-ups
        $placedPitchIds = array_filter([
            $result->first_place_pitch_id,
            $result->second_place_pitch_id, 
            $result->third_place_pitch_id,
            ...($result->runner_up_pitch_ids ?? [])
        ]);

        if ($result->first_place_pitch_id) {
            Pitch::where('id', $result->first_place_pitch_id)
                ->update([
                    'status' => Pitch::STATUS_CONTEST_WINNER,
                    'rank' => Pitch::RANK_FIRST,
                    'placement_finalized_at' => now()
                ]);
        }

        if ($result->second_place_pitch_id) {
            Pitch::where('id', $result->second_place_pitch_id)
                ->update([
                    'status' => Pitch::STATUS_CONTEST_RUNNER_UP,
                    'rank' => Pitch::RANK_SECOND,
                    'placement_finalized_at' => now()
                ]);
        }

        if ($result->third_place_pitch_id) {
            Pitch::where('id', $result->third_place_pitch_id)
                ->update([
                    'status' => Pitch::STATUS_CONTEST_RUNNER_UP,
                    'rank' => Pitch::RANK_THIRD,
                    'placement_finalized_at' => now()
                ]);
        }

        if (!empty($result->runner_up_pitch_ids)) {
            Pitch::whereIn('id', $result->runner_up_pitch_ids)
                ->update([
                    'status' => Pitch::STATUS_CONTEST_RUNNER_UP,
                    'rank' => Pitch::RANK_RUNNER_UP,
                    'placement_finalized_at' => now()
                ]);
        }

        // Mark non-placed entries as not selected
        $project->pitches()
            ->where('status', Pitch::STATUS_CONTEST_ENTRY)
            ->whereNotIn('id', $placedPitchIds)
            ->update([
                'status' => Pitch::STATUS_CONTEST_NOT_SELECTED,
                'placement_finalized_at' => now()
            ]);
    }

    // Additional helper methods...
}
```

---

### **Phase 4: New Livewire Components**

#### **4.1 Contest Judging Component (`app/Livewire/Project/Component/ContestJudging.php`)**
```php
<?php

namespace App\Livewire\Project\Component;

use Livewire\Component;
use App\Models\Project;
use App\Models\Pitch;
use App\Services\ContestJudgingService;

class ContestJudging extends Component
{
    public Project $project;
    public $entries;
    public $contestResult;
    public $canFinalize = false;

    protected $listeners = ['judgingUpdated' => '$refresh'];

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->loadData();
    }

    public function setPlacement($pitchId, $placement)
    {
        try {
            $pitch = Pitch::findOrFail($pitchId);
            
            app(ContestJudgingService::class)->setPlacement(
                $pitch, 
                $placement === 'none' ? null : $placement, 
                auth()->user()
            );
            
            $this->loadData();
            $this->dispatch('judgingUpdated');
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function finalizeJudging()
    {
        try {
            app(ContestJudgingService::class)->finalizeJudging($this->project, auth()->user());
            
            $this->loadData();
            session()->flash('success', 'Contest judging has been finalized!');
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    private function loadData()
    {
        $this->entries = $this->project->getContestEntries();
        $this->contestResult = $this->project->contestResult;
        $this->canFinalize = $this->project->canFinalizeJudging();
    }

    public function render()
    {
        return view('livewire.project.component.contest-judging');
    }
}
```

#### **4.2 Contest Results Component (`app/Livewire/Contest/ContestResults.php`)**
```php
<?php

namespace App\Livewire\Contest;

use Livewire\Component;
use App\Models\Project;

class ContestResults extends Component
{
    public Project $project;
    public $contestResult;
    public $winners = [];
    public $runnerUps = [];
    public $participants = [];

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->loadResults();
    }

    private function loadResults()
    {
        $this->contestResult = $this->project->contestResult;
        
        if ($this->contestResult) {
            // Load winners in order
            $this->winners = collect([
                '1st' => $this->contestResult->firstPlace,
                '2nd' => $this->contestResult->secondPlace,
                '3rd' => $this->contestResult->thirdPlace,
            ])->filter();

            $this->runnerUps = $this->contestResult->runnerUps();
            
            // Get all other participants
            $placedIds = array_filter([
                $this->contestResult->first_place_pitch_id,
                $this->contestResult->second_place_pitch_id,
                $this->contestResult->third_place_pitch_id,
                ...($this->contestResult->runner_up_pitch_ids ?? [])
            ]);

            $this->participants = $this->project->pitches()
                ->where('status', Pitch::STATUS_CONTEST_NOT_SELECTED)
                ->whereNotIn('id', $placedIds)
                ->with('user')
                ->get();
        }
    }

    public function render()
    {
        return view('livewire.contest.contest-results');
    }
}
```

---

### **Phase 5: View Templates**

#### **5.1 Contest Judging Interface (`resources/views/livewire/project/component/contest-judging.blade.php`)**
```blade
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-900">Contest Judging</h3>
        
        @if($canFinalize && $contestResult && ($contestResult->first_place_pitch_id || !empty($contestResult->runner_up_pitch_ids)))
            <button wire:click="finalizeJudging" 
                    wire:confirm="Are you sure you want to finalize the judging? This cannot be undone."
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                Finalize Judging
            </button>
        @endif
    </div>

    @if($project->isJudgingFinalized())
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <p class="text-blue-800 font-medium">
                <i class="fas fa-check-circle mr-2"></i>
                Contest judging has been finalized on {{ $project->judging_finalized_at->format('M j, Y \a\t g:i A') }}
            </p>
        </div>
    @endif

    <div class="space-y-4">
        @foreach($entries as $entry)
            <div class="border rounded-lg p-4 {{ $entry->isPlaced() ? 'bg-yellow-50 border-yellow-200' : 'bg-gray-50' }}">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">{{ $entry->user->name }}</h4>
                        <p class="text-sm text-gray-600">
                            Submitted {{ $entry->submitted_at ? $entry->submitted_at->format('M j, Y') : 'Draft' }}
                        </p>
                        @if($entry->currentSnapshot)
                            <a href="{{ route('projects.pitches.snapshots.show', ['project' => $project, 'pitch' => $entry, 'snapshot' => $entry->currentSnapshot->id]) }}"
                               class="text-blue-600 hover:text-blue-800 text-sm">
                                <i class="fas fa-eye mr-1"></i>View Submission
                            </a>
                        @endif
                    </div>
                    
                    <div class="ml-4">
                        @if(!$project->isJudgingFinalized())
                            <select wire:change="setPlacement({{ $entry->id }}, $event.target.value)"
                                    class="form-select rounded-lg border-gray-300">
                                <option value="none" {{ !$entry->rank ? 'selected' : '' }}>No Placement</option>
                                
                                <option value="1st" 
                                        {{ $entry->rank === '1st' ? 'selected' : '' }}
                                        {{ $contestResult && $contestResult->first_place_pitch_id && $contestResult->first_place_pitch_id !== $entry->id ? 'disabled' : '' }}>
                                    1st Place {{ $contestResult && $contestResult->first_place_pitch_id && $contestResult->first_place_pitch_id !== $entry->id ? '(Already Selected)' : '' }}
                                </option>
                                
                                <option value="2nd" 
                                        {{ $entry->rank === '2nd' ? 'selected' : '' }}
                                        {{ $contestResult && $contestResult->second_place_pitch_id && $contestResult->second_place_pitch_id !== $entry->id ? 'disabled' : '' }}>
                                    2nd Place {{ $contestResult && $contestResult->second_place_pitch_id && $contestResult->second_place_pitch_id !== $entry->id ? '(Already Selected)' : '' }}
                                </option>
                                
                                <option value="3rd" 
                                        {{ $entry->rank === '3rd' ? 'selected' : '' }}
                                        {{ $contestResult && $contestResult->third_place_pitch_id && $contestResult->third_place_pitch_id !== $entry->id ? 'disabled' : '' }}>
                                    3rd Place {{ $contestResult && $contestResult->third_place_pitch_id && $contestResult->third_place_pitch_id !== $entry->id ? '(Already Selected)' : '' }}
                                </option>
                                
                                <option value="runner-up" {{ $entry->rank === 'runner-up' ? 'selected' : '' }}>
                                    Runner-up
                                </option>
                            </select>
                        @else
                            @if($entry->rank)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    {{ $entry->rank === '1st' ? 'bg-yellow-100 text-yellow-800' : 
                                       ($entry->rank === '2nd' ? 'bg-gray-100 text-gray-800' : 
                                       ($entry->rank === '3rd' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800')) }}">
                                    {{ $entry->getPlacementLabel() }}
                                </span>
                            @else
                                <span class="text-gray-500 text-sm">Not Placed</span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
```

#### **5.2 Public Contest Results (`resources/views/livewire/contest/contest-results.blade.php`)**
```blade
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="bg-gradient-to-r from-yellow-400 to-orange-500 p-6">
        <h2 class="text-2xl font-bold text-white">
            <i class="fas fa-trophy mr-2"></i>Contest Results
        </h2>
        <p class="text-yellow-100">{{ $project->name }}</p>
    </div>

    <div class="p-6 space-y-8">
        <!-- Winners Section -->
        @if($winners->isNotEmpty())
            <div>
                <h3 class="text-lg font-bold text-gray-900 mb-4">🏆 Winners</h3>
                <div class="space-y-4">
                    @foreach($winners as $place => $winner)
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r 
                            {{ $place === '1st' ? 'from-yellow-50 to-yellow-100 border border-yellow-200' : 
                               ($place === '2nd' ? 'from-gray-50 to-gray-100 border border-gray-200' : 
                                'from-amber-50 to-amber-100 border border-amber-200') }} 
                            rounded-lg">
                            <div class="flex items-center">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-r 
                                    {{ $place === '1st' ? 'from-yellow-400 to-yellow-600' : 
                                       ($place === '2nd' ? 'from-gray-400 to-gray-600' : 
                                        'from-amber-400 to-amber-600') }} 
                                    flex items-center justify-center text-white font-bold mr-4">
                                    {{ $place }}
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $winner->user->name }}</h4>
                                    <p class="text-sm text-gray-600">{{ $place }} Place</p>
                                </div>
                            </div>
                            
                            @if($contestResult->show_submissions_publicly && $winner->currentSnapshot)
                                <a href="{{ route('projects.pitches.snapshots.show', ['project' => $project, 'pitch' => $winner, 'snapshot' => $winner->currentSnapshot->id]) }}"
                                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                                    View Submission
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Runner-ups Section -->
        @if($runnerUps->isNotEmpty())
            <div>
                <h3 class="text-lg font-bold text-gray-900 mb-4">🥈 Runner-ups</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($runnerUps as $runnerUp)
                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $runnerUp->user->name }}</h4>
                                    <p class="text-sm text-blue-600">Runner-up</p>
                                </div>
                                
                                @if($contestResult->show_submissions_publicly && $runnerUp->currentSnapshot)
                                    <a href="{{ route('projects.pitches.snapshots.show', ['project' => $project, 'pitch' => $runnerUp, 'snapshot' => $runnerUp->currentSnapshot->id]) }}"
                                       class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                        View
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Participants Section -->
        @if($participants->isNotEmpty())
            <div>
                <h3 class="text-lg font-bold text-gray-900 mb-4">👥 Other Participants</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach($participants as $participant)
                        <div class="p-3 bg-gray-50 rounded-lg text-center">
                            <p class="text-sm font-medium text-gray-900">{{ $participant->user->name }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
```

---

### **Phase 6: Integration Updates**

#### **6.1 Update Project Management Page**
Replace the current `ContestEntries` component with the new `ContestJudging` component:

```blade
<!-- In resources/views/projects/manage.blade.php -->
@if($project->isContest())
    @if($project->canFinalizeJudging() || $project->isJudgingFinalized())
        <livewire:project.component.contest-judging :project="$project" />
    @else
        <livewire:project.component.contest-entries :project="$project" />
    @endif
@endif
```

#### **6.2 Update Snapshot Show Page**
Add judging interface to individual snapshots:

```blade
<!-- In resources/views/livewire/pitch/snapshot/show-snapshot.blade.php -->
@if($project->isContest() && auth()->id() === $project->user_id && !$project->isJudgingFinalized())
    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <h4 class="font-medium text-yellow-800 mb-3">Judge This Entry</h4>
        <livewire:contest.snapshot-judging :pitch="$pitch" />
    </div>
@endif
```

#### **6.3 Update Public Project Page**
Add contest results display:

```blade
<!-- In resources/views/projects/show.blade.php -->
@if($project->isContest() && $project->isJudgingFinalized())
    <div class="mt-8">
        <livewire:contest.contest-results :project="$project" />
    </div>
@endif
```

---

### **Phase 7: Policy Updates**

#### **7.1 Update PitchPolicy (`app/Policies/PitchPolicy.php`)**
```php
// Replace existing selectWinner and selectRunnerUp methods
public function judgeContest(User $user, Project $project): bool
{
    return $user->id === $project->user_id && 
           $project->isContest() && 
           !$project->isJudgingFinalized();
}

public function finalizeJudging(User $user, Project $project): bool
{
    return $user->id === $project->user_id && 
           $project->canFinalizeJudging();
}
```

---

### **Phase 8: Notification Enhancements**

#### **8.1 Update NotificationService (`app/Services/NotificationService.php`)**
```php
public function notifyContestResultsFinalized(Project $project): void
{
    $result = $project->contestResult;
    
    // Notify winners with special messages
    if ($result->first_place_pitch_id) {
        $this->notifyContestWinner($result->firstPlace, '1st');
    }
    if ($result->second_place_pitch_id) {
        $this->notifyContestWinner($result->secondPlace, '2nd'); 
    }
    if ($result->third_place_pitch_id) {
        $this->notifyContestWinner($result->thirdPlace, '3rd');
    }
    
    // Notify runner-ups
    foreach ($result->runnerUps() as $runnerUp) {
        $this->notifyContestRunnerUp($runnerUp);
    }
    
    // Notify all other participants
    $this->notifyContestParticipants($project);
}

private function notifyContestWinner(Pitch $pitch, string $place): void
{
    // Implementation for winner notifications
}

private function notifyContestRunnerUp(Pitch $pitch): void
{
    // Implementation for runner-up notifications  
}

private function notifyContestParticipants(Project $project): void
{
    // Implementation for general participant notifications
}
```

---

## Testing Strategy

### **Unit Tests:**
- `ContestJudgingServiceTest`: Test placement logic and finalization
- `ContestResultModelTest`: Test relationships and helper methods
- `PitchPolicyTest`: Update existing tests for new judging policies

### **Feature Tests:**
- `ContestJudgingWorkflowTest`: End-to-end judging and finalization
- `ContestResultsDisplayTest`: Public results page functionality
- `ContestNotificationTest`: Notification delivery verification

### **Integration Tests:**
- Test judging interface across project management and snapshot pages
- Verify dynamic dropdown behavior
- Test finalization restrictions and notifications

---

## Migration Timeline

### **Week 1: Database & Models**
- Create migrations for new tables and columns
- Implement ContestResult model and relationships
- Update existing models with new methods

### **Week 2: Service Layer**
- Implement ContestJudgingService
- Update notification system
- Create policy updates

### **Week 3: Components & Views**
- Build ContestJudging Livewire component
- Create ContestResults display component
- Build judging interface templates

### **Week 4: Integration & Testing**
- Integrate components into existing pages
- Write comprehensive tests
- Handle edge cases and error scenarios

### **Week 5: Polish & Deploy**
- UI/UX refinements
- Performance optimizations
- Documentation updates
- Production deployment

---

## Notes & Considerations

1. **Backward Compatibility**: Existing contest entries will need data migration to work with new system
2. **Performance**: Consider caching contest results for public display
3. **Admin Override**: Future feature for re-opening finalized judging (not in initial scope)
4. **Scalability**: Current design supports unlimited runner-ups efficiently
5. **Security**: All judging actions require proper authorization checks

This plan provides a comprehensive overhaul of the contest system while maintaining the existing entry submission workflow and adding the robust judging functionality you requested. 
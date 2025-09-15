# Simple Time Tracking Overlay Implementation Plan

## Feature Overview

The Simple Time Tracking Overlay enables producers and audio engineers to track work sessions directly within MixPitch projects. This feature provides essential time logging capabilities for freelancers who need to bill clients accurately and studio professionals who require project cost analysis.

### Core Functionality
- **Session Timer**: Start/stop timer for active work sessions
- **Project-Based Tracking**: Associate time with specific projects and tasks
- **Manual Time Entry**: Edit and adjust tracked time entries
- **Export Capabilities**: Generate CSV reports for billing and analysis
- **Idle Detection**: Automatic pause during periods of inactivity
- **Cross-Device Sync**: Continue sessions across multiple devices

## Technical Architecture

### Database Schema

```sql
-- Time tracking sessions and entries
CREATE TABLE time_tracking_sessions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    pitch_id BIGINT UNSIGNED NULL,
    session_name VARCHAR(255) NULL,
    task_description TEXT NULL,
    started_at TIMESTAMP NOT NULL,
    ended_at TIMESTAMP NULL,
    duration_seconds INT UNSIGNED NULL,
    is_active BOOLEAN DEFAULT FALSE,
    is_billable BOOLEAN DEFAULT TRUE,
    hourly_rate DECIMAL(8,2) NULL,
    notes TEXT NULL,
    auto_paused_at TIMESTAMP NULL,
    auto_pause_duration INT UNSIGNED DEFAULT 0,
    device_info JSON NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (pitch_id) REFERENCES pitches(id) ON DELETE SET NULL,
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_project_date (project_id, started_at),
    INDEX idx_billable (is_billable, ended_at)
);

-- Time tracking categories and tasks
CREATE TABLE time_tracking_categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    default_hourly_rate DECIMAL(8,2) NULL,
    color_hex VARCHAR(7) DEFAULT '#6366f1',
    is_billable_default BOOLEAN DEFAULT TRUE,
    sort_order INT UNSIGNED DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_sort (user_id, sort_order)
);

-- Link sessions to categories
CREATE TABLE time_tracking_session_categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (session_id) REFERENCES time_tracking_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES time_tracking_categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session_category (session_id, category_id)
);

-- Time tracking exports and reports
CREATE TABLE time_tracking_exports (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    export_type ENUM('csv', 'pdf', 'json') NOT NULL DEFAULT 'csv',
    filters JSON NOT NULL,
    file_path VARCHAR(500) NULL,
    download_url VARCHAR(500) NULL,
    total_sessions INT UNSIGNED DEFAULT 0,
    total_hours DECIMAL(10,2) DEFAULT 0,
    total_billable_hours DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_expires (expires_at)
);

-- User time tracking preferences
CREATE TABLE time_tracking_preferences (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    default_hourly_rate DECIMAL(8,2) NULL,
    auto_pause_after_minutes INT UNSIGNED DEFAULT 15,
    reminder_intervals JSON DEFAULT '[]',
    export_format ENUM('csv', 'pdf', 'json') DEFAULT 'csv',
    timezone VARCHAR(100) DEFAULT 'UTC',
    week_start_day ENUM('monday', 'sunday') DEFAULT 'monday',
    preferences JSON DEFAULT '{}',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preferences (user_id)
);
```

### Service Architecture

#### TimeTrackingService
```php
<?php

namespace App\Services;

use App\Models\TimeTrackingSession;
use App\Models\TimeTrackingCategory;
use App\Models\TimeTrackingExport;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class TimeTrackingService
{
    public function startSession(
        User $user,
        Project $project,
        ?Pitch $pitch = null,
        ?string $taskDescription = null,
        ?array $categories = null
    ): TimeTrackingSession {
        // Stop any active sessions for this user
        $this->stopActiveSession($user);

        $session = TimeTrackingSession::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'pitch_id' => $pitch?->id,
            'task_description' => $taskDescription,
            'started_at' => now(),
            'is_active' => true,
            'is_billable' => $user->timeTrackingPreferences?->is_billable_default ?? true,
            'hourly_rate' => $user->timeTrackingPreferences?->default_hourly_rate,
            'device_info' => [
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
                'platform' => $this->detectPlatform()
            ]
        ]);

        // Associate with categories if provided
        if ($categories) {
            $this->attachCategories($session, $categories);
        }

        return $session;
    }

    public function stopSession(User $user, ?string $notes = null): ?TimeTrackingSession
    {
        $session = $this->getActiveSession($user);
        
        if (!$session) {
            return null;
        }

        $endTime = now();
        $duration = $session->started_at->diffInSeconds($endTime) - $session->auto_pause_duration;

        $session->update([
            'ended_at' => $endTime,
            'duration_seconds' => max(0, $duration),
            'is_active' => false,
            'notes' => $notes
        ]);

        return $session;
    }

    public function pauseSession(User $user): ?TimeTrackingSession
    {
        $session = $this->getActiveSession($user);
        
        if (!$session || $session->auto_paused_at) {
            return null;
        }

        $session->update([
            'auto_paused_at' => now()
        ]);

        return $session;
    }

    public function resumeSession(User $user): ?TimeTrackingSession
    {
        $session = $this->getActiveSession($user);
        
        if (!$session || !$session->auto_paused_at) {
            return null;
        }

        $pauseDuration = $session->auto_paused_at->diffInSeconds(now());
        
        $session->update([
            'auto_pause_duration' => $session->auto_pause_duration + $pauseDuration,
            'auto_paused_at' => null
        ]);

        return $session;
    }

    public function updateSession(
        TimeTrackingSession $session,
        array $updates
    ): TimeTrackingSession {
        // Validate updates
        $allowedFields = [
            'task_description', 'notes', 'is_billable', 'hourly_rate',
            'started_at', 'ended_at', 'duration_seconds'
        ];

        $filteredUpdates = array_intersect_key($updates, array_flip($allowedFields));

        // Recalculate duration if start/end times are updated
        if (isset($filteredUpdates['started_at']) || isset($filteredUpdates['ended_at'])) {
            $startTime = Carbon::parse($filteredUpdates['started_at'] ?? $session->started_at);
            $endTime = Carbon::parse($filteredUpdates['ended_at'] ?? $session->ended_at);
            
            if ($endTime && $startTime) {
                $filteredUpdates['duration_seconds'] = $startTime->diffInSeconds($endTime) - $session->auto_pause_duration;
            }
        }

        $session->update($filteredUpdates);
        
        return $session;
    }

    public function deleteSession(TimeTrackingSession $session): bool
    {
        return $session->delete();
    }

    public function getActiveSession(User $user): ?TimeTrackingSession
    {
        return TimeTrackingSession::where('user_id', $user->id)
            ->where('is_active', true)
            ->with(['project', 'pitch', 'categories'])
            ->first();
    }

    public function getUserSessions(
        User $user,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?int $projectId = null,
        ?bool $billableOnly = null
    ) {
        $query = TimeTrackingSession::where('user_id', $user->id)
            ->with(['project', 'pitch', 'categories']);

        if ($startDate) {
            $query->where('started_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('started_at', '<=', $endDate);
        }

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        if ($billableOnly !== null) {
            $query->where('is_billable', $billableOnly);
        }

        return $query->orderBy('started_at', 'desc');
    }

    public function generateTimeReport(
        User $user,
        array $filters = [],
        string $format = 'csv'
    ): TimeTrackingExport {
        $export = TimeTrackingExport::create([
            'user_id' => $user->id,
            'export_type' => $format,
            'filters' => $filters,
            'status' => 'pending',
            'expires_at' => now()->addDays(7)
        ]);

        // Queue the export generation
        ProcessTimeTrackingExport::dispatch($export);

        return $export;
    }

    public function processTimeReportExport(TimeTrackingExport $export): void
    {
        try {
            $export->update(['status' => 'processing']);

            $sessions = $this->getUserSessions(
                $export->user,
                isset($export->filters['start_date']) ? Carbon::parse($export->filters['start_date']) : null,
                isset($export->filters['end_date']) ? Carbon::parse($export->filters['end_date']) : null,
                $export->filters['project_id'] ?? null,
                $export->filters['billable_only'] ?? null
            )->get();

            $filePath = match ($export->export_type) {
                'csv' => $this->generateCSVReport($export, $sessions),
                'pdf' => $this->generatePDFReport($export, $sessions),
                'json' => $this->generateJSONReport($export, $sessions),
                default => throw new \InvalidArgumentException('Unsupported export format')
            };

            $totalHours = $sessions->sum(function ($session) {
                return $session->duration_seconds / 3600;
            });

            $billableHours = $sessions->where('is_billable', true)->sum(function ($session) {
                return $session->duration_seconds / 3600;
            });

            $totalAmount = $sessions->sum(function ($session) {
                if (!$session->is_billable || !$session->hourly_rate) {
                    return 0;
                }
                return ($session->duration_seconds / 3600) * $session->hourly_rate;
            });

            $export->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'download_url' => Storage::disk('s3')->temporaryUrl($filePath, now()->addDays(7)),
                'total_sessions' => $sessions->count(),
                'total_hours' => round($totalHours, 2),
                'total_billable_hours' => round($billableHours, 2),
                'total_amount' => round($totalAmount, 2)
            ]);

        } catch (\Exception $e) {
            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    private function generateCSVReport(TimeTrackingExport $export, $sessions): string
    {
        $csvData = [];
        $csvData[] = [
            'Date',
            'Project',
            'Task Description',
            'Start Time',
            'End Time',
            'Duration (Hours)',
            'Billable',
            'Hourly Rate',
            'Amount',
            'Categories',
            'Notes'
        ];

        foreach ($sessions as $session) {
            $duration = $session->duration_seconds ? round($session->duration_seconds / 3600, 2) : 0;
            $amount = $session->is_billable && $session->hourly_rate ? $duration * $session->hourly_rate : 0;

            $csvData[] = [
                $session->started_at->format('Y-m-d'),
                $session->project->name,
                $session->task_description ?? '',
                $session->started_at->format('H:i'),
                $session->ended_at?->format('H:i') ?? 'In Progress',
                $duration,
                $session->is_billable ? 'Yes' : 'No',
                $session->hourly_rate ? '$' . number_format($session->hourly_rate, 2) : '',
                $amount ? '$' . number_format($amount, 2) : '',
                $session->categories->pluck('name')->join(', '),
                $session->notes ?? ''
            ];
        }

        $filename = sprintf(
            'time_tracking_%s_%s.csv',
            $export->user_id,
            now()->format('Y-m-d_H-i-s')
        );
        
        $path = "time-tracking-exports/{$filename}";
        
        $csvContent = '';
        foreach ($csvData as $row) {
            $csvContent .= '"' . implode('","', $row) . '"' . "\n";
        }
        
        Storage::disk('s3')->put($path, $csvContent);
        
        return $path;
    }

    private function stopActiveSession(User $user): void
    {
        TimeTrackingSession::where('user_id', $user->id)
            ->where('is_active', true)
            ->update([
                'ended_at' => now(),
                'is_active' => false,
                'duration_seconds' => \DB::raw('TIMESTAMPDIFF(SECOND, started_at, NOW()) - auto_pause_duration')
            ]);
    }

    private function attachCategories(TimeTrackingSession $session, array $categoryIds): void
    {
        $validCategories = TimeTrackingCategory::where('user_id', $session->user_id)
            ->whereIn('id', $categoryIds)
            ->where('is_active', true)
            ->pluck('id');

        $session->categories()->sync($validCategories);
    }

    private function detectPlatform(): string
    {
        $userAgent = request()->userAgent();
        
        if (str_contains($userAgent, 'Mobile')) {
            return 'mobile';
        } elseif (str_contains($userAgent, 'Tablet')) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
}
```

#### Background Job for Export Processing
```php
<?php

namespace App\Jobs;

use App\Models\TimeTrackingExport;
use App\Services\TimeTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTimeTrackingExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private TimeTrackingExport $export
    ) {}

    public function handle(TimeTrackingService $timeTrackingService): void
    {
        $timeTrackingService->processTimeReportExport($this->export);
    }
}
```

## UI Implementation

### Time Tracking Overlay Component
```php
<?php

namespace App\Livewire\TimeTracking;

use App\Models\TimeTrackingSession;
use App\Models\TimeTrackingCategory;
use App\Models\Project;
use App\Services\TimeTrackingService;
use Livewire\Component;
use Carbon\Carbon;

class TimeTrackingOverlay extends Component
{
    public ?TimeTrackingSession $activeSession = null;
    public ?Project $selectedProject = null;
    public string $taskDescription = '';
    public array $selectedCategories = [];
    public string $currentTime = '';
    public bool $isMinimized = false;
    public bool $showSessionForm = false;
    public string $sessionNotes = '';

    protected $rules = [
        'selectedProject' => 'required',
        'taskDescription' => 'nullable|string|max:500',
        'selectedCategories' => 'array',
        'selectedCategories.*' => 'exists:time_tracking_categories,id'
    ];

    public function mount()
    {
        $this->loadActiveSession();
        $this->updateCurrentTime();
    }

    public function startSession(TimeTrackingService $timeTrackingService)
    {
        $this->validate();

        if ($this->activeSession) {
            $this->addError('timer', 'Stop current session before starting a new one.');
            return;
        }

        $this->activeSession = $timeTrackingService->startSession(
            auth()->user(),
            $this->selectedProject,
            null,
            $this->taskDescription,
            $this->selectedCategories
        );

        $this->showSessionForm = false;
        $this->reset(['taskDescription', 'selectedCategories']);

        $this->dispatch('session-started', [
            'message' => 'Time tracking started for ' . $this->selectedProject->name
        ]);
    }

    public function stopSession(TimeTrackingService $timeTrackingService)
    {
        if (!$this->activeSession) {
            return;
        }

        $session = $timeTrackingService->stopSession(auth()->user(), $this->sessionNotes);
        
        if ($session) {
            $duration = gmdate('H:i:s', $session->duration_seconds);
            
            $this->dispatch('session-stopped', [
                'message' => "Session stopped. Total time: {$duration}",
                'session' => $session
            ]);
        }

        $this->reset(['activeSession', 'sessionNotes']);
    }

    public function pauseSession(TimeTrackingService $timeTrackingService)
    {
        if (!$this->activeSession) {
            return;
        }

        $timeTrackingService->pauseSession(auth()->user());
        $this->loadActiveSession();

        $this->dispatch('session-paused');
    }

    public function resumeSession(TimeTrackingService $timeTrackingService)
    {
        if (!$this->activeSession) {
            return;
        }

        $timeTrackingService->resumeSession(auth()->user());
        $this->loadActiveSession();

        $this->dispatch('session-resumed');
    }

    public function toggleMinimized()
    {
        $this->isMinimized = !$this->isMinimized;
    }

    public function updateCurrentTime()
    {
        $this->currentTime = now()->format('H:i:s');
    }

    private function loadActiveSession()
    {
        $timeTrackingService = app(TimeTrackingService::class);
        $this->activeSession = $timeTrackingService->getActiveSession(auth()->user());
        
        if ($this->activeSession) {
            $this->selectedProject = $this->activeSession->project;
        }
    }

    public function render()
    {
        $userProjects = auth()->user()->projects()->latest()->limit(10)->get();
        $categories = TimeTrackingCategory::where('user_id', auth()->id())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('livewire.time-tracking.overlay', [
            'userProjects' => $userProjects,
            'categories' => $categories
        ]);
    }
}
```

### Time Tracking Manager Component
```php
<?php

namespace App\Livewire\TimeTracking;

use App\Models\TimeTrackingSession;
use App\Models\TimeTrackingExport;
use App\Services\TimeTrackingService;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class TimeTrackingManager extends Component
{
    use WithPagination;

    public array $filters = [
        'start_date' => '',
        'end_date' => '',
        'project_id' => '',
        'billable_only' => ''
    ];
    
    public bool $showFilters = false;
    public bool $showEditModal = false;
    public ?TimeTrackingSession $editingSession = null;
    public array $sessionForm = [];

    protected $rules = [
        'sessionForm.task_description' => 'nullable|string|max:500',
        'sessionForm.started_at' => 'required|date',
        'sessionForm.ended_at' => 'nullable|date|after:sessionForm.started_at',
        'sessionForm.is_billable' => 'boolean',
        'sessionForm.hourly_rate' => 'nullable|numeric|min:0|max:999.99',
        'sessionForm.notes' => 'nullable|string|max:1000'
    ];

    public function mount()
    {
        $this->filters['start_date'] = now()->subDays(30)->format('Y-m-d');
        $this->filters['end_date'] = now()->format('Y-m-d');
    }

    public function exportSessions(TimeTrackingService $timeTrackingService)
    {
        $export = $timeTrackingService->generateTimeReport(
            auth()->user(),
            array_filter($this->filters),
            'csv'
        );

        $this->dispatch('export-started', [
            'message' => 'Export generation started. You\'ll receive a notification when ready.',
            'exportId' => $export->id
        ]);
    }

    public function editSession(int $sessionId)
    {
        $this->editingSession = TimeTrackingSession::where('user_id', auth()->id())
            ->findOrFail($sessionId);

        $this->sessionForm = [
            'task_description' => $this->editingSession->task_description,
            'started_at' => $this->editingSession->started_at->format('Y-m-d\TH:i'),
            'ended_at' => $this->editingSession->ended_at?->format('Y-m-d\TH:i'),
            'is_billable' => $this->editingSession->is_billable,
            'hourly_rate' => $this->editingSession->hourly_rate,
            'notes' => $this->editingSession->notes
        ];

        $this->showEditModal = true;
    }

    public function updateSession(TimeTrackingService $timeTrackingService)
    {
        $this->validate();

        $updates = [
            'task_description' => $this->sessionForm['task_description'],
            'started_at' => $this->sessionForm['started_at'],
            'ended_at' => $this->sessionForm['ended_at'],
            'is_billable' => $this->sessionForm['is_billable'],
            'hourly_rate' => $this->sessionForm['hourly_rate'],
            'notes' => $this->sessionForm['notes']
        ];

        $timeTrackingService->updateSession($this->editingSession, $updates);

        $this->reset(['showEditModal', 'editingSession', 'sessionForm']);
        $this->resetPage();

        $this->dispatch('session-updated', [
            'message' => 'Time tracking session updated successfully'
        ]);
    }

    public function deleteSession(int $sessionId, TimeTrackingService $timeTrackingService)
    {
        $session = TimeTrackingSession::where('user_id', auth()->id())
            ->findOrFail($sessionId);

        $timeTrackingService->deleteSession($session);
        $this->resetPage();

        $this->dispatch('session-deleted', [
            'message' => 'Time tracking session deleted'
        ]);
    }

    public function render(TimeTrackingService $timeTrackingService)
    {
        $startDate = $this->filters['start_date'] ? Carbon::parse($this->filters['start_date']) : null;
        $endDate = $this->filters['end_date'] ? Carbon::parse($this->filters['end_date']) : null;

        $sessions = $timeTrackingService->getUserSessions(
            auth()->user(),
            $startDate,
            $endDate,
            $this->filters['project_id'] ?: null,
            $this->filters['billable_only'] !== '' ? (bool) $this->filters['billable_only'] : null
        )->paginate(20);

        $userProjects = auth()->user()->projects()->latest()->get();
        
        $recentExports = TimeTrackingExport::where('user_id', auth()->id())
            ->latest()
            ->limit(5)
            ->get();

        // Calculate summary statistics
        $allSessions = $timeTrackingService->getUserSessions(
            auth()->user(),
            $startDate,
            $endDate,
            $this->filters['project_id'] ?: null,
            $this->filters['billable_only'] !== '' ? (bool) $this->filters['billable_only'] : null
        )->get();

        $summary = [
            'total_hours' => round($allSessions->sum(fn($s) => $s->duration_seconds / 3600), 2),
            'billable_hours' => round($allSessions->where('is_billable', true)->sum(fn($s) => $s->duration_seconds / 3600), 2),
            'total_amount' => $allSessions->sum(function ($session) {
                return $session->is_billable && $session->hourly_rate 
                    ? ($session->duration_seconds / 3600) * $session->hourly_rate 
                    : 0;
            }),
            'session_count' => $allSessions->count()
        ];

        return view('livewire.time-tracking.manager', [
            'sessions' => $sessions,
            'userProjects' => $userProjects,
            'recentExports' => $recentExports,
            'summary' => $summary
        ]);
    }
}
```

### Blade Templates

#### Time Tracking Overlay Template
```blade
{{-- Floating overlay positioned at bottom-right of screen --}}
<div 
    class="fixed bottom-4 right-4 z-50 max-w-sm"
    x-data="timeTrackingOverlay()"
    x-init="startTimer()"
    wire:poll.1s="updateCurrentTime"
>
    {{-- Minimized State --}}
    @if($isMinimized && $activeSession)
        <flux:card class="p-3 shadow-lg border border-indigo-200 bg-white dark:bg-slate-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <flux:icon 
                        icon="clock" 
                        class="w-4 h-4 text-indigo-600 {{ $activeSession->auto_paused_at ? 'animate-pulse' : 'animate-spin' }}"
                    />
                    <span class="text-sm font-medium" x-text="formatDuration(sessionDuration)">
                        00:00:00
                    </span>
                </div>
                
                <flux:button 
                    wire:click="toggleMinimized" 
                    variant="ghost" 
                    size="xs"
                >
                    <flux:icon icon="chevron-up" class="w-4 h-4" />
                </flux:button>
            </div>
        </flux:card>
    @endif

    {{-- Expanded State --}}
    @if(!$isMinimized)
        <flux:card class="shadow-xl border border-indigo-200 bg-white dark:bg-slate-800">
            <flux:card.header class="pb-2">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">Time Tracking</flux:heading>
                    
                    <div class="flex items-center space-x-1">
                        @if($activeSession)
                            <flux:button 
                                wire:click="toggleMinimized" 
                                variant="ghost" 
                                size="xs"
                            >
                                <flux:icon icon="minus" class="w-4 h-4" />
                            </flux:button>
                        @endif
                        
                        <flux:button 
                            x-on:click="show = false" 
                            variant="ghost" 
                            size="xs"
                        >
                            <flux:icon icon="x-mark" class="w-4 h-4" />
                        </flux:button>
                    </div>
                </div>
            </flux:card.header>

            <flux:card.body class="space-y-4">
                @if($activeSession)
                    {{-- Active Session Display --}}
                    <div class="text-center">
                        <div class="text-2xl font-mono font-bold text-indigo-600" x-text="formatDuration(sessionDuration)">
                            00:00:00
                        </div>
                        
                        @if($activeSession->auto_paused_at)
                            <flux:badge variant="warning" size="sm" class="mt-1">
                                Paused
                            </flux:badge>
                        @else
                            <flux:badge variant="success" size="sm" class="mt-1">
                                Active
                            </flux:badge>
                        @endif
                    </div>
                    
                    <div class="text-sm">
                        <div class="font-medium">{{ $activeSession->project->name }}</div>
                        @if($activeSession->task_description)
                            <div class="text-gray-600 dark:text-gray-400">
                                {{ $activeSession->task_description }}
                            </div>
                        @endif
                        <div class="text-xs text-gray-500 mt-1">
                            Started: {{ $activeSession->started_at->format('g:i A') }}
                        </div>
                    </div>

                    {{-- Session Controls --}}
                    <div class="flex space-x-2">
                        @if($activeSession->auto_paused_at)
                            <flux:button 
                                wire:click="resumeSession" 
                                variant="primary" 
                                size="sm" 
                                class="flex-1"
                            >
                                <flux:icon icon="play" class="w-4 h-4" />
                                Resume
                            </flux:button>
                        @else
                            <flux:button 
                                wire:click="pauseSession" 
                                variant="outline" 
                                size="sm" 
                                class="flex-1"
                            >
                                <flux:icon icon="pause" class="w-4 h-4" />
                                Pause
                            </flux:button>
                        @endif
                        
                        <flux:button 
                            wire:click="stopSession" 
                            variant="danger" 
                            size="sm" 
                            class="flex-1"
                        >
                            <flux:icon icon="stop" class="w-4 h-4" />
                            Stop
                        </flux:button>
                    </div>

                    {{-- Session Notes --}}
                    <flux:field>
                        <flux:label>Session Notes</flux:label>
                        <flux:textarea 
                            wire:model="sessionNotes" 
                            placeholder="Add notes about this session..."
                            rows="2"
                        />
                    </flux:field>

                @else
                    {{-- Start Session Form --}}
                    @if($showSessionForm)
                        <div class="space-y-3">
                            <flux:field>
                                <flux:label>Project</flux:label>
                                <flux:select wire:model="selectedProject">
                                    <option value="">Select a project</option>
                                    @foreach($userProjects as $project)
                                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="selectedProject" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Task Description (Optional)</flux:label>
                                <flux:input 
                                    wire:model="taskDescription" 
                                    placeholder="What are you working on?"
                                />
                            </flux:field>

                            @if($categories->isNotEmpty())
                                <flux:field>
                                    <flux:label>Categories</flux:label>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($categories as $category)
                                            <label class="flex items-center">
                                                <input 
                                                    type="checkbox" 
                                                    wire:model="selectedCategories" 
                                                    value="{{ $category->id }}"
                                                    class="mr-1"
                                                >
                                                <flux:badge 
                                                    style="background-color: {{ $category->color_hex }}"
                                                    size="sm"
                                                >
                                                    {{ $category->name }}
                                                </flux:badge>
                                            </label>
                                        @endforeach
                                    </div>
                                </flux:field>
                            @endif

                            <div class="flex space-x-2">
                                <flux:button 
                                    wire:click="$set('showSessionForm', false)" 
                                    variant="outline" 
                                    size="sm" 
                                    class="flex-1"
                                >
                                    Cancel
                                </flux:button>
                                <flux:button 
                                    wire:click="startSession" 
                                    variant="primary" 
                                    size="sm" 
                                    class="flex-1"
                                >
                                    <flux:icon icon="play" class="w-4 h-4" />
                                    Start
                                </flux:button>
                            </div>
                        </div>
                    @else
                        {{-- Start Button --}}
                        <div class="text-center">
                            <flux:button 
                                wire:click="$set('showSessionForm', true)" 
                                variant="primary"
                                class="w-full"
                            >
                                <flux:icon icon="play" class="w-4 h-4" />
                                Start Time Tracking
                            </flux:button>
                        </div>
                    @endif
                @endif
            </flux:card.body>
        </flux:card>
    @endif
</div>

@script
<script>
    Alpine.data('timeTrackingOverlay', () => ({
        sessionDuration: 0,
        timerInterval: null,

        startTimer() {
            this.updateDuration();
            this.timerInterval = setInterval(() => {
                this.updateDuration();
            }, 1000);
        },

        updateDuration() {
            @if($activeSession)
                const startTime = new Date('{{ $activeSession->started_at->toISOString() }}');
                const now = new Date();
                const pauseDuration = {{ $activeSession->auto_pause_duration }};
                
                @if($activeSession->auto_paused_at)
                    const pauseTime = new Date('{{ $activeSession->auto_paused_at->toISOString() }}');
                    this.sessionDuration = Math.floor((pauseTime - startTime) / 1000) - pauseDuration;
                @else
                    this.sessionDuration = Math.floor((now - startTime) / 1000) - pauseDuration;
                @endif
            @else
                this.sessionDuration = 0;
            @endif
        },

        formatDuration(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
    }));

    $wire.on('session-started', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { type: 'success', message: data.message }
        }));
    });

    $wire.on('session-stopped', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { type: 'success', message: data.message }
        }));
    });
</script>
@endscript
```

#### Time Tracking Manager Template
```blade
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">Time Tracking</flux:heading>
            <flux:text variant="muted">
                Manage your work sessions and generate billing reports
            </flux:text>
        </div>
        
        <div class="flex items-center space-x-3">
            <flux:button 
                wire:click="$toggle('showFilters')" 
                variant="outline" 
                size="sm"
            >
                <flux:icon icon="adjustments-horizontal" class="w-4 h-4" />
                Filters
            </flux:button>
            
            <flux:button 
                wire:click="exportSessions" 
                variant="primary" 
                size="sm"
            >
                <flux:icon icon="document-arrow-down" class="w-4 h-4" />
                Export CSV
            </flux:button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <flux:card>
            <flux:card.body class="text-center p-4">
                <div class="text-2xl font-bold text-indigo-600">{{ $summary['total_hours'] }}</div>
                <div class="text-sm text-gray-600">Total Hours</div>
            </flux:card.body>
        </flux:card>
        
        <flux:card>
            <flux:card.body class="text-center p-4">
                <div class="text-2xl font-bold text-green-600">{{ $summary['billable_hours'] }}</div>
                <div class="text-sm text-gray-600">Billable Hours</div>
            </flux:card.body>
        </flux:card>
        
        <flux:card>
            <flux:card.body class="text-center p-4">
                <div class="text-2xl font-bold text-blue-600">${{ number_format($summary['total_amount'], 2) }}</div>
                <div class="text-sm text-gray-600">Total Amount</div>
            </flux:card.body>
        </flux:card>
        
        <flux:card>
            <flux:card.body class="text-center p-4">
                <div class="text-2xl font-bold text-purple-600">{{ $summary['session_count'] }}</div>
                <div class="text-sm text-gray-600">Sessions</div>
            </flux:card.body>
        </flux:card>
    </div>

    {{-- Filters Panel --}}
    @if($showFilters)
        <flux:card>
            <flux:card.body>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <flux:field>
                        <flux:label>Start Date</flux:label>
                        <flux:input type="date" wire:model.live="filters.start_date" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>End Date</flux:label>
                        <flux:input type="date" wire:model.live="filters.end_date" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Project</flux:label>
                        <flux:select wire:model.live="filters.project_id">
                            <option value="">All Projects</option>
                            @foreach($userProjects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Billable</flux:label>
                        <flux:select wire:model.live="filters.billable_only">
                            <option value="">All Sessions</option>
                            <option value="1">Billable Only</option>
                            <option value="0">Non-Billable Only</option>
                        </flux:select>
                    </flux:field>
                </div>
            </flux:card.body>
        </flux:card>
    @endif

    {{-- Sessions Table --}}
    <flux:card>
        <flux:table>
            <flux:table.header>
                <flux:table.row>
                    <flux:table.cell>Date</flux:table.cell>
                    <flux:table.cell>Project</flux:table.cell>
                    <flux:table.cell>Task</flux:table.cell>
                    <flux:table.cell>Duration</flux:table.cell>
                    <flux:table.cell>Billable</flux:table.cell>
                    <flux:table.cell>Amount</flux:table.cell>
                    <flux:table.cell>Actions</flux:table.cell>
                </flux:table.row>
            </flux:table.header>
            
            <flux:table.body>
                @forelse($sessions as $session)
                    <flux:table.row>
                        <flux:table.cell>
                            <div>{{ $session->started_at->format('M j, Y') }}</div>
                            <div class="text-sm text-gray-500">
                                {{ $session->started_at->format('g:i A') }} - 
                                {{ $session->ended_at?->format('g:i A') ?? 'Active' }}
                            </div>
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            <div class="font-medium">{{ $session->project->name }}</div>
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            <div>{{ $session->task_description ?: 'No description' }}</div>
                            @if($session->categories->isNotEmpty())
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($session->categories as $category)
                                        <flux:badge 
                                            style="background-color: {{ $category->color_hex }}"
                                            size="xs"
                                        >
                                            {{ $category->name }}
                                        </flux:badge>
                                    @endforeach
                                </div>
                            @endif
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            @if($session->duration_seconds)
                                {{ gmdate('H:i:s', $session->duration_seconds) }}
                            @else
                                <span class="text-green-600">Active</span>
                            @endif
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            <flux:badge 
                                variant="{{ $session->is_billable ? 'success' : 'outline' }}" 
                                size="sm"
                            >
                                {{ $session->is_billable ? 'Yes' : 'No' }}
                            </flux:badge>
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            @if($session->is_billable && $session->hourly_rate && $session->duration_seconds)
                                ${{ number_format(($session->duration_seconds / 3600) * $session->hourly_rate, 2) }}
                            @else
                                -
                            @endif
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            <div class="flex items-center space-x-2">
                                <flux:button 
                                    wire:click="editSession({{ $session->id }})"
                                    variant="outline" 
                                    size="xs"
                                >
                                    <flux:icon icon="pencil" class="w-4 h-4" />
                                </flux:button>
                                
                                <flux:button 
                                    wire:click="deleteSession({{ $session->id }})"
                                    wire:confirm="Are you sure you want to delete this session?"
                                    variant="danger" 
                                    size="xs"
                                >
                                    <flux:icon icon="trash" class="w-4 h-4" />
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center text-gray-500 py-8">
                            No time tracking sessions found for the selected period.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.body>
        </flux:table>
        
        {{ $sessions->links() }}
    </flux:card>

    {{-- Edit Session Modal --}}
    @if($showEditModal && $editingSession)
        <flux:modal wire:model="showEditModal" size="lg">
            <flux:modal.header>
                <flux:heading>Edit Time Session</flux:heading>
            </flux:modal.header>
            
            <flux:modal.body>
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Task Description</flux:label>
                        <flux:input wire:model="sessionForm.task_description" />
                        <flux:error name="sessionForm.task_description" />
                    </flux:field>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Start Time</flux:label>
                            <flux:input type="datetime-local" wire:model="sessionForm.started_at" />
                            <flux:error name="sessionForm.started_at" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>End Time</flux:label>
                            <flux:input type="datetime-local" wire:model="sessionForm.ended_at" />
                            <flux:error name="sessionForm.ended_at" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Billable</flux:label>
                            <flux:checkbox wire:model="sessionForm.is_billable">
                                This session is billable
                            </flux:checkbox>
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Hourly Rate</flux:label>
                            <flux:input 
                                type="number" 
                                step="0.01" 
                                wire:model="sessionForm.hourly_rate"
                                placeholder="0.00"
                            />
                            <flux:error name="sessionForm.hourly_rate" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Notes</flux:label>
                        <flux:textarea wire:model="sessionForm.notes" rows="3" />
                        <flux:error name="sessionForm.notes" />
                    </flux:field>
                </div>
            </flux:modal.body>
            
            <flux:modal.footer>
                <flux:button 
                    wire:click="$set('showEditModal', false)" 
                    variant="outline"
                >
                    Cancel
                </flux:button>
                <flux:button 
                    wire:click="updateSession" 
                    variant="primary"
                >
                    Update Session
                </flux:button>
            </flux:modal.footer>
        </flux:modal>
    @endif
</div>

@script
<script>
    $wire.on('session-updated', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { type: 'success', message: data.message }
        }));
    });

    $wire.on('session-deleted', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { type: 'success', message: data.message }
        }));
    });

    $wire.on('export-started', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { type: 'info', message: data.message }
        }));
    });
</script>
@endscript
```

## Testing Strategy

### Feature Tests
```php
<?php

namespace Tests\Feature\TimeTracking;

use App\Models\User;
use App\Models\Project;
use App\Models\TimeTrackingSession;
use App\Services\TimeTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class TimeTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_start_time_tracking_session(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $service = new TimeTrackingService();

        $session = $service->startSession($user, $project, null, 'Working on mix');

        $this->assertInstanceOf(TimeTrackingSession::class, $session);
        $this->assertEquals($user->id, $session->user_id);
        $this->assertEquals($project->id, $session->project_id);
        $this->assertEquals('Working on mix', $session->task_description);
        $this->assertTrue($session->is_active);
        $this->assertNotNull($session->started_at);
        $this->assertNull($session->ended_at);
    }

    public function test_starting_new_session_stops_active_session(): void
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->for($user)->create();
        $project2 = Project::factory()->for($user)->create();
        $service = new TimeTrackingService();

        $firstSession = $service->startSession($user, $project1);
        $this->assertTrue($firstSession->is_active);

        $secondSession = $service->startSession($user, $project2);
        
        $firstSession->refresh();
        $this->assertFalse($firstSession->is_active);
        $this->assertNotNull($firstSession->ended_at);
        $this->assertTrue($secondSession->is_active);
    }

    public function test_user_can_stop_active_session(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $service = new TimeTrackingService();

        $session = $service->startSession($user, $project);
        
        // Simulate some time passing
        Carbon::setTestNow(now()->addMinutes(30));
        
        $stoppedSession = $service->stopSession($user, 'Session complete');

        $this->assertNotNull($stoppedSession);
        $this->assertEquals($session->id, $stoppedSession->id);
        $this->assertFalse($stoppedSession->is_active);
        $this->assertNotNull($stoppedSession->ended_at);
        $this->assertEquals(1800, $stoppedSession->duration_seconds); // 30 minutes
        $this->assertEquals('Session complete', $stoppedSession->notes);
    }

    public function test_user_can_pause_and_resume_session(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $service = new TimeTrackingService();

        $session = $service->startSession($user, $project);
        
        // Pause after 10 minutes
        Carbon::setTestNow(now()->addMinutes(10));
        $pausedSession = $service->pauseSession($user);
        
        $this->assertNotNull($pausedSession->auto_paused_at);
        
        // Resume after 5 minutes of being paused
        Carbon::setTestNow(now()->addMinutes(5));
        $resumedSession = $service->resumeSession($user);
        
        $this->assertNull($resumedSession->auto_paused_at);
        $this->assertEquals(300, $resumedSession->auto_pause_duration); // 5 minutes
        
        // Stop after another 10 minutes
        Carbon::setTestNow(now()->addMinutes(10));
        $stoppedSession = $service->stopSession($user);
        
        // Total duration should be 20 minutes (10 + 10), pause time (5 minutes) excluded
        $this->assertEquals(1200, $stoppedSession->duration_seconds);
    }

    public function test_user_can_update_session_details(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $service = new TimeTrackingService();

        $session = $service->startSession($user, $project);
        $service->stopSession($user);

        $updatedSession = $service->updateSession($session, [
            'task_description' => 'Updated task',
            'is_billable' => false,
            'hourly_rate' => 75.00,
            'notes' => 'Updated notes'
        ]);

        $this->assertEquals('Updated task', $updatedSession->task_description);
        $this->assertFalse($updatedSession->is_billable);
        $this->assertEquals(75.00, $updatedSession->hourly_rate);
        $this->assertEquals('Updated notes', $updatedSession->notes);
    }

    public function test_time_report_generation(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $service = new TimeTrackingService();

        // Create several sessions
        TimeTrackingSession::factory()
            ->for($user)
            ->for($project)
            ->count(5)
            ->create([
                'duration_seconds' => 3600, // 1 hour each
                'is_billable' => true,
                'hourly_rate' => 50.00,
                'ended_at' => now(),
                'is_active' => false
            ]);

        $export = $service->generateTimeReport($user, [], 'csv');

        $this->assertEquals($user->id, $export->user_id);
        $this->assertEquals('csv', $export->export_type);
        $this->assertEquals('pending', $export->status);
    }

    public function test_get_user_sessions_with_filters(): void
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->for($user)->create();
        $project2 = Project::factory()->for($user)->create();
        $service = new TimeTrackingService();

        // Create sessions for different projects and dates
        TimeTrackingSession::factory()
            ->for($user)
            ->for($project1)
            ->create(['started_at' => now()->subDays(5), 'is_billable' => true]);

        TimeTrackingSession::factory()
            ->for($user)
            ->for($project2)
            ->create(['started_at' => now()->subDays(2), 'is_billable' => false]);

        // Test project filter
        $project1Sessions = $service->getUserSessions(
            $user, null, null, $project1->id
        )->get();
        
        $this->assertEquals(1, $project1Sessions->count());
        $this->assertEquals($project1->id, $project1Sessions->first()->project_id);

        // Test billable filter
        $billableSessions = $service->getUserSessions(
            $user, null, null, null, true
        )->get();
        
        $this->assertEquals(1, $billableSessions->count());
        $this->assertTrue($billableSessions->first()->is_billable);

        // Test date range filter
        $recentSessions = $service->getUserSessions(
            $user, now()->subDays(3), now()
        )->get();
        
        $this->assertEquals(1, $recentSessions->count());
    }
}
```

### Unit Tests
```php
<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Project;
use App\Models\TimeTrackingSession;
use App\Services\TimeTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class TimeTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_duration_correctly_with_pause(): void
    {
        $service = new TimeTrackingService();
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $session = TimeTrackingSession::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'auto_pause_duration' => 600, // 10 minutes paused
            'is_active' => false
        ]);

        // Should be 50 minutes (3600 - 600 seconds)
        $this->assertEquals(3000, $session->duration_seconds);
    }

    public function test_only_one_active_session_per_user(): void
    {
        $service = new TimeTrackingService();
        $user = User::factory()->create();
        $project1 = Project::factory()->for($user)->create();
        $project2 = Project::factory()->for($user)->create();

        $firstSession = $service->startSession($user, $project1);
        $secondSession = $service->startSession($user, $project2);

        $activeSessions = TimeTrackingSession::where('user_id', $user->id)
            ->where('is_active', true)
            ->count();

        $this->assertEquals(1, $activeSessions);
        $this->assertEquals($secondSession->id, $service->getActiveSession($user)->id);
    }

    public function test_session_duration_calculation_excludes_pause_time(): void
    {
        $startTime = Carbon::parse('2024-01-01 09:00:00');
        $pauseTime = Carbon::parse('2024-01-01 10:00:00');
        $resumeTime = Carbon::parse('2024-01-01 10:30:00');
        $endTime = Carbon::parse('2024-01-01 11:30:00');

        // Total time: 2.5 hours
        // Pause time: 30 minutes
        // Expected working time: 2 hours = 7200 seconds

        $session = TimeTrackingSession::factory()->create([
            'started_at' => $startTime,
            'ended_at' => $endTime,
            'auto_pause_duration' => 1800, // 30 minutes
            'is_active' => false
        ]);

        $this->assertEquals(7200, $session->duration_seconds);
    }

    public function test_validates_session_update_fields(): void
    {
        $service = new TimeTrackingService();
        $session = TimeTrackingSession::factory()->create();

        $updates = [
            'task_description' => 'Valid field',
            'invalid_field' => 'Should be filtered out',
            'hourly_rate' => 50.00
        ];

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('updateSession');

        $updatedSession = $method->invoke($service, $session, $updates);

        $this->assertEquals('Valid field', $updatedSession->task_description);
        $this->assertEquals(50.00, $updatedSession->hourly_rate);
        $this->assertNull($updatedSession->getAttributes()['invalid_field'] ?? null);
    }
}
```

## Implementation Steps

### Phase 1: Core Infrastructure (Week 1)
1. **Database Migration Setup**
   - Create time tracking sessions table
   - Add categories and preferences tables
   - Create export tracking table

2. **Service Architecture**
   - Implement `TimeTrackingService` with session management
   - Add pause/resume functionality with duration calculation
   - Create export generation system

3. **Background Jobs**
   - Export processing job for CSV/PDF generation
   - Cleanup job for old export files
   - Session timeout detection job

### Phase 2: UI Implementation (Week 2)
1. **Floating Overlay Component**
   - Real-time timer with Alpine.js
   - Session controls (start/stop/pause)
   - Minimized state for distraction-free work

2. **Management Interface**
   - Session listing with filters
   - Edit/delete functionality
   - Summary statistics and charts

3. **Export Interface**
   - Report generation with custom filters
   - Download management
   - Export history tracking

### Phase 3: Advanced Features (Week 3)
1. **Categories and Organization**
   - Task categorization system
   - Color-coded project grouping
   - Custom hourly rates per category

2. **Automation Features**
   - Idle detection and auto-pause
   - Smart session suggestions
   - Recurring task templates

3. **Reporting Enhancements**
   - PDF report generation
   - Chart visualizations
   - Billing integrations

### Phase 4: Polish and Integration (Week 4)
1. **Cross-Device Sync**
   - Session state persistence
   - Mobile app considerations
   - Offline capability

2. **Integration Points**
   - Connect with project workflows
   - Invoice generation integration
   - Calendar and scheduling integration

3. **Performance Optimization**
   - Efficient timer updates
   - Database query optimization
   - Large dataset handling

## Security Considerations

### Data Protection
- **Session Isolation**: Users can only access their own time tracking data
- **Input Validation**: Comprehensive validation for all time entries
- **Rate Limiting**: Prevent spam session creation
- **Audit Trail**: Track all modifications to time entries

### Privacy Features
- **Data Retention**: Configurable retention policies for old sessions
- **Export Security**: Temporary URLs with expiration for downloads
- **Role-Based Access**: Team member permissions for shared projects
- **Anonymous Options**: Option to track time without detailed descriptions

This comprehensive implementation plan provides professional time tracking capabilities while maintaining MixPitch's focus on creative workflows and user experience.
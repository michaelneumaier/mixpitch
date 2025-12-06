# Communication Hub Technical Specification

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           FRONTEND                                       │
├─────────────────────────────────────────────────────────────────────────┤
│  CommunicationHub (Livewire)                                            │
│  ├── CommunicationFab (Livewire) - FAB with badge                       │
│  ├── CommunicationPanel (Livewire) - Expandable panel                   │
│  │   ├── MessagesTab - Direct messages                                  │
│  │   ├── ActivityTab - Event timeline                                   │
│  │   └── TodoTab - Actions needed                                       │
│  └── Alpine.js - UI state (open/close, tabs, scroll)                    │
├─────────────────────────────────────────────────────────────────────────┤
│                           BACKEND                                        │
├─────────────────────────────────────────────────────────────────────────┤
│  CommunicationService                                                    │
│  ├── Message CRUD                                                       │
│  ├── Read receipt tracking                                              │
│  ├── Activity feed generation                                           │
│  └── Notification orchestration                                         │
├─────────────────────────────────────────────────────────────────────────┤
│  WorkSessionService                                                      │
│  ├── Session start/pause/end                                            │
│  ├── Time tracking                                                      │
│  └── Presence management                                                │
├─────────────────────────────────────────────────────────────────────────┤
│                           DATA LAYER                                     │
├─────────────────────────────────────────────────────────────────────────┤
│  PitchEvent (extended) - Messages and events                            │
│  WorkSession (new) - Time tracking                                      │
│  UserPresence (new or cached) - Online status                           │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Data Model Changes

### Extended PitchEvent Model

```php
// Migration: add_communication_fields_to_pitch_events
Schema::table('pitch_events', function (Blueprint $table) {
    // Read receipt tracking
    $table->timestamp('read_at')->nullable()->after('metadata');
    $table->json('read_by')->nullable()->after('read_at'); // For multiple readers

    // Message delivery status
    $table->enum('delivery_status', ['pending', 'delivered', 'read'])
          ->default('delivered')
          ->after('read_by');

    // Urgency flag
    $table->boolean('is_urgent')->default(false)->after('delivery_status');

    // Threading support
    $table->foreignId('thread_id')
          ->nullable()
          ->constrained('pitch_events')
          ->nullOnDelete()
          ->after('is_urgent');

    // Indexes
    $table->index(['pitch_id', 'read_at']);
    $table->index(['pitch_id', 'delivery_status']);
});
```

### New WorkSession Model

```php
// Migration: create_work_sessions_table
Schema::create('work_sessions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->timestamp('started_at');
    $table->timestamp('ended_at')->nullable();
    $table->timestamp('paused_at')->nullable();
    $table->unsignedInteger('total_seconds')->default(0);
    $table->text('notes')->nullable();
    $table->boolean('focus_mode')->default(false);
    $table->enum('visibility', ['full', 'summary', 'minimal'])->default('summary');
    $table->enum('status', ['active', 'paused', 'completed'])->default('active');
    $table->timestamps();

    $table->index(['project_id', 'status']);
    $table->index(['user_id', 'status']);
});
```

### User Presence (Cache-Based)

```php
// No database table - use Redis/Cache for ephemeral presence data

// Cache key structure:
// user_presence:{user_id} => ['status' => 'online', 'last_seen' => timestamp, 'project_id' => nullable]

// Example presence data:
[
    'status' => 'online', // online, away, offline
    'last_seen' => 1701892800,
    'current_project_id' => 123, // null if not in a project
    'work_session_id' => 456, // null if no active session
]
```

---

## Model Definitions

### WorkSession Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkSession extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'started_at',
        'ended_at',
        'paused_at',
        'total_seconds',
        'notes',
        'focus_mode',
        'visibility',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'paused_at' => 'datetime',
        'focus_mode' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function getDurationAttribute(): int
    {
        if ($this->ended_at) {
            return $this->total_seconds;
        }

        $elapsed = now()->diffInSeconds($this->paused_at ?? $this->started_at);
        return $this->total_seconds + $elapsed;
    }

    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->duration;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }
}
```

### Extended PitchEvent Model

```php
// Add to existing PitchEvent model

// New constants
const DELIVERY_PENDING = 'pending';
const DELIVERY_DELIVERED = 'delivered';
const DELIVERY_READ = 'read';

// Add to $casts
protected $casts = [
    // ... existing casts
    'read_at' => 'datetime',
    'read_by' => 'array',
    'is_urgent' => 'boolean',
];

// New relationships
public function thread(): BelongsTo
{
    return $this->belongsTo(PitchEvent::class, 'thread_id');
}

public function replies(): HasMany
{
    return $this->hasMany(PitchEvent::class, 'thread_id');
}

// New methods
public function markAsRead(int $userId): void
{
    $readBy = $this->read_by ?? [];

    if (!in_array($userId, $readBy)) {
        $readBy[] = $userId;
        $this->update([
            'read_at' => $this->read_at ?? now(),
            'read_by' => $readBy,
            'delivery_status' => self::DELIVERY_READ,
        ]);
    }
}

public function isReadBy(int $userId): bool
{
    return in_array($userId, $this->read_by ?? []);
}

public function isUnread(): bool
{
    return $this->delivery_status !== self::DELIVERY_READ;
}

// Scopes
public function scopeUnread(Builder $query): Builder
{
    return $query->where('delivery_status', '!=', self::DELIVERY_READ);
}

public function scopeMessages(Builder $query): Builder
{
    return $query->whereIn('event_type', ['client_comment', 'producer_comment']);
}

public function scopeForCommunicationHub(Builder $query): Builder
{
    return $query->whereIn('event_type', [
        'client_comment',
        'producer_comment',
        'client_revisions_requested',
        'status_change',
        'file_uploaded',
        'client_approved',
    ]);
}
```

---

## Service Layer

### CommunicationService

```php
<?php

namespace App\Services;

use App\Models\Pitch;
use App\Models\PitchEvent;
use App\Models\User;
use Illuminate\Support\Collection;

class CommunicationService
{
    public function __construct(
        private NotificationService $notificationService,
        private EmailService $emailService,
    ) {}

    /**
     * Send a message from producer to client
     */
    public function sendProducerMessage(
        Pitch $pitch,
        User $producer,
        string $message,
        bool $sendEmail = false,
        bool $isUrgent = false,
    ): PitchEvent {
        $event = PitchEvent::create([
            'pitch_id' => $pitch->id,
            'event_type' => 'producer_comment',
            'comment' => $message,
            'status' => $pitch->status,
            'created_by' => $producer->id,
            'is_urgent' => $isUrgent,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
            'metadata' => [
                'visible_to_client' => true,
                'comment_type' => 'producer_update',
                'send_email' => $sendEmail,
            ],
        ]);

        // Always notify client (they may not be logged in)
        $this->notificationService->notifyClientProducerCommented(
            $pitch,
            $producer,
            $message
        );

        // Dispatch event for real-time (when implemented)
        event(new \App\Events\CommunicationMessageSent($event));

        return $event;
    }

    /**
     * Send a message from client to producer
     */
    public function sendClientMessage(
        Pitch $pitch,
        string $clientEmail,
        string $message,
        bool $isUrgent = false,
    ): PitchEvent {
        $event = PitchEvent::create([
            'pitch_id' => $pitch->id,
            'event_type' => 'client_comment',
            'comment' => $message,
            'status' => $pitch->status,
            'created_by' => null,
            'is_urgent' => $isUrgent,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
            'metadata' => [
                'client_email' => $clientEmail,
                'visible_to_client' => true,
            ],
        ]);

        $this->notificationService->notifyProducerClientCommented(
            $pitch,
            $clientEmail,
            $message
        );

        event(new \App\Events\CommunicationMessageSent($event));

        return $event;
    }

    /**
     * Mark messages as read
     */
    public function markMessagesAsRead(Pitch $pitch, User $user): int
    {
        $unreadEvents = $pitch->events()
            ->messages()
            ->unread()
            ->where('created_by', '!=', $user->id)
            ->orWhereNull('created_by') // Client messages
            ->get();

        foreach ($unreadEvents as $event) {
            $event->markAsRead($user->id);
        }

        event(new \App\Events\MessagesRead($pitch, $user, $unreadEvents->pluck('id')));

        return $unreadEvents->count();
    }

    /**
     * Get unread count for a pitch
     */
    public function getUnreadCount(Pitch $pitch, User $user): int
    {
        return $pitch->events()
            ->messages()
            ->unread()
            ->where(function ($query) use ($user) {
                $query->where('created_by', '!=', $user->id)
                      ->orWhereNull('created_by');
            })
            ->count();
    }

    /**
     * Get communication feed for hub
     */
    public function getCommunicationFeed(Pitch $pitch, int $limit = 50): Collection
    {
        return $pitch->events()
            ->forCommunicationHub()
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Get pending actions (to-do items) for a user
     */
    public function getPendingActions(Pitch $pitch, User $user): Collection
    {
        $actions = collect();

        // Unread messages
        $unreadMessages = $pitch->events()
            ->messages()
            ->unread()
            ->where(function ($query) use ($user) {
                $query->where('created_by', '!=', $user->id)
                      ->orWhereNull('created_by');
            })
            ->get();

        foreach ($unreadMessages as $message) {
            $actions->push([
                'type' => 'unread_message',
                'event' => $message,
                'priority' => $message->is_urgent ? 'high' : 'normal',
            ]);
        }

        // Pending revision requests (for producer)
        if ($pitch->status === Pitch::STATUS_CLIENT_REVISIONS_REQUESTED) {
            $actions->push([
                'type' => 'revision_requested',
                'event' => $pitch->events()
                    ->where('event_type', 'client_revisions_requested')
                    ->latest()
                    ->first(),
                'priority' => 'high',
            ]);
        }

        return $actions->sortByDesc('priority');
    }
}
```

### WorkSessionService

```php
<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Support\Facades\Cache;

class WorkSessionService
{
    /**
     * Start a new work session
     */
    public function startSession(
        Project $project,
        User $user,
        bool $focusMode = false,
        string $visibility = 'summary',
        ?string $notes = null,
    ): WorkSession {
        // End any existing active session
        $this->endActiveSession($user);

        $session = WorkSession::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'started_at' => now(),
            'focus_mode' => $focusMode,
            'visibility' => $visibility,
            'notes' => $notes,
            'status' => 'active',
        ]);

        $this->updatePresence($user, 'online', $project->id, $session->id);

        event(new \App\Events\WorkSessionStarted($session));

        return $session;
    }

    /**
     * Pause current session
     */
    public function pauseSession(WorkSession $session): WorkSession
    {
        if (!$session->isActive()) {
            return $session;
        }

        $elapsed = now()->diffInSeconds($session->started_at);

        $session->update([
            'paused_at' => now(),
            'total_seconds' => $session->total_seconds + $elapsed,
            'status' => 'paused',
        ]);

        event(new \App\Events\WorkSessionPaused($session));

        return $session->fresh();
    }

    /**
     * Resume paused session
     */
    public function resumeSession(WorkSession $session): WorkSession
    {
        if (!$session->isPaused()) {
            return $session;
        }

        $session->update([
            'started_at' => now(),
            'paused_at' => null,
            'status' => 'active',
        ]);

        event(new \App\Events\WorkSessionResumed($session));

        return $session->fresh();
    }

    /**
     * End a work session
     */
    public function endSession(WorkSession $session): WorkSession
    {
        if ($session->status === 'completed') {
            return $session;
        }

        $elapsed = $session->isActive()
            ? now()->diffInSeconds($session->paused_at ?? $session->started_at)
            : 0;

        $session->update([
            'ended_at' => now(),
            'total_seconds' => $session->total_seconds + $elapsed,
            'status' => 'completed',
        ]);

        $this->updatePresence($session->user, 'online', null, null);

        event(new \App\Events\WorkSessionEnded($session));

        return $session->fresh();
    }

    /**
     * Update session notes
     */
    public function updateNotes(WorkSession $session, string $notes): WorkSession
    {
        $session->update(['notes' => $notes]);

        event(new \App\Events\WorkSessionNotesUpdated($session));

        return $session->fresh();
    }

    /**
     * Get active session for user
     */
    public function getActiveSession(User $user): ?WorkSession
    {
        return WorkSession::where('user_id', $user->id)
            ->whereIn('status', ['active', 'paused'])
            ->first();
    }

    /**
     * End any active session for user
     */
    public function endActiveSession(User $user): void
    {
        $session = $this->getActiveSession($user);

        if ($session) {
            $this->endSession($session);
        }
    }

    /**
     * Get session history for a project
     */
    public function getProjectSessionHistory(Project $project, int $days = 30): Collection
    {
        return WorkSession::where('project_id', $project->id)
            ->where('status', 'completed')
            ->where('ended_at', '>=', now()->subDays($days))
            ->orderBy('ended_at', 'desc')
            ->get();
    }

    /**
     * Get total time worked on a project
     */
    public function getTotalProjectTime(Project $project): int
    {
        return WorkSession::where('project_id', $project->id)
            ->where('status', 'completed')
            ->sum('total_seconds');
    }

    /**
     * Update user presence in cache
     */
    private function updatePresence(
        User $user,
        string $status,
        ?int $projectId,
        ?int $sessionId,
    ): void {
        Cache::put(
            "user_presence:{$user->id}",
            [
                'status' => $status,
                'last_seen' => now()->timestamp,
                'current_project_id' => $projectId,
                'work_session_id' => $sessionId,
            ],
            now()->addMinutes(30)
        );
    }

    /**
     * Get user presence
     */
    public function getPresence(User $user): array
    {
        return Cache::get("user_presence:{$user->id}", [
            'status' => 'offline',
            'last_seen' => null,
            'current_project_id' => null,
            'work_session_id' => null,
        ]);
    }

    /**
     * Heartbeat to keep presence alive
     */
    public function heartbeat(User $user, ?int $projectId = null): void
    {
        $presence = $this->getPresence($user);
        $presence['last_seen'] = now()->timestamp;
        $presence['status'] = 'online';

        if ($projectId) {
            $presence['current_project_id'] = $projectId;
        }

        Cache::put("user_presence:{$user->id}", $presence, now()->addMinutes(30));
    }
}
```

---

## Livewire Components

### CommunicationHub Component

```php
<?php

namespace App\Livewire\Components;

use App\Models\Pitch;
use App\Services\CommunicationService;
use App\Services\WorkSessionService;
use Livewire\Component;
use Livewire\Attributes\On;

class CommunicationHub extends Component
{
    public Pitch $pitch;
    public bool $isOpen = false;
    public string $activeTab = 'messages';
    public string $newMessage = '';
    public bool $sendEmailCopy = false;
    public bool $isUrgent = false;

    // Computed data
    public int $unreadCount = 0;
    public bool $otherPartyOnline = false;

    protected CommunicationService $communicationService;
    protected WorkSessionService $workSessionService;

    public function boot(
        CommunicationService $communicationService,
        WorkSessionService $workSessionService,
    ): void {
        $this->communicationService = $communicationService;
        $this->workSessionService = $workSessionService;
    }

    public function mount(Pitch $pitch): void
    {
        $this->pitch = $pitch;
        $this->refreshUnreadCount();
        $this->checkOtherPartyPresence();
    }

    public function openHub(): void
    {
        $this->isOpen = true;
        $this->markMessagesAsRead();
    }

    public function closeHub(): void
    {
        $this->isOpen = false;
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;

        if ($tab === 'messages') {
            $this->markMessagesAsRead();
        }
    }

    public function sendMessage(): void
    {
        $this->validate([
            'newMessage' => 'required|string|max:5000',
        ]);

        $this->communicationService->sendProducerMessage(
            $this->pitch,
            auth()->user(),
            $this->newMessage,
            $this->sendEmailCopy,
            $this->isUrgent,
        );

        $this->reset(['newMessage', 'sendEmailCopy', 'isUrgent']);
        $this->dispatch('message-sent');
    }

    public function markMessagesAsRead(): void
    {
        $this->communicationService->markMessagesAsRead(
            $this->pitch,
            auth()->user()
        );

        $this->unreadCount = 0;
    }

    #[On('refresh-communication')]
    public function refresh(): void
    {
        $this->refreshUnreadCount();
        $this->checkOtherPartyPresence();
    }

    private function refreshUnreadCount(): void
    {
        $this->unreadCount = $this->communicationService->getUnreadCount(
            $this->pitch,
            auth()->user()
        );
    }

    private function checkOtherPartyPresence(): void
    {
        // For producer, check client presence (if they're logged in)
        // For client portal, this would check producer presence
        $producer = $this->pitch->user;
        $presence = $this->workSessionService->getPresence($producer);

        $this->otherPartyOnline = $presence['status'] === 'online'
            && $presence['current_project_id'] === $this->pitch->project_id;
    }

    public function getMessagesProperty(): Collection
    {
        return $this->communicationService->getCommunicationFeed($this->pitch);
    }

    public function getPendingActionsProperty(): Collection
    {
        return $this->communicationService->getPendingActions(
            $this->pitch,
            auth()->user()
        );
    }

    public function render()
    {
        return view('livewire.components.communication-hub');
    }
}
```

---

## Events for Real-Time Ready

```php
// app/Events/CommunicationMessageSent.php
class CommunicationMessageSent implements ShouldBroadcast
{
    public function __construct(public PitchEvent $event) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("pitch.{$this->event->pitch_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}

// app/Events/MessagesRead.php
class MessagesRead implements ShouldBroadcast
{
    public function __construct(
        public Pitch $pitch,
        public User $user,
        public Collection $eventIds,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("pitch.{$this->pitch->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'messages.read';
    }
}

// app/Events/WorkSessionStarted.php
class WorkSessionStarted implements ShouldBroadcast
{
    public function __construct(public WorkSession $session) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("project.{$this->session->project_id}"),
        ];
    }
}
```

---

## Polling Strategy (Pre-Real-Time)

Until WebSockets are implemented, use smart polling:

```javascript
// resources/js/communication-hub.js
class CommunicationHubPoller {
    constructor(pitchId) {
        this.pitchId = pitchId;
        this.pollInterval = 30000; // 30 seconds
        this.fastPollInterval = 5000; // 5 seconds when hub is open
        this.currentInterval = this.pollInterval;
        this.timer = null;
    }

    start() {
        this.poll();
        this.timer = setInterval(() => this.poll(), this.currentInterval);
    }

    stop() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }

    setFastMode(enabled) {
        this.currentInterval = enabled ? this.fastPollInterval : this.pollInterval;
        this.stop();
        this.start();
    }

    async poll() {
        // Livewire will handle the actual update
        Livewire.dispatch('refresh-communication');
    }
}

// Usage in Alpine component
document.addEventListener('alpine:init', () => {
    Alpine.data('communicationHub', () => ({
        poller: null,

        init() {
            this.poller = new CommunicationHubPoller(this.$wire.pitch.id);
            this.poller.start();
        },

        openHub() {
            this.$wire.openHub();
            this.poller.setFastMode(true);
        },

        closeHub() {
            this.$wire.closeHub();
            this.poller.setFastMode(false);
        },

        destroy() {
            this.poller.stop();
        }
    }));
});
```

---

## API Endpoints (Optional)

For mobile apps or SPA enhancements:

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('pitches/{pitch}/communication')->group(function () {
        Route::get('messages', [CommunicationController::class, 'messages']);
        Route::post('messages', [CommunicationController::class, 'send']);
        Route::post('read', [CommunicationController::class, 'markRead']);
        Route::get('unread-count', [CommunicationController::class, 'unreadCount']);
    });

    Route::prefix('work-sessions')->group(function () {
        Route::post('start', [WorkSessionController::class, 'start']);
        Route::post('{session}/pause', [WorkSessionController::class, 'pause']);
        Route::post('{session}/resume', [WorkSessionController::class, 'resume']);
        Route::post('{session}/end', [WorkSessionController::class, 'end']);
        Route::put('{session}/notes', [WorkSessionController::class, 'updateNotes']);
    });

    Route::post('heartbeat', [PresenceController::class, 'heartbeat']);
});
```

---

## Testing Strategy

```php
// tests/Feature/CommunicationHubTest.php
it('sends a message from producer to client', function () {
    $producer = User::factory()->create();
    $project = Project::factory()->clientManagement()->create(['user_id' => $producer->id]);
    $pitch = $project->pitches()->first();

    Livewire::actingAs($producer)
        ->test(CommunicationHub::class, ['pitch' => $pitch])
        ->set('newMessage', 'Test message to client')
        ->call('sendMessage')
        ->assertDispatched('message-sent');

    expect($pitch->events()->where('event_type', 'producer_comment')->count())->toBe(1);
});

it('marks messages as read when hub is opened', function () {
    // Create unread client message
    $event = PitchEvent::factory()->clientComment()->create([
        'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
    ]);

    Livewire::actingAs($event->pitch->user)
        ->test(CommunicationHub::class, ['pitch' => $event->pitch])
        ->call('openHub');

    expect($event->fresh()->delivery_status)->toBe(PitchEvent::DELIVERY_READ);
});

it('tracks work session time correctly', function () {
    $session = WorkSession::factory()->create([
        'started_at' => now()->subHours(2),
        'status' => 'active',
    ]);

    $service = app(WorkSessionService::class);
    $service->endSession($session);

    expect($session->fresh())
        ->status->toBe('completed')
        ->total_seconds->toBeGreaterThanOrEqual(7200); // ~2 hours
});
```

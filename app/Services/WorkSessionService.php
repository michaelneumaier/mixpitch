<?php

namespace App\Services;

use App\Models\Pitch;
use App\Models\PitchEvent;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WorkSessionService
{
    // Cache keys
    private const PRESENCE_CACHE_PREFIX = 'user_presence_';

    private const PRESENCE_TTL = 120; // 2 minutes

    /**
     * Start a new work session
     */
    public function startSession(
        Pitch $pitch,
        User $user,
        bool $isVisibleToClient = true,
        bool $focusMode = false
    ): WorkSession {
        // End any existing active session for this user globally (not just on this pitch)
        $existingSession = $this->getUserActiveSession($user);
        if ($existingSession) {
            $this->endSession($existingSession);
        }

        $session = WorkSession::create([
            'user_id' => $user->id,
            'pitch_id' => $pitch->id,
            'status' => WorkSession::STATUS_ACTIVE,
            'started_at' => now(),
            'is_visible_to_client' => $isVisibleToClient,
            'focus_mode' => $focusMode,
        ]);

        // Update presence
        $this->updatePresence($user, 'working', $pitch->id);

        Log::info('Work session started', [
            'session_id' => $session->id,
            'user_id' => $user->id,
            'pitch_id' => $pitch->id,
            'focus_mode' => $focusMode,
        ]);

        return $session;
    }

    /**
     * Pause an active session
     */
    public function pauseSession(WorkSession $session): WorkSession
    {
        if (! $session->isActive()) {
            throw new \InvalidArgumentException('Cannot pause a session that is not active');
        }

        // Calculate duration since start/last resume
        $activeStart = $session->paused_at ?? $session->started_at;
        $additionalSeconds = now()->diffInSeconds($activeStart);

        $session->update([
            'status' => WorkSession::STATUS_PAUSED,
            'paused_at' => now(),
            'total_duration_seconds' => $session->total_duration_seconds + $additionalSeconds,
        ]);

        // Update presence
        $this->updatePresence($session->user, 'away', $session->pitch_id);

        Log::info('Work session paused', [
            'session_id' => $session->id,
            'duration_added' => $additionalSeconds,
        ]);

        return $session->fresh();
    }

    /**
     * Resume a paused session
     */
    public function resumeSession(WorkSession $session): WorkSession
    {
        if (! $session->isPaused()) {
            throw new \InvalidArgumentException('Cannot resume a session that is not paused');
        }

        $session->update([
            'status' => WorkSession::STATUS_ACTIVE,
            'paused_at' => now(), // Reset the active start time
        ]);

        // Update presence
        $this->updatePresence($session->user, 'working', $session->pitch_id);

        Log::info('Work session resumed', [
            'session_id' => $session->id,
        ]);

        return $session->fresh();
    }

    /**
     * End a session
     */
    public function endSession(WorkSession $session): WorkSession
    {
        if ($session->isEnded()) {
            return $session;
        }

        // Calculate final duration if session was active
        $totalDuration = $session->total_duration_seconds;
        if ($session->isActive()) {
            $activeStart = $session->paused_at ?? $session->started_at;
            $totalDuration += now()->diffInSeconds($activeStart);
        }

        $session->update([
            'status' => WorkSession::STATUS_ENDED,
            'ended_at' => now(),
            'total_duration_seconds' => $totalDuration,
        ]);

        // Create activity event for the communication hub (only if visible to client)
        if ($session->is_visible_to_client) {
            PitchEvent::create([
                'pitch_id' => $session->pitch_id,
                'event_type' => PitchEvent::TYPE_WORK_SESSION_COMPLETED,
                'created_by' => $session->user_id,
                'metadata' => [
                    'session_id' => $session->id,
                    'duration_seconds' => $totalDuration,
                    'duration_formatted' => $this->formatDuration($totalDuration),
                    'notes' => $session->notes,
                ],
            ]);
        }

        // Update presence
        $this->updatePresence($session->user, 'online');

        Log::info('Work session ended', [
            'session_id' => $session->id,
            'total_duration' => $totalDuration,
        ]);

        return $session->fresh();
    }

    /**
     * Update session notes
     */
    public function updateNotes(WorkSession $session, ?string $notes): WorkSession
    {
        $session->update(['notes' => $notes]);

        return $session->fresh();
    }

    /**
     * Toggle focus mode
     */
    public function toggleFocusMode(WorkSession $session): WorkSession
    {
        $session->update(['focus_mode' => ! $session->focus_mode]);

        Log::info('Focus mode toggled', [
            'session_id' => $session->id,
            'focus_mode' => $session->focus_mode,
        ]);

        return $session->fresh();
    }

    /**
     * Toggle visibility to client
     */
    public function toggleVisibility(WorkSession $session): WorkSession
    {
        $session->update(['is_visible_to_client' => ! $session->is_visible_to_client]);

        return $session->fresh();
    }

    /**
     * Get the active session for a user on a pitch
     */
    public function getActiveSession(Pitch $pitch, User $user): ?WorkSession
    {
        return WorkSession::forPitch($pitch)
            ->forUser($user)
            ->inProgress()
            ->latest('started_at')
            ->first();
    }

    /**
     * Get any active session for a user (across all pitches)
     */
    public function getUserActiveSession(User $user): ?WorkSession
    {
        return WorkSession::forUser($user)
            ->inProgress()
            ->latest('started_at')
            ->first();
    }

    /**
     * Get recent sessions for a pitch (visible to client)
     */
    public function getRecentSessions(Pitch $pitch, int $days = 7, bool $visibleOnly = true): Collection
    {
        $query = WorkSession::forPitch($pitch)
            ->recent($days)
            ->with('user')
            ->orderBy('started_at', 'desc');

        if ($visibleOnly) {
            $query->visibleToClient();
        }

        return $query->get();
    }

    /**
     * Get total work time for a pitch
     */
    public function getTotalWorkTime(Pitch $pitch, bool $visibleOnly = true): int
    {
        $query = WorkSession::forPitch($pitch)->ended();

        if ($visibleOnly) {
            $query->visibleToClient();
        }

        return $query->sum('total_duration_seconds');
    }

    /**
     * Get formatted total work time
     */
    public function getFormattedTotalWorkTime(Pitch $pitch, bool $visibleOnly = true): string
    {
        $seconds = $this->getTotalWorkTime($pitch, $visibleOnly);

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }

        return sprintf('%dm', $minutes);
    }

    // ==================
    // Presence Methods
    // ==================

    /**
     * Update user presence
     */
    public function updatePresence(User $user, string $status, ?int $pitchId = null): void
    {
        $cacheKey = self::PRESENCE_CACHE_PREFIX.$user->id;

        $presenceData = [
            'status' => $status, // online, away, working, offline
            'pitch_id' => $pitchId,
            'updated_at' => now()->toISOString(),
        ];

        Cache::put($cacheKey, $presenceData, self::PRESENCE_TTL);
    }

    /**
     * Get user presence
     */
    public function getPresence(User $user): array
    {
        $cacheKey = self::PRESENCE_CACHE_PREFIX.$user->id;

        $presence = Cache::get($cacheKey);

        if (! $presence) {
            return [
                'status' => 'offline',
                'pitch_id' => null,
                'updated_at' => null,
            ];
        }

        return $presence;
    }

    /**
     * Heartbeat to keep presence alive
     */
    public function heartbeat(User $user, ?int $pitchId = null): void
    {
        $currentPresence = $this->getPresence($user);

        // If currently working on this pitch, maintain working status
        if ($pitchId && $currentPresence['status'] === 'working' && $currentPresence['pitch_id'] === $pitchId) {
            $this->updatePresence($user, 'working', $pitchId);
        } else {
            $this->updatePresence($user, 'online', $pitchId);
        }
    }

    /**
     * Get presence for a pitch's producer (for client view)
     */
    public function getProducerPresenceForPitch(Pitch $pitch): array
    {
        $producer = $pitch->user;

        if (! $producer) {
            return ['status' => 'offline', 'label' => 'Offline'];
        }

        // Check visibility setting
        if ($producer->presence_visibility === WorkSession::VISIBILITY_MINIMAL) {
            return ['status' => 'hidden', 'label' => ''];
        }

        $presence = $this->getPresence($producer);

        // Check if working on this specific pitch
        $activeSession = $this->getActiveSession($pitch, $producer);

        if ($activeSession && $activeSession->is_visible_to_client) {
            if ($activeSession->isActive()) {
                // Use session notes as label if available, otherwise default
                $label = $activeSession->notes
                    ? $activeSession->notes
                    : ($producer->presence_visibility === WorkSession::VISIBILITY_FULL
                        ? 'Working on your project'
                        : 'Online');

                return [
                    'status' => 'working',
                    'label' => $label,
                    'notes' => $activeSession->notes,
                    'session' => $activeSession,
                    'duration' => $activeSession->getFormattedDuration(),
                ];
            }

            if ($activeSession->isPaused()) {
                return [
                    'status' => 'away',
                    'label' => 'Taking a break',
                    'session' => $activeSession,
                ];
            }
        }

        // General presence
        return match ($presence['status']) {
            'working' => ['status' => 'busy', 'label' => 'Working on another project'],
            'online' => ['status' => 'online', 'label' => 'Online'],
            'away' => ['status' => 'away', 'label' => 'Away'],
            default => ['status' => 'offline', 'label' => 'Offline'],
        };
    }

    /**
     * Check if focus mode should block messages
     */
    public function shouldBlockMessage(Pitch $pitch, bool $isUrgent = false): bool
    {
        $producer = $pitch->user;
        if (! $producer) {
            return false;
        }

        $activeSession = $this->getActiveSession($pitch, $producer);

        if (! $activeSession || ! $activeSession->focus_mode) {
            return false;
        }

        // Urgent messages bypass focus mode
        return ! $isUrgent;
    }

    /**
     * Format duration in seconds to human-readable string
     */
    protected function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }

        if ($minutes > 0) {
            return sprintf('%dm', $minutes);
        }

        return 'Less than a minute';
    }
}

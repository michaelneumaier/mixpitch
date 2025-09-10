<?php

namespace App\Livewire;

use App\Models\PitchFile;
use Livewire\Component;

class FileComparisonPlayer extends Component
{
    public PitchFile $leftFile;

    public PitchFile $rightFile;

    public ?array $leftSnapshot = null;

    public ?array $rightSnapshot = null;

    public bool $syncPlayback = true;

    public string $comparisonMode = 'side-by-side';

    public function mount(PitchFile $leftFile, PitchFile $rightFile)
    {
        // Validate that both files belong to the same pitch
        if ($leftFile->pitch_id !== $rightFile->pitch_id) {
            throw new \InvalidArgumentException('Files must belong to the same pitch for comparison');
        }

        $this->leftFile = $leftFile;
        $this->rightFile = $rightFile;

        $this->loadSnapshots();
    }

    protected function loadSnapshots()
    {
        // Load snapshots for context
        $this->leftSnapshot = $this->leftFile->pitch->snapshots()
            ->whereJsonContains('snapshot_data->file_ids', $this->leftFile->id)
            ->first()?->toArray();

        $this->rightSnapshot = $this->rightFile->pitch->snapshots()
            ->whereJsonContains('snapshot_data->file_ids', $this->rightFile->id)
            ->first()?->toArray();
    }

    public function toggleSync()
    {
        $this->syncPlayback = ! $this->syncPlayback;
    }

    public function setComparisonMode(string $mode)
    {
        $allowedModes = ['side-by-side', 'overlay', 'sequential'];

        if (in_array($mode, $allowedModes)) {
            $this->comparisonMode = $mode;
        }
    }

    public function jumpToTimestamp(float $timestamp, string $target = 'both')
    {
        $eventData = ['timestamp' => $timestamp];

        switch ($target) {
            case 'left':
                $this->dispatch('seekToPosition', array_merge($eventData, ['player' => 'left']));
                break;
            case 'right':
                $this->dispatch('seekToPosition', array_merge($eventData, ['player' => 'right']));
                break;
            case 'both':
            default:
                $this->dispatch('seekToPosition', array_merge($eventData, ['player' => 'both']));
                break;
        }
    }

    public function onPlayerEvent(string $player, string $event, float $timestamp = 0)
    {
        // Handle synchronization events from the frontend
        if ($this->syncPlayback && $event === 'play') {
            $otherPlayer = $player === 'left' ? 'right' : 'left';
            $this->dispatch('syncPlay', ['target' => $otherPlayer, 'timestamp' => $timestamp]);
        }

        if ($this->syncPlayback && $event === 'pause') {
            $otherPlayer = $player === 'left' ? 'right' : 'left';
            $this->dispatch('syncPause', ['target' => $otherPlayer]);
        }

        if ($this->syncPlayback && $event === 'seek') {
            $otherPlayer = $player === 'left' ? 'right' : 'left';
            $this->dispatch('syncSeek', ['target' => $otherPlayer, 'timestamp' => $timestamp]);
        }
    }

    public function getFileMetadata(PitchFile $file): array
    {
        return [
            'duration' => $file->duration,
            'file_size' => $file->file_size,
            'created_at' => $file->created_at,
            'file_name' => $file->file_name,
            'file_type' => $file->file_type,
        ];
    }

    public function getFileDifferences(): array
    {
        $leftMeta = $this->getFileMetadata($this->leftFile);
        $rightMeta = $this->getFileMetadata($this->rightFile);

        $leftVersion = $this->leftSnapshot['snapshot_data']['version'] ?? 0;
        $rightVersion = $this->rightSnapshot['snapshot_data']['version'] ?? 0;

        return [
            'duration_diff' => $rightMeta['duration'] - $leftMeta['duration'],
            'size_diff' => ($rightMeta['file_size'] ?? 0) - ($leftMeta['file_size'] ?? 0),
            'version_diff' => $rightVersion - $leftVersion,
            'time_diff' => $rightMeta['created_at']->diffInMinutes($leftMeta['created_at']),
        ];
    }

    public function getLeftComments()
    {
        return $this->leftFile->comments()
            ->with(['user', 'replies.user'])
            ->whereNull('parent_id')
            ->orderBy('timestamp')
            ->get();
    }

    public function getRightComments()
    {
        return $this->rightFile->comments()
            ->with(['user', 'replies.user'])
            ->whereNull('parent_id')
            ->orderBy('timestamp')
            ->get();
    }

    public function getComparisonSummary(): array
    {
        $differences = $this->getFileDifferences();
        $leftComments = $this->getLeftComments();
        $rightComments = $this->getRightComments();

        return [
            'files_compared' => 2,
            'total_duration_change' => $differences['duration_diff'],
            'total_size_change' => $differences['size_diff'],
            'version_span' => abs($differences['version_diff']),
            'time_span_minutes' => $differences['time_diff'],
            'comment_changes' => [
                'left_comments' => $leftComments->count(),
                'right_comments' => $rightComments->count(),
                'total_comments' => $leftComments->count() + $rightComments->count(),
            ],
        ];
    }

    public function getCommentComparison(): array
    {
        $leftComments = $this->getLeftComments();
        $rightComments = $this->getRightComments();

        // Group comments by time intervals for comparison
        $leftGrouped = $leftComments->groupBy(function ($comment) {
            return floor($comment->timestamp / 30); // 30-second intervals
        });

        $rightGrouped = $rightComments->groupBy(function ($comment) {
            return floor($comment->timestamp / 30);
        });

        $allIntervals = collect($leftGrouped->keys())
            ->merge($rightGrouped->keys())
            ->unique()
            ->sort()
            ->values();

        return $allIntervals->map(function ($interval) use ($leftGrouped, $rightGrouped) {
            $startTime = $interval * 30;
            $endTime = ($interval + 1) * 30;

            return [
                'interval' => $interval,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'time_label' => gmdate('i:s', $startTime).' - '.gmdate('i:s', $endTime),
                'left_comments' => $leftGrouped->get($interval, collect())->toArray(),
                'right_comments' => $rightGrouped->get($interval, collect())->toArray(),
                'has_changes' => $leftGrouped->has($interval) !== $rightGrouped->has($interval),
            ];
        })->toArray();
    }

    /**
     * Play left file in the global audio player
     */
    public function playLeftInGlobalPlayer()
    {
        $this->dispatch('playPitchFile',
            pitchFileId: $this->leftFile->id,
            clientMode: false,
            clientEmail: ''
        );
    }

    /**
     * Play right file in the global audio player
     */
    public function playRightInGlobalPlayer()
    {
        $this->dispatch('playPitchFile',
            pitchFileId: $this->rightFile->id,
            clientMode: false,
            clientEmail: ''
        );
    }

    public function render()
    {
        return view('livewire.file-comparison-player', [
            'leftMetadata' => $this->getFileMetadata($this->leftFile),
            'rightMetadata' => $this->getFileMetadata($this->rightFile),
            'differences' => $this->getFileDifferences(),
            'summary' => $this->getComparisonSummary(),
            'commentComparison' => $this->getCommentComparison(),
        ]);
    }
}

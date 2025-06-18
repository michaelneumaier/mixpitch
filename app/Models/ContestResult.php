<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class ContestResult extends Model
{
    use HasFactory;

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

    /**
     * Get the project that owns this contest result
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the first place pitch
     */
    public function firstPlace(): BelongsTo
    {
        return $this->belongsTo(Pitch::class, 'first_place_pitch_id');
    }

    /**
     * Get the second place pitch
     */
    public function secondPlace(): BelongsTo
    {
        return $this->belongsTo(Pitch::class, 'second_place_pitch_id');
    }

    /**
     * Get the third place pitch
     */
    public function thirdPlace(): BelongsTo
    {
        return $this->belongsTo(Pitch::class, 'third_place_pitch_id');
    }

    /**
     * Get the user who finalized the judging
     */
    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /**
     * Get all runner-up pitches
     */
    public function runnerUps(): Collection
    {
        if (empty($this->runner_up_pitch_ids)) {
            return collect();
        }
        
        return Pitch::whereIn('id', $this->runner_up_pitch_ids)
            ->with('user')
            ->get();
    }

    /**
     * Check if a pitch has a placement and return the placement type
     */
    public function hasPlacement(int $pitchId): ?string
    {
        if ($this->first_place_pitch_id === $pitchId) return '1st';
        if ($this->second_place_pitch_id === $pitchId) return '2nd';
        if ($this->third_place_pitch_id === $pitchId) return '3rd';
        if (in_array($pitchId, $this->runner_up_pitch_ids ?? [])) return 'runner-up';
        return null;
    }

    /**
     * Check if the contest has been finalized
     */
    public function isFinalized(): bool
    {
        return !is_null($this->finalized_at);
    }

    /**
     * Get all placed pitch IDs
     */
    public function getPlacedPitchIds(): array
    {
        return array_filter([
            $this->first_place_pitch_id,
            $this->second_place_pitch_id,
            $this->third_place_pitch_id,
            ...($this->runner_up_pitch_ids ?? [])
        ]);
    }

    /**
     * Check if any winners have been selected
     */
    public function hasWinners(): bool
    {
        return $this->first_place_pitch_id || 
               $this->second_place_pitch_id || 
               $this->third_place_pitch_id || 
               !empty($this->runner_up_pitch_ids);
    }

    /**
     * Get the winner pitch for a specific placement
     */
    public function getWinnerForPlacement(string $placement): ?Pitch
    {
        switch ($placement) {
            case '1st':
                return $this->first_place_pitch_id ? $this->firstPlace : null;
            case '2nd':
                return $this->second_place_pitch_id ? $this->secondPlace : null;
            case '3rd':
                return $this->third_place_pitch_id ? $this->thirdPlace : null;
            case 'runner_up':
                // For runner-ups, return the first one (or could be modified to return all)
                $runnerUps = $this->runnerUps();
                return $runnerUps->first();
            default:
                return null;
        }
    }

    /**
     * Get the count of placed entries
     */
    public function getPlacedCount(): int
    {
        $count = 0;
        if ($this->first_place_pitch_id) $count++;
        if ($this->second_place_pitch_id) $count++;
        if ($this->third_place_pitch_id) $count++;
        if (!empty($this->runner_up_pitch_ids)) {
            $count += count($this->runner_up_pitch_ids);
        }
        return $count;
    }

    /**
     * Remove a pitch from all placements in this contest result
     */
    public function removePitchFromAllPlacements(int $pitchId): bool
    {
        $wasRemoved = false;
        
        // Check and remove from first place
        if ($this->first_place_pitch_id === $pitchId) {
            $this->first_place_pitch_id = null;
            $wasRemoved = true;
        }
        
        // Check and remove from second place
        if ($this->second_place_pitch_id === $pitchId) {
            $this->second_place_pitch_id = null;
            $wasRemoved = true;
        }
        
        // Check and remove from third place
        if ($this->third_place_pitch_id === $pitchId) {
            $this->third_place_pitch_id = null;
            $wasRemoved = true;
        }
        
        // Check and remove from runner-ups
        $runnerUpIds = $this->runner_up_pitch_ids ?? [];
        if (in_array($pitchId, $runnerUpIds)) {
            $this->runner_up_pitch_ids = array_values(array_filter($runnerUpIds, function($id) use ($pitchId) {
                return $id !== $pitchId;
            }));
            
            // Set to null if array is empty
            if (empty($this->runner_up_pitch_ids)) {
                $this->runner_up_pitch_ids = null;
            }
            
            $wasRemoved = true;
        }
        
        return $wasRemoved;
    }

    /**
     * Remove a specific pitch from runner-up placements
     */
    public function removeFromRunnerUps(int $pitchId): bool
    {
        $runnerUpIds = $this->runner_up_pitch_ids ?? [];
        
        if (!in_array($pitchId, $runnerUpIds)) {
            return false;
        }
        
        $this->runner_up_pitch_ids = array_values(array_filter($runnerUpIds, function($id) use ($pitchId) {
            return $id !== $pitchId;
        }));
        
        // Set to null if array is empty
        if (empty($this->runner_up_pitch_ids)) {
            $this->runner_up_pitch_ids = null;
        }
        
        return true;
    }

    /**
     * Check if this contest result has any orphaned pitch references
     */
    public function hasOrphanedPitches(): array
    {
        $orphaned = [];
        
        // Check individual placements
        if ($this->first_place_pitch_id && !Pitch::find($this->first_place_pitch_id)) {
            $orphaned['first_place'] = $this->first_place_pitch_id;
        }
        
        if ($this->second_place_pitch_id && !Pitch::find($this->second_place_pitch_id)) {
            $orphaned['second_place'] = $this->second_place_pitch_id;
        }
        
        if ($this->third_place_pitch_id && !Pitch::find($this->third_place_pitch_id)) {
            $orphaned['third_place'] = $this->third_place_pitch_id;
        }
        
        // Check runner-ups
        $runnerUpIds = $this->runner_up_pitch_ids ?? [];
        if (!empty($runnerUpIds)) {
            $validIds = Pitch::whereIn('id', $runnerUpIds)->pluck('id')->toArray();
            $orphanedRunnerUps = array_diff($runnerUpIds, $validIds);
            
            if (!empty($orphanedRunnerUps)) {
                $orphaned['runner_ups'] = $orphanedRunnerUps;
            }
        }
        
        return $orphaned;
    }

    /**
     * Clean up all orphaned pitch references in this contest result
     */
    public function cleanupOrphanedPitches(): array
    {
        $orphaned = $this->hasOrphanedPitches();
        $cleaned = [];
        
        if (empty($orphaned)) {
            return $cleaned;
        }
        
        // Clean first place
        if (isset($orphaned['first_place'])) {
            $cleaned['first_place'] = $this->first_place_pitch_id;
            $this->first_place_pitch_id = null;
        }
        
        // Clean second place
        if (isset($orphaned['second_place'])) {
            $cleaned['second_place'] = $this->second_place_pitch_id;
            $this->second_place_pitch_id = null;
        }
        
        // Clean third place
        if (isset($orphaned['third_place'])) {
            $cleaned['third_place'] = $this->third_place_pitch_id;
            $this->third_place_pitch_id = null;
        }
        
        // Clean runner-ups
        if (isset($orphaned['runner_ups'])) {
            $runnerUpIds = $this->runner_up_pitch_ids ?? [];
            $validIds = Pitch::whereIn('id', $runnerUpIds)->pluck('id')->toArray();
            
            $cleaned['runner_ups'] = $orphaned['runner_ups'];
            $this->runner_up_pitch_ids = empty($validIds) ? null : array_values($validIds);
        }
        
        return $cleaned;
    }

    /**
     * Get a summary of this contest's current state
     */
    public function getContestSummary(): array
    {
        $placedPitchIds = $this->getPlacedPitchIds();
        $totalEntries = $this->project ? $this->project->getContestEntries()->count() : 0;
        $orphaned = $this->hasOrphanedPitches();
        
        return [
            'total_entries' => $totalEntries,
            'placed_entries' => count($placedPitchIds),
            'unplaced_entries' => max(0, $totalEntries - count($placedPitchIds)),
            'is_finalized' => $this->isFinalized(),
            'has_winners' => $this->hasWinners(),
            'has_orphaned_references' => !empty($orphaned),
            'orphaned_references' => $orphaned,
            'placements' => [
                'first_place' => $this->first_place_pitch_id,
                'second_place' => $this->second_place_pitch_id,
                'third_place' => $this->third_place_pitch_id,
                'runner_ups' => $this->runner_up_pitch_ids ?? []
            ]
        ];
    }
}

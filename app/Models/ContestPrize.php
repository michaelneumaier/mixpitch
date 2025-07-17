<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContestPrize extends Model
{
    use HasFactory;

    // Placement constants
    const PLACEMENT_FIRST = '1st';

    const PLACEMENT_SECOND = '2nd';

    const PLACEMENT_THIRD = '3rd';

    const PLACEMENT_RUNNER_UP = 'runner_up';

    // Prize type constants
    const TYPE_CASH = 'cash';

    const TYPE_OTHER = 'other';

    protected $fillable = [
        'project_id',
        'placement',
        'prize_type',
        'cash_amount',
        'currency',
        'prize_title',
        'prize_description',
        'prize_value_estimate',
    ];

    protected $casts = [
        'cash_amount' => 'decimal:2',
        'prize_value_estimate' => 'decimal:2',
    ];

    /**
     * Mutator for cash_amount to handle empty strings
     */
    public function setCashAmountAttribute($value)
    {
        // Handle various empty states and non-numeric values
        if (empty($value) || ! is_numeric($value)) {
            $this->attributes['cash_amount'] = null;
        } else {
            $this->attributes['cash_amount'] = $value;
        }
    }

    /**
     * Mutator for prize_value_estimate to handle empty strings
     */
    public function setPrizeValueEstimateAttribute($value)
    {
        // Handle various empty states and non-numeric values
        if (empty($value) || ! is_numeric($value)) {
            $this->attributes['prize_value_estimate'] = null;
        } else {
            $this->attributes['prize_value_estimate'] = $value;
        }
    }

    /**
     * Get the project that owns this prize
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Check if this is a cash prize
     */
    public function isCashPrize(): bool
    {
        return $this->prize_type === self::TYPE_CASH;
    }

    /**
     * Check if this is an other type prize
     */
    public function isOtherPrize(): bool
    {
        return $this->prize_type === self::TYPE_OTHER;
    }

    /**
     * Get the display value of this prize
     */
    public function getDisplayValue(): string
    {
        if ($this->isCashPrize()) {
            if (! empty($this->cash_amount) && is_numeric($this->cash_amount)) {
                return $this->currency.' '.number_format($this->cash_amount, 2);
            }

            return $this->currency.' 0.00';
        }

        return $this->prize_title ?: 'Other Prize';
    }

    /**
     * Get the cash value for budget calculations
     */
    public function getCashValue(): float
    {
        try {
            return $this->isCashPrize() && ! empty($this->cash_amount) && is_numeric($this->cash_amount) ? (float) $this->cash_amount : 0.0;
        } catch (Exception $e) {
            \Log::warning('ContestPrize getCashValue error: '.$e->getMessage(), ['prize_id' => $this->id]);

            return 0.0;
        }
    }

    /**
     * Get the estimated value (cash amount for cash prizes, estimate for others)
     */
    public function getEstimatedValue(): float
    {
        try {
            if ($this->isCashPrize()) {
                return ! empty($this->cash_amount) && is_numeric($this->cash_amount) ? (float) $this->cash_amount : 0.0;
            }

            return ! empty($this->prize_value_estimate) && is_numeric($this->prize_value_estimate) ? (float) $this->prize_value_estimate : 0.0;
        } catch (Exception $e) {
            \Log::warning('ContestPrize getEstimatedValue error: '.$e->getMessage(), ['prize_id' => $this->id]);

            return 0.0;
        }
    }

    /**
     * Get placement display name
     */
    public function getPlacementDisplayName(): string
    {
        return match ($this->placement) {
            self::PLACEMENT_FIRST => '1st Place',
            self::PLACEMENT_SECOND => '2nd Place',
            self::PLACEMENT_THIRD => '3rd Place',
            self::PLACEMENT_RUNNER_UP => 'Runner-up',
            default => $this->placement
        };
    }

    /**
     * Get placement emoji
     */
    public function getPlacementEmoji(): string
    {
        return match ($this->placement) {
            self::PLACEMENT_FIRST => '🥇',
            self::PLACEMENT_SECOND => '🥈',
            self::PLACEMENT_THIRD => '🥉',
            self::PLACEMENT_RUNNER_UP => '🏅',
            default => '🎖️'
        };
    }

    /**
     * Scope to filter by placement
     */
    public function scopeForPlacement($query, string $placement)
    {
        return $query->where('placement', $placement);
    }

    /**
     * Scope to filter by prize type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('prize_type', $type);
    }

    /**
     * Scope for cash prizes only
     */
    public function scopeCashPrizes($query)
    {
        return $query->where('prize_type', self::TYPE_CASH);
    }

    /**
     * Scope for other prizes only
     */
    public function scopeOtherPrizes($query)
    {
        return $query->where('prize_type', self::TYPE_OTHER);
    }

    /**
     * Get all available placement options
     */
    public static function getAvailablePlacements(): array
    {
        return [
            self::PLACEMENT_FIRST => '1st Place',
            self::PLACEMENT_SECOND => '2nd Place',
            self::PLACEMENT_THIRD => '3rd Place',
            self::PLACEMENT_RUNNER_UP => 'Runner-ups',
        ];
    }

    /**
     * Get all available prize types
     */
    public static function getAvailablePrizeTypes(): array
    {
        return [
            self::TYPE_CASH => 'Cash Prize',
            self::TYPE_OTHER => 'Other Prize',
        ];
    }
}

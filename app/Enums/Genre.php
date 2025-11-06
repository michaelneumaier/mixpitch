<?php

namespace App\Enums;

enum Genre: string
{
    case POP = 'Pop';
    case ROCK = 'Rock';
    case HIP_HOP = 'Hip Hop';
    case ELECTRONIC = 'Electronic';
    case RNB = 'R&B';
    case COUNTRY = 'Country';
    case JAZZ = 'Jazz';
    case CLASSICAL = 'Classical';
    case METAL = 'Metal';
    case BLUES = 'Blues';
    case FOLK = 'Folk';
    case FUNK = 'Funk';
    case REGGAE = 'Reggae';
    case SOUL = 'Soul';
    case PUNK = 'Punk';

    /**
     * Get all genre values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all genres as a comma-separated string for validation
     */
    public static function validationString(): string
    {
        return implode(',', self::values());
    }

    /**
     * Get genre label (same as value for display)
     */
    public function label(): string
    {
        return $this->value;
    }
}

<?php

namespace App\Enums;

enum ProductionDay: string
{
    case SATURDAY = 'saturday';
    case SUNDAY = 'sunday';

    public function label(): string
    {
        return match($this) {
            self::SATURDAY => 'Saturday',
            self::SUNDAY => 'Sunday',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the Carbon day constant for this production day.
     */
    public function toCarbonDay(): int
    {
        return match($this) {
            self::SATURDAY => \Carbon\Carbon::SATURDAY,
            self::SUNDAY => \Carbon\Carbon::SUNDAY,
        };
    }

    /**
     * Check if a given Carbon date matches this production day.
     */
    public function matchesDate(\Carbon\Carbon $date): bool
    {
        return $date->dayOfWeek === $this->toCarbonDay();
    }

    /**
     * Get the next occurrence of this production day.
     */
    public function nextOccurrence(\Carbon\Carbon $from = null): \Carbon\Carbon
    {
        $from = $from ?: \Carbon\Carbon::now();
        return $from->next($this->toCarbonDay());
    }
}
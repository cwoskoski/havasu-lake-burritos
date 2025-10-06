<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case IN_PREPARATION = 'in_preparation';
    case READY = 'ready';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::IN_PREPARATION => 'In Preparation',
            self::READY => 'Ready for Pickup',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function canTransitionTo(OrderStatus $status): bool
    {
        return match($this) {
            self::PENDING => in_array($status, [self::CONFIRMED, self::CANCELLED]),
            self::CONFIRMED => in_array($status, [self::IN_PREPARATION, self::CANCELLED]),
            self::IN_PREPARATION => in_array($status, [self::READY, self::CANCELLED]),
            self::READY => in_array($status, [self::COMPLETED]),
            self::COMPLETED => false,
            self::CANCELLED => false,
        };
    }
}
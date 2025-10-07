<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductionDay;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class ProductionSchedule extends Model
{
    use HasFactory;

    public const DEFAULT_MAX_CAPACITY = 100;

    public const DEFAULT_CUTOFF_TIME = '22:00:00';

    protected $fillable = [
        'production_date',
        'day_of_week',
        'max_burritos',
        'burritos_ordered',
        'order_cutoff_time',
        'pickup_start_time',
        'pickup_end_time',
        'is_active',
        'special_notes',
    ];

    protected $casts = [
        'production_date' => 'date',
        'day_of_week' => ProductionDay::class,
        'max_burritos' => 'integer',
        'burritos_ordered' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'burritos_ordered' => 0,
        'order_cutoff_time' => self::DEFAULT_CUTOFF_TIME,
        'pickup_start_time' => '11:00:00',
        'pickup_end_time' => '16:00:00',
        'is_active' => true,
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // === VALIDATION METHODS ===

    /**
     * Check if a given date is valid for production (weekend only).
     */
    public static function isValidProductionDate(Carbon $date): bool
    {
        return $date->isWeekend();
    }

    /**
     * Validate production schedule before saving.
     */
    protected static function booted(): void
    {
        static::creating(function (ProductionSchedule $schedule) {
            $schedule->validateProductionRules();
        });

        static::updating(function (ProductionSchedule $schedule) {
            $schedule->validateProductionRules();
        });
    }

    /**
     * Validate business rules for production scheduling.
     */
    private function validateProductionRules(): void
    {
        // Ensure production date is a weekend
        if (! self::isValidProductionDate($this->production_date)) {
            throw new InvalidArgumentException('Production can only be scheduled for weekends');
        }

        // Ensure production day enum matches the actual date
        if (! $this->day_of_week->matchesDate($this->production_date)) {
            throw new InvalidArgumentException(
                "Production day {$this->day_of_week->value} does not match date {$this->production_date->format('l')}"
            );
        }

        // Validate capacity limits
        if ($this->max_burritos < 1 || $this->max_burritos > 500) {
            throw new InvalidArgumentException('Max burritos must be between 1 and 500');
        }

        if ($this->burritos_ordered < 0 || $this->burritos_ordered > $this->max_burritos) {
            throw new InvalidArgumentException('Ordered burritos cannot exceed max capacity');
        }
    }

    // === CAPACITY MANAGEMENT ===

    /**
     * Get available capacity for new orders.
     */
    public function getAvailableCapacity(): int
    {
        return max(0, $this->max_burritos - $this->burritos_ordered);
    }

    /**
     * Check if the schedule can accept an order for the given quantity.
     */
    public function canAcceptOrder(int $quantity): bool
    {
        return $this->getAvailableCapacity() >= $quantity;
    }

    /**
     * Reserve capacity for an order.
     */
    public function reserveCapacity(int $quantity): bool
    {
        if (! $this->canAcceptOrder($quantity)) {
            return false;
        }

        $this->burritos_ordered += $quantity;
        $this->save();

        return true;
    }

    /**
     * Release reserved capacity (e.g., when order is cancelled).
     */
    public function releaseCapacity(int $quantity): void
    {
        $this->burritos_ordered = max(0, $this->burritos_ordered - $quantity);
        $this->save();
    }

    // === CUTOFF TIME MANAGEMENT ===

    /**
     * Check if current time is within ordering window.
     */
    public function isWithinOrderingWindow(): bool
    {
        $now = Carbon::now();
        $cutoffDateTime = $this->production_date->copy()->setTimeFromTimeString($this->order_cutoff_time);

        return $now->lessThan($cutoffDateTime);
    }

    /**
     * Check if schedule can accept new orders (combines capacity and time checks).
     */
    public function canAcceptNewOrders(): bool
    {
        return $this->is_active &&
               $this->isWithinOrderingWindow() &&
               $this->getAvailableCapacity() > 0;
    }

    /**
     * Get time remaining until cutoff.
     */
    public function getTimeUntilCutoff(): Carbon
    {
        return $this->production_date->copy()->setTimeFromTimeString($this->order_cutoff_time);
    }

    /**
     * Get cutoff status with detailed information.
     */
    public function getCutoffStatus(): array
    {
        $acceptingOrders = $this->isWithinOrderingWindow();

        return [
            'accepting_orders' => $acceptingOrders,
            'cutoff_time' => $this->order_cutoff_time,
            'time_until_cutoff' => $acceptingOrders ? $this->getTimeUntilCutoff() : null,
            'reason' => $acceptingOrders ? null : 'Orders have closed for this production day (past cutoff time)',
        ];
    }

    // === SCHEDULE GENERATION ===

    /**
     * Generate production schedules for the current weekend.
     */
    public static function generateWeekendSchedules(): array
    {
        $schedules = [];
        $now = Carbon::now();

        // Find next Saturday and Sunday
        $saturday = $now->next(Carbon::SATURDAY);
        $sunday = $saturday->copy()->addDay();

        $schedules[] = new self([
            'production_date' => $saturday,
            'day_of_week' => ProductionDay::SATURDAY,
            'max_burritos' => self::DEFAULT_MAX_CAPACITY,
            'order_cutoff_time' => self::DEFAULT_CUTOFF_TIME,
        ]);

        $schedules[] = new self([
            'production_date' => $sunday,
            'day_of_week' => ProductionDay::SUNDAY,
            'max_burritos' => self::DEFAULT_MAX_CAPACITY,
            'order_cutoff_time' => self::DEFAULT_CUTOFF_TIME,
        ]);

        return $schedules;
    }

    /**
     * Generate schedules for multiple weeks.
     */
    public static function generateWeeklySchedules(int $weeks): array
    {
        $schedules = [];

        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = Carbon::now()->addWeeks($i);
            $saturday = $weekStart->next(Carbon::SATURDAY);
            $sunday = $saturday->copy()->addDay();

            $schedules[] = new self([
                'production_date' => $saturday,
                'day_of_week' => ProductionDay::SATURDAY,
                'max_burritos' => self::DEFAULT_MAX_CAPACITY,
                'order_cutoff_time' => self::DEFAULT_CUTOFF_TIME,
            ]);

            $schedules[] = new self([
                'production_date' => $sunday,
                'day_of_week' => ProductionDay::SUNDAY,
                'max_burritos' => self::DEFAULT_MAX_CAPACITY,
                'order_cutoff_time' => self::DEFAULT_CUTOFF_TIME,
            ]);
        }

        return $schedules;
    }

    // === PRODUCTION STATISTICS ===

    /**
     * Get production statistics for analytics.
     */
    public function getProductionStats(): array
    {
        $capacityPercentage = ($this->burritos_ordered / $this->max_burritos) * 100;

        return [
            'total_capacity' => $this->max_burritos,
            'reserved_capacity' => $this->burritos_ordered,
            'remaining_capacity' => $this->getAvailableCapacity(),
            'capacity_percentage' => round($capacityPercentage, 1),
            'is_near_capacity' => $capacityPercentage >= 70.0,
            'is_sold_out' => $this->burritos_ordered >= $this->max_burritos,
        ];
    }

    /**
     * Get mobile-optimized capacity display information.
     */
    public function getMobileCapacityDisplay(): array
    {
        $stats = $this->getProductionStats();
        $remaining = $stats['remaining_capacity'];

        if ($stats['is_sold_out']) {
            return [
                'status' => 'sold_out',
                'message' => 'Sold out for this day',
                'urgency_level' => 'critical',
                'show_waitlist' => true,
            ];
        }

        $urgencyLevel = match (true) {
            $stats['capacity_percentage'] >= 95 => 'critical',
            $stats['capacity_percentage'] >= 80 => 'high', // 90% = high urgency
            $stats['capacity_percentage'] >= 50 => 'medium',
            default => 'low',
        };

        $message = match (true) {
            $remaining <= 5 => "Only {$remaining} left!",
            $remaining <= 20 => "Only {$remaining} burritos available",
            default => "{$remaining} burritos available",
        };

        return [
            'status' => 'available',
            'message' => $message,
            'urgency_level' => $urgencyLevel,
            'remaining_count' => $remaining,
            'show_waitlist' => false,
        ];
    }

    // === QUERY SCOPES ===

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForDate(Builder $query, Carbon $date): Builder
    {
        return $query->where('production_date', $date->format('Y-m-d'));
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('production_date', '>=', Carbon::now()->format('Y-m-d'));
    }

    public function scopeAcceptingOrders(Builder $query): Builder
    {
        return $query->active()
            ->upcoming()
            ->where('burritos_ordered', '<', 'max_burritos');
    }
}

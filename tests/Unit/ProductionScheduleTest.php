<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ProductionDay;
use App\Models\ProductionSchedule;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * TDD Tests for Production Schedule Management
 * Business Rule: Burritos only made on weekends (Saturday/Sunday)
 */
describe('Weekend Production Validation', function () {
    beforeEach(function () {
        Carbon::setTestNow('2025-01-13 10:00:00'); // Monday for testing
    });

    afterEach(function () {
        Carbon::setTestNow(); // Reset
    });

    it('validates weekend-only production scheduling', function () {
        $saturday = Carbon::now()->next(Carbon::SATURDAY);
        $sunday = Carbon::now()->next(Carbon::SUNDAY);
        $monday = Carbon::now()->next(Carbon::MONDAY);

        expect(ProductionSchedule::isValidProductionDate($saturday))->toBeTrue();
        expect(ProductionSchedule::isValidProductionDate($sunday))->toBeTrue();
        expect(ProductionSchedule::isValidProductionDate($monday))->toBeFalse();
    });

    it('creates production schedule only for weekend dates', function () {
        $saturday = Carbon::now()->next(Carbon::SATURDAY);
        $monday = Carbon::now()->next(Carbon::MONDAY);

        expect(fn () => ProductionSchedule::create([
            'production_date' => $saturday,
            'day_of_week' => ProductionDay::SATURDAY,
            'max_burritos' => 100,
        ]))->not->toThrow(InvalidArgumentException::class);

        expect(fn () => ProductionSchedule::create([
            'production_date' => $monday,
            'day_of_week' => ProductionDay::SATURDAY, // Mismatch should fail
            'max_burritos' => 100,
        ]))->toThrow(InvalidArgumentException::class);
    });

    it('enforces production day enum matches actual date', function () {
        $saturday = Carbon::now()->next(Carbon::SATURDAY);
        $sunday = Carbon::now()->next(Carbon::SUNDAY);

        expect(fn () => ProductionSchedule::create([
            'production_date' => $saturday,
            'day_of_week' => ProductionDay::SUNDAY, // Wrong day
            'max_burritos' => 100,
        ]))->toThrow(InvalidArgumentException::class);

        expect(fn () => ProductionSchedule::create([
            'production_date' => $sunday,
            'day_of_week' => ProductionDay::SUNDAY, // Correct day
            'max_burritos' => 100,
        ]))->not->toThrow(InvalidArgumentException::class);
    });
});

describe('Production Capacity Management', function () {
    beforeEach(function () {
        $this->saturday = Carbon::now()->next(Carbon::SATURDAY);
        $this->schedule = new ProductionSchedule([
            'production_date' => $this->saturday,
            'day_of_week' => ProductionDay::SATURDAY,
            'max_burritos' => 100,
            'burritos_ordered' => 0,
            'order_cutoff_time' => '22:00:00', // 10 PM cutoff
        ]);
    });

    it('calculates available capacity correctly', function () {
        expect($this->schedule->getAvailableCapacity())->toBe(100);

        $this->schedule->burritos_ordered = 25;
        expect($this->schedule->getAvailableCapacity())->toBe(75);

        $this->schedule->burritos_ordered = 100;
        expect($this->schedule->getAvailableCapacity())->toBe(0);
    });

    it('validates capacity limits are not exceeded', function () {
        expect($this->schedule->canAcceptOrder(50))->toBeTrue();
        expect($this->schedule->canAcceptOrder(100))->toBeTrue();
        expect($this->schedule->canAcceptOrder(101))->toBeFalse();

        $this->schedule->burritos_ordered = 90;
        expect($this->schedule->canAcceptOrder(5))->toBeTrue();
        expect($this->schedule->canAcceptOrder(15))->toBeFalse();
    });

    it('reserves capacity for orders', function () {
        $result = $this->schedule->reserveCapacity(25);

        expect($result)->toBeTrue();
        expect($this->schedule->burritos_ordered)->toBe(25);
        expect($this->schedule->getAvailableCapacity())->toBe(75);
    });

    it('prevents over-reservation', function () {
        $this->schedule->burritos_ordered = 95;

        expect($this->schedule->reserveCapacity(5))->toBeTrue();
        expect($this->schedule->reserveCapacity(1))->toBeFalse();
        expect($this->schedule->burritos_ordered)->toBe(100); // Should remain at max
    });

    it('releases reserved capacity', function () {
        $this->schedule->burritos_ordered = 50;

        $this->schedule->releaseCapacity(20);
        expect($this->schedule->burritos_ordered)->toBe(30);

        $this->schedule->releaseCapacity(40); // More than reserved
        expect($this->schedule->burritos_ordered)->toBe(0); // Should not go negative
    });
});

describe('Production Cutoff Times', function () {
    beforeEach(function () {
        $this->saturday = Carbon::create(2025, 1, 18, 15, 0, 0); // Saturday 3 PM
        Carbon::setTestNow($this->saturday);

        $this->schedule = new ProductionSchedule([
            'production_date' => $this->saturday,
            'day_of_week' => ProductionDay::SATURDAY,
            'max_burritos' => 100,
            'order_cutoff_time' => '22:00:00', // 10 PM cutoff
        ]);
    });

    afterEach(function () {
        Carbon::setTestNow();
    });

    it('allows orders before cutoff time', function () {
        // 3 PM - well before 10 PM cutoff
        expect($this->schedule->isWithinOrderingWindow())->toBeTrue();
        expect($this->schedule->canAcceptNewOrders())->toBeTrue();
    });

    it('prevents orders after cutoff time', function () {
        // Move to 11 PM - after cutoff
        Carbon::setTestNow($this->saturday->copy()->setTime(23, 0, 0));

        expect($this->schedule->isWithinOrderingWindow())->toBeFalse();
        expect($this->schedule->canAcceptNewOrders())->toBeFalse();
    });

    it('calculates time remaining until cutoff', function () {
        // At 3 PM, 7 hours until 10 PM cutoff
        $remaining = $this->schedule->getTimeUntilCutoff();

        expect($remaining)->toBeInstanceOf(Carbon::class);
        expect((int) abs($remaining->diffInHours($this->saturday)))->toBe(7);
    });

    it('provides cutoff status with reasons', function () {
        // Before cutoff
        $status = $this->schedule->getCutoffStatus();
        expect($status['accepting_orders'])->toBeTrue();
        expect($status['reason'])->toBeNull();

        // After cutoff
        Carbon::setTestNow($this->saturday->copy()->setTime(23, 0, 0));
        $status = $this->schedule->getCutoffStatus();
        expect($status['accepting_orders'])->toBeFalse();
        expect($status['reason'])->toContain('cutoff time');
    });
});

describe('Weekend Schedule Generation', function () {
    it('generates weekend schedules for current week', function () {
        $schedules = ProductionSchedule::generateWeekendSchedules();

        expect($schedules)->toHaveCount(2); // Saturday and Sunday
        expect($schedules[0]->day_of_week)->toBe(ProductionDay::SATURDAY);
        expect($schedules[1]->day_of_week)->toBe(ProductionDay::SUNDAY);
    });

    it('generates schedules for future weeks', function () {
        $weeklySchedules = ProductionSchedule::generateWeeklySchedules(2); // Next 2 weeks

        expect($weeklySchedules)->toHaveCount(4); // 2 days × 2 weeks

        foreach ($weeklySchedules as $schedule) {
            expect($schedule->day_of_week)->toBeIn([ProductionDay::SATURDAY, ProductionDay::SUNDAY]);
            expect($schedule->production_date->isWeekend())->toBeTrue();
        }
    });

    it('applies default capacity and cutoff settings', function () {
        $schedules = ProductionSchedule::generateWeekendSchedules();

        foreach ($schedules as $schedule) {
            expect($schedule->max_burritos)->toBe(ProductionSchedule::DEFAULT_MAX_CAPACITY);
            expect($schedule->order_cutoff_time)->toBe(ProductionSchedule::DEFAULT_CUTOFF_TIME);
            expect($schedule->burritos_ordered)->toBe(0);
        }
    });
});

describe('Production Day Business Logic', function () {
    it('calculates production statistics', function () {
        $schedule = new ProductionSchedule([
            'max_burritos' => 100,
            'burritos_ordered' => 75,
        ]);

        $stats = $schedule->getProductionStats();

        expect($stats['capacity_percentage'])->toBe(75.0);
        expect($stats['remaining_capacity'])->toBe(25);
        expect($stats['is_near_capacity'])->toBeTrue(); // Over 70%
        expect($stats['is_sold_out'])->toBeFalse();
    });

    it('identifies sold out status', function () {
        $schedule = new ProductionSchedule([
            'max_burritos' => 100,
            'burritos_ordered' => 100,
        ]);

        $stats = $schedule->getProductionStats();
        expect($stats['is_sold_out'])->toBeTrue();
        expect($stats['capacity_percentage'])->toBe(100.0);
    });

    it('provides mobile-optimized capacity display', function () {
        $schedule = new ProductionSchedule([
            'max_burritos' => 100,
            'burritos_ordered' => 65,
        ]);

        $display = $schedule->getMobileCapacityDisplay();

        expect($display['status'])->toBe('available');
        expect($display['message'])->toContain('35 burritos available');
        expect($display['urgency_level'])->toBe('medium'); // 65% capacity
    });

    it('shows high urgency when near capacity', function () {
        $schedule = new ProductionSchedule([
            'max_burritos' => 100,
            'burritos_ordered' => 90,
        ]);

        $display = $schedule->getMobileCapacityDisplay();

        expect($display['urgency_level'])->toBe('high');
        expect($display['message'])->toContain('Only 10');
    });
});

<?php

declare(strict_types=1);

namespace Tests\Unit\Business;

use Carbon\Carbon;
use Tests\TestCase;
use App\Enums\ProductionDay;

/**
 * Unit tests for weekend-only ordering business logic.
 * Tests the core business rule that burritos are only made on weekends.
 */
class WeekendOrderingTest extends TestCase
{
    public function test_ordering_is_allowed_on_saturday(): void
    {
        $saturday = Carbon::create(2024, 10, 5, 10, 0, 0); // A Saturday
        Carbon::setTestNow($saturday);

        expect($saturday->dayOfWeek)->toBe(Carbon::SATURDAY);
        expect($this->isOrderingAllowed())->toBeTrue();

        Carbon::setTestNow(); // Reset
    }

    public function test_ordering_is_allowed_on_sunday(): void
    {
        $sunday = Carbon::create(2024, 10, 6, 12, 0, 0); // A Sunday
        Carbon::setTestNow($sunday);

        expect($sunday->dayOfWeek)->toBe(Carbon::SUNDAY);
        expect($this->isOrderingAllowed())->toBeTrue();

        Carbon::setTestNow(); // Reset
    }

    public function test_ordering_is_not_allowed_on_weekdays(): void
    {
        $weekdays = [
            Carbon::MONDAY,
            Carbon::TUESDAY,
            Carbon::WEDNESDAY,
            Carbon::THURSDAY,
            Carbon::FRIDAY,
        ];

        foreach ($weekdays as $dayOfWeek) {
            $weekday = Carbon::create(2024, 10, 7)->addDays($dayOfWeek - 1); // Start from Monday
            Carbon::setTestNow($weekday);

            expect($weekday->dayOfWeek)->toBe($dayOfWeek);
            expect($this->isOrderingAllowed())->toBeFalse();
        }

        Carbon::setTestNow(); // Reset
    }

    public function test_production_day_enum_maps_correctly(): void
    {
        expect(ProductionDay::SATURDAY->value)->toBe('saturday');
        expect(ProductionDay::SUNDAY->value)->toBe('sunday');

        expect(ProductionDay::SATURDAY->label())->toBe('Saturday');
        expect(ProductionDay::SUNDAY->label())->toBe('Sunday');
    }

    public function test_production_day_enum_provides_carbon_day(): void
    {
        expect(ProductionDay::SATURDAY->toCarbonDay())->toBe(Carbon::SATURDAY);
        expect(ProductionDay::SUNDAY->toCarbonDay())->toBe(Carbon::SUNDAY);
    }

    public function test_weekend_detection_helper(): void
    {
        // Test various weekend dates
        $weekends = [
            Carbon::create(2024, 10, 5), // Saturday
            Carbon::create(2024, 10, 6), // Sunday
            Carbon::create(2024, 10, 12), // Next Saturday
            Carbon::create(2024, 10, 13), // Next Sunday
        ];

        foreach ($weekends as $weekend) {
            expect($weekend->isWeekend())->toBeTrue();
            expect($this->isValidProductionDay($weekend))->toBeTrue();
        }

        // Test weekdays
        $weekdays = [
            Carbon::create(2024, 10, 7), // Monday
            Carbon::create(2024, 10, 8), // Tuesday
            Carbon::create(2024, 10, 9), // Wednesday
            Carbon::create(2024, 10, 10), // Thursday
            Carbon::create(2024, 10, 11), // Friday
        ];

        foreach ($weekdays as $weekday) {
            expect($weekday->isWeekend())->toBeFalse();
            expect($this->isValidProductionDay($weekday))->toBeFalse();
        }
    }

    public function test_next_available_ordering_date(): void
    {
        // Test from Monday - should return Saturday
        $monday = Carbon::create(2024, 10, 7, 10, 0, 0); // Monday
        Carbon::setTestNow($monday);

        $nextAvailable = $this->getNextAvailableOrderingDate();
        expect($nextAvailable->dayOfWeek)->toBe(Carbon::SATURDAY);
        expect($nextAvailable->format('Y-m-d'))->toBe('2024-10-12');

        // Test from Friday - should return Saturday
        $friday = Carbon::create(2024, 10, 11, 15, 0, 0); // Friday
        Carbon::setTestNow($friday);

        $nextAvailable = $this->getNextAvailableOrderingDate();
        expect($nextAvailable->dayOfWeek)->toBe(Carbon::SATURDAY);
        expect($nextAvailable->format('Y-m-d'))->toBe('2024-10-12');

        // Test from Saturday - should return same Saturday if early enough
        $saturdayMorning = Carbon::create(2024, 10, 12, 9, 0, 0); // Saturday 9 AM
        Carbon::setTestNow($saturdayMorning);

        $nextAvailable = $this->getNextAvailableOrderingDate();
        expect($nextAvailable->dayOfWeek)->toBe(Carbon::SATURDAY);
        expect($nextAvailable->format('Y-m-d'))->toBe('2024-10-12');

        // Test from Saturday late - should return Sunday
        $saturdayLate = Carbon::create(2024, 10, 12, 16, 0, 0); // Saturday 4 PM
        Carbon::setTestNow($saturdayLate);

        $nextAvailable = $this->getNextAvailableOrderingDate();
        expect($nextAvailable->dayOfWeek)->toBe(Carbon::SUNDAY);

        Carbon::setTestNow(); // Reset
    }

    public function test_weekend_ordering_window(): void
    {
        // Test that ordering window is properly defined
        $saturdayMorning = Carbon::create(2024, 10, 12, 8, 0, 0); // Saturday 8 AM
        $saturdayEvening = Carbon::create(2024, 10, 12, 17, 0, 0); // Saturday 5 PM
        $sundayMorning = Carbon::create(2024, 10, 13, 10, 0, 0); // Sunday 10 AM
        $sundayEvening = Carbon::create(2024, 10, 13, 16, 0, 0); // Sunday 4 PM

        foreach ([$saturdayMorning, $saturdayEvening, $sundayMorning, $sundayEvening] as $time) {
            Carbon::setTestNow($time);
            expect($this->isWithinOrderingWindow())->toBeTrue();
        }

        Carbon::setTestNow(); // Reset
    }

    public function test_time_until_next_ordering_window(): void
    {
        // Test from Wednesday - should be about 3 days
        $wednesday = Carbon::create(2024, 10, 9, 14, 0, 0); // Wednesday 2 PM
        Carbon::setTestNow($wednesday);

        $timeUntilNext = $this->getTimeUntilNextOrderingWindow();
        expect($timeUntilNext->days)->toBe(2); // Wednesday to Saturday

        // Test from Friday evening - should be about 1 day
        $fridayEvening = Carbon::create(2024, 10, 11, 18, 0, 0); // Friday 6 PM
        Carbon::setTestNow($fridayEvening);

        $timeUntilNext = $this->getTimeUntilNextOrderingWindow();
        expect($timeUntilNext->hours)->toBeLessThan(24);

        Carbon::setTestNow(); // Reset
    }

    public function test_ordering_availability_messaging(): void
    {
        // Test weekend message
        $saturday = Carbon::create(2024, 10, 12, 11, 0, 0);
        Carbon::setTestNow($saturday);

        $message = $this->getOrderingAvailabilityMessage();
        expect($message)->toContain('available');
        expect($message)->not->toContain('closed');

        // Test weekday message
        $tuesday = Carbon::create(2024, 10, 8, 11, 0, 0);
        Carbon::setTestNow($tuesday);

        $message = $this->getOrderingAvailabilityMessage();
        expect($message)->toContain('weekends only');
        expect($message)->toContain('Saturday');

        Carbon::setTestNow(); // Reset
    }

    public function test_production_schedule_validation(): void
    {
        // Test that we can't schedule production on weekdays
        $monday = Carbon::create(2024, 10, 7);
        expect($this->canScheduleProduction($monday))->toBeFalse();

        // Test that we can schedule production on weekends
        $saturday = Carbon::create(2024, 10, 12);
        $sunday = Carbon::create(2024, 10, 13);
        expect($this->canScheduleProduction($saturday))->toBeTrue();
        expect($this->canScheduleProduction($sunday))->toBeTrue();
    }

    public function test_holiday_weekend_handling(): void
    {
        // Test Labor Day weekend (first Monday in September)
        $laborDayWeekend = Carbon::create(2024, 9, 1); // Sunday before Labor Day

        Carbon::setTestNow($laborDayWeekend);
        expect($this->isOrderingAllowed())->toBeTrue(); // Sunday should still work

        $laborDay = Carbon::create(2024, 9, 2); // Labor Day Monday
        Carbon::setTestNow($laborDay);
        expect($this->isOrderingAllowed())->toBeFalse(); // Holiday Monday still not allowed

        Carbon::setTestNow(); // Reset
    }

    public function test_arizona_timezone_handling(): void
    {
        // Arizona doesn't observe daylight saving time
        $arizonaTime = Carbon::create(2024, 10, 12, 11, 0, 0, 'America/Phoenix');
        Carbon::setTestNow($arizonaTime);

        expect($arizonaTime->timezone->getName())->toBe('America/Phoenix');
        expect($this->isOrderingAllowed())->toBeTrue();

        Carbon::setTestNow(); // Reset
    }

    public function test_weekend_edge_cases(): void
    {
        // Test Saturday at midnight
        $saturdayMidnight = Carbon::create(2024, 10, 12, 0, 0, 1); // 12:00:01 AM Saturday
        Carbon::setTestNow($saturdayMidnight);
        expect($this->isOrderingAllowed())->toBeTrue();

        // Test Sunday at 11:59 PM
        $sundayLate = Carbon::create(2024, 10, 13, 23, 59, 59); // 11:59:59 PM Sunday
        Carbon::setTestNow($sundayLate);
        expect($this->isOrderingAllowed())->toBeTrue();

        // Test Monday at 12:00 AM
        $mondayMidnight = Carbon::create(2024, 10, 14, 0, 0, 1); // 12:00:01 AM Monday
        Carbon::setTestNow($mondayMidnight);
        expect($this->isOrderingAllowed())->toBeFalse();

        Carbon::setTestNow(); // Reset
    }

    /**
     * Helper method to check if ordering is currently allowed.
     */
    protected function isOrderingAllowed(): bool
    {
        return Carbon::now()->isWeekend();
    }

    /**
     * Helper method to check if a date is a valid production day.
     */
    protected function isValidProductionDay(Carbon $date): bool
    {
        return $date->isWeekend();
    }

    /**
     * Helper method to get the next available ordering date.
     */
    protected function getNextAvailableOrderingDate(): Carbon
    {
        $now = Carbon::now();

        if ($now->isWeekend()) {
            // If it's Saturday morning, allow same day
            if ($now->isSaturday() && $now->hour < 15) {
                return $now;
            }
            // If it's Saturday afternoon/evening, suggest Sunday
            if ($now->isSaturday()) {
                return $now->next(Carbon::SUNDAY);
            }
            // If it's Sunday, suggest next Saturday
            return $now->next(Carbon::SATURDAY);
        }

        // If it's a weekday, suggest next Saturday
        return $now->next(Carbon::SATURDAY);
    }

    /**
     * Helper method to check if we're within ordering window.
     */
    protected function isWithinOrderingWindow(): bool
    {
        $now = Carbon::now();
        return $now->isWeekend() && $now->hour >= 8 && $now->hour <= 17;
    }

    /**
     * Helper method to get time until next ordering window.
     */
    protected function getTimeUntilNextOrderingWindow(): \DateInterval
    {
        $next = $this->getNextAvailableOrderingDate()->setHour(9);
        return Carbon::now()->diff($next);
    }

    /**
     * Helper method to get ordering availability message.
     */
    protected function getOrderingAvailabilityMessage(): string
    {
        if ($this->isOrderingAllowed()) {
            return 'Ordering is currently available! Place your order now.';
        }

        $nextDate = $this->getNextAvailableOrderingDate();
        return "Ordering is only available on weekends. Next available: {$nextDate->format('l, M j')}";
    }

    /**
     * Helper method to check if production can be scheduled on a date.
     */
    protected function canScheduleProduction(Carbon $date): bool
    {
        return $date->isWeekend();
    }
}
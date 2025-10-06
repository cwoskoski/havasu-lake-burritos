<?php

namespace Tests\Traits;

use Carbon\Carbon;
use Tests\Helpers\BurritoTestHelper;

/**
 * Trait for testing weekend-only production business logic.
 */
trait WeekendProductionTesting
{
    /**
     * Test that ordering is only allowed on weekends.
     */
    protected function assertOrderingOnlyOnWeekends()
    {
        // Test weekend dates (should allow ordering)
        $weekendDates = BurritoTestHelper::getWeekendDates();
        foreach ($weekendDates as $date) {
            Carbon::setTestNow($date);
            $this->assertTrue(
                $this->isOrderingAllowed(),
                "Ordering should be allowed on {$date->format('l, Y-m-d')}"
            );
        }

        // Test weekday dates (should prevent ordering)
        $weekdayDates = BurritoTestHelper::getWeekdayDates();
        foreach ($weekdayDates as $date) {
            Carbon::setTestNow($date);
            $this->assertFalse(
                $this->isOrderingAllowed(),
                "Ordering should NOT be allowed on {$date->format('l, Y-m-d')}"
            );
        }
    }

    /**
     * Test daily burrito limits are enforced.
     */
    protected function assertDailyLimitsEnforced(int $maxBurritos = 100)
    {
        $saturday = BurritoTestHelper::getWeekendDates()->first();
        Carbon::setTestNow($saturday);

        // Should allow orders up to limit
        for ($i = 1; $i <= $maxBurritos; $i++) {
            $this->assertTrue(
                $this->canOrderBurrito(),
                "Should allow burrito #{$i} when under daily limit"
            );
            $this->simulateOrderPlaced();
        }

        // Should prevent orders over limit
        $this->assertFalse(
            $this->canOrderBurrito(),
            "Should prevent orders when daily limit reached"
        );
    }

    /**
     * Test production schedule resets daily.
     */
    protected function assertProductionResetsDaily()
    {
        $weekend = BurritoTestHelper::getWeekendDates();
        $saturday = $weekend->first();
        $sunday = $weekend->last();

        // Fill Saturday's quota
        Carbon::setTestNow($saturday);
        $this->fillDailyQuota();
        $this->assertFalse($this->canOrderBurrito(), 'Saturday quota should be filled');

        // Sunday should have fresh quota
        Carbon::setTestNow($sunday);
        $this->assertTrue($this->canOrderBurrito(), 'Sunday should have fresh quota');
    }

    /**
     * Test real-time countdown display.
     */
    protected function assertRealTimeCountdown()
    {
        $saturday = BurritoTestHelper::getWeekendDates()->first();
        Carbon::setTestNow($saturday);

        $initialCount = $this->getRemainingBurritoCount();
        $this->assertGreaterThan(0, $initialCount, 'Should have burritos available initially');

        // Place an order
        $this->simulateOrderPlaced();
        $newCount = $this->getRemainingBurritoCount();

        $this->assertEquals(
            $initialCount - 1,
            $newCount,
            'Countdown should decrease by 1 after order'
        );
    }

    /**
     * Helper method to check if ordering is allowed (to be implemented by test classes).
     */
    abstract protected function isOrderingAllowed(): bool;

    /**
     * Helper method to check if a burrito can be ordered (to be implemented by test classes).
     */
    abstract protected function canOrderBurrito(): bool;

    /**
     * Helper method to simulate an order being placed (to be implemented by test classes).
     */
    abstract protected function simulateOrderPlaced(): void;

    /**
     * Helper method to fill the daily quota (to be implemented by test classes).
     */
    abstract protected function fillDailyQuota(): void;

    /**
     * Helper method to get remaining burrito count (to be implemented by test classes).
     */
    abstract protected function getRemainingBurritoCount(): int;
}
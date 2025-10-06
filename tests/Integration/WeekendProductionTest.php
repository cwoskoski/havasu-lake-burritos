<?php

namespace Tests\Integration;

use Tests\TestCase;
use Tests\Traits\WeekendProductionTesting;
use Tests\Helpers\BurritoTestHelper;
use Carbon\Carbon;

class WeekendProductionTest extends TestCase
{
    use WeekendProductionTesting;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up production schedule data
        $this->artisan('migrate:fresh');
        $this->seed(\Database\Seeders\ProductionScheduleSeeder::class);
    }

    public function test_weekend_only_production_scheduling()
    {
        $this->assertOrderingOnlyOnWeekends();
    }

    public function test_daily_burrito_limits()
    {
        $this->assertDailyLimitsEnforced(100);
    }

    public function test_production_resets_between_days()
    {
        $this->assertProductionResetsDaily();
    }

    public function test_real_time_countdown_updates()
    {
        $this->assertRealTimeCountdown();
    }

    public function test_multiple_orders_reduce_availability()
    {
        $saturday = BurritoTestHelper::getWeekendDates()->first();
        Carbon::setTestNow($saturday);

        $initialCount = $this->getRemainingBurritoCount();

        // Place multiple orders
        $this->simulateOrderPlaced();
        $this->simulateOrderPlaced();
        $this->simulateOrderPlaced();

        $finalCount = $this->getRemainingBurritoCount();

        $this->assertEquals(
            $initialCount - 3,
            $finalCount,
            'Availability should decrease by number of orders placed'
        );
    }

    public function test_weekend_schedule_spans_saturday_and_sunday()
    {
        $weekend = BurritoTestHelper::getWeekendDates();
        $saturday = $weekend->first();
        $sunday = $weekend->last();

        // Both days should allow ordering
        Carbon::setTestNow($saturday);
        $this->assertTrue($this->isOrderingAllowed(), 'Saturday should allow ordering');

        Carbon::setTestNow($sunday);
        $this->assertTrue($this->isOrderingAllowed(), 'Sunday should allow ordering');
    }

    public function test_availability_api_returns_correct_data()
    {
        $saturday = BurritoTestHelper::getWeekendDates()->first();
        Carbon::setTestNow($saturday);

        $response = $this->get('/api/availability');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'date',
                'remaining_burritos',
                'max_burritos',
                'ordering_enabled',
                'is_weekend'
            ]);

        $data = $response->json();
        $this->assertTrue($data['ordering_enabled']);
        $this->assertTrue($data['is_weekend']);
        $this->assertGreaterThan(0, $data['remaining_burritos']);
    }

    public function test_ordering_disabled_when_quota_reached()
    {
        $saturday = BurritoTestHelper::getWeekendDates()->first();
        Carbon::setTestNow($saturday);

        // Fill the quota
        $this->fillDailyQuota();

        $response = $this->get('/api/availability');
        $data = $response->json();

        $this->assertFalse($data['ordering_enabled']);
        $this->assertEquals(0, $data['remaining_burritos']);
    }

    public function test_ingredient_availability_tracks_usage()
    {
        $saturday = BurritoTestHelper::getWeekendDates()->first();
        Carbon::setTestNow($saturday);

        // Get initial ingredient availability
        $response = $this->get('/api/ingredients/availability');
        $initialAvailability = $response->json();

        // Place order with specific ingredients
        $burritoConfig = BurritoTestHelper::createBurritoConfiguration();
        $this->post('/api/orders', ['burrito' => $burritoConfig]);

        // Check updated availability
        $response = $this->get('/api/ingredients/availability');
        $updatedAvailability = $response->json();

        // Carnitas should have decreased availability
        $carnitasInitial = collect($initialAvailability)->firstWhere('name', 'Carnitas');
        $carnitasUpdated = collect($updatedAvailability)->firstWhere('name', 'Carnitas');

        $this->assertLessThan(
            $carnitasInitial['remaining_portions'],
            $carnitasUpdated['remaining_portions'],
            'Carnitas availability should decrease after order'
        );
    }

    // Implementation of abstract methods from WeekendProductionTesting trait
    protected function isOrderingAllowed(): bool
    {
        $response = $this->get('/api/availability');
        return $response->json()['ordering_enabled'] ?? false;
    }

    protected function canOrderBurrito(): bool
    {
        $response = $this->get('/api/availability');
        $data = $response->json();
        return $data['ordering_enabled'] && $data['remaining_burritos'] > 0;
    }

    protected function simulateOrderPlaced(): void
    {
        $burritoConfig = BurritoTestHelper::createBurritoConfiguration();
        $this->post('/api/orders', [
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'burrito' => $burritoConfig
        ]);
    }

    protected function fillDailyQuota(): void
    {
        $maxBurritos = config('burrito.max_daily_burritos', 100);

        for ($i = 0; $i < $maxBurritos; $i++) {
            $this->simulateOrderPlaced();
        }
    }

    protected function getRemainingBurritoCount(): int
    {
        $response = $this->get('/api/availability');
        return $response->json()['remaining_burritos'];
    }
}
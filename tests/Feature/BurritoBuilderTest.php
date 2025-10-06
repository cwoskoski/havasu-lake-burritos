<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\MobileTesting;
use Tests\Traits\WeekendProductionTesting;
use Tests\Helpers\BurritoTestHelper;
use Carbon\Carbon;

class BurritoBuilderTest extends TestCase
{
    use MobileTesting, WeekendProductionTesting;

    public function test_burrito_builder_loads_on_mobile()
    {
        $response = $this->withMobileUserAgent('iPhone')
            ->get('/burrito-builder');

        $response->assertStatus(200);
        $this->assertMobileViewport($response);
        $this->assertMobileOptimized($response);
    }

    public function test_ingredient_categories_display_correctly()
    {
        $response = $this->get('/burrito-builder');

        $response->assertStatus(200);
        $response->assertSee('Proteins');
        $response->assertSee('Rice & Beans');
        $response->assertSee('Fresh Toppings');
        $response->assertSee('Salsas');
        $response->assertSee('Creamy');
    }

    public function test_burrito_builder_track_progression()
    {
        // Test the guided "track" process through all ingredient categories
        $categories = ['proteins', 'rice-beans', 'fresh-toppings', 'salsas', 'creamy'];

        foreach ($categories as $index => $category) {
            $response = $this->get("/burrito-builder/{$category}");
            $response->assertStatus(200);

            // Should show progress indicator
            $response->assertSee("Step " . ($index + 1));
            $response->assertSee("of 5");
        }
    }

    public function test_weekend_only_ordering_enforcement()
    {
        // Test weekend ordering (should work)
        $saturday = BurritoTestHelper::getWeekendDates()->first();
        Carbon::setTestNow($saturday);

        $response = $this->get('/burrito-builder');
        $response->assertStatus(200);
        $response->assertDontSee('Ordering is only available on weekends');

        // Test weekday ordering (should be blocked)
        $monday = BurritoTestHelper::getWeekdayDates()->first();
        Carbon::setTestNow($monday);

        $response = $this->get('/burrito-builder');
        $response->assertSee('Ordering is only available on weekends');
    }

    public function test_daily_burrito_limit_display()
    {
        $saturday = BurritoTestHelper::getWeekendDates()->first();
        Carbon::setTestNow($saturday);

        $response = $this->get('/burrito-builder');
        $response->assertStatus(200);

        // Should show remaining burrito count
        $response->assertSee('burritos remaining');
        $response->assertSeeText('100'); // Default test limit
    }

    public function test_touch_target_compliance()
    {
        $response = $this->withMobileUserAgent('iPhone')
            ->get('/burrito-builder');

        $response->assertStatus(200);

        // These would be implemented with browser testing
        $this->assertTouchTargetSizes([
            '.ingredient-button',
            '.next-step-button',
            '.back-button',
            '.submit-order',
        ]);
    }

    public function test_single_handed_operation()
    {
        $response = $this->withMobileUserAgent('iPhone')
            ->get('/burrito-builder');

        $response->assertStatus(200);
        $this->assertSingleHandedOperation();
    }

    public function test_mobile_performance()
    {
        $this->assertMobilePerformance('/burrito-builder');
    }

    public function test_responsive_ingredient_grid()
    {
        $response = $this->get('/burrito-builder/proteins');
        $response->assertStatus(200);

        // Should use responsive grid classes
        $response->assertSee('grid');
        $response->assertSee('grid-cols-1'); // Mobile: single column
        $response->assertSee('md:grid-cols-2'); // Tablet: two columns
        $response->assertSee('lg:grid-cols-3'); // Desktop: three columns
    }

    public function test_ingredient_selection_persistence()
    {
        $this->withSession(['burrito_selections' => []])
            ->post('/burrito-builder/proteins', [
                'selected' => ['Carnitas', 'Chicken']
            ])
            ->assertSessionHas('burrito_selections.proteins');

        // Navigate to next step and verify selections persist
        $response = $this->get('/burrito-builder/rice-beans');
        $response->assertStatus(200);
        $response->assertSee('Carnitas'); // Should show previous selections
        $response->assertSee('Chicken');
    }

    public function test_portion_calculation_accuracy()
    {
        $burritoConfig = BurritoTestHelper::createBurritoConfiguration();
        $portions = BurritoTestHelper::calculatePortions($burritoConfig);

        // Verify standard portions
        $this->assertEquals(0.5, $portions['Carnitas']['amount']); // 1/2 cup protein
        $this->assertEquals('cup', $portions['Carnitas']['unit']);

        $this->assertEquals(0.67, $portions['Black Beans']['amount']); // 2/3 cup beans
        $this->assertEquals('cup', $portions['Black Beans']['unit']);
    }

    // Abstract method implementations for WeekendProductionTesting trait
    protected function isOrderingAllowed(): bool
    {
        $response = $this->get('/burrito-builder');
        return $response->status() === 200 &&
               !str_contains($response->getContent(), 'Ordering is only available on weekends');
    }

    protected function canOrderBurrito(): bool
    {
        $response = $this->get('/api/availability');
        $data = $response->json();
        return $data['remaining_burritos'] > 0;
    }

    protected function simulateOrderPlaced(): void
    {
        // Simulate placing an order to test quota enforcement
        $this->post('/api/orders', [
            'burrito' => BurritoTestHelper::createBurritoConfiguration()
        ]);
    }

    protected function fillDailyQuota(): void
    {
        // Fill the daily quota for testing limit enforcement
        $maxBurritos = config('app.max_daily_burritos', 100);
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
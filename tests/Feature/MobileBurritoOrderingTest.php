<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Feature tests for mobile burrito ordering workflow.
 * Tests the complete user journey on mobile devices.
 */
class MobileBurritoOrderingTest extends TestCase
{
    /**
     * Test mobile user can access burrito builder page.
     */
    public function test_mobile_user_can_access_burrito_builder(): void
    {
        $response = $this->get('/build-burrito');

        $response->assertStatus(200);
        $this->assertMobileOptimized($response);
        $response->assertSee('Build Your Burrito');
    }

    /**
     * Test mobile burrito builder track navigation.
     */
    public function test_mobile_burrito_track_navigation(): void
    {
        $response = $this->get('/build-burrito');

        $response->assertStatus(200)
                ->assertSee('Proteins') // First track step
                ->assertSee('Rice & Beans') // Second track step
                ->assertSee('Fresh Toppings') // Third track step
                ->assertSee('Salsas') // Fourth track step
                ->assertSee('Creamy'); // Fifth track step

        // Test mobile-specific navigation elements
        $response->assertSee('Next')
                ->assertSee('Previous');
    }

    /**
     * Test weekend-only ordering restriction.
     */
    public function test_weekend_only_ordering_restriction(): void
    {
        // This test would check current day and behave accordingly
        $today = now();

        if ($today->isWeekend()) {
            $response = $this->get('/build-burrito');
            $response->assertStatus(200)
                    ->assertSee('Build Your Burrito')
                    ->assertDontSee('Orders are only available on weekends');
        } else {
            $response = $this->get('/build-burrito');
            $response->assertStatus(200)
                    ->assertSee('Orders are only available on weekends')
                    ->assertSee('Saturday')
                    ->assertSee('Sunday');
        }
    }

    /**
     * Test mobile API endpoints for burrito building.
     */
    public function test_mobile_api_burrito_building(): void
    {
        // Test getting available ingredients
        $response = $this->getJson('/api/ingredients/available');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'proteins',
                    'rice_beans',
                    'fresh_toppings',
                    'salsas',
                    'creamy'
                ]);

        // Test mobile-optimized response size
        $responseSize = strlen($response->getContent());
        $this->assertLessThan(50000, $responseSize, 'API response should be under 50KB for mobile');
    }

    /**
     * Test mobile burrito price calculation API.
     */
    public function test_mobile_burrito_price_calculation(): void
    {
        $burritoData = [
            'ingredients' => [
                'protein' => 'chicken',
                'rice' => 'spanish_rice',
                'beans' => 'black_beans',
                'toppings' => ['lettuce', 'tomatoes'],
                'salsa' => 'medium',
                'creamy' => 'cheese'
            ]
        ];

        $response = $this->postJson('/api/burrito/calculate-price', $burritoData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'total_price',
                    'ingredients_breakdown',
                    'estimated_pickup_time'
                ]);

        // Verify mobile-friendly price format
        $priceData = $response->json();
        $this->assertIsFloat($priceData['total_price']);
        $this->assertGreaterThan(0, $priceData['total_price']);
    }

    /**
     * Test mobile order submission workflow.
     */
    public function test_mobile_order_submission(): void
    {
        $user = $this->actingAsUser();

        $orderData = [
            'burritos' => [
                [
                    'ingredients' => [
                        'protein' => 'chicken',
                        'rice' => 'spanish_rice',
                        'beans' => 'black_beans',
                        'toppings' => ['lettuce', 'tomatoes'],
                        'salsa' => 'medium',
                        'creamy' => 'cheese'
                    ],
                    'quantity' => 2
                ]
            ],
            'pickup_date' => now()->nextWeekend()->format('Y-m-d'),
            'pickup_time' => '12:00',
            'customer_notes' => 'Extra spicy please!'
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'order_id',
                    'total_price',
                    'pickup_datetime',
                    'estimated_ready_time',
                    'order_status'
                ]);
    }

    /**
     * Test mobile performance requirements.
     */
    public function test_mobile_performance_requirements(): void
    {
        // Test page load performance
        $startTime = microtime(true);

        $response = $this->get('/build-burrito');

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Mobile should load in under 2 seconds
        $this->assertLessThan(2000, $loadTime, 'Mobile page should load in under 2 seconds');
    }

    /**
     * Test mobile touch target compliance.
     */
    public function test_mobile_touch_target_compliance(): void
    {
        $response = $this->get('/build-burrito');

        $response->assertStatus(200);

        // Verify mobile viewport meta tag
        $response->assertSee('width=device-width', false);
        $response->assertSee('initial-scale=1', false);

        // Check for mobile-specific CSS classes
        $response->assertSee('touch-target', false);
        $response->assertSee('mobile-button', false);
    }

    /**
     * Test weekend production capacity tracking.
     */
    public function test_weekend_production_capacity(): void
    {
        $response = $this->getJson('/api/production/capacity');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'saturday' => [
                        'total_capacity',
                        'remaining_capacity',
                        'orders_filled'
                    ],
                    'sunday' => [
                        'total_capacity',
                        'remaining_capacity',
                        'orders_filled'
                    ]
                ]);

        $capacity = $response->json();

        // Verify capacity limits
        $this->assertLessThanOrEqual(100, $capacity['saturday']['total_capacity']);
        $this->assertLessThanOrEqual(100, $capacity['sunday']['total_capacity']);
    }

    /**
     * Test mobile-friendly error handling.
     */
    public function test_mobile_error_handling(): void
    {
        // Test invalid ingredient selection
        $invalidOrder = [
            'ingredients' => [
                'protein' => 'invalid_protein',
                'rice' => 'spanish_rice'
            ]
        ];

        $response = $this->postJson('/api/burrito/calculate-price', $invalidOrder);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);

        // Verify mobile-friendly error messages
        $errorData = $response->json();
        $this->assertNotEmpty($errorData['message']);
        $this->assertIsArray($errorData['errors']);
    }

    /**
     * Test Arizona timezone handling.
     */
    public function test_arizona_timezone_handling(): void
    {
        $response = $this->getJson('/api/business-hours');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'timezone',
                    'current_time',
                    'weekend_hours',
                    'is_open'
                ]);

        $businessHours = $response->json();
        $this->assertEquals('America/Phoenix', $businessHours['timezone']);
    }
}
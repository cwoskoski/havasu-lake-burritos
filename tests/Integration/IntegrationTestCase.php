<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Enums\IngredientCategory;
use App\Models\Ingredient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\ApiTesting;
use Tests\Traits\DatabaseTesting;
use Tests\Traits\ServiceTesting;

/**
 * Base class for integration tests that test multiple components working together.
 * These tests verify that different parts of the burrito ordering system integrate properly.
 *
 * Integration tests use real database connections and test complete workflows.
 */
abstract class IntegrationTestCase extends TestCase
{
    use ApiTesting;
    use DatabaseTesting;
    use RefreshDatabase;
    use ServiceTesting;

    /**
     * Setup for integration tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Enable real events and queues for integration testing
        $this->enableRealEvents();
        $this->enableRealQueues();

        // Seed necessary data for integration testing
        $this->seedIntegrationData();
    }

    /**
     * Seed data specifically needed for integration tests.
     */
    protected function seedIntegrationData(): void
    {
        // Create a complete set of ingredients
        $this->createCompleteIngredientSet();

        // Set up weekend production schedule
        $this->setupWeekendProduction();

        // Create test users with different verification states
        $this->createTestUsers();
    }

    /**
     * Create a complete ingredient set for testing.
     */
    protected function createCompleteIngredientSet(): void
    {
        foreach (IngredientCategory::cases() as $category) {
            Ingredient::factory()
                ->withCategory($category)
                ->count(3)
                ->create();
        }
    }

    /**
     * Set up weekend production schedule for testing.
     */
    protected function setupWeekendProduction(): void
    {
        // Create production schedules for the next few weekends
        $weekends = collect();
        for ($i = 0; $i < 4; $i++) {
            $saturday = Carbon::now()->addWeeks($i)->next(Carbon::SATURDAY);
            $sunday = $saturday->copy()->addDay();
            $weekends->push($saturday, $sunday);
        }

        foreach ($weekends as $date) {
            // This would create ProductionSchedule records when that model exists
            // For now, we'll use the helper method
            $this->mockWeekendSchedule($date, 100);
        }
    }

    /**
     * Create test users with different states.
     */
    protected function createTestUsers(): void
    {
        // Verified user
        User::factory()->verified()->create([
            'email' => 'verified@test.com',
            'phone' => '+15551111111',
        ]);

        // Unverified user
        User::factory()->create([
            'email' => 'unverified@test.com',
            'phone' => '+15552222222',
            'phone_verified_at' => null,
        ]);

        // User without phone
        User::factory()->create([
            'email' => 'nophone@test.com',
            'phone' => null,
        ]);
    }

    /**
     * Create a complete burrito ordering scenario for testing.
     */
    protected function createBurritoOrderingScenario(): array
    {
        // Travel to a weekend for testing
        $saturday = Carbon::now()->next(Carbon::SATURDAY);
        Carbon::setTestNow($saturday);

        return [
            'date' => $saturday,
            'ingredients' => $this->getAvailableIngredients(),
            'user' => User::factory()->verified()->create(),
            'production_capacity' => 100,
            'remaining_capacity' => 95,
        ];
    }

    /**
     * Get available ingredients organized by category.
     */
    protected function getAvailableIngredients(): array
    {
        $ingredients = [];

        foreach (IngredientCategory::cases() as $category) {
            $ingredients[$category->value] = Ingredient::active()
                ->byCategory($category)
                ->ordered()
                ->get()
                ->toArray();
        }

        return $ingredients;
    }

    /**
     * Assert that the complete mobile ordering flow works end-to-end.
     */
    protected function assertCompleteOrderingFlow(): void
    {
        $scenario = $this->createBurritoOrderingScenario();

        // Test ingredient selection API
        $this->assertIngredientSelectionFlow($scenario['ingredients']);

        // Test burrito building API
        $this->assertBurritoBuilderFlow($scenario['user']);

        // Test order submission API
        $this->assertOrderSubmissionFlow($scenario['user']);

        // Test order confirmation flow
        $this->assertOrderConfirmationFlow();
    }

    /**
     * Assert ingredient selection flow works correctly.
     */
    protected function assertIngredientSelectionFlow(array $ingredients): void
    {
        // Test ingredients API endpoint
        $response = $this->getJson('/api/ingredients');
        $this->assertApiResponse($response);
        $this->assertIngredientAvailabilityApi('/api/ingredients');

        // Verify data structure matches expected format
        foreach (IngredientCategory::cases() as $category) {
            $categoryIngredients = $response->json("data.{$category->value}");
            $this->assertNotEmpty($categoryIngredients, "Should have {$category->value} ingredients");
        }
    }

    /**
     * Assert burrito builder flow works correctly.
     */
    protected function assertBurritoBuilderFlow(User $user): void
    {
        $this->actingAs($user);

        // Test creating a burrito configuration
        $burritoData = [
            'proteins' => ['carnitas'],
            'rice_beans' => ['cilantro_rice', 'black_beans'],
            'fresh_toppings' => ['lettuce', 'tomatoes'],
            'salsas' => ['medium'],
            'creamy' => ['cheese'],
        ];

        $response = $this->postJson('/api/burrito-builder', $burritoData);
        $this->assertApiResponse($response, 201);

        // Test burrito validation
        $invalidBurrito = ['proteins' => []]; // Missing required protein
        $this->assertBurritoBuildingRules('/api/burrito-builder', $invalidBurrito);
    }

    /**
     * Assert order submission flow works correctly.
     */
    protected function assertOrderSubmissionFlow(User $user): void
    {
        $this->actingAs($user);

        $orderData = [
            'burritos' => [
                [
                    'proteins' => ['carnitas'],
                    'rice_beans' => ['cilantro_rice', 'black_beans'],
                    'fresh_toppings' => ['lettuce'],
                    'salsas' => ['medium'],
                    'creamy' => ['cheese'],
                ],
            ],
            'pickup_time' => Carbon::now()->addHours(2)->toISOString(),
            'customer_notes' => 'Extra spicy please',
        ];

        $response = $this->postJson('/api/orders', $orderData);
        $this->assertApiResponse($response, 201);

        // Verify order was created in database
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Assert order confirmation flow works correctly.
     */
    protected function assertOrderConfirmationFlow(): void
    {
        // Test that SMS notifications are sent
        Queue::assertPushed(\App\Jobs\SendOrderConfirmationSms::class);

        // Test that order events are fired
        Event::assertDispatched(\App\Events\OrderCreated::class);
    }

    /**
     * Assert guest ordering flow works correctly.
     */
    protected function assertGuestOrderingFlow(): void
    {
        $guestData = [
            'guest_phone' => '+15559999999',
            'guest_name' => 'Guest Customer',
            'burritos' => [
                [
                    'proteins' => ['chicken'],
                    'rice_beans' => ['spanish_rice'],
                    'fresh_toppings' => ['lettuce'],
                    'salsas' => ['mild'],
                    'creamy' => [],
                ],
            ],
        ];

        // Mock phone verification
        session(['guest_phone_verified' => true]);

        $response = $this->postJson('/api/guest-orders', $guestData);
        $this->assertApiResponse($response, 201);

        // Verify guest order was created
        $this->assertDatabaseHas('orders', [
            'guest_phone' => '+15559999999',
            'guest_name' => 'Guest Customer',
        ]);
    }

    /**
     * Assert weekend-only business logic across all endpoints.
     */
    protected function assertWeekendOnlyBusinessLogic(): void
    {
        $endpoints = [
            '/api/burrito-builder',
            '/api/orders',
            '/api/guest-orders',
        ];

        foreach ($endpoints as $endpoint) {
            $this->assertWeekendOnlyApi($endpoint, ['test' => 'data']);
        }
    }

    /**
     * Assert that database transactions work correctly under load.
     */
    protected function assertDatabaseIntegrity(): void
    {
        $this->assertTransactionPerformance(function () {
            // Simulate concurrent order creation
            $user = User::factory()->verified()->create();

            // Create order with related models
            $order = $user->orders()->create([
                'status' => 'pending',
                'total_amount' => 1200,
                'pickup_time' => Carbon::now()->addHours(2),
            ]);

            // Create burrito with ingredients
            $burrito = $order->burritos()->create([
                'price' => 1200,
            ]);

            // Add ingredients
            $ingredients = Ingredient::take(5)->get();
            foreach ($ingredients as $ingredient) {
                $burrito->ingredients()->attach($ingredient->id, [
                    'quantity' => 1.0,
                ]);
            }
        }, 15); // Allow up to 15 queries for this complex operation
    }

    /**
     * Assert that caching works correctly across the application.
     */
    protected function assertCachingBehavior(): void
    {
        // Test ingredient caching
        $this->assertServiceCaching(
            fn () => $this->getJson('/api/ingredients')->json(),
            'ingredients.available.current_week'
        );

        // Test production schedule caching
        $this->assertServiceCaching(
            fn () => $this->getJson('/api/production-status')->json(),
            'production.schedule.current_weekend'
        );
    }

    /**
     * Assert that the mobile performance targets are met.
     */
    protected function assertMobilePerformanceTargets(): void
    {
        $criticalEndpoints = [
            '/api/ingredients',
            '/api/production-status',
            '/api/burrito-builder',
        ];

        foreach ($criticalEndpoints as $endpoint) {
            $this->assertApiPerformance($endpoint, 5); // 5 concurrent requests

            $response = $this->get($endpoint);
            $this->assertMobilePerformanceHints($response);
        }
    }

    /**
     * Clean up after integration tests.
     */
    protected function tearDown(): void
    {
        // Reset any time mocking
        Carbon::setTestNow();

        parent::tearDown();
    }
}

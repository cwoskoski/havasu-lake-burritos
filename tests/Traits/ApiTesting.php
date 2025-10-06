<?php

namespace Tests\Traits;

use Illuminate\Testing\TestResponse;
use Tests\Helpers\BurritoTestHelper;

/**
 * Trait for API endpoint testing with mobile-first considerations.
 * Provides utilities for testing REST API endpoints and JSON responses.
 */
trait ApiTesting
{
    /**
     * Assert that API response has correct structure and status.
     */
    protected function assertApiResponse(TestResponse $response, int $expectedStatus = 200, array $expectedStructure = []): void
    {
        $response->assertStatus($expectedStatus);
        $response->assertHeader('Content-Type', 'application/json');

        if (!empty($expectedStructure)) {
            $response->assertJsonStructure($expectedStructure);
        }
    }

    /**
     * Assert that API response includes proper pagination metadata.
     */
    protected function assertApiPagination(TestResponse $response, array $expectedMeta = []): void
    {
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
        ]);

        if (!empty($expectedMeta)) {
            foreach ($expectedMeta as $key => $value) {
                $response->assertJsonPath("meta.{$key}", $value);
            }
        }
    }

    /**
     * Assert that API error response has proper error structure.
     */
    protected function assertApiError(TestResponse $response, int $expectedStatus, string $expectedMessage = null): void
    {
        $response->assertStatus($expectedStatus);
        $response->assertJsonStructure([
            'message',
            'errors' => []
        ]);

        if ($expectedMessage) {
            $response->assertJsonPath('message', $expectedMessage);
        }
    }

    /**
     * Assert that API validation errors are properly formatted.
     */
    protected function assertApiValidationErrors(TestResponse $response, array $expectedFields): void
    {
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors'
        ]);

        foreach ($expectedFields as $field) {
            $response->assertJsonValidationErrors($field);
        }
    }

    /**
     * Assert that API response includes mobile-optimized data.
     */
    protected function assertMobileOptimizedApi(TestResponse $response): void
    {
        // Check for mobile-specific optimizations in API responses
        $data = $response->json();

        // Should include minimal data for faster mobile loading
        $this->assertArrayHasKey('data', $data, 'API should wrap data in data key');

        // Should include meta information for mobile apps
        if (isset($data['meta'])) {
            $this->assertArrayHasKey('mobile_optimized', $data['meta'], 'Should indicate mobile optimization');
        }
    }

    /**
     * Test API endpoint with various mobile user agents.
     */
    protected function assertMobileUserAgentSupport(string $endpoint, string $method = 'GET', array $data = []): void
    {
        $userAgents = BurritoTestHelper::getMobileUserAgents();

        foreach ($userAgents as $device => $userAgent) {
            $response = $this->withHeaders(['User-Agent' => $userAgent])
                ->json($method, $endpoint, $data);

            $response->assertStatus(200);
            $this->assertMobileOptimizedApi($response);
        }
    }

    /**
     * Assert that API rate limiting works correctly.
     */
    protected function assertApiRateLimit(string $endpoint, int $maxAttempts = 60, int $windowMinutes = 1): void
    {
        // Make requests up to the limit
        for ($i = 1; $i <= $maxAttempts; $i++) {
            $response = $this->get($endpoint);
            $response->assertStatus(200);
        }

        // Next request should be rate limited
        $response = $this->get($endpoint);
        $response->assertStatus(429);
        $response->assertJsonStructure([
            'message',
            'retry_after'
        ]);
    }

    /**
     * Assert that API authentication works correctly.
     */
    protected function assertApiAuthentication(string $endpoint, string $method = 'GET', array $data = []): void
    {
        // Test without authentication - should fail
        $response = $this->json($method, $endpoint, $data);
        $response->assertStatus(401);

        // Test with authentication - should succeed
        $user = \App\Models\User::factory()->verified()->create();
        $response = $this->actingAs($user)->json($method, $endpoint, $data);
        $response->assertStatus(200);
    }

    /**
     * Assert that API endpoints support guest ordering.
     */
    protected function assertGuestOrderingSupport(string $endpoint, array $orderData): void
    {
        // Include guest phone verification in the request
        $guestData = array_merge($orderData, [
            'guest_phone' => '+15551234567',
            'phone_verified' => true
        ]);

        $response = $this->json('POST', $endpoint, $guestData);
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'guest_phone',
                'status'
            ]
        ]);
    }

    /**
     * Assert that weekend-only endpoints respect business hours.
     */
    protected function assertWeekendOnlyApi(string $endpoint, array $requestData = []): void
    {
        // Test on weekday - should be forbidden
        $weekday = \Carbon\Carbon::now()->next(\Carbon\Carbon::MONDAY);
        \Carbon\Carbon::setTestNow($weekday);

        $response = $this->json('POST', $endpoint, $requestData);
        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Ordering is only available on weekends');

        // Test on weekend - should succeed
        $weekend = \Carbon\Carbon::now()->next(\Carbon\Carbon::SATURDAY);
        \Carbon\Carbon::setTestNow($weekend);

        $response = $this->json('POST', $endpoint, $requestData);
        $response->assertSuccessful();

        \Carbon\Carbon::setTestNow(); // Reset
    }

    /**
     * Assert that ingredient availability is properly reflected in API.
     */
    protected function assertIngredientAvailabilityApi(string $endpoint): void
    {
        $response = $this->get($endpoint);
        $response->assertSuccessful();

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'category',
                    'is_available',
                    'availability_info' => [
                        'week_start',
                        'week_end',
                        'remaining_quantity'
                    ]
                ]
            ]
        ]);

        // Verify that only available ingredients are marked as available
        $ingredients = $response->json('data');
        foreach ($ingredients as $ingredient) {
            if ($ingredient['is_available']) {
                $this->assertGreaterThan(0, $ingredient['availability_info']['remaining_quantity']);
            }
        }
    }

    /**
     * Assert that burrito building API enforces business rules.
     */
    protected function assertBurritoBuildingRules(string $endpoint, array $invalidBurrito): void
    {
        $response = $this->json('POST', $endpoint, $invalidBurrito);
        $response->assertStatus(422);

        // Should validate required categories
        if (!isset($invalidBurrito['proteins'])) {
            $response->assertJsonValidationErrors('proteins');
        }

        // Should validate portion limits
        if (isset($invalidBurrito['proteins']) && count($invalidBurrito['proteins']) > 2) {
            $response->assertJsonPath('errors.proteins.0', 'Maximum 2 proteins allowed');
        }
    }

    /**
     * Assert that order submission API handles capacity limits.
     */
    protected function assertOrderCapacityLimits(string $endpoint, array $validOrder): void
    {
        // Mock a scenario where daily capacity is reached
        $this->mockWeekendScheduleForApi(\Carbon\Carbon::now()->next(\Carbon\Carbon::SATURDAY), 0);

        $response = $this->json('POST', $endpoint, $validOrder);
        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Daily burrito capacity reached');
    }

    /**
     * Assert that API responses include performance hints for mobile.
     */
    protected function assertMobilePerformanceHints(TestResponse $response): void
    {
        // Check for cache headers
        $this->assertTrue(
            $response->headers->has('Cache-Control'),
            'API should include cache control headers for mobile performance'
        );

        // Check response size is reasonable for mobile
        $contentLength = strlen($response->getContent());
        $this->assertLessThan(
            1024 * 100, // 100KB
            $contentLength,
            'API response should be under 100KB for mobile performance'
        );
    }

    /**
     * Assert that API includes proper CORS headers for mobile apps.
     */
    protected function assertCorsHeaders(TestResponse $response): void
    {
        $response->assertHeader('Access-Control-Allow-Origin');
        $response->assertHeader('Access-Control-Allow-Methods');
        $response->assertHeader('Access-Control-Allow-Headers');
    }

    /**
     * Test API endpoint performance under load.
     */
    protected function assertApiPerformance(string $endpoint, int $concurrentRequests = 10): void
    {
        $startTime = microtime(true);
        $responses = [];

        // Simulate concurrent requests
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $responses[] = $this->get($endpoint);
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertSuccessful();
        }

        // Average response time should be reasonable
        $averageTime = $totalTime / $concurrentRequests;
        $this->assertLessThan(
            0.5, // 500ms
            $averageTime,
            "Average API response time ({$averageTime}s) exceeds performance target"
        );
    }

    /**
     * Helper to mock weekend schedule for testing.
     */
    protected function mockWeekendScheduleForApi(\Carbon\Carbon $date, int $remainingBurritos): void
    {
        // This would integrate with your actual weekend scheduling service
        \Carbon\Carbon::setTestNow($date);

        // Mock the service response
        $this->mock(\App\Services\WeekendProductionService::class, function ($mock) use ($remainingBurritos) {
            $mock->shouldReceive('getRemainingBurritos')->andReturn($remainingBurritos);
            $mock->shouldReceive('canAcceptOrder')->andReturn($remainingBurritos > 0);
        });
    }
}
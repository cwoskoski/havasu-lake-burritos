<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use App\Models\User;
use Carbon\Carbon;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Indicates whether the default seeder should run before each test.
     */
    protected bool $seed = false;

    /**
     * Track test execution time for performance monitoring.
     */
    protected float $testStartTime;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        $this->testStartTime = microtime(true);

        parent::setUp();

        // Set consistent timezone for all tests (Arizona - no DST)
        date_default_timezone_set('America/Phoenix');
        Carbon::setTestNow();

        // Performance optimizations for faster test execution
        $this->optimizeForTesting();

        // Mock external services by default
        $this->mockExternalServices();

        // Clear any cached state
        $this->clearTestState();
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        // Log slow tests for optimization
        $executionTime = microtime(true) - $this->testStartTime;
        if ($executionTime > 2.0) { // Only warn for very slow tests
            $testName = method_exists($this, 'getName') ? $this->getName() : get_class($this);
            // Log to error_log instead of echoing to avoid test output interference
            error_log("Slow test detected: " . $testName . " took {$executionTime}s");
        }

        // Reset Carbon test time
        Carbon::setTestNow();

        // Clean up Mockery
        Mockery::close();

        parent::tearDown();
    }

    /**
     * Optimize Laravel for faster test execution.
     */
    protected function optimizeForTesting(): void
    {
        // Disable unnecessary features during testing
        DB::connection()->disableQueryLog();

        // Clear caches for clean state
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
    }

    /**
     * Mock external services to prevent actual API calls during testing.
     */
    protected function mockExternalServices(): void
    {
        // Mock mail sending
        Mail::fake();

        // Mock notifications
        Notification::fake();

        // Mock event dispatching for some tests
        // Event::fake(); // Only when needed

        // Mock queue jobs
        Queue::fake();
    }

    /**
     * Clear any cached state between tests.
     */
    protected function clearTestState(): void
    {
        Cache::flush();
    }

    /**
     * Create authenticated user for testing.
     */
    protected function actingAsUser($user = null): static
    {
        $user = $user ?: User::factory()->verified()->create();
        return $this->actingAs($user);
    }

    /**
     * Create an authenticated user with phone verification.
     */
    protected function actingAsVerifiedUser(array $attributes = []): static
    {
        $user = User::factory()->verified()->create($attributes);
        return $this->actingAs($user);
    }

    /**
     * Create a guest user with phone verification for guest checkout.
     */
    protected function actingAsGuestWithPhone(string $phone = '+15551234567'): array
    {
        session(['guest_phone' => $phone, 'phone_verified' => true]);
        return ['phone' => $phone];
    }

    /**
     * Assert that response contains mobile-optimized viewport meta tag.
     */
    protected function assertMobileOptimized($response): void
    {
        $response->assertSee('viewport', false);
        $response->assertSee('width=device-width', false);
        $response->assertSee('initial-scale=1', false);
    }

    /**
     * Assert response meets mobile performance requirements.
     */
    protected function assertMobilePerformant($response, float $maxTimeMs = 300): void
    {
        $executionTime = (microtime(true) - $this->testStartTime) * 1000;
        $this->assertLessThan(
            $maxTimeMs,
            $executionTime,
            "Response took {$executionTime}ms, exceeding mobile limit of {$maxTimeMs}ms"
        );
    }

    /**
     * Assert that touch targets meet minimum 44px requirement.
     */
    protected function assertTouchTargetCompliant($selector, $response = null): void
    {
        // This would be used with browser testing to verify touch target sizes
        // For now, we'll mark as incomplete to remind us to implement with Dusk
        $this->markTestIncomplete("Touch target testing for {$selector} requires browser automation");
    }

    /**
     * Mock weekend production schedule.
     */
    protected function mockWeekendSchedule(Carbon $date = null, int $maxBurritos = 100): array
    {
        $date = $date ?: Carbon::now()->next(Carbon::SATURDAY);

        return [
            'date' => $date->format('Y-m-d'),
            'max_burritos' => $maxBurritos,
            'remaining_burritos' => $maxBurritos,
            'is_production_day' => $date->isWeekend(),
        ];
    }

    /**
     * Assert weekend-only business logic.
     */
    protected function assertWeekendOnlyOrdering(): void
    {
        // Test weekend (should allow)
        $saturday = Carbon::now()->next(Carbon::SATURDAY);
        Carbon::setTestNow($saturday);
        $this->assertTrue($this->isOrderingAllowed(), 'Should allow ordering on Saturday');

        $sunday = Carbon::now()->next(Carbon::SUNDAY);
        Carbon::setTestNow($sunday);
        $this->assertTrue($this->isOrderingAllowed(), 'Should allow ordering on Sunday');

        // Test weekday (should prevent)
        $monday = Carbon::now()->next(Carbon::MONDAY);
        Carbon::setTestNow($monday);
        $this->assertFalse($this->isOrderingAllowed(), 'Should prevent ordering on Monday');

        Carbon::setTestNow(); // Reset
    }

    /**
     * Helper to check if ordering is currently allowed.
     * Should be overridden by test classes that test ordering logic.
     */
    protected function isOrderingAllowed(): bool
    {
        // Default implementation - tests should override this
        return Carbon::now()->isWeekend();
    }

    /**
     * Assert database state matches expected structure.
     */
    protected function assertDatabaseStructure(): void
    {
        // Verify critical tables exist
        $this->assertTrue(
            DB::getSchemaBuilder()->hasTable('users'),
            'Users table should exist'
        );
        $this->assertTrue(
            DB::getSchemaBuilder()->hasTable('ingredients'),
            'Ingredients table should exist'
        );
    }

    /**
     * Measure and assert query performance.
     */
    protected function assertQueryPerformance(callable $callback, int $maxQueries = 10): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $callback();

        $queryCount = count(DB::getQueryLog());
        $this->assertLessThanOrEqual(
            $maxQueries,
            $queryCount,
            "Query count ({$queryCount}) exceeded maximum ({$maxQueries})"
        );

        DB::disableQueryLog();
    }

    /**
     * Assert memory usage stays within limits.
     */
    protected function assertMemoryUsage(int $maxMB = 10): void
    {
        $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024;
        $this->assertLessThan(
            $maxMB,
            $memoryUsage,
            "Memory usage ({$memoryUsage}MB) exceeded limit ({$maxMB}MB)"
        );
    }

    /**
     * Create test data quickly without database factories when appropriate.
     */
    protected function createTestData(string $type, array $data = []): array
    {
        $defaults = [
            'ingredient' => [
                'name' => 'Test Ingredient',
                'category' => 'proteins',
                'standard_portion_oz' => 4.0,
                'is_active' => true,
            ],
            'user' => [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '+15551234567',
                'phone_verified_at' => Carbon::now(),
            ],
        ];

        return array_merge($defaults[$type] ?? [], $data);
    }

    /**
     * Enable real events for integration testing.
     */
    protected function enableRealEvents(): void
    {
        Event::getFacadeRoot()->clearResolvedInstances();
    }

    /**
     * Enable real queues for integration testing.
     */
    protected function enableRealQueues(): void
    {
        Queue::getFacadeRoot()->clearResolvedInstances();
    }
}
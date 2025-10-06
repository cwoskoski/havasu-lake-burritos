<?php

declare(strict_types=1);

namespace Tests\Performance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Base class for performance tests.
 * Ensures mobile-first performance requirements are met.
 */
abstract class PerformanceTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Maximum acceptable response time for mobile (milliseconds).
     */
    protected int $maxMobileResponseTime = 500;

    /**
     * Maximum acceptable database query count per request.
     */
    protected int $maxQueriesPerRequest = 10;

    /**
     * Maximum acceptable memory usage (MB).
     */
    protected int $maxMemoryUsage = 32;

    /**
     * Setup performance monitoring.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Enable query logging for performance analysis
        DB::enableQueryLog();

        // Start memory usage tracking
        $this->startMemoryTracking();
    }

    /**
     * Tear down performance monitoring.
     */
    protected function tearDown(): void
    {
        $this->assertPerformanceMetrics();
        parent::tearDown();
    }

    /**
     * Start tracking memory usage.
     */
    protected function startMemoryTracking(): void
    {
        $this->initialMemory = memory_get_usage(true);
    }

    /**
     * Assert all performance metrics are within mobile-first requirements.
     */
    protected function assertPerformanceMetrics(): void
    {
        $this->assertQueryCount();
        $this->assertMemoryUsage();
    }

    /**
     * Assert database query count is optimized.
     */
    protected function assertQueryCount(): void
    {
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        $this->assertLessThanOrEqual(
            $this->maxQueriesPerRequest,
            $queryCount,
            "Too many database queries: {$queryCount}. Maximum allowed: {$this->maxQueriesPerRequest}. Queries: " .
            collect($queries)->pluck('query')->implode(', ')
        );
    }

    /**
     * Assert memory usage is within mobile-optimized limits.
     */
    protected function assertMemoryUsage(int $maxMB = null): void
    {
        $currentMemory = memory_get_usage(true);
        $memoryUsed = ($currentMemory - $this->initialMemory) / 1024 / 1024; // Convert to MB

        $maxLimit = $maxMB ?? $this->maxMemoryUsage;
        $this->assertLessThanOrEqual(
            $maxLimit,
            $memoryUsed,
            "Memory usage too high: {$memoryUsed}MB. Maximum allowed: {$maxLimit}MB"
        );
    }

    /**
     * Benchmark a closure and assert execution time.
     */
    protected function benchmarkExecution(callable $callback, int $maxTimeMs = null): float
    {
        $maxTimeMs = $maxTimeMs ?? $this->maxMobileResponseTime;

        $startTime = microtime(true);
        $callback();
        $endTime = microtime(true);

        $executionTimeMs = ($endTime - $startTime) * 1000;

        $this->assertLessThanOrEqual(
            $maxTimeMs,
            $executionTimeMs,
            "Execution time too slow: {$executionTimeMs}ms. Maximum allowed: {$maxTimeMs}ms"
        );

        return $executionTimeMs;
    }

    /**
     * Test API response times for mobile.
     */
    protected function assertMobileApiPerformance(string $endpoint, array $data = []): void
    {
        $startTime = microtime(true);

        $response = $this->postJson($endpoint, $data);

        $endTime = microtime(true);
        $responseTimeMs = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThanOrEqual(
            $this->maxMobileResponseTime,
            $responseTimeMs,
            "Mobile API response too slow: {$responseTimeMs}ms for {$endpoint}"
        );
    }

    /**
     * Test burrito builder performance under load.
     */
    protected function performBurritoBuilderLoadTest(int $concurrentUsers = 10): void
    {
        $tasks = [];

        for ($i = 0; $i < $concurrentUsers; $i++) {
            $tasks[] = function () {
                $this->assertMobileApiPerformance('/api/burrito-builder/start');
                $this->assertMobileApiPerformance('/api/ingredients/available');
                $this->assertMobileApiPerformance('/api/burrito/calculate-price', [
                    'ingredients' => ['chicken', 'spanish_rice', 'black_beans']
                ]);
            };
        }

        // Execute concurrent requests
        foreach ($tasks as $task) {
            $this->benchmarkExecution($task);
        }
    }

    /**
     * Test weekend production availability performance.
     */
    protected function assertWeekendAvailabilityPerformance(): void
    {
        $this->benchmarkExecution(function () {
            // Test getting current weekend availability
            $this->getJson('/api/weekend-availability');

            // Test checking remaining burrito count
            $this->getJson('/api/production/remaining-count');

            // Test validating order capacity
            $this->postJson('/api/orders/validate-capacity', [
                'burrito_count' => 2,
                'pickup_date' => now()->nextWeekend()->format('Y-m-d')
            ]);
        }, 200); // Tighter performance requirement for critical business logic
    }

    /**
     * Profile a specific operation and provide detailed metrics.
     */
    protected function profileOperation(string $operationName, callable $operation): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $startQueries = count(DB::getQueryLog());

        $result = $operation();

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $endQueries = count(DB::getQueryLog());

        $profile = [
            'operation' => $operationName,
            'execution_time_ms' => ($endTime - $startTime) * 1000,
            'memory_used_mb' => ($endMemory - $startMemory) / 1024 / 1024,
            'queries_executed' => $endQueries - $startQueries,
            'result' => $result,
        ];

        return $profile;
    }
}
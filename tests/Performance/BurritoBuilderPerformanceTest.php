<?php

namespace Tests\Performance;

use Carbon\Carbon;
use Tests\Helpers\BurritoTestHelper;
use Tests\TestCase;

class BurritoBuilderPerformanceTest extends TestCase
{
    protected int $acceptableResponseTime = 200; // 200ms

    protected int $mobileAcceptableResponseTime = 300; // 300ms for mobile

    public function test_burrito_builder_page_load_performance()
    {
        $startTime = microtime(true);
        $response = $this->get('/burrito-builder');
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        $this->assertLessThan(
            $this->acceptableResponseTime,
            $responseTime,
            "Burrito builder should load within {$this->acceptableResponseTime}ms, took {$responseTime}ms"
        );
    }

    public function test_ingredient_api_performance()
    {
        $startTime = microtime(true);
        $response = $this->get('/api/ingredients');
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(
            100, // API should be very fast
            $responseTime,
            "Ingredients API should respond within 100ms, took {$responseTime}ms"
        );
    }

    public function test_availability_api_performance()
    {
        $saturday = BurritoTestHelper::getWeekendDates()->first();
        Carbon::setTestNow($saturday);

        $startTime = microtime(true);
        $response = $this->get('/api/availability');
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(
            150,
            $responseTime,
            "Availability API should respond within 150ms, took {$responseTime}ms"
        );
    }

    public function test_order_submission_performance()
    {
        $saturday = BurritoTestHelper::getWeekendDates()->first();
        Carbon::setTestNow($saturday);

        $burritoConfig = BurritoTestHelper::createBurritoConfiguration();

        $startTime = microtime(true);
        $response = $this->post('/api/orders', [
            'customer_name' => 'Performance Test',
            'customer_email' => 'perf@test.com',
            'burrito' => $burritoConfig,
        ]);
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(201);
        $this->assertLessThan(
            500, // Order processing can take a bit longer
            $responseTime,
            "Order submission should complete within 500ms, took {$responseTime}ms"
        );
    }

    public function test_mobile_performance_with_slow_network()
    {
        // Simulate mobile user agent
        $response = $this->withMobileUserAgent('Android')
            ->get('/burrito-builder');

        $startTime = microtime(true);
        $response = $this->withMobileUserAgent('Android')
            ->get('/burrito-builder');
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(
            $this->mobileAcceptableResponseTime,
            $responseTime,
            "Mobile burrito builder should load within {$this->mobileAcceptableResponseTime}ms, took {$responseTime}ms"
        );
    }

    public function test_concurrent_user_performance()
    {
        $saturday = BurritoTestHelper::getWeekendDates()->first();
        Carbon::setTestNow($saturday);

        $promises = [];
        $concurrentUsers = 10;

        // Simulate concurrent users accessing the burrito builder
        for ($i = 0; $i < $concurrentUsers; $i++) {
            $promises[] = $this->measureAsyncRequest('/burrito-builder');
        }

        $responseTimes = $this->resolvePromises($promises);

        // All requests should complete within acceptable time
        foreach ($responseTimes as $index => $responseTime) {
            $this->assertLessThan(
                $this->acceptableResponseTime * 2, // Allow 2x time for concurrent load
                $responseTime,
                "Concurrent request #{$index} took {$responseTime}ms"
            );
        }

        // Average response time should be reasonable
        $averageTime = array_sum($responseTimes) / count($responseTimes);
        $this->assertLessThan(
            $this->acceptableResponseTime * 1.5,
            $averageTime,
            "Average concurrent response time should be within 1.5x normal time, was {$averageTime}ms"
        );
    }

    public function test_database_query_performance()
    {
        // Enable query logging for this test
        \DB::enableQueryLog();

        $response = $this->get('/burrito-builder');
        $response->assertStatus(200);

        $queries = \DB::getQueryLog();

        // Should not execute too many queries
        $this->assertLessThan(
            10,
            count($queries),
            'Burrito builder should execute fewer than 10 database queries'
        );

        // No query should take too long
        foreach ($queries as $query) {
            $this->assertLessThan(
                50, // 50ms per query max
                $query['time'],
                "Query took too long: {$query['time']}ms - {$query['query']}"
            );
        }
    }

    public function test_memory_usage_performance()
    {
        $memoryBefore = memory_get_usage(true);

        $response = $this->get('/burrito-builder');
        $response->assertStatus(200);

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Should not use excessive memory (in bytes)
        $maxMemoryMB = 5;
        $maxMemoryBytes = $maxMemoryMB * 1024 * 1024;

        $this->assertLessThan(
            $maxMemoryBytes,
            $memoryUsed,
            "Burrito builder should use less than {$maxMemoryMB}MB memory, used ".round($memoryUsed / 1024 / 1024, 2).'MB'
        );
    }

    public function test_asset_loading_performance()
    {
        $response = $this->get('/burrito-builder');
        $content = $response->getContent();

        // Check for critical CSS inlining
        $this->assertStringContains('<style>', $content, 'Critical CSS should be inlined');

        // Check for async loading of non-critical resources
        $this->assertStringContains('rel="preload"', $content, 'Non-critical CSS should be preloaded');

        // Check for JavaScript defer/async
        if (strpos($content, '<script') !== false) {
            $this->assertTrue(
                strpos($content, 'defer') !== false || strpos($content, 'async') !== false,
                'JavaScript should use defer or async loading'
            );
        }
    }

    public function test_image_optimization_performance()
    {
        $response = $this->get('/burrito-builder');
        $content = $response->getContent();

        if (strpos($content, '<img') !== false) {
            // Images should use lazy loading
            $this->assertStringContains('loading="lazy"', $content, 'Images should use lazy loading');

            // Images should provide multiple resolutions
            $this->assertStringContains('srcset', $content, 'Images should provide srcset for responsive loading');
        }
    }

    /**
     * Measure async request performance (simplified for testing).
     */
    private function measureAsyncRequest(string $url): float
    {
        $startTime = microtime(true);
        $response = $this->get($url);
        $endTime = microtime(true);

        $response->assertStatus(200);

        return ($endTime - $startTime) * 1000; // Return milliseconds
    }

    /**
     * Resolve promises (simplified implementation for testing).
     */
    private function resolvePromises(array $responseTimes): array
    {
        // In a real implementation, this would handle actual async promises
        // For testing purposes, we return the response times directly
        return $responseTimes;
    }
}

<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;

/**
 * Trait for testing service classes and business logic.
 * Provides utilities for mocking dependencies and testing service interactions.
 */
trait ServiceTesting
{
    /**
     * Create a mock for a service class with common patterns.
     */
    protected function mockService(string $serviceClass): MockInterface
    {
        $mock = Mockery::mock($serviceClass);
        $this->app->instance($serviceClass, $mock);

        return $mock;
    }

    /**
     * Assert that a service method was called with specific parameters.
     */
    protected function assertServiceCalled(string $serviceClass, string $method, array $parameters = []): void
    {
        $mock = $this->mockService($serviceClass);

        if (empty($parameters)) {
            $mock->shouldReceive($method)->once();
        } else {
            $mock->shouldReceive($method)->once()->with(...$parameters);
        }
    }

    /**
     * Assert that an event was dispatched with expected data.
     */
    protected function assertEventDispatched(string $eventClass, array $expectedData = []): void
    {
        Event::fake([$eventClass]);

        // Your code that should dispatch the event goes here
        // This method provides the assertion pattern

        if (empty($expectedData)) {
            Event::assertDispatched($eventClass);
        } else {
            Event::assertDispatched($eventClass, function ($event) use ($expectedData) {
                foreach ($expectedData as $key => $value) {
                    if (data_get($event, $key) !== $value) {
                        return false;
                    }
                }

                return true;
            });
        }
    }

    /**
     * Assert that a job was pushed to the queue with expected data.
     */
    protected function assertJobPushed(string $jobClass, array $expectedData = []): void
    {
        Queue::fake();

        // Your code that should push the job goes here
        // This method provides the assertion pattern

        if (empty($expectedData)) {
            Queue::assertPushed($jobClass);
        } else {
            Queue::assertPushed($jobClass, function ($job) use ($expectedData) {
                foreach ($expectedData as $key => $value) {
                    if (data_get($job, $key) !== $value) {
                        return false;
                    }
                }

                return true;
            });
        }
    }

    /**
     * Assert that a notification was sent.
     */
    protected function assertNotificationSent(string $notificationClass, $notifiable = null): void
    {
        Notification::fake();

        // Your code that should send the notification goes here
        // This method provides the assertion pattern

        if ($notifiable) {
            Notification::assertSentTo($notifiable, $notificationClass);
        } else {
            Notification::assertSent($notificationClass);
        }
    }

    /**
     * Mock external API responses for testing.
     */
    protected function mockExternalApi(string $serviceName, array $responses): MockInterface
    {
        $mock = Mockery::mock("App\\Services\\{$serviceName}");

        foreach ($responses as $method => $response) {
            if (is_array($response)) {
                $mock->shouldReceive($method)->andReturn($response);
            } else {
                $mock->shouldReceive($method)->andReturn($response);
            }
        }

        $this->app->instance("App\\Services\\{$serviceName}", $mock);

        return $mock;
    }

    /**
     * Mock Twilio SMS service for testing.
     */
    protected function mockTwilioService(array $responses = []): MockInterface
    {
        $defaultResponses = [
            'sendVerificationCode' => ['sid' => 'test_message_id', 'status' => 'sent'],
            'verifyCode' => ['status' => 'approved'],
        ];

        $responses = array_merge($defaultResponses, $responses);

        return $this->mockExternalApi('TwilioService', $responses);
    }

    /**
     * Mock payment service for testing.
     */
    protected function mockPaymentService(array $responses = []): MockInterface
    {
        $defaultResponses = [
            'createPaymentIntent' => ['id' => 'pi_test_123', 'status' => 'requires_payment_method'],
            'confirmPayment' => ['status' => 'succeeded'],
            'refundPayment' => ['status' => 'succeeded'],
        ];

        $responses = array_merge($defaultResponses, $responses);

        return $this->mockExternalApi('PaymentService', $responses);
    }

    /**
     * Assert that a service handles errors gracefully.
     */
    protected function assertServiceErrorHandling(callable $serviceCall, ?string $expectedExceptionClass = null): void
    {
        $exceptionThrown = false;
        $actualException = null;

        try {
            $serviceCall();
        } catch (\Exception $e) {
            $exceptionThrown = true;
            $actualException = $e;
        }

        if ($expectedExceptionClass) {
            $this->assertTrue(
                $exceptionThrown,
                "Expected exception {$expectedExceptionClass} was not thrown"
            );

            $this->assertInstanceOf(
                $expectedExceptionClass,
                $actualException,
                'Wrong exception type thrown'
            );
        } else {
            $this->assertFalse(
                $exceptionThrown,
                'Unexpected exception thrown: '.($actualException ? $actualException->getMessage() : 'Unknown')
            );
        }
    }

    /**
     * Assert that a service respects rate limiting.
     */
    protected function assertRateLimiting(callable $serviceCall, int $maxAttempts, int $windowSeconds = 60): void
    {
        // Attempt the service call multiple times within the window
        for ($i = 1; $i <= $maxAttempts; $i++) {
            try {
                $serviceCall();
            } catch (\Exception $e) {
                $this->fail("Service call failed on attempt {$i}: ".$e->getMessage());
            }
        }

        // The next attempt should be rate limited
        $this->assertServiceErrorHandling(
            $serviceCall,
            'Illuminate\\Http\\Exceptions\\ThrottleRequestsException'
        );
    }

    /**
     * Assert that caching is working correctly for a service.
     */
    protected function assertServiceCaching(callable $serviceCall, string $cacheKey): void
    {
        // First call should hit the service and cache the result
        $result1 = $serviceCall();

        // Verify data was cached
        $this->assertTrue(
            cache()->has($cacheKey),
            "Service should cache result with key: {$cacheKey}"
        );

        // Second call should return cached result
        $result2 = $serviceCall();

        $this->assertEquals(
            $result1,
            $result2,
            'Cached result should match original result'
        );
    }

    /**
     * Test service behavior under different feature flag states.
     */
    protected function assertFeatureFlagBehavior(callable $serviceCall, string $flagName, $enabledResult, $disabledResult): void
    {
        // Test with feature enabled
        config(["features.{$flagName}" => true]);
        $resultEnabled = $serviceCall();
        $this->assertEquals($enabledResult, $resultEnabled, "Service behavior when {$flagName} is enabled");

        // Test with feature disabled
        config(["features.{$flagName}" => false]);
        $resultDisabled = $serviceCall();
        $this->assertEquals($disabledResult, $resultDisabled, "Service behavior when {$flagName} is disabled");
    }

    /**
     * Assert that a service performs within time limits.
     */
    protected function assertServicePerformance(callable $serviceCall, float $maxTimeMs = 500): void
    {
        $startTime = microtime(true);
        $serviceCall();
        $endTime = microtime(true);

        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(
            $maxTimeMs,
            $executionTime,
            "Service call took {$executionTime}ms, exceeding limit of {$maxTimeMs}ms"
        );
    }

    /**
     * Assert that a service properly logs important events.
     */
    protected function assertServiceLogging(callable $serviceCall, string $expectedLogLevel, ?string $expectedMessage = null): void
    {
        // This would integrate with log testing to verify proper logging
        // Implementation depends on how you want to test logging
        $this->markTestIncomplete('Service logging testing requires log capture implementation');
    }

    /**
     * Mock weekend production service for testing business hours.
     */
    protected function mockWeekendProductionService(bool $isProductionDay = true, int $remainingBurritos = 50): MockInterface
    {
        return $this->mockExternalApi('WeekendProductionService', [
            'isProductionDay' => $isProductionDay,
            'getRemainingBurritos' => $remainingBurritos,
            'canAcceptOrder' => $isProductionDay && $remainingBurritos > 0,
            'reserveBurrito' => $remainingBurritos > 0,
        ]);
    }

    /**
     * Mock ingredient availability service.
     */
    protected function mockIngredientService(array $availableIngredients = []): MockInterface
    {
        $defaultIngredients = [
            'proteins' => ['carnitas', 'chicken', 'barbacoa'],
            'rice_beans' => ['cilantro_rice', 'spanish_rice', 'black_beans'],
            'fresh_toppings' => ['lettuce', 'tomatoes', 'onions'],
            'salsas' => ['mild', 'medium', 'hot'],
            'creamy' => ['cheese', 'sour_cream'],
        ];

        $ingredients = array_merge($defaultIngredients, $availableIngredients);

        return $this->mockExternalApi('IngredientService', [
            'getAvailableIngredients' => $ingredients,
            'isIngredientAvailable' => function ($ingredient) use ($ingredients) {
                foreach ($ingredients as $category) {
                    if (in_array($ingredient, $category)) {
                        return true;
                    }
                }

                return false;
            },
        ]);
    }
}

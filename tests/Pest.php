<?php

use App\Enums\IngredientCategory;
use App\Models\Ingredient;
use App\Models\User;
use Carbon\Carbon;
use Tests\Helpers\BurritoTestHelper;

/*
|--------------------------------------------------------------------------
| Test Case Configuration
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

// Feature tests with database refresh
pest()->extend(Tests\TestCase::class)
    ->use(
        Illuminate\Foundation\Testing\RefreshDatabase::class,
        Tests\Traits\MobileTesting::class,
        Tests\Traits\WeekendProductionTesting::class
    )
    ->in('Feature');

// Unit tests - fast execution, no database by default
pest()->extend(Tests\TestCase::class)
    ->in('Unit');

// Integration tests with database and external service mocking
pest()->extend(Tests\Integration\IntegrationTestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Integration');

// Browser tests with Dusk
pest()->extend(Tests\Browser\BrowserTestCase::class)
    ->use(Laravel\Dusk\Concerns\ProvidesBrowser::class)
    ->in('Browser');

// Performance tests with specialized setup
pest()->extend(Tests\Performance\PerformanceTestCase::class)
    ->in('Performance');

/*
|--------------------------------------------------------------------------
| Custom Expectations
|--------------------------------------------------------------------------
|
| Domain-specific expectations for burrito ordering platform testing.
| These expectations make tests more readable and maintainable.
|
*/

// Basic expectations
expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

// Burrito domain expectations
expect()->extend('toBeValidIngredientCategory', function () {
    return $this->toBeIn(IngredientCategory::values());
});

expect()->extend('toBeWeekendDate', function () {
    return $this->toBeInstanceOf(Carbon::class)
        ->and($this->value->isWeekend())->toBeTrue('Expected date to be a weekend');
});

expect()->extend('toBeWeekdayDate', function () {
    return $this->toBeInstanceOf(Carbon::class)
        ->and($this->value->isWeekday())->toBeTrue('Expected date to be a weekday');
});

expect()->extend('toHaveValidPortionSize', function () {
    return $this->toBeGreaterThan(0)
        ->and($this->value)->toBeLessThanOrEqual(2.0); // Max 2 cups/tbsp
});

expect()->extend('toBeMobileOptimized', function () {
    $response = $this->value;
    $response->assertSee('viewport', false);
    $response->assertSee('width=device-width', false);

    return $this;
});

expect()->extend('toHaveValidPhoneNumber', function () {
    return $this->toMatch('/^\+?[1-9]\d{1,14}$/'); // E.164 format
});

expect()->extend('toBeBefore', function ($date) {
    return $this->toBeLessThan($date);
});

expect()->extend('toBeAfter', function ($date) {
    return $this->toBeGreaterThan($date);
});

/*
|--------------------------------------------------------------------------
| Global Test Functions
|--------------------------------------------------------------------------
|
| Helper functions that reduce boilerplate and make tests more readable.
| These functions are available in all test files.
|
*/

/**
 * Create a verified user for testing.
 */
function createVerifiedUser(array $attributes = []): User
{
    return User::factory()
        ->verified()
        ->create($attributes);
}

/**
 * Create an ingredient with specific category.
 */
function createIngredient(IngredientCategory $category, array $attributes = []): Ingredient
{
    return Ingredient::factory()
        ->withCategory($category)
        ->create($attributes);
}

/**
 * Create a complete ingredient set for burrito building.
 */
function createIngredientSet(): array
{
    $ingredients = [];

    foreach (IngredientCategory::cases() as $category) {
        $ingredients[$category->value] = Ingredient::factory()
            ->withCategory($category)
            ->count(3)
            ->create()
            ->toArray();
    }

    return $ingredients;
}

/**
 * Travel to a weekend date for testing.
 */
function travelToWeekend(int $weeksFromNow = 0): Carbon
{
    $saturday = BurritoTestHelper::getWeekendDates($weeksFromNow)->first();
    Carbon::setTestNow($saturday);

    return $saturday;
}

/**
 * Travel to a weekday date for testing.
 */
function travelToWeekday(int $weeksFromNow = 0): Carbon
{
    $monday = BurritoTestHelper::getWeekdayDates($weeksFromNow)->first();
    Carbon::setTestNow($monday);

    return $monday;
}

/**
 * Mock Twilio SMS service for testing.
 */
function mockTwilioService(): void
{
    // Mock the Twilio client to prevent actual SMS sending
    $mock = Mockery::mock('alias:Twilio\Rest\Client');
    $mock->shouldReceive('messages')
        ->andReturn(Mockery::mock([
            'create' => Mockery::mock(['sid' => 'test_message_sid']),
        ]));
}

/**
 * Assert response has mobile-first design elements.
 */
function assertMobileFirst($response): void
{
    $response->assertSee('viewport', false);
    $response->assertSee('width=device-width', false);
    $response->assertSee('initial-scale=1', false);
}

/**
 * Assert weekend-only business logic.
 */
function assertWeekendOnly(callable $businessLogic): void
{
    // Test on weekend - should work
    travelToWeekend();
    expect($businessLogic())->toBeTrue();

    // Test on weekday - should fail
    travelToWeekday();
    expect($businessLogic())->toBeFalse();

    Carbon::setTestNow(); // Reset
}

/**
 * Get test data for different mobile devices.
 */
function getMobileTestData(): array
{
    return [
        'iPhone SE' => ['width' => 375, 'height' => 667, 'user_agent' => 'iPhone'],
        'iPhone Pro' => ['width' => 390, 'height' => 844, 'user_agent' => 'iPhone'],
        'Android' => ['width' => 360, 'height' => 800, 'user_agent' => 'Android'],
        'iPad' => ['width' => 768, 'height' => 1024, 'user_agent' => 'iPad'],
    ];
}

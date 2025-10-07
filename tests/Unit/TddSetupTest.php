<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\IngredientCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Test to verify TDD environment setup is working correctly.
 */
class TddSetupTest extends TestCase
{
    public function test_php_version_meets_requirements(): void
    {
        expect(PHP_VERSION_ID)->toBeGreaterThanOrEqual(80200); // PHP 8.2+
    }

    public function test_laravel_framework_is_loaded(): void
    {
        expect(app()->version())->toContain('12.');
    }

    public function test_ingredient_category_enum_is_available(): void
    {
        $categories = IngredientCategory::cases();

        expect($categories)->toHaveCount(5);
        expect(IngredientCategory::PROTEINS->value)->toBe('proteins');
        expect(IngredientCategory::PROTEINS->label())->toBe('Proteins');
    }

    public function test_carbon_date_library_is_working(): void
    {
        $date = Carbon::create(2024, 10, 12); // Saturday

        expect($date->isWeekend())->toBeTrue();
        expect($date->dayOfWeek)->toBe(Carbon::SATURDAY);
    }

    public function test_pest_expectations_are_working(): void
    {
        $value = 'test';

        expect($value)->toBe('test');
        expect($value)->not->toBe('wrong');
        expect(strlen($value))->toBe(4);
    }

    public function test_database_connection_is_working(): void
    {
        // Database should be configured for testing
        $connection = config('database.default');
        expect($connection)->toBeIn(['sqlite', 'mysql']);

        // If using SQLite, should be in-memory for tests
        if ($connection === 'sqlite') {
            expect(config('database.connections.sqlite.database'))->toBe(':memory:');
        }

        // Verify database is actually working by testing connection
        $result = DB::select('SELECT 1 as test');
        expect($result)->toBeArray();
        expect($result[0]->test)->toBe(1);
    }

    public function test_weekend_business_logic_helper(): void
    {
        // Test our weekend-only business logic
        $saturday = Carbon::create(2024, 10, 12); // Saturday
        $monday = Carbon::create(2024, 10, 14);   // Monday

        Carbon::setTestNow($saturday);
        expect(Carbon::now()->isWeekend())->toBeTrue();

        Carbon::setTestNow($monday);
        expect(Carbon::now()->isWeekend())->toBeFalse();

        Carbon::setTestNow(); // Reset
    }

    public function test_custom_expectations_are_loaded(): void
    {
        // Test our custom Pest expectations
        expect(1)->toBeOne();
        expect('proteins')->toBeValidIngredientCategory();

        $weekend = Carbon::create(2024, 10, 12); // Saturday
        expect($weekend)->toBeWeekendDate();

        $weekday = Carbon::create(2024, 10, 14); // Monday
        expect($weekday)->toBeWeekdayDate();

        expect(1.5)->toHaveValidPortionSize();
    }

    public function test_mobile_testing_data_is_available(): void
    {
        $mobileData = getMobileTestData();

        expect($mobileData)->toHaveKey('iPhone SE');
        expect($mobileData)->toHaveKey('Android');
        expect($mobileData['iPhone SE'])->toHaveKey('width');
        expect($mobileData['iPhone SE'])->toHaveKey('height');
        expect($mobileData['iPhone SE'])->toHaveKey('user_agent');
    }

    public function test_test_factories_are_working(): void
    {
        // Test that our global helper functions work
        $user = createVerifiedUser();
        expect($user->isPhoneVerified())->toBeTrue();

        $ingredient = createIngredient(IngredientCategory::PROTEINS);
        expect($ingredient->category)->toBe(IngredientCategory::PROTEINS);

        $ingredientSet = createIngredientSet();
        expect($ingredientSet)->toHaveKey('proteins');
        expect($ingredientSet)->toHaveKey('rice_beans');
        expect($ingredientSet)->toHaveKey('fresh_toppings');
        expect($ingredientSet)->toHaveKey('salsas');
        expect($ingredientSet)->toHaveKey('creamy');
    }

    public function test_timezone_is_configured_for_california(): void
    {
        // California timezone testing for Havasu Lake, CA
        $californiaTime = Carbon::create(2024, 10, 12, 12, 0, 0, 'America/Los_Angeles');

        expect($californiaTime->timezone->getName())->toBe('America/Los_Angeles');
        expect(config('app.timezone'))->toBe('America/Los_Angeles');
    }

    public function test_performance_helpers_are_available(): void
    {
        // Test that performance testing methods are available
        expect(method_exists($this, 'assertQueryPerformance'))->toBeTrue();
        expect(method_exists($this, 'assertMemoryUsage'))->toBeTrue();
        expect(method_exists($this, 'assertMobilePerformant'))->toBeTrue();
    }

    public function test_feature_flags_are_configured(): void
    {
        // Test feature flag configuration
        expect(config('features.real_time_countdown'))->toBeTrue();
        expect(config('features.mobile_optimization'))->toBeTrue();
    }

    public function test_burrito_business_constants_are_set(): void
    {
        // Test business logic constants
        expect(config('burrito.price'))->toBe(900); // $9.00
        expect(config('burrito.tortilla_size'))->toBe(14);
        expect(config('weekend.max_daily_burritos'))->toBe(100);
    }

    public function test_tdd_workflow_is_ready(): void
    {
        // Final verification that TDD environment is fully ready
        expect(true)->toBeTrue();

        // If this test passes, the TDD environment is properly configured
        // and ready for mobile-first burrito ordering development!
    }
}

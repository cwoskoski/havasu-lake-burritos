# Test Automation Framework

Comprehensive test automation setup for Havasu Lake Burritos - a mobile-first burrito ordering platform.

## Quick Start

```bash
# Run all tests
./vendor/bin/sail test

# Run specific test suites
./vendor/bin/sail test --testsuite=Unit
./vendor/bin/sail test --testsuite=Feature
./vendor/bin/sail test --testsuite=Integration
./vendor/bin/sail test --testsuite=Browser
./vendor/bin/sail test --testsuite=Performance

# Run tests with coverage
./vendor/bin/sail test --coverage --min=80

# Run tests in parallel
./vendor/bin/sail test --parallel
```

## Test Structure

### 1. Unit Tests (`tests/Unit/`)
- Pure unit tests with no external dependencies
- Test individual classes and methods in isolation
- Focus on business logic and calculations
- **Target**: < 10ms execution time per test

### 2. Feature Tests (`tests/Feature/`)
- HTTP-level testing of application features
- Test complete user workflows
- Database interactions with RefreshDatabase
- **Target**: < 100ms execution time per test

### 3. Integration Tests (`tests/Integration/`)
- End-to-end testing of business processes
- Multi-component interactions
- Weekend production scheduling
- Inventory management
- **Target**: < 500ms execution time per test

### 4. Browser Tests (`tests/Browser/`)
- Laravel Dusk browser automation
- Mobile-first UI testing
- Touch target compliance
- Real user interaction simulation
- **Target**: < 30s execution time per test

### 5. Performance Tests (`tests/Performance/`)
- Response time validation
- Memory usage monitoring
- Mobile network simulation
- Concurrent user testing
- **Target**: Meets performance thresholds

## Business Logic Testing

### Weekend Production Schedule
```php
use Tests\Traits\WeekendProductionTesting;

class MyTest extends TestCase
{
    use WeekendProductionTesting;

    public function test_weekend_ordering()
    {
        $this->assertOrderingOnlyOnWeekends();
        $this->assertDailyLimitsEnforced(100);
        $this->assertProductionResetsDaily();
    }
}
```

### Mobile Testing
```php
use Tests\Traits\MobileTesting;

class MyTest extends TestCase
{
    use MobileTesting;

    public function test_mobile_experience()
    {
        $response = $this->withMobileUserAgent('iPhone')
            ->get('/burrito-builder');

        $this->assertMobileViewport($response);
        $this->assertTouchTargetSizes();
        $this->assertSingleHandedOperation();
    }
}
```

## Test Helpers

### BurritoTestHelper
```php
// Create standard ingredient sets
$ingredients = BurritoTestHelper::createIngredientSet();

// Generate burrito configurations
$config = BurritoTestHelper::createBurritoConfiguration();

// Calculate portions
$portions = BurritoTestHelper::calculatePortions($config);

// Get weekend/weekday dates
$weekends = BurritoTestHelper::getWeekendDates();
$weekdays = BurritoTestHelper::getWeekdayDates();
```

### Test Data Management
```php
// Use TestDataSeeder for consistent test data
$this->seed(TestDataSeeder::class);

// Create specific scenarios
$seeder = new TestDataSeeder();
$seeder->createWeekendScenario();
$seeder->createSoldOutScenario();
```

## Pest Testing Framework

Using Pest PHP for expressive testing:

```php
// Unit test with Pest
test('proteins have standard 0.5 cup portions')
    ->expect(createIngredientSet()['proteins'])
    ->each->toHaveStandardPortion(0.5, 'cup');

// Feature test with Pest
test('burrito builder loads on mobile')
    ->withMobileUserAgent('iPhone')
    ->get('/burrito-builder')
    ->assertStatus(200)
    ->and(fn($response) => assertMobileOptimized($response));
```

## Performance Standards

### Response Times
- **Unit Tests**: < 10ms per test
- **Feature Tests**: < 100ms per test
- **API Endpoints**: < 150ms
- **Page Loads**: < 200ms desktop, < 300ms mobile
- **Order Processing**: < 500ms

### Coverage Requirements
- **Minimum Coverage**: 80%
- **Critical Business Logic**: 95%
- **Controller Actions**: 90%
- **Model Methods**: 85%

### Mobile Performance
- **First Paint**: < 2s on 3G
- **Interactive**: < 3s on mobile
- **Touch Targets**: ≥ 44px × 44px
- **Thumb Reach**: Primary actions in bottom 50% of screen

## Test Execution Strategies

### Local Development
```bash
# Fast feedback loop
./vendor/bin/sail test --testsuite=Unit

# Feature development
./vendor/bin/sail test --testsuite=Feature --filter=BurritoBuilder

# Mobile testing
./vendor/bin/sail dusk tests/Browser/BurritoBuilderBrowserTest.php
```

### CI/CD Pipeline
- **Parallel Execution**: Multiple test suites run simultaneously
- **Fast Failure**: Fails fast on critical issues
- **Progressive Testing**: Unit → Feature → Integration → Browser
- **Performance Gates**: Blocks deployment if performance degrades

### Test Data Isolation
```php
// Each test gets fresh database
use RefreshDatabase;

// Consistent timezone
date_default_timezone_set('America/Phoenix');

// Weekend testing scenario
Carbon::setTestNow(getWeekendDates()->first());
```

## Quality Gates

### Pre-commit Checks
```bash
# Code style
./vendor/bin/pint --test

# Static analysis
./vendor/bin/phpstan analyse

# Unit tests
./vendor/bin/sail test --testsuite=Unit
```

### CI/CD Quality Gates
1. **Code Style**: Laravel Pint compliance
2. **Static Analysis**: PHPStan level 8
3. **Unit Tests**: 100% pass rate
4. **Feature Tests**: 100% pass rate
5. **Coverage**: ≥ 80% overall
6. **Performance**: Within defined thresholds
7. **Security**: No vulnerabilities

## Browser Testing Setup

### Mobile Device Simulation
```php
// Test multiple device sizes
$browser->resize(375, 667); // iPhone SE
$browser->resize(414, 896); // iPhone 11
$browser->resize(360, 640); // Android

// Test touch interactions
$browser->tap('@ingredient-button');
$browser->swipe('.carousel', 'left');
```

### Network Conditions
```php
// Simulate slow 3G
$browser->driver->getCommandExecutor()->execute([
    'cmd' => 'Network.emulateNetworkConditions',
    'params' => [
        'latency' => 300,
        'downloadThroughput' => 1.5 * 1024 * 1024 / 8,
        'uploadThroughput' => 750 * 1024 / 8,
    ]
]);
```

## Debugging Tests

### Test Debugging
```bash
# Run single test with verbose output
./vendor/bin/sail test --filter=test_burrito_builder_loads --verbose

# Debug browser tests
./vendor/bin/sail dusk --browse

# View test coverage
./vendor/bin/sail test --coverage-html=build/coverage
```

### Performance Profiling
```php
// Memory usage tracking
$memoryBefore = memory_get_usage(true);
// ... test code ...
$memoryAfter = memory_get_usage(true);

// Query logging
DB::enableQueryLog();
// ... test code ...
$queries = DB::getQueryLog();
```

## Continuous Integration

The test automation pipeline runs on every commit and provides:

- **Fast Feedback**: Results within 5 minutes
- **Parallel Execution**: Multiple test suites simultaneously
- **Mobile Testing**: Real device simulation
- **Performance Monitoring**: Regression detection
- **Coverage Reports**: Track coverage trends
- **Quality Metrics**: Code quality scoring

## Best Practices

### Test Organization
- Group related tests in same file
- Use descriptive test names
- Follow AAA pattern (Arrange, Act, Assert)
- Keep tests independent and isolated

### Mobile-First Testing
- Always test mobile experience first
- Verify touch target sizes
- Test on real devices when possible
- Simulate various network conditions

### Business Logic Testing
- Test weekend-only ordering thoroughly
- Verify daily burrito limits
- Test ingredient availability tracking
- Validate portion calculations

### Performance Testing
- Set realistic thresholds
- Test under various conditions
- Monitor trends over time
- Fail builds on regressions

This test automation framework ensures reliable, fast feedback for the mobile-first burrito ordering platform while maintaining high code quality and performance standards.
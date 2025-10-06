# Feature Flags System

This Laravel application includes a comprehensive feature flags system designed for trunk-based development. It allows you to safely deploy incomplete features while keeping the main branch deployable.

## Overview

The feature flags system provides:
- Environment-based configuration
- Simple boolean flags and complex configurations
- Helper functions for easy usage in controllers and views
- Custom Blade directives
- Artisan command for managing flags
- Full test coverage

## Configuration

### Basic Setup

Feature flags are configured in `config/features.php` and can be overridden via environment variables in your `.env` file.

Example `.env` configuration:
```bash
# Enable new burrito builder
FEATURE_BURRITO_BUILDER_V2=true

# Enable SMS notifications
FEATURE_SMS_NOTIFICATIONS=true

# Payment method settings
FEATURE_PAYMENT_STRIPE=true
FEATURE_PAYMENT_CASH=true
```

### Feature Categories

The system organizes features into logical categories:
- **Core Application Features**: Authentication, profiles, checkout
- **Burrito Builder Features**: Builder interface, tracking, calculations
- **Ordering Features**: Inventory, scheduling, production limits
- **Payment Features**: Payment methods and flows
- **Administrative Features**: Admin panels and management tools
- **Mobile App Features**: Push notifications, offline mode

## Usage

### In Controllers

```php
use App\Services\FeatureService;

class BurritoController extends Controller
{
    public function __construct(
        private readonly FeatureService $featureService
    ) {}

    public function index()
    {
        // Simple boolean check
        if (config('features.burrito_builder_v2')) {
            return view('burrito.builder-v2');
        }

        // Using helper function
        if (feature('nutritional_info')) {
            $data['nutrition'] = $this->getNutritionalData();
        }

        // Using service method
        if ($this->featureService->enabled('ingredient_tracking')) {
            $this->trackIngredientUsage();
        }

        // Check multiple features
        $hasAdvancedFeatures = $this->featureService->allEnabled([
            'nutritional_info',
            'allergen_warnings',
            'portion_calculator'
        ]);

        return view('burrito.builder', compact('data', 'hasAdvancedFeatures'));
    }
}
```

### In Blade Templates

```blade
{{-- Simple feature check --}}
@feature('burrito_builder_v2')
    <div class="new-builder">
        <!-- New builder interface -->
    </div>
@else
    <div class="classic-builder">
        <!-- Current builder -->
    </div>
@endfeature

{{-- Using config helper --}}
@if(config('features.nutritional_info'))
    <div class="nutrition-panel">
        <!-- Nutritional information -->
    </div>
@endif

{{-- Check multiple features --}}
@featureall(['allergen_warnings', 'nutritional_info'])
    <div class="health-warnings">
        <!-- Show when both features are enabled -->
    </div>
@endfeatureall

{{-- Check any feature enabled --}}
@featureany(['push_notifications', 'sms_notifications'])
    <div class="notifications-panel">
        <!-- Show if any notification method is available -->
    </div>
@endfeatureany

{{-- Complex feature configuration --}}
@if(config('features.payment_methods.stripe'))
    <button class="stripe-pay">Pay with Stripe</button>
@endif
```

### Helper Functions

The system provides convenient helper functions:

```php
// Check if feature is enabled
if (feature('new_payment_flow')) {
    // Feature is enabled
}

// Check if feature is disabled
if (feature_disabled('old_feature')) {
    // Feature is disabled
}

// Get complex feature configuration
$paymentConfig = feature_config('payment_methods');
if ($paymentConfig['stripe']) {
    // Stripe is enabled
}

// Check multiple features
if (features_enabled(['feature1', 'feature2'])) {
    // All features are enabled
}

if (any_feature_enabled(['feature1', 'feature2'])) {
    // At least one feature is enabled
}
```

### Complex Features

Some features support complex configurations:

```php
// Payment methods configuration
$paymentMethods = $featureService->getEnabledPaymentMethods();
// Returns: ['stripe' => true, 'cash' => true] or []

// Production schedule
$schedule = $featureService->getProductionSchedule();
// Returns: [
//     'enabled' => true,
//     'saturday_capacity' => 50,
//     'sunday_capacity' => 50,
//     'advance_order_days' => 7
// ]

// SMS settings
$smsSettings = $featureService->getSmsSettings();
// Returns: [
//     'enabled' => true,
//     'verification_required' => true,
//     'order_confirmations' => true,
//     'pickup_reminders' => true,
//     'marketing_messages' => false
// ]
```

## Artisan Commands

### List Features

```bash
# List all features
./vendor/bin/sail artisan features:list

# Show only enabled features
./vendor/bin/sail artisan features:list --enabled

# Show only disabled features
./vendor/bin/sail artisan features:list --disabled

# Show only complex features
./vendor/bin/sail artisan features:list --complex
```

## Environment-Based Feature Sets

You can enable groups of features based on environment:

```bash
# Enable all development features
FEATURE_DEVELOPMENT_MODE=true

# Enable staging features
FEATURE_STAGING_MODE=true

# Enable beta features
FEATURE_BETA_MODE=true
```

Check these modes in your code:
```php
if ($featureService->isDevelopmentMode()) {
    // Development features active
}
```

## Testing

The feature flags system includes comprehensive tests:

```bash
# Run unit tests
./vendor/bin/sail artisan test tests/Unit/FeatureServiceTest.php

# Run integration tests
./vendor/bin/sail artisan test tests/Feature/FeatureFlagsTest.php
```

## Best Practices

### Trunk-Based Development

1. **Small increments**: Use feature flags to deploy incomplete features
2. **Frequent commits**: Commit to main branch multiple times per day
3. **Feature toggles**: Hide incomplete features behind flags
4. **Gradual rollout**: Enable features incrementally

### Naming Conventions

- Use descriptive, consistent names: `burrito_builder_v2`, `new_payment_flow`
- Group related features: `admin_*`, `payment_*`, `mobile_*`
- Use past tense for completed features: `phone_verification_added`

### Environment Configuration

- **Development**: Enable new features for testing
- **Staging**: Mirror production with select beta features
- **Production**: Conservative feature enablement

### Cleanup

Remove feature flags once features are stable and fully deployed:

1. Remove from `config/features.php`
2. Remove environment variables
3. Remove conditional code
4. Update tests

## Examples

See `/resources/views/examples/feature-flags-demo.blade.php` for comprehensive usage examples.

See `/app/Http/Controllers/ExampleFeatureController.php` for controller examples.
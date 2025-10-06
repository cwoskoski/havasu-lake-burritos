<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains feature flags for the Havasu Lake Burritos application.
    | Feature flags allow us to safely deploy incomplete features while using
    | trunk-based development. Each flag can be controlled via environment
    | variables for different deployment environments.
    |
    | Usage in Controllers:
    | if (config('features.new_payment_flow')) { ... }
    |
    | Usage in Blade Templates:
    | @if(config('features.burrito_builder_v2'))
    |     <!-- New feature -->
    | @endif
    |
    | Usage with Helper:
    | if (feature('new_payment_flow')) { ... }
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Core Application Features
    |--------------------------------------------------------------------------
    */

    'phone_authentication' => env('FEATURE_PHONE_AUTH', true),
    'sms_notifications' => env('FEATURE_SMS_NOTIFICATIONS', true),
    'guest_checkout' => env('FEATURE_GUEST_CHECKOUT', false),
    'user_profiles' => env('FEATURE_USER_PROFILES', true),

    /*
    |--------------------------------------------------------------------------
    | Burrito Builder Features
    |--------------------------------------------------------------------------
    */

    'burrito_builder_v2' => env('FEATURE_BURRITO_BUILDER_V2', false),
    'ingredient_tracking' => env('FEATURE_INGREDIENT_TRACKING', true),
    'portion_calculator' => env('FEATURE_PORTION_CALCULATOR', false),
    'nutritional_info' => env('FEATURE_NUTRITIONAL_INFO', false),
    'allergen_warnings' => env('FEATURE_ALLERGEN_WARNINGS', false),

    /*
    |--------------------------------------------------------------------------
    | Ordering Features
    |--------------------------------------------------------------------------
    */

    'real_time_inventory' => env('FEATURE_REAL_TIME_INVENTORY', false),
    'order_scheduling' => env('FEATURE_ORDER_SCHEDULING', false),
    'production_limits' => env('FEATURE_PRODUCTION_LIMITS', true),
    'weekend_only_orders' => env('FEATURE_WEEKEND_ONLY_ORDERS', true),
    'real_time_countdown' => env('FEATURE_REAL_TIME_COUNTDOWN', true),
    'ingredient_availability' => env('FEATURE_INGREDIENT_AVAILABILITY', true),
    'mobile_optimization' => env('FEATURE_MOBILE_OPTIMIZATION', true),
    'offline_support' => env('FEATURE_OFFLINE_SUPPORT', false),
    'premium_ingredients' => env('FEATURE_PREMIUM_INGREDIENTS', true),

    /*
    |--------------------------------------------------------------------------
    | Payment Features
    |--------------------------------------------------------------------------
    */

    'new_payment_flow' => env('FEATURE_NEW_PAYMENT_FLOW', false),
    'stripe_integration' => env('FEATURE_STRIPE_INTEGRATION', false),
    'cash_on_pickup' => env('FEATURE_CASH_ON_PICKUP', true),
    'payment_splitting' => env('FEATURE_PAYMENT_SPLITTING', false),

    /*
    |--------------------------------------------------------------------------
    | Administrative Features
    |--------------------------------------------------------------------------
    */

    'admin_dashboard' => env('FEATURE_ADMIN_DASHBOARD', false),
    'ingredient_management' => env('FEATURE_INGREDIENT_MANAGEMENT', false),
    'analytics_dashboard' => env('FEATURE_ANALYTICS_DASHBOARD', false),
    'customer_management' => env('FEATURE_CUSTOMER_MANAGEMENT', false),

    /*
    |--------------------------------------------------------------------------
    | Mobile App Features
    |--------------------------------------------------------------------------
    */

    'push_notifications' => env('FEATURE_PUSH_NOTIFICATIONS', false),
    'offline_mode' => env('FEATURE_OFFLINE_MODE', false),
    'location_services' => env('FEATURE_LOCATION_SERVICES', false),
    'camera_integration' => env('FEATURE_CAMERA_INTEGRATION', false),

    /*
    |--------------------------------------------------------------------------
    | Complex Feature Configurations
    |--------------------------------------------------------------------------
    |
    | Some features require more than just boolean toggles. These configurations
    | allow for more nuanced feature control.
    |
    */

    'payment_methods' => [
        'enabled' => env('FEATURE_PAYMENT_METHODS_ENABLED', true),
        'stripe' => env('FEATURE_PAYMENT_STRIPE', false),
        'cash' => env('FEATURE_PAYMENT_CASH', true),
        'apple_pay' => env('FEATURE_PAYMENT_APPLE_PAY', false),
        'google_pay' => env('FEATURE_PAYMENT_GOOGLE_PAY', false),
    ],

    'production_schedule' => [
        'enabled' => env('FEATURE_PRODUCTION_SCHEDULE_ENABLED', true),
        'saturday_capacity' => (int) env('FEATURE_SATURDAY_CAPACITY', 50),
        'sunday_capacity' => (int) env('FEATURE_SUNDAY_CAPACITY', 50),
        'advance_order_days' => (int) env('FEATURE_ADVANCE_ORDER_DAYS', 7),
    ],

    'sms_settings' => [
        'enabled' => env('FEATURE_SMS_ENABLED', true),
        'verification_required' => env('FEATURE_SMS_VERIFICATION_REQUIRED', true),
        'order_confirmations' => env('FEATURE_SMS_ORDER_CONFIRMATIONS', true),
        'pickup_reminders' => env('FEATURE_SMS_PICKUP_REMINDERS', true),
        'marketing_messages' => env('FEATURE_SMS_MARKETING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment-Based Feature Sets
    |--------------------------------------------------------------------------
    |
    | Quick way to enable/disable groups of features based on environment.
    | These override individual flags when enabled.
    |
    */

    'development_features' => env('FEATURE_DEVELOPMENT_MODE', false),
    'staging_features' => env('FEATURE_STAGING_MODE', false),
    'beta_features' => env('FEATURE_BETA_MODE', false),
];
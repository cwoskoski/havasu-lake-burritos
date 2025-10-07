<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Havasu Lake Burrito Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Havasu Lake, CA burrito business logic including
    | pricing, portions, tax rates, and build restrictions.
    |
    */

    'pricing' => [
        'base_price_cents' => (int) env('BURRITO_PRICE', 900), // $9.00 community-friendly
        'tax_rate' => 0.0725, // California sales tax rate for Havasu Lake, CA
    ],

    // Legacy support
    'price' => (int) env('BURRITO_PRICE', 900), // Base price in cents ($9.00 - community friendly)
    'tortilla_size' => (int) env('TORTILLA_SIZE', 14), // 14-inch tortillas
    'premium_upcharge' => env('PREMIUM_UPCHARGE', 200), // $2.00 for premium ingredients

    /*
    |--------------------------------------------------------------------------
    | Ingredient Selection Limits
    |--------------------------------------------------------------------------
    */

    'limits' => [
        'proteins' => ['min' => 1, 'max' => 2],
        'rice_beans' => ['min' => 1, 'max' => 3],
        'fresh_toppings' => ['min' => 0, 'max' => 5],
        'salsas' => ['min' => 0, 'max' => 2],
        'creamy' => ['min' => 0, 'max' => 2],
    ],

    /*
    |--------------------------------------------------------------------------
    | Standard Portions (in ounces)
    |--------------------------------------------------------------------------
    */

    'portions' => [
        'proteins' => 4.0,           // 1/2 cup
        'rice' => 4.0,              // 1/2 cup
        'beans' => 5.3,             // 2/3 cup
        'fresh_toppings' => 2.0,    // 1/4 cup
        'salsas' => 1.0,            // 2 tablespoons
        'cheese' => 2.0,            // 2 oz
        'other_creamy' => 1.0,      // 1 oz
    ],

    /*
    |--------------------------------------------------------------------------
    | Location & Operations (Havasu Lake, CA)
    |--------------------------------------------------------------------------
    */

    'location' => [
        'city' => 'Havasu Lake',
        'state' => 'CA',
        'timezone' => 'America/Los_Angeles', // California Pacific timezone
        'production_days' => ['saturday', 'sunday'],
    ],
];

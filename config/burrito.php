<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Burrito Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for burrito business logic including pricing, portions,
    | and build restrictions.
    |
    */

    'price' => (int) env('BURRITO_PRICE', 1200), // Base price in cents ($12.00)
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
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Weekend Production Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for weekend-only burrito production including capacity
    | limits and scheduling.
    |
    */

    'production_enabled' => env('WEEKEND_PRODUCTION_ENABLED', true),
    'max_daily_burritos' => (int) env('MAX_DAILY_BURRITOS', 100),

    /*
    |--------------------------------------------------------------------------
    | Production Hours
    |--------------------------------------------------------------------------
    */

    'hours' => [
        'saturday' => [
            'start' => '08:00',
            'end' => '17:00',
        ],
        'sunday' => [
            'start' => '09:00',
            'end' => '16:00',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ordering Cutoff Times
    |--------------------------------------------------------------------------
    */

    'cutoff' => [
        'saturday' => '15:00', // Stop taking orders at 3 PM Saturday
        'sunday' => '14:00',   // Stop taking orders at 2 PM Sunday
    ],
];

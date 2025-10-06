<?php

namespace Tests\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Helper class for burrito-specific testing utilities.
 */
class BurritoTestHelper
{
    /**
     * Generate test data for burrito ingredients across all categories.
     */
    public static function createIngredientSet(): array
    {
        return [
            'proteins' => [
                ['name' => 'Carnitas', 'category' => 'proteins', 'portion_size' => 0.5, 'unit' => 'cup'],
                ['name' => 'Chicken', 'category' => 'proteins', 'portion_size' => 0.5, 'unit' => 'cup'],
                ['name' => 'Barbacoa', 'category' => 'proteins', 'portion_size' => 0.5, 'unit' => 'cup'],
            ],
            'rice_beans' => [
                ['name' => 'Cilantro Lime Rice', 'category' => 'rice_beans', 'portion_size' => 0.5, 'unit' => 'cup'],
                ['name' => 'Spanish Rice', 'category' => 'rice_beans', 'portion_size' => 0.5, 'unit' => 'cup'],
                ['name' => 'Black Beans', 'category' => 'rice_beans', 'portion_size' => 0.67, 'unit' => 'cup'],
                ['name' => 'Pinto Beans', 'category' => 'rice_beans', 'portion_size' => 0.67, 'unit' => 'cup'],
            ],
            'fresh_toppings' => [
                ['name' => 'Lettuce', 'category' => 'fresh_toppings', 'portion_size' => 0.25, 'unit' => 'cup'],
                ['name' => 'Tomatoes', 'category' => 'fresh_toppings', 'portion_size' => 0.25, 'unit' => 'cup'],
                ['name' => 'Onions', 'category' => 'fresh_toppings', 'portion_size' => 0.125, 'unit' => 'cup'],
            ],
            'salsas' => [
                ['name' => 'Mild Salsa', 'category' => 'salsas', 'portion_size' => 2, 'unit' => 'tbsp'],
                ['name' => 'Medium Salsa', 'category' => 'salsas', 'portion_size' => 2, 'unit' => 'tbsp'],
                ['name' => 'Hot Salsa', 'category' => 'salsas', 'portion_size' => 2, 'unit' => 'tbsp'],
            ],
            'creamy' => [
                ['name' => 'Cheese', 'category' => 'creamy', 'portion_size' => 0.25, 'unit' => 'cup'],
                ['name' => 'Sour Cream', 'category' => 'creamy', 'portion_size' => 2, 'unit' => 'tbsp'],
            ],
        ];
    }

    /**
     * Create a complete burrito configuration for testing.
     */
    public static function createBurritoConfiguration(): array
    {
        return [
            'tortilla' => '14-inch',
            'proteins' => ['Carnitas'],
            'rice_beans' => ['Cilantro Lime Rice', 'Black Beans'],
            'fresh_toppings' => ['Lettuce', 'Tomatoes'],
            'salsas' => ['Medium Salsa'],
            'creamy' => ['Cheese'],
        ];
    }

    /**
     * Get weekend dates for testing production schedules.
     */
    public static function getWeekendDates(int $weeksFromNow = 0): Collection
    {
        $baseDate = Carbon::now()->addWeeks($weeksFromNow);

        // Find next Saturday
        $saturday = $baseDate->next(Carbon::SATURDAY);
        $sunday = $saturday->copy()->addDay();

        return collect([$saturday, $sunday]);
    }

    /**
     * Get weekday dates for testing business logic violations.
     */
    public static function getWeekdayDates(int $weeksFromNow = 0): Collection
    {
        $baseDate = Carbon::now()->addWeeks($weeksFromNow);

        // Get Monday through Friday
        $monday = $baseDate->next(Carbon::MONDAY);
        $weekdays = collect();

        for ($i = 0; $i < 5; $i++) {
            $weekdays->push($monday->copy()->addDays($i));
        }

        return $weekdays;
    }

    /**
     * Create production schedule data for testing.
     */
    public static function createProductionSchedule(Carbon $date, int $maxBurritos = 100): array
    {
        return [
            'date' => $date->format('Y-m-d'),
            'max_burritos' => $maxBurritos,
            'remaining_burritos' => $maxBurritos,
            'is_weekend' => $date->isWeekend(),
            'production_enabled' => $date->isWeekend(),
        ];
    }

    /**
     * Calculate total portion requirements for a burrito.
     */
    public static function calculatePortions(array $burritoConfig): array
    {
        $ingredients = self::createIngredientSet();
        $portions = [];

        foreach ($burritoConfig as $category => $selectedItems) {
            if ($category === 'tortilla') continue;

            foreach ($selectedItems as $itemName) {
                $ingredient = collect($ingredients[$category])
                    ->firstWhere('name', $itemName);

                if ($ingredient) {
                    $portions[$itemName] = [
                        'amount' => $ingredient['portion_size'],
                        'unit' => $ingredient['unit']
                    ];
                }
            }
        }

        return $portions;
    }

    /**
     * Generate realistic mobile user agents for testing.
     */
    public static function getMobileUserAgents(): array
    {
        return [
            'iPhone' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            'Android' => 'Mozilla/5.0 (Linux; Android 13; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Mobile Safari/537.36',
            'iPad' => 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        ];
    }

    /**
     * Mock slow network conditions for mobile testing.
     */
    public static function getSlowNetworkConfig(): array
    {
        return [
            'latency' => 300, // 300ms latency
            'download_throughput' => 1.5 * 1024 * 1024 / 8, // 1.5 Mbps
            'upload_throughput' => 750 * 1024 / 8, // 750 Kbps
        ];
    }
}
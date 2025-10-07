<?php

namespace Database\Seeders;

use App\Enums\IngredientCategory;
use App\Models\Ingredient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeder specifically for test data.
 * Creates consistent test data for TDD and manual testing.
 */
class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createTestUsers();
        $this->createTestIngredients();
        $this->createWeekendProductionSchedule();
    }

    /**
     * Create test users with various states for testing different scenarios.
     */
    private function createTestUsers(): void
    {
        // Verified customer (most common case)
        User::factory()
            ->verified()
            ->create([
                'name' => 'John Customer',
                'email' => 'customer@test.com',
                'phone' => '+15551234567',
            ]);

        // New customer (just signed up)
        User::factory()
            ->newCustomer()
            ->create([
                'name' => 'New Customer',
                'email' => 'new@test.com',
                'phone' => '+15551234568',
            ]);

        // Returning customer (has order history)
        User::factory()
            ->returningCustomer()
            ->create([
                'name' => 'Returning Customer',
                'email' => 'returning@test.com',
                'phone' => '+15551234569',
            ]);

        // Customer without phone verification
        User::factory()
            ->phoneUnverified()
            ->create([
                'name' => 'Unverified Phone',
                'email' => 'unverified@test.com',
                'phone' => '+15551234570',
            ]);

        // Customer who opted out of SMS
        User::factory()
            ->verified()
            ->noSms()
            ->create([
                'name' => 'No SMS Customer',
                'email' => 'nosms@test.com',
                'phone' => '+15551234571',
            ]);

        // Customer who wants marketing
        User::factory()
            ->verified()
            ->wantsMarketing()
            ->create([
                'name' => 'Marketing Customer',
                'email' => 'marketing@test.com',
                'phone' => '+15551234572',
            ]);

        // Test user for automated testing
        User::factory()
            ->testUser()
            ->create();

        // Admin user (if you have admin functionality)
        if (method_exists(User::factory(), 'admin')) {
            User::factory()
                ->admin()
                ->create([
                    'email' => 'admin@test.com',
                ]);
        }
    }

    /**
     * Create a complete set of test ingredients for each category.
     */
    private function createTestIngredients(): void
    {
        // Create 3 ingredients per category for variety
        foreach (IngredientCategory::cases() as $category) {
            $this->createIngredientsForCategory($category);
        }

        // Create some inactive ingredients for testing availability
        Ingredient::factory()
            ->protein()
            ->inactive()
            ->create([
                'name' => 'Unavailable Protein',
                'description' => 'This protein is currently unavailable',
            ]);

        Ingredient::factory()
            ->freshTopping()
            ->inactive()
            ->create([
                'name' => 'Out of Season Topping',
                'description' => 'This topping is out of season',
            ]);

        // Create ingredients with allergens for testing
        Ingredient::factory()
            ->creamy()
            ->withAllergens(['dairy', 'nuts'])
            ->create([
                'name' => 'Nut Cheese',
                'description' => 'Contains dairy and nuts',
            ]);

        // Create vegan ingredients for testing dietary restrictions
        Ingredient::factory()
            ->protein()
            ->vegan()
            ->create([
                'name' => 'Tofu Scramble',
                'description' => 'Plant-based protein option',
            ]);
    }

    /**
     * Create ingredients for a specific category with realistic test data.
     */
    private function createIngredientsForCategory(IngredientCategory $category): void
    {
        $ingredientData = $this->getTestIngredientsForCategory($category);

        foreach ($ingredientData as $data) {
            Ingredient::factory()
                ->withCategory($category)
                ->create($data);
        }
    }

    /**
     * Get test ingredient data for each category.
     */
    private function getTestIngredientsForCategory(IngredientCategory $category): array
    {
        return match ($category) {
            IngredientCategory::PROTEINS => [
                [
                    'name' => 'Carnitas',
                    'description' => 'Slow-cooked pork shoulder with traditional spices',
                    'standard_portion_oz' => 4.0,
                    'calories_per_oz' => 65.0,
                    'allergens' => [],
                    'dietary_info' => ['gluten_free', 'high_protein'],
                    'color_hex' => '#8B4513',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Grilled Chicken',
                    'description' => 'Marinated and grilled chicken breast',
                    'standard_portion_oz' => 4.0,
                    'calories_per_oz' => 55.0,
                    'allergens' => [],
                    'dietary_info' => ['gluten_free', 'high_protein'],
                    'color_hex' => '#CD853F',
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Barbacoa',
                    'description' => 'Slow-cooked beef with chipotle and spices',
                    'standard_portion_oz' => 4.0,
                    'calories_per_oz' => 70.0,
                    'allergens' => [],
                    'dietary_info' => ['gluten_free', 'high_protein'],
                    'color_hex' => '#A0522D',
                    'sort_order' => 3,
                ],
            ],

            IngredientCategory::RICE_BEANS => [
                [
                    'name' => 'Cilantro Lime Rice',
                    'description' => 'Fluffy white rice with fresh cilantro and lime',
                    'standard_portion_oz' => 4.0,
                    'calories_per_oz' => 30.0,
                    'allergens' => [],
                    'dietary_info' => ['vegan', 'vegetarian', 'gluten_free'],
                    'color_hex' => '#F5DEB3',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Spanish Rice',
                    'description' => 'Seasoned rice with tomatoes and spices',
                    'standard_portion_oz' => 4.0,
                    'calories_per_oz' => 32.0,
                    'allergens' => [],
                    'dietary_info' => ['vegan', 'vegetarian', 'gluten_free'],
                    'color_hex' => '#DEB887',
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Black Beans',
                    'description' => 'Seasoned black beans with cumin and garlic',
                    'standard_portion_oz' => 5.3,
                    'calories_per_oz' => 25.0,
                    'allergens' => [],
                    'dietary_info' => ['vegan', 'vegetarian', 'gluten_free', 'high_fiber'],
                    'color_hex' => '#654321',
                    'sort_order' => 3,
                ],
            ],

            IngredientCategory::FRESH_TOPPINGS => [
                [
                    'name' => 'Crisp Lettuce',
                    'description' => 'Fresh, crispy iceberg lettuce',
                    'standard_portion_oz' => 2.0,
                    'calories_per_oz' => 4.0,
                    'allergens' => [],
                    'dietary_info' => ['vegan', 'vegetarian', 'gluten_free', 'low_calorie'],
                    'color_hex' => '#228B22',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Diced Tomatoes',
                    'description' => 'Fresh diced Roma tomatoes',
                    'standard_portion_oz' => 2.0,
                    'calories_per_oz' => 5.0,
                    'allergens' => [],
                    'dietary_info' => ['vegan', 'vegetarian', 'gluten_free', 'low_calorie'],
                    'color_hex' => '#FF6347',
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Red Onions',
                    'description' => 'Fresh diced red onions',
                    'standard_portion_oz' => 1.0,
                    'calories_per_oz' => 10.0,
                    'allergens' => [],
                    'dietary_info' => ['vegan', 'vegetarian', 'gluten_free', 'low_calorie'],
                    'color_hex' => '#8B0000',
                    'sort_order' => 3,
                ],
            ],

            IngredientCategory::SALSAS => [
                [
                    'name' => 'Mild Salsa',
                    'description' => 'Fresh tomato salsa with mild heat',
                    'standard_portion_oz' => 1.0,
                    'calories_per_oz' => 8.0,
                    'allergens' => [],
                    'dietary_info' => ['vegan', 'vegetarian', 'gluten_free', 'low_calorie'],
                    'color_hex' => '#FF4500',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Medium Salsa',
                    'description' => 'Tomato salsa with jalapeños for medium heat',
                    'standard_portion_oz' => 1.0,
                    'calories_per_oz' => 8.0,
                    'allergens' => [],
                    'dietary_info' => ['vegan', 'vegetarian', 'gluten_free', 'low_calorie'],
                    'color_hex' => '#DC143C',
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Salsa Verde',
                    'description' => 'Tomatillo-based green salsa with cilantro',
                    'standard_portion_oz' => 1.0,
                    'calories_per_oz' => 6.0,
                    'allergens' => [],
                    'dietary_info' => ['vegan', 'vegetarian', 'gluten_free', 'low_calorie'],
                    'color_hex' => '#32CD32',
                    'sort_order' => 3,
                ],
            ],

            IngredientCategory::CREAMY => [
                [
                    'name' => 'Monterey Jack Cheese',
                    'description' => 'Shredded Monterey Jack cheese',
                    'standard_portion_oz' => 2.0,
                    'calories_per_oz' => 105.0,
                    'allergens' => ['dairy'],
                    'dietary_info' => ['vegetarian'],
                    'color_hex' => '#FFFACD',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Sour Cream',
                    'description' => 'Cool and creamy sour cream',
                    'standard_portion_oz' => 1.0,
                    'calories_per_oz' => 60.0,
                    'allergens' => ['dairy'],
                    'dietary_info' => ['vegetarian'],
                    'color_hex' => '#F5F5DC',
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Fresh Guacamole',
                    'description' => 'Made fresh daily with ripe avocados',
                    'standard_portion_oz' => 1.0,
                    'calories_per_oz' => 45.0,
                    'allergens' => [],
                    'dietary_info' => ['vegan', 'vegetarian', 'gluten_free'],
                    'color_hex' => '#228B22',
                    'sort_order' => 3,
                ],
            ],
        };
    }

    /**
     * Create production schedules for testing weekend-only business logic.
     */
    private function createWeekendProductionSchedule(): void
    {
        // Create production schedules for the next 4 weekends
        for ($i = 0; $i < 4; $i++) {
            $saturday = Carbon::now()->addWeeks($i)->next(Carbon::SATURDAY);
            $sunday = $saturday->copy()->addDay();

            foreach ([$saturday, $sunday] as $date) {
                // This would create ProductionSchedule records when that model exists
                // For now, we're just documenting the structure needed for testing
                /*
                ProductionSchedule::create([
                    'date' => $date->format('Y-m-d'),
                    'max_burritos' => 100,
                    'remaining_burritos' => 100,
                    'is_production_day' => true,
                    'production_start_time' => $date->setTime(8, 0),
                    'production_end_time' => $date->setTime(18, 0),
                    'last_order_time' => $date->setTime(16, 0),
                ]);
                */
            }
        }
    }

    /**
     * Create test orders for quota testing.
     */
    public function createTestOrders(int $count = 10): void
    {
        $saturday = Carbon::now()->next(Carbon::SATURDAY);

        for ($i = 0; $i < $count; $i++) {
            // This will be implemented when Order and Burrito models exist
            /*
            $order = \App\Models\Order::create([
                'customer_name' => "Test Customer {$i}",
                'customer_email' => "test{$i}@example.com",
                'order_date' => $saturday->format('Y-m-d'),
                'status' => 'pending',
                'total_amount' => 1200, // $12.00
            ]);

            $burrito = \App\Models\Burrito::create([
                'order_id' => $order->id,
                'configuration' => json_encode([
                    'proteins' => ['Carnitas'],
                    'rice_beans' => ['Cilantro Lime Rice', 'Black Beans'],
                    'fresh_toppings' => ['Lettuce', 'Tomatoes'],
                    'salsas' => ['Medium Salsa'],
                    'creamy' => ['Cheese'],
                ]),
                'price' => 1200,
            ]);
            */
        }
    }

    /**
     * Reset production schedule for testing.
     */
    public function resetProductionSchedule(): void
    {
        // Reset all production days to full capacity
        /*
        \App\Models\ProductionDay::query()->update([
            'remaining_burritos' => 100,
        ]);
        */
    }

    /**
     * Create weekend test scenario.
     */
    public function createWeekendScenario(): void
    {
        $this->createTestIngredients();
        $this->createWeekendProductionSchedule();

        // Set current time to Saturday for testing
        Carbon::setTestNow(Carbon::now()->next(Carbon::SATURDAY)->setTime(10, 0));
    }

    /**
     * Create weekday test scenario.
     */
    public function createWeekdayScenario(): void
    {
        $this->createTestIngredients();
        $this->createWeekendProductionSchedule();

        // Set current time to Monday for testing
        Carbon::setTestNow(Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0));
    }

    /**
     * Create sold out scenario.
     */
    public function createSoldOutScenario(): void
    {
        $this->createWeekendScenario();

        // Set all production days to sold out
        /*
        \App\Models\ProductionDay::query()->update([
            'remaining_burritos' => 0,
        ]);
        */
    }

    /**
     * Create low inventory scenario for testing.
     */
    public function createLowInventoryScenario(): void
    {
        $this->createWeekendScenario();

        // Mark some ingredients as unavailable
        Ingredient::where('name', 'Carnitas')->update(['is_active' => false]);
        Ingredient::where('name', 'Guacamole')->update(['is_active' => false]);
    }

    /**
     * Create high load testing scenario.
     */
    public function createHighLoadScenario(): void
    {
        // Create many users for load testing
        User::factory()->loadTest()->count(100)->create();

        // Create full ingredient set
        $this->createTestIngredients();

        // Create production schedule with higher capacity
        // This would set max_burritos to 500 instead of 100
    }
}

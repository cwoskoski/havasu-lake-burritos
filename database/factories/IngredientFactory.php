<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\IngredientCategory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ingredient>
 */
class IngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = $this->faker->randomElement(IngredientCategory::cases());
        $name = $this->generateNameByCategory($category);

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'category' => $category,
            'description' => $this->generateDescriptionByCategory($category),
            'standard_portion_oz' => $this->getPortionSizeByCategory($category),
            'calories_per_oz' => $this->getCaloriesByCategory($category),
            'allergens' => $this->getAllergensByCategory($category),
            'dietary_info' => $this->getDietaryInfoByCategory($category),
            'color_hex' => $this->getColorByCategory($category),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
        ];
    }

    /**
     * Create an ingredient with a specific category.
     */
    public function withCategory(IngredientCategory $category): static
    {
        return $this->state(function (array $attributes) use ($category) {
            $name = $this->generateNameByCategory($category);

            return [
                'name' => $name,
                'slug' => Str::slug($name),
                'category' => $category,
                'description' => $this->generateDescriptionByCategory($category),
                'standard_portion_oz' => $this->getPortionSizeByCategory($category),
                'calories_per_oz' => $this->getCaloriesByCategory($category),
                'allergens' => $this->getAllergensByCategory($category),
                'dietary_info' => $this->getDietaryInfoByCategory($category),
                'color_hex' => $this->getColorByCategory($category),
            ];
        });
    }

    /**
     * Create a protein ingredient.
     */
    public function protein(): static
    {
        return $this->withCategory(IngredientCategory::PROTEINS);
    }

    /**
     * Create a rice & beans ingredient.
     */
    public function riceBeans(): static
    {
        return $this->withCategory(IngredientCategory::RICE_BEANS);
    }

    /**
     * Create a fresh topping ingredient.
     */
    public function freshTopping(): static
    {
        return $this->withCategory(IngredientCategory::FRESH_TOPPINGS);
    }

    /**
     * Create a salsa ingredient.
     */
    public function salsa(): static
    {
        return $this->withCategory(IngredientCategory::SALSAS);
    }

    /**
     * Create a creamy ingredient.
     */
    public function creamy(): static
    {
        return $this->withCategory(IngredientCategory::CREAMY);
    }

    /**
     * Create an inactive ingredient.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create an ingredient with allergens.
     */
    public function withAllergens(array $allergens = ['dairy']): static
    {
        return $this->state(fn (array $attributes) => [
            'allergens' => $allergens,
        ]);
    }

    /**
     * Create a complete set of ingredients for testing burrito building.
     */
    public function completeSet(): array
    {
        $ingredients = [];

        foreach (IngredientCategory::cases() as $category) {
            $ingredients[$category->value] = $this->withCategory($category)->count(3)->make();
        }

        return $ingredients;
    }

    /**
     * Create ingredients with high allergen content for testing.
     */
    public function withHighAllergens(): static
    {
        return $this->state(fn (array $attributes) => [
            'allergens' => ['dairy', 'gluten', 'nuts', 'soy'],
        ]);
    }

    /**
     * Create vegan-friendly ingredients.
     */
    public function vegan(): static
    {
        return $this->state(fn (array $attributes) => [
            'allergens' => [],
            'dietary_info' => ['vegan', 'vegetarian', 'gluten_free'],
        ]);
    }

    /**
     * Create ingredients for testing availability.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create ingredients for testing unavailability.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a premium ingredient (for upcharge testing).
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_premium' => true,
        ]);
    }

    /**
     * Generate appropriate name based on category.
     */
    private function generateNameByCategory(IngredientCategory $category): string
    {
        return match ($category) {
            IngredientCategory::PROTEINS => $this->faker->randomElement([
                'Carnitas', 'Chicken', 'Barbacoa', 'Beef', 'Tofu', 'Chorizo', 'Fish'
            ]),
            IngredientCategory::RICE_BEANS => $this->faker->randomElement([
                'Cilantro Lime Rice', 'Spanish Rice', 'Brown Rice',
                'Black Beans', 'Pinto Beans', 'Refried Beans', 'White Rice'
            ]),
            IngredientCategory::FRESH_TOPPINGS => $this->faker->randomElement([
                'Lettuce', 'Tomatoes', 'Onions', 'Cilantro', 'Jalapeños',
                'Red Onions', 'Bell Peppers', 'Corn', 'Avocado'
            ]),
            IngredientCategory::SALSAS => $this->faker->randomElement([
                'Mild Salsa', 'Medium Salsa', 'Hot Salsa', 'Salsa Verde',
                'Corn Salsa', 'Pico de Gallo', 'Chipotle Salsa'
            ]),
            IngredientCategory::CREAMY => $this->faker->randomElement([
                'Cheese', 'Sour Cream', 'Guacamole', 'Crema', 'Queso Fresco'
            ]),
        };
    }

    /**
     * Generate appropriate description based on category.
     */
    private function generateDescriptionByCategory(IngredientCategory $category): string
    {
        return match ($category) {
            IngredientCategory::PROTEINS => $this->faker->randomElement([
                'Slow-cooked and seasoned to perfection',
                'Grilled with traditional spices',
                'Fresh and flavorful protein option'
            ]),
            IngredientCategory::RICE_BEANS => $this->faker->randomElement([
                'Fluffy and perfectly seasoned',
                'Cooked with authentic flavors',
                'Traditional recipe with fresh herbs'
            ]),
            IngredientCategory::FRESH_TOPPINGS => $this->faker->randomElement([
                'Fresh and crisp',
                'Locally sourced when possible',
                'Adds perfect crunch and flavor'
            ]),
            IngredientCategory::SALSAS => $this->faker->randomElement([
                'Made fresh daily',
                'Perfect blend of spices and flavor',
                'Traditional recipe with a kick'
            ]),
            IngredientCategory::CREAMY => $this->faker->randomElement([
                'Rich and creamy texture',
                'Made fresh with quality ingredients',
                'Adds perfect richness to your burrito'
            ]),
        };
    }

    /**
     * Get appropriate portion size based on category (in ounces).
     */
    private function getPortionSizeByCategory(IngredientCategory $category): float
    {
        return match ($category) {
            IngredientCategory::PROTEINS => 4.0,           // 4 oz (1/2 cup)
            IngredientCategory::RICE_BEANS => $this->faker->randomElement([4.0, 5.3]), // 4 oz rice, 5.3 oz beans
            IngredientCategory::FRESH_TOPPINGS => 2.0,     // 2 oz (1/4 cup)
            IngredientCategory::SALSAS => 1.0,             // 1 oz (2 tablespoons)
            IngredientCategory::CREAMY => $this->faker->randomElement([2.0, 1.0]), // 2 oz cheese, 1 oz others
        };
    }

    /**
     * Get appropriate calories per ounce based on category.
     */
    private function getCaloriesByCategory(IngredientCategory $category): float
    {
        return match ($category) {
            IngredientCategory::PROTEINS => $this->faker->numberBetween(40, 80),      // 40-80 cal/oz
            IngredientCategory::RICE_BEANS => $this->faker->numberBetween(25, 35),    // 25-35 cal/oz
            IngredientCategory::FRESH_TOPPINGS => $this->faker->numberBetween(3, 10), // 3-10 cal/oz
            IngredientCategory::SALSAS => $this->faker->numberBetween(5, 15),         // 5-15 cal/oz
            IngredientCategory::CREAMY => $this->faker->numberBetween(80, 120),       // 80-120 cal/oz
        };
    }

    /**
     * Get appropriate allergens based on category.
     */
    private function getAllergensByCategory(IngredientCategory $category): array
    {
        return match ($category) {
            IngredientCategory::PROTEINS => $this->faker->randomElements(['soy'], 0, 1),
            IngredientCategory::RICE_BEANS => [],
            IngredientCategory::FRESH_TOPPINGS => [],
            IngredientCategory::SALSAS => [],
            IngredientCategory::CREAMY => $this->faker->randomElements(['dairy'], 0, 1),
        };
    }

    /**
     * Get appropriate dietary info based on category.
     */
    private function getDietaryInfoByCategory(IngredientCategory $category): array
    {
        $baseInfo = ['gluten_free'];

        return match ($category) {
            IngredientCategory::PROTEINS => array_merge($baseInfo,
                $this->faker->randomElements(['high_protein'], 0, 1)
            ),
            IngredientCategory::RICE_BEANS => array_merge($baseInfo,
                $this->faker->randomElements(['vegan', 'vegetarian', 'high_fiber'], 0, 2)
            ),
            IngredientCategory::FRESH_TOPPINGS => array_merge($baseInfo,
                $this->faker->randomElements(['vegan', 'vegetarian', 'low_calorie'], 0, 2)
            ),
            IngredientCategory::SALSAS => array_merge($baseInfo,
                $this->faker->randomElements(['vegan', 'vegetarian', 'low_calorie'], 0, 2)
            ),
            IngredientCategory::CREAMY => $this->faker->randomElements(['vegetarian'], 0, 1),
        };
    }

    /**
     * Get appropriate color based on category.
     */
    private function getColorByCategory(IngredientCategory $category): string
    {
        return match ($category) {
            IngredientCategory::PROTEINS => $this->faker->randomElement([
                '#8B4513', '#CD853F', '#A0522D', '#D2691E' // Browns
            ]),
            IngredientCategory::RICE_BEANS => $this->faker->randomElement([
                '#F5DEB3', '#DEB887', '#8B4513', '#654321' // Beiges and browns
            ]),
            IngredientCategory::FRESH_TOPPINGS => $this->faker->randomElement([
                '#228B22', '#32CD32', '#FF6347', '#FFA500' // Greens and bright colors
            ]),
            IngredientCategory::SALSAS => $this->faker->randomElement([
                '#FF4500', '#DC143C', '#B22222', '#8B0000' // Reds
            ]),
            IngredientCategory::CREAMY => $this->faker->randomElement([
                '#FFFACD', '#F5F5DC', '#FFFFE0', '#FFF8DC' // Creams and whites
            ]),
        };
    }
}
<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Ingredient;
use App\Enums\IngredientCategory;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for the Ingredient model.
 * Tests business logic, relationships, and data integrity.
 */
class IngredientModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingredient_can_be_created_with_valid_data(): void
    {
        $ingredient = Ingredient::factory()->create([
            'name' => 'Carnitas',
            'category' => IngredientCategory::PROTEINS,
            'standard_portion_oz' => 4.0,
            'is_active' => true,
        ]);

        expect($ingredient->name)->toBe('Carnitas');
        expect($ingredient->category)->toBe(IngredientCategory::PROTEINS);
        expect($ingredient->standard_portion_oz)->toBe(4.0);
        expect($ingredient->is_active)->toBeTrue();
    }

    public function test_ingredient_category_is_properly_cast_to_enum(): void
    {
        $ingredient = Ingredient::factory()->create([
            'category' => 'proteins',
        ]);

        expect($ingredient->category)->toBeInstanceOf(IngredientCategory::class);
        expect($ingredient->category)->toBe(IngredientCategory::PROTEINS);
        expect($ingredient->category->value)->toBe('proteins');
        expect($ingredient->category->label())->toBe('Proteins');
    }

    public function test_standard_portion_oz_is_cast_to_decimal(): void
    {
        $ingredient = Ingredient::factory()->create([
            'standard_portion_oz' => '4.50',
        ]);

        expect($ingredient->standard_portion_oz)->toBe(4.5);
        expect($ingredient->standard_portion_oz)->toBeFloat();
    }

    public function test_allergens_are_cast_to_array(): void
    {
        $allergens = ['gluten', 'dairy', 'nuts'];

        $ingredient = Ingredient::factory()->create([
            'allergens' => $allergens,
        ]);

        expect($ingredient->allergens)->toBe($allergens);
        expect($ingredient->allergens)->toBeArray();
    }

    public function test_dietary_info_is_cast_to_array(): void
    {
        $dietaryInfo = ['vegetarian', 'gluten-free'];

        $ingredient = Ingredient::factory()->create([
            'dietary_info' => $dietaryInfo,
        ]);

        expect($ingredient->dietary_info)->toBe($dietaryInfo);
        expect($ingredient->dietary_info)->toBeArray();
    }

    public function test_active_scope_returns_only_active_ingredients(): void
    {
        // Create active and inactive ingredients
        Ingredient::factory()->count(3)->create(['is_active' => true]);
        Ingredient::factory()->count(2)->create(['is_active' => false]);

        $activeIngredients = Ingredient::active()->get();

        expect($activeIngredients)->toHaveCount(3);
        foreach ($activeIngredients as $ingredient) {
            expect($ingredient->is_active)->toBeTrue();
        }
    }

    public function test_by_category_scope_filters_correctly(): void
    {
        // Create ingredients in different categories
        Ingredient::factory()->count(2)->create(['category' => IngredientCategory::PROTEINS]);
        Ingredient::factory()->count(3)->create(['category' => IngredientCategory::SALSAS]);

        $proteins = Ingredient::byCategory(IngredientCategory::PROTEINS)->get();
        $salsas = Ingredient::byCategory(IngredientCategory::SALSAS)->get();

        expect($proteins)->toHaveCount(2);
        expect($salsas)->toHaveCount(3);

        foreach ($proteins as $ingredient) {
            expect($ingredient->category)->toBe(IngredientCategory::PROTEINS);
        }
    }

    public function test_ordered_scope_sorts_by_sort_order_then_name(): void
    {
        // Create ingredients with different sort orders and names
        $ingredient1 = Ingredient::factory()->create(['name' => 'Zebra', 'sort_order' => 3]);
        $ingredient2 = Ingredient::factory()->create(['name' => 'Alpha', 'sort_order' => 1]);
        $ingredient3 = Ingredient::factory()->create(['name' => 'Beta', 'sort_order' => 1]);

        $orderedIngredients = Ingredient::ordered()->get();

        expect($orderedIngredients->first()->id)->toBe($ingredient2->id); // Alpha (sort_order 1)
        expect($orderedIngredients->get(1)->id)->toBe($ingredient3->id); // Beta (sort_order 1)
        expect($orderedIngredients->last()->id)->toBe($ingredient1->id);  // Zebra (sort_order 3)
    }

    public function test_combined_scopes_work_together(): void
    {
        // Create a mix of ingredients
        Ingredient::factory()->create([
            'category' => IngredientCategory::PROTEINS,
            'is_active' => true,
            'sort_order' => 2,
            'name' => 'Carnitas',
        ]);

        Ingredient::factory()->create([
            'category' => IngredientCategory::PROTEINS,
            'is_active' => false,
            'sort_order' => 1,
            'name' => 'Inactive Protein',
        ]);

        Ingredient::factory()->create([
            'category' => IngredientCategory::PROTEINS,
            'is_active' => true,
            'sort_order' => 1,
            'name' => 'Chicken',
        ]);

        $result = Ingredient::active()
            ->byCategory(IngredientCategory::PROTEINS)
            ->ordered()
            ->get();

        expect($result)->toHaveCount(2);
        expect($result->first()->name)->toBe('Chicken'); // Sort order 1, alphabetically first
        expect($result->last()->name)->toBe('Carnitas'); // Sort order 2
    }

    public function test_ingredient_portion_validation(): void
    {
        // Test that standard portions are within reasonable ranges
        $categories = [
            IngredientCategory::PROTEINS => [3.0, 6.0], // 3-6 oz
            IngredientCategory::RICE_BEANS => [2.0, 5.0], // 2-5 oz
            IngredientCategory::FRESH_TOPPINGS => [0.5, 3.0], // 0.5-3 oz
            IngredientCategory::SALSAS => [0.5, 2.0], // 0.5-2 oz
            IngredientCategory::CREAMY => [0.5, 2.0], // 0.5-2 oz
        ];

        foreach ($categories as $category => $range) {
            $ingredient = Ingredient::factory()->create([
                'category' => $category,
                'standard_portion_oz' => $range[0],
            ]);

            expect($ingredient->standard_portion_oz)
                ->toBeGreaterThanOrEqual($range[0])
                ->toBeLessThanOrEqual($range[1]);
        }
    }

    public function test_ingredient_color_hex_validation(): void
    {
        $ingredient = Ingredient::factory()->create([
            'color_hex' => '#FF5722',
        ]);

        expect($ingredient->color_hex)->toMatch('/^#[0-9A-F]{6}$/i');
    }

    public function test_ingredient_slug_generation(): void
    {
        $ingredient = Ingredient::factory()->create([
            'name' => 'Black Bean Salsa',
        ]);

        // Assuming slug is automatically generated from name
        expect($ingredient->slug)->toBe('black-bean-salsa');
    }

    public function test_ingredient_nutritional_data(): void
    {
        $ingredient = Ingredient::factory()->create([
            'calories_per_oz' => 45.5,
        ]);

        expect($ingredient->calories_per_oz)->toBe(45.5);
        expect($ingredient->calories_per_oz)->toBeFloat();
    }

    public function test_ingredient_allergen_info(): void
    {
        $commonAllergens = ['dairy', 'gluten', 'nuts', 'soy', 'eggs'];

        $ingredient = Ingredient::factory()->create([
            'allergens' => ['dairy', 'gluten'],
        ]);

        foreach ($ingredient->allergens as $allergen) {
            expect($allergen)->toBeIn($commonAllergens);
        }
    }

    public function test_ingredient_dietary_restrictions(): void
    {
        $dietaryOptions = ['vegetarian', 'vegan', 'gluten-free', 'dairy-free', 'keto-friendly'];

        $ingredient = Ingredient::factory()->create([
            'dietary_info' => ['vegetarian', 'gluten-free'],
        ]);

        foreach ($ingredient->dietary_info as $diet) {
            expect($diet)->toBeIn($dietaryOptions);
        }
    }

    public function test_ingredient_business_rules(): void
    {
        // Test that protein portions follow business rules
        $protein = Ingredient::factory()->create([
            'category' => IngredientCategory::PROTEINS,
            'standard_portion_oz' => 4.0, // Standard 1/2 cup protein
        ]);

        expect($protein->standard_portion_oz)->toHaveValidPortionSize();

        // Test that all categories are valid
        expect($protein->category->value)->toBeValidIngredientCategory();
    }

    public function test_ingredient_weekend_availability_relationship(): void
    {
        $ingredient = Ingredient::factory()->create();

        // Test that the relationship exists
        expect($ingredient->ingredientWeeks())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    }

    public function test_ingredient_factory_states(): void
    {
        // Test different factory states
        $proteinIngredient = Ingredient::factory()->withCategory(IngredientCategory::PROTEINS)->create();
        expect($proteinIngredient->category)->toBe(IngredientCategory::PROTEINS);

        $inactiveIngredient = Ingredient::factory()->inactive()->create();
        expect($inactiveIngredient->is_active)->toBeFalse();
    }

    public function test_ingredient_data_integrity(): void
    {
        $ingredient = Ingredient::factory()->create();

        // Required fields should never be null
        expect($ingredient->name)->not->toBeNull();
        expect($ingredient->category)->not->toBeNull();
        expect($ingredient->standard_portion_oz)->not->toBeNull();
        expect($ingredient->is_active)->not->toBeNull();

        // Numeric fields should be proper types
        expect($ingredient->standard_portion_oz)->toBeFloat();
        expect($ingredient->sort_order)->toBeInt();
        expect($ingredient->is_active)->toBeBool();
    }

    public function test_ingredient_performance_for_large_datasets(): void
    {
        // Create a large dataset for performance testing
        Ingredient::factory()->count(1000)->create();

        $this->assertQueryPerformance(function () {
            // Test that querying large datasets is performant
            $activeIngredients = Ingredient::active()->ordered()->get();
            expect($activeIngredients->count())->toBeGreaterThan(0);
        }, 5); // Should use max 5 queries

        $this->assertMemoryUsage(8); // Should use less than 8MB
    }
}
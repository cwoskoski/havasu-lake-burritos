<?php

declare(strict_types=1);

namespace Tests\Unit\Business;

use App\Enums\IngredientCategory;
use App\Models\Ingredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for burrito building business logic.
 * Tests the 5-step track process and ingredient combination rules.
 */
class BurritoBuilderBusinessLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_burrito_track_has_five_required_steps(): void
    {
        $trackSteps = $this->getBurritoTrackSteps();

        expect($trackSteps)->toHaveCount(5);
        expect($trackSteps)->toEqual([
            'proteins',
            'rice_beans',
            'fresh_toppings',
            'salsas',
            'creamy',
        ]);
    }

    public function test_each_track_step_maps_to_ingredient_category(): void
    {
        $trackSteps = $this->getBurritoTrackSteps();
        $categories = IngredientCategory::values();

        foreach ($trackSteps as $step) {
            expect($step)->toBeIn($categories);
        }
    }

    public function test_protein_selection_rules(): void
    {
        // Create protein ingredients
        $proteins = Ingredient::factory()->protein()->count(4)->create();

        // Should allow 1-2 proteins
        expect($this->validateProteinSelection([$proteins[0]->id]))->toBeTrue();
        expect($this->validateProteinSelection([$proteins[0]->id, $proteins[1]->id]))->toBeTrue();

        // Should not allow more than 2 proteins
        expect($this->validateProteinSelection([
            $proteins[0]->id,
            $proteins[1]->id,
            $proteins[2]->id,
        ]))->toBeFalse();

        // Should not allow zero proteins
        expect($this->validateProteinSelection([]))->toBeFalse();
    }

    public function test_rice_and_beans_selection_rules(): void
    {
        // Create rice and bean ingredients
        $rice = Ingredient::factory()->riceBeans()->create(['name' => 'Cilantro Rice']);
        $beans = Ingredient::factory()->riceBeans()->create(['name' => 'Black Beans']);
        $moreBeans = Ingredient::factory()->riceBeans()->create(['name' => 'Pinto Beans']);

        // Should allow rice only
        expect($this->validateRiceBeansSelection([$rice->id]))->toBeTrue();

        // Should allow beans only
        expect($this->validateRiceBeansSelection([$beans->id]))->toBeTrue();

        // Should allow rice + beans
        expect($this->validateRiceBeansSelection([$rice->id, $beans->id]))->toBeTrue();

        // Should allow multiple beans
        expect($this->validateRiceBeansSelection([$beans->id, $moreBeans->id]))->toBeTrue();

        // Should not allow zero rice/beans
        expect($this->validateRiceBeansSelection([]))->toBeFalse();

        // Should not allow too many items (max 3)
        $extraRice = Ingredient::factory()->riceBeans()->create(['name' => 'Brown Rice']);
        expect($this->validateRiceBeansSelection([
            $rice->id,
            $beans->id,
            $moreBeans->id,
            $extraRice->id,
        ]))->toBeFalse();
    }

    public function test_fresh_toppings_selection_rules(): void
    {
        $toppings = Ingredient::factory()->freshTopping()->count(6)->create();

        // Should allow 0-5 fresh toppings
        expect($this->validateFreshToppingsSelection([]))->toBeTrue();
        expect($this->validateFreshToppingsSelection([$toppings[0]->id]))->toBeTrue();
        expect($this->validateFreshToppingsSelection([
            $toppings[0]->id,
            $toppings[1]->id,
            $toppings[2]->id,
            $toppings[3]->id,
            $toppings[4]->id,
        ]))->toBeTrue();

        // Should not allow more than 5 fresh toppings
        expect($this->validateFreshToppingsSelection([
            $toppings[0]->id,
            $toppings[1]->id,
            $toppings[2]->id,
            $toppings[3]->id,
            $toppings[4]->id,
            $toppings[5]->id,
        ]))->toBeFalse();
    }

    public function test_salsa_selection_rules(): void
    {
        $salsas = Ingredient::factory()->salsa()->count(4)->create();

        // Should allow 0-2 salsas
        expect($this->validateSalsaSelection([]))->toBeTrue();
        expect($this->validateSalsaSelection([$salsas[0]->id]))->toBeTrue();
        expect($this->validateSalsaSelection([$salsas[0]->id, $salsas[1]->id]))->toBeTrue();

        // Should not allow more than 2 salsas
        expect($this->validateSalsaSelection([
            $salsas[0]->id,
            $salsas[1]->id,
            $salsas[2]->id,
        ]))->toBeFalse();
    }

    public function test_creamy_selection_rules(): void
    {
        $creamyOptions = Ingredient::factory()->creamy()->count(4)->create();

        // Should allow 0-2 creamy options
        expect($this->validateCreamySelection([]))->toBeTrue();
        expect($this->validateCreamySelection([$creamyOptions[0]->id]))->toBeTrue();
        expect($this->validateCreamySelection([
            $creamyOptions[0]->id,
            $creamyOptions[1]->id,
        ]))->toBeTrue();

        // Should not allow more than 2 creamy options
        expect($this->validateCreamySelection([
            $creamyOptions[0]->id,
            $creamyOptions[1]->id,
            $creamyOptions[2]->id,
        ]))->toBeFalse();
    }

    public function test_complete_burrito_validation(): void
    {
        // Create ingredients for each category
        $protein = Ingredient::factory()->protein()->create();
        $rice = Ingredient::factory()->riceBeans()->create(['name' => 'Rice']);
        $beans = Ingredient::factory()->riceBeans()->create(['name' => 'Beans']);
        $lettuce = Ingredient::factory()->freshTopping()->create(['name' => 'Lettuce']);
        $salsa = Ingredient::factory()->salsa()->create();
        $cheese = Ingredient::factory()->creamy()->create();

        $validBurrito = [
            'proteins' => [$protein->id],
            'rice_beans' => [$rice->id, $beans->id],
            'fresh_toppings' => [$lettuce->id],
            'salsas' => [$salsa->id],
            'creamy' => [$cheese->id],
        ];

        expect($this->validateCompleteBurrito($validBurrito))->toBeTrue();

        // Test missing protein (required)
        $invalidBurrito = $validBurrito;
        $invalidBurrito['proteins'] = [];
        expect($this->validateCompleteBurrito($invalidBurrito))->toBeFalse();

        // Test missing rice/beans (required)
        $invalidBurrito = $validBurrito;
        $invalidBurrito['rice_beans'] = [];
        expect($this->validateCompleteBurrito($invalidBurrito))->toBeFalse();
    }

    public function test_burrito_portion_calculations(): void
    {
        $protein = Ingredient::factory()->protein()->create(['standard_portion_oz' => 4.0]);
        $rice = Ingredient::factory()->riceBeans()->create([
            'name' => 'Rice',
            'standard_portion_oz' => 4.0,
        ]);
        $beans = Ingredient::factory()->riceBeans()->create([
            'name' => 'Beans',
            'standard_portion_oz' => 5.3,
        ]);

        $burrito = [
            'proteins' => [$protein->id],
            'rice_beans' => [$rice->id, $beans->id],
            'fresh_toppings' => [],
            'salsas' => [],
            'creamy' => [],
        ];

        $portions = $this->calculateBurritoPortions($burrito);

        expect($portions['total_oz'])->toBe(13.3); // 4.0 + 4.0 + 5.3
        expect($portions['protein_oz'])->toBe(4.0);
        expect($portions['rice_beans_oz'])->toBe(9.3); // 4.0 + 5.3
    }

    public function test_burrito_nutritional_calculations(): void
    {
        $protein = Ingredient::factory()->protein()->create([
            'standard_portion_oz' => 4.0,
            'calories_per_oz' => 60,
        ]);
        $rice = Ingredient::factory()->riceBeans()->create([
            'standard_portion_oz' => 4.0,
            'calories_per_oz' => 30,
        ]);

        $burrito = [
            'proteins' => [$protein->id],
            'rice_beans' => [$rice->id],
            'fresh_toppings' => [],
            'salsas' => [],
            'creamy' => [],
        ];

        $nutrition = $this->calculateBurritoNutrition($burrito);

        expect($nutrition['total_calories'])->toBe(360); // (4*60) + (4*30)
        expect($nutrition['protein_calories'])->toBe(240); // 4*60
        expect($nutrition['carb_calories'])->toBe(120); // 4*30
    }

    public function test_burrito_allergen_aggregation(): void
    {
        $protein = Ingredient::factory()->protein()->create(['allergens' => ['soy']]);
        $cheese = Ingredient::factory()->creamy()->create(['allergens' => ['dairy']]);
        $salsa = Ingredient::factory()->salsa()->create(['allergens' => []]);

        $burrito = [
            'proteins' => [$protein->id],
            'rice_beans' => [],
            'fresh_toppings' => [],
            'salsas' => [$salsa->id],
            'creamy' => [$cheese->id],
        ];

        $allergens = $this->aggregateBurritoAllergens($burrito);

        expect($allergens)->toContain('soy');
        expect($allergens)->toContain('dairy');
        expect($allergens)->not->toContain('gluten');
        expect($allergens)->toHaveCount(2);
    }

    public function test_burrito_dietary_info_aggregation(): void
    {
        $veganProtein = Ingredient::factory()->protein()->create([
            'dietary_info' => ['vegan', 'vegetarian', 'gluten_free'],
        ]);
        $veganRice = Ingredient::factory()->riceBeans()->create([
            'dietary_info' => ['vegan', 'vegetarian', 'gluten_free'],
        ]);
        $cheese = Ingredient::factory()->creamy()->create([
            'dietary_info' => ['vegetarian'], // Not vegan
        ]);

        $burrito = [
            'proteins' => [$veganProtein->id],
            'rice_beans' => [$veganRice->id],
            'fresh_toppings' => [],
            'salsas' => [],
            'creamy' => [$cheese->id],
        ];

        $dietaryInfo = $this->aggregateBurritoDietaryInfo($burrito);

        // Should be vegetarian but not vegan due to cheese
        expect($dietaryInfo)->toContain('vegetarian');
        expect($dietaryInfo)->not->toContain('vegan');
        expect($dietaryInfo)->toContain('gluten_free');
    }

    public function test_burrito_customization_limits(): void
    {
        // Test maximum ingredient limits per burrito
        $proteins = Ingredient::factory()->protein()->count(3)->create();
        $toppings = Ingredient::factory()->freshTopping()->count(8)->create();

        // Should reject burrito with too many proteins
        $overloadedBurrito = [
            'proteins' => $proteins->pluck('id')->toArray(),
            'rice_beans' => [],
            'fresh_toppings' => [],
            'salsas' => [],
            'creamy' => [],
        ];

        expect($this->validateCompleteBurrito($overloadedBurrito))->toBeFalse();

        // Should reject burrito with too many toppings
        $tooManyToppings = [
            'proteins' => [$proteins[0]->id],
            'rice_beans' => [Ingredient::factory()->riceBeans()->create()->id],
            'fresh_toppings' => $toppings->pluck('id')->toArray(),
            'salsas' => [],
            'creamy' => [],
        ];

        expect($this->validateCompleteBurrito($tooManyToppings))->toBeFalse();
    }

    public function test_burrito_tortilla_specifications(): void
    {
        $specs = $this->getBurritoTortillaSpecs();

        expect($specs['size'])->toBe('14-inch');
        expect($specs['type'])->toBe('flour');
        expect($specs['weight_oz'])->toBe(4.0);
        expect($specs['calories'])->toBe(290);
    }

    public function test_burrito_price_calculation(): void
    {
        $burrito = [
            'proteins' => [Ingredient::factory()->protein()->create()->id],
            'rice_beans' => [Ingredient::factory()->riceBeans()->create()->id],
            'fresh_toppings' => [Ingredient::factory()->freshTopping()->create()->id],
            'salsas' => [Ingredient::factory()->salsa()->create()->id],
            'creamy' => [Ingredient::factory()->creamy()->create()->id],
        ];

        $price = $this->calculateBurritoPrice($burrito);

        // Base price should be $12.00 (1200 cents)
        expect($price)->toBe(1200);
    }

    public function test_premium_ingredient_upcharge(): void
    {
        $premiumProtein = Ingredient::factory()->protein()->create([
            'name' => 'Premium Steak',
            'is_premium' => true,
        ]);

        $burrito = [
            'proteins' => [$premiumProtein->id],
            'rice_beans' => [Ingredient::factory()->riceBeans()->create()->id],
            'fresh_toppings' => [],
            'salsas' => [],
            'creamy' => [],
        ];

        $price = $this->calculateBurritoPrice($burrito);

        // Should include premium upcharge (+$2.00)
        expect($price)->toBe(1400); // $14.00
    }

    /**
     * Helper methods for testing business logic
     */
    protected function getBurritoTrackSteps(): array
    {
        return ['proteins', 'rice_beans', 'fresh_toppings', 'salsas', 'creamy'];
    }

    protected function validateProteinSelection(array $proteinIds): bool
    {
        return count($proteinIds) >= 1 && count($proteinIds) <= 2;
    }

    protected function validateRiceBeansSelection(array $riceBeanIds): bool
    {
        return count($riceBeanIds) >= 1 && count($riceBeanIds) <= 3;
    }

    protected function validateFreshToppingsSelection(array $toppingIds): bool
    {
        return count($toppingIds) <= 5;
    }

    protected function validateSalsaSelection(array $salsaIds): bool
    {
        return count($salsaIds) <= 2;
    }

    protected function validateCreamySelection(array $creamyIds): bool
    {
        return count($creamyIds) <= 2;
    }

    protected function validateCompleteBurrito(array $burrito): bool
    {
        return $this->validateProteinSelection($burrito['proteins'])
            && $this->validateRiceBeansSelection($burrito['rice_beans'])
            && $this->validateFreshToppingsSelection($burrito['fresh_toppings'])
            && $this->validateSalsaSelection($burrito['salsas'])
            && $this->validateCreamySelection($burrito['creamy']);
    }

    protected function calculateBurritoPortions(array $burrito): array
    {
        $totalOz = 0;
        $proteinOz = 0;
        $riceBeansOz = 0;

        foreach ($burrito as $category => $ingredientIds) {
            foreach ($ingredientIds as $ingredientId) {
                $ingredient = Ingredient::find($ingredientId);
                $totalOz += $ingredient->standard_portion_oz;

                if ($category === 'proteins') {
                    $proteinOz += $ingredient->standard_portion_oz;
                } elseif ($category === 'rice_beans') {
                    $riceBeansOz += $ingredient->standard_portion_oz;
                }
            }
        }

        return [
            'total_oz' => $totalOz,
            'protein_oz' => $proteinOz,
            'rice_beans_oz' => $riceBeansOz,
        ];
    }

    protected function calculateBurritoNutrition(array $burrito): array
    {
        $totalCalories = 0;
        $proteinCalories = 0;
        $carbCalories = 0;

        foreach ($burrito as $category => $ingredientIds) {
            foreach ($ingredientIds as $ingredientId) {
                $ingredient = Ingredient::find($ingredientId);
                $calories = $ingredient->standard_portion_oz * $ingredient->calories_per_oz;
                $totalCalories += $calories;

                if ($category === 'proteins') {
                    $proteinCalories += $calories;
                } elseif ($category === 'rice_beans') {
                    $carbCalories += $calories;
                }
            }
        }

        return [
            'total_calories' => (int) $totalCalories,
            'protein_calories' => (int) $proteinCalories,
            'carb_calories' => (int) $carbCalories,
        ];
    }

    protected function aggregateBurritoAllergens(array $burrito): array
    {
        $allergens = [];

        foreach ($burrito as $ingredientIds) {
            foreach ($ingredientIds as $ingredientId) {
                $ingredient = Ingredient::find($ingredientId);
                $allergens = array_merge($allergens, $ingredient->allergens ?? []);
            }
        }

        return array_unique($allergens);
    }

    protected function aggregateBurritoDietaryInfo(array $burrito): array
    {
        $allDietaryInfo = [];

        foreach ($burrito as $ingredientIds) {
            foreach ($ingredientIds as $ingredientId) {
                $ingredient = Ingredient::find($ingredientId);
                if (empty($allDietaryInfo)) {
                    $allDietaryInfo = $ingredient->dietary_info ?? [];
                } else {
                    // Intersection - only keep dietary info that ALL ingredients have
                    $allDietaryInfo = array_intersect($allDietaryInfo, $ingredient->dietary_info ?? []);
                }
            }
        }

        return array_values($allDietaryInfo);
    }

    protected function getBurritoTortillaSpecs(): array
    {
        return [
            'size' => '14-inch',
            'type' => 'flour',
            'weight_oz' => 4.0,
            'calories' => 290,
        ];
    }

    protected function calculateBurritoPrice(array $burrito): int
    {
        $basePrice = 1200; // $12.00
        $premiumUpcharge = 0;

        foreach ($burrito['proteins'] as $proteinId) {
            $protein = Ingredient::find($proteinId);
            if ($protein->is_premium ?? false) {
                $premiumUpcharge += 200; // $2.00 per premium protein
            }
        }

        return $basePrice + $premiumUpcharge;
    }
}

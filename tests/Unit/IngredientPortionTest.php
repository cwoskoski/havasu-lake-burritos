<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Helpers\BurritoTestHelper;

class IngredientPortionTest extends TestCase
{
    public function test_protein_portions_are_standardized()
    {
        $ingredients = BurritoTestHelper::createIngredientSet();
        $proteins = $ingredients['proteins'];

        foreach ($proteins as $protein) {
            $this->assertEquals(0.5, $protein['portion_size'],
                "Protein {$protein['name']} should have 0.5 cup portion");
            $this->assertEquals('cup', $protein['unit']);
        }
    }

    public function test_bean_portions_are_standardized()
    {
        $ingredients = BurritoTestHelper::createIngredientSet();
        $riceBeans = $ingredients['rice_beans'];

        $beans = array_filter($riceBeans, fn($item) => str_contains(strtolower($item['name']), 'bean'));

        foreach ($beans as $bean) {
            $this->assertEquals(0.67, $bean['portion_size'],
                "Bean {$bean['name']} should have 2/3 cup portion");
            $this->assertEquals('cup', $bean['unit']);
        }
    }

    public function test_rice_portions_are_standardized()
    {
        $ingredients = BurritoTestHelper::createIngredientSet();
        $riceBeans = $ingredients['rice_beans'];

        $rice = array_filter($riceBeans, fn($item) => str_contains(strtolower($item['name']), 'rice'));

        foreach ($rice as $riceItem) {
            $this->assertEquals(0.5, $riceItem['portion_size'],
                "Rice {$riceItem['name']} should have 0.5 cup portion");
            $this->assertEquals('cup', $riceItem['unit']);
        }
    }

    public function test_salsa_portions_are_tablespoon_based()
    {
        $ingredients = BurritoTestHelper::createIngredientSet();
        $salsas = $ingredients['salsas'];

        foreach ($salsas as $salsa) {
            $this->assertEquals(2, $salsa['portion_size'],
                "Salsa {$salsa['name']} should have 2 tablespoon portion");
            $this->assertEquals('tbsp', $salsa['unit']);
        }
    }

    public function test_burrito_portion_calculation()
    {
        $config = BurritoTestHelper::createBurritoConfiguration();
        $portions = BurritoTestHelper::calculatePortions($config);

        // Verify calculated portions
        $this->assertArrayHasKey('Carnitas', $portions);
        $this->assertEquals(0.5, $portions['Carnitas']['amount']);

        $this->assertArrayHasKey('Black Beans', $portions);
        $this->assertEquals(0.67, $portions['Black Beans']['amount']);

        $this->assertArrayHasKey('Medium Salsa', $portions);
        $this->assertEquals(2, $portions['Medium Salsa']['amount']);
    }

    public function test_tortilla_size_is_standardized()
    {
        $config = BurritoTestHelper::createBurritoConfiguration();
        $this->assertEquals('14-inch', $config['tortilla']);
    }

    public function test_ingredient_categories_are_complete()
    {
        $ingredients = BurritoTestHelper::createIngredientSet();

        $expectedCategories = ['proteins', 'rice_beans', 'fresh_toppings', 'salsas', 'creamy'];
        $actualCategories = array_keys($ingredients);

        $this->assertEquals($expectedCategories, $actualCategories,
            'All ingredient categories must be present');
    }

    public function test_each_category_has_multiple_options()
    {
        $ingredients = BurritoTestHelper::createIngredientSet();

        foreach ($ingredients as $category => $items) {
            $this->assertGreaterThanOrEqual(2, count($items),
                "Category {$category} should have at least 2 options");
        }
    }

    public function test_portion_units_are_valid()
    {
        $ingredients = BurritoTestHelper::createIngredientSet();
        $validUnits = ['cup', 'tbsp', 'tsp', 'oz'];

        foreach ($ingredients as $category => $items) {
            foreach ($items as $item) {
                $this->assertContains($item['unit'], $validUnits,
                    "Ingredient {$item['name']} has invalid unit: {$item['unit']}");
            }
        }
    }
}
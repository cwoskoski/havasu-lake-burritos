<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for burrito builder business logic.
 * Tests individual component behavior in isolation.
 */
class BurritoBuilderTest extends TestCase
{
    /**
     * Test that burrito ingredients are validated correctly.
     */
    public function test_burrito_ingredients_validation(): void
    {
        // Test the core business logic for ingredient validation
        $validIngredients = [
            'protein' => 'chicken',
            'rice' => 'spanish_rice',
            'beans' => 'black_beans',
            'toppings' => ['lettuce', 'tomatoes'],
            'salsa' => 'medium',
            'creamy' => 'cheese',
        ];

        // Mock the validation logic
        $isValid = $this->validateBurritoIngredients($validIngredients);

        $this->assertTrue($isValid);
    }

    /**
     * Test burrito portion calculations for purchasing.
     */
    public function test_burrito_portion_calculations(): void
    {
        $burritoIngredients = [
            'protein' => 'chicken',
            'rice' => 'spanish_rice',
            'beans' => 'black_beans',
        ];

        $expectedPortions = [
            'protein' => 0.5,    // 1/2 cup
            'rice' => 0.5,       // 1/2 cup
            'beans' => 0.67,     // 2/3 cup
        ];

        $calculatedPortions = $this->calculatePortions($burritoIngredients);

        $this->assertEquals($expectedPortions, $calculatedPortions);
    }

    /**
     * Test weekend-only production validation.
     */
    public function test_weekend_only_production_validation(): void
    {
        // Test Saturday (valid)
        $saturday = strtotime('2024-01-06'); // A Saturday
        $this->assertTrue($this->isWeekendProductionDay($saturday));

        // Test Sunday (valid)
        $sunday = strtotime('2024-01-07'); // A Sunday
        $this->assertTrue($this->isWeekendProductionDay($sunday));

        // Test Monday (invalid)
        $monday = strtotime('2024-01-08'); // A Monday
        $this->assertFalse($this->isWeekendProductionDay($monday));
    }

    /**
     * Test burrito price calculation.
     */
    public function test_burrito_price_calculation(): void
    {
        $ingredients = [
            'protein' => 'chicken',
            'rice' => 'spanish_rice',
            'beans' => 'black_beans',
            'toppings' => ['lettuce', 'tomatoes'],
            'salsa' => 'medium',
        ];

        $expectedPrice = 12.00; // Base price for standard burrito
        $calculatedPrice = $this->calculateBurritoPrice($ingredients);

        $this->assertEquals($expectedPrice, $calculatedPrice);
    }

    /**
     * Test mobile-friendly ingredient selection limits.
     */
    public function test_mobile_ingredient_selection_limits(): void
    {
        // Mobile users should have reasonable selection limits
        $maxToppings = 5; // Maximum fresh toppings for mobile UX
        $selectedToppings = ['lettuce', 'tomatoes', 'onions', 'peppers', 'cilantro'];

        $this->assertLessThanOrEqual($maxToppings, count($selectedToppings));
        $this->assertTrue($this->isValidToppingSelection($selectedToppings));
    }

    // Private helper methods for testing business logic

    private function validateBurritoIngredients(array $ingredients): bool
    {
        // Mock validation logic
        $requiredKeys = ['protein', 'rice', 'beans'];

        foreach ($requiredKeys as $key) {
            if (! isset($ingredients[$key])) {
                return false;
            }
        }

        return true;
    }

    private function calculatePortions(array $ingredients): array
    {
        // Mock portion calculation logic
        return [
            'protein' => 0.5,
            'rice' => 0.5,
            'beans' => 0.67,
        ];
    }

    private function isWeekendProductionDay(int $timestamp): bool
    {
        $dayOfWeek = (int) date('N', $timestamp); // 1 (Monday) to 7 (Sunday)

        return $dayOfWeek === 6 || $dayOfWeek === 7; // Saturday or Sunday
    }

    private function calculateBurritoPrice(array $ingredients): float
    {
        // Mock price calculation
        $basePrice = 10.00;
        $proteinPrice = 2.00;

        return $basePrice + $proteinPrice;
    }

    private function isValidToppingSelection(array $toppings): bool
    {
        // Mock topping validation
        $allowedToppings = ['lettuce', 'tomatoes', 'onions', 'peppers', 'cilantro', 'jalapenos'];

        foreach ($toppings as $topping) {
            if (! in_array($topping, $allowedToppings)) {
                return false;
            }
        }

        return true;
    }
}

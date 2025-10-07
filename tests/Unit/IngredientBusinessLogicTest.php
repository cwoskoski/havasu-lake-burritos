<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\IngredientCategory;
use App\Models\Ingredient;
use InvalidArgumentException;

/**
 * TDD Tests for Ingredient Business Logic
 * Following Red-Green-Refactor methodology with modern PHP 8.2+ features
 */
describe('Ingredient Portion Management', function () {
    beforeEach(function () {
        $this->proteinIngredient = new Ingredient([
            'name' => 'Carnitas',
            'category' => IngredientCategory::PROTEINS,
            'standard_portion_oz' => 4.0,
            'calories_per_oz' => 85.5,
            'is_premium' => false,
        ]);

        $this->salsaIngredient = new Ingredient([
            'name' => 'Hot Salsa',
            'category' => IngredientCategory::SALSAS,
            'standard_portion_oz' => 1.0,
            'calories_per_oz' => 12.0,
            'is_premium' => false,
        ]);
    });

    it('calculates portion weight accurately based on multiplier', function () {
        // Red phase - these will fail initially
        expect($this->proteinIngredient->getPortionWeight(1.0))->toBe(4.0);
        expect($this->proteinIngredient->getPortionWeight(0.5))->toBe(2.0);
        expect($this->proteinIngredient->getPortionWeight(1.5))->toBe(6.0);
    });

    it('calculates portion calories correctly', function () {
        expect($this->proteinIngredient->getPortionCalories(1.0))->toBe(342.0); // 4.0 * 85.5
        expect($this->proteinIngredient->getPortionCalories(0.5))->toBe(171.0);
        expect($this->salsaIngredient->getPortionCalories(2.0))->toBe(24.0); // 2.0 * 12.0
    });

    it('validates portion multipliers within business rules', function () {
        // Valid portions should not throw
        expect(fn () => $this->proteinIngredient->getPortionWeight(0.25))->not->toThrow(InvalidArgumentException::class);
        expect(fn () => $this->proteinIngredient->getPortionWeight(3.0))->not->toThrow(InvalidArgumentException::class);

        // Invalid portions should throw
        expect(fn () => $this->proteinIngredient->getPortionWeight(0.0))->toThrow(InvalidArgumentException::class);
        expect(fn () => $this->proteinIngredient->getPortionWeight(-1.0))->toThrow(InvalidArgumentException::class);
        expect(fn () => $this->proteinIngredient->getPortionWeight(5.1))->toThrow(InvalidArgumentException::class);
    });

    it('provides category-specific standard multipliers', function () {
        $protein = new Ingredient(['category' => IngredientCategory::PROTEINS]);
        $riceBeans = new Ingredient(['category' => IngredientCategory::RICE_BEANS]);
        $toppings = new Ingredient(['category' => IngredientCategory::FRESH_TOPPINGS]);
        $salsa = new Ingredient(['category' => IngredientCategory::SALSAS]);
        $creamy = new Ingredient(['category' => IngredientCategory::CREAMY]);

        expect($protein->getStandardPortionMultiplier())->toBe(1.0);
        expect($riceBeans->getStandardPortionMultiplier())->toBe(1.0);
        expect($toppings->getStandardPortionMultiplier())->toBe(0.5);
        expect($salsa->getStandardPortionMultiplier())->toBe(2.0);
        expect($creamy->getStandardPortionMultiplier())->toBe(1.0);
    });
});

describe('Ingredient Category Validation', function () {
    it('validates portion size limits per category', function () {
        $validProtein = new Ingredient([
            'category' => IngredientCategory::PROTEINS,
            'standard_portion_oz' => 6.0, // Within 8oz limit
        ]);

        $invalidProtein = new Ingredient([
            'category' => IngredientCategory::PROTEINS,
            'standard_portion_oz' => 10.0, // Exceeds 8oz limit
        ]);

        $validSalsa = new Ingredient([
            'category' => IngredientCategory::SALSAS,
            'standard_portion_oz' => 1.5, // Within 2oz limit
        ]);

        $invalidSalsa = new Ingredient([
            'category' => IngredientCategory::SALSAS,
            'standard_portion_oz' => 3.0, // Exceeds 2oz limit
        ]);

        expect($validProtein->isValidPortionSize())->toBeTrue();
        expect($invalidProtein->isValidPortionSize())->toBeFalse();
        expect($validSalsa->isValidPortionSize())->toBeTrue();
        expect($invalidSalsa->isValidPortionSize())->toBeFalse();
    });

    it('returns appropriate max portion size by category', function () {
        $protein = new Ingredient(['category' => IngredientCategory::PROTEINS]);
        $salsa = new Ingredient(['category' => IngredientCategory::SALSAS]);
        $toppings = new Ingredient(['category' => IngredientCategory::FRESH_TOPPINGS]);

        expect($protein->getMaxPortionSize())->toBe(8.0);
        expect($salsa->getMaxPortionSize())->toBe(2.0);
        expect($toppings->getMaxPortionSize())->toBe(4.0);
    });
});

describe('Ingredient Cost Calculations', function () {
    beforeEach(function () {
        $this->standardIngredient = new Ingredient([
            'standard_portion_oz' => 4.0,
            'is_premium' => false,
        ]);

        $this->premiumIngredient = new Ingredient([
            'standard_portion_oz' => 4.0,
            'is_premium' => true,
        ]);
    });

    it('calculates base cost for standard ingredients', function () {
        expect($this->standardIngredient->getBaseCost())->toBe(2.50); // $0.625 per oz
    });

    it('applies premium pricing multiplier', function () {
        expect($this->premiumIngredient->getBaseCost())->toBe(3.75); // 1.5x multiplier
    });

    it('calculates portion-based costs', function () {
        expect($this->standardIngredient->getPortionCost(0.5))->toBe(1.25); // Half portion
        expect($this->premiumIngredient->getPortionCost(2.0))->toBe(7.50);   // Double portion
    });

    it('provides cost per serving with correct precision', function () {
        $ingredient = new Ingredient([
            'standard_portion_oz' => 3.33,
            'is_premium' => false,
        ]);

        expect($ingredient->getBaseCost())->toBe(2.08); // Rounded to cents
    });
});

describe('Ingredient Availability Logic', function () {
    it('checks availability based on active status', function () {
        $activeIngredient = new Ingredient(['is_active' => true]);
        $inactiveIngredient = new Ingredient(['is_active' => false]);

        expect($activeIngredient->isAvailable())->toBeTrue();
        expect($inactiveIngredient->isAvailable())->toBeFalse();
    });

    it('provides availability status with reasons', function () {
        $activeIngredient = new Ingredient(['is_active' => true]);
        $inactiveIngredient = new Ingredient(['is_active' => false]);

        expect($activeIngredient->getAvailabilityStatus())->toBe([
            'available' => true,
            'reason' => null,
        ]);

        expect($inactiveIngredient->getAvailabilityStatus())->toBe([
            'available' => false,
            'reason' => 'Ingredient is currently unavailable',
        ]);
    });
});

describe('Ingredient Nutritional Information', function () {
    beforeEach(function () {
        $this->ingredient = new Ingredient([
            'standard_portion_oz' => 4.0,
            'calories_per_oz' => 85.5,
            'dietary_info' => ['vegetarian', 'gluten-free'],
            'allergens' => ['soy'],
        ]);
    });

    it('provides nutritional facts per standard serving', function () {
        $facts = $this->ingredient->getNutritionalFacts();

        expect($facts)->toHaveKey('calories');
        expect($facts)->toHaveKey('portion_size');
        expect($facts['calories'])->toBe(342.0);
        expect($facts['portion_size'])->toBe(4.0);
    });

    it('checks dietary restrictions compliance', function () {
        expect($this->ingredient->isDietaryCompliant(['vegetarian']))->toBeTrue();
        expect($this->ingredient->isDietaryCompliant(['vegan']))->toBeFalse();
        expect($this->ingredient->isDietaryCompliant(['vegetarian', 'gluten-free']))->toBeTrue();
    });

    it('identifies allergen concerns', function () {
        expect($this->ingredient->hasAllergens(['soy']))->toBeTrue();
        expect($this->ingredient->hasAllergens(['nuts']))->toBeFalse();
        expect($this->ingredient->hasAllergens(['soy', 'dairy']))->toBeTrue();
    });
});

describe('Ingredient Business Rules', function () {
    it('enforces protein category business rules', function () {
        $protein = new Ingredient([
            'category' => IngredientCategory::PROTEINS,
            'standard_portion_oz' => 4.0,
        ]);

        expect($protein->getBusinessRules())->toContain('Maximum 2 proteins per burrito');
        expect($protein->getMaxSelectionsPerBurrito())->toBe(2);
    });

    it('enforces salsa category business rules', function () {
        $salsa = new Ingredient([
            'category' => IngredientCategory::SALSAS,
            'standard_portion_oz' => 1.0,
        ]);

        expect($salsa->getBusinessRules())->toContain('Maximum 3 salsas per burrito');
        expect($salsa->getMaxSelectionsPerBurrito())->toBe(3);
    });

    it('provides category description and usage guidelines', function () {
        $protein = new Ingredient(['category' => IngredientCategory::PROTEINS]);

        $description = $protein->getCategoryDescription();
        expect($description)->toContain('protein');
        expect($description)->toContain('0.5 cup');
    });
});

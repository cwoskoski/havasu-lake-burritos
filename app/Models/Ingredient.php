<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IngredientCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'standard_portion_oz',
        'calories_per_oz',
        'allergens',
        'dietary_info',
        'color_hex',
        'sort_order',
        'is_active',
        'is_premium',
    ];

    protected $casts = [
        'category' => IngredientCategory::class,
        'standard_portion_oz' => 'decimal:2',
        'calories_per_oz' => 'decimal:1',
        'allergens' => 'array',
        'dietary_info' => 'array',
        'is_active' => 'boolean',
        'is_premium' => 'boolean',
    ];

    public function ingredientWeeks(): HasMany
    {
        return $this->hasMany(IngredientWeek::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, IngredientCategory $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // === PORTION MANAGEMENT ===

    /**
     * Calculate the weight of a portion based on multiplier.
     */
    public function getPortionWeight(float $multiplier): float
    {
        $this->validatePortionMultiplier($multiplier);

        return round($this->standard_portion_oz * $multiplier, 2);
    }

    /**
     * Calculate calories for a given portion multiplier.
     */
    public function getPortionCalories(float $multiplier): float
    {
        $portionWeight = $this->getPortionWeight($multiplier);

        return round($portionWeight * $this->calories_per_oz, 1);
    }

    /**
     * Get the standard portion multiplier for this ingredient's category.
     */
    public function getStandardPortionMultiplier(): float
    {
        return match ($this->category) {
            IngredientCategory::PROTEINS => 1.0,
            IngredientCategory::RICE_BEANS => 1.0,
            IngredientCategory::FRESH_TOPPINGS => 0.5,
            IngredientCategory::SALSAS => 2.0,
            IngredientCategory::CREAMY => 1.0,
        };
    }

    /**
     * Validate that portion multiplier is within business rules.
     */
    private function validatePortionMultiplier(float $multiplier): void
    {
        if ($multiplier <= 0.0) {
            throw new InvalidArgumentException('Portion multiplier must be greater than 0');
        }

        if ($multiplier > 5.0) {
            throw new InvalidArgumentException('Portion multiplier cannot exceed 5.0');
        }
    }

    // === CATEGORY VALIDATION ===

    /**
     * Check if the portion size is valid for this ingredient's category.
     */
    public function isValidPortionSize(): bool
    {
        return $this->standard_portion_oz <= $this->getMaxPortionSize();
    }

    /**
     * Get the maximum allowed portion size for this ingredient's category.
     */
    public function getMaxPortionSize(): float
    {
        return match ($this->category) {
            IngredientCategory::PROTEINS => 8.0,
            IngredientCategory::RICE_BEANS => 6.0,
            IngredientCategory::FRESH_TOPPINGS => 4.0,
            IngredientCategory::SALSAS => 2.0,
            IngredientCategory::CREAMY => 4.0,
        };
    }

    // === COST CALCULATIONS ===

    private const BASE_COST_PER_OZ = 0.625;

    private const PREMIUM_MULTIPLIER = 1.5;

    /**
     * Calculate the base cost for a standard portion.
     */
    public function getBaseCost(): float
    {
        $baseCost = $this->standard_portion_oz * self::BASE_COST_PER_OZ;

        if ($this->is_premium) {
            $baseCost *= self::PREMIUM_MULTIPLIER;
        }

        return round($baseCost, 2);
    }

    /**
     * Calculate cost for a specific portion multiplier.
     */
    public function getPortionCost(float $multiplier): float
    {
        return round($this->getBaseCost() * $multiplier, 2);
    }

    // === AVAILABILITY LOGIC ===

    /**
     * Check if ingredient is currently available.
     */
    public function isAvailable(): bool
    {
        return $this->is_active;
    }

    /**
     * Get availability status with reason if unavailable.
     */
    public function getAvailabilityStatus(): array
    {
        return [
            'available' => $this->is_active,
            'reason' => $this->is_active ? null : 'Ingredient is currently unavailable',
        ];
    }

    // === NUTRITIONAL INFORMATION ===

    /**
     * Get nutritional facts for standard serving.
     */
    public function getNutritionalFacts(): array
    {
        return [
            'calories' => round($this->standard_portion_oz * $this->calories_per_oz, 1),
            'portion_size' => (float) $this->standard_portion_oz,
            'dietary_info' => $this->dietary_info ?? [],
            'allergens' => $this->allergens ?? [],
        ];
    }

    /**
     * Check if ingredient complies with dietary restrictions.
     */
    public function isDietaryCompliant(array $requirements): bool
    {
        $dietaryInfo = $this->dietary_info ?? [];

        foreach ($requirements as $requirement) {
            if (! in_array($requirement, $dietaryInfo, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if ingredient contains any of the specified allergens.
     */
    public function hasAllergens(array $allergens): bool
    {
        $ingredientAllergens = $this->allergens ?? [];

        return ! empty(array_intersect($allergens, $ingredientAllergens));
    }

    // === BUSINESS RULES ===

    /**
     * Get business rules for this ingredient category.
     */
    public function getBusinessRules(): array
    {
        return match ($this->category) {
            IngredientCategory::PROTEINS => [
                'Maximum 2 proteins per burrito',
                'Standard portion is 0.5 cup (4oz)',
            ],
            IngredientCategory::RICE_BEANS => [
                'Maximum 1 rice and 1 bean per burrito',
                'Standard portions: Rice 0.5 cup, Beans 2/3 cup',
            ],
            IngredientCategory::FRESH_TOPPINGS => [
                'No limit on fresh toppings',
                'Standard portion is 0.25 cup',
            ],
            IngredientCategory::SALSAS => [
                'Maximum 3 salsas per burrito',
                'Standard portion is 2 tablespoons',
            ],
            IngredientCategory::CREAMY => [
                'Maximum 2 creamy additions per burrito',
                'Standard portions vary by item',
            ],
        };
    }

    /**
     * Get maximum number of selections allowed per burrito for this category.
     */
    public function getMaxSelectionsPerBurrito(): int
    {
        return match ($this->category) {
            IngredientCategory::PROTEINS => 2,
            IngredientCategory::RICE_BEANS => 2, // 1 rice + 1 bean
            IngredientCategory::FRESH_TOPPINGS => 10, // Virtually unlimited
            IngredientCategory::SALSAS => 3,
            IngredientCategory::CREAMY => 2,
        };
    }

    /**
     * Get category description and usage guidelines.
     */
    public function getCategoryDescription(): string
    {
        return match ($this->category) {
            IngredientCategory::PROTEINS => 'Choose your protein base. Standard serving is 0.5 cup (4oz). Maximum 2 proteins per burrito.',
            IngredientCategory::RICE_BEANS => 'Select rice and beans. Rice portions are 0.5 cup, bean portions are 2/3 cup.',
            IngredientCategory::FRESH_TOPPINGS => 'Fresh vegetables and toppings. Standard portion is 0.25 cup each.',
            IngredientCategory::SALSAS => 'Choose your salsas. Standard portion is 2 tablespoons. Maximum 3 per burrito.',
            IngredientCategory::CREAMY => 'Cheese and creamy additions. Varies by item. Maximum 2 per burrito.',
        };
    }
}

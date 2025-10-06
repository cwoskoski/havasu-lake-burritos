<?php

namespace App\Models;

use App\Enums\IngredientCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

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
}

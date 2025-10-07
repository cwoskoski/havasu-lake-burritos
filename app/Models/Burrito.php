<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Burrito extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
    ];

    protected $casts = [];

    protected $attributes = [];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'burrito_ingredients')
            ->withPivot('portion_multiplier', 'custom_instructions')
            ->withTimestamps();
    }

    /**
     * Get the price in dollars.
     */
    public function getPrice(): float
    {
        return $this->price_cents / 100;
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPrice(): string
    {
        return '$'.number_format($this->getPrice(), 2);
    }

    /**
     * Calculate total price for quantity.
     */
    public function getTotalPrice(): float
    {
        return $this->getPrice() * $this->quantity;
    }

    /**
     * Get formatted total price.
     */
    public function getFormattedTotalPrice(): string
    {
        return '$'.number_format($this->getTotalPrice(), 2);
    }
}

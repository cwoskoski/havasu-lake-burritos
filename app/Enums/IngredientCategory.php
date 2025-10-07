<?php

namespace App\Enums;

enum IngredientCategory: string
{
    case PROTEINS = 'proteins';
    case RICE_BEANS = 'rice_beans';
    case FRESH_TOPPINGS = 'fresh_toppings';
    case SALSAS = 'salsas';
    case CREAMY = 'creamy';

    public function label(): string
    {
        return match ($this) {
            self::PROTEINS => 'Proteins',
            self::RICE_BEANS => 'Rice & Beans',
            self::FRESH_TOPPINGS => 'Fresh Toppings',
            self::SALSAS => 'Salsas',
            self::CREAMY => 'Creamy',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class BurritoBuilderController extends Controller
{
    /**
     * Show the burrito builder interface
     */
    public function index(): View
    {
        $steps = [
            [
                'title' => 'Choose Proteins',
                'description' => 'Select your favorite proteins',
                'category' => 'proteins'
            ],
            [
                'title' => 'Rice & Beans',
                'description' => 'Pick your rice and bean varieties',
                'category' => 'rice_beans'
            ],
            [
                'title' => 'Fresh Toppings',
                'description' => 'Add fresh vegetables and garnishes',
                'category' => 'fresh_toppings'
            ],
            [
                'title' => 'Choose Salsas',
                'description' => 'Select your salsa preferences',
                'category' => 'salsas'
            ],
            [
                'title' => 'Creamy Additions',
                'description' => 'Add cheese and sour cream',
                'category' => 'creamy'
            ]
        ];

        $currentStep = 0;

        return view('burrito-builder.index', compact('steps', 'currentStep'));
    }

    /**
     * Show the order review page
     */
    public function review(): View
    {
        return view('burrito-builder.review');
    }

    /**
     * Get active ingredients for the frontend
     */
    public function getActiveIngredients(): array
    {
        // In a real implementation, this would fetch from the Ingredient model
        // with proper category filtering and active status
        return [
            'proteins' => [
                [
                    'id' => 1,
                    'name' => 'Grilled Chicken',
                    'category' => 'proteins',
                    'portion_size' => 0.5,
                    'description' => 'Tender grilled chicken breast'
                ],
                [
                    'id' => 2,
                    'name' => 'Carnitas',
                    'category' => 'proteins',
                    'portion_size' => 0.5,
                    'description' => 'Slow-cooked pork shoulder'
                ],
                [
                    'id' => 3,
                    'name' => 'Black Bean',
                    'category' => 'proteins',
                    'portion_size' => 0.5,
                    'description' => 'Seasoned black beans (vegetarian)'
                ],
            ],
            'riceBeans' => [
                [
                    'id' => 4,
                    'name' => 'Cilantro Lime Rice',
                    'category' => 'rice_beans',
                    'portion_size' => 0.5,
                    'description' => 'Fresh cilantro and lime-infused rice'
                ],
                [
                    'id' => 5,
                    'name' => 'Black Beans',
                    'category' => 'rice_beans',
                    'portion_size' => 0.67,
                    'description' => 'Seasoned black beans'
                ],
                [
                    'id' => 6,
                    'name' => 'Pinto Beans',
                    'category' => 'rice_beans',
                    'portion_size' => 0.67,
                    'description' => 'Traditional pinto beans'
                ],
            ],
            'freshToppings' => [
                [
                    'id' => 7,
                    'name' => 'Lettuce',
                    'category' => 'fresh_toppings',
                    'portion_size' => null,
                    'description' => 'Crisp romaine lettuce'
                ],
                [
                    'id' => 8,
                    'name' => 'Tomatoes',
                    'category' => 'fresh_toppings',
                    'portion_size' => null,
                    'description' => 'Fresh diced tomatoes'
                ],
                [
                    'id' => 9,
                    'name' => 'Red Onion',
                    'category' => 'fresh_toppings',
                    'portion_size' => null,
                    'description' => 'Thinly sliced red onion'
                ],
            ],
            'salsas' => [
                [
                    'id' => 10,
                    'name' => 'Pico de Gallo',
                    'category' => 'salsas',
                    'portion_size' => null,
                    'description' => 'Fresh tomato, onion, and cilantro salsa'
                ],
                [
                    'id' => 11,
                    'name' => 'Salsa Verde',
                    'category' => 'salsas',
                    'portion_size' => null,
                    'description' => 'Tomatillo and green chili salsa'
                ],
                [
                    'id' => 12,
                    'name' => 'Hot Salsa Roja',
                    'category' => 'salsas',
                    'portion_size' => null,
                    'description' => 'Spicy red salsa with heat'
                ],
            ],
            'creamy' => [
                [
                    'id' => 13,
                    'name' => 'Cheese',
                    'category' => 'creamy',
                    'portion_size' => null,
                    'description' => 'Shredded Mexican cheese blend'
                ],
                [
                    'id' => 14,
                    'name' => 'Sour Cream',
                    'category' => 'creamy',
                    'portion_size' => null,
                    'description' => 'Tangy sour cream'
                ],
                [
                    'id' => 15,
                    'name' => 'Guacamole',
                    'category' => 'creamy',
                    'portion_size' => null,
                    'description' => 'Fresh avocado guacamole'
                ],
            ]
        ];
    }

    /**
     * Get current availability
     */
    public function getAvailability(): array
    {
        // In a real implementation, this would fetch from ProductionSchedule model
        return [
            'saturday' => 25,
            'sunday' => 30,
            'isActive' => true,
            'lastUpdated' => now()->toISOString()
        ];
    }
}
# Current Ingredients from Paper Forms

## 🥩 PROTEIN (Select only one!)
- Pork Barbacoa (0.5 cup per burrito)
- Chicken (0.5 cup per burrito)
- "More to come" (indicates future ingredient rotation)

## 🍚 RICE & BEANS
- Spanish Rice (0.5 cup per burrito)
- Boiled Beans (0.667 cup per burrito)
- Black Beans (0.667 cup per burrito)
- Refried Beans (0.667 cup per burrito)

## 🥬 FRESH TOPPINGS (Multiple selections allowed)
- Lettuce
- Tomato
- Chopped Onions
- Cilantro

## 🌶 SALSAS (Multiple selections allowed)
- Mild Salsa
- Hot Salsa

## 🧀 CREAMY (Multiple selections allowed)
- Cheese
- Sour Cream

## Fixed Components
- 14-inch Tortilla (1 per burrito)
- Base Price: $7.50

## Database Seeding Data
```php
// Initial ingredient seeding based on current paper forms
$ingredients = [
    // Proteins
    ['name' => 'Pork Barbacoa', 'category' => 'proteins', 'portion_size' => 0.5],
    ['name' => 'Chicken', 'category' => 'proteins', 'portion_size' => 0.5],

    // Rice & Beans
    ['name' => 'Spanish Rice', 'category' => 'rice_beans', 'portion_size' => 0.5],
    ['name' => 'Boiled Beans', 'category' => 'rice_beans', 'portion_size' => 0.667],
    ['name' => 'Black Beans', 'category' => 'rice_beans', 'portion_size' => 0.667],
    ['name' => 'Refried Beans', 'category' => 'rice_beans', 'portion_size' => 0.667],

    // Fresh Toppings (variable portions)
    ['name' => 'Lettuce', 'category' => 'fresh_toppings', 'portion_size' => null],
    ['name' => 'Tomato', 'category' => 'fresh_toppings', 'portion_size' => null],
    ['name' => 'Chopped Onions', 'category' => 'fresh_toppings', 'portion_size' => null],
    ['name' => 'Cilantro', 'category' => 'fresh_toppings', 'portion_size' => null],

    // Salsas
    ['name' => 'Mild Salsa', 'category' => 'salsas', 'portion_size' => null],
    ['name' => 'Hot Salsa', 'category' => 'salsas', 'portion_size' => null],

    // Creamy
    ['name' => 'Cheese', 'category' => 'creamy', 'portion_size' => null],
    ['name' => 'Sour Cream', 'category' => 'creamy', 'portion_size' => null],
];
```

## Selection Rules from Paper Forms
1. **Protein**: "Select only one!" - Radio button behavior
2. **Rice & Beans**: Multiple selections allowed - Checkbox behavior
3. **Fresh Toppings**: Multiple selections allowed - Checkbox behavior
4. **Salsas**: Multiple selections allowed - Checkbox behavior
5. **Creamy**: Multiple selections allowed - Checkbox behavior

## Shopping List Calculations
Based on standard portions and number of orders:

### For 10 Burritos Example:
- **Pork Barbacoa** (if 6 orders): 6 × 0.5 = 3 cups needed
- **Chicken** (if 4 orders): 4 × 0.5 = 2 cups needed
- **Spanish Rice** (if 8 orders): 8 × 0.5 = 4 cups needed
- **Black Beans** (if 10 orders): 10 × 0.667 = 6.67 cups needed
- **14-inch Tortillas**: 10 tortillas needed
- **Fresh Toppings**: Count by popularity/selection frequency
# Database Schema Design

## Entity Relationship Overview

```
Users (Laravel default)
    ↓ (1:many)
Orders ← (1:many) → Burritos ← (many:many) → Ingredients
    ↓                    ↓
OrderItems          BurritoIngredients

Ingredients ← (1:many) → IngredientWeeks
    ↓ (1:many)
IngredientUsage

ProductionDays (standalone)
PurchaseOrders ← (1:many) → PurchaseOrderItems → Ingredients
```

## Core Tables

### users (Laravel default + extensions)
```sql
id (bigint, primary key)
name (string)
email (string, unique)
email_verified_at (timestamp, nullable)
password (string)
phone (string, nullable)
role (enum: 'customer', 'admin', default: 'customer')
preferences (json, nullable) -- dietary restrictions, favorites
created_at (timestamp)
updated_at (timestamp)
```

### ingredients
```sql
id (bigint, primary key)
name (string) -- e.g., "Pork Barbacoa", "Spanish Rice"
category (enum: 'proteins', 'rice_beans', 'fresh_toppings', 'salsas', 'creamy')
portion_size (decimal, 8,3, nullable) -- 0.5 for proteins, 0.667 for beans, null for toppings
selection_type (enum: 'single', 'multiple') -- 'single' for proteins, 'multiple' for others
display_order (integer) -- order to display in category
emoji (string, nullable) -- category emoji for display
description (text, nullable)
image_path (string, nullable)
base_cost_per_unit (decimal, 8,2) -- cost per cup/portion
allergens (json, nullable) -- ["gluten", "dairy", etc.]
is_active (boolean, default: true)
created_at (timestamp)
updated_at (timestamp)

-- Indexes
INDEX idx_ingredients_category (category)
INDEX idx_ingredients_active (is_active)
INDEX idx_ingredients_display_order (display_order)
```

### ingredient_weeks
```sql
id (bigint, primary key)
ingredient_id (bigint, foreign key → ingredients.id)
week_start (date) -- Monday of the week
week_end (date) -- Sunday of the week
is_available (boolean, default: true)
cost_per_unit (decimal, 8,2) -- weekly pricing
notes (text, nullable) -- "Local organic this week"
created_at (timestamp)
updated_at (timestamp)

-- Indexes
INDEX idx_ingredient_weeks_dates (week_start, week_end)
INDEX idx_ingredient_weeks_ingredient (ingredient_id)
UNIQUE KEY unique_ingredient_week (ingredient_id, week_start)
```

### production_days
```sql
id (bigint, primary key)
production_date (date, unique) -- Saturday or Sunday only
max_burritos (integer) -- daily production limit
burritos_sold (integer, default: 0)
is_active (boolean, default: true) -- can disable specific days
notes (text, nullable) -- "Special holiday hours"
created_at (timestamp)
updated_at (timestamp)

-- Indexes
INDEX idx_production_days_date (production_date)
INDEX idx_production_days_active (is_active)
```

### orders
```sql
id (bigint, primary key)
user_id (bigint, foreign key → users.id)
production_day_id (bigint, foreign key → production_days.id)
order_number (string, unique) -- "HLB-20241005-001"
status (enum: 'cart', 'pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled')
total_amount (decimal, 8,2)
customer_name (string)
customer_email (string)
customer_phone (string, nullable)
special_instructions (text, nullable)
ordered_at (timestamp, nullable) -- when moved from cart to pending
confirmed_at (timestamp, nullable)
completed_at (timestamp, nullable)
created_at (timestamp)
updated_at (timestamp)

-- Indexes
INDEX idx_orders_user (user_id)
INDEX idx_orders_production_day (production_day_id)
INDEX idx_orders_status (status)
INDEX idx_orders_order_number (order_number)
```

### burritos
```sql
id (bigint, primary key)
order_id (bigint, foreign key → orders.id)
name (string, nullable) -- custom name like "My Favorite"
quantity (integer, default: 1)
base_price (decimal, 8,2) -- base burrito price
total_price (decimal, 8,2) -- including ingredient costs
notes (text, nullable) -- "Extra spicy"
created_at (timestamp)
updated_at (timestamp)

-- Indexes
INDEX idx_burritos_order (order_id)
```

### burrito_ingredients (pivot table)
```sql
id (bigint, primary key)
burrito_id (bigint, foreign key → burritos.id)
ingredient_id (bigint, foreign key → ingredients.id)
quantity (decimal, 8,3) -- cups/portions (0.5 for protein, 0.667 for beans)
cost_per_unit (decimal, 8,2) -- snapshot of cost at order time
total_cost (decimal, 8,2) -- quantity * cost_per_unit
created_at (timestamp)
updated_at (timestamp)

-- Indexes
INDEX idx_burrito_ingredients_burrito (burrito_id)
INDEX idx_burrito_ingredients_ingredient (ingredient_id)
UNIQUE KEY unique_burrito_ingredient (burrito_id, ingredient_id)
```

## Inventory & Purchasing Tables

### ingredient_usage
```sql
id (bigint, primary key)
ingredient_id (bigint, foreign key → ingredients.id)
production_date (date)
planned_quantity (decimal, 8,3) -- expected usage based on orders
actual_quantity (decimal, 8,3, nullable) -- actual usage after production
waste_quantity (decimal, 8,3, default: 0) -- spoilage/waste
cost_per_unit (decimal, 8,2) -- cost at time of usage
total_cost (decimal, 8,2)
notes (text, nullable)
created_at (timestamp)
updated_at (timestamp)

-- Indexes
INDEX idx_ingredient_usage_ingredient_date (ingredient_id, production_date)
INDEX idx_ingredient_usage_date (production_date)
```

### purchase_orders
```sql
id (bigint, primary key)
po_number (string, unique) -- "PO-20241005-001"
supplier_name (string, nullable)
order_date (date)
delivery_date (date, nullable)
status (enum: 'draft', 'sent', 'confirmed', 'delivered', 'cancelled')
total_amount (decimal, 8,2)
notes (text, nullable)
created_at (timestamp)
updated_at (timestamp)

-- Indexes
INDEX idx_purchase_orders_status (status)
INDEX idx_purchase_orders_order_date (order_date)
```

### purchase_order_items
```sql
id (bigint, primary key)
purchase_order_id (bigint, foreign key → purchase_orders.id)
ingredient_id (bigint, foreign key → ingredients.id)
quantity_ordered (decimal, 8,3) -- pounds, cases, etc.
unit_type (string) -- "lbs", "cases", "bags"
cost_per_unit (decimal, 8,2)
total_cost (decimal, 8,2)
quantity_received (decimal, 8,3, nullable)
created_at (timestamp)
updated_at (timestamp)

-- Indexes
INDEX idx_purchase_order_items_po (purchase_order_id)
INDEX idx_purchase_order_items_ingredient (ingredient_id)
```

## Business Logic Constraints

### Portion Standards (Application Level)
```php
// Standard portions per burrito
const PORTION_STANDARDS = [
    'proteins' => 0.5,      // 1/2 cup
    'rice_beans' => [
        'rice' => 0.5,      // 1/2 cup
        'beans' => 0.667,   // 2/3 cup
    ],
    'tortilla' => 1,        // 1 x 14-inch tortilla
    // Other toppings variable based on selection
];
```

### Production Day Validation
```php
// Only Saturdays (6) and Sundays (0)
$allowedDays = [0, 6]; // Carbon dayOfWeek values
```

### Order Status Flow
```
cart → pending → confirmed → preparing → ready → completed
                    ↓
                cancelled (from any status except completed)
```

## Indexes and Performance

### Critical Indexes
- Production day lookups for availability checking
- Ingredient availability by week
- Order tracking by customer and status
- Real-time inventory calculations

### Caching Strategy
- Current week ingredients (Redis, 1 hour TTL)
- Daily production limits (Redis, updated real-time)
- Order counts by production day (Redis, real-time)

## Data Seeding Requirements

### Essential Seed Data
1. **Admin User**: Default admin account
2. **Current Ingredients**: Exact ingredients from paper forms
   - 🥩 PROTEIN: Pork Barbacoa, Chicken (single selection)
   - 🍚 RICE & BEANS: Spanish Rice, Boiled/Black/Refried Beans (multiple)
   - 🥬 FRESH TOPPINGS: Lettuce, Tomato, Chopped Onions, Cilantro (multiple)
   - 🌶 SALSAS: Mild Salsa, Hot Salsa (multiple)
   - 🧀 CREAMY: Cheese, Sour Cream (multiple)
3. **Ingredient Weeks**: Current and next week's availability
4. **Production Days**: Next 4 weekends with limits
5. **Base Configuration**: $7.50 price, 14-inch tortillas

### Test Data (Factory-generated)
1. **Customers**: 50 fake customers
2. **Orders**: Various order states and dates
3. **Historical Data**: Previous weeks for reporting

## Migration Order
1. Create `ingredients` table
2. Create `ingredient_weeks` table
3. Create `production_days` table
4. Create `orders` table
5. Create `burritos` table
6. Create `burrito_ingredients` pivot table
7. Create `ingredient_usage` table
8. Create `purchase_orders` table
9. Create `purchase_order_items` table
10. Add foreign key constraints
11. Create indexes

## Backup and Recovery Strategy
- **Daily Backups**: Full database backup every night
- **Real-time Replication**: For high availability
- **Point-in-time Recovery**: Transaction log backups every 15 minutes
- **Critical Data**: Orders and customer data priority for recovery
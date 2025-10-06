# Frontend Architecture & Component Design

## Technology Stack (Laravel Breeze + Alpine.js)
- **Authentication**: Laravel Breeze (minimal, simple)
- **Templates**: Blade components and layouts
- **CSS Framework**: TailwindCSS 4.0 (included with Breeze)
- **JavaScript**: Alpine.js (included with Breeze)
- **Build Tool**: Vite with HMR (configured by Breeze)
- **Permissions**: Spatie Laravel Permission for admin/customer roles

## Page Structure & Routing

### Public Pages
```
/ (Homepage)
├── /order (Burrito Builder - requires auth)
├── /login
├── /register
├── /about
└── /contact
```

### Customer Dashboard
```
/dashboard
├── /orders (Order history)
├── /profile (Account settings)
└── /favorites (Saved burrito configurations)
```

### Admin Dashboard
```
/admin
├── /ingredients (Ingredient management)
├── /production (Production planning)
├── /orders (Order management)
├── /analytics (Business intelligence)
└── /settings (System configuration)
```

## Component Architecture

### Global Components

#### 1. Navigation Header (`components/navigation.blade.php`)
```php
@props(['showOrderCount' => false])

<nav class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Logo, Navigation Links, User Menu -->
        <!-- Weekend Schedule Indicator -->
        <!-- Cart Count (if authenticated) -->
    </div>
</nav>
```

#### 2. Availability Banner (`components/availability-banner.blade.php`)
```php
@props(['remainingBurritos', 'productionDate'])

<div class="bg-gradient-to-r from-blue-600 to-orange-500 text-white">
    <div class="max-w-4xl mx-auto px-6 py-4 text-center">
        <!-- Countdown Display -->
        <!-- Next Production Day (if sold out) -->
    </div>
</div>
```

#### 3. Footer (`components/footer.blade.php`)
```php
<footer class="bg-gray-900 text-white">
    <!-- Contact Info, Hours, Social Links -->
</footer>
```

### Burrito Builder Components

#### 1. Track Progress Indicator (`components/track-progress.blade.php`)
```php
@props(['currentStep', 'completedSteps'])

<div class="flex justify-between items-center mb-8">
    @foreach(['proteins', 'rice_beans', 'fresh_toppings', 'salsas', 'creamy'] as $index => $step)
        <!-- Step indicator with progress states -->
    @endforeach
</div>
```

#### 2. Ingredient Category Display (`components/ingredient-category.blade.php`)
```php
@props(['category', 'ingredients', 'selectedIngredients', 'portions'])

<div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-900">{{ $category->title }}</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($ingredients as $ingredient)
            <x-ingredient-card :ingredient="$ingredient" :selected="in_array($ingredient->id, $selectedIngredients)" />
        @endforeach
    </div>
</div>
```

#### 3. Ingredient Card (`components/ingredient-card.blade.php`)
```php
@props(['ingredient', 'selected' => false, 'showPortion' => false])

<div
    class="ingredient-card {{ $selected ? 'selected' : '' }}"
    x-data="{ selected: {{ $selected ? 'true' : 'false' }} }"
    @click="toggleIngredient({{ $ingredient->id }})"
>
    <!-- Ingredient Image -->
    <!-- Ingredient Name -->
    <!-- Portion Info (if applicable) -->
    <!-- Selection Indicator -->
</div>
```

#### 4. Order Summary Sidebar (`components/order-summary.blade.php`)
```php
@props(['burrito', 'totalPrice'])

<div class="lg:fixed lg:right-6 lg:top-24 lg:w-80 bg-white rounded-xl shadow-lg p-6">
    <h3 class="font-bold text-lg mb-4">Your Burrito</h3>

    <!-- Selected Ingredients by Category -->
    <!-- Price Breakdown -->
    <!-- Continue/Complete Button -->
</div>
```

### Form Components

#### 1. Input Field (`components/forms/input.blade.php`)
```php
@props(['label', 'name', 'type' => 'text', 'required' => false])

<div class="space-y-2">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">
        {{ $label }}
        @if($required) <span class="text-red-500">*</span> @endif
    </label>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $attributes->merge(['class' => 'form-input']) }}
        @if($required) required @endif
    />
</div>
```

#### 2. Button (`components/forms/button.blade.php`)
```php
@props(['type' => 'button', 'variant' => 'primary'])

@php
$classes = [
    'primary' => 'bg-blue-600 hover:bg-blue-700 text-white',
    'secondary' => 'bg-orange-100 hover:bg-orange-200 text-orange-700 border border-orange-300',
    'danger' => 'bg-red-600 hover:bg-red-700 text-white'
][$variant];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "font-medium px-6 py-3 rounded-lg transition-colors {$classes}"]) }}
>
    {{ $slot }}
</button>
```

### Admin Components

#### 1. Data Table (`components/admin/data-table.blade.php`)
```php
@props(['headers', 'rows', 'actions' => []])

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <!-- Headers -->
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <!-- Rows with actions -->
        </tbody>
    </table>
</div>
```

#### 2. Stats Card (`components/admin/stat-card.blade.php`)
```php
@props(['title', 'value', 'change' => null, 'icon' => null])

<div class="bg-white rounded-lg shadow p-6">
    <!-- Icon and Title -->
    <!-- Value and Change Indicator -->
</div>
```

## Alpine.js Integration

### Burrito Builder State Management
```javascript
document.addEventListener('alpine:init', () => {
    Alpine.data('burritoBuilder', () => ({
        currentStep: 1,
        selectedIngredients: {
            proteins: [],
            rice_beans: [],
            fresh_toppings: [],
            salsas: [],
            creamy: []
        },

        toggleIngredient(ingredientId, category) {
            // Handle ingredient selection logic
        },

        nextStep() {
            if (this.canProceed()) {
                this.currentStep++;
            }
        },

        previousStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
            }
        },

        canProceed() {
            // Validation logic for current step
        },

        calculateTotal() {
            // Price calculation
        }
    }))
})
```

### Real-time Availability Updates
```javascript
Alpine.data('availabilityTracker', () => ({
    remainingBurritos: 0,
    isAvailable: true,

    init() {
        // Setup polling or WebSocket connection
        this.checkAvailability();
        setInterval(() => this.checkAvailability(), 30000); // Check every 30 seconds
    },

    async checkAvailability() {
        try {
            const response = await fetch('/api/availability');
            const data = await response.json();
            this.remainingBurritos = data.remaining;
            this.isAvailable = data.available;
        } catch (error) {
            console.error('Failed to check availability:', error);
        }
    }
}))
```

## Page Templates

### 1. Homepage (`resources/views/welcome.blade.php`)
```php
@extends('layouts.app')

@section('content')
<div class="min-h-screen">
    <!-- Hero Section with Availability -->
    <x-availability-banner :remainingBurritos="$remainingBurritos" :productionDate="$nextProductionDate" />

    <!-- Hero Content -->
    <section class="py-20 bg-gradient-to-br from-blue-50 to-orange-50">
        <!-- Main CTA and Brand Message -->
    </section>

    <!-- How It Works -->
    <section class="py-16 bg-white">
        <!-- 5-Step Process Explanation -->
    </section>

    <!-- This Week's Ingredients -->
    <section class="py-16 bg-gray-50">
        <!-- Featured ingredients carousel -->
    </section>
</div>
@endsection
```

### 2. Burrito Builder (`resources/views/order/builder.blade.php`)
```php
@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50" x-data="burritoBuilder">
    <x-availability-banner :remainingBurritos="$remainingBurritos" :productionDate="$productionDate" />

    <div class="max-w-7xl mx-auto px-6 py-8">
        <x-track-progress :currentStep="1" />

        <div class="lg:flex lg:space-x-8">
            <!-- Main Content Area -->
            <div class="lg:flex-1">
                <!-- Dynamic step content -->
                <div x-show="currentStep === 1">
                    <x-ingredient-category
                        category="proteins"
                        :ingredients="$ingredients['proteins']"
                    />
                </div>
                <!-- Other steps... -->
            </div>

            <!-- Order Summary Sidebar -->
            <x-order-summary :burrito="$currentBurrito" />
        </div>
    </div>
</div>
@endsection
```

### 3. Admin Dashboard (`resources/views/admin/dashboard.blade.php`)
```php
@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <x-forms.button variant="primary" href="{{ route('admin.production.create') }}">
            New Production Day
        </x-forms.button>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-admin.stat-card title="Today's Orders" :value="$todayOrders" />
        <x-admin.stat-card title="Revenue" value="${{ number_format($revenue, 2) }}" />
        <x-admin.stat-card title="Remaining Burritos" :value="$remainingBurritos" />
        <x-admin.stat-card title="Next Production" :value="$nextProductionDate" />
    </div>

    <!-- Recent Orders -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <h2 class="text-xl font-semibold mb-4">Recent Orders</h2>
            <x-admin.data-table :headers="['Order', 'Customer', 'Status', 'Total']" :rows="$recentOrders" />
        </div>
    </div>
</div>
@endsection
```

## Mobile Responsiveness

### Breakpoint Strategy
- **Mobile First**: Base styles for mobile (< 768px)
- **Tablet**: `md:` prefix for tablet adjustments (768px+)
- **Desktop**: `lg:` prefix for desktop layout (1024px+)

### Key Mobile Considerations
1. **Touch Targets**: Minimum 44px for all interactive elements
2. **Ingredient Grid**: 2 columns on mobile, 3 on tablet, 4 on desktop
3. **Order Summary**: Full-width bottom sheet on mobile, sidebar on desktop
4. **Navigation**: Hamburger menu on mobile, full nav on desktop

## Performance Optimization

### Image Loading
```php
// Optimized ingredient images
<img
    src="{{ asset('images/ingredients/' . $ingredient->slug . '.webp') }}"
    alt="{{ $ingredient->name }}"
    loading="lazy"
    class="w-full h-32 object-cover rounded-lg"
    onerror="this.src='{{ asset('images/ingredients/' . $ingredient->slug . '.jpg') }}'"
/>
```

### Critical CSS
```css
/* Inline critical above-the-fold styles */
.availability-banner,
.hero-section,
.track-progress {
    /* Critical styles here */
}
```

### JavaScript Optimization
- Lazy load Alpine.js components below the fold
- Bundle common functionality
- Use native browser APIs where possible

## Accessibility Features

### ARIA Implementation
```php
<!-- Ingredient selection with proper ARIA -->
<div
    role="button"
    tabindex="0"
    aria-pressed="{{ $selected ? 'true' : 'false' }}"
    aria-describedby="ingredient-{{ $ingredient->id }}-description"
    @keydown.enter="toggleIngredient({{ $ingredient->id }})"
    @keydown.space.prevent="toggleIngredient({{ $ingredient->id }})"
>
```

### Focus Management
- Clear focus indicators with ring utilities
- Skip links for keyboard navigation
- Focus trapping in modals

### Screen Reader Support
- Descriptive alt text for ingredient images
- Live regions for availability updates
- Proper heading hierarchy
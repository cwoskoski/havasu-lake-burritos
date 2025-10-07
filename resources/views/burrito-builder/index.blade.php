@extends('layouts.mobile')

@section('title', 'Build Your Burrito - Havasu Lake Burritos')

@section('content')
<div
    x-data="burritoBuilder"
    x-init="init"
    class="min-h-screen bg-gradient-to-br from-lake-blue-50 to-desert-sand-50"
>
    <!-- Header with logo and availability -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-gray-200">
        <div class="max-w-md mx-auto px-4 py-3">
            <div class="flex items-center justify-between mb-3">
                <!-- Logo -->
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-lake-blue-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold">🌮</span>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900">Build Your Burrito</h1>
                        <p class="text-xs text-gray-600">Weekend Fresh • $9.00</p>
                    </div>
                </div>

                <!-- Exit button -->
                <a href="{{ route('home') }}" class="p-2 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </a>
            </div>

            <!-- Compact availability counter -->
            <x-availability-counter :show-day-details="false" class="mb-2" />
        </div>
    </header>

    <!-- Main content area -->
    <main class="flex-1 pb-32">
        <div class="max-w-md mx-auto px-4 py-6">
            <!-- Step indicator -->
            <div class="mb-6">
                <div class="flex items-center justify-center space-x-1 mb-2">
                    @foreach($steps as $index => $step)
                        <div class="w-8 h-1 rounded-full {{ $index < $currentStep ? 'bg-lake-blue-600' : ($index == $currentStep ? 'bg-lake-blue-300' : 'bg-gray-200') }}"></div>
                    @endforeach
                </div>
                <h2 class="text-xl font-bold text-center text-gray-900 mb-1" x-text="currentStepTitle">
                    {{ $steps[$currentStep]['title'] ?? 'Choose Proteins' }}
                </h2>
                <p class="text-sm text-center text-gray-600" x-text="currentStepDescription">
                    {{ $steps[$currentStep]['description'] ?? 'Select your favorite proteins' }}
                </p>
            </div>

            <!-- Step content -->
            <div class="space-y-4" x-show="currentStep === 0" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-full" x-transition:enter-end="opacity-100 transform translate-x-0">
                <template x-for="ingredient in proteins" :key="ingredient.id">
                    <x-ingredient-card
                        :ingredient="ingredient"
                        :selected="selections.proteins.includes(ingredient.id)"
                        :selection-type="'multiple'"
                        @click="toggleIngredient('proteins', ingredient.id)"
                    />
                </template>
            </div>

            <div class="space-y-4" x-show="currentStep === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-full" x-transition:enter-end="opacity-100 transform translate-x-0">
                <template x-for="ingredient in riceBeans" :key="ingredient.id">
                    <x-ingredient-card
                        :ingredient="ingredient"
                        :selected="selections.riceBeans.includes(ingredient.id)"
                        :selection-type="'multiple'"
                        @click="toggleIngredient('riceBeans', ingredient.id)"
                    />
                </template>
            </div>

            <div class="space-y-4" x-show="currentStep === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-full" x-transition:enter-end="opacity-100 transform translate-x-0">
                <template x-for="ingredient in freshToppings" :key="ingredient.id">
                    <x-ingredient-card
                        :ingredient="ingredient"
                        :selected="selections.freshToppings.includes(ingredient.id)"
                        :selection-type="'multiple'"
                        @click="toggleIngredient('freshToppings', ingredient.id)"
                    />
                </template>
            </div>

            <div class="space-y-4" x-show="currentStep === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-full" x-transition:enter-end="opacity-100 transform translate-x-0">
                <template x-for="ingredient in salsas" :key="ingredient.id">
                    <x-ingredient-card
                        :ingredient="ingredient"
                        :selected="selections.salsas.includes(ingredient.id)"
                        :selection-type="'multiple'"
                        @click="toggleIngredient('salsas', ingredient.id)"
                    />
                </template>
            </div>

            <div class="space-y-4" x-show="currentStep === 4" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-full" x-transition:enter-end="opacity-100 transform translate-x-0">
                <template x-for="ingredient in creamy" :key="ingredient.id">
                    <x-ingredient-card
                        :ingredient="ingredient"
                        :selected="selections.creamy.includes(ingredient.id)"
                        :selection-type="'multiple'"
                        @click="toggleIngredient('creamy', ingredient.id)"
                    />
                </template>
            </div>
        </div>
    </main>

    <!-- Step navigation (sticky bottom) -->
    <x-step-navigation
        :current-step="$currentStep + 1"
        :total-steps="count($steps)"
        :can-go-back="$currentStep > 0"
        :can-go-next="true"
        :next-label="$currentStep == count($steps) - 1 ? 'Review Order' : 'Continue'"
    />
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('burritoBuilder', () => ({
            currentStep: 0,
            steps: [
                { title: 'Choose Proteins', description: 'Select your favorite proteins', category: 'proteins' },
                { title: 'Rice & Beans', description: 'Pick your rice and bean varieties', category: 'riceBeans' },
                { title: 'Fresh Toppings', description: 'Add fresh vegetables and garnishes', category: 'freshToppings' },
                { title: 'Choose Salsas', description: 'Select your salsa preferences', category: 'salsas' },
                { title: 'Creamy Additions', description: 'Add cheese and sour cream', category: 'creamy' }
            ],

            // Ingredient data (will be populated via AJAX or passed from controller)
            proteins: [],
            riceBeans: [],
            freshToppings: [],
            salsas: [],
            creamy: [],

            // User selections
            selections: {
                proteins: [],
                riceBeans: [],
                freshToppings: [],
                salsas: [],
                creamy: []
            },

            get currentStepTitle() {
                return this.steps[this.currentStep]?.title || 'Build Your Burrito';
            },

            get currentStepDescription() {
                return this.steps[this.currentStep]?.description || '';
            },

            get totalPrice() {
                return 12.00; // Base price for now
            },

            init() {
                this.loadIngredients();
                this.setupEventListeners();
                this.loadSavedSelections();
            },

            async loadIngredients() {
                try {
                    const response = await fetch('/api/ingredients/active');
                    if (response.ok) {
                        const data = await response.json();
                        this.proteins = data.proteins || [];
                        this.riceBeans = data.riceBeans || [];
                        this.freshToppings = data.freshToppings || [];
                        this.salsas = data.salsas || [];
                        this.creamy = data.creamy || [];
                    }
                } catch (error) {
                    console.error('Failed to load ingredients:', error);
                    // Show user-friendly error message
                    this.showError('Failed to load ingredients. Please refresh the page.');
                }
            },

            setupEventListeners() {
                // Global navigation functions
                window.nextStep = () => this.nextStep();
                window.previousStep = () => this.previousStep();

                // Listen for custom events
                window.addEventListener('step-next', () => this.nextStep());
                window.addEventListener('step-previous', () => this.previousStep());

                // Auto-save selections
                this.$watch('selections', () => {
                    this.saveSelections();
                });
            },

            toggleIngredient(category, ingredientId) {
                const selections = this.selections[category];
                const index = selections.indexOf(ingredientId);

                if (index > -1) {
                    selections.splice(index, 1);
                } else {
                    selections.push(ingredientId);
                }
            },

            nextStep() {
                if (this.currentStep < this.steps.length - 1) {
                    this.currentStep++;
                } else {
                    // Go to review page
                    this.goToReview();
                }
            },

            previousStep() {
                if (this.currentStep > 0) {
                    this.currentStep--;
                }
            },

            goToReview() {
                // Save final selections and navigate
                this.saveSelections();
                window.location.href = '/burrito-builder/review';
            },

            saveSelections() {
                sessionStorage.setItem('burritoSelections', JSON.stringify(this.selections));
            },

            loadSavedSelections() {
                const saved = sessionStorage.getItem('burritoSelections');
                if (saved) {
                    try {
                        this.selections = JSON.parse(saved);
                    } catch (error) {
                        console.error('Failed to load saved selections:', error);
                    }
                }
            },

            showError(message) {
                // Simple error display - could be enhanced with a toast component
                alert(message);
            }
        }))
    });
</script>
@endsection
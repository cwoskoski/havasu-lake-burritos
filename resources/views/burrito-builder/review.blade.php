@extends('layouts.mobile')

@section('title', 'Review Your Order - Havasu Lake Burritos')

@section('content')
<div
    x-data="orderReview"
    x-init="init"
    class="min-h-screen bg-gradient-to-br from-lake-blue-50 to-desert-sand-50"
>
    <!-- Header -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-gray-200">
        <div class="max-w-md mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <!-- Back button -->
                <button @click="goBack" class="p-2 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>

                <div class="text-center">
                    <h1 class="text-lg font-bold text-gray-900">Review Order</h1>
                    <p class="text-xs text-gray-600">Confirm your selections</p>
                </div>

                <!-- Edit button -->
                <button @click="goBack" class="p-2 text-lake-blue-600 hover:text-lake-blue-800 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Main content -->
    <main class="flex-1 pb-32">
        <div class="max-w-md mx-auto px-4 py-6">
            <!-- Order summary card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-900">Your Burrito</h2>
                    <span class="bg-lake-blue-100 text-lake-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                        Custom
                    </span>
                </div>

                <!-- Burrito visualization -->
                <div class="bg-gradient-to-r from-desert-sand-100 to-arizona-100 rounded-xl p-4 mb-6">
                    <div class="text-center">
                        <div class="text-4xl mb-2">🌯</div>
                        <p class="text-sm text-gray-600">Your custom burrito</p>
                    </div>
                </div>

                <!-- Ingredient breakdown -->
                <div class="space-y-4">
                    <!-- Proteins -->
                    <div x-show="selections.proteins.length > 0">
                        <h3 class="font-medium text-gray-900 mb-2 flex items-center">
                            <span class="w-2 h-2 bg-red-400 rounded-full mr-2"></span>
                            Proteins
                        </h3>
                        <div class="space-y-1 pl-4">
                            <template x-for="protein in selectedProteins" :key="protein.id">
                                <p class="text-sm text-gray-600" x-text="protein.name"></p>
                            </template>
                        </div>
                    </div>

                    <!-- Rice & Beans -->
                    <div x-show="selections.riceBeans.length > 0">
                        <h3 class="font-medium text-gray-900 mb-2 flex items-center">
                            <span class="w-2 h-2 bg-amber-400 rounded-full mr-2"></span>
                            Rice & Beans
                        </h3>
                        <div class="space-y-1 pl-4">
                            <template x-for="item in selectedRiceBeans" :key="item.id">
                                <p class="text-sm text-gray-600" x-text="item.name"></p>
                            </template>
                        </div>
                    </div>

                    <!-- Fresh Toppings -->
                    <div x-show="selections.freshToppings.length > 0">
                        <h3 class="font-medium text-gray-900 mb-2 flex items-center">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                            Fresh Toppings
                        </h3>
                        <div class="space-y-1 pl-4">
                            <template x-for="topping in selectedFreshToppings" :key="topping.id">
                                <p class="text-sm text-gray-600" x-text="topping.name"></p>
                            </template>
                        </div>
                    </div>

                    <!-- Salsas -->
                    <div x-show="selections.salsas.length > 0">
                        <h3 class="font-medium text-gray-900 mb-2 flex items-center">
                            <span class="w-2 h-2 bg-orange-400 rounded-full mr-2"></span>
                            Salsas
                        </h3>
                        <div class="space-y-1 pl-4">
                            <template x-for="salsa in selectedSalsas" :key="salsa.id">
                                <p class="text-sm text-gray-600" x-text="salsa.name"></p>
                            </template>
                        </div>
                    </div>

                    <!-- Creamy -->
                    <div x-show="selections.creamy.length > 0">
                        <h3 class="font-medium text-gray-900 mb-2 flex items-center">
                            <span class="w-2 h-2 bg-yellow-400 rounded-full mr-2"></span>
                            Creamy
                        </h3>
                        <div class="space-y-1 pl-4">
                            <template x-for="item in selectedCreamy" :key="item.id">
                                <p class="text-sm text-gray-600" x-text="item.name"></p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pickup day selection -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                <h3 class="font-bold text-gray-900 mb-4">Choose Pickup Day</h3>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        @click="selectPickupDay('saturday')"
                        :class="pickupDay === 'saturday' ? 'bg-lake-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="p-4 rounded-xl transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-lake-blue-500 focus:ring-offset-2"
                    >
                        <div class="font-medium">Saturday</div>
                        <div class="text-sm opacity-75" x-text="availableSaturday + ' available'">25 available</div>
                    </button>
                    <button
                        @click="selectPickupDay('sunday')"
                        :class="pickupDay === 'sunday' ? 'bg-lake-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="p-4 rounded-xl transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-lake-blue-500 focus:ring-offset-2"
                    >
                        <div class="font-medium">Sunday</div>
                        <div class="text-sm opacity-75" x-text="availableSunday + ' available'">30 available</div>
                    </button>
                </div>
            </div>

            <!-- Customer info form -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-6" x-show="!isLoggedIn">
                <h3 class="font-bold text-gray-900 mb-4">Your Information</h3>
                <form @submit.prevent="submitOrder" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                        <input
                            type="text"
                            x-model="customerInfo.name"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-lake-blue-500 focus:border-transparent"
                            placeholder="Your full name"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input
                            type="tel"
                            x-model="customerInfo.phone"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-lake-blue-500 focus:border-transparent"
                            placeholder="(555) 123-4567"
                        >
                        <p class="text-xs text-gray-500 mt-1">We'll text you when your order is ready</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Special Instructions (Optional)</label>
                        <textarea
                            x-model="customerInfo.instructions"
                            rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-lake-blue-500 focus:border-transparent resize-none"
                            placeholder="Any special requests or modifications..."
                        ></textarea>
                    </div>
                </form>
            </div>

            <!-- Order total -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between text-lg font-bold">
                    <span class="text-gray-900">Total</span>
                    <span class="text-lake-blue-600">$9.00</span>
                </div>
                <p class="text-sm text-gray-500 mt-1">Includes all selected ingredients</p>
            </div>
        </div>
    </main>

    <!-- Submit button (sticky bottom) -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-3">
        <div class="max-w-md mx-auto">
            <button
                @click="submitOrder"
                :disabled="!canSubmit"
                :class="canSubmit ? 'bg-lake-blue-600 hover:bg-lake-blue-700' : 'bg-gray-300 cursor-not-allowed'"
                class="w-full py-4 px-6 rounded-xl font-bold text-white text-lg transition-colors duration-150
                       focus:outline-none focus:ring-2 focus:ring-lake-blue-500 focus:ring-offset-2 shadow-lg"
            >
                <span x-show="!isSubmitting">Place Order</span>
                <span x-show="isSubmitting">Placing Order...</span>
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('orderReview', () => ({
            selections: {},
            pickupDay: null,
            availableSaturday: 25,
            availableSunday: 30,
            isLoggedIn: false, // Will be set from backend
            isSubmitting: false,
            customerInfo: {
                name: '',
                phone: '',
                instructions: ''
            },

            // Computed properties for selected ingredients
            selectedProteins: [],
            selectedRiceBeans: [],
            selectedFreshToppings: [],
            selectedSalsas: [],
            selectedCreamy: [],

            get canSubmit() {
                const hasPickupDay = this.pickupDay !== null;
                const hasCustomerInfo = this.isLoggedIn || (
                    this.customerInfo.name.trim() !== '' &&
                    this.customerInfo.phone.trim() !== ''
                );
                return hasPickupDay && hasCustomerInfo && !this.isSubmitting;
            },

            init() {
                this.loadSelections();
                this.checkAuthStatus();
                this.loadAvailability();
            },

            loadSelections() {
                const saved = sessionStorage.getItem('burritoSelections');
                if (saved) {
                    try {
                        this.selections = JSON.parse(saved);
                        this.loadIngredientDetails();
                    } catch (error) {
                        console.error('Failed to load selections:', error);
                        this.goBack(); // Return to builder if no selections
                    }
                } else {
                    this.goBack(); // Return to builder if no selections
                }
            },

            async loadIngredientDetails() {
                try {
                    const response = await fetch('/api/ingredients/active');
                    if (response.ok) {
                        const ingredients = await response.json();

                        // Map selected IDs to full ingredient objects
                        this.selectedProteins = this.getSelectedIngredients(ingredients.proteins, this.selections.proteins);
                        this.selectedRiceBeans = this.getSelectedIngredients(ingredients.riceBeans, this.selections.riceBeans);
                        this.selectedFreshToppings = this.getSelectedIngredients(ingredients.freshToppings, this.selections.freshToppings);
                        this.selectedSalsas = this.getSelectedIngredients(ingredients.salsas, this.selections.salsas);
                        this.selectedCreamy = this.getSelectedIngredients(ingredients.creamy, this.selections.creamy);
                    }
                } catch (error) {
                    console.error('Failed to load ingredient details:', error);
                }
            },

            getSelectedIngredients(ingredients, selectedIds) {
                return ingredients.filter(ingredient => selectedIds.includes(ingredient.id));
            },

            async checkAuthStatus() {
                try {
                    const response = await fetch('/api/user');
                    this.isLoggedIn = response.ok;
                } catch (error) {
                    this.isLoggedIn = false;
                }
            },

            async loadAvailability() {
                try {
                    const response = await fetch('/api/availability');
                    if (response.ok) {
                        const data = await response.json();
                        this.availableSaturday = data.saturday || 0;
                        this.availableSunday = data.sunday || 0;
                    }
                } catch (error) {
                    console.error('Failed to load availability:', error);
                }
            },

            selectPickupDay(day) {
                this.pickupDay = day;
            },

            async submitOrder() {
                if (!this.canSubmit) return;

                this.isSubmitting = true;

                try {
                    const orderData = {
                        selections: this.selections,
                        pickupDay: this.pickupDay,
                        customerInfo: this.isLoggedIn ? null : this.customerInfo,
                        totalPrice: 12.00
                    };

                    const response = await fetch('/api/orders', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(orderData)
                    });

                    if (response.ok) {
                        const result = await response.json();

                        // Clear saved selections
                        sessionStorage.removeItem('burritoSelections');

                        // Redirect to confirmation page
                        window.location.href = `/orders/${result.orderId}/confirmation`;
                    } else {
                        const error = await response.json();
                        alert(error.message || 'Failed to place order. Please try again.');
                    }
                } catch (error) {
                    console.error('Order submission failed:', error);
                    alert('Failed to place order. Please check your connection and try again.');
                } finally {
                    this.isSubmitting = false;
                }
            },

            goBack() {
                window.history.back();
            }
        }))
    });
</script>
@endsection
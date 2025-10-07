<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2563eb">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Havasu Lake Burritos - Weekend Burrito Orders</title>
    <meta name="description" content="Order custom burritos online for weekend pickup. Fresh ingredients, $9.00 per burrito, Saturday & Sunday only.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased font-sans">
    <div x-data="homepage" x-init="init" class="min-h-screen bg-gradient-to-br from-lake-blue-50 to-desert-sand-50">
        <!-- Availability Banner -->
        <div class="bg-gradient-to-r from-lake-blue-600 to-lake-blue-700 text-white py-3 px-4">
            <div class="max-w-md mx-auto text-center">
                <div class="flex items-center justify-center space-x-2 text-sm font-medium">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                    <span>Weekend Orders Open</span>
                    <span>•</span>
                    <span x-text="`${totalAvailable} burritos remaining`">55 burritos remaining</span>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="relative z-10 px-4 py-4">
            <div class="max-w-md mx-auto flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-lake-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                        <span class="text-white font-bold text-lg">🌮</span>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Havasu Lake Burritos</h1>
                        <p class="text-sm text-gray-600">Weekend Fresh • Lake Havasu</p>
                    </div>
                </div>

                <!-- Auth Links -->
                @if (Route::has('login'))
                    <div class="flex items-center space-x-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="text-sm text-gray-700 hover:text-lake-blue-600 transition-colors font-medium">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm text-gray-700 hover:text-lake-blue-600 transition-colors font-medium">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="bg-lake-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-lake-blue-700 transition-colors">
                                    Sign up
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </nav>

            <!-- Hero Section -->
            <main class="flex-1 px-4 pb-8">
                <div class="max-w-md mx-auto">
                    <!-- Hero Content -->
                    <div class="text-center mb-8">
                        <div class="relative mb-6">
                            <!-- Background decoration -->
                            <div class="absolute inset-0 bg-gradient-to-r from-arizona-100 to-desert-sand-100 rounded-3xl transform rotate-3 opacity-50"></div>
                            <div class="relative bg-white rounded-3xl p-8 shadow-xl">
                                <div class="text-6xl mb-4">🌯</div>
                                <h2 class="text-2xl font-bold text-gray-900 mb-2">Build Your Perfect Burrito</h2>
                                <p class="text-gray-600 leading-relaxed">
                                    Fresh ingredients, custom built, weekend-only production. Each burrito is made to order with love.
                                </p>
                            </div>
                        </div>

                        <!-- Price highlight -->
                        <div class="bg-gradient-to-r from-arizona-500 to-arizona-600 text-white rounded-2xl p-4 mb-6 shadow-lg">
                            <div class="text-3xl font-bold">$9.00</div>
                            <div class="text-sm opacity-90">per burrito • all ingredients included</div>
                        </div>
                    </div>

                    <!-- Live availability -->
                    <div class="mb-8">
                        <x-availability-counter
                            :remaining-saturday="$availableSaturday ?? 25"
                            :remaining-sunday="$availableSunday ?? 30"
                        />
                    </div>

                    <!-- Order button -->
                    <div class="mb-8">
                        <a
                            href="/burrito-builder"
                            class="block w-full bg-gradient-to-r from-lake-blue-600 to-lake-blue-700 text-white text-center py-4 rounded-2xl font-bold text-lg shadow-lg hover:shadow-xl transform hover:scale-105 active:scale-95 transition-all duration-150"
                        >
                            <div class="flex items-center justify-center space-x-2">
                                <span>Start Building</span>
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </div>
                        </a>
                        <p class="text-center text-xs text-gray-500 mt-2">
                            Takes 2 minutes • No account required
                        </p>
                    </div>

                    <!-- Weekend schedule -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                        <h3 class="font-bold text-gray-900 mb-4 text-center">Weekend Production Schedule</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Saturday -->
                            <div class="text-center p-4 bg-lake-blue-50 rounded-xl">
                                <div class="text-2xl mb-2">🗓️</div>
                                <div class="font-bold text-lake-blue-900">Saturday</div>
                                <div class="text-sm text-lake-blue-700 mt-1">10 AM - 2 PM</div>
                                <div class="text-xs text-lake-blue-600 mt-1" x-text="`${availableSaturday} available`">25 available</div>
                            </div>

                            <!-- Sunday -->
                            <div class="text-center p-4 bg-arizona-50 rounded-xl">
                                <div class="text-2xl mb-2">🗓️</div>
                                <div class="font-bold text-arizona-900">Sunday</div>
                                <div class="text-sm text-arizona-700 mt-1">10 AM - 2 PM</div>
                                <div class="text-xs text-arizona-600 mt-1" x-text="`${availableSunday} available`">30 available</div>
                            </div>
                        </div>
                        <p class="text-xs text-center text-gray-500 mt-4">
                            Orders close when weekend capacity is reached
                        </p>
                    </div>

                    <!-- How it works -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                        <h3 class="font-bold text-gray-900 mb-4 text-center">How It Works</h3>
                        <div class="space-y-4">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-lake-blue-100 rounded-full flex items-center justify-center text-sm font-bold text-lake-blue-600">1</div>
                                <div>
                                    <div class="font-medium text-gray-900">Choose Your Ingredients</div>
                                    <div class="text-sm text-gray-600">Select from 5 categories: proteins, rice & beans, fresh toppings, salsas, and creamy</div>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-lake-blue-100 rounded-full flex items-center justify-center text-sm font-bold text-lake-blue-600">2</div>
                                <div>
                                    <div class="font-medium text-gray-900">Pick Weekend Day</div>
                                    <div class="text-sm text-gray-600">Choose Saturday or Sunday pickup (10 AM - 2 PM)</div>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-lake-blue-100 rounded-full flex items-center justify-center text-sm font-bold text-lake-blue-600">3</div>
                                <div>
                                    <div class="font-medium text-gray-900">Get Notified</div>
                                    <div class="text-sm text-gray-600">We'll text you when your fresh burrito is ready</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-white rounded-xl p-4 text-center shadow-md">
                            <div class="text-2xl mb-2">🥑</div>
                            <div class="font-medium text-gray-900 text-sm">Fresh Daily</div>
                            <div class="text-xs text-gray-600">Premium ingredients</div>
                        </div>
                        <div class="bg-white rounded-xl p-4 text-center shadow-md">
                            <div class="text-2xl mb-2">📱</div>
                            <div class="font-medium text-gray-900 text-sm">SMS Updates</div>
                            <div class="text-xs text-gray-600">Real-time notifications</div>
                        </div>
                        <div class="bg-white rounded-xl p-4 text-center shadow-md">
                            <div class="text-2xl mb-2">⏰</div>
                            <div class="font-medium text-gray-900 text-sm">Weekend Only</div>
                            <div class="text-xs text-gray-600">Limited production</div>
                        </div>
                        <div class="bg-white rounded-xl p-4 text-center shadow-md">
                            <div class="text-2xl mb-2">🏞️</div>
                            <div class="font-medium text-gray-900 text-sm">Lake Havasu</div>
                            <div class="text-xs text-gray-600">Local favorite</div>
                        </div>
                    </div>

                    <!-- Footer info -->
                    <div class="text-center text-sm text-gray-600 space-y-2">
                        <p>Questions? <a href="tel:+15551234567" class="text-lake-blue-600 hover:text-lake-blue-800 font-medium">Call (555) 123-4567</a></p>
                        <p class="text-xs">© 2025 Havasu Lake Burritos • Made with ❤️ in Arizona</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('homepage', () => ({
                availableSaturday: {{ $availableSaturday ?? 25 }},
                availableSunday: {{ $availableSunday ?? 30 }},

                get totalAvailable() {
                    return this.availableSaturday + this.availableSunday;
                },

                init() {
                    this.loadAvailability();
                    // Refresh availability every 60 seconds
                    setInterval(() => {
                        this.loadAvailability();
                    }, 60000);
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
                }
            }))
        });
    </script>
</body>
</html>
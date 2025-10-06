{{-- Example Blade template demonstrating feature flag usage --}}
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Feature Flags Demo</h1>

    {{-- Example 1: Simple feature check --}}
    @feature('burrito_builder_v2')
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <h2 class="font-bold">New Burrito Builder (V2)</h2>
            <p>You're seeing the enhanced burrito builder interface!</p>
        </div>
    @else
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
            <h2 class="font-bold">Classic Burrito Builder</h2>
            <p>You're using the original burrito builder.</p>
        </div>
    @endfeature

    {{-- Example 2: Using config helper in template --}}
    @if(config('features.nutritional_info'))
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            <h3 class="font-bold">Nutritional Information Available</h3>
            <p>Calories: 650 | Protein: 32g | Carbs: 78g | Fat: 18g</p>
        </div>
    @endif

    {{-- Example 3: Multiple feature checks --}}
    @featureall(['allergen_warnings', 'nutritional_info'])
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <h3 class="font-bold">⚠️ Health Information</h3>
            <p>Contains: Gluten, Dairy. Full nutritional and allergen information available.</p>
        </div>
    @endfeatureall

    {{-- Example 4: Any feature enabled --}}
    @featureany(['push_notifications', 'sms_notifications'])
        <div class="bg-purple-100 border border-purple-400 text-purple-700 px-4 py-3 rounded mb-4">
            <h3 class="font-bold">📱 Notifications Available</h3>
            <p>We can keep you updated about your order!</p>
        </div>
    @endfeatureany

    {{-- Example 5: Payment methods based on features --}}
    <div class="bg-gray-100 p-6 rounded-lg mb-4">
        <h3 class="font-bold text-lg mb-4">Payment Options</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @if(config('features.payment_methods.cash'))
                <div class="text-center p-4 bg-white rounded border">
                    <div class="text-2xl mb-2">💵</div>
                    <p class="text-sm">Cash on Pickup</p>
                </div>
            @endif

            @if(config('features.payment_methods.stripe'))
                <div class="text-center p-4 bg-white rounded border">
                    <div class="text-2xl mb-2">💳</div>
                    <p class="text-sm">Credit Card</p>
                </div>
            @endif

            @if(config('features.payment_methods.apple_pay'))
                <div class="text-center p-4 bg-white rounded border">
                    <div class="text-2xl mb-2">📱</div>
                    <p class="text-sm">Apple Pay</p>
                </div>
            @endif

            @if(config('features.payment_methods.google_pay'))
                <div class="text-center p-4 bg-white rounded border">
                    <div class="text-2xl mb-2">🔍</div>
                    <p class="text-sm">Google Pay</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Example 6: Production schedule information --}}
    @if(config('features.production_schedule.enabled'))
        <div class="bg-indigo-100 border border-indigo-400 text-indigo-700 px-4 py-3 rounded mb-4">
            <h3 class="font-bold">📅 Production Schedule</h3>
            <div class="mt-2">
                <p><strong>Saturday:</strong> {{ config('features.production_schedule.saturday_capacity', 50) }} burritos available</p>
                <p><strong>Sunday:</strong> {{ config('features.production_schedule.sunday_capacity', 50) }} burritos available</p>
                <p><strong>Order ahead:</strong> Up to {{ config('features.production_schedule.advance_order_days', 7) }} days in advance</p>
            </div>
        </div>
    @endif

    {{-- Example 7: Admin features (only show if any admin feature is enabled) --}}
    @featureany(['admin_dashboard', 'ingredient_management', 'analytics_dashboard'])
        <div class="bg-gray-800 text-white p-6 rounded-lg mb-4">
            <h3 class="font-bold text-lg mb-4">🔧 Admin Features</h3>
            <div class="flex flex-wrap gap-2">
                @feature('ingredient_management')
                    <span class="bg-blue-600 px-3 py-1 rounded text-sm">Ingredient Management</span>
                @endfeature

                @feature('analytics_dashboard')
                    <span class="bg-green-600 px-3 py-1 rounded text-sm">Analytics</span>
                @endfeature

                @feature('customer_management')
                    <span class="bg-purple-600 px-3 py-1 rounded text-sm">Customer Management</span>
                @endfeature
            </div>
        </div>
    @endfeatureany

    {{-- Example 8: Development/staging features --}}
    @if(config('features.development_features') || config('features.staging_features'))
        <div class="bg-orange-100 border border-orange-400 text-orange-700 px-4 py-3 rounded mb-4">
            <h3 class="font-bold">🚧 Development Features Active</h3>
            <p class="text-sm">
                You're seeing additional features available in
                @if(config('features.development_features'))
                    development
                @elseif(config('features.staging_features'))
                    staging
                @endif
                mode.
            </p>
        </div>
    @endif

    {{-- Example 9: Mobile app features --}}
    @featureany(['push_notifications', 'offline_mode', 'camera_integration'])
        <div class="bg-teal-100 border border-teal-400 text-teal-700 px-4 py-3 rounded mb-4">
            <h3 class="font-bold">📱 Mobile App Features</h3>
            <ul class="list-disc list-inside mt-2">
                @feature('push_notifications')
                    <li>Push notifications for order updates</li>
                @endfeature
                @feature('offline_mode')
                    <li>Offline browsing capability</li>
                @endfeature
                @feature('camera_integration')
                    <li>Camera integration for QR codes</li>
                @endfeature
            </ul>
        </div>
    @endfeatureany

    {{-- Example 10: Guest checkout vs user accounts --}}
    <div class="bg-white border border-gray-300 p-6 rounded-lg">
        <h3 class="font-bold text-lg mb-4">Order Options</h3>

        @feature('guest_checkout')
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded">
                <h4 class="font-semibold text-green-800">Quick Guest Checkout</h4>
                <p class="text-green-700 text-sm">Order without creating an account!</p>
            </div>
        @endfeature

        @feature('user_profiles')
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded">
                <h4 class="font-semibold text-blue-800">Create Account</h4>
                <p class="text-blue-700 text-sm">Save your preferences and order history.</p>
            </div>
        @endfeature
    </div>
</div>
@endsection
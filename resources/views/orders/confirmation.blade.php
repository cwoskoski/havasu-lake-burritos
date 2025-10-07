@extends('layouts.mobile')

@section('title', 'Order Confirmed - Havasu Lake Burritos')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-lake-blue-50 to-desert-sand-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-md mx-auto px-4 py-4">
            <div class="flex items-center justify-center">
                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center mr-3">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Order Confirmed!</h1>
                    <p class="text-sm text-gray-600">Order #{{ $order->order_number ?? 'HLB' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT) }}</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Main content -->
    <main class="flex-1 px-4 py-8">
        <div class="max-w-md mx-auto space-y-6">
            <!-- Success message -->
            <div class="bg-green-50 border border-green-200 rounded-2xl p-6 text-center">
                <div class="text-4xl mb-3">🎉</div>
                <h2 class="text-lg font-bold text-green-800 mb-2">Your burrito is on its way!</h2>
                <p class="text-sm text-green-700">
                    We'll start preparing your order this weekend and send you a text when it's ready for pickup.
                </p>
            </div>

            <!-- Order details -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="font-bold text-gray-900 mb-4">Order Summary</h3>

                <!-- Burrito visualization -->
                <div class="bg-gradient-to-r from-desert-sand-100 to-arizona-100 rounded-xl p-4 mb-4 text-center">
                    <div class="text-3xl mb-2">🌯</div>
                    <p class="text-sm font-medium text-gray-800">Custom Burrito</p>
                    <p class="text-xs text-gray-600">Made fresh to order</p>
                </div>

                <!-- Pickup details -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Pickup Day</span>
                        <span class="text-sm text-gray-900 font-medium">
                            {{ ucfirst($order->pickup_day ?? 'Saturday') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Pickup Time</span>
                        <span class="text-sm text-gray-900">10:00 AM - 2:00 PM</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Location</span>
                        <span class="text-sm text-gray-900">Havasu Lake</span>
                    </div>
                </div>

                <div class="border-t border-gray-200 mt-4 pt-4">
                    <div class="flex items-center justify-between text-lg font-bold">
                        <span class="text-gray-900">Total Paid</span>
                        <span class="text-lake-blue-600">${{ number_format($order->total_price ?? 12.00, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Contact info -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="font-bold text-gray-900 mb-4">Contact Information</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Name</label>
                        <p class="text-sm text-gray-900">{{ $order->customer_name ?? 'Customer Name' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Phone</label>
                        <p class="text-sm text-gray-900">{{ $order->customer_phone ?? '(555) 123-4567' }}</p>
                    </div>
                    @if($order->special_instructions ?? false)
                    <div>
                        <label class="text-sm font-medium text-gray-700">Special Instructions</label>
                        <p class="text-sm text-gray-900">{{ $order->special_instructions }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- What happens next -->
            <div class="bg-lake-blue-50 rounded-2xl p-6">
                <h3 class="font-bold text-lake-blue-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    What happens next?
                </h3>
                <div class="space-y-3 text-sm text-lake-blue-800">
                    <div class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-lake-blue-200 rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">1</span>
                        <p>We'll prepare your burrito fresh on {{ ucfirst($order->pickup_day ?? 'Saturday') }} morning</p>
                    </div>
                    <div class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-lake-blue-200 rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">2</span>
                        <p>You'll get a text message when your order is ready for pickup</p>
                    </div>
                    <div class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-lake-blue-200 rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">3</span>
                        <p>Come pick up your delicious burrito between 10 AM - 2 PM</p>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="space-y-3">
                <a href="/" class="block w-full bg-lake-blue-600 text-white text-center py-4 rounded-xl font-bold text-lg hover:bg-lake-blue-700 transition-colors">
                    Order Another Burrito
                </a>

                <div class="flex space-x-3">
                    @auth
                        <a href="/dashboard" class="flex-1 bg-gray-100 text-gray-700 text-center py-3 rounded-xl font-medium hover:bg-gray-200 transition-colors">
                            View All Orders
                        </a>
                    @endauth

                    <button onclick="shareOrder()" class="flex-1 bg-gray-100 text-gray-700 text-center py-3 rounded-xl font-medium hover:bg-gray-200 transition-colors">
                        Share
                    </button>
                </div>
            </div>

            <!-- Support -->
            <div class="text-center text-sm text-gray-600">
                <p class="mb-2">Questions about your order?</p>
                <a href="tel:+15551234567" class="text-lake-blue-600 hover:text-lake-blue-800 underline font-medium">
                    Call (555) 123-4567
                </a>
                <span class="mx-2">•</span>
                <a href="mailto:orders@havasulakeburritos.com" class="text-lake-blue-600 hover:text-lake-blue-800 underline font-medium">
                    Email Us
                </a>
            </div>
        </div>
    </main>
</div>

<script>
    function shareOrder() {
        if (navigator.share) {
            navigator.share({
                title: 'Havasu Lake Burritos',
                text: 'I just ordered a delicious custom burrito from Havasu Lake Burritos!',
                url: window.location.origin
            });
        } else {
            // Fallback for browsers that don't support Web Share API
            const text = `I just ordered a delicious custom burrito from Havasu Lake Burritos! Check them out at ${window.location.origin}`;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text);
                alert('Link copied to clipboard!');
            } else {
                prompt('Share this link:', window.location.origin);
            }
        }
    }
</script>
@endsection
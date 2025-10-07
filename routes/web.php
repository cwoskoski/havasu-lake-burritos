<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BurritoBuilderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Get current availability data
    $availableSaturday = 25; // This would come from ProductionSchedule model
    $availableSunday = 30;   // This would come from ProductionSchedule model

    return view('home', compact('availableSaturday', 'availableSunday'));
})->name('home');

// Burrito builder routes
Route::get('/burrito-builder', [BurritoBuilderController::class, 'index'])->name('burrito-builder');
Route::get('/burrito-builder/review', [BurritoBuilderController::class, 'review'])->name('burrito-builder.review');

// Order confirmation
Route::get('/orders/{orderId}/confirmation', function ($orderId) {
    // This would normally load the order from database
    $order = (object) [
        'id' => $orderId,
        'order_number' => 'HLB' . str_pad($orderId, 4, '0', STR_PAD_LEFT),
        'pickup_day' => 'saturday',
        'total_price' => 12.00,
        'customer_name' => 'Customer Name',
        'customer_phone' => '(555) 123-4567',
        'special_instructions' => null
    ];

    return view('orders.confirmation', compact('order'));
})->name('order.confirmation');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

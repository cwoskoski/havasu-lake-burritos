<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BurritoBuilderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public API routes for frontend
Route::get('/availability', function () {
    $controller = new BurritoBuilderController();
    return response()->json($controller->getAvailability());
});

Route::get('/ingredients/active', function () {
    $controller = new BurritoBuilderController();
    return response()->json($controller->getActiveIngredients());
});

// Order submission endpoint
Route::post('/orders', function (Request $request) {
    $request->validate([
        'selections' => 'required|array',
        'selections.proteins' => 'array',
        'selections.riceBeans' => 'array',
        'selections.freshToppings' => 'array',
        'selections.salsas' => 'array',
        'selections.creamy' => 'array',
        'pickupDay' => 'required|in:saturday,sunday',
        'customerInfo' => 'nullable|array',
        'customerInfo.name' => 'required_with:customerInfo|string',
        'customerInfo.phone' => 'required_with:customerInfo|string',
        'customerInfo.instructions' => 'nullable|string',
        'totalPrice' => 'required|numeric'
    ]);

    // This would normally create an Order record in the database
    $orderId = rand(1000, 9999);

    return response()->json([
        'success' => true,
        'orderId' => $orderId,
        'message' => 'Order placed successfully!'
    ], 201);
});

// User authentication check
Route::get('/user', function (Request $request) {
    if (auth()->check()) {
        return response()->json([
            'authenticated' => true,
            'user' => auth()->user()
        ]);
    }

    return response()->json([
        'authenticated' => false
    ], 401);
});
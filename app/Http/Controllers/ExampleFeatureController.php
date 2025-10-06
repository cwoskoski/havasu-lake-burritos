<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\FeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Example controller demonstrating feature flag usage patterns.
 * This controller shows various ways to use feature flags in real applications.
 */
class ExampleFeatureController extends Controller
{
    public function __construct(
        private readonly FeatureService $featureService
    ) {}

    /**
     * Show burrito builder based on feature flags.
     */
    public function showBurritoBuilder(): View
    {
        // Example 1: Simple boolean check
        $useNewBuilder = config('features.burrito_builder_v2');

        // Example 2: Using helper function
        $showNutritionalInfo = feature('nutritional_info');

        // Example 3: Using service method
        $trackIngredients = $this->featureService->enabled('ingredient_tracking');

        // Example 4: Check multiple features
        $advancedFeatures = $this->featureService->allEnabled([
            'nutritional_info',
            'allergen_warnings',
            'portion_calculator'
        ]);

        return view('burrito-builder', compact(
            'useNewBuilder',
            'showNutritionalInfo',
            'trackIngredients',
            'advancedFeatures'
        ));
    }

    /**
     * Handle payment processing with feature-based logic.
     */
    public function processPayment(Request $request): JsonResponse
    {
        // Get enabled payment methods
        $paymentMethods = $this->featureService->getEnabledPaymentMethods();

        if (empty($paymentMethods)) {
            return response()->json([
                'error' => 'No payment methods available'
            ], 503);
        }

        $requestedMethod = $request->input('payment_method');

        if (!isset($paymentMethods[$requestedMethod])) {
            return response()->json([
                'error' => 'Payment method not available'
            ], 400);
        }

        // Route to different payment processors based on features
        if (feature('new_payment_flow')) {
            return $this->processPaymentV2($requestedMethod, $request);
        }

        return $this->processPaymentV1($requestedMethod, $request);
    }

    /**
     * Get available ordering options based on features.
     */
    public function getOrderingOptions(): JsonResponse
    {
        $options = [];

        // Check production schedule features
        $schedule = $this->featureService->getProductionSchedule();
        if ($schedule['enabled']) {
            $options['production_schedule'] = [
                'saturday_capacity' => $schedule['saturday_capacity'],
                'sunday_capacity' => $schedule['sunday_capacity'],
                'advance_order_days' => $schedule['advance_order_days'],
            ];
        }

        // Check ordering features
        if (feature('order_scheduling')) {
            $options['scheduling_available'] = true;
        }

        if (feature('real_time_inventory')) {
            $options['real_time_updates'] = true;
        }

        // Guest checkout option
        if (feature('guest_checkout')) {
            $options['guest_checkout'] = true;
        }

        return response()->json($options);
    }

    /**
     * Example of conditional API responses based on features.
     */
    public function getIngredientData(): JsonResponse
    {
        $data = [
            'ingredients' => $this->getBasicIngredients(),
        ];

        // Add nutritional info if feature is enabled
        if (feature('nutritional_info')) {
            $data['nutritional_info'] = $this->getNutritionalData();
        }

        // Add allergen warnings if feature is enabled
        if (feature('allergen_warnings')) {
            $data['allergen_warnings'] = $this->getAllergenData();
        }

        // Add portion calculations if feature is enabled
        if (feature('portion_calculator')) {
            $data['portion_calculations'] = $this->getPortionData();
        }

        return response()->json($data);
    }

    /**
     * Show different notification preferences based on SMS features.
     */
    public function showNotificationSettings(): View
    {
        $smsSettings = $this->featureService->getSmsSettings();

        return view('notification-settings', [
            'sms_enabled' => $smsSettings['enabled'],
            'can_opt_out_confirmations' => !$smsSettings['order_confirmations'],
            'can_opt_out_reminders' => !$smsSettings['pickup_reminders'],
            'marketing_available' => $smsSettings['marketing_messages'],
        ]);
    }

    /**
     * Admin panel access based on feature flags.
     */
    public function adminDashboard(): View
    {
        // Check if any admin features are enabled
        $adminFeatures = $this->featureService->anyEnabled([
            'admin_dashboard',
            'ingredient_management',
            'analytics_dashboard',
            'customer_management'
        ]);

        if (!$adminFeatures) {
            abort(404);
        }

        $availableModules = [];

        if (feature('ingredient_management')) {
            $availableModules[] = 'ingredients';
        }

        if (feature('analytics_dashboard')) {
            $availableModules[] = 'analytics';
        }

        if (feature('customer_management')) {
            $availableModules[] = 'customers';
        }

        return view('admin.dashboard', compact('availableModules'));
    }

    // Private helper methods for examples

    private function processPaymentV1(string $method, Request $request): JsonResponse
    {
        // Legacy payment processing
        return response()->json(['message' => 'Payment processed (v1)', 'method' => $method]);
    }

    private function processPaymentV2(string $method, Request $request): JsonResponse
    {
        // New payment processing with enhanced features
        return response()->json(['message' => 'Payment processed (v2)', 'method' => $method]);
    }

    private function getBasicIngredients(): array
    {
        return ['proteins', 'rice', 'beans', 'vegetables'];
    }

    private function getNutritionalData(): array
    {
        return ['calories', 'protein', 'carbs', 'fat'];
    }

    private function getAllergenData(): array
    {
        return ['gluten', 'dairy', 'nuts', 'soy'];
    }

    private function getPortionData(): array
    {
        return ['protein' => '1/2 cup', 'rice' => '1/2 cup', 'beans' => '2/3 cup'];
    }
}
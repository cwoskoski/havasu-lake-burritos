<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\FeatureService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class FeatureServiceTest extends TestCase
{
    protected FeatureService $featureService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->featureService = app(FeatureService::class);
    }

    public function test_can_check_boolean_feature_enabled(): void
    {
        Config::set('features.test_feature', true);

        $this->assertTrue($this->featureService->enabled('test_feature'));
    }

    public function test_can_check_boolean_feature_disabled(): void
    {
        Config::set('features.test_feature', false);

        $this->assertFalse($this->featureService->enabled('test_feature'));
        $this->assertTrue($this->featureService->disabled('test_feature'));
    }

    public function test_can_handle_complex_feature_configuration(): void
    {
        $complexConfig = [
            'enabled' => true,
            'option1' => 'value1',
            'option2' => 100,
        ];

        Config::set('features.complex_feature', $complexConfig);

        $this->assertTrue($this->featureService->enabled('complex_feature'));
        $this->assertEquals($complexConfig, $this->featureService->config('complex_feature'));
    }

    public function test_complex_feature_disabled_when_enabled_false(): void
    {
        $complexConfig = [
            'enabled' => false,
            'option1' => 'value1',
        ];

        Config::set('features.complex_feature', $complexConfig);

        $this->assertFalse($this->featureService->enabled('complex_feature'));
    }

    public function test_can_check_multiple_features_any_enabled(): void
    {
        Config::set('features.feature1', false);
        Config::set('features.feature2', true);

        $this->assertTrue($this->featureService->anyEnabled(['feature1', 'feature2']));
    }

    public function test_can_check_multiple_features_all_enabled(): void
    {
        Config::set('features.feature1', true);
        Config::set('features.feature2', true);

        $this->assertTrue($this->featureService->allEnabled(['feature1', 'feature2']));
    }

    public function test_all_enabled_returns_false_when_one_disabled(): void
    {
        Config::set('features.feature1', true);
        Config::set('features.feature2', false);

        $this->assertFalse($this->featureService->allEnabled(['feature1', 'feature2']));
    }

    public function test_can_get_payment_methods_configuration(): void
    {
        $paymentConfig = [
            'enabled' => true,
            'stripe' => true,
            'cash' => true,
            'apple_pay' => false,
            'google_pay' => false,
        ];

        Config::set('features.payment_methods', $paymentConfig);

        $enabledMethods = $this->featureService->getEnabledPaymentMethods();

        $this->assertEquals([
            'stripe' => true,
            'cash' => true,
        ], $enabledMethods);
    }

    public function test_payment_methods_empty_when_disabled(): void
    {
        $paymentConfig = [
            'enabled' => false,
            'stripe' => true,
            'cash' => true,
        ];

        Config::set('features.payment_methods', $paymentConfig);

        $enabledMethods = $this->featureService->getEnabledPaymentMethods();

        $this->assertEquals([], $enabledMethods);
    }

    public function test_can_get_production_schedule_configuration(): void
    {
        $scheduleConfig = [
            'enabled' => true,
            'saturday_capacity' => 75,
            'sunday_capacity' => 100,
            'advance_order_days' => 14,
        ];

        Config::set('features.production_schedule', $scheduleConfig);

        $schedule = $this->featureService->getProductionSchedule();

        $this->assertEquals([
            'enabled' => true,
            'saturday_capacity' => 75,
            'sunday_capacity' => 100,
            'advance_order_days' => 14,
        ], $schedule);
    }

    public function test_production_schedule_has_defaults(): void
    {
        Config::set('features.production_schedule', []);

        $schedule = $this->featureService->getProductionSchedule();

        $this->assertEquals([
            'enabled' => true,
            'saturday_capacity' => 50,
            'sunday_capacity' => 50,
            'advance_order_days' => 7,
        ], $schedule);
    }
}

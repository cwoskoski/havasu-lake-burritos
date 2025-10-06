<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\FeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class FeatureFlagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_feature_service_is_registered(): void
    {
        $service = $this->app->make(FeatureService::class);
        $this->assertInstanceOf(FeatureService::class, $service);
    }

    public function test_feature_helper_function_works(): void
    {
        Config::set('features.test_feature', true);
        $this->assertTrue(feature('test_feature'));

        Config::set('features.test_feature', false);
        $this->assertFalse(feature('test_feature'));
    }

    public function test_feature_config_helper_works(): void
    {
        $config = [
            'enabled' => true,
            'value' => 'test_value',
        ];

        Config::set('features.complex_feature', $config);
        $this->assertEquals($config, feature_config('complex_feature'));
    }

    public function test_feature_disabled_helper_works(): void
    {
        Config::set('features.test_feature', false);
        $this->assertTrue(feature_disabled('test_feature'));

        Config::set('features.test_feature', true);
        $this->assertFalse(feature_disabled('test_feature'));
    }

    public function test_features_enabled_helper_works(): void
    {
        Config::set('features.feature1', true);
        Config::set('features.feature2', true);
        $this->assertTrue(features_enabled(['feature1', 'feature2']));

        Config::set('features.feature2', false);
        $this->assertFalse(features_enabled(['feature1', 'feature2']));
    }

    public function test_any_feature_enabled_helper_works(): void
    {
        Config::set('features.feature1', false);
        Config::set('features.feature2', true);
        $this->assertTrue(any_feature_enabled(['feature1', 'feature2']));

        Config::set('features.feature2', false);
        $this->assertFalse(any_feature_enabled(['feature1', 'feature2']));
    }

    public function test_blade_feature_directive_works(): void
    {
        Config::set('features.test_feature', true);

        $view = $this->renderBlade(
            '@feature(\'test_feature\')
                <div>Feature is enabled</div>
            @endfeature'
        );

        $view->assertSee('Feature is enabled');
    }

    public function test_blade_feature_directive_hides_content_when_disabled(): void
    {
        Config::set('features.test_feature', false);

        $view = $this->renderBlade(
            '@feature(\'test_feature\')
                <div>Feature is enabled</div>
            @endfeature'
        );

        $view->assertDontSee('Feature is enabled');
    }

    public function test_can_check_payment_methods_through_service(): void
    {
        Config::set('features.payment_methods', [
            'enabled' => true,
            'stripe' => true,
            'cash' => true,
            'apple_pay' => false,
            'google_pay' => false,
        ]);

        $service = $this->app->make(FeatureService::class);
        $methods = $service->getEnabledPaymentMethods();

        $this->assertEquals([
            'stripe' => true,
            'cash' => true,
        ], $methods);
    }

    public function test_can_check_sms_settings_through_service(): void
    {
        Config::set('features.sms_settings', [
            'enabled' => true,
            'verification_required' => true,
            'order_confirmations' => true,
            'pickup_reminders' => false,
            'marketing_messages' => false,
        ]);

        $service = $this->app->make(FeatureService::class);
        $settings = $service->getSmsSettings();

        $this->assertEquals([
            'enabled' => true,
            'verification_required' => true,
            'order_confirmations' => true,
            'pickup_reminders' => false,
            'marketing_messages' => false,
        ], $settings);
    }

    public function test_environment_mode_checks_work(): void
    {
        $service = $this->app->make(FeatureService::class);

        Config::set('features.development_features', true);
        $this->assertTrue($service->isDevelopmentMode());

        Config::set('features.staging_features', true);
        $this->assertTrue($service->isStagingMode());

        Config::set('features.beta_features', true);
        $this->assertTrue($service->isBetaMode());
    }

    /**
     * Helper method to render Blade templates in tests.
     */
    protected function renderBlade(string $template, array $data = []): \Illuminate\Testing\TestView
    {
        return $this->blade($template, $data);
    }
}
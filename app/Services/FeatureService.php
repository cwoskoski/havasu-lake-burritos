<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Config;

/**
 * Feature Flag Service
 *
 * Provides a clean interface for checking feature flags throughout the application.
 * Supports both simple boolean flags and complex feature configurations.
 */
class FeatureService
{
    /**
     * Check if a feature is enabled.
     */
    public function enabled(string $feature): bool
    {
        $value = Config::get("features.{$feature}");

        // Handle complex feature configurations
        if (is_array($value)) {
            return (bool) ($value['enabled'] ?? false);
        }

        return (bool) $value;
    }

    /**
     * Check if a feature is disabled.
     */
    public function disabled(string $feature): bool
    {
        return ! $this->enabled($feature);
    }

    /**
     * Get feature configuration (for complex features).
     */
    public function config(string $feature, mixed $default = null): mixed
    {
        return Config::get("features.{$feature}", $default);
    }

    /**
     * Check if any of the provided features are enabled.
     */
    public function anyEnabled(array $features): bool
    {
        foreach ($features as $feature) {
            if ($this->enabled($feature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if all of the provided features are enabled.
     */
    public function allEnabled(array $features): bool
    {
        foreach ($features as $feature) {
            if ($this->disabled($feature)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all enabled features.
     *
     * @return array<string, mixed>
     */
    public function getAllEnabled(): array
    {
        $features = Config::get('features', []);
        $enabled = [];

        foreach ($features as $key => $value) {
            if ($this->enabled($key)) {
                $enabled[$key] = $value;
            }
        }

        return $enabled;
    }

    /**
     * Check environment-based feature modes.
     */
    public function isDevelopmentMode(): bool
    {
        return $this->enabled('development_features');
    }

    public function isStagingMode(): bool
    {
        return $this->enabled('staging_features');
    }

    public function isBetaMode(): bool
    {
        return $this->enabled('beta_features');
    }

    /**
     * Get payment methods configuration.
     *
     * @return array<string, bool>
     */
    public function getEnabledPaymentMethods(): array
    {
        $config = $this->config('payment_methods', []);

        if (! ($config['enabled'] ?? false)) {
            return [];
        }

        $methods = [];
        foreach (['stripe', 'cash', 'apple_pay', 'google_pay'] as $method) {
            if ($config[$method] ?? false) {
                $methods[$method] = true;
            }
        }

        return $methods;
    }

    /**
     * Get production schedule configuration.
     *
     * @return array<string, int|bool>
     */
    public function getProductionSchedule(): array
    {
        $config = $this->config('production_schedule', []);

        return [
            'enabled' => $config['enabled'] ?? true,
            'saturday_capacity' => $config['saturday_capacity'] ?? 50,
            'sunday_capacity' => $config['sunday_capacity'] ?? 50,
            'advance_order_days' => $config['advance_order_days'] ?? 7,
        ];
    }

    /**
     * Get SMS settings configuration.
     *
     * @return array<string, bool>
     */
    public function getSmsSettings(): array
    {
        $config = $this->config('sms_settings', []);

        return [
            'enabled' => $config['enabled'] ?? true,
            'verification_required' => $config['verification_required'] ?? true,
            'order_confirmations' => $config['order_confirmations'] ?? true,
            'pickup_reminders' => $config['pickup_reminders'] ?? true,
            'marketing_messages' => $config['marketing_messages'] ?? false,
        ];
    }
}

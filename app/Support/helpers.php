<?php

declare(strict_types=1);

use App\Services\FeatureService;
use Illuminate\Support\Facades\App;

if (!function_exists('feature')) {
    /**
     * Check if a feature is enabled.
     */
    function feature(string $feature): bool
    {
        return App::make(FeatureService::class)->enabled($feature);
    }
}

if (!function_exists('feature_config')) {
    /**
     * Get feature configuration.
     */
    function feature_config(string $feature, mixed $default = null): mixed
    {
        return App::make(FeatureService::class)->config($feature, $default);
    }
}

if (!function_exists('feature_disabled')) {
    /**
     * Check if a feature is disabled.
     */
    function feature_disabled(string $feature): bool
    {
        return App::make(FeatureService::class)->disabled($feature);
    }
}

if (!function_exists('features_enabled')) {
    /**
     * Check if all provided features are enabled.
     */
    function features_enabled(array $features): bool
    {
        return App::make(FeatureService::class)->allEnabled($features);
    }
}

if (!function_exists('any_feature_enabled')) {
    /**
     * Check if any of the provided features are enabled.
     */
    function any_feature_enabled(array $features): bool
    {
        return App::make(FeatureService::class)->anyEnabled($features);
    }
}
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\FeatureService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Aurora Serverless provider for local development
        $this->app->register(AuroraServerlessServiceProvider::class);

        // Register Feature Service as singleton
        $this->app->singleton(FeatureService::class, function ($app) {
            return new FeatureService;
        });

        // Load feature flag helper functions
        if (file_exists(app_path('Support/helpers.php'))) {
            require_once app_path('Support/helpers.php');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Blade directives for feature flags
        $this->registerFeatureBladeDirectives();
    }

    /**
     * Register custom Blade directives for feature flags.
     */
    protected function registerFeatureBladeDirectives(): void
    {
        // @feature('feature_name')
        Blade::if('feature', function (string $feature) {
            return feature($feature);
        });

        // @featureany(['feature1', 'feature2'])
        Blade::if('featureany', function (array $features) {
            return any_feature_enabled($features);
        });

        // @featureall(['feature1', 'feature2'])
        Blade::if('featureall', function (array $features) {
            return features_enabled($features);
        });
    }
}

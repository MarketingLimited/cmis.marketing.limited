<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\FeatureToggle\FeatureFlagService;

/**
 * Feature Toggle Service Provider
 *
 * Registers the FeatureFlagService as a singleton and provides
 * convenient access methods throughout the application.
 */
class FeatureToggleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register FeatureFlagService as a singleton
        $this->app->singleton(FeatureFlagService::class, function ($app) {
            return new FeatureFlagService();
        });

        // Register convenient alias
        $this->app->alias(FeatureFlagService::class, 'feature.flags');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Register Blade directives for feature checking in templates
        $this->registerBladeDirectives();

        // Register view composers to inject feature flags into all views
        $this->registerViewComposers();
    }

    /**
     * Register custom Blade directives for feature flags
     *
     * @return void
     */
    protected function registerBladeDirectives()
    {
        // @featureEnabled('scheduling.meta.enabled')
        \Blade::directive('featureEnabled', function ($expression) {
            return "<?php if(app('feature.flags')->isEnabled($expression)): ?>";
        });

        // @endFeatureEnabled
        \Blade::directive('endFeatureEnabled', function () {
            return "<?php endif; ?>";
        });

        // @featureDisabled('scheduling.meta.enabled')
        \Blade::directive('featureDisabled', function ($expression) {
            return "<?php if(!app('feature.flags')->isEnabled($expression)): ?>";
        });

        // @endFeatureDisabled
        \Blade::directive('endFeatureDisabled', function () {
            return "<?php endif; ?>";
        });

        // @enabledPlatforms('scheduling')
        \Blade::directive('enabledPlatforms', function ($expression) {
            return "<?php echo json_encode(app('feature.flags')->getEnabledPlatforms($expression)); ?>";
        });
    }

    /**
     * Register view composers to inject feature flags data
     *
     * @return void
     */
    protected function registerViewComposers()
    {
        // Share feature flag service with all views
        view()->composer('*', function ($view) {
            $view->with('featureFlags', app('feature.flags'));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            FeatureFlagService::class,
            'feature.flags',
        ];
    }
}

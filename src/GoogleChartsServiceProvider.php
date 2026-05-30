<?php

namespace Premmohantyagi\GoogleCharts;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Premmohantyagi\GoogleCharts\Http\Controllers\BuilderController;
use Premmohantyagi\GoogleCharts\Http\Controllers\ChartController;
use Premmohantyagi\GoogleCharts\View\Components\GoogleChartComponent;

class GoogleChartsServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/google-charts.php', 'google-charts');

        $this->app->singleton('google-charts', function () {
            return new GoogleChartFactory();
        });

        $this->app->alias('google-charts', GoogleChartFactory::class);
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'google-charts');

        $this->registerBladeComponents();
        // The builder route is registered first so its static path is matched before
        // the AJAX endpoint's "{name}" wildcard when both share a prefix.
        $this->registerBuilderRoute();
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }
    }

    /**
     * Register the optional AJAX endpoint when enabled in the config.
     */
    protected function registerRoutes(): void
    {
        $config = (array) $this->app['config']->get('google-charts.route', []);

        if (empty($config['enabled'])) {
            return;
        }

        Route::group([
            'prefix' => $config['prefix'] ?? 'google-charts',
            'middleware' => $config['middleware'] ?? ['web'],
            'as' => $config['as'] ?? 'google-charts.',
        ], function () {
            Route::get('{name}', [ChartController::class, 'show'])->name('show');
        });
    }

    /**
     * Register the optional visual builder page when enabled in the config.
     */
    protected function registerBuilderRoute(): void
    {
        $config = (array) $this->app['config']->get('google-charts.builder', []);

        if (empty($config['enabled'])) {
            return;
        }

        Route::group([
            'prefix' => $config['prefix'] ?? 'google-charts',
            'middleware' => $config['middleware'] ?? ['web'],
            'as' => $config['as'] ?? 'google-charts.',
        ], function () use ($config) {
            Route::get($config['path'] ?? 'builder', [BuilderController::class, 'index'])->name('builder');
        });
    }

    /**
     * Register the package's Blade components.
     */
    protected function registerBladeComponents(): void
    {
        // Registers the <x-google-chart :chart="$chart" /> tag.
        Blade::component(GoogleChartComponent::class, 'google-chart');
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../config/google-charts.php' => $this->app->configPath('google-charts.php'),
        ], 'google-charts-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => $this->app->resourcePath('views/vendor/google-charts'),
        ], 'google-charts-views');
    }
}

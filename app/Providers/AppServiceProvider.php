<?php

namespace App\Providers;

use App\Livewire\AiAssistantWidget;
use App\Services\AiAssistantService;
use App\Services\FinancialPlanningService;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(AiAssistantService::class);
        $this->app->singleton(FinancialPlanningService::class);

        // Register TaxConfigRepository as a singleton so all Tax classes share the same instance
        $this->app->singleton(\App\Services\Tax\TaxConfigRepository::class, function ($app) {
            return new \App\Services\Tax\TaxConfigRepository('no');
        });

        // Register TaxPropertyRepository as a singleton for property tax data
        $this->app->singleton(\App\Services\Tax\TaxPropertyRepository::class, function ($app) {
            return new \App\Services\Tax\TaxPropertyRepository('no');
        });

        // Register Tax calculation classes as singletons
        $this->app->singleton(\App\Models\Core\TaxIncome::class, function ($app) {
            return new \App\Models\Core\TaxIncome('no');
        });

        $this->app->singleton(\App\Models\Core\TaxFortune::class, function ($app) {
            return new \App\Models\Core\TaxFortune('no');
        });

        $this->app->singleton(\App\Models\Core\TaxRealization::class, function ($app) {
            return new \App\Models\Core\TaxRealization('no');
        });

        $this->app->singleton(\App\Models\Core\TaxProperty::class, function ($app) {
            return new \App\Models\Core\TaxProperty('no');
        });

        // Register utility classes as singletons
        $this->app->singleton(\App\Models\Core\Helper::class, function ($app) {
            return new \App\Models\Core\Helper;
        });

        $this->app->singleton(\App\Models\Core\Rules::class, function ($app) {
            return new \App\Models\Core\Rules;
        });

        // Register Changerate as a singleton
        // The scenario type will be set when first instantiated
        // Default to 'realistic' if not specified
        $this->app->singleton(\App\Models\Core\Changerate::class, function ($app) {
            // Get scenario from config or default to 'realistic'
            $scenario = config('app.prognosis_scenario', 'realistic');

            return new \App\Models\Core\Changerate($scenario);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Manually register AI Assistant Widget as Livewire component
        Livewire::component('ai-assistant-widget', AiAssistantWidget::class);
    }
}

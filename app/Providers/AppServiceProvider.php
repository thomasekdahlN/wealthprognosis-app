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

        // Register TaxConfigPropertyRepository as a singleton for property tax data
        $this->app->singleton(\App\Services\Tax\TaxConfigPropertyRepository::class, function ($app) {
            return new \App\Services\Tax\TaxConfigPropertyRepository('no');
        });

        // Register Tax calculation classes as singletons with proper dependency injection
        $this->app->singleton(\App\Services\Tax\TaxSalary::class, function ($app) {
            return new \App\Services\Tax\TaxSalary(
                'no',
                $app->make(\App\Services\Tax\TaxConfigRepository::class)
            );
        });

        $this->app->singleton(\App\Services\Tax\TaxIncome::class, function ($app) {
            return new \App\Services\Tax\TaxIncome(
                'no',
                $app->make(\App\Services\Tax\TaxConfigRepository::class),
                $app->make(\App\Services\Tax\TaxSalary::class)
            );
        });

        $this->app->singleton(\App\Services\Tax\TaxFortune::class, function ($app) {
            return new \App\Services\Tax\TaxFortune(
                'no',
                $app->make(\App\Services\Tax\TaxConfigRepository::class),
                $app->make(\App\Services\Tax\TaxConfigPropertyRepository::class)
            );
        });

        $this->app->singleton(\App\Services\Tax\TaxRealization::class, function ($app) {
            return new \App\Services\Tax\TaxRealization(
                'no',
                $app->make(\App\Services\Tax\TaxConfigRepository::class),
                $app->make(\App\Services\Tax\TaxSalary::class)
            );
        });

        $this->app->singleton(\App\Services\Tax\TaxProperty::class, function ($app) {
            return new \App\Services\Tax\TaxProperty('no');
        });

        // Register utility classes as singletons
        $this->app->singleton(\App\Services\Utilities\HelperService::class, function ($app) {
            return new \App\Services\Utilities\HelperService;
        });

        $this->app->singleton(\App\Services\Utilities\RulesService::class, function ($app) {
            return new \App\Services\Utilities\RulesService;
        });

        // Register Changerate as a singleton
        // The scenario type will be set when first instantiated
        // Default to 'realistic' if not specified
        $this->app->singleton(\App\Services\Prognosis\ChangerateService::class, function ($app) {
            // Get scenario from config or default to 'realistic'
            $scenario = config('app.prognosis_scenario', 'realistic');

            return new \App\Services\Prognosis\ChangerateService($scenario);
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

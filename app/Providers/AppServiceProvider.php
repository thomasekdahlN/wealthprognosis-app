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
        $this->app->singleton(\App\Services\Tax\TaxSalaryService::class, function ($app) {
            return new \App\Services\Tax\TaxSalaryService(
                'no',
                $app->make(\App\Services\Tax\TaxConfigRepository::class)
            );
        });

        $this->app->singleton(\App\Services\Tax\TaxIncomeService::class, function ($app) {
            return new \App\Services\Tax\TaxIncomeService(
                'no',
                $app->make(\App\Services\Tax\TaxConfigRepository::class),
                $app->make(\App\Services\Tax\TaxSalaryService::class)
            );
        });

        $this->app->singleton(\App\Services\Tax\TaxFortuneService::class, function ($app) {
            return new \App\Services\Tax\TaxFortuneService(
                'no',
                $app->make(\App\Services\Tax\TaxConfigRepository::class),
                $app->make(\App\Services\Tax\TaxConfigPropertyRepository::class)
            );
        });

        $this->app->singleton(\App\Services\Tax\TaxRealizationService::class, function ($app) {
            return new \App\Services\Tax\TaxRealizationService(
                'no',
                $app->make(\App\Services\Tax\TaxConfigRepository::class),
                $app->make(\App\Services\Tax\TaxSalaryService::class)
            );
        });

        $this->app->singleton(\App\Services\Tax\TaxPropertyService::class, function ($app) {
            return new \App\Services\Tax\TaxPropertyService('no');
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

        // Register Processing services as singletons
        $this->app->singleton(\App\Services\Processing\YearlyProcessor::class, function ($app) {
            return new \App\Services\Processing\YearlyProcessor(
                $app->make(\App\Services\Tax\TaxFortuneService::class),
                $app->make(\App\Services\Utilities\HelperService::class)
            );
        });

        $this->app->singleton(\App\Services\Processing\GroupProcessor::class, function ($app) {
            return new \App\Services\Processing\GroupProcessor(
                $app->make(\App\Services\Tax\TaxFortuneService::class)
            );
        });

        $this->app->singleton(\App\Services\Processing\PostProcessorService::class, function ($app) {
            return new \App\Services\Processing\PostProcessorService(
                $app->make(\App\Services\Processing\YearlyProcessor::class),
                $app->make(\App\Services\Processing\GroupProcessor::class)
            );
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

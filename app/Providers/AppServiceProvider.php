<?php

namespace App\Providers;

use App\Livewire\AiAssistantWidget;
use App\Services\AiAssistantService;
use App\Services\AssetTypeService;
use App\Services\FinancialPlanningService;
use App\Services\MarkdownPageRenderer;
use App\Services\Processing\GroupProcessor;
use App\Services\Processing\PostProcessorService;
use App\Services\Processing\YearlyProcessor;
use App\Services\Prognosis\ChangerateService;
use App\Services\Prognosis\PrognosisService;
use App\Services\Tax\TaxCashflowService;
use App\Services\Tax\TaxConfigPropertyRepository;
use App\Services\Tax\TaxConfigRepository;
use App\Services\Tax\TaxFortuneService;
use App\Services\Tax\TaxIncomeService;
use App\Services\Tax\TaxPropertyService;
use App\Services\Tax\TaxRealizationService;
use App\Services\Tax\TaxSalaryService;
use App\Services\Utilities\HelperService;
use App\Services\Utilities\RulesService;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AiAssistantService::class);
        $this->app->singleton(FinancialPlanningService::class);

        $this->app->singleton(MarkdownPageRenderer::class, function ($app): MarkdownPageRenderer {
            return new MarkdownPageRenderer(
                contentPath: (string) config('public_pages.content_path', resource_path('content')),
                defaultLocale: (string) config('public_pages.default_locale', 'en'),
            );
        });

        // Register TaxConfigRepository as a singleton so all Tax classes share the same instance
        $this->app->singleton(TaxConfigRepository::class, function ($app) {
            return new TaxConfigRepository('no');
        });

        // Register TaxConfigPropertyRepository as a singleton for property tax data
        $this->app->singleton(TaxConfigPropertyRepository::class, function ($app) {
            return new TaxConfigPropertyRepository('no');
        });

        // Register Tax calculation classes as singletons with proper dependency injection
        $this->app->singleton(TaxSalaryService::class, function ($app) {
            return new TaxSalaryService(
                'no',
                $app->make(TaxConfigRepository::class)
            );
        });

        $this->app->singleton(TaxIncomeService::class, function ($app) {
            return new TaxIncomeService(
                'no',
                $app->make(TaxConfigRepository::class),
                $app->make(TaxSalaryService::class)
            );
        });

        $this->app->singleton(TaxFortuneService::class, function ($app) {
            return new TaxFortuneService(
                'no',
                $app->make(TaxConfigRepository::class)
            );
        });

        $this->app->singleton(TaxRealizationService::class, function ($app) {
            return new TaxRealizationService(
                'no',
                $app->make(TaxConfigRepository::class),
                $app->make(TaxSalaryService::class)
            );
        });

        $this->app->singleton(TaxPropertyService::class, function ($app) {
            return new TaxPropertyService(
                'no',
                $app->make(HelperService::class),
                $app->make(TaxConfigPropertyRepository::class)
            );
        });

        // Register utility classes as singletons
        $this->app->singleton(HelperService::class, function ($app) {
            return new HelperService;
        });

        $this->app->singleton(RulesService::class, function ($app) {
            return new RulesService(
                $app->make(HelperService::class)
            );
        });

        // Register Changerate as a singleton
        // The scenario type will be set when first instantiated
        // Default to 'realistic' if not specified
        $this->app->singleton(ChangerateService::class, function ($app) {
            // Get scenario from config or default to 'realistic'
            $scenario = config('app.prognosis_scenario', 'realistic');

            return new ChangerateService($scenario);
        });

        // Register Processing services as singletons
        $this->app->singleton(YearlyProcessor::class, function ($app) {
            return new YearlyProcessor(
                $app->make(TaxFortuneService::class),
                $app->make(TaxCashflowService::class),
                $app->make(HelperService::class),
                $app->make(AssetTypeService::class)
            );
        });

        $this->app->singleton(GroupProcessor::class, function ($app) {
            return new GroupProcessor(
                $app->make(TaxFortuneService::class)
            );
        });

        $this->app->singleton(PostProcessorService::class, function ($app) {
            return new PostProcessorService(
                $app->make(YearlyProcessor::class),
                $app->make(GroupProcessor::class)
            );
        });

        // PrognosisService is not a singleton because it takes a runtime $config array.
        // Resolve via: app(PrognosisService::class, ['config' => $configArray])
        $this->app->bind(PrognosisService::class, function ($app, array $parameters) {
            return new PrognosisService(
                $parameters['config'] ?? [],
                $app->make(TaxIncomeService::class),
                $app->make(TaxFortuneService::class),
                $app->make(TaxRealizationService::class),
                $app->make(ChangerateService::class),
                $app->make(HelperService::class),
                $app->make(RulesService::class),
                $app->make(TaxCashflowService::class),
                $app->make(PostProcessorService::class),
                $app->make(AssetTypeService::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Manually register AI Assistant Widget as Livewire component
        Livewire::component('ai-assistant-widget', AiAssistantWidget::class);
    }
}

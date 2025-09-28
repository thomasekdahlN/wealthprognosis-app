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

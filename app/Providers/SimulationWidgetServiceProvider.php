<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class SimulationWidgetServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Manually register simulation widgets as Livewire components
        Livewire::component('app.filament.widgets.simulation-stats-overview-widget', \App\Filament\Widgets\SimulationStatsOverviewWidget::class);
        Livewire::component('app.filament.widgets.simulation-fire-analysis-widget', \App\Filament\Widgets\SimulationFireAnalysisWidget::class);
        Livewire::component('app.filament.widgets.simulation-tax-analysis-widget', \App\Filament\Widgets\SimulationTaxAnalysisWidget::class);
        Livewire::component('app.filament.widgets.simulation-asset-allocation-chart-widget', \App\Filament\Widgets\SimulationAssetAllocationChartWidget::class);
    }
}

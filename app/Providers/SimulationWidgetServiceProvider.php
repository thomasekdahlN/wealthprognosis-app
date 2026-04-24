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
        // Manually register simulation dashboard widgets as Livewire components
        Livewire::component('app.filament.widgets.simulation.simulation-key-figures-widget', \App\Filament\Widgets\Simulation\SimulationKeyFiguresWidget::class);
        Livewire::component('app.filament.widgets.simulation.simulation-milestones-widget', \App\Filament\Widgets\Simulation\SimulationMilestonesWidget::class);
        Livewire::component('app.filament.widgets.simulation.simulation-net-worth-growth-widget', \App\Filament\Widgets\Simulation\SimulationNetWorthGrowthWidget::class);
        Livewire::component('app.filament.widgets.simulation.simulation-income-vs-expenses-widget', \App\Filament\Widgets\Simulation\SimulationIncomeVsExpensesWidget::class);
        Livewire::component('app.filament.widgets.simulation.simulation-annual-cash-flow-widget', \App\Filament\Widgets\Simulation\SimulationAnnualCashFlowWidget::class);
        Livewire::component('app.filament.widgets.simulation.simulation-fire-progression-widget', \App\Filament\Widgets\Simulation\SimulationFireProgressionWidget::class);
        Livewire::component('app.filament.widgets.simulation.simulation-asset-allocation-widget', \App\Filament\Widgets\Simulation\SimulationAssetAllocationWidget::class);
        Livewire::component('app.filament.widgets.simulation.simulation-debt-allocation-widget', \App\Filament\Widgets\Simulation\SimulationDebtAllocationWidget::class);

        // Manually register detailed reporting widgets as Livewire components
        Livewire::component('app.filament.widgets.simulation.simulation-asset-drill-down-table-widget', \App\Filament\Widgets\Simulation\SimulationAssetDrillDownTableWidget::class);
        Livewire::component('app.filament.widgets.simulation.simulation-income-report-widget', \App\Filament\Widgets\Simulation\SimulationIncomeReportWidget::class);
        Livewire::component('app.filament.widgets.simulation.simulation-expense-report-widget', \App\Filament\Widgets\Simulation\SimulationExpenseReportWidget::class);
        Livewire::component('app.filament.widgets.simulation.simulation-tax-report-widget', \App\Filament\Widgets\Simulation\SimulationTaxReportWidget::class);
        Livewire::component('app.filament.widgets.simulation.simulation-financial-metrics-heatmap-widget', \App\Filament\Widgets\Simulation\SimulationFinancialMetricsHeatmapWidget::class);

        // Manually register comparison widgets as Livewire components
        Livewire::component('app.filament.widgets.compare.compare-key-outcomes-widget', \App\Filament\Widgets\Compare\CompareKeyOutcomesWidget::class);
        Livewire::component('app.filament.widgets.compare.compare-net-worth-trajectory-widget', \App\Filament\Widgets\Compare\CompareNetWorthTrajectoryWidget::class);
        Livewire::component('app.filament.widgets.compare.compare-cash-flow-trajectory-widget', \App\Filament\Widgets\Compare\CompareCashFlowTrajectoryWidget::class);
        Livewire::component('app.filament.widgets.compare.compare-annual-income-widget', \App\Filament\Widgets\Compare\CompareAnnualIncomeWidget::class);
        Livewire::component('app.filament.widgets.compare.compare-annual-expenses-widget', \App\Filament\Widgets\Compare\CompareAnnualExpensesWidget::class);
        Livewire::component('app.filament.widgets.compare.compare-debt-load-widget', \App\Filament\Widgets\Compare\CompareDebtLoadWidget::class);
        Livewire::component('app.filament.widgets.compare.compare-fire-achievement-widget', \App\Filament\Widgets\Compare\CompareFireAchievementWidget::class);
        Livewire::component('app.filament.widgets.compare.compare-total-tax-widget', \App\Filament\Widgets\Compare\CompareTotalTaxWidget::class);
        Livewire::component('app.filament.widgets.compare.compare-tax-to-income-widget', \App\Filament\Widgets\Compare\CompareTaxToIncomeWidget::class);
        Livewire::component('app.filament.widgets.compare.compare-tax-to-net-worth-widget', \App\Filament\Widgets\Compare\CompareTaxToNetWorthWidget::class);
        Livewire::component('app.filament.widgets.compare.compare-ai-analysis-widget', \App\Filament\Widgets\Compare\CompareAiAnalysisWidget::class);
    }
}

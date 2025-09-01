<?php

namespace App\Filament\Widgets;

use App\Models\SimulationConfiguration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class SimulationStatsOverviewWidget extends BaseWidget
{
    public ?SimulationConfiguration $simulationConfiguration = null;

    public function mount(): void
    {
        // Get simulation_configuration_id from request
        $simulationConfigurationId = request()->get('simulation_configuration_id');

        if ($simulationConfigurationId) {
            $this->simulationConfiguration = SimulationConfiguration::with([
                'assetConfiguration',
                'simulationAssets.simulationAssetYears'
            ])
            ->where('user_id', auth()->id())
            ->find($simulationConfigurationId);
        }
    }

    protected function getStats(): array
    {
        if (!$this->simulationConfiguration) {
            return [];
        }

        $simulationAssets = $this->simulationConfiguration->simulationAssets;

        if ($simulationAssets->isEmpty()) {
            return [
                Stat::make('No Data', 'No simulation assets found')
                    ->description('Please run a simulation first')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning'),
            ];
        }

        // Calculate key metrics
        $totalStartValue = 0;
        $totalEndValue = 0;
        $totalIncome = 0;
        $totalExpenses = 0;
        $totalTaxes = 0;
        $yearCount = 0;

        foreach ($simulationAssets as $asset) {
            $assetYears = $asset->simulationAssetYears;

            if ($assetYears->isNotEmpty()) {
                $firstYear = $assetYears->first();
                $lastYear = $assetYears->last();

                $totalStartValue += $firstYear->start_value ?? 0;
                $totalEndValue += $lastYear->end_value ?? 0;

                foreach ($assetYears as $year) {
                    $totalIncome += $year->income_amount ?? 0;
                    $totalExpenses += $year->expence_amount ?? 0;
                    $totalTaxes += $year->asset_tax_amount ?? 0;
                }

                $yearCount = max($yearCount, $assetYears->count());
            }
        }

        $totalGrowth = $totalEndValue - $totalStartValue;
        $netCashFlow = $totalIncome - $totalExpenses;
        $annualGrowthRate = $yearCount > 0 && $totalStartValue > 0
            ? (pow($totalEndValue / $totalStartValue, 1 / $yearCount) - 1) * 100
            : 0;

        return [
            Stat::make('Starting Portfolio Value', Number::currency($totalStartValue, 'NOK'))
                ->description('Initial investment value')
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Projected End Value', Number::currency($totalEndValue, 'NOK'))
                ->description('Final portfolio value')
                ->icon('heroicon-o-chart-bar-square')
                ->color('primary'),

            Stat::make('Total Growth', Number::currency($totalGrowth, 'NOK'))
                ->description($totalGrowth >= 0 ? 'Portfolio appreciation' : 'Portfolio depreciation')
                ->icon($totalGrowth >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($totalGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('Annual Growth Rate', number_format($annualGrowthRate, 2) . '%')
                ->description('Compound annual growth rate')
                ->icon('heroicon-o-calculator')
                ->color($annualGrowthRate >= 0 ? 'success' : 'danger'),

            Stat::make('Total Income', Number::currency($totalIncome, 'NOK'))
                ->description('All income generated')
                ->icon('heroicon-o-plus-circle')
                ->color('success'),

            Stat::make('Total Expenses', Number::currency($totalExpenses, 'NOK'))
                ->description('All expenses incurred')
                ->icon('heroicon-o-minus-circle')
                ->color('warning'),

            Stat::make('Net Cash Flow', Number::currency($netCashFlow, 'NOK'))
                ->description($netCashFlow >= 0 ? 'Positive cash flow' : 'Negative cash flow')
                ->icon($netCashFlow >= 0 ? 'heroicon-o-arrow-up-circle' : 'heroicon-o-arrow-down-circle')
                ->color($netCashFlow >= 0 ? 'success' : 'danger'),

            Stat::make('Tax Burden', Number::currency($totalTaxes, 'NOK'))
                ->description('Total taxes paid')
                ->icon('heroicon-o-receipt-percent')
                ->color('warning'),

            Stat::make('Simulation Period', $yearCount . ' years')
                ->description('Analysis timeframe')
                ->icon('heroicon-o-calendar-days')
                ->color('info'),
        ];
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}

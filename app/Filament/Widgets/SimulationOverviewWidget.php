<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Auth;

class SimulationOverviewWidget extends BaseWidget
{
    protected ?int $simulationConfigurationId = null;
    protected ?\App\Models\SimulationConfiguration $simulationConfiguration = null;

    public function mount($simulationConfiguration = null): void
    {
        if ($simulationConfiguration) {
            $this->simulationConfiguration = $simulationConfiguration;
            $this->simulationConfigurationId = $simulationConfiguration->id;
        } else {
            // Get simulation_configuration_id from request
            $this->simulationConfigurationId = request()->get('simulation_configuration_id');

            if ($this->simulationConfigurationId) {
                $this->simulationConfiguration = \App\Models\SimulationConfiguration::with([
                    'assetConfiguration',
                    'simulationAssets.simulationAssetYears'
                ])
                ->where('user_id', Auth::id())
                ->find($this->simulationConfigurationId);
            }
        }
    }

    public function getStats(): array
    {
        if (!$this->simulationConfiguration) {
            return [];
        }

        $summary = $this->calculateSummary();

        return [
            Stat::make('Starting Portfolio Value', Number::currency($summary['start_value'], 'NOK'))
                ->description('Total assets at simulation start')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),

            Stat::make('Projected End Value', Number::currency($summary['end_value'], 'NOK'))
                ->description('Portfolio value at death age')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Total Growth', Number::currency($summary['total_growth'], 'NOK'))
                ->description($summary['growth_percentage'] . '% total return')
                ->descriptionIcon($summary['total_growth'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($summary['total_growth'] >= 0 ? 'success' : 'danger'),

            Stat::make('Annual Growth Rate', $summary['annual_growth_rate'] . '%')
                ->description('Average annual return')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Total Income', Number::currency($summary['total_income'], 'NOK'))
                ->description('Lifetime income generated')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('success'),

            Stat::make('Total Expenses', Number::currency($summary['total_expenses'], 'NOK'))
                ->description('Lifetime expenses')
                ->descriptionIcon('heroicon-m-minus-circle')
                ->color('warning'),

            Stat::make('Net Cash Flow', Number::currency($summary['net_cash_flow'], 'NOK'))
                ->description('Income minus expenses')
                ->descriptionIcon($summary['net_cash_flow'] >= 0 ? 'heroicon-m-arrow-up' : 'heroicon-m-arrow-down')
                ->color($summary['net_cash_flow'] >= 0 ? 'success' : 'danger'),

            Stat::make('Tax Burden', Number::currency($summary['total_taxes'], 'NOK'))
                ->description($summary['tax_rate'] . '% effective tax rate')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('warning'),

            Stat::make('Simulation Period', $summary['years'] . ' years')
                ->description($summary['start_year'] . ' - ' . $summary['end_year'])
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('gray'),
        ];
    }

    protected function calculateSummary(): array
    {
        $simulationAssets = $this->simulationConfiguration->simulationAssets;

        $startValue = 0;
        $endValue = 0;
        $totalIncome = 0;
        $totalExpenses = 0;
        $totalTaxes = 0;
        $startYear = null;
        $endYear = null;

        foreach ($simulationAssets as $asset) {
            $assetYears = $asset->simulationAssetYears->sortBy('year');

            if ($assetYears->isNotEmpty()) {
                $firstYear = $assetYears->first();
                $lastYear = $assetYears->last();

                $startValue += $firstYear->asset_market_amount ?? 0;
                $endValue += $lastYear->asset_market_amount ?? 0;

                if ($startYear === null || $firstYear->year < $startYear) {
                    $startYear = $firstYear->year;
                }
                if ($endYear === null || $lastYear->year > $endYear) {
                    $endYear = $lastYear->year;
                }

                foreach ($assetYears as $year) {
                    $totalIncome += $year->income_amount ?? 0;
                    $totalExpenses += $year->expence_amount ?? 0;
                    $totalTaxes += $year->asset_tax_amount ?? 0;
                }
            }
        }

        $totalGrowth = $endValue - $startValue;
        $years = $endYear && $startYear ? $endYear - $startYear + 1 : 0;
        $annualGrowthRate = $years > 0 && $startValue > 0
            ? round((pow($endValue / $startValue, 1 / $years) - 1) * 100, 2)
            : 0;
        $growthPercentage = $startValue > 0 ? round(($totalGrowth / $startValue) * 100, 1) : 0;
        $netCashFlow = $totalIncome - $totalExpenses;
        $taxRate = $totalIncome > 0 ? round(($totalTaxes / $totalIncome) * 100, 1) : 0;

        return [
            'start_value' => $startValue,
            'end_value' => $endValue,
            'total_growth' => $totalGrowth,
            'growth_percentage' => $growthPercentage,
            'annual_growth_rate' => $annualGrowthRate,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'total_taxes' => $totalTaxes,
            'net_cash_flow' => $netCashFlow,
            'tax_rate' => $taxRate,
            'years' => $years,
            'start_year' => $startYear,
            'end_year' => $endYear,
        ];
    }

    public function setSimulationConfiguration(\App\Models\SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
        $this->simulationConfigurationId = $simulationConfiguration->id;
    }
}

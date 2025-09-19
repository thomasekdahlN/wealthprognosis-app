<?php

namespace App\Filament\Widgets;

use App\Models\SimulationConfiguration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class SimulationTaxAnalysisWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    public ?SimulationConfiguration $simulationConfiguration = null;

    public function mount(?SimulationConfiguration $simulationConfiguration = null): void
    {
        if ($simulationConfiguration) {
            $this->simulationConfiguration = $simulationConfiguration;
            return;
        }

        // Fallback: Get simulation_configuration_id from request
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
                Stat::make('No Tax Data', 'No simulation data available')
                    ->description('Run simulation to see tax analysis')
                    ->icon('heroicon-o-receipt-percent')
                    ->color('warning'),
            ];
        }

        // Calculate tax metrics
        $totalTaxes = 0;
        $totalIncome = 0;
        $totalGains = 0;
        $yearCount = 0;
        $maxTaxYear = 0;
        $minTaxYear = PHP_FLOAT_MAX;

        foreach ($simulationAssets as $asset) {
            foreach ($asset->simulationAssetYears as $year) {
                $yearTax = $year->asset_tax_amount ?? 0;
                $yearIncome = $year->income_amount ?? 0;
                $yearGains = ($year->end_value ?? 0) - ($year->start_value ?? 0);

                $totalTaxes += $yearTax;
                $totalIncome += $yearIncome;
                $totalGains += $yearGains;
                $yearCount++;

                if ($yearTax > 0) {
                    $maxTaxYear = max($maxTaxYear, $yearTax);
                    $minTaxYear = min($minTaxYear, $yearTax);
                }
            }
        }

        $averageAnnualTax = $yearCount > 0 ? $totalTaxes / $yearCount : 0;
        $effectiveTaxRate = $totalIncome > 0 ? ($totalTaxes / $totalIncome) * 100 : 0;
        $taxOnGainsRate = $totalGains > 0 ? ($totalTaxes / $totalGains) * 100 : 0;

        if ($minTaxYear === PHP_FLOAT_MAX) {
            $minTaxYear = 0;
        }

        return [
            Stat::make('Total Tax Burden', Number::currency($totalTaxes, 'NOK'))
                ->description('Lifetime tax payments')
                ->icon('heroicon-o-receipt-percent')
                ->color('danger'),

            Stat::make('Average Annual Tax', Number::currency($averageAnnualTax, 'NOK'))
                ->description('Average yearly tax payment')
                ->icon('heroicon-o-calendar')
                ->color('warning'),

            Stat::make('Effective Tax Rate', number_format($effectiveTaxRate, 2) . '%')
                ->description('Tax as percentage of income')
                ->icon('heroicon-o-calculator')
                ->color($effectiveTaxRate > 30 ? 'danger' : ($effectiveTaxRate > 20 ? 'warning' : 'success')),

            Stat::make('Tax on Gains Rate', number_format($taxOnGainsRate, 2) . '%')
                ->description('Tax as percentage of capital gains')
                ->icon('heroicon-o-chart-bar')
                ->color($taxOnGainsRate > 25 ? 'danger' : ($taxOnGainsRate > 15 ? 'warning' : 'success')),

            Stat::make('Highest Tax Year', Number::currency($maxTaxYear, 'NOK'))
                ->description('Peak annual tax payment')
                ->icon('heroicon-o-arrow-up')
                ->color('danger'),

            Stat::make('Tax Efficiency Score', number_format(100 - min(100, $effectiveTaxRate), 2) . '%')
                ->description('Higher is better')
                ->icon('heroicon-o-bolt')
                ->color($effectiveTaxRate < 20 ? 'success' : ($effectiveTaxRate < 30 ? 'warning' : 'danger')),

            Stat::make('Lowest Tax Year', Number::currency($minTaxYear, 'NOK'))
                ->description('Minimum annual tax payment')
                ->icon('heroicon-o-arrow-down')
                ->color('success'),
        ];
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}

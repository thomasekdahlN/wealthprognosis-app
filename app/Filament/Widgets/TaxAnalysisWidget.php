<?php

namespace App\Filament\Widgets;

use App\Models\SimulationConfiguration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class TaxAnalysisWidget extends BaseWidget
{
    public ?SimulationConfiguration $simulationConfiguration = null;

    public function mount($simulationConfiguration = null): void
    {
        if ($simulationConfiguration) {
            $this->simulationConfiguration = $simulationConfiguration;
        } else {
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
    }

    protected function getStats(): array
    {
        if (!$this->simulationConfiguration) {
            return [];
        }

        $taxAnalysis = $this->calculateTaxAnalysis();

        return [
            Stat::make('Total Tax Burden', Number::currency($taxAnalysis['total_taxes'], 'NOK'))
                ->description('Lifetime tax payments')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('warning'),

            Stat::make('Effective Tax Rate', $taxAnalysis['effective_tax_rate'] . '%')
                ->description('Average tax rate on income')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Tax-Deferred Growth', Number::currency($taxAnalysis['tax_deferred_growth'], 'NOK'))
                ->description('Growth in tax-advantaged accounts')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Capital Gains Tax', Number::currency($taxAnalysis['capital_gains_tax'], 'NOK'))
                ->description('Tax on investment gains')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

            Stat::make('Tax Efficiency Score', $taxAnalysis['tax_efficiency_score'] . '/100')
                ->description('Portfolio tax optimization')
                ->descriptionIcon($taxAnalysis['tax_efficiency_score'] >= 70 ? 'heroicon-m-shield-check' : 'heroicon-m-shield-exclamation')
                ->color($taxAnalysis['tax_efficiency_score'] >= 70 ? 'success' : 'warning'),

            Stat::make('Tax-Free Income', Number::currency($taxAnalysis['tax_free_income'], 'NOK'))
                ->description('Income not subject to tax')
                ->descriptionIcon('heroicon-m-gift')
                ->color('success'),
        ];
    }

    protected function calculateTaxAnalysis(): array
    {
        $simulationAssets = $this->simulationConfiguration->simulationAssets;

        $totalTaxes = 0;
        $totalIncome = 0;
        $capitalGainsTax = 0;
        $taxDeferredGrowth = 0;
        $taxFreeIncome = 0;
        $taxAdvantageAssets = 0;
        $totalAssets = 0;

        foreach ($simulationAssets as $asset) {
            $assetYears = $asset->simulationAssetYears;
            $isTaxAdvantaged = in_array($asset->tax_type, ['none', 'tax_deferred']);

            foreach ($assetYears as $year) {
                $income = $year->income_amount ?? 0;
                $taxes = $year->tax_amount ?? 0;
                $assetValue = $year->asset_market_amount ?? 0;

                $totalIncome += $income;
                $totalTaxes += $taxes;
                $totalAssets += $assetValue;

                if ($isTaxAdvantaged) {
                    $taxAdvantageAssets += $assetValue;
                    $taxFreeIncome += $income;
                }

                if ($asset->tax_type === 'capital_gains') {
                    $capitalGainsTax += $taxes;
                }

                // Calculate tax-deferred growth (simplified)
                if ($asset->tax_type === 'tax_deferred') {
                    $taxDeferredGrowth += max(0, $assetValue - ($year->asset_acquisition_amount ?? 0));
                }
            }
        }

        $effectiveTaxRate = $totalIncome > 0 ? round(($totalTaxes / $totalIncome) * 100, 2) : 0;

        // Tax efficiency score based on tax-advantaged asset allocation
        $taxAdvantageRatio = $totalAssets > 0 ? ($taxAdvantageAssets / $totalAssets) : 0;
        $taxEfficiencyScore = round($taxAdvantageRatio * 100, 0);

        // Adjust score based on effective tax rate (lower is better)
        if ($effectiveTaxRate < 20) {
            $taxEfficiencyScore = min(100, $taxEfficiencyScore + 20);
        } elseif ($effectiveTaxRate > 35) {
            $taxEfficiencyScore = max(0, $taxEfficiencyScore - 20);
        }

        return [
            'total_taxes' => $totalTaxes,
            'effective_tax_rate' => $effectiveTaxRate,
            'tax_deferred_growth' => $taxDeferredGrowth,
            'capital_gains_tax' => $capitalGainsTax,
            'tax_efficiency_score' => $taxEfficiencyScore,
            'tax_free_income' => $taxFreeIncome,
            'total_income' => $totalIncome,
            'tax_advantage_ratio' => $taxAdvantageRatio,
        ];
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}

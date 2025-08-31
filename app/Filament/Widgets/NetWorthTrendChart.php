<?php

namespace App\Filament\Widgets;

use App\Models\Asset;
use App\Services\AssetConfigurationSessionService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class NetWorthTrendChart extends ChartWidget
{
    protected static ?int $sort = 6;

    public function getHeading(): string
    {
        return 'Net Worth Projection';
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $activeScenario = AssetConfigurationSessionService::getActiveAssetOwner();

        if (! $activeScenario) {
            return [
                'datasets' => [
                    [
                        'label' => 'No Data',
                        'data' => [0],
                        'borderColor' => '#ef4444',
                        'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    ],
                ],
                'labels' => ['Create a scenario to see projections'],
            ];
        }

        // Project net worth for the next 20 years
        $currentYear = now()->year;
        $years = [];
        $netWorthData = [];
        $assetsData = [];
        $debtsData = [];

        // Get current financial data
        $currentAssets = (float) (\App\Models\Asset::where('user_id', $user->id)
            ->where('is_active', true)
            ->sum('market_amount') ?? 0);
        $currentDebts = (float) (\App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->sum('mortgage_amount') ?? 0);

        $annualIncome = $this->calculateAnnualIncome($user);
        $annualExpenses = $this->calculateAnnualExpenses($user);
        $annualSavings = $annualIncome - $annualExpenses;

        // Project for 20 years
        $projectedAssets = $currentAssets;
        $projectedDebts = $currentDebts;

        for ($i = 0; $i <= 20; $i++) {
            $year = $currentYear + $i;
            $years[] = $year;

            if ($i == 0) {
                // Current year
                $assetsData[] = $projectedAssets;
                $debtsData[] = $projectedDebts;
                $netWorthData[] = $projectedAssets - $projectedDebts;
            } else {
                // Get growth rates for this year
                $assetGrowthRate = $this->getAverageGrowthRate($user, $year);
                $debtReductionRate = 0.05; // Assume 5% debt reduction per year

                // Project asset growth
                $projectedAssets = ($projectedAssets + $annualSavings) * (1 + $assetGrowthRate);

                // Project debt reduction (simplified)
                $projectedDebts = max(0, $projectedDebts * (1 - $debtReductionRate));

                $assetsData[] = $projectedAssets;
                $debtsData[] = $projectedDebts;
                $netWorthData[] = $projectedAssets - $projectedDebts;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Net Worth (NOK)',
                    'data' => $netWorthData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Total Assets (NOK)',
                    'data' => $assetsData,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Total Debts (NOK)',
                    'data' => $debtsData,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $years,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.dataset.label + ": NOK " + context.parsed.y.toLocaleString();
                        }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => false,
                    'ticks' => [
                        'callback' => 'function(value) {
                            return "NOK " + value.toLocaleString();
                        }',
                    ],
                ],
            ],
        ];
    }

    private function calculateAnnualIncome(\App\Models\User $user): float
    {
        // Get income from asset_years for the current year
        $currentYear = now()->year;

        return \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->where('year', $currentYear)
            ->whereNotNull('income_amount')
            ->sum('income_amount') ?? 0;
    }

    private function calculateAnnualExpenses(\App\Models\User $user): float
    {
        // Get expenses from asset_years for the current year
        $currentYear = now()->year;

        return \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->where('year', $currentYear)
            ->whereNotNull('expence_amount')
            ->sum('expence_amount') ?? 0;
    }

    private function getAverageGrowthRate(\App\Models\User $user, int $year): float
    {
        // Get weighted average growth rate based on asset allocation
        $assets = \App\Models\Asset::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $totalAssets = $assets->sum('market_amount');

        if ($totalAssets <= 0) {
            return 0.03; // Default 3% growth
        }

        $weightedGrowthRate = 0;

        foreach ($assets as $asset) {
            $weight = $asset->market_amount / $totalAssets;
            $growthRate = $this->getAssetTypeGrowthRate($asset->asset_type, $year);
            $weightedGrowthRate += $weight * $growthRate;
        }

        return $weightedGrowthRate;
    }

    private function getAssetTypeGrowthRate(string $assetType, int $year): float
    {
        // Try to get growth rate from configuration
        $changeRate = \App\Models\PrognosisChangeRate::forScenario('realistic')
            ->forAssetType($assetType)
            ->forYear($year)
            ->active()
            ->first();

        if ($changeRate) {
            return $changeRate->change_rate / 100;
        }

        // Default growth rates by asset type
        return match ($assetType) {
            'equityfund', 'stock' => 0.07,  // 7% for equities
            'bondfund' => 0.03,             // 3% for bonds
            'house', 'rental', 'cabin' => 0.04, // 4% for real estate
            'crypto' => 0.10,               // 10% for crypto (high volatility)
            'cash', 'bank' => 0.01,         // 1% for cash
            'gold' => 0.02,                 // 2% for gold
            default => 0.03,                // 3% default
        };
    }
}

<?php

namespace App\Filament\Widgets;

use App\Services\CurrentAssetConfiguration;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class FireMetricsOverview extends ChartWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'FIRE Progress Over Time';
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $activeScenario = app(CurrentAssetConfiguration::class)->get();

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
                'labels' => ['Create a scenario to see your FIRE progress'],
            ];
        }

        // Calculate FIRE metrics for the next 30 years
        $currentYear = now()->year;
        $years = [];
        $fireNumbers = [];
        $netWorthData = [];

        // Get current financial data
        $totalAssets = \App\Models\Asset::where('user_id', $user->id)
            ->where('is_active', true)
            ->sum('market_amount');
        $annualIncome = $this->calculateAnnualIncome($user);
        $annualExpenses = $this->calculateAnnualExpenses($user);
        $annualSavings = $annualIncome - $annualExpenses;

        // FIRE number (25x annual expenses)
        $fireNumber = $annualExpenses * 25;

        // Project wealth growth (simplified calculation)
        $currentNetWorth = $totalAssets;
        $growthRate = 0.07; // 7% average return

        for ($i = 0; $i <= 30; $i++) {
            $year = $currentYear + $i;
            $years[] = $year;
            $fireNumbers[] = $fireNumber * pow(1.03, $i); // Adjust FIRE number for inflation

            // Project net worth growth
            if ($i == 0) {
                $netWorthData[] = $currentNetWorth;
            } else {
                $currentNetWorth = ($currentNetWorth + $annualSavings) * (1 + $growthRate);
                $netWorthData[] = $currentNetWorth;
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
                ],
                [
                    'label' => 'FIRE Number (NOK)',
                    'data' => $fireNumbers,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'borderDash' => [5, 5],
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
            'scales' => [
                'y' => [
                    'beginAtZero' => false,
                    'ticks' => [
                        'callback' => 'function(value) { return "NOK " + value.toLocaleString(); }',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": NOK " + context.parsed.y.toLocaleString(); }',
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
}

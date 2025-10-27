<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class FireProgressAndCrossover extends ChartWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'FIRE Progress & Crossover Point';
    }

    protected function getData(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Get current financial data
        $currentYear = now()->year;

        $annualExpenses = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->where('year', $currentYear)
            ->whereNotNull('expence_amount')
            ->sum('expence_amount') ?: 0;

        $annualIncome = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->where('year', $currentYear)
            ->whereNotNull('income_amount')
            ->sum('income_amount') ?: 0;

        $annualSavings = $annualIncome - $annualExpenses;

        // Only show current year data - no future projections
        $years = [now()->year];
        $portfolioValueData = [];
        $fireNumberData = [];
        $potentialIncomeData = [];
        $expensesData = [];

        $currentAssets = \App\Models\Asset::where('user_id', $user->id)
            ->where('is_active', true)
            ->sum('market_amount');

        $projectedAssets = $currentAssets;
        $growthRate = 0.07; // 7% annual return

        foreach ($years as $year) {
            if ($year == now()->year) {
                $portfolioValue = $currentAssets;
            } else {
                // Project portfolio growth
                $projectedAssets = ($projectedAssets + $annualSavings) * (1 + $growthRate);
                $portfolioValue = $projectedAssets;
            }

            $fireNumber = $annualExpenses * 25;
            $potentialIncome = $portfolioValue * 0.04; // 4% rule

            $portfolioValueData[] = round($portfolioValue / 1000); // Convert to thousands
            $fireNumberData[] = round($fireNumber / 1000);
            $potentialIncomeData[] = round($potentialIncome / 1000);
            $expensesData[] = round($annualExpenses / 1000);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Investment Portfolio Value',
                    'data' => $portfolioValueData,
                    'borderColor' => '#10b981', // Green
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'FIRE Number (25× expenses)',
                    'data' => $fireNumberData,
                    'borderColor' => '#f59e0b', // Amber
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => false,
                    'borderDash' => [5, 5],
                    'tension' => 0,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Potential Annual Income (4% rule)',
                    'data' => $potentialIncomeData,
                    'borderColor' => '#3b82f6', // Blue
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                ],
                [
                    'label' => 'Annual Expenses',
                    'data' => $expensesData,
                    'borderColor' => '#ef4444', // Red
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => false,
                    'borderDash' => [10, 5],
                    'tension' => 0,
                    'yAxisID' => 'y1',
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
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Portfolio Value (NOK thousands)',
                    ],
                    'ticks' => [
                        'callback' => null,
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Annual Income/Expenses (NOK thousands)',
                    ],
                    'ticks' => [
                        'callback' => null,
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Year',
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
        ];
    }
}

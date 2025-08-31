<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class YearlyCashflowWidget extends ChartWidget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Yearly Cashflow';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Amount (NOK)',
                    ],
                    'ticks' => [
                        'callback' => null,
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Year',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'elements' => [
                'point' => [
                    'radius' => 4,
                    'hoverRadius' => 6,
                ],
            ],
        ];
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

        // Get years with data from asset_years table for the current user - only up to current year
        $years = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->where('year', '<=', now()->year) // Don't go beyond current year
            ->distinct()
            ->orderBy('year')
            ->pluck('year')
            ->toArray();

        // If no years found, use current year range - only up to current year
        if (empty($years)) {
            $years = range(now()->year - 2, now()->year);
        }

        $incomeData = [];
        $expenseData = [];
        $netData = [];

        foreach ($years as $year) {
            // Calculate total income for this year (considering factors)
            $incomeRecords = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('is_active', true);
            })
                ->where('year', $year)
                ->whereNotNull('income_amount')
                ->where('income_amount', '>', 0)
                ->get();

            $totalIncome = $incomeRecords->sum(function ($record) {
                $factor = $record->income_factor === 'monthly' ? 12 : 1; // Convert enum to multiplier

                return $record->income_amount * $factor; // Convert to annual
            });

            // Calculate total expenses for this year (considering factors)
            $expenseRecords = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('is_active', true);
            })
                ->where('year', $year)
                ->whereNotNull('expence_amount')
                ->where('expence_amount', '>', 0)
                ->get();

            $totalExpenses = $expenseRecords->sum(function ($record) {
                $factor = $record->expence_factor === 'monthly' ? 12 : 1; // Convert enum to multiplier

                return $record->expence_amount * $factor; // Convert to annual
            });

            // Calculate net (money left)
            $net = $totalIncome - $totalExpenses;

            // Full amounts, no division
            $incomeData[] = round($totalIncome);
            $expenseData[] = round($totalExpenses);
            $netData[] = round($net);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Income',
                    'data' => $incomeData,
                    'borderColor' => '#22c55e', // Green
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Expenses',
                    'data' => $expenseData,
                    'borderColor' => '#ef4444', // Red
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Net (Money Left)',
                    'data' => $netData,
                    'borderColor' => '#3b82f6', // Blue
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
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
}

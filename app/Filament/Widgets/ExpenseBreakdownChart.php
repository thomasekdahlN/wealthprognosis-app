<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class ExpenseBreakdownChart extends ChartWidget
{
    protected static ?int $sort = 7;

    public function getHeading(): string
    {
        return 'Monthly Expense Breakdown';
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

        // Get expense data by asset type from asset_years
        $currentYear = now()->year;

        $expenseData = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->where('year', $currentYear)
            ->whereNotNull('expence_amount')
            ->where('expence_amount', '>', 0)
            ->with('asset')
            ->get()
            ->groupBy('asset.asset_type')
            ->map(function ($items) {
                return $items->sum('expence_amount') / 12; // Convert to monthly
            })
            ->sortDesc();

        // Define colors for different expense categories
        $colors = [
            'house' => '#ef4444',      // Red
            'rental' => '#f97316',     // Orange
            'cabin' => '#f59e0b',      // Amber
            'car' => '#eab308',        // Yellow
            'boat' => '#84cc16',       // Lime
            'motorcycle' => '#22c55e', // Green
            'other' => '#10b981',      // Emerald
            'default' => '#6b7280',    // Gray
        ];

        $labels = [];
        $data = [];
        $backgroundColors = [];

        foreach ($expenseData as $assetType => $amount) {
            $labels[] = $this->getExpenseCategoryLabel($assetType);
            $data[] = round($amount);
            $backgroundColors[] = $colors[$assetType] ?? $colors['default'];
        }

        // If no expense data, show a placeholder
        if (empty($data)) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['#e5e7eb'],
                        'borderColor' => ['#d1d5db'],
                        'borderWidth' => 1,
                    ],
                ],
                'labels' => ['No expense data available'],
            ];
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => array_map(function ($color) {
                        return $color.'dd'; // Add transparency
                    }, $backgroundColors),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ": NOK " + context.parsed.toLocaleString() + " (" + percentage + "%)";
                        }',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }

    private function getExpenseCategoryLabel(string $assetType): string
    {
        return match ($assetType) {
            'house' => 'Housing',
            'rental' => 'Rental Property',
            'cabin' => 'Cabin',
            'car' => 'Car',
            'boat' => 'Boat',
            'motorcycle' => 'Motorcycle',
            'salary' => 'Living Expenses',
            default => ucfirst($assetType),
        };
    }
}

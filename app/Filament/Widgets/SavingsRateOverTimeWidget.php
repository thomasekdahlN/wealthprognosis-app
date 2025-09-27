<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SavingsRateOverTimeWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected ?int $assetConfigId = null;

    public function mount(): void
    {
        $this->assetConfigId = app(\App\Services\CurrentAssetConfiguration::class)->id();
    }

    public function getHeading(): string
    {
        $heading = 'Savings Rate Over Time';

        if ($this->assetConfigId) {
            $assetConfiguration = \App\Models\AssetConfiguration::find($this->assetConfigId);
            if ($assetConfiguration) {
                $heading = 'Savings Rate Over Time - '.$assetConfiguration->name;
            }
        }

        return $heading;
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

            // Apply asset configuration filtering if specified
            if ($this->assetConfigId) {
                $query->where('asset_configuration_id', $this->assetConfigId);
            }
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

        $savingsRateData = [];
        $targetRateData = [];

        foreach ($years as $year) {
            // Calculate total income for this year (considering factors)
            $incomeRecords = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('is_active', true);

                // Apply asset configuration filtering if specified
                if ($this->assetConfigId) {
                    $query->where('asset_configuration_id', $this->assetConfigId);
                }
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

                // Apply asset configuration filtering if specified
                if ($this->assetConfigId) {
                    $query->where('asset_configuration_id', $this->assetConfigId);
                }
            })
                ->where('year', $year)
                ->whereNotNull('expence_amount')
                ->where('expence_amount', '>', 0)
                ->get();

            $totalExpenses = $expenseRecords->sum(function ($record) {
                $factor = $record->expence_factor === 'monthly' ? 12 : 1; // Convert enum to multiplier

                return $record->expence_amount * $factor; // Convert to annual
            });

            // Calculate savings rate
            $savings = $totalIncome - $totalExpenses;
            $savingsRate = $totalIncome > 0 ? ($savings / $totalIncome) * 100 : 0;

            $savingsRateData[] = round($savingsRate, 1);
            $targetRateData[] = 50; // 50% target savings rate line
        }

        return [
            'datasets' => [
                [
                    'label' => 'Actual Savings Rate',
                    'data' => $savingsRateData,
                    'borderColor' => '#10b981', // Green
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Target (50%)',
                    'data' => $targetRateData,
                    'borderColor' => '#f59e0b', // Amber
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => false,
                    'borderDash' => [5, 5],
                    'tension' => 0,
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
                    'max' => 100, // Max 100% savings rate
                    'title' => [
                        'display' => true,
                        'text' => 'Savings Rate (%)',
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
}

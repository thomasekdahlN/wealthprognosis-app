<?php

namespace App\Filament\Widgets;

use App\Services\CurrentAssetConfiguration;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class CashFlowOverTime extends ChartWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        $heading = 'Cash Flow Over Time';

        $assetConfig = app(CurrentAssetConfiguration::class)->get();
        if ($assetConfig) {
            $heading .= ' - ' . $assetConfig->name;
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

        $assetConfigId = app(CurrentAssetConfiguration::class)->id()
            ?? request()->get('asset_configuration_id')
            ?? request()->get('asset_owner_id');

        // Collect years with data up to current year
        $years = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user, $assetConfigId) {
                $query->where('user_id', $user->id)->where('is_active', true);
                if ($assetConfigId) {
                    $query->where('asset_configuration_id', $assetConfigId);
                }
            })
            ->where('year', '<=', now()->year)
            ->distinct()
            ->orderBy('year')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            $years = range(now()->year - 2, now()->year);
        }

        $incomeData = [];
        $expenseData = [];
        $netCashflowData = [];

        foreach ($years as $year) {
            $incomeRecords = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user, $assetConfigId) {
                    $query->where('user_id', $user->id)->where('is_active', true);
                    if ($assetConfigId) {
                        $query->where('asset_configuration_id', $assetConfigId);
                    }
                })
                ->where('year', $year)
                ->whereNotNull('income_amount')
                ->where('income_amount', '>', 0)
                ->get();

            $expenseRecords = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user, $assetConfigId) {
                    $query->where('user_id', $user->id)->where('is_active', true);
                    if ($assetConfigId) {
                        $query->where('asset_configuration_id', $assetConfigId);
                    }
                })
                ->where('year', $year)
                ->whereNotNull('expence_amount')
                ->where('expence_amount', '>', 0)
                ->get();

            $annualIncome = $incomeRecords->sum(function ($record) {
                $factor = $record->income_factor === 'monthly' ? 12 : 1;
                return $record->income_amount * $factor;
            });

            $annualExpenses = $expenseRecords->sum(function ($record) {
                $factor = $record->expence_factor === 'monthly' ? 12 : 1;
                return $record->expence_amount * $factor;
            });

            $incomeData[] = round($annualIncome, 2);
            $expenseData[] = round($annualExpenses, 2);
            $netCashflowData[] = round($annualIncome - $annualExpenses, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Annual Income (NOK)',
                    'data' => $incomeData,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Annual Expenses (NOK)',
                    'data' => $expenseData,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Net Cashflow (NOK)',
                    'data' => $netCashflowData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
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
            'responsive' => true,
            'maintainAspectRatio' => false,
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
                    'beginAtZero' => false,
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
        ];
    }
}


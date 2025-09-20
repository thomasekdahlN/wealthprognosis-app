<?php

namespace App\Filament\Widgets;

use App\Services\CurrentAssetConfiguration;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class NetWorthOverTime extends ChartWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        $heading = 'Net Worth Over Time';

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

        // Collect years with data for this user & configuration up to current year
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

        $netWorthData = [];

        foreach ($years as $year) {
            $totals = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user, $assetConfigId) {
                    $query->where('user_id', $user->id)->where('is_active', true);
                    if ($assetConfigId) {
                        $query->where('asset_configuration_id', $assetConfigId);
                    }
                })
                ->where('year', $year)
                ->selectRaw('COALESCE(SUM(asset_market_amount), 0) as assets, COALESCE(SUM(mortgage_amount), 0) as liabilities')
                ->first();

            $assets = (float) ($totals->assets ?? 0);
            $liabilities = (float) ($totals->liabilities ?? 0);
            $netWorthData[] = round($assets - $liabilities, 2);
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


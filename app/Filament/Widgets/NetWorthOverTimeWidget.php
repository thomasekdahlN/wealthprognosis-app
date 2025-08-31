<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class NetWorthOverTimeWidget extends ChartWidget
{
    protected static ?int $sort = 3; // Charts below stat widgets

    protected int|string|array $columnSpan = 'full';

    protected ?int $assetOwnerId = null;

    public function mount(): void
    {
        $this->assetOwnerId = request()->get('asset_owner_id');
    }

    public function getHeading(): string
    {
        $heading = 'Net Worth Over Time';

        if ($this->assetOwnerId) {
            $assetOwner = \App\Models\AssetConfiguration::find($this->assetOwnerId);
            if ($assetOwner) {
                $heading = 'Net Worth Over Time - '.$assetOwner->name;
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

        // Get historical data from asset_years table - only up to current year
        $years = range(2023, now()->year); // Show only historical and current year data
        $netWorthData = [];
        $assetsData = [];
        $liabilitiesData = [];

        foreach ($years as $year) {
            // Get total assets for this year
            $totalAssets = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('is_active', true);

                // Apply asset owner filtering if specified
                if ($this->assetOwnerId) {
                    $query->where('asset_owner_id', $this->assetOwnerId);
                }
            })
                ->where('year', $year)
                ->sum('asset_market_amount') ?? 0;

            // If no data for future years, use current assets with growth projection
            if ($totalAssets == 0 && $year > now()->year) {
                $currentAssets = \App\Models\Asset::where('user_id', $user->id)
                    ->where('is_active', true)
                    ->sum('market_amount');

                $yearsFromNow = $year - now()->year;
                $growthRate = 0.07; // 7% annual growth
                $totalAssets = $currentAssets * pow(1 + $growthRate, $yearsFromNow);
            }

            // Get total liabilities (mortgages) for this year
            $totalLiabilities = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('is_active', true);

                // Apply asset owner filtering if specified
                if ($this->assetOwnerId) {
                    $query->where('asset_owner_id', $this->assetOwnerId);
                }
            })
                ->where('year', $year)
                ->sum('mortgage_amount') ?? 0;

            // Calculate net worth
            $netWorth = $totalAssets - $totalLiabilities;

            $netWorthData[] = round($netWorth); // Full amount, no division
            $assetsData[] = round($totalAssets);
            $liabilitiesData[] = round($totalLiabilities);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Net Worth',
                    'data' => $netWorthData,
                    'borderColor' => '#10b981', // Green
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Total Assets',
                    'data' => $assetsData,
                    'borderColor' => '#3b82f6', // Blue
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Total Liabilities',
                    'data' => $liabilitiesData,
                    'borderColor' => '#ef4444', // Red
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
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
        ];
    }
}

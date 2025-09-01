<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TotalAssetsWidget extends BaseWidget
{
    protected static ?int $sort = 0; // Row 1: Asset Overview

    protected ?int $assetConfigurationId = null;

    public function mount(): void
    {
        $this->assetConfigurationId = request()->get('asset_configuration_id') ?? request()->get('asset_owner_id');
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $currentYear = now()->year;

        if (! $user) {
            return [
                Stat::make('No User', 'Please log in to see portfolio data')
                    ->color('warning'),
            ];
        }

        // Calculate total assets value (ALL assets with positive market value)
        $totalPortfolio = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);

            // Apply asset configuration filtering if specified
            if ($this->assetConfigurationId) {
                $query->where('asset_configuration_id', $this->assetConfigurationId);
            }
        })
            ->where('year', $currentYear)
            ->where('asset_market_amount', '>', 0)
            ->sum('asset_market_amount') ?? 0;

        // Calculate FIRE-sellable assets for comparison
        $fireSellableAssets = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);

            // Apply asset owner filtering if specified
            if ($this->assetOwnerId) {
                $query->where('asset_owner_id', $this->assetOwnerId);
            }

            // Only include assets that are FIRE-sellable
            $query->whereHas('assetType', function ($assetTypeQuery) {
                $assetTypeQuery->where('is_fire_sellable', true);
            });
        })
            ->where('year', $currentYear)
            ->where('asset_market_amount', '>', 0)
            ->sum('asset_market_amount') ?? 0;

        // Calculate non-FIRE assets (illiquid assets like house, car, etc.)
        $nonFireAssets = $totalPortfolio - $fireSellableAssets;

        // Calculate percentage breakdown
        $nonFirePercentage = $totalPortfolio > 0 ? ($nonFireAssets / $totalPortfolio) * 100 : 0;

        return [
            Stat::make('Total Assets', 'NOK '.number_format($totalPortfolio, 0, ',', ' '))
                ->description('All assets combined')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('success'),

            Stat::make('Illiquid Assets', 'NOK '.number_format($nonFireAssets, 0, ',', ' '))
                ->description(number_format($nonFirePercentage, 1).'% of total assets')
                ->descriptionIcon('heroicon-m-home')
                ->color('warning'),
        ];
    }
}

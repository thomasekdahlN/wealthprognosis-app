<?php

namespace App\Filament\Widgets;

use App\Helpers\AmountHelper;
use App\Services\FireCalculationService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AssetOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 0; // Row 1: Asset Overview

    public ?int $assetOwnerId = null;

    public function mount(?int $assetOwnerId = null): void
    {
        $this->assetOwnerId = $assetOwnerId ?? request()->get('asset_owner_id') ?? session('dashboard_asset_owner_id');
    }

    protected function getStats(): array
    {
        $data = FireCalculationService::getFinancialData($this->assetOwnerId);

        if (! $data['user']) {
            return [
                Stat::make('Total Assets', 'Please log in')->color('warning'),
                Stat::make('Investment Assets', 'Please log in')->color('warning'),
                Stat::make('Net Worth', 'Please log in')->color('warning'),
                Stat::make('Total Mortgage', 'Please log in')->color('warning'),
            ];
        }

        // Calculate illiquid assets (non-FIRE-sellable)
        $illiquidAssets = $data['allAssets'] - $data['totalAssets'];
        $illiquidPercentage = $data['allAssets'] > 0 ? ($illiquidAssets / $data['allAssets']) * 100 : 0;

        return [
            // Total Assets
            Stat::make('Total Assets', 'NOK '.AmountHelper::formatNorwegian($data['allAssets']))
                ->description('All assets combined')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('success'),

            // Investment Assets (FIRE-sellable)
            Stat::make('Investment Assets', 'NOK '.AmountHelper::formatNorwegian($data['totalAssets']))
                ->description('FIRE-sellable assets only')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('info'),

            // Net Worth
            Stat::make('Net Worth', 'NOK '.AmountHelper::formatNorwegian($data['netWorth']))
                ->description('Assets minus liabilities')
                ->descriptionIcon('heroicon-m-scale')
                ->color($data['netWorth'] > 0 ? 'success' : 'danger'),

            // Total Mortgage
            Stat::make('Total Mortgage', 'NOK '.AmountHelper::formatNorwegian($data['totalLiabilities']))
                ->description('Current mortgage balance')
                ->descriptionIcon('heroicon-m-home')
                ->color($data['totalLiabilities'] > 0 ? 'warning' : 'success'),
        ];
    }
}

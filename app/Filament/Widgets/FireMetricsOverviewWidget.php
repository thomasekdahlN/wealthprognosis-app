<?php

namespace App\Filament\Widgets;

use App\Services\FireCalculationService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FireMetricsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2; // Row 3: FIRE Metrics

    protected int|string|array $columnSpan = 6; // Place side-by-side in one row

    public ?int $assetConfigurationId = null;

    public function mount(?int $assetConfigurationId = null): void
    {
        $this->assetConfigurationId = $assetConfigurationId ?? app(\App\Services\CurrentAssetConfiguration::class)->id();
    }

    protected function getStats(): array
    {
        $data = FireCalculationService::getFinancialData($this->assetConfigurationId);

        if (! $data['user']) {
            return [
                Stat::make('FIRE Number', 'Please log in')->color('warning'),
                Stat::make('FIRE Progress', 'Please log in')->color('warning'),
                Stat::make('FIRE Passive Income', 'Please log in')->color('warning'),
                Stat::make('FIRE Gap', 'Please log in')->color('warning'),
            ];
        }

        return [
            // FIRE Number
            Stat::make('FIRE Number', 'NOK '.number_format($data['fireNumber'], 0, ',', ' '))
                ->description('25x annual expenses')
                ->descriptionIcon('heroicon-m-fire')
                ->color('info'),

            // FIRE Progress
            Stat::make('FIRE Progress', number_format($data['progressToFire'], 1).'%')
                ->description('Progress to FIRE goal')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($data['progressToFire'] >= 100 ? 'success' : 'info'),

            // FIRE Passive Income
            Stat::make('FIRE Passive Income', 'NOK '.number_format($data['potentialAnnualIncome'], 0, ',', ' '))
                ->description('4% rule annual withdrawal')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color($data['potentialAnnualIncome'] >= $data['annualExpenses'] ? 'success' : 'gray'),

            // FIRE Gap (The Gap)
            Stat::make('FIRE Gap', 'NOK '.number_format($data['theGap'], 0, ',', ' '))
                ->description('Annual savings (Income - Expenses)')
                ->descriptionIcon($data['theGap'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($data['theGap'] >= 0 ? 'success' : 'danger'),
        ];
    }
}

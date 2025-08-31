<?php

namespace App\Filament\Widgets;

use App\Services\AssetConfigurationSessionService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class WealthPrognosisOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $user = Auth::user();
        $activeScenario = AssetConfigurationSessionService::getActiveAssetOwner();

        if (! $activeScenario) {
            return [
                Stat::make('No Active Scenario', 'Create a scenario to see your wealth prognosis')
                    ->description('Start by creating your first financial scenario')
                    ->descriptionIcon('heroicon-m-plus-circle')
                    ->color('warning'),
            ];
        }

        // Get assets for the current user instead of from prognosis
        $totalAssets = \App\Models\Asset::where('user_id', $user->id)
            ->where('is_active', true)
            ->sum('market_amount');

        // Calculate total mortgages from asset_years table
        $totalMortgages = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->sum('mortgage_amount');

        $netWorth = $totalAssets - $totalMortgages;

        // Get asset owner information for retirement calculations
        $assetOwner = \App\Models\AssetConfiguration::where('user_id', $user->id)
            ->first();

        if (! $assetOwner) {
            // If no asset owner, provide default values
            $yearsToRetirement = 0;
            $currentAge = 0;
            $birthYear = date('Y') - 40; // Default to 40 years old
            $pensionWishYear = 65; // Default retirement age
        } else {
            $currentYear = date('Y');
            $currentAge = $currentYear - $assetOwner->birth_year;
            $yearsToRetirement = max(0, $assetOwner->pension_wish_year - $currentAge);
            $birthYear = $assetOwner->birth_year;
            $pensionWishYear = $assetOwner->pension_wish_year;
        }

        return [
            Stat::make('Net Worth', 'NOK '.number_format($netWorth, 0, ',', ' '))
                ->description('Total assets minus mortgages')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($netWorth > 0 ? 'success' : 'danger'),

            Stat::make('Total Assets', 'NOK '.number_format($totalAssets, 0, ',', ' '))
                ->description('Market value of all assets')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('info'),

            Stat::make('Years to Retirement', $yearsToRetirement > 0 ? $yearsToRetirement : 'Achieved!')
                ->description($yearsToRetirement > 0 ? "Retire at age {$pensionWishYear}" : 'Retirement age reached')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($yearsToRetirement <= 5 ? 'success' : ($yearsToRetirement <= 15 ? 'warning' : 'primary')),

            Stat::make('Current Age', $currentAge > 0 ? $currentAge : 'Not set')
                ->description($currentAge > 0 ? "Born in {$birthYear}" : 'Create an asset owner to set age')
                ->descriptionIcon('heroicon-m-user')
                ->color($currentAge > 0 ? 'gray' : 'warning'),
        ];
    }
}

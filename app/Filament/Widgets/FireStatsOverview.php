<?php

namespace App\Filament\Widgets;

use App\Services\AssetConfigurationSessionService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class FireStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::user();
        $activeScenario = AssetConfigurationSessionService::getActiveAssetOwner();

        if (! $activeScenario) {
            return [
                Stat::make('No Active Scenario', 'Create a scenario to see FIRE metrics')
                    ->description('Start by creating your first financial scenario')
                    ->descriptionIcon('heroicon-m-plus-circle')
                    ->color('warning'),
            ];
        }

        // Calculate key FIRE metrics
        $annualIncome = (float) $this->calculateAnnualIncome($user);
        $annualExpenses = (float) $this->calculateAnnualExpenses($user);
        $annualSavings = max(0.0, $annualIncome - $annualExpenses);
        $savingsRate = $annualIncome > 0 ? ($annualSavings / $annualIncome) * 100 : 0;

        // FIRE number (25x annual expenses)
        $fireNumber = max(0.0, $annualExpenses * 25);

        // Current net worth
        $totalAssets = (float) (\App\Models\Asset::where('user_id', $user->id)
            ->where('is_active', true)
            ->sum('market_amount') ?? 0);
        $totalMortgages = (float) (\App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->sum('mortgage_amount') ?? 0);
        $netWorth = $totalAssets - $totalMortgages;

        // FIRE progress percentage
        $fireProgress = $fireNumber > 0 ? ($netWorth / $fireNumber) * 100 : 0;

        // Safe withdrawal rate (4% rule)
        $safeWithdrawalAmount = $netWorth * 0.04;

        // Years to FIRE (simplified calculation)
        $yearsToFire = $this->calculateYearsToFire($netWorth, $fireNumber, $annualSavings);

        return [
            Stat::make('Savings Rate', number_format($savingsRate, 1).'%')
                ->description('Percentage of income saved')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($savingsRate >= 50 ? 'success' : ($savingsRate >= 20 ? 'warning' : 'danger')),

            Stat::make('FIRE Number', 'NOK '.number_format($fireNumber, 0, ',', ' '))
                ->description('25x annual expenses')
                ->descriptionIcon('heroicon-m-fire')
                ->color('info'),

            Stat::make('FIRE Progress', number_format($fireProgress, 1).'%')
                ->description($fireProgress >= 100 ? 'FIRE achieved!' : 'Progress to financial independence')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($fireProgress >= 100 ? 'success' : ($fireProgress >= 75 ? 'warning' : 'primary')),

            Stat::make('Safe Withdrawal', 'NOK '.number_format($safeWithdrawalAmount, 0, ',', ' '))
                ->description('4% rule annual withdrawal')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($safeWithdrawalAmount >= $annualExpenses ? 'success' : 'gray'),

            Stat::make('Years to FIRE', $yearsToFire > 0 ? $yearsToFire : 'Achieved!')
                ->description($yearsToFire > 0 ? 'At current savings rate' : 'Financial independence reached')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($yearsToFire <= 5 ? 'success' : ($yearsToFire <= 15 ? 'warning' : 'primary')),

            Stat::make('Annual Savings', 'NOK '.number_format($annualSavings, 0, ',', ' '))
                ->description('Income minus expenses')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($annualSavings > 0 ? 'success' : 'danger'),
        ];
    }

    private function calculateAnnualIncome(\App\Models\User $user): float
    {
        // Get income from asset_years for the current year
        $currentYear = now()->year;

        return \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->where('year', $currentYear)
            ->whereNotNull('income_amount')
            ->sum('income_amount') ?? 0;
    }

    private function calculateAnnualExpenses(\App\Models\User $user): float
    {
        // Get expenses from asset_years for the current year
        $currentYear = now()->year;

        return \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->where('year', $currentYear)
            ->whereNotNull('expence_amount')
            ->sum('expence_amount') ?? 0;
    }

    private function calculateYearsToFire(float $currentNetWorth, float $fireNumber, float $annualSavings): int
    {
        if ($currentNetWorth >= $fireNumber) {
            return 0; // Already achieved FIRE
        }

        if ($annualSavings <= 0) {
            return 999; // Cannot achieve FIRE with negative or zero savings
        }

        // Simplified calculation assuming 7% annual return
        $growthRate = 0.07;
        $years = 0;
        $netWorth = $currentNetWorth;

        while ($netWorth < $fireNumber && $years < 100) {
            $netWorth = ($netWorth + $annualSavings) * (1 + $growthRate);
            $years++;
        }

        return $years;
    }
}

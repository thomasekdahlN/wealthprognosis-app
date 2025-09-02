<?php

namespace App\Filament\Widgets;

use App\Services\AssetConfigurationSessionService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PersonalFinanceOverview extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $user = Auth::user();
        $activeScenario = AssetConfigurationSessionService::getActiveAssetConfiguration();

        if (! $activeScenario) {
            return [
                Stat::make('No Active Scenario', 'Create a scenario to see your finances')
                    ->description('Start by creating your first financial scenario')
                    ->descriptionIcon('heroicon-m-plus-circle')
                    ->color('warning'),
            ];
        }

        // Calculate monthly income for debt-to-income ratio
        $annualIncome = $this->calculateAnnualIncome($user);
        $monthlyIncome = max(0.0, $annualIncome / 12);

        // Assets and debts
        $totalAssets = (float) (\App\Models\Asset::where('user_id', $user->id)
            ->where('is_active', true)
            ->sum('market_amount') ?? 0);
        $totalMortgages = (float) (\App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->sum('mortgage_amount') ?? 0);

        $netWorth = $totalAssets - $totalMortgages;

        // Debt-to-income ratio
        $monthlyDebtPayments = max(0.0, $this->calculateMonthlyDebtPayments($user));
        $debtToIncomeRatio = $monthlyIncome > 0 ? ($monthlyDebtPayments / $monthlyIncome) * 100 : 0;

        return [
            Stat::make('Net Worth', 'NOK '.number_format($netWorth, 0, ',', ' '))
                ->description('Assets minus debts')
                ->descriptionIcon('heroicon-m-scale')
                ->color($netWorth > 0 ? 'success' : 'danger'),

            Stat::make('Debt-to-Income', number_format($debtToIncomeRatio, 1).'%')
                ->description('Monthly debt payments vs income')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color($debtToIncomeRatio <= 28 ? 'success' : ($debtToIncomeRatio <= 36 ? 'warning' : 'danger')),
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

    private function calculateMonthlyDebtPayments(\App\Models\User $user): float
    {
        // Calculate monthly mortgage payments from asset_years
        $currentYear = now()->year;

        $mortgages = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->where('year', $currentYear)
            ->whereNotNull('mortgage_amount')
            ->where('mortgage_amount', '>', 0)
            ->get(['mortgage_amount', 'mortgage_interest', 'mortgage_years']);

        return $mortgages->sum(function ($mortgage) {
            // Calculate monthly payment for each mortgage
            $years = (int) $mortgage->mortgage_years;
            $interestRate = (float) str_replace('%', '', $mortgage->mortgage_interest ?? '0');

            if ($years <= 0 || $interestRate <= 0) {
                return 0;
            }

            $monthlyRate = $interestRate / 100 / 12;
            $totalPayments = $years * 12;

            return $mortgage->mortgage_amount *
                ($monthlyRate * pow(1 + $monthlyRate, $totalPayments)) /
                (pow(1 + $monthlyRate, $totalPayments) - 1);
        });
    }
}

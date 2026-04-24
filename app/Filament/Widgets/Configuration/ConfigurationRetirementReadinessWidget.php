<?php

namespace App\Filament\Widgets\Configuration;

use App\Models\AssetConfiguration;
use App\Models\AssetYear;
use App\Models\User;
use App\Services\CurrentAssetConfiguration;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class ConfigurationRetirementReadinessWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    public function getHeading(): string
    {
        return 'Retirement Readiness';
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $activeScenario = app(CurrentAssetConfiguration::class)->get();

        if (! $activeScenario) {
            return [
                'datasets' => [
                    [
                        'label' => 'No Data',
                        'data' => [0],
                        'borderColor' => '#ef4444',
                        'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    ],
                ],
                'labels' => ['Create a scenario to see retirement projections'],
            ];
        }

        // Get retirement information from asset_configurations table
        $assetOwner = AssetConfiguration::where('user_id', $user->id)->first();

        if (! $assetOwner) {
            // Use default values if no asset owner
            $currentAge = 40;
            $retirementAge = 65;
            $lifeExpectancy = 85;
        } else {
            $currentYear = date('Y');
            $currentAge = $currentYear - $assetOwner->birth_year;
            $retirementAge = $assetOwner->pension_wish_year ?? 65; // Default to 65 if null
            $lifeExpectancy = $assetOwner->death_year ?? 85;
        }

        $ages = [];
        $netWorthData = [];
        $retirementNeedsData = [];
        $pensionIncomeData = [];

        // Current financial situation
        $currentYear = (int) date('Y');
        $totalAssets = AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->where('year', $currentYear)
            ->sum('asset_market_amount') ?: 0;
        $totalMortgages = AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->where('year', $currentYear)
            ->sum('mortgage_amount') ?: 0;
        $currentNetWorth = $totalAssets - $totalMortgages;

        $annualIncome = $this->calculateAnnualIncome($user);
        $annualExpenses = $this->calculateAnnualExpenses($user);
        $annualSavings = $annualIncome - $annualExpenses;

        // Retirement income replacement ratio (typically 70-80% of pre-retirement income)
        $retirementIncomeNeeded = $annualExpenses * 0.8; // 80% of current expenses
        $retirementCapitalNeeded = $retirementIncomeNeeded * 25; // 4% withdrawal rule

        // Project from current age to life expectancy
        $projectedNetWorth = $currentNetWorth;
        $growthRate = 0.06; // 6% average return

        for ($age = $currentAge; $age <= $lifeExpectancy; $age++) {
            $ages[] = $age;

            if ($age < $retirementAge) {
                // Accumulation phase
                $projectedNetWorth = ($projectedNetWorth + $annualSavings) * (1 + $growthRate);
                $pensionIncome = 0;
            } else {
                // Retirement phase - withdrawing 4% annually
                $withdrawalAmount = $projectedNetWorth * 0.04;
                $projectedNetWorth = $projectedNetWorth * (1 + $growthRate) - $withdrawalAmount;

                // Add pension income (simplified calculation)
                $pensionIncome = $this->calculatePensionIncome($user, $age, $retirementAge);
            }

            $netWorthData[] = max(0, $projectedNetWorth);
            $retirementNeedsData[] = $retirementCapitalNeeded;
            $pensionIncomeData[] = $pensionIncome * 25; // Convert annual income to capital equivalent
        }

        return [
            'datasets' => [
                [
                    'label' => 'Projected Net Worth (NOK)',
                    'data' => $netWorthData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Retirement Capital Needed (NOK)',
                    'data' => $retirementNeedsData,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'borderDash' => [5, 5],
                    'fill' => false,
                ],
                [
                    'label' => 'Pension Capital Equivalent (NOK)',
                    'data' => $pensionIncomeData,
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $ages,
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
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Age',
                    ],
                ],
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
            ],
        ];
    }

    private function calculateAnnualIncome(User $user): float
    {
        // Get income from asset_years for the current year
        $currentYear = now()->year;

        return AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->where('year', $currentYear)
            ->whereNotNull('income_amount')
            ->sum('income_amount') ?: 0;
    }

    private function calculateAnnualExpenses(User $user): float
    {
        // Get expenses from asset_years for the current year
        $currentYear = now()->year;

        return AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->where('year', $currentYear)
            ->whereNotNull('expence_amount')
            ->sum('expence_amount') ?: 0;
    }

    private function calculatePensionIncome(User $user, int $age, ?int $retirementAge): float
    {
        // Simplified Norwegian pension calculation
        // This would normally be much more complex

        // Default retirement age if null
        $retirementAge = $retirementAge ?? 65;

        if ($age < $retirementAge) {
            return 0;
        }

        // Basic pension amount (simplified)
        $basicPension = 120000; // Approximate basic pension in NOK

        // Add occupational pension if available (4% withdrawal of current market value)
        $currentYear = now()->year;
        $occupationalPension = AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->where('is_active', true)
                ->where('asset_type', 'otp');
        })
            ->where('year', $currentYear)
            ->sum('asset_market_amount') * 0.04;

        return $basicPension + $occupationalPension;
    }
}

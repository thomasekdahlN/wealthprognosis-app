<?php

namespace App\Filament\Widgets;

use App\Models\SimulationConfiguration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class SimulationFireAnalysisWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    public ?SimulationConfiguration $simulationConfiguration = null;

    public static function canView(): bool
    {
        // Only render on the Simulation Dashboard, not on the main dashboard
        return request()->routeIs('filament.admin.pages.simulation-dashboard');
    }

    public function mount(?SimulationConfiguration $simulationConfiguration = null): void
    {
        if ($simulationConfiguration) {
            $this->simulationConfiguration = $simulationConfiguration;

            return;
        }

        // Fallback: Get simulation_configuration_id from request
        $simulationConfigurationId = request()->get('simulation_configuration_id');

        if ($simulationConfigurationId) {
            $this->simulationConfiguration = SimulationConfiguration::with([
                'assetConfiguration',
                'simulationAssets.simulationAssetYears',
            ])
                ->where('user_id', auth()->id())
                ->find($simulationConfigurationId);
        }
    }

    protected function getStats(): array
    {
        if (! $this->simulationConfiguration) {
            return [];
        }

        $simulationAssets = $this->simulationConfiguration->simulationAssets;

        if ($simulationAssets->isEmpty()) {
            return [
                Stat::make('No FIRE Data', 'No simulation data available')
                    ->description('Run simulation to see FIRE analysis')
                    ->icon('heroicon-o-fire')
                    ->color('warning'),
            ];
        }

        // Calculate FIRE metrics
        $currentYear = date('Y');
        $birthYear = $this->simulationConfiguration->birth_year ?? $currentYear - 30;
        $currentAge = $currentYear - $birthYear;
        $deathAge = $this->simulationConfiguration->death_age ?? 85;

        // Calculate annual expenses (average from simulation)
        $totalExpenses = 0;
        $yearCount = 0;

        foreach ($simulationAssets as $asset) {
            foreach ($asset->simulationAssetYears as $year) {
                $totalExpenses += $year->expence_amount ?? 0;
                $yearCount++;
            }
        }

        $annualExpenses = $yearCount > 0 ? $totalExpenses / $yearCount : 0;

        // FIRE number (25x annual expenses)
        $fireNumber = $annualExpenses * 25;

        // Current portfolio value (from simulation start)
        $currentPortfolioValue = 0;
        foreach ($simulationAssets as $asset) {
            $firstYear = $asset->simulationAssetYears->first();
            if ($firstYear) {
                $currentPortfolioValue += $firstYear->start_value ?? 0;
            }
        }

        // FIRE progress
        $fireProgress = $fireNumber > 0 ? ($currentPortfolioValue / $fireNumber) * 100 : 0;

        // Years to FIRE (simplified calculation)
        $yearsToFire = $fireProgress >= 100 ? 0 : max(0, $deathAge - $currentAge);

        // Check if FIRE is achieved
        $fireAchieved = $fireProgress >= 100;
        $fireYear = $fireAchieved ? $currentYear : $currentYear + $yearsToFire;

        // Safe withdrawal rate (4% rule)
        $safeWithdrawalRate = 4.0;
        $annualPassiveIncome = $currentPortfolioValue * ($safeWithdrawalRate / 100);

        // Expense coverage
        $expenseCoverage = $annualExpenses > 0 ? ($annualPassiveIncome / $annualExpenses) * 100 : 0;

        return [
            Stat::make('FIRE Number', Number::currency($fireNumber, 'NOK'))
                ->description('Target portfolio for financial independence')
                ->icon('heroicon-o-fire')
                ->color('primary'),

            Stat::make('Current Portfolio', Number::currency($currentPortfolioValue, 'NOK'))
                ->description('Current investment value')
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Current Progress', number_format($fireProgress, 1).'%')
                ->description($fireAchieved ? 'FIRE achieved!' : 'Progress to financial independence')
                ->icon($fireAchieved ? 'heroicon-o-check-circle' : 'heroicon-o-clock')
                ->color($fireAchieved ? 'success' : ($fireProgress > 50 ? 'warning' : 'danger')),

            Stat::make('Years to FIRE', $fireAchieved ? 'Achieved' : $yearsToFire.' years')
                ->description($fireAchieved ? 'Financial independence reached' : 'Estimated time to FIRE')
                ->icon($fireAchieved ? 'heroicon-o-trophy' : 'heroicon-o-calendar')
                ->color($fireAchieved ? 'success' : 'info'),

            Stat::make('Annual Expenses', Number::currency($annualExpenses, 'NOK'))
                ->description('Average yearly expenses')
                ->icon('heroicon-o-minus-circle')
                ->color('warning'),

            Stat::make('Passive Income', Number::currency($annualPassiveIncome, 'NOK'))
                ->description('4% Safe Withdrawal Rate')
                ->icon('heroicon-o-arrow-down-circle')
                ->color('success'),

            Stat::make('Safe Withdrawal Rate', '4%')
                ->description('Common heuristic for sustainable withdrawals')
                ->icon('heroicon-o-scale')
                ->color('info'),

            Stat::make('Expense Coverage', number_format($expenseCoverage, 1).'%')
                ->description($expenseCoverage >= 100 ? 'Expenses fully covered' : 'Partial expense coverage')
                ->icon($expenseCoverage >= 100 ? 'heroicon-o-shield-check' : 'heroicon-o-shield-exclamation')
                ->color($expenseCoverage >= 100 ? 'success' : 'warning'),

            Stat::make('FIRE Age', $fireAchieved ? $currentAge.' years' : ($currentAge + $yearsToFire).' years')
                ->description($fireAchieved ? 'Current age (FIRE achieved)' : 'Projected FIRE age')
                ->icon('heroicon-o-user')
                ->color($fireAchieved ? 'success' : 'info'),
        ];
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}

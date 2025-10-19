<?php

namespace App\Filament\Widgets;

use App\Models\SimulationConfiguration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class FireAnalysisWidget extends BaseWidget
{
    public ?SimulationConfiguration $simulationConfiguration = null;

    public function mount($simulationConfiguration = null): void
    {
        if ($simulationConfiguration) {
            $this->simulationConfiguration = $simulationConfiguration;
        } else {
            // Get simulation_configuration_id from request
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
    }

    protected function getStats(): array
    {
        if (! $this->simulationConfiguration) {
            return [];
        }

        $fireAnalysis = $this->calculateFireAnalysis();

        return [
            Stat::make('FIRE Number', Number::currency($fireAnalysis['fire_number'], 'NOK'))
                ->description('25x annual expenses (4% rule)')
                ->descriptionIcon('heroicon-m-fire')
                ->color('warning'),

            Stat::make('Current Progress', $fireAnalysis['fire_progress'].'%')
                ->description('Progress towards FIRE goal')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($fireAnalysis['fire_progress'] >= 100 ? 'success' : 'info'),

            Stat::make('FIRE Achievement', $fireAnalysis['fire_achieved'] ? 'Achieved' : 'Not Achieved')
                ->description($fireAnalysis['fire_achieved']
                    ? 'FIRE achieved in '.$fireAnalysis['fire_year']
                    : 'Years to FIRE: '.$fireAnalysis['years_to_fire'])
                ->descriptionIcon($fireAnalysis['fire_achieved'] ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($fireAnalysis['fire_achieved'] ? 'success' : 'warning'),

            Stat::make('Safe Withdrawal Rate', $fireAnalysis['safe_withdrawal_rate'].'%')
                ->description('Based on current portfolio')
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('info'),

            Stat::make('Annual Passive Income', Number::currency($fireAnalysis['annual_passive_income'], 'NOK'))
                ->description('4% of FIRE portfolio')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Expense Coverage', $fireAnalysis['expense_coverage'].'%')
                ->description('Passive income vs expenses')
                ->descriptionIcon($fireAnalysis['expense_coverage'] >= 100 ? 'heroicon-m-shield-check' : 'heroicon-m-shield-exclamation')
                ->color($fireAnalysis['expense_coverage'] >= 100 ? 'success' : 'danger'),
        ];
    }

    protected function calculateFireAnalysis(): array
    {
        $simulationAssets = $this->simulationConfiguration->simulationAssets;

        // Calculate annual expenses (average over first few years)
        $annualExpenses = 0;
        $expenseYears = 0;
        $currentPortfolioValue = 0;
        $fireAchieved = false;
        $fireYear = null;
        $yearsToFire = 'Unknown';

        foreach ($simulationAssets as $asset) {
            $assetYears = $asset->simulationAssetYears->sortBy('year');

            if ($assetYears->isNotEmpty()) {
                $firstYear = $assetYears->first();
                $currentPortfolioValue += $firstYear->asset_market_amount ?? 0;

                // Calculate average annual expenses from first 3 years
                foreach ($assetYears->take(3) as $year) {
                    $annualExpenses += $year->expence_amount ?? 0;
                    $expenseYears++;
                }

                // Check for FIRE achievement
                foreach ($assetYears as $year) {
                    $yearPortfolioValue = $year->asset_market_amount ?? 0;
                    $fireNumber = $annualExpenses * 25; // 4% rule

                    if ($yearPortfolioValue >= $fireNumber && ! $fireAchieved) {
                        $fireAchieved = true;
                        $fireYear = $year->year;
                        break;
                    }
                }
            }
        }

        if ($expenseYears > 0) {
            $annualExpenses = $annualExpenses / $expenseYears;
        }

        $fireNumber = $annualExpenses * 25; // 4% rule
        $fireProgress = $fireNumber > 0 ? min(100, round(($currentPortfolioValue / $fireNumber) * 100, 1)) : 0;
        $safeWithdrawalRate = $currentPortfolioValue > 0 ? round(($annualExpenses / $currentPortfolioValue) * 100, 2) : 0;
        $annualPassiveIncome = $currentPortfolioValue * 0.04; // 4% rule
        $expenseCoverage = $annualExpenses > 0 ? round(($annualPassiveIncome / $annualExpenses) * 100, 1) : 0;

        // Calculate years to FIRE if not achieved
        if (! $fireAchieved && $fireNumber > $currentPortfolioValue) {
            $shortfall = $fireNumber - $currentPortfolioValue;
            // Assume 7% annual growth and calculate years needed
            if ($currentPortfolioValue > 0) {
                $yearsToFire = ceil(log($fireNumber / $currentPortfolioValue) / log(1.07));
            }
        }

        return [
            'fire_number' => $fireNumber,
            'fire_progress' => $fireProgress,
            'fire_achieved' => $fireAchieved,
            'fire_year' => $fireYear,
            'years_to_fire' => $yearsToFire,
            'safe_withdrawal_rate' => $safeWithdrawalRate,
            'annual_passive_income' => $annualPassiveIncome,
            'expense_coverage' => $expenseCoverage,
            'annual_expenses' => $annualExpenses,
            'current_portfolio_value' => $currentPortfolioValue,
        ];
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}

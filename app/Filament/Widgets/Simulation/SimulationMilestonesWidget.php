<?php

/*
 * Copyright (C) 2024 Thomas Ekdahl
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace App\Filament\Widgets\Simulation;

use App\Models\SimulationConfiguration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SimulationMilestonesWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    public ?SimulationConfiguration $simulationConfiguration = null;

    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.pages.simulation-dashboard');
    }

    public function mount(?SimulationConfiguration $simulationConfiguration = null): void
    {
        if ($simulationConfiguration) {
            $this->simulationConfiguration = $simulationConfiguration;

            return;
        }

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
                Stat::make('No Data', 'No simulation data available')
                    ->description('Please run a simulation first')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning'),
            ];
        }

        // Calculate milestones
        $fireAchievedYear = $this->calculateFireAchievedYear();
        $debtFreeYear = $this->calculateDebtFreeYear();
        $netWorthMillionYear = $this->calculateNetWorthMillionYear();
        $passiveIncomeYear = $this->calculatePassiveIncomeExceedsExpensesYear();

        $currentYear = now()->year;

        return [
            Stat::make('FIRE Achieved', $this->formatMilestoneYear($fireAchievedYear, $currentYear))
                ->description($fireAchievedYear ? 'Financial Independence achieved' : 'Not yet achieved in simulation')
                ->descriptionIcon($fireAchievedYear ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($fireAchievedYear ? 'success' : 'warning')
                ->icon('heroicon-o-fire'),

            Stat::make('Debt-Free', $this->formatMilestoneYear($debtFreeYear, $currentYear))
                ->description($debtFreeYear ? 'All debts paid off' : 'Not yet achieved in simulation')
                ->descriptionIcon($debtFreeYear ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($debtFreeYear ? 'success' : 'warning')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Net Worth 1M NOK', $this->formatMilestoneYear($netWorthMillionYear, $currentYear))
                ->description($netWorthMillionYear ? 'Millionaire status achieved' : 'Not yet achieved in simulation')
                ->descriptionIcon($netWorthMillionYear ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($netWorthMillionYear ? 'success' : 'warning')
                ->icon('heroicon-o-trophy'),

            Stat::make('Passive Income > Expenses', $this->formatMilestoneYear($passiveIncomeYear, $currentYear))
                ->description($passiveIncomeYear ? 'Income exceeds expenses' : 'Not yet achieved in simulation')
                ->descriptionIcon($passiveIncomeYear ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($passiveIncomeYear ? 'success' : 'warning')
                ->icon('heroicon-o-arrow-trending-up'),
        ];
    }

    protected function calculateFireAchievedYear(): ?int
    {
        $minYear = null;

        foreach ($this->simulationConfiguration->simulationAssets as $asset) {
            foreach ($asset->simulationAssetYears->sortBy('year') as $yearData) {
                if (($yearData->fire_percent ?? 0) >= 100) {
                    if ($minYear === null || $yearData->year < $minYear) {
                        $minYear = $yearData->year;
                    }
                    break; // Found the first year for this asset
                }
            }
        }

        return $minYear;
    }

    protected function calculateDebtFreeYear(): ?int
    {
        // Group by year and sum all debts
        $yearlyDebt = [];

        foreach ($this->simulationConfiguration->simulationAssets as $asset) {
            foreach ($asset->simulationAssetYears as $yearData) {
                $year = $yearData->year;
                if (! isset($yearlyDebt[$year])) {
                    $yearlyDebt[$year] = 0;
                }
                $yearlyDebt[$year] += $yearData->mortgage_balance_amount ?? 0;
            }
        }

        ksort($yearlyDebt);

        foreach ($yearlyDebt as $year => $totalDebt) {
            if ($totalDebt <= 0) {
                return $year;
            }
        }

        return null;
    }

    protected function calculateNetWorthMillionYear(): ?int
    {
        // Group by year and calculate net worth
        $yearlyNetWorth = [];

        foreach ($this->simulationConfiguration->simulationAssets as $asset) {
            foreach ($asset->simulationAssetYears as $yearData) {
                $year = $yearData->year;
                if (! isset($yearlyNetWorth[$year])) {
                    $yearlyNetWorth[$year] = 0;
                }
                $yearlyNetWorth[$year] += ($yearData->asset_market_amount ?? 0) - ($yearData->mortgage_balance_amount ?? 0);
            }
        }

        ksort($yearlyNetWorth);

        foreach ($yearlyNetWorth as $year => $netWorth) {
            if ($netWorth >= 1000000) {
                return $year;
            }
        }

        return null;
    }

    protected function calculatePassiveIncomeExceedsExpensesYear(): ?int
    {
        // Group by year and sum income and expenses
        $yearlyData = [];

        foreach ($this->simulationConfiguration->simulationAssets as $asset) {
            foreach ($asset->simulationAssetYears as $yearData) {
                $year = $yearData->year;
                if (! isset($yearlyData[$year])) {
                    $yearlyData[$year] = ['income' => 0, 'expenses' => 0];
                }
                $yearlyData[$year]['income'] += $yearData->fire_income_amount ?? 0;
                $yearlyData[$year]['expenses'] += $yearData->fire_expence_amount ?? 0;
            }
        }

        ksort($yearlyData);

        foreach ($yearlyData as $year => $data) {
            if ($data['income'] >= $data['expenses'] && $data['expenses'] > 0) {
                return $year;
            }
        }

        return null;
    }

    protected function formatMilestoneYear(?int $year, int $currentYear): string
    {
        if ($year === null) {
            return 'Not achieved';
        }

        if ($year <= $currentYear) {
            return 'Year '.$year.' (Achieved)';
        }

        $yearsUntil = $year - $currentYear;

        return 'Year '.$year.' (in '.$yearsUntil.' years)';
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}

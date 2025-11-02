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
use Illuminate\Support\Number;

class SimulationKeyFiguresWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

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

        // Get current year (first year with data) and final year
        $currentYear = now()->year;
        $finalYear = 0;

        foreach ($simulationAssets as $asset) {
            $years = $asset->simulationAssetYears;
            if ($years->isNotEmpty()) {
                $finalYear = max($finalYear, $years->max('year'));
            }
        }

        // Calculate metrics for current year and final year
        $currentMetrics = $this->calculateYearMetrics($currentYear);
        $finalMetrics = $this->calculateYearMetrics($finalYear);

        return [
            Stat::make('Total Net Worth', Number::currency($currentMetrics['net_worth'], 'NOK'))
                ->description('Current: '.Number::currency($currentMetrics['net_worth'], 'NOK').' → Final: '.Number::currency($finalMetrics['net_worth'], 'NOK'))
                ->descriptionIcon($finalMetrics['net_worth'] >= $currentMetrics['net_worth'] ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($finalMetrics['net_worth'] >= $currentMetrics['net_worth'] ? 'success' : 'danger')
                ->chart($this->getNetWorthTrend()),

            Stat::make('Total Assets', Number::currency($currentMetrics['total_assets'], 'NOK'))
                ->description('Current: '.Number::currency($currentMetrics['total_assets'], 'NOK').' → Final: '.Number::currency($finalMetrics['total_assets'], 'NOK'))
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info')
                ->chart($this->getAssetsTrend()),

            Stat::make('Total Debt', Number::currency($currentMetrics['total_debt'], 'NOK'))
                ->description('Current: '.Number::currency($currentMetrics['total_debt'], 'NOK').' → Final: '.Number::currency($finalMetrics['total_debt'], 'NOK'))
                ->descriptionIcon($finalMetrics['total_debt'] <= $currentMetrics['total_debt'] ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($finalMetrics['total_debt'] <= $currentMetrics['total_debt'] ? 'success' : 'warning')
                ->chart($this->getDebtTrend()),

            Stat::make('Annual Cash Flow (After Tax)', Number::currency($currentMetrics['cashflow'], 'NOK'))
                ->description('Current: '.Number::currency($currentMetrics['cashflow'], 'NOK').' → Final: '.Number::currency($finalMetrics['cashflow'], 'NOK'))
                ->descriptionIcon($currentMetrics['cashflow'] >= 0 ? 'heroicon-m-arrow-up-circle' : 'heroicon-m-arrow-down-circle')
                ->color($currentMetrics['cashflow'] >= 0 ? 'success' : 'danger')
                ->chart($this->getCashflowTrend()),

            Stat::make('FIRE % Achieved', number_format($currentMetrics['fire_percent'], 1).'%')
                ->description('Current: '.number_format($currentMetrics['fire_percent'], 1).'% → Final: '.number_format($finalMetrics['fire_percent'], 1).'%')
                ->descriptionIcon($currentMetrics['fire_percent'] >= 100 ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($currentMetrics['fire_percent'] >= 100 ? 'success' : 'warning')
                ->chart($this->getFirePercentTrend()),

            Stat::make('Average LTV', number_format($currentMetrics['avg_ltv'], 1).'%')
                ->description('Current: '.number_format($currentMetrics['avg_ltv'], 1).'% → Final: '.number_format($finalMetrics['avg_ltv'], 1).'%')
                ->descriptionIcon('heroicon-m-scale')
                ->color($currentMetrics['avg_ltv'] <= 70 ? 'success' : ($currentMetrics['avg_ltv'] <= 85 ? 'warning' : 'danger'))
                ->chart($this->getLtvTrend()),
        ];
    }

    protected function calculateYearMetrics(int $year): array
    {
        $totalAssets = 0;
        $totalDebt = 0;
        $totalCashflow = 0;
        $totalFirePercent = 0;
        $totalLtv = 0;
        $ltvCount = 0;
        $fireCount = 0;

        foreach ($this->simulationConfiguration->simulationAssets as $asset) {
            $yearData = $asset->simulationAssetYears->where('year', $year)->first();

            if ($yearData) {
                $totalAssets += $yearData->asset_market_amount ?? 0;
                $totalDebt += $yearData->mortgage_balance_amount ?? 0;
                $totalCashflow += $yearData->cashflow_after_tax_amount ?? 0;

                if (($yearData->fire_percent ?? 0) > 0) {
                    $totalFirePercent += $yearData->fire_percent;
                    $fireCount++;
                }

                if (($yearData->metrics_ltv_percent ?? 0) > 0) {
                    $totalLtv += $yearData->metrics_ltv_percent;
                    $ltvCount++;
                }
            }
        }

        return [
            'total_assets' => $totalAssets,
            'total_debt' => $totalDebt,
            'net_worth' => $totalAssets - $totalDebt,
            'cashflow' => $totalCashflow,
            'fire_percent' => $fireCount > 0 ? $totalFirePercent / $fireCount : 0,
            'avg_ltv' => $ltvCount > 0 ? $totalLtv / $ltvCount : 0,
        ];
    }

    protected function getNetWorthTrend(): array
    {
        return $this->getTrendData(fn ($yearData) => ($yearData->asset_market_amount ?? 0) - ($yearData->mortgage_balance_amount ?? 0));
    }

    protected function getAssetsTrend(): array
    {
        return $this->getTrendData(fn ($yearData) => $yearData->asset_market_amount ?? 0);
    }

    protected function getDebtTrend(): array
    {
        return $this->getTrendData(fn ($yearData) => $yearData->mortgage_balance_amount ?? 0);
    }

    protected function getCashflowTrend(): array
    {
        return $this->getTrendData(fn ($yearData) => $yearData->cashflow_after_tax_amount ?? 0);
    }

    protected function getFirePercentTrend(): array
    {
        return $this->getTrendData(fn ($yearData) => $yearData->fire_percent ?? 0);
    }

    protected function getLtvTrend(): array
    {
        return $this->getTrendData(fn ($yearData) => $yearData->metrics_ltv_percent ?? 0);
    }

    protected function getTrendData(callable $valueExtractor): array
    {
        $yearlyData = [];

        foreach ($this->simulationConfiguration->simulationAssets as $asset) {
            foreach ($asset->simulationAssetYears as $yearData) {
                $year = $yearData->year;
                if (! isset($yearlyData[$year])) {
                    $yearlyData[$year] = 0;
                }
                $yearlyData[$year] += $valueExtractor($yearData);
            }
        }

        ksort($yearlyData);

        return array_values($yearlyData);
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}

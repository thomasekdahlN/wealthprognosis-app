<?php

/*
 * Copyright (C) 2024 Thomas Ekdahl
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace App\Filament\Widgets\Compare;

use App\Models\SimulationConfiguration;
use Filament\Widgets\Widget;

class CompareKeyOutcomesWidget extends Widget
{
    protected static string $view = 'filament.widgets.compare.compare-key-outcomes-widget';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public ?SimulationConfiguration $simulationA = null;

    public ?SimulationConfiguration $simulationB = null;

    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.pages.compare-dashboard');
    }

    public function mount(?SimulationConfiguration $simulationA = null, ?SimulationConfiguration $simulationB = null): void
    {
        $this->simulationA = $simulationA;
        $this->simulationB = $simulationB;
    }

    protected function getViewData(): array
    {
        if (! $this->simulationA || ! $this->simulationB) {
            return ['outcomes' => []];
        }

        $outcomes = [
            [
                'metric' => 'Final Net Worth',
                'valueA' => $this->getFinalNetWorth($this->simulationA),
                'valueB' => $this->getFinalNetWorth($this->simulationB),
                'format' => 'currency',
            ],
            [
                'metric' => 'Year FIRE Achieved',
                'valueA' => $this->getFireAchievedYear($this->simulationA),
                'valueB' => $this->getFireAchievedYear($this->simulationB),
                'format' => 'year',
            ],
            [
                'metric' => 'Year Debt-Free',
                'valueA' => $this->getDebtFreeYear($this->simulationA),
                'valueB' => $this->getDebtFreeYear($this->simulationB),
                'format' => 'year',
            ],
            [
                'metric' => 'Final Year Cash Flow',
                'valueA' => $this->getFinalCashFlow($this->simulationA),
                'valueB' => $this->getFinalCashFlow($this->simulationB),
                'format' => 'currency_per_year',
            ],
            [
                'metric' => 'Total Taxes Paid',
                'valueA' => $this->getTotalTaxes($this->simulationA),
                'valueB' => $this->getTotalTaxes($this->simulationB),
                'format' => 'currency',
            ],
        ];

        return ['outcomes' => $outcomes];
    }

    protected function getFinalNetWorth(SimulationConfiguration $simulation): ?float
    {
        $maxYear = $simulation->simulationAssets
            ->flatMap->simulationAssetYears
            ->max('year');

        if (! $maxYear) {
            return null;
        }

        $totalAssets = $simulation->simulationAssets
            ->flatMap->simulationAssetYears
            ->where('year', $maxYear)
            ->sum('asset_market_amount');

        $totalDebt = $simulation->simulationAssets
            ->flatMap->simulationAssetYears
            ->where('year', $maxYear)
            ->sum('mortgage_balance_amount');

        return $totalAssets - $totalDebt;
    }

    protected function getFireAchievedYear(SimulationConfiguration $simulation): ?int
    {
        $fireYear = $simulation->simulationAssets
            ->flatMap->simulationAssetYears
            ->where('fire_percent', '>=', 100)
            ->min('year');

        return $fireYear;
    }

    protected function getDebtFreeYear(SimulationConfiguration $simulation): ?int
    {
        $yearlyDebt = [];

        foreach ($simulation->simulationAssets as $asset) {
            foreach ($asset->simulationAssetYears as $yearData) {
                if (! isset($yearlyDebt[$yearData->year])) {
                    $yearlyDebt[$yearData->year] = 0;
                }
                $yearlyDebt[$yearData->year] += $yearData->mortgage_balance_amount ?? 0;
            }
        }

        foreach ($yearlyDebt as $year => $debt) {
            if ($debt <= 0) {
                return $year;
            }
        }

        return null;
    }

    protected function getFinalCashFlow(SimulationConfiguration $simulation): ?float
    {
        $maxYear = $simulation->simulationAssets
            ->flatMap->simulationAssetYears
            ->max('year');

        if (! $maxYear) {
            return null;
        }

        return $simulation->simulationAssets
            ->flatMap->simulationAssetYears
            ->where('year', $maxYear)
            ->sum('cashflow_after_tax_amount');
    }

    protected function getTotalTaxes(SimulationConfiguration $simulation): ?float
    {
        $totalTaxes = 0;

        foreach ($simulation->simulationAssets as $asset) {
            foreach ($asset->simulationAssetYears as $yearData) {
                $totalTaxes += ($yearData->cashflow_tax_amount ?? 0)
                    + ($yearData->asset_tax_amount ?? 0)
                    + ($yearData->asset_tax_property_amount ?? 0)
                    + ($yearData->asset_tax_fortune_amount ?? 0)
                    + ($yearData->realization_tax_amount ?? 0);
            }
        }

        return $totalTaxes;
    }
}

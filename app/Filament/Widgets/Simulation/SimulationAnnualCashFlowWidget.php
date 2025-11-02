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
use Filament\Widgets\ChartWidget;

class SimulationAnnualCashFlowWidget extends ChartWidget
{
    protected ?string $heading = 'Annual Cash Flow (After Tax)';

    protected static bool $isLazy = false;

    protected static ?int $sort = 5;

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

    protected function getData(): array
    {
        if (! $this->simulationConfiguration) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $simulationAssets = $this->simulationConfiguration->simulationAssets;

        if ($simulationAssets->isEmpty()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Collect data by year
        $yearlyData = [];

        foreach ($simulationAssets as $asset) {
            foreach ($asset->simulationAssetYears as $yearData) {
                $year = $yearData->year;
                if (! isset($yearlyData[$year])) {
                    $yearlyData[$year] = 0;
                }
                $yearlyData[$year] += $yearData->cashflow_after_tax_amount ?? 0;
            }
        }

        ksort($yearlyData);

        $labels = array_keys($yearlyData);
        $cashflowData = [];
        $backgroundColors = [];
        $borderColors = [];

        foreach ($yearlyData as $cashflow) {
            $cashflowData[] = round($cashflow, 2);
            // Green for positive, red for negative
            if ($cashflow >= 0) {
                $backgroundColors[] = 'rgba(34, 197, 94, 0.8)';
                $borderColors[] = 'rgb(34, 197, 94)';
            } else {
                $backgroundColors[] = 'rgba(239, 68, 68, 0.8)';
                $borderColors[] = 'rgb(239, 68, 68)';
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Cash Flow After Tax',
                    'data' => $cashflowData,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return new Intl.NumberFormat("nb-NO", { style: "currency", currency: "NOK", minimumFractionDigits: 0 }).format(value); }',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Year',
                    ],
                ],
            ],
        ];
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}

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

class SimulationNetWorthGrowthWidget extends ChartWidget
{
    protected static ?string $heading = 'Net Worth Growth Over Time';

    protected static bool $isLazy = false;

    protected static ?int $sort = 3;

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
                    $yearlyData[$year] = [
                        'equity' => 0,
                        'debt' => 0,
                        'assets' => 0,
                    ];
                }
                $yearlyData[$year]['equity'] += $yearData->asset_equity_amount ?? 0;
                $yearlyData[$year]['debt'] += $yearData->mortgage_balance_amount ?? 0;
                $yearlyData[$year]['assets'] += $yearData->asset_market_amount ?? 0;
            }
        }

        ksort($yearlyData);

        $labels = array_keys($yearlyData);
        $equityData = [];
        $debtData = [];
        $assetsData = [];

        foreach ($yearlyData as $data) {
            $equityData[] = round($data['equity'], 2);
            $debtData[] = round($data['debt'], 2);
            $assetsData[] = round($data['assets'], 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Equity',
                    'data' => $equityData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Total Debt',
                    'data' => $debtData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Total Asset Value',
                    'data' => $assetsData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 3,
                    'fill' => false,
                    'tension' => 0.4,
                    'type' => 'line',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
                    'mode' => 'index',
                    'intersect' => false,
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
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}

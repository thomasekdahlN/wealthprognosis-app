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

class SimulationFireProgressionWidget extends ChartWidget
{
    protected ?string $heading = 'FIRE Progression Over Time';

    protected static bool $isLazy = false;

    protected static ?int $sort = 6;

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
                        'fire_percent' => 0,
                        'count' => 0,
                    ];
                }
                if (($yearData->fire_percent ?? 0) > 0) {
                    $yearlyData[$year]['fire_percent'] += $yearData->fire_percent;
                    $yearlyData[$year]['count']++;
                }
            }
        }

        ksort($yearlyData);

        $labels = array_keys($yearlyData);
        $firePercentData = [];
        $goalLineData = [];

        foreach ($yearlyData as $data) {
            $avgFirePercent = $data['count'] > 0 ? $data['fire_percent'] / $data['count'] : 0;
            $firePercentData[] = round($avgFirePercent, 2);
            $goalLineData[] = 100; // Goal line at 100%
        }

        return [
            'datasets' => [
                [
                    'label' => 'FIRE %',
                    'data' => $firePercentData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Goal (100%)',
                    'data' => $goalLineData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'borderDash' => [5, 5],
                    'fill' => false,
                    'pointRadius' => 0,
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
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": " + context.parsed.y.toFixed(1) + "%"; }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return value + "%"; }',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'FIRE Percentage',
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

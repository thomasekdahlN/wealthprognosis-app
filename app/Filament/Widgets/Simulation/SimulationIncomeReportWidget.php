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

class SimulationIncomeReportWidget extends ChartWidget
{
    protected ?string $heading = 'Income Report by Year';

    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public ?SimulationConfiguration $simulationConfiguration = null;

    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.pages.simulation-detailed-reporting-dashboard');
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

        // Collect income data by year and asset
        $yearlyData = [];
        $assetNames = [];

        foreach ($simulationAssets as $asset) {
            $assetName = $asset->name ?? 'Unknown Asset';
            $assetNames[$asset->id] = $assetName;

            foreach ($asset->simulationAssetYears as $yearData) {
                $year = $yearData->year;
                if (! isset($yearlyData[$year])) {
                    $yearlyData[$year] = [];
                }
                if (! isset($yearlyData[$year][$asset->id])) {
                    $yearlyData[$year][$asset->id] = 0;
                }
                $yearlyData[$year][$asset->id] += $yearData->income_amount ?? 0;
            }
        }

        ksort($yearlyData);

        $labels = array_keys($yearlyData);
        $datasets = [];

        // Create a dataset for each asset
        $colors = $this->generateColors(count($assetNames));
        $colorIndex = 0;

        foreach ($assetNames as $assetId => $assetName) {
            $data = [];
            foreach ($yearlyData as $yearAssets) {
                $data[] = round($yearAssets[$assetId] ?? 0, 2);
            }

            $datasets[] = [
                'label' => $assetName,
                'data' => $data,
                'backgroundColor' => $colors[$colorIndex],
                'borderColor' => str_replace('0.8', '1', $colors[$colorIndex]),
                'borderWidth' => 2,
            ];
            $colorIndex++;
        }

        return [
            'datasets' => $datasets,
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
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Year',
                    ],
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return new Intl.NumberFormat("nb-NO", { style: "currency", currency: "NOK", minimumFractionDigits: 0 }).format(value); }',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Income Amount',
                    ],
                ],
            ],
        ];
    }

    protected function generateColors(int $count): array
    {
        $baseColors = [
            'rgba(34, 197, 94, 0.8)',    // Green
            'rgba(59, 130, 246, 0.8)',   // Blue
            'rgba(251, 146, 60, 0.8)',   // Orange
            'rgba(168, 85, 247, 0.8)',   // Purple
            'rgba(236, 72, 153, 0.8)',   // Pink
            'rgba(14, 165, 233, 0.8)',   // Sky
            'rgba(132, 204, 22, 0.8)',   // Lime
            'rgba(234, 179, 8, 0.8)',    // Yellow
            'rgba(99, 102, 241, 0.8)',   // Indigo
            'rgba(16, 185, 129, 0.8)',   // Emerald
        ];

        $colors = [];
        for ($i = 0; $i < $count; $i++) {
            $colors[] = $baseColors[$i % count($baseColors)];
        }

        return $colors;
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}

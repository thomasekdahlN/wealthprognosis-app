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

class SimulationAssetAllocationWidget extends ChartWidget
{
    protected ?string $heading = 'Asset Allocation (Current Year)';

    protected static bool $isLazy = false;

    protected static ?int $sort = 7;

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
                'simulationAssets.asset.assetType',
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

        // Get current year
        $currentYear = now()->year;

        // Collect data by asset type for current year
        $assetTypeData = [];

        foreach ($simulationAssets as $simulationAsset) {
            $yearData = $simulationAsset->simulationAssetYears->where('year', $currentYear)->first();

            if ($yearData && ($yearData->asset_market_amount ?? 0) > 0) {
                $assetType = $simulationAsset->asset?->assetType?->type ?? 'Unknown';

                if (! isset($assetTypeData[$assetType])) {
                    $assetTypeData[$assetType] = 0;
                }

                $assetTypeData[$assetType] += $yearData->asset_market_amount;
            }
        }

        // Sort by value descending
        arsort($assetTypeData);

        $labels = array_keys($assetTypeData);
        $data = array_values($assetTypeData);

        // Generate colors
        $colors = $this->generateColors(count($labels));

        return [
            'datasets' => [
                [
                    'label' => 'Asset Value',
                    'data' => array_map(fn ($value) => round($value, 2), $data),
                    'backgroundColor' => $colors,
                    'borderColor' => array_map(fn ($color) => str_replace('0.8', '1', $color), $colors),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                ],
                'tooltip' => [
                    'enabled' => true,
                    'callbacks' => [
                        'label' => 'function(context) { 
                            var label = context.label || "";
                            var value = context.parsed || 0;
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var percentage = ((value / total) * 100).toFixed(1);
                            var formattedValue = new Intl.NumberFormat("nb-NO", { style: "currency", currency: "NOK", minimumFractionDigits: 0 }).format(value);
                            return label + ": " + formattedValue + " (" + percentage + "%)";
                        }',
                    ],
                ],
            ],
        ];
    }

    protected function generateColors(int $count): array
    {
        $baseColors = [
            'rgba(59, 130, 246, 0.8)',   // Blue
            'rgba(34, 197, 94, 0.8)',    // Green
            'rgba(251, 146, 60, 0.8)',   // Orange
            'rgba(168, 85, 247, 0.8)',   // Purple
            'rgba(236, 72, 153, 0.8)',   // Pink
            'rgba(14, 165, 233, 0.8)',   // Sky
            'rgba(132, 204, 22, 0.8)',   // Lime
            'rgba(234, 179, 8, 0.8)',    // Yellow
            'rgba(239, 68, 68, 0.8)',    // Red
            'rgba(99, 102, 241, 0.8)',   // Indigo
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

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

class SimulationDebtAllocationWidget extends ChartWidget
{
    protected ?string $heading = 'Debt Allocation by Asset (Current Year)';

    protected static bool $isLazy = false;

    protected static ?int $sort = 8;

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

        // Get current year
        $currentYear = now()->year;

        // Collect data by asset for current year
        $assetDebtData = [];

        foreach ($simulationAssets as $simulationAsset) {
            $yearData = $simulationAsset->simulationAssetYears->where('year', $currentYear)->first();

            if ($yearData && ($yearData->mortgage_balance_amount ?? 0) > 0) {
                $assetName = $simulationAsset->name ?? 'Unknown Asset';

                if (! isset($assetDebtData[$assetName])) {
                    $assetDebtData[$assetName] = 0;
                }

                $assetDebtData[$assetName] += $yearData->mortgage_balance_amount;
            }
        }

        // If no debt, show a message
        if (empty($assetDebtData)) {
            return [
                'datasets' => [
                    [
                        'label' => 'No Debt',
                        'data' => [1],
                        'backgroundColor' => ['rgba(34, 197, 94, 0.8)'],
                        'borderColor' => ['rgb(34, 197, 94)'],
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => ['Debt Free'],
            ];
        }

        // Sort by value descending
        arsort($assetDebtData);

        $labels = array_keys($assetDebtData);
        $data = array_values($assetDebtData);

        // Generate colors (red shades for debt)
        $colors = $this->generateDebtColors(count($labels));

        return [
            'datasets' => [
                [
                    'label' => 'Debt Amount',
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

    protected function generateDebtColors(int $count): array
    {
        $baseColors = [
            'rgba(239, 68, 68, 0.8)',    // Red
            'rgba(220, 38, 38, 0.8)',    // Dark Red
            'rgba(248, 113, 113, 0.8)',  // Light Red
            'rgba(252, 165, 165, 0.8)',  // Lighter Red
            'rgba(254, 202, 202, 0.8)',  // Very Light Red
            'rgba(251, 146, 60, 0.8)',   // Orange
            'rgba(234, 88, 12, 0.8)',    // Dark Orange
            'rgba(253, 186, 116, 0.8)',  // Light Orange
            'rgba(254, 215, 170, 0.8)',  // Lighter Orange
            'rgba(255, 237, 213, 0.8)',  // Very Light Orange
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

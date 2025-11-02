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
use Filament\Widgets\ChartWidget;

class CompareRiskMetricsWidget extends ChartWidget
{
    protected static ?string $heading = 'Portfolio Risk Metrics Comparison (Average LTV)';

    protected static ?int $sort = 7;

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

    protected function getData(): array
    {
        if (! $this->simulationA || ! $this->simulationB) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Get all years from both simulations
        $yearsA = $this->simulationA->simulationAssets
            ->flatMap->simulationAssetYears
            ->pluck('year')
            ->unique()
            ->sort()
            ->values();

        $yearsB = $this->simulationB->simulationAssets
            ->flatMap->simulationAssetYears
            ->pluck('year')
            ->unique()
            ->sort()
            ->values();

        $allYears = $yearsA->merge($yearsB)->unique()->sort()->values()->toArray();

        // Calculate average LTV for each year for both simulations
        $avgLtvA = [];
        $avgLtvB = [];

        foreach ($allYears as $year) {
            $yearDataA = $this->simulationA->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year);

            $avgA = $yearDataA->avg('metrics_ltv_percent');
            $avgLtvA[] = $avgA ? round($avgA, 2) : 0;

            $yearDataB = $this->simulationB->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year);

            $avgB = $yearDataB->avg('metrics_ltv_percent');
            $avgLtvB[] = $avgB ? round($avgB, 2) : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => "Simulation A: {$this->simulationA->name}",
                    'data' => $avgLtvA,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderWidth' => 3,
                    'tension' => 0.3,
                    'fill' => false,
                ],
                [
                    'label' => "Simulation B: {$this->simulationB->name}",
                    'data' => $avgLtvB,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderWidth' => 3,
                    'tension' => 0.3,
                    'fill' => false,
                ],
            ],
            'labels' => $allYears,
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
                'annotation' => [
                    'annotations' => [
                        [
                            'type' => 'line',
                            'yMin' => 70,
                            'yMax' => 70,
                            'borderColor' => 'rgb(234, 179, 8)',
                            'borderWidth' => 2,
                            'borderDash' => [5, 5],
                            'label' => [
                                'content' => 'Safe Threshold (70%)',
                                'enabled' => true,
                                'position' => 'end',
                            ],
                        ],
                        [
                            'type' => 'line',
                            'yMin' => 85,
                            'yMax' => 85,
                            'borderColor' => 'rgb(239, 68, 68)',
                            'borderWidth' => 2,
                            'borderDash' => [5, 5],
                            'label' => [
                                'content' => 'High Risk (85%)',
                                'enabled' => true,
                                'position' => 'end',
                            ],
                        ],
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Year',
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'ticks' => [
                        'callback' => 'function(value) { return value + "%"; }',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Average LTV (%)',
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
        ];
    }
}

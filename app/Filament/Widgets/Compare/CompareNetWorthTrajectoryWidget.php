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

class CompareNetWorthTrajectoryWidget extends ChartWidget
{
    protected ?string $heading = 'Net Worth Trajectory Comparison';

    protected static ?int $sort = 3;

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

        // Calculate net worth for each year for both simulations
        $netWorthA = [];
        $netWorthB = [];

        foreach ($allYears as $year) {
            $assetsA = $this->simulationA->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year)
                ->sum('asset_market_amount');

            $debtA = $this->simulationA->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year)
                ->sum('mortgage_balance_amount');

            $netWorthA[] = round($assetsA - $debtA, 2);

            $assetsB = $this->simulationB->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year)
                ->sum('asset_market_amount');

            $debtB = $this->simulationB->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year)
                ->sum('mortgage_balance_amount');

            $netWorthB[] = round($assetsB - $debtB, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => "Simulation A: {$this->simulationA->name}",
                    'data' => $netWorthA,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderWidth' => 3,
                    'tension' => 0.3,
                    'fill' => false,
                ],
                [
                    'label' => "Simulation B: {$this->simulationB->name}",
                    'data' => $netWorthB,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
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
                        'label' => 'function(context) { return context.dataset.label + ": " + new Intl.NumberFormat("nb-NO", { style: "currency", currency: "NOK", minimumFractionDigits: 0 }).format(context.parsed.y); }',
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
                    'beginAtZero' => false,
                    'ticks' => [
                        'callback' => 'function(value) { return new Intl.NumberFormat("nb-NO", { style: "currency", currency: "NOK", minimumFractionDigits: 0 }).format(value); }',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Net Worth',
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

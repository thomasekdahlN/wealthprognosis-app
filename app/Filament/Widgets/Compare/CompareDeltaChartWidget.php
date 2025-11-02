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

class CompareDeltaChartWidget extends ChartWidget
{
    protected ?string $heading = 'Net Worth Delta (Simulation B - Simulation A)';

    protected static ?int $sort = 5;

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

        // Calculate delta for each year
        $deltas = [];
        $colors = [];

        foreach ($allYears as $year) {
            $assetsA = $this->simulationA->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year)
                ->sum('asset_market_amount');

            $debtA = $this->simulationA->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year)
                ->sum('mortgage_balance_amount');

            $netWorthA = $assetsA - $debtA;

            $assetsB = $this->simulationB->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year)
                ->sum('asset_market_amount');

            $debtB = $this->simulationB->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year)
                ->sum('mortgage_balance_amount');

            $netWorthB = $assetsB - $debtB;

            $delta = $netWorthB - $netWorthA;
            $deltas[] = round($delta, 2);

            // Color bars: green for positive, red for negative
            $colors[] = $delta >= 0 ? 'rgba(34, 197, 94, 0.8)' : 'rgba(239, 68, 68, 0.8)';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Net Worth Difference (B - A)',
                    'data' => $deltas,
                    'backgroundColor' => $colors,
                    'borderColor' => array_map(fn ($color) => str_replace('0.8', '1', $color), $colors),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $allYears,
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
                    'ticks' => [
                        'callback' => 'function(value) { return new Intl.NumberFormat("nb-NO", { style: "currency", currency: "NOK", minimumFractionDigits: 0 }).format(value); }',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Net Worth Delta',
                    ],
                ],
            ],
        ];
    }
}

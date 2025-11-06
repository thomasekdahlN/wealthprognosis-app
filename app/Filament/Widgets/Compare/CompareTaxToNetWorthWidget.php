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

class CompareTaxToNetWorthWidget extends ChartWidget
{
    protected ?string $heading = 'Tax as % of Net Worth Comparison';

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 1;

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

        // Calculate tax as % of net worth for each year for both simulations
        $taxPercentA = [];
        $taxPercentB = [];

        foreach ($allYears as $year) {
            $yearDataA = $this->simulationA->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year);

            $totalAssetsA = $yearDataA->sum('asset_market_amount');
            $totalDebtA = $yearDataA->sum('mortgage_balance_amount');
            $netWorthA = $totalAssetsA - $totalDebtA;

            $totalTaxA = $yearDataA->sum(function ($yearData) {
                return ($yearData->cashflow_tax_amount ?? 0)
                    + ($yearData->asset_tax_amount ?? 0)
                    + ($yearData->asset_tax_property_amount ?? 0)
                    + ($yearData->asset_tax_fortune_amount ?? 0)
                    + ($yearData->realization_tax_amount ?? 0);
            });

            $percentA = $netWorthA > 0 ? ($totalTaxA / $netWorthA) * 100 : 0;
            $taxPercentA[] = round($percentA, 2);

            $yearDataB = $this->simulationB->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year);

            $totalAssetsB = $yearDataB->sum('asset_market_amount');
            $totalDebtB = $yearDataB->sum('mortgage_balance_amount');
            $netWorthB = $totalAssetsB - $totalDebtB;

            $totalTaxB = $yearDataB->sum(function ($yearData) {
                return ($yearData->cashflow_tax_amount ?? 0)
                    + ($yearData->asset_tax_amount ?? 0)
                    + ($yearData->asset_tax_property_amount ?? 0)
                    + ($yearData->asset_tax_fortune_amount ?? 0)
                    + ($yearData->realization_tax_amount ?? 0);
            });

            $percentB = $netWorthB > 0 ? ($totalTaxB / $netWorthB) * 100 : 0;
            $taxPercentB[] = round($percentB, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => "Simulation A: {$this->simulationA->name}",
                    'data' => $taxPercentA,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderWidth' => 3,
                    'tension' => 0.3,
                    'fill' => true,
                ],
                [
                    'label' => "Simulation B: {$this->simulationB->name}",
                    'data' => $taxPercentB,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderWidth' => 3,
                    'tension' => 0.3,
                    'fill' => true,
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
                        'label' => 'function(context) { return context.dataset.label + ": " + context.parsed.y.toFixed(2) + "%"; }',
                    ],
                ],
                'annotation' => [
                    'annotations' => [
                        [
                            'type' => 'line',
                            'yMin' => 1,
                            'yMax' => 1,
                            'borderColor' => 'rgb(34, 197, 94)',
                            'borderWidth' => 2,
                            'borderDash' => [5, 5],
                            'label' => [
                                'content' => 'Wealth Tax (~1%)',
                                'enabled' => true,
                                'position' => 'end',
                            ],
                        ],
                        [
                            'type' => 'line',
                            'yMin' => 2,
                            'yMax' => 2,
                            'borderColor' => 'rgb(234, 179, 8)',
                            'borderWidth' => 2,
                            'borderDash' => [5, 5],
                            'label' => [
                                'content' => 'High Wealth Tax (~2%)',
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
                    'max' => 5,
                    'ticks' => [
                        'callback' => 'function(value) { return value + "%"; }',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Tax as % of Net Worth',
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


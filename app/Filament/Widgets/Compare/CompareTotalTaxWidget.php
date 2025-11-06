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
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class CompareTotalTaxWidget extends ChartWidget
{
    protected ?string $heading = 'Total Tax Paid Comparison';

    protected static ?int $sort = 16;

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

        // Get all years from both simulations, starting from previous year
        $startYear = now()->year - 1;

        $yearsA = $this->simulationA->simulationAssets
            ->flatMap->simulationAssetYears
            ->pluck('year')
            ->filter(fn ($year) => $year >= $startYear)
            ->unique()
            ->sort()
            ->values();

        $yearsB = $this->simulationB->simulationAssets
            ->flatMap->simulationAssetYears
            ->pluck('year')
            ->filter(fn ($year) => $year >= $startYear)
            ->unique()
            ->sort()
            ->values();

        $allYears = $yearsA->merge($yearsB)->unique()->sort()->values()->toArray();

        // Calculate total tax for each year for both simulations
        $totalTaxA = [];
        $totalTaxB = [];

        foreach ($allYears as $year) {
            $yearDataA = $this->simulationA->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year);

            $taxA = $yearDataA->sum(function ($yearData) {
                return ($yearData->cashflow_tax_amount ?? 0)
                    + ($yearData->asset_tax_amount ?? 0)
                    + ($yearData->asset_tax_property_amount ?? 0)
                    + ($yearData->asset_tax_fortune_amount ?? 0)
                    + ($yearData->realization_tax_amount ?? 0);
            });
            $totalTaxA[] = round($taxA, 0);

            $yearDataB = $this->simulationB->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year);

            $taxB = $yearDataB->sum(function ($yearData) {
                return ($yearData->cashflow_tax_amount ?? 0)
                    + ($yearData->asset_tax_amount ?? 0)
                    + ($yearData->asset_tax_property_amount ?? 0)
                    + ($yearData->asset_tax_fortune_amount ?? 0)
                    + ($yearData->realization_tax_amount ?? 0);
            });
            $totalTaxB[] = round($taxB, 0);
        }

        return [
            'datasets' => [
                [
                    'label' => "Simulation A: {$this->simulationA->name}",
                    'data' => $totalTaxA,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => "Simulation B: {$this->simulationB->name}",
                    'data' => $totalTaxB,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
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

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        enabled: true,
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('nb-NO', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    }).format(context.parsed.y) + ' kr';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Year'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Total Tax Paid (kr)'
                        },
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('nb-NO', {
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0
                                }).format(value) + ' kr';
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                }
            }
        JS);
    }
}

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

class CompareAnnualExpensesWidget extends ChartWidget
{
    protected static ?int $sort = 13;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = 'Annual Expenses Comparison';

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

        // Calculate expenses for each year for both simulations
        $expensesA = [];
        $expensesB = [];

        foreach ($allYears as $year) {
            $expA = $this->simulationA->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year)
                ->sum('expence_amount');

            $expensesA[] = round($expA, 2);

            $expB = $this->simulationB->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year)
                ->sum('expence_amount');

            $expensesB[] = round($expB, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => "Simulation A: {$this->simulationA->name}",
                    'data' => $expensesA,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderWidth' => 3,
                    'tension' => 0.3,
                    'fill' => false,
                ],
                [
                    'label' => "Simulation B: {$this->simulationB->name}",
                    'data' => $expensesB,
                    'borderColor' => 'rgb(251, 146, 60)',
                    'backgroundColor' => 'rgba(251, 146, 60, 0.1)',
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
                                return context.dataset.label + ': ' + new Intl.NumberFormat('nb-NO', {
                                    style: 'currency',
                                    currency: 'NOK',
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0
                                }).format(context.parsed.y);
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
                            text: 'Annual Expenses'
                        },
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('nb-NO', {
                                    style: 'currency',
                                    currency: 'NOK',
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0
                                }).format(value);
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

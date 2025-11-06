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

class CompareFireAchievementWidget extends ChartWidget
{
    protected static ?int $sort = 15;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = 'FIRE Progress Comparison';

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

        // Calculate FIRE percentage for each year for both simulations
        $firePercentA = [];
        $firePercentB = [];

        foreach ($allYears as $year) {
            // For Simulation A
            $yearDataA = $this->simulationA->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year);

            $avgFireA = $yearDataA->avg('fire_percent') ?? 0;
            $firePercentA[] = round(min(100, $avgFireA), 2);

            // For Simulation B
            $yearDataB = $this->simulationB->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year);

            $avgFireB = $yearDataB->avg('fire_percent') ?? 0;
            $firePercentB[] = round(min(100, $avgFireB), 2);
        }

        return [
            'datasets' => [
                [
                    'label' => "Simulation A: {$this->simulationA->name}",
                    'data' => $firePercentA,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderWidth' => 3,
                    'tension' => 0.3,
                    'fill' => false,
                ],
                [
                    'label' => "Simulation B: {$this->simulationB->name}",
                    'data' => $firePercentB,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderWidth' => 3,
                    'tension' => 0.3,
                    'fill' => false,
                ],
                [
                    'label' => 'FIRE Target (100%)',
                    'data' => array_fill(0, count($allYears), 100),
                    'borderColor' => 'rgb(234, 179, 8)',
                    'backgroundColor' => 'rgba(234, 179, 8, 0.05)',
                    'borderWidth' => 2,
                    'borderDash' => [5, 5],
                    'tension' => 0,
                    'fill' => false,
                    'pointRadius' => 0,
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
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('nb-NO', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    }).format(context.parsed.y) + '%';
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
                        max: 120,
                        title: {
                            display: true,
                            text: 'FIRE Progress (%)'
                        },
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('nb-NO', {
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0
                                }).format(value) + '%';
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

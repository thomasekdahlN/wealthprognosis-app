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

class CompareNetWorthTrajectoryWidget extends ChartWidget
{
    protected ?string $heading = 'Net Worth Trajectory (Before & After Realization Tax)';

    protected static ?int $sort = 13;

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

        // Calculate net worth for each year for both simulations
        $netWorthA = [];
        $netWorthB = [];
        $netWorthAfterTaxA = [];
        $netWorthAfterTaxB = [];

        foreach ($allYears as $year) {
            $yearDataA = $this->simulationA->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year);

            $assetsA = $yearDataA->sum('asset_market_amount');
            $debtA = $yearDataA->sum('mortgage_balance_amount');
            $realizationTaxA = $yearDataA->sum('realization_tax_amount');

            $netWorthA[] = round($assetsA - $debtA, 2);
            $netWorthAfterTaxA[] = round($assetsA - $debtA - $realizationTaxA, 2);

            $yearDataB = $this->simulationB->simulationAssets
                ->flatMap->simulationAssetYears
                ->where('year', $year);

            $assetsB = $yearDataB->sum('asset_market_amount');
            $debtB = $yearDataB->sum('mortgage_balance_amount');
            $realizationTaxB = $yearDataB->sum('realization_tax_amount');

            $netWorthB[] = round($assetsB - $debtB, 2);
            $netWorthAfterTaxB[] = round($assetsB - $debtB - $realizationTaxB, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => "A: {$this->simulationA->name} (Before Tax)",
                    'data' => $netWorthA,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderWidth' => 2,
                    'borderDash' => [5, 5],
                    'tension' => 0.3,
                    'fill' => false,
                ],
                [
                    'label' => "A: {$this->simulationA->name} (After Tax)",
                    'data' => $netWorthAfterTaxA,
                    'borderColor' => 'rgb(37, 99, 235)',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.1)',
                    'borderWidth' => 3,
                    'tension' => 0.3,
                    'fill' => false,
                ],
                [
                    'label' => "B: {$this->simulationB->name} (Before Tax)",
                    'data' => $netWorthB,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderWidth' => 2,
                    'borderDash' => [5, 5],
                    'tension' => 0.3,
                    'fill' => false,
                ],
                [
                    'label' => "B: {$this->simulationB->name} (After Tax)",
                    'data' => $netWorthAfterTaxB,
                    'borderColor' => 'rgb(22, 163, 74)',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.1)',
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
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('nb-NO', {
                                        style: 'currency',
                                        currency: 'NOK',
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    }).format(context.parsed.y);
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
                        beginAtZero: false,
                        title: {
                            display: true,
                            text: 'Net Worth'
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

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

class SimulationTaxReportWidget extends ChartWidget
{
    protected static ?string $heading = 'Total Tax Breakdown by Year';

    protected static bool $isLazy = false;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public ?SimulationConfiguration $simulationConfiguration = null;

    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.pages.simulation-detailed-reporting-dashboard');
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

        // Collect tax data by year
        $yearlyData = [];

        foreach ($simulationAssets as $asset) {
            foreach ($asset->simulationAssetYears as $yearData) {
                $year = $yearData->year;
                if (! isset($yearlyData[$year])) {
                    $yearlyData[$year] = [
                        'cashflow_tax' => 0,
                        'asset_tax' => 0,
                        'property_tax' => 0,
                        'fortune_tax' => 0,
                        'realization_tax' => 0,
                        'deductions' => 0,
                    ];
                }
                $yearlyData[$year]['cashflow_tax'] += $yearData->cashflow_tax_amount ?? 0;
                $yearlyData[$year]['asset_tax'] += $yearData->asset_tax_amount ?? 0;
                $yearlyData[$year]['property_tax'] += $yearData->asset_tax_property_amount ?? 0;
                $yearlyData[$year]['fortune_tax'] += $yearData->asset_tax_fortune_amount ?? 0;
                $yearlyData[$year]['realization_tax'] += $yearData->realization_tax_amount ?? 0;
                $yearlyData[$year]['deductions'] += $yearData->mortgage_tax_deductable_amount ?? 0;
            }
        }

        ksort($yearlyData);

        $labels = array_keys($yearlyData);
        $cashflowTaxData = [];
        $assetTaxData = [];
        $propertyTaxData = [];
        $fortuneTaxData = [];
        $realizationTaxData = [];
        $deductionsData = [];

        foreach ($yearlyData as $data) {
            $cashflowTaxData[] = round($data['cashflow_tax'], 2);
            $assetTaxData[] = round($data['asset_tax'], 2);
            $propertyTaxData[] = round($data['property_tax'], 2);
            $fortuneTaxData[] = round($data['fortune_tax'], 2);
            $realizationTaxData[] = round($data['realization_tax'], 2);
            $deductionsData[] = round(-abs($data['deductions']), 2); // Negative for deductions
        }

        return [
            'datasets' => [
                [
                    'label' => 'Cashflow Tax',
                    'data' => $cashflowTaxData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Asset Tax',
                    'data' => $assetTaxData,
                    'backgroundColor' => 'rgba(251, 146, 60, 0.8)',
                    'borderColor' => 'rgb(251, 146, 60)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Property Tax',
                    'data' => $propertyTaxData,
                    'backgroundColor' => 'rgba(234, 179, 8, 0.8)',
                    'borderColor' => 'rgb(234, 179, 8)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Fortune Tax',
                    'data' => $fortuneTaxData,
                    'backgroundColor' => 'rgba(220, 38, 38, 0.8)',
                    'borderColor' => 'rgb(220, 38, 38)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Realization Tax',
                    'data' => $realizationTaxData,
                    'backgroundColor' => 'rgba(234, 88, 12, 0.8)',
                    'borderColor' => 'rgb(234, 88, 12)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Tax Deductions',
                    'data' => $deductionsData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
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
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Year',
                    ],
                ],
                'y' => [
                    'stacked' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return new Intl.NumberFormat("nb-NO", { style: "currency", currency: "NOK", minimumFractionDigits: 0 }).format(value); }',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Tax Amount',
                    ],
                ],
            ],
        ];
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}

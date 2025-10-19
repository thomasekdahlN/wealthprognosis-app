<?php

namespace App\Filament\Widgets;

use App\Models\SimulationConfiguration;
use Filament\Widgets\ChartWidget;

class AssetAllocationOverTimeChart extends ChartWidget
{
    protected ?string $heading = 'Asset Allocation Over Time';

    protected static ?int $sort = 5;

    public ?SimulationConfiguration $simulationConfiguration = null;

    public function mount($simulationConfiguration = null): void
    {
        if ($simulationConfiguration) {
            $this->simulationConfiguration = $simulationConfiguration;
        } else {
            // Get simulation_configuration_id from request
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
    }

    protected function getData(): array
    {
        if (! $this->simulationConfiguration) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $data = $this->calculateAssetAllocationOverTime();

        $colors = [
            'cash' => 'rgb(156, 163, 175)',
            'equity' => 'rgb(59, 130, 246)',
            'real_estate' => 'rgb(34, 197, 94)',
            'bond' => 'rgb(168, 85, 247)',
            'crypto' => 'rgb(251, 191, 36)',
            'commodity' => 'rgb(239, 68, 68)',
            'other' => 'rgb(107, 114, 128)',
        ];

        $datasets = [];
        foreach ($data['asset_types'] as $assetType) {
            $datasets[] = [
                'label' => ucfirst(str_replace('_', ' ', $assetType)),
                'data' => $data['allocations'][$assetType] ?? [],
                'borderColor' => $colors[$assetType] ?? 'rgb(107, 114, 128)',
                'backgroundColor' => str_replace('rgb', 'rgba', str_replace(')', ', 0.8)', $colors[$assetType] ?? 'rgb(107, 114, 128)')),
                'fill' => true,
                'tension' => 0.4,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $data['years'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'ticks' => [
                        'callback' => 'function(value) { return value + "%"; }',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Allocation (%)',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Year',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": " + context.parsed.y.toFixed(1) + "%"; }',
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }

    protected function calculateAssetAllocationOverTime(): array
    {
        $simulationAssets = $this->simulationConfiguration->simulationAssets;
        $yearlyData = [];
        $assetTypes = [];

        // Collect all years and asset types
        foreach ($simulationAssets as $asset) {
            $assetType = $asset->asset_type;
            if (! in_array($assetType, $assetTypes)) {
                $assetTypes[] = $assetType;
            }

            foreach ($asset->simulationAssetYears as $assetYear) {
                $year = $assetYear->year;

                if (! isset($yearlyData[$year])) {
                    $yearlyData[$year] = [
                        'total' => 0,
                        'by_type' => [],
                    ];
                }

                if (! isset($yearlyData[$year]['by_type'][$assetType])) {
                    $yearlyData[$year]['by_type'][$assetType] = 0;
                }

                $assetValue = $assetYear->asset_market_amount ?? 0;
                $yearlyData[$year]['total'] += $assetValue;
                $yearlyData[$year]['by_type'][$assetType] += $assetValue;
            }
        }

        ksort($yearlyData);
        sort($assetTypes);

        $years = [];
        $allocations = [];

        // Initialize allocations array
        foreach ($assetTypes as $assetType) {
            $allocations[$assetType] = [];
        }

        foreach ($yearlyData as $year => $data) {
            $years[] = $year;
            $total = $data['total'];

            foreach ($assetTypes as $assetType) {
                $value = $data['by_type'][$assetType] ?? 0;
                $percentage = $total > 0 ? ($value / $total) * 100 : 0;
                $allocations[$assetType][] = round($percentage, 2);
            }
        }

        return [
            'years' => $years,
            'asset_types' => $assetTypes,
            'allocations' => $allocations,
        ];
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\SimulationConfiguration;
use Filament\Widgets\ChartWidget;

class SimulationAssetAllocationChartWidget extends ChartWidget
{
    protected ?string $heading = 'Asset Allocation Over Time';
    protected static ?int $sort = 6;

    public ?SimulationConfiguration $simulationConfiguration = null;

    public function mount(): void
    {
        // Get simulation_configuration_id from request
        $simulationConfigurationId = request()->get('simulation_configuration_id');

        if ($simulationConfigurationId) {
            $this->simulationConfiguration = SimulationConfiguration::with([
                'assetConfiguration',
                'simulationAssets.simulationAssetYears'
            ])
            ->where('user_id', auth()->id())
            ->find($simulationConfigurationId);
        }
    }

    protected function getData(): array
    {
        if (!$this->simulationConfiguration) {
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

        // Get the latest year data for current allocation
        $latestYear = 0;
        $assetAllocation = [];

        foreach ($simulationAssets as $asset) {
            $lastYearData = $asset->simulationAssetYears->last();
            if ($lastYearData) {
                $latestYear = max($latestYear, $lastYearData->year);
            }
        }

        // Collect asset allocation for the latest year
        foreach ($simulationAssets as $asset) {
            $lastYearData = $asset->simulationAssetYears->where('year', $latestYear)->first();
            if ($lastYearData && ($lastYearData->end_value ?? 0) > 0) {
                $assetType = $asset->asset_type ?? 'Unknown';
                $value = $lastYearData->end_value ?? 0;

                if (!isset($assetAllocation[$assetType])) {
                    $assetAllocation[$assetType] = 0;
                }
                $assetAllocation[$assetType] += $value;
            }
        }

        if (empty($assetAllocation)) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Prepare data for pie chart
        $labels = array_keys($assetAllocation);
        $data = array_values($assetAllocation);

        // Generate colors for each asset type
        $colors = [
            'rgba(59, 130, 246, 0.8)',   // Blue
            'rgba(34, 197, 94, 0.8)',    // Green
            'rgba(239, 68, 68, 0.8)',    // Red
            'rgba(245, 158, 11, 0.8)',   // Yellow
            'rgba(168, 85, 247, 0.8)',   // Purple
            'rgba(236, 72, 153, 0.8)',   // Pink
            'rgba(14, 165, 233, 0.8)',   // Sky
            'rgba(99, 102, 241, 0.8)',   // Indigo
        ];

        $backgroundColors = array_slice($colors, 0, count($labels));
        $borderColors = array_map(function($color) {
            return str_replace('0.8', '1', $color);
        }, $backgroundColors);

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            const value = new Intl.NumberFormat("nb-NO", { style: "currency", currency: "NOK", minimumFractionDigits: 0 }).format(context.parsed);
                            return context.label + ": " + value + " (" + percentage + "%)";
                        }',
                    ],
                ],
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}

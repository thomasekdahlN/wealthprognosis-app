<?php

namespace App\Filament\Widgets;

use App\Models\SimulationConfiguration;
use Filament\Widgets\ChartWidget;

class SimulationNetWorthChartWidget extends ChartWidget
{
    protected ?string $heading = 'Net Worth Projection';
    protected static ?int $sort = 4;

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

        // Collect all years and calculate net worth for each year
        $yearlyNetWorth = [];
        $years = [];

        foreach ($simulationAssets as $asset) {
            foreach ($asset->simulationAssetYears as $yearData) {
                $year = $yearData->year;
                $endValue = $yearData->end_value ?? 0;

                if (!isset($yearlyNetWorth[$year])) {
                    $yearlyNetWorth[$year] = 0;
                    $years[] = $year;
                }

                $yearlyNetWorth[$year] += $endValue;
            }
        }

        // Sort years
        sort($years);
        $years = array_unique($years);

        // Prepare data for chart
        $netWorthData = [];
        foreach ($years as $year) {
            $netWorthData[] = $yearlyNetWorth[$year] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Net Worth (NOK)',
                    'data' => $netWorthData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $years,
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
                    'beginAtZero' => false,
                    'ticks' => [
                        'callback' => 'function(value) { return new Intl.NumberFormat("nb-NO", { style: "currency", currency: "NOK", minimumFractionDigits: 0 }).format(value); }',
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
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": " + new Intl.NumberFormat("nb-NO", { style: "currency", currency: "NOK", minimumFractionDigits: 0 }).format(context.parsed.y); }',
                    ],
                ],
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
        ];
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}

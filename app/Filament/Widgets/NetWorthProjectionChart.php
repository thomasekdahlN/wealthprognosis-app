<?php

namespace App\Filament\Widgets;

use App\Models\SimulationConfiguration;
use Filament\Widgets\ChartWidget;

class NetWorthProjectionChart extends ChartWidget
{
    protected ?string $heading = 'Net Worth Projection Over Time';

    protected static ?int $sort = 3;

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
                    'simulationAssets.simulationAssetYears'
                ])
                ->where('user_id', auth()->id())
                ->find($simulationConfigurationId);
            }
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

        $data = $this->calculateNetWorthOverTime();

        return [
            'datasets' => [
                [
                    'label' => 'Total Net Worth',
                    'data' => $data['net_worth'],
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Asset Value',
                    'data' => $data['asset_value'],
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Cumulative Income',
                    'data' => $data['cumulative_income'],
                    'borderColor' => 'rgb(168, 85, 247)',
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'FIRE Target',
                    'data' => $data['fire_target'],
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderDash' => [5, 5],
                    'fill' => false,
                    'pointRadius' => 0,
                ],
            ],
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
                    'ticks' => [
                        'callback' => 'function(value) { return new Intl.NumberFormat("no-NO", { style: "currency", currency: "NOK", minimumFractionDigits: 0 }).format(value); }',
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
                        'label' => 'function(context) { return context.dataset.label + ": " + new Intl.NumberFormat("no-NO", { style: "currency", currency: "NOK", minimumFractionDigits: 0 }).format(context.parsed.y); }',
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

    protected function calculateNetWorthOverTime(): array
    {
        $simulationAssets = $this->simulationConfiguration->simulationAssets;
        $yearlyData = [];

        // Collect all years and aggregate data
        foreach ($simulationAssets as $asset) {
            foreach ($asset->simulationAssetYears as $assetYear) {
                $year = $assetYear->year;

                if (!isset($yearlyData[$year])) {
                    $yearlyData[$year] = [
                        'asset_value' => 0,
                        'income' => 0,
                        'expenses' => 0,
                    ];
                }

                $yearlyData[$year]['asset_value'] += $assetYear->asset_market_amount ?? 0;
                $yearlyData[$year]['income'] += $assetYear->income_amount ?? 0;
                $yearlyData[$year]['expenses'] += $assetYear->expence_amount ?? 0;
            }
        }

        ksort($yearlyData);

        $years = [];
        $netWorth = [];
        $assetValue = [];
        $cumulativeIncome = [];
        $fireTarget = [];
        $totalIncome = 0;

        // Calculate FIRE target (25x annual expenses)
        $firstYearExpenses = 0;
        if (!empty($yearlyData)) {
            $firstYear = reset($yearlyData);
            $firstYearExpenses = $firstYear['expenses'];
        }
        $fireAmount = $firstYearExpenses * 25;

        foreach ($yearlyData as $year => $data) {
            $years[] = $year;
            $assetValue[] = $data['asset_value'];

            $totalIncome += $data['income'];
            $cumulativeIncome[] = $totalIncome;

            // Net worth = assets + cumulative income - cumulative expenses
            $netWorth[] = $data['asset_value'];

            $fireTarget[] = $fireAmount;
        }

        return [
            'years' => $years,
            'net_worth' => $netWorth,
            'asset_value' => $assetValue,
            'cumulative_income' => $cumulativeIncome,
            'fire_target' => $fireTarget,
        ];
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}

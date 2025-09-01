<?php

namespace App\Filament\Widgets;

use App\Models\SimulationConfiguration;
use Filament\Widgets\ChartWidget;

class CashFlowProjectionChart extends ChartWidget
{
    protected ?string $heading = 'Cash Flow Projection';

    protected static ?int $sort = 4;

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

        $data = $this->calculateCashFlowOverTime();

        return [
            'datasets' => [
                [
                    'label' => 'Total Income',
                    'data' => $data['income'],
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                    'type' => 'bar',
                ],
                [
                    'label' => 'Total Expenses',
                    'data' => $data['expenses'],
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                    'type' => 'bar',
                ],
                [
                    'label' => 'Net Cash Flow',
                    'data' => $data['net_cash_flow'],
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'type' => 'line',
                ],
                [
                    'label' => 'Cumulative Cash Flow',
                    'data' => $data['cumulative_cash_flow'],
                    'borderColor' => 'rgb(168, 85, 247)',
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                    'type' => 'line',
                ],
            ],
            'labels' => $data['years'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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

    protected function calculateCashFlowOverTime(): array
    {
        $simulationAssets = $this->simulationConfiguration->simulationAssets;
        $yearlyData = [];

        // Collect all years and aggregate data
        foreach ($simulationAssets as $asset) {
            foreach ($asset->simulationAssetYears as $assetYear) {
                $year = $assetYear->year;

                if (!isset($yearlyData[$year])) {
                    $yearlyData[$year] = [
                        'income' => 0,
                        'expenses' => 0,
                    ];
                }

                $yearlyData[$year]['income'] += $assetYear->income_amount ?? 0;
                $yearlyData[$year]['expenses'] += $assetYear->expence_amount ?? 0;
            }
        }

        ksort($yearlyData);

        $years = [];
        $income = [];
        $expenses = [];
        $netCashFlow = [];
        $cumulativeCashFlow = [];
        $totalCumulative = 0;

        foreach ($yearlyData as $year => $data) {
            $years[] = $year;
            $income[] = $data['income'];
            $expenses[] = $data['expenses'];

            $netFlow = $data['income'] - $data['expenses'];
            $netCashFlow[] = $netFlow;

            $totalCumulative += $netFlow;
            $cumulativeCashFlow[] = $totalCumulative;
        }

        return [
            'years' => $years,
            'income' => $income,
            'expenses' => $expenses,
            'net_cash_flow' => $netCashFlow,
            'cumulative_cash_flow' => $cumulativeCashFlow,
        ];
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}

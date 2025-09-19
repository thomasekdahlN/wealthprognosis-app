<?php

namespace App\Filament\Widgets;

use App\Models\SimulationConfiguration;
use Filament\Widgets\ChartWidget;

class SimulationCashFlowChartWidget extends ChartWidget
{
    protected static bool $isLazy = false;

    protected ?string $heading = 'Cash Flow Analysis';
    protected static ?int $sort = 5;

    public ?SimulationConfiguration $simulationConfiguration = null;

    public function mount(?SimulationConfiguration $simulationConfiguration = null): void
    {
        if ($simulationConfiguration) {
            $this->simulationConfiguration = $simulationConfiguration;
            return;
        }

        // Fallback: Get simulation_configuration_id from request
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

        // Collect yearly cash flow data
        $yearlyIncome = [];
        $yearlyExpenses = [];
        $years = [];

        foreach ($simulationAssets as $asset) {
            foreach ($asset->simulationAssetYears as $yearData) {
                $year = $yearData->year;
                $income = $yearData->income_amount ?? 0;
                $expenses = $yearData->expence_amount ?? 0;

                if (!isset($yearlyIncome[$year])) {
                    $yearlyIncome[$year] = 0;
                    $yearlyExpenses[$year] = 0;
                    $years[] = $year;
                }

                $yearlyIncome[$year] += $income;
                $yearlyExpenses[$year] += $expenses;
            }
        }

        // Sort years
        sort($years);
        $years = array_unique($years);

        // Prepare data for chart
        $incomeData = [];
        $expenseData = [];
        $netCashFlowData = [];

        foreach ($years as $year) {
            $income = $yearlyIncome[$year] ?? 0;
            $expenses = $yearlyExpenses[$year] ?? 0;

            $incomeData[] = $income;
            $expenseData[] = $expenses;
            $netCashFlowData[] = $income - $expenses;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Income (NOK)',
                    'data' => $incomeData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Expenses (NOK)',
                    'data' => $expenseData,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Net Cash Flow (NOK)',
                    'data' => $netCashFlowData,
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
                    'beginAtZero' => true,
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

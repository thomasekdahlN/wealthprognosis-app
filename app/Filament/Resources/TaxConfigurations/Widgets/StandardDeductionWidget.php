<?php

namespace App\Filament\Resources\TaxConfigurations\Widgets;

use App\Models\TaxConfiguration;
use Filament\Widgets\ChartWidget;

class StandardDeductionWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public string $country = '';

    public string $taxType = '';

    public ?TaxConfiguration $record = null;

    public function mount(array $properties = []): void
    {
        if (isset($properties['record'])) {
            $this->record = $properties['record'];
        }
        if (isset($properties['country'])) {
            $this->country = (string) $properties['country'];
        }
        if (isset($properties['tax_type'])) {
            $this->taxType = (string) $properties['tax_type'];
        }
    }

    public function getHeading(): string
    {
        [$country, $taxType] = $this->resolveContext();
        $taxLabel = $taxType !== '' ? ucfirst(str_replace('_', ' ', $taxType)) : '—';
        $countryLabel = strtoupper($country);

        return "Standard deduction over time — {$taxLabel} ({$countryLabel})";
    }

    protected function getData(): array
    {
        [$country, $taxType] = $this->resolveContext();

        if ($country === '' || $taxType === '') {
            return [
                'datasets' => [[
                    'label' => 'No Data',
                    'data' => [0],
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ]],
                'labels' => ['Missing context'],
            ];
        }

        $rows = TaxConfiguration::query()
            ->where('country_code', $country)
            ->where('tax_type', $taxType)
            ->orderBy('year')
            ->get(['year', 'configuration']);

        if ($rows->isEmpty()) {
            return [
                'datasets' => [[
                    'label' => 'No data available',
                    'data' => [0],
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ]],
                'labels' => ['No data'],
            ];
        }

        /** @var \Illuminate\Support\Collection<int, string> $labelsCollection */
        $labelsCollection = $rows->pluck('year')->map(fn ($y) => (string) $y)->values();
        $labels = $labelsCollection->all();

        // Check if we have any non-zero values for standard deduction
        $hasStandardDeduction = $rows->some(fn ($row) => isset($row->configuration['standardDeduction']) && (float) $row->configuration['standardDeduction'] > 0);

        if (!$hasStandardDeduction) {
            return [
                'datasets' => [[
                    'label' => 'No standard deduction data available',
                    'data' => array_fill(0, count($labels), 0),
                    'borderColor' => '#9ca3af',
                    'backgroundColor' => 'rgba(156, 163, 175, 0.1)',
                ]],
                'labels' => $labels,
            ];
        }

        /** @var \Illuminate\Support\Collection<int, float> $dataCollection */
        $dataCollection = $rows->pluck('configuration')->map(fn ($cfg) => (float) ($cfg['standardDeduction'] ?? 0))->values();

        return [
            'datasets' => [[
                'label' => 'Standard Deduction',
                'data' => $dataCollection->all(),
                'borderColor' => '#8b5cf6', // Purple
                'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                'tension' => 0.2,
                'fill' => true,
                'pointRadius' => 3,
                'pointHoverRadius' => 5,
            ]],
            'labels' => $labels,
        ];
    }

    /**
     * Resolve country and tax type from widget properties or route parameters
     *
     * @return array{0: string, 1: string}
     */
    private function resolveContext(): array
    {
        // First, try to use the record passed to the widget
        if ($this->record !== null) {
            return [
                (string) $this->record->country_code,
                (string) $this->record->tax_type,
            ];
        }

        // Fall back to properties
        $country = $this->country ?? (string) (request()->route('country') ?? '');
        $taxType = $this->taxType ?? '';

        // Finally, try route parameters
        if ($taxType === '') {
            $recordParam = request()->route('record');
            if ($recordParam) {
                $record = $recordParam instanceof TaxConfiguration
                    ? $recordParam
                    : TaxConfiguration::query()->find($recordParam);

                if ($record) {
                    $country = (string) $record->country_code;
                    $taxType = (string) $record->tax_type;
                }
            }
        }

        return [$country, $taxType];
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
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": " + context.parsed.y.toLocaleString("no-NO"); }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Amount',
                    ],
                    'ticks' => [
                        'callback' => 'function(value) { return value.toLocaleString("no-NO"); }',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Year',
                    ],
                ],
            ],
        ];
    }
}


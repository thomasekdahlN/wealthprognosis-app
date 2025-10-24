<?php

namespace App\Filament\Resources\TaxConfigurations\Widgets;

use App\Models\TaxConfiguration;
use Filament\Widgets\ChartWidget;

class IncomeTaxRateTrend extends ChartWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public string $country = '';

    public string $taxType = '';

    public function mount(array $properties = []): void
    {
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

        return "Income tax rate over time — {$taxLabel}";
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
                    'label' => 'Income tax rate %',
                    'data' => [0],
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.25,
                    'fill' => true,
                ]],
                'labels' => ['No data'],
            ];
        }

        $labels = $rows->pluck('year')->map(fn ($y) => (string) $y)->values()->all();
        $data = $rows->pluck('configuration')->map(fn ($cfg) => (float) (($cfg['income'] ?? 0))).values()->all();

        return [
            'datasets' => [[
                'label' => 'Income tax rate %',
                'data' => $data,
                'borderColor' => '#10b981',
                'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                'tension' => 0.2,
                'fill' => true,
                'pointRadius' => 3,
                'pointHoverRadius' => 5,
            ]],
            'labels' => $labels,
        ];
    }

    private function resolveContext(): array
    {
        $country = $this->country ?? (string) (request()->route('country') ?? '');
        $taxType = $this->taxType ?? '';

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
                    'callbacks' => [
                        'label' => 'function(context) { return context.parsed.y + "%"; }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Income tax rate %',
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

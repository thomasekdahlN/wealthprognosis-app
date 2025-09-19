<?php

namespace App\Filament\Widgets;

use App\Models\PrognosisChangeRate;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ChangeRateTrendWidget extends ChartWidget
{
    protected static bool $isLazy = false;
    protected int|string|array $columnSpan = 'full';

    public ?string $scenario = null;
    public ?string $asset = null;

    public function mount(array $data = []): void
    {
        // Important: ensure ChartWidget's own mount runs
        parent::mount($data);

        // Prefer data passed from the page
        $this->scenario = $data['scenario'] ?? $this->scenario;
        $this->asset = $data['asset'] ?? $this->asset;

        // Fallbacks to query params
        $this->scenario ??= request()->get('scenario', 'realistic');
        $this->asset ??= request()->get('asset', 'equityfund');
    }

    public function getHeading(): string
    {
        $scenario = $this->scenario ?? request()->get('scenario', 'realistic');
        $asset = $this->asset ?? request()->get('asset', 'equityfund');

        $scenarioLabel = \App\Models\PrognosisType::where('code', $scenario)->value('label') ?? ucfirst($scenario);
        $assetLabel = \App\Models\PrognosisChangeRate::ASSET_TYPES[$asset] ?? ucfirst($asset);

        return "Change Rate Trend - {$assetLabel} ({$scenarioLabel})";
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $scenario = $this->scenario ?? request()->get('scenario', 'realistic');
        $asset = $this->asset ?? request()->get('asset', 'equityfund');

        $records = PrognosisChangeRate::query()
            ->where('scenario_type', $scenario)
            ->where('asset_type', $asset)
            ->orderBy('year')
            ->get(['year', 'change_rate']);

        // Debug: log what we are about to render
        \Log::debug('ChangeRateTrendWidget:getData', [
            'scenario' => $scenario,
            'asset' => $asset,
            'count' => $records->count(),
            'years' => $records->pluck('year')->all(),
        ]);

        $years = $records->pluck('year')->map(fn ($y) => (string) $y)->all();
        $rates = $records->pluck('change_rate')->map(function ($v) {
            return $v === null ? null : round((float) $v, 2);
        })->all();

        // Minimal chart dataset (let Filament/Chart.js apply defaults)
        return [
            'datasets' => [
                [
                    'label' => 'Change Rate %',
                    'data' => $rates,
                ],
            ],
            'labels' => $years,
        ];
    }

    protected function getOptions(): array
    {
        // Minimal stable Chart.js options as an associative array (encodes to an object in JSON)
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['display' => true],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => new RawJs(<<<'JS'
                            function(context) {
                                var v = (context.parsed && context.parsed.y !== undefined) ? context.parsed.y : context.raw;
                                if (v === null || v === undefined) {
                                    return context.dataset.label + ': 0.00%';
                                }
                                var n = Number(v);
                                if (isNaN(n)) {
                                    return context.dataset.label + ': 0.00%';
                                }
                                return context.dataset.label + ': ' + n.toFixed(2) + '%';
                            }
                        JS),
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'title' => ['display' => true, 'text' => 'Change Rate (%)'],
                    'grace' => '10%',
                ],
                'x' => [
                    'title' => ['display' => true, 'text' => 'Year'],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
        ];
    }
}


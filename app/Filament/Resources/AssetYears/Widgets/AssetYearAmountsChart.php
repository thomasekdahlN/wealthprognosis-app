<?php

namespace App\Filament\Resources\AssetYears\Widgets;

use App\Models\Asset;
use App\Models\AssetYear;
use Filament\Widgets\ChartWidget;

class AssetYearAmountsChart extends ChartWidget
{
    public ?string $filter = null;

    public function getHeading(): string
    {
        return __('Amounts over years');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        // Get the asset ID from the request parameters
        $assetId = (int) request()->get('asset');

        if (! $assetId) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $asset = Asset::find($assetId);
        if (! $asset) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $years = AssetYear::query()
            ->where('asset_id', $assetId)
            ->orderBy('year')
            ->get(['year', 'income_amount', 'expence_amount', 'asset_market_amount', 'mortgage_amount']);

        if ($years->isEmpty()) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $labels = $years->pluck('year')->unique()->values()->map(fn ($y) => (string) $y)->all();

        $datasets = [];

        // Colors (Tailwind palette approximations)
        $blue = 'rgba(59,130,246,0.9)';      // income
        $rose = 'rgba(244,63,94,0.9)';       // expense
        $emerald = 'rgba(16,185,129,0.9)';   // market
        $amber = 'rgba(245,158,11,0.9)';     // mortgage

        // Try to get asset type, but fall back to showing all data if relationship fails
        $type = null;
        try {
            $type = $asset->assetType;
        } catch (\Exception $e) {
            // If asset type relationship fails, we'll show all datasets
        }

        // Show income data (default to true if asset type is not available)
        if (($type?->can_generate_income ?? true)) {
            $datasets[] = [
                'label' => __('Income'),
                'data' => $years->pluck('income_amount')->map(fn ($v) => is_null($v) ? null : (float) $v)->all(),
                'borderColor' => $blue,
                'backgroundColor' => $blue,
                'tension' => 0.25,
            ];
        }

        // Show expense data (default to true if asset type is not available)
        if (($type?->can_generate_expenses ?? true)) {
            $datasets[] = [
                'label' => __('Expense'),
                'data' => $years->pluck('expence_amount')->map(fn ($v) => is_null($v) ? null : (float) $v)->all(),
                'borderColor' => $rose,
                'backgroundColor' => $rose,
                'tension' => 0.25,
            ];
        }

        // Show market value data (default to true if asset type is not available)
        if (($type?->can_have_market_value ?? true)) {
            $datasets[] = [
                'label' => __('Market value'),
                'data' => $years->pluck('asset_market_amount')->map(fn ($v) => is_null($v) ? null : (float) $v)->all(),
                'borderColor' => $emerald,
                'backgroundColor' => $emerald,
                'tension' => 0.25,
            ];
        }

        // Show mortgage data (default to true if asset type is not available)
        if (($type?->can_have_mortgage ?? true)) {
            $datasets[] = [
                'label' => __('Mortgage'),
                'data' => $years->pluck('mortgage_amount')->map(fn ($v) => is_null($v) ? null : (float) $v)->all(),
                'borderColor' => $amber,
                'backgroundColor' => $amber,
                'tension' => 0.25,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }
}

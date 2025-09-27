<?php

namespace App\Filament\Widgets;

use App\Models\Asset;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class AssetAllocationByCategory extends ChartWidget
{
    protected static ?int $sort = 22;

    protected int|string|array $columnSpan = 4; // Show 3 charts per row (12-column grid)

    protected ?int $assetConfigId = null;

    public function mount(): void
    {
        // Use session via CurrentAssetConfiguration only (no querystring fallback)
        $this->assetConfigId = app(\App\Services\CurrentAssetConfiguration::class)->id();
    }

    public function getHeading(): string
    {
        $heading = 'Asset Allocation by Category ('.now()->year.')';

        if ($this->assetConfigId) {
            $assetConfiguration = \App\Models\AssetConfiguration::find($this->assetConfigId);
            if ($assetConfiguration) {
                $heading = 'Asset Allocation by Category - '.$assetConfiguration->name.' ('.now()->year.')';
            }
        }

        return $heading;
    }

    protected function getData(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Group assets by category and calculate values from AssetYear data
        $currentYear = now()->year;

        $assetGroups = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);

            // Apply asset configuration filtering if specified
            if ($this->assetConfigId) {
                $query->where('asset_configuration_id', $this->assetConfigId);
            }
        })
            ->where('year', '<=', $currentYear) // Don't go beyond current year
            ->where('year', $currentYear) // Use current year data
            ->where('asset_market_amount', '>', 0)
            ->with(['asset', 'asset.assetType'])
            ->get()
            ->groupBy(function ($assetYear) {
                // Group by category from the asset_type relation
                return $assetYear->asset->assetType->category ?? 'Uncategorized';
            })
            ->map(function ($assetYears) {
                return $assetYears->sum('asset_market_amount');
            })
            ->sortDesc();

        // Define colors for different categories
        $colors = [
            'private' => '#10b981',     // Green
            'business' => '#3b82f6',    // Blue
            'investment' => '#f59e0b',  // Amber
            'real_estate' => '#ef4444', // Red
            'retirement' => '#8b5cf6',  // Purple
            'cash' => '#06b6d4',        // Cyan
            'vehicle' => '#f97316',     // Orange
            'collectible' => '#ec4899', // Pink
            'other' => '#6b7280',       // Gray
            'uncategorized' => '#9ca3af', // Light Gray
            'default' => '#6b7280',     // Gray
        ];

        $labels = [];
        $data = [];
        $backgroundColors = [];

        foreach ($assetGroups as $category => $amount) {
            $labels[] = $this->getCategoryLabel($category);
            $data[] = round($amount);
            $backgroundColors[] = $colors[$category] ?? $colors['default'];
        }

        // If no asset data, show a placeholder
        if (empty($data)) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['#e5e7eb'],
                        'borderColor' => ['#d1d5db'],
                        'borderWidth' => 1,
                    ],
                ],
                'labels' => ['No assets available'],
            ];
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => array_map(function ($color) {
                        return $color.'dd'; // Add transparency
                    }, $backgroundColors),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'mode' => 'nearest',
                    'intersect' => true,
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }

    private function getCategoryLabel(string $category): string
    {
        return match ($category) {
            'private' => 'Private Assets',
            'business' => 'Business Assets',
            'investment' => 'Investments',
            'real_estate' => 'Real Estate',
            'retirement' => 'Retirement Accounts',
            'cash' => 'Cash & Equivalents',
            'vehicle' => 'Vehicles',
            'collectible' => 'Collectibles',
            'other' => 'Other Assets',
            'uncategorized' => 'Uncategorized',
            default => ucfirst(str_replace('_', ' ', $category)),
        };
    }
}

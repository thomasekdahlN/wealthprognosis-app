<?php

namespace App\Filament\Widgets;

use App\Models\Asset;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class AssetAllocationByType extends ChartWidget
{
    protected static ?int $sort = 20;

    protected ?int $assetConfigId = null;

    public function mount(): void
    {
        // Accept both new and legacy param keys
        $this->assetConfigId = (int) (request()->get('asset_configuration_id') ?? request()->get('asset_owner_id') ?? 0) ?: null;
    }

    public function getHeading(): string
    {
        $heading = 'Asset Allocation by Type ('.now()->year.')';

        if ($this->assetConfigId) {
            $assetConfiguration = \App\Models\AssetConfiguration::find($this->assetConfigId);
            if ($assetConfiguration) {
                $heading = 'Asset Allocation by Type - ' . $assetConfiguration->name . ' (' . now()->year . ')';
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

        // Group assets by type and calculate values from AssetYear data
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
            // Remove the asset_market_amount > 0 filter to include all assets
            ->with('asset')
            ->get()
            ->groupBy(function ($assetYear) {
                // Group by asset_type (the code field)
                return $assetYear->asset->asset_type ?? 'Unknown Type';
            })
            ->map(function ($assetYears) {
                return $assetYears->sum('asset_market_amount');
            })
            ->filter(function ($amount) {
                // Only show groups with positive total amounts
                return $amount > 0;
            })
            ->sortDesc();

        // Define colors for different asset types
        $colors = [
            'house' => '#ef4444',      // Red
            'rental' => '#f97316',     // Orange
            'cabin' => '#f59e0b',      // Amber
            'car' => '#eab308',        // Yellow
            'boat' => '#84cc16',       // Lime
            'motorcycle' => '#22c55e', // Green
            'equityfund' => '#10b981', // Emerald
            'bondfund' => '#14b8a6',   // Teal
            'stock' => '#06b6d4',      // Cyan
            'crypto' => '#0ea5e9',     // Sky
            'cash' => '#3b82f6',       // Blue
            'bank' => '#6366f1',       // Indigo
            'salary' => '#8b5cf6',     // Violet
            'pension' => '#a855f7',    // Purple
            'other' => '#d946ef',      // Fuchsia
            'default' => '#6b7280',    // Gray
        ];

        $labels = [];
        $data = [];
        $backgroundColors = [];

        foreach ($assetGroups as $assetType => $amount) {
            $labels[] = $this->getAssetTypeLabel($assetType);
            $data[] = round($amount);
            $backgroundColors[] = $colors[$assetType] ?? $colors['default'];
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
        return 'doughnut';
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

    private function getAssetTypeLabel(string $assetType): string
    {
        return match ($assetType) {
            'house' => 'House',
            'rental' => 'Rental Property',
            'cabin' => 'Cabin',
            'car' => 'Car',
            'boat' => 'Boat',
            'motorcycle' => 'Motorcycle',
            'equityfund' => 'Equity Fund',
            'bondfund' => 'Bond Fund',
            'stock' => 'Stock',
            'crypto' => 'Cryptocurrency',
            'cash' => 'Cash',
            'bank' => 'Bank Account',
            'salary' => 'Salary',
            'pension' => 'Pension',
            'other' => 'Other',
            default => ucfirst($assetType),
        };
    }
}

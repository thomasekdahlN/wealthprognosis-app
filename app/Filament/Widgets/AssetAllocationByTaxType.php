<?php

namespace App\Filament\Widgets;

use App\Models\Asset;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class AssetAllocationByTaxType extends ChartWidget
{
    protected static ?int $sort = 21;

    protected int|string|array $columnSpan = 4; // Show 3 charts per row (12-column grid)

    protected ?int $assetConfigId = null;

    public function mount(): void
    {
        $this->assetConfigId = app(\App\Services\CurrentAssetConfiguration::class)->id()
            ?? request()->get('asset_configuration_id')
            ?? request()->get('asset_owner_id');
    }

    public function getHeading(): string
    {
        $heading = 'Asset Allocation by Tax Type ('.now()->year.')';

        if ($this->assetConfigId) {
            $assetConfiguration = \App\Models\AssetConfiguration::find($this->assetConfigId);
            if ($assetConfiguration) {
                $heading = 'Asset Allocation by Tax Type - '.$assetConfiguration->name.' ('.now()->year.')';
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

        // Group assets by tax type and calculate values from AssetYear data
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
            ->with('asset')
            ->get()
            ->groupBy('asset.tax_type')
            ->map(function ($assetYears) {
                return $assetYears->sum('asset_market_amount');
            })
            ->sortDesc();

        // Define colors for different tax types
        $colors = [
            'salary' => '#ef4444',      // Red
            'house' => '#f97316',       // Orange
            'equityfund' => '#f59e0b',  // Amber
            'bondfund' => '#eab308',    // Yellow
            'stock' => '#84cc16',       // Lime
            'crypto' => '#22c55e',      // Green
            'rental' => '#10b981',      // Emerald
            'otp' => '#14b8a6',         // Teal
            'ips' => '#06b6d4',         // Cyan
            'ask' => '#0ea5e9',         // Sky
            'pension' => '#3b82f6',     // Blue
            'cash' => '#6366f1',        // Indigo
            'bank' => '#8b5cf6',        // Violet
            'none' => '#a855f7',        // Purple
            'other' => '#d946ef',       // Fuchsia
            'default' => '#6b7280',     // Gray
        ];

        $labels = [];
        $data = [];
        $backgroundColors = [];

        foreach ($assetGroups as $taxType => $amount) {
            $labels[] = $this->getTaxTypeLabel($taxType);
            $data[] = round($amount);
            $backgroundColors[] = $colors[$taxType] ?? $colors['default'];
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

    private function getTaxTypeLabel(string $taxType): string
    {
        return match ($taxType) {
            'salary' => 'Salary Tax',
            'house' => 'Property Tax',
            'equityfund' => 'Equity Fund Tax',
            'bondfund' => 'Bond Fund Tax',
            'stock' => 'Stock Tax',
            'crypto' => 'Crypto Tax',
            'rental' => 'Rental Tax',
            'otp' => 'Occupational Pension',
            'ips' => 'Individual Pension',
            'ask' => 'Share Savings Account',
            'pension' => 'Pension Tax',
            'cash' => 'Cash Tax',
            'bank' => 'Bank Tax',
            'none' => 'No Tax',
            'other' => 'Other Tax',
            default => ucfirst($taxType).' Tax',
        };
    }
}

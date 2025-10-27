<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class ActualTaxRateOverTime extends ChartWidget
{
    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Actual Tax Rate Over Time';
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

        // Get years with data from asset_years table for the current user - only up to current year
        $years = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
            ->where('year', '<=', now()->year) // Don't go beyond current year
            ->distinct()
            ->orderBy('year')
            ->pluck('year')
            ->toArray();

        // If no years found, use current year range - only up to current year
        if (empty($years)) {
            $years = range(now()->year - 2, now()->year);
        }

        $taxRateData = [];
        $effectiveTaxRateData = [];
        $marginalTaxRateData = [];

        foreach ($years as $year) {
            // Calculate total income for this year (considering factors)
            $incomeRecords = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('is_active', true);
            })
                ->where('year', $year)
                ->whereNotNull('income_amount')
                ->where('income_amount', '>', 0)
                ->get();

            $totalIncome = $incomeRecords->sum(function ($record) {
                $factor = $record->income_factor === 'monthly' ? 12 : 1; // Convert enum to multiplier

                return $record->income_amount * $factor; // Convert to annual
            });

            // Calculate total tax paid for this year for the current user
            // First try to get actual tax_amount, if not available, estimate from income
            $totalTax = \App\Models\AssetYear::whereHas('asset', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('is_active', true);
            })
                ->where('year', $year)
                ->whereNotNull('tax_amount')
                ->sum('tax_amount') ?: 0;

            // If no tax_amount data, estimate tax based on Norwegian tax system
            if ($totalTax == 0 && $totalIncome > 0) {
                $totalTax = $this->estimateTaxFromIncome($totalIncome);
            }

            // Calculate tax rate as percentage of income
            $taxRate = $totalIncome > 0 ? ($totalTax / $totalIncome) * 100 : 0;

            // For demonstration, calculate estimated marginal tax rate (simplified Norwegian tax system)
            $marginalTaxRate = $this->calculateMarginalTaxRate($totalIncome);

            // Effective tax rate is the actual tax paid
            $effectiveTaxRate = $taxRate;

            $taxRateData[] = round($taxRate, 2);
            $effectiveTaxRateData[] = round($effectiveTaxRate, 2);
            $marginalTaxRateData[] = round($marginalTaxRate, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Actual Tax Rate',
                    'data' => $taxRateData,
                    'borderColor' => '#ef4444', // Red
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Estimated Marginal Tax Rate',
                    'data' => $marginalTaxRateData,
                    'borderColor' => '#f59e0b', // Amber
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                    'borderDash' => [5, 5],
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
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 60, // Max 60% tax rate for better visualization
                    'title' => [
                        'display' => true,
                        'text' => 'Tax Rate (%)',
                    ],
                    'ticks' => [
                        'callback' => null,
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Year',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'elements' => [
                'point' => [
                    'radius' => 4,
                    'hoverRadius' => 6,
                ],
            ],
        ];
    }

    /**
     * Estimate actual tax amount from income using Norwegian tax system
     */
    private function estimateTaxFromIncome(float $income): float
    {
        // Simplified Norwegian tax calculation for 2024
        $tax = 0;

        // Personal allowance (approximate)
        $personalAllowance = 208050;
        $taxableIncome = max(0, $income - $personalAllowance);

        // Base tax rate (22%)
        $tax += $taxableIncome * 0.22;

        // Progressive tax steps
        if ($income > 292850) {
            $tax += min($income - 292850, 670000 - 292850) * 0.017;
        }
        if ($income > 670000) {
            $tax += min($income - 670000, 937900 - 670000) * 0.04;
        }
        if ($income > 937900) {
            $tax += min($income - 937900, 1350000 - 937900) * 0.134;
        }
        if ($income > 1350000) {
            $tax += ($income - 1350000) * 0.164;
        }

        return max(0, $tax);
    }

    /**
     * Calculate estimated marginal tax rate based on Norwegian tax system (simplified)
     */
    private function calculateMarginalTaxRate(float $income): float
    {
        // Simplified Norwegian tax brackets for 2024 (approximate)
        // This is a simplified calculation for demonstration

        if ($income <= 208050) {
            return 0.0; // Below personal allowance
        } elseif ($income <= 292850) {
            return 22.0; // Base tax rate
        } elseif ($income <= 670000) {
            return 22.0 + 1.7; // Base + step 1
        } elseif ($income <= 937900) {
            return 22.0 + 1.7 + 4.0; // Base + step 1 + step 2
        } elseif ($income <= 1350000) {
            return 22.0 + 1.7 + 4.0 + 13.4; // Base + step 1 + step 2 + step 3
        } else {
            return 22.0 + 1.7 + 4.0 + 13.4 + 16.4; // All steps
        }
    }
}

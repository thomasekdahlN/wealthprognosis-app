<?php

namespace App\Services;

use App\Models\AssetYear;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class FireCalculationService
{
    public static function getFinancialData(?int $assetOwnerId = null): array
    {
        $user = Auth::user();
        $currentYear = now()->year;

        if (! $user) {
            // For testing purposes, use user ID 1 if no auth user
            $user = \App\Models\User::find(1);
            if (! $user) {
                return self::getEmptyData();
            }
        }

        // Use provided asset owner ID, or get from request/session if not provided
        if ($assetOwnerId === null) {
            $assetOwnerId = request()->get('asset_owner_id') ?? session('dashboard_asset_owner_id');
        }

        // Calculate FIRE-sellable assets (Investment Assets)
        $totalAssets = AssetYear::whereHas('asset', function ($query) use ($user, $assetOwnerId) {
            $query->where('user_id', $user->id)->where('is_active', true);

            if ($assetOwnerId) {
                $query->where('asset_owner_id', $assetOwnerId);
            }

            $query->whereHas('assetType', function ($assetTypeQuery) {
                $assetTypeQuery->where('is_fire_sellable', true);
            });
        })
            ->where('year', $currentYear)
            ->where('asset_market_amount', '>', 0)
            ->sum('asset_market_amount') ?? 0;

        // Calculate ALL assets for Net Worth
        $allAssets = AssetYear::whereHas('asset', function ($query) use ($user, $assetOwnerId) {
            $query->where('user_id', $user->id)->where('is_active', true);

            if ($assetOwnerId) {
                $query->where('asset_owner_id', $assetOwnerId);
            }
        })
            ->where('year', $currentYear)
            ->where('asset_market_amount', '>', 0)
            ->sum('asset_market_amount') ?? 0;

        // Calculate total liabilities
        $totalLiabilities = AssetYear::whereHas('asset', function ($query) use ($user, $assetOwnerId) {
            $query->where('user_id', $user->id)->where('is_active', true);

            if ($assetOwnerId) {
                $query->where('asset_owner_id', $assetOwnerId);
            }
        })
            ->where('year', $currentYear)
            ->sum('mortgage_amount') ?? 0;

        // Calculate monthly income
        $incomeRecords = AssetYear::whereHas('asset', function ($query) use ($user, $assetOwnerId) {
            $query->where('user_id', $user->id)->where('is_active', true);

            if ($assetOwnerId) {
                $query->where('asset_owner_id', $assetOwnerId);
            }
        })
            ->where('year', $currentYear)
            ->whereNotNull('income_amount')
            ->where('income_amount', '>', 0)
            ->get();

        $monthlyIncome = $incomeRecords->sum(function ($record) {
            if ($record->income_factor === 'monthly') {
                return $record->income_amount; // Already monthly
            } else {
                return $record->income_amount / 12; // Convert yearly to monthly
            }
        });

        // Calculate monthly expenses
        $expenseRecords = AssetYear::whereHas('asset', function ($query) use ($user, $assetOwnerId) {
            $query->where('user_id', $user->id)->where('is_active', true);

            if ($assetOwnerId) {
                $query->where('asset_owner_id', $assetOwnerId);
            }
        })
            ->where('year', $currentYear)
            ->whereNotNull('expence_amount')
            ->where('expence_amount', '>', 0)
            ->get();

        $monthlyExpenses = $expenseRecords->sum(function ($record) {
            if ($record->expence_factor === 'monthly') {
                return $record->expence_amount; // Already monthly
            } else {
                return $record->expence_amount / 12; // Convert yearly to monthly
            }
        });

        // Calculate derived values
        $annualIncome = $monthlyIncome * 12;
        $annualExpenses = $monthlyExpenses * 12;
        $netWorth = $allAssets - $totalLiabilities;
        $theGap = $annualIncome - $annualExpenses;
        $fireNumber = $annualExpenses * 25;
        $progressToFire = $fireNumber > 0 ? ($totalAssets / $fireNumber) * 100 : 0;
        $potentialAnnualIncome = $totalAssets * 0.04;
        $crossoverAchieved = $potentialAnnualIncome >= $annualExpenses;

        return [
            'user' => $user,
            'currentYear' => $currentYear,
            'assetOwnerId' => $assetOwnerId,
            'totalAssets' => $totalAssets, // FIRE-sellable assets
            'allAssets' => $allAssets, // All assets
            'totalLiabilities' => $totalLiabilities,
            'monthlyIncome' => $monthlyIncome,
            'monthlyExpenses' => $monthlyExpenses,
            'annualIncome' => $annualIncome,
            'annualExpenses' => $annualExpenses,
            'netWorth' => $netWorth,
            'theGap' => $theGap,
            'fireNumber' => $fireNumber,
            'progressToFire' => $progressToFire,
            'potentialAnnualIncome' => $potentialAnnualIncome,
            'crossoverAchieved' => $crossoverAchieved,
        ];
    }

    private static function getEmptyData(): array
    {
        return [
            'user' => null,
            'currentYear' => now()->year,
            'assetOwnerId' => null,
            'totalAssets' => 0,
            'allAssets' => 0,
            'totalLiabilities' => 0,
            'monthlyIncome' => 0,
            'monthlyExpenses' => 0,
            'annualIncome' => 0,
            'annualExpenses' => 0,
            'netWorth' => 0,
            'theGap' => 0,
            'fireNumber' => 0,
            'progressToFire' => 0,
            'potentialAnnualIncome' => 0,
            'crossoverAchieved' => false,
        ];
    }
}

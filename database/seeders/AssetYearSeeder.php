<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetYear;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssetYearSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'thomas@ekdahl.no')->first() ?? User::first();
        if (! $user) {
            $this->call(UserSeeder::class);
            $user = User::first();
        }

        // Get all assets that don't have asset years yet
        $assets = Asset::whereDoesntHave('years')->get();

        foreach ($assets as $asset) {
            // Create asset years for 2023, 2024, 2025
            foreach ([2023, 2024, 2025] as $year) {
                $this->createAssetYear($asset, $year, $user);
            }
        }

        // Also ensure existing assets have proper asset years
        $assetsWithYears = Asset::has('years')->get();
        foreach ($assetsWithYears as $asset) {
            // Check if we need to add missing years
            $existingYears = $asset->years->pluck('year')->toArray();
            $requiredYears = [2023, 2024, 2025];
            $missingYears = array_diff($requiredYears, $existingYears);

            foreach ($missingYears as $year) {
                $this->createAssetYear($asset, $year, $user);
            }
        }
    }

    private function createAssetYear(Asset $asset, int $year, User $user): void
    {
        $baseIncomeAmount = $this->getBaseIncomeAmount($asset->asset_type);
        $baseExpenseAmount = $this->getBaseExpenseAmount($asset->asset_type);
        $baseMarketAmount = $this->getBaseMarketAmount($asset->asset_type, $asset);
        $baseMortgageAmount = $this->getBaseMortgageAmount($asset->asset_type, $asset);

        // Add some year-over-year variation
        $yearMultiplier = 1 + (($year - 2023) * 0.05); // 5% growth per year

        AssetYear::updateOrCreate(
            [
                'asset_id' => $asset->id,
                'year' => $year,
            ],
            [
                'user_id' => $user->id,
                'team_id' => $asset->team_id,
                'asset_configuration_id' => $asset->asset_configuration_id,
                'income_description' => $this->getIncomeDescription($asset->asset_type),
                'income_amount' => $baseIncomeAmount ? round($baseIncomeAmount * $yearMultiplier) : null,
                'income_factor' => $baseIncomeAmount ? 'monthly' : null,
                'income_changerate' => $baseIncomeAmount ? 'changerates.kpi' : null,
                'income_repeat' => $baseIncomeAmount ? true : false,
                'expence_description' => $this->getExpenseDescription($asset->asset_type),
                'expence_amount' => $baseExpenseAmount ? round($baseExpenseAmount * $yearMultiplier) : null,
                'expence_factor' => $baseExpenseAmount ? 'monthly' : null,
                'expence_changerate' => $baseExpenseAmount ? 'changerates.kpi' : null,
                'expence_repeat' => $baseExpenseAmount ? true : false,
                'asset_description' => 'Asset value for '.$year,
                'asset_market_amount' => $baseMarketAmount ? round($baseMarketAmount * $yearMultiplier) : null,
                'asset_acquisition_amount' => $asset->acquisition_amount,
                'asset_equity_amount' => $baseMarketAmount ? round(($baseMarketAmount * 0.8) * $yearMultiplier) : null,
                'asset_paid_amount' => $asset->paid_amount,
                'asset_taxable_initial_amount' => $asset->taxable_initial_amount,
                'asset_changerate' => $asset->change_rate_type,
                // mortgage_name field removed
                'mortgage_description' => $this->getMortgageDescription($asset->asset_type),
                'mortgage_amount' => $baseMortgageAmount ? round($baseMortgageAmount * (1 - (($year - 2023) * 0.1))) : null, // Decreasing mortgage
                'mortgage_interest' => $baseMortgageAmount ? '5.5' : null,
                'mortgage_years' => $baseMortgageAmount ? 20 : null,
                'mortgage_gebyr' => $baseMortgageAmount ? 600 : null,
                'mortgage_tax' => $baseMortgageAmount ? 22 : null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => hash('sha256', $asset->id.'_'.$year.'_created'),
                'updated_checksum' => hash('sha256', $asset->id.'_'.$year.'_updated'),
            ]
        );
    }

    private function getBaseIncomeAmount(string $type): ?int
    {
        return match ($type) {
            'salary' => 50000,
            'rental' => 15000,
            'dividend' => 2000,
            'interest' => 500,
            default => null,
        };
    }

    private function getBaseExpenseAmount(string $type): ?int
    {
        return match ($type) {
            'house', 'rental', 'cabin' => 5000,
            'car' => 2000,
            'boat' => 1000,
            default => null,
        };
    }

    private function getBaseMarketAmount(string $type, Asset $asset): ?int
    {
        if ($asset->market_amount && $asset->market_amount > 0) {
            return $asset->market_amount;
        }

        return match ($type) {
            'house' => 3000000,
            'rental' => 2000000,
            'cabin' => 1500000,
            'equityfund', 'bondfund' => 500000,
            'stock' => 100000,
            'crypto' => 50000,
            'cash', 'bank' => 100000,
            'car' => 300000,
            'boat' => 200000,
            default => null,
        };
    }

    private function getBaseMortgageAmount(string $type, Asset $asset): ?int
    {
        return match ($type) {
            'house' => 1500000,
            'rental' => 1000000,
            'cabin' => 800000,
            default => null,
        };
    }

    private function getIncomeDescription(string $type): ?string
    {
        return match ($type) {
            'salary' => 'Monthly salary income',
            'rental' => 'Monthly rental income from property',
            'dividend' => 'Quarterly dividend payments',
            'interest' => 'Annual interest income',
            default => null,
        };
    }

    private function getExpenseDescription(string $type): ?string
    {
        return match ($type) {
            'house', 'rental', 'cabin' => 'Property taxes, insurance, maintenance',
            'car' => 'Insurance, fuel, maintenance',
            'boat' => 'Insurance, fuel, maintenance, mooring',
            default => null,
        };
    }

    private function getMortgageDescription(string $type): ?string
    {
        return match ($type) {
            'house' => 'Primary residence mortgage',
            'rental' => 'Investment property mortgage',
            'cabin' => 'Vacation home mortgage',
            default => null,
        };
    }
}

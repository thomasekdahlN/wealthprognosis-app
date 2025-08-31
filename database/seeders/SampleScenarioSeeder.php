<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetExpense;
use App\Models\AssetIncome;
use App\Models\AssetMortgage;
use App\Models\Scenario;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleScenarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user (Thomas)
        $user = User::where('email', 'thomas@ekdahl.no')->first();

        if (! $user) {
            $this->command->error('User thomas@ekdahl.no not found. Please run UserSeeder first.');

            return;
        }

        // Create a sample scenario based on the README example
        $scenario = Scenario::create([
            'user_id' => $user->id,
            'name' => 'Thomas - Realistic Scenario',
            'description' => 'Sample wealth prognosis scenario based on README example',
            'birth_year' => 1974,
            'prognosis_year' => 2025,
            'pension_official_year' => 67,
            'pension_wish_year' => 63,
            'death_year' => 85,
            'export_start_year' => 2024,
            'is_active' => true,
            'country_code' => 'no',
            'currency' => 'NOK',
        ]);

        $this->createSampleAssets($scenario);

        $this->command->info("Created sample scenario for {$user->name}");
    }

    private function createSampleAssets(Scenario $scenario): void
    {
        // Get the user and team from the scenario
        $user = $scenario->user;
        $team = $scenario->team;
        // 1. Income Asset
        $incomeAsset = Asset::create([
            'scenario_id' => $scenario->id,
            'user_id' => $user->id,
            'team_id' => $team?->id,
            'name' => 'Salary Income',
            'description' => 'Monthly salary income',
            'asset_type' => 'salary',
            'group' => 'private',
            'tax_type' => 'salary',
            'is_active' => true,
            'sort_order' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => hash('sha256', 'salary_income_created'),
            'updated_checksum' => hash('sha256', 'salary_income_updated'),
        ]);

        AssetIncome::create([
            'asset_id' => $incomeAsset->id,
            'user_id' => $user->id,
            'team_id' => $team?->id,
            'year' => 2024,
            'name' => 'Monthly Salary',
            'description' => 'Monthly salary income',
            'amount' => 40000,
            'factor' => 12,
            'change_rate_type' => 'kpi',
            'repeat' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => hash('sha256', 'monthly_salary_created'),
            'updated_checksum' => hash('sha256', 'monthly_salary_updated'),
        ]);

        AssetExpense::create([
            'asset_id' => $incomeAsset->id,
            'user_id' => $user->id,
            'team_id' => $team?->id,
            'year' => 2024,
            'name' => 'Living Expenses',
            'description' => 'Monthly living expenses',
            'amount' => 15000,
            'factor' => 12,
            'change_rate_type' => 'kpi',
            'repeat' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => hash('sha256', 'living_expenses_created'),
            'updated_checksum' => hash('sha256', 'living_expenses_updated'),
        ]);

        // 2. House Asset
        $houseAsset = Asset::create([
            'scenario_id' => $scenario->id,
            'user_id' => $user->id,
            'team_id' => $team?->id,
            'name' => 'Primary Residence',
            'description' => 'Main house where I live',
            'asset_type' => 'house',
            'group' => 'private',
            'tax_type' => 'house',
            'is_active' => true,
            'market_amount' => 3000000,
            'acquisition_amount' => 2500000,
            'change_rate_type' => 'house',
            'sort_order' => 2,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => hash('sha256', 'primary_residence_created'),
            'updated_checksum' => hash('sha256', 'primary_residence_updated'),
        ]);

        AssetExpense::create([
            'asset_id' => $houseAsset->id,
            'user_id' => $user->id,
            'team_id' => $team?->id,
            'year' => 2024,
            'name' => 'House Expenses',
            'description' => 'Municipal fees, insurance, electricity, property tax',
            'amount' => 7300,
            'factor' => 12,
            'change_rate_type' => 'kpi',
            'repeat' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => hash('sha256', 'house_expenses_created'),
            'updated_checksum' => hash('sha256', 'house_expenses_updated'),
        ]);

        AssetMortgage::create([
            'asset_id' => $houseAsset->id,
            'user_id' => $user->id,
            'team_id' => $team?->id,
            'year' => 2024,
            'name' => 'House Mortgage',
            'description' => 'Primary residence mortgage',
            'amount' => 1500000,
            'interest_rate' => 5.5,
            'interest_rate_type' => 'interest',
            'years' => 20,
            'fee_amount' => 600,
            'tax_deductible_rate' => 22,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => hash('sha256', 'house_mortgage_created'),
            'updated_checksum' => hash('sha256', 'house_mortgage_updated'),
        ]);

        // 3. Equity Fund Asset
        $equityFundAsset = Asset::create([
            'scenario_id' => $scenario->id,
            'user_id' => $user->id,
            'team_id' => $team?->id,
            'name' => 'Global Equity Fund',
            'description' => 'Diversified global equity fund investment',
            'asset_type' => 'equityfund',
            'group' => 'private',
            'tax_type' => 'equityfund',
            'is_active' => true,
            'market_amount' => 500000,
            'acquisition_amount' => 400000,
            'change_rate_type' => 'equityfund',
            'sort_order' => 3,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => hash('sha256', 'global_equity_fund_created'),
            'updated_checksum' => hash('sha256', 'global_equity_fund_updated'),
        ]);

        AssetIncome::create([
            'asset_id' => $equityFundAsset->id,
            'user_id' => $user->id,
            'team_id' => $team?->id,
            'year' => 2024,
            'name' => 'Monthly Investment',
            'description' => 'Monthly investment in equity fund',
            'amount' => 10000,
            'factor' => 12,
            'change_rate_type' => 'zero',
            'repeat' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => hash('sha256', 'monthly_investment_created'),
            'updated_checksum' => hash('sha256', 'monthly_investment_updated'),
        ]);

        // 4. Cash Asset
        Asset::create([
            'scenario_id' => $scenario->id,
            'user_id' => $user->id,
            'team_id' => $team?->id,
            'name' => 'Emergency Fund',
            'description' => 'Cash emergency fund',
            'asset_type' => 'cash',
            'group' => 'private',
            'tax_type' => 'cash',
            'is_active' => true,
            'market_amount' => 100000,
            'change_rate_type' => 'cash',
            'sort_order' => 4,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => hash('sha256', 'emergency_fund_created'),
            'updated_checksum' => hash('sha256', 'emergency_fund_updated'),
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetYear;
use App\Models\Prognosis;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssetConfigurationAndYearTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_asset_configuration_assets_and_yearly_data_with_audit_fields(): void
    {
        $user = User::factory()->create();
        $team = Team::create([
            'name' => 'Test Team',
            'owner_id' => $user->id,
            'is_active' => true,
        ]);

        // Create a prognosis row (no Eloquent model in app; use DB)
        $prognosisId = DB::table('prognoses')->insertGetId([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'code' => 'realistic',
            'label' => 'Test Prognosis',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $owner = AssetConfiguration::create([
            'name' => 'Example Advanced Wealth Prognosis',
            'birth_year' => 1985,
            'prognose_age' => 50,
            'pension_official_age' => 67,
            'pension_wish_age' => 63,
            'death_age' => 80,
            'export_start_age' => 2020,
            'risk_tolerance' => 'moderate',
            'user_id' => $user->id,
            'team_id' => $team->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => 'chk1',
            'updated_checksum' => 'chk2',
        ]);

        $asset = Asset::create([
            'asset_configuration_id' => $owner->id,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'code' => 'salary-001',
            'name' => 'Lønn',
            'description' => 'Lønn',
            'asset_type' => 'salary',
            'group' => 'private',
            'tax_type' => 'salary',
            'tax_property' => null,
            'tax_country' => 'no',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => 'chk3',
            'updated_checksum' => 'chk4',
        ]);

        $year = AssetYear::create([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'year' => 2024,
            'asset_id' => $asset->id,
            'asset_configuration_id' => $owner->id,
            // unified description replaces income/expense/asset/mortgage descriptions
            'description' => 'Income | Expense | Asset | Mortgage',
            'income_amount' => 40000,
            'income_rule' => null,
            'income_transfer' => null,
            'income_source' => 'salary-001.$year.income.amount',
            'income_changerate' => 'changerates.kpi',
            'income_repeat' => true,
                        'expence_amount' => 15000,
            'expence_factor' => 'monthly',
            'expence_rule' => null,
            'expence_transfer' => null,
            'expence_source' => 'manual',
            'expence_changerate' => 'changerates.kpi',
            'expence_repeat' => true,
                        'asset_market_amount' => 0,
            'asset_acquisition_amount' => 0,
            'asset_equity_amount' => 0,
            'asset_taxable_initial_amount' => 0,
            'asset_paid_amount' => 1,
            'asset_changerate' => 'changerates.kpi',
            'asset_rule' => null,
            'asset_transfer' => null,
            'asset_source' => 'manual',
            'asset_repeat' => true,
                        'mortgage_amount' => 0,
            'mortgage_years' => 0,
            'mortgage_interest' => '5.00',
            'mortgage_interest_only_years' => 0,
            'mortgage_extra_downpayment_amount' => '0',
            'mortgage_gebyr' => 600,
            'mortgage_tax' => 22,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => 'chk5',
            'updated_checksum' => 'chk6',
        ]);

        $this->assertSame(1, $owner->assets()->count());
        $this->assertSame(1, $asset->years()->count());

        // Basic schema expectations
        $this->assertTrue(\Schema::hasTable('asset_configurations'));
        $this->assertTrue(\Schema::hasTable('asset_years'));
    }
}

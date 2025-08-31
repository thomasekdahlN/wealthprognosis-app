<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetType;
use App\Models\PrognosisType;
use App\Models\TaxType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveAuditFieldsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::create([
            'name' => 'Test Team',
            'description' => 'A test team',
            'owner_id' => $this->user->id,
            'is_active' => true,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', 'team_created'),
            'updated_checksum' => hash('sha256', 'team_updated'),
        ]);
    }

    public function test_teams_table_has_all_audit_fields()
    {
        $this->assertEquals($this->user->id, $this->team->owner_id);
        $this->assertEquals($this->user->id, $this->team->created_by);
        $this->assertEquals($this->user->id, $this->team->updated_by);
        $this->assertEquals(hash('sha256', 'team_created'), $this->team->created_checksum);
        $this->assertEquals(hash('sha256', 'team_updated'), $this->team->updated_checksum);
        $this->assertNotNull($this->team->created_at);
        $this->assertNotNull($this->team->updated_at);
    }

    public function test_prognoses_table_has_all_audit_fields()
    {
        $prognosis = PrognosisType::create([
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
            'code' => 'realistic',
            'label' => 'Test Prognosis',
            'description' => 'A test prognosis',
            'is_active' => true,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', 'scenario_created'),
            'updated_checksum' => hash('sha256', 'scenario_updated'),
        ]);

        $this->assertEquals($this->user->id, $prognosis->user_id);
        $this->assertEquals($this->team->id, $prognosis->team_id);
        $this->assertEquals($this->user->id, $prognosis->created_by);
        $this->assertEquals($this->user->id, $prognosis->updated_by);
        $this->assertNotNull($prognosis->created_checksum);
        $this->assertNotNull($prognosis->updated_checksum);
    }

    public function test_assets_table_has_all_audit_fields()
    {
        $prognosis = PrognosisType::create([
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
            'code' => 'realistic',
            'label' => 'Test Prognosis',
        ]);

        $asset = Asset::create([
            'prognosis_id' => $prognosis->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
            'name' => 'Test Asset',
            'asset_type' => 'stock',
            'tax_type' => 'stock',
            'group' => 'private',
            'market_amount' => 100000,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', 'asset_created'),
            'updated_checksum' => hash('sha256', 'asset_updated'),
        ]);

        $this->assertEquals($this->user->id, $asset->user_id);
        $this->assertEquals($this->team->id, $asset->team_id);
        $this->assertEquals($this->user->id, $asset->created_by);
        $this->assertEquals($this->user->id, $asset->updated_by);
        $this->assertNotNull($asset->created_checksum);
        $this->assertNotNull($asset->updated_checksum);
    }

    public function test_asset_types_table_has_all_audit_fields()
    {
        $assetType = AssetType::create([
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
            'type' => 'test_asset_type',
            'name' => 'Test Asset Type',
            'category' => 'Test',
            'icon' => 'heroicon-o-star',
            'color' => 'blue',
            'is_active' => true,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'checksum_created' => hash('sha256', 'asset_type_created'),
            'checksum_updated' => hash('sha256', 'asset_type_updated'),
        ]);

        $this->assertEquals($this->user->id, $assetType->user_id);
        $this->assertEquals($this->team->id, $assetType->team_id);
        $this->assertEquals($this->user->id, $assetType->created_by);
        $this->assertEquals($this->user->id, $assetType->updated_by);
        $this->assertNotNull($assetType->created_checksum);
        $this->assertNotNull($assetType->updated_checksum);
    }

    public function test_asset_categories_table_has_all_audit_fields()
    {
        $category = AssetCategory::create([
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
            'code' => 'test_category',
            'name' => 'Test Category',
            'description' => 'A test category',
            'icon' => 'heroicon-o-folder',
            'color' => 'green',
            'is_active' => true,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', 'category_created'),
            'updated_checksum' => hash('sha256', 'category_updated'),
        ]);

        $this->assertEquals($this->user->id, $category->user_id);
        $this->assertEquals($this->team->id, $category->team_id);
        $this->assertEquals($this->user->id, $category->created_by);
        $this->assertEquals($this->user->id, $category->updated_by);
        $this->assertNotNull($category->created_checksum);
        $this->assertNotNull($category->updated_checksum);
    }

    public function test_tax_types_table_has_all_audit_fields()
    {
        $taxType = TaxType::create([
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
            'type' => 'test_tax',
            'name' => 'Test Tax Type',
            'description' => 'A test tax type',
            'default_rate' => 25.0000,
            'is_active' => true,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', 'tax_type_created'),
            'updated_checksum' => hash('sha256', 'tax_type_updated'),
        ]);

        $this->assertEquals($this->user->id, $taxType->user_id);
        $this->assertEquals($this->team->id, $taxType->team_id);
        $this->assertEquals($this->user->id, $taxType->created_by);
        $this->assertEquals($this->user->id, $taxType->updated_by);
        $this->assertNotNull($taxType->created_checksum);
        $this->assertNotNull($taxType->updated_checksum);
    }

    public function test_all_tables_have_proper_indexes()
    {
        // This test verifies that the database schema was created correctly
        // If the migrations failed, this test would fail too

        $tables = [
            'teams', 'prognoses', 'assets', 'asset_types',
            'asset_categories', 'tax_types', 'asset_owners',
            'asset_years', 'tax_configurations', 'prognosis_change_rates',
            'ai_instructions',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(\Schema::hasTable($table), "Table {$table} should exist");

            // Check for audit fields
            $this->assertTrue(\Schema::hasColumn($table, 'created_at'), "Table {$table} should have created_at");
            $this->assertTrue(\Schema::hasColumn($table, 'updated_at'), "Table {$table} should have updated_at");
            $this->assertTrue(\Schema::hasColumn($table, 'created_by'), "Table {$table} should have created_by");
            $this->assertTrue(\Schema::hasColumn($table, 'updated_by'), "Table {$table} should have updated_by");

            // Check for checksum fields (now consistent across all tables)
            $this->assertTrue(\Schema::hasColumn($table, 'created_checksum'), "Table {$table} should have created_checksum");
            $this->assertTrue(\Schema::hasColumn($table, 'updated_checksum'), "Table {$table} should have updated_checksum");

            // Check for user/team fields (except teams table which has owner_id instead of user_id)
            if ($table !== 'teams') {
                $this->assertTrue(\Schema::hasColumn($table, 'user_id'), "Table {$table} should have user_id");
                $this->assertTrue(\Schema::hasColumn($table, 'team_id'), "Table {$table} should have team_id");
            }
        }
    }
}

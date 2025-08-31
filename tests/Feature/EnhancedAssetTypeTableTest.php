<?php

namespace Tests\Feature;

use App\Models\AssetType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnhancedAssetTypeTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_type_has_new_fields()
    {
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);
        $this->artisan('db:seed', ['--class' => 'AssetTypeSeeder']);

        $assetType = AssetType::where('type', 'equityfund')->first();
        $this->assertNotNull($assetType);

        // Test new fields exist
        $this->assertNotNull($assetType->color);
        $this->assertEquals('success', $assetType->color);
        $this->assertNotNull($assetType->created_at);
        $this->assertNotNull($assetType->updated_at);

        // Test audit fields exist (even if null)
        $this->assertTrue(array_key_exists('created_by', $assetType->getAttributes()));
        $this->assertTrue(array_key_exists('updated_by', $assetType->getAttributes()));
    }

    public function test_asset_type_audit_relationships()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $assetType = AssetType::factory()->create([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'type' => 'test_asset',
            'name' => 'Test Asset',
            'category' => 'Test Category',
            'icon' => 'heroicon-o-star',
            'color' => 'blue',
            'is_active' => true,
            'is_private' => true,
            'is_company' => false,
            'is_tax_optimized' => false,
            'sort_order' => 99,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // Test relationships work
        $this->assertEquals($user->id, $assetType->created_by);
        $this->assertEquals($user->id, $assetType->updated_by);
        $this->assertEquals($user->name, $assetType->createdBy->name);
        $this->assertEquals($user->name, $assetType->updatedBy->name);
    }

    public function test_asset_type_color_field_works()
    {
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);
        $this->artisan('db:seed', ['--class' => 'AssetTypeSeeder']);

        // Test different colors are set
        $equityFund = AssetType::where('type', 'equityfund')->first();
        $this->assertEquals('success', $equityFund->color);

        $stock = AssetType::where('type', 'stock')->first();
        $this->assertEquals('info', $stock->color);

        $ask = AssetType::where('type', 'ask')->first();
        $this->assertEquals('warning', $ask->color);
    }

    public function test_asset_type_icon_rendering_data_available()
    {
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);
        $this->artisan('db:seed', ['--class' => 'AssetTypeSeeder']);

        $assetTypes = AssetType::whereNotNull('icon')->take(5)->get();

        foreach ($assetTypes as $assetType) {
            // Verify icon field contains heroicon class
            $this->assertStringContainsString('heroicon-', $assetType->icon);

            // Verify we have all data needed for icon rendering
            $this->assertNotNull($assetType->icon);
            $this->assertNotNull($assetType->color);
            $this->assertNotNull($assetType->name);
        }
    }

    public function test_asset_type_table_columns_data_integrity()
    {
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);
        $this->artisan('db:seed', ['--class' => 'AssetTypeSeeder']);

        $assetType = AssetType::first();

        // Test all table columns have data or proper defaults
        $this->assertNotNull($assetType->type);
        $this->assertNotNull($assetType->name);
        $this->assertNotNull($assetType->icon);
        $this->assertNotNull($assetType->color);
        $this->assertNotNull($assetType->created_at);
        $this->assertNotNull($assetType->updated_at);
        $this->assertIsBool($assetType->is_private);
        $this->assertIsBool($assetType->is_company);
        $this->assertIsBool($assetType->is_tax_optimized);
        $this->assertIsBool($assetType->is_active);
        $this->assertIsInt($assetType->sort_order);
    }
}

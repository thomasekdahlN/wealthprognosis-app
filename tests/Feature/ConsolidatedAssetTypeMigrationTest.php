<?php

namespace Tests\Feature;

use App\Models\AssetCategory;
use App\Models\AssetType;
use App\Models\TaxType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsolidatedAssetTypeMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_consolidated_migration_creates_all_fields()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $this->artisan('db:seed', ['--class' => 'TaxTypeSeeder']);
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);
        $this->artisan('db:seed', ['--class' => 'AssetTypeSeeder']);

        $assetType = AssetType::factory()->create([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'type' => 'test_consolidated',
            'name' => 'Test Consolidated Asset',
            'description' => 'Testing all consolidated fields',
            'category' => 'Test Category',
            'icon' => 'heroicon-o-star',
            'color' => 'blue',
            'is_active' => true,
            'is_private' => true,
            'is_company' => false,
            'is_tax_optimized' => true,
            'is_liquid' => true,
            'sort_order' => 99,
            'created_checksum' => 'test_checksum_created',
            'updated_checksum' => 'test_checksum_updated',
        ]);

        // Test all fields exist and are properly set
        $this->assertEquals('test_consolidated', $assetType->type);
        $this->assertEquals('Test Consolidated Asset', $assetType->name);
        $this->assertEquals('blue', $assetType->color);
        $this->assertTrue($assetType->is_active);
        $this->assertTrue($assetType->is_private);
        $this->assertFalse($assetType->is_company);
        $this->assertTrue($assetType->is_tax_optimized);
        $this->assertTrue($assetType->is_liquid);
        $this->assertEquals('test_checksum_created', $assetType->created_checksum);
        $this->assertEquals('test_checksum_updated', $assetType->updated_checksum);
    }

    public function test_liquid_functionality()
    {
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);
        $this->artisan('db:seed', ['--class' => 'AssetTypeSeeder']);

        // Update some assets to be Liquid
        AssetType::whereIn('type', ['equityfund', 'stock'])->update(['is_liquid' => true]);

        // Test Liquid scope
        $liquidAssets = AssetType::liquid()->get();
        $this->assertGreaterThanOrEqual(2, $liquidAssets->count());

        $types = $liquidAssets->pluck('type')->toArray();
        $this->assertContains('equityfund', $types);
        $this->assertContains('stock', $types);
    }

    public function test_all_relationships_work_together()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $this->artisan('db:seed', ['--class' => 'TaxTypeSeeder']);
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);
        $this->artisan('db:seed', ['--class' => 'AssetTypeSeeder']);

        // Get related models (use first available ones if specific ones don't exist)
        $taxType = TaxType::first();
        $category = AssetCategory::first();

        // Create asset type with all relationships (using tax_type string field)
        $assetType = AssetType::factory()->create([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'type' => 'test_relationships',
            'name' => 'Test Relationships',
            'category' => 'Investment Funds',
            'icon' => 'heroicon-o-chart-bar',
            'color' => 'success',
            'is_active' => true,
            'is_liquid' => true,
            'tax_type' => $taxType?->type,
            'asset_category_id' => $category?->id,
        ]);

        // Test all relationships
        $this->assertEquals($taxType->name, $assetType->taxType->name);
        $this->assertEquals($category->name, $assetType->assetCategory->name);
        $this->assertTrue($taxType->assetTypes->contains($assetType));
        $this->assertTrue($category->assetTypes->contains($assetType));
    }

    public function test_checksum_fields_work()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);

        $createdChecksum = hash('sha256', 'test_created');
        $updatedChecksum = hash('sha256', 'test_updated');

        $assetType = AssetType::factory()->create([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'type' => 'test_checksum',
            'name' => 'Test Checksum',
            'category' => 'Test',
            'created_checksum' => $createdChecksum,
            'updated_checksum' => $updatedChecksum,
        ]);

        $this->assertEquals($createdChecksum, $assetType->created_checksum);
        $this->assertEquals($updatedChecksum, $assetType->updated_checksum);

        // Test that checksums are in fillable array
        $this->assertTrue(in_array('created_checksum', $assetType->getFillable()));
        $this->assertTrue(in_array('updated_checksum', $assetType->getFillable()));
    }

    public function test_liquid_form_and_table_integration()
    {
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);
        $this->artisan('db:seed', ['--class' => 'AssetTypeSeeder']);

        // Test that Liquid field is in fillable array
        $assetType = new AssetType;
        $this->assertTrue(in_array('is_liquid', $assetType->getFillable()));

        // Test that Liquid field is cast as boolean
        $casts = $assetType->getCasts();
        $this->assertEquals('boolean', $casts['is_liquid']);

        // Test creating asset with Liquid flag
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $fireSellableAsset = AssetType::factory()->create([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'type' => 'fire_test',
            'name' => 'FIRE Test Asset',
            'category' => 'Investment',
            'is_liquid' => true,
        ]);

        $this->assertTrue($fireSellableAsset->is_liquid);
    }

    public function test_consolidated_migration_no_duplicate_columns()
    {
        // This test ensures the migration runs without errors
        // If there were duplicate columns, the migration would have failed
        $this->assertTrue(true);

        // Verify the table structure has all expected columns
        $columns = \Schema::getColumnListing('asset_types');

        $expectedColumns = [
            'id', 'user_id', 'team_id', 'type', 'name', 'description', 'category', 'icon', 'color',
            'is_active', 'is_private', 'is_company', 'is_tax_optimized', 'is_liquid',
            'sort_order', 'asset_category_id', 'tax_type', 'created_by', 'updated_by',
            'created_checksum', 'updated_checksum', 'created_at', 'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertContains($column, $columns, "Column {$column} should exist in asset_types table");
        }
    }
}

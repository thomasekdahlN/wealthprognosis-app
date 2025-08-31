<?php

namespace Tests\Feature;

use App\Models\AssetCategory;
use App\Models\AssetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_category_seeder_creates_expected_records()
    {
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);

        $this->assertDatabaseCount('asset_categories', 13);

        // Check specific categories exist
        $this->assertDatabaseHas('asset_categories', [
            'code' => 'investment_funds',
            'name' => 'Investment Funds',
            'color' => 'success',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('asset_categories', [
            'code' => 'securities',
            'name' => 'Securities',
            'color' => 'info',
            'is_active' => true,
        ]);
    }

    public function test_asset_type_has_new_fields()
    {
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);
        $this->artisan('db:seed', ['--class' => 'AssetTypeSeeder']);

        // Test that asset types have the new boolean fields
        $askAccount = AssetType::where('type', 'ask')->first();
        $this->assertNotNull($askAccount);
        $this->assertTrue($askAccount->is_private);
        $this->assertFalse($askAccount->is_company);
        $this->assertTrue($askAccount->is_tax_optimized);

        $stock = AssetType::where('type', 'stock')->first();
        $this->assertNotNull($stock);
        $this->assertTrue($stock->is_private);
        $this->assertTrue($stock->is_company);
        $this->assertFalse($stock->is_tax_optimized);
    }

    public function test_asset_type_scopes_work_correctly()
    {
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);
        $this->artisan('db:seed', ['--class' => 'AssetTypeSeeder']);

        // Test private scope
        $privateAssets = AssetType::private()->get();
        $this->assertGreaterThan(0, $privateAssets->count());

        // Test company scope
        $companyAssets = AssetType::company()->get();
        $this->assertGreaterThan(0, $companyAssets->count());

        // Test tax optimized scope
        $taxOptimizedAssets = AssetType::taxOptimized()->get();
        $this->assertGreaterThan(0, $taxOptimizedAssets->count());

        // Verify specific tax optimized assets
        $taxOptimizedTypes = $taxOptimizedAssets->pluck('type')->toArray();
        $this->assertContains('ask', $taxOptimizedTypes);
        $this->assertContains('ips', $taxOptimizedTypes);
        $this->assertContains('endowment', $taxOptimizedTypes);
    }

    public function test_asset_category_model_relationships()
    {
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);
        $this->artisan('db:seed', ['--class' => 'AssetTypeSeeder']);

        $category = AssetCategory::where('code', 'investment_funds')->first();
        $this->assertNotNull($category);

        // Test that the relationship method exists
        $this->assertTrue(method_exists($category, 'assetTypes'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\AssetCategory;
use App\Models\AssetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetCategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_category_model_relationships_work()
    {
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);
        $this->artisan('db:seed', ['--class' => 'AssetTypeSeeder']);

        // Update relationships
        $category = AssetCategory::where('code', 'investment_funds')->first();
        AssetType::where('category', 'Investment Funds')->update(['asset_category_id' => $category->id]);

        // Test relationship
        $this->assertGreaterThan(0, $category->assetTypes()->count());

        // Test reverse relationship
        $assetType = $category->assetTypes()->first();
        $this->assertEquals($category->id, $assetType->asset_category_id);
        $this->assertEquals($category->name, $assetType->assetCategory->name);
    }

    public function test_asset_category_scopes_work()
    {
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);

        // Test active scope
        $activeCategories = AssetCategory::active()->get();
        $this->assertGreaterThan(0, $activeCategories->count());

        // All seeded categories should be active
        $this->assertEquals(13, $activeCategories->count());

        // Test ordered scope
        $orderedCategories = AssetCategory::ordered()->get();
        $this->assertEquals($activeCategories->count(), $orderedCategories->count());

        // First category should have lowest sort_order
        $this->assertEquals(1, $orderedCategories->first()->sort_order);
    }

    public function test_asset_category_has_all_required_fields()
    {
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);

        $category = AssetCategory::first();

        // Test all required fields exist
        $this->assertNotNull($category->code);
        $this->assertNotNull($category->name);
        $this->assertNotNull($category->color);
        $this->assertNotNull($category->icon);
        $this->assertNotNull($category->sort_order);
        $this->assertIsBool($category->is_active);

        // Test specific values
        $investmentFunds = AssetCategory::where('code', 'investment_funds')->first();
        $this->assertEquals('Investment Funds', $investmentFunds->name);
        $this->assertEquals('success', $investmentFunds->color);
        $this->assertEquals('heroicon-o-chart-bar', $investmentFunds->icon);
        $this->assertTrue($investmentFunds->is_active);
    }

    public function test_all_expected_categories_are_seeded()
    {
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);

        $expectedCategories = [
            'investment_funds' => 'Investment Funds',
            'securities' => 'Securities',
            'real_assets' => 'Real Assets',
            'cash_equivalents' => 'Cash Equivalents',
            'alternative_investments' => 'Alternative Investments',
            'personal_assets' => 'Personal Assets',
            'pension_retirement' => 'Pension & Retirement',
            'income' => 'Income',
            'business' => 'Business',
            'insurance_protection' => 'Insurance & Protection',
            'debt_liabilities' => 'Debt & Liabilities',
            'special' => 'Special',
            'reference' => 'Reference',
        ];

        foreach ($expectedCategories as $code => $name) {
            $this->assertDatabaseHas('asset_categories', [
                'code' => $code,
                'name' => $name,
                'is_active' => true,
            ]);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\AssetType;
use App\Models\TaxType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetTypeTaxTypeRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_type_has_tax_type_relationship()
    {
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);

        // Get a tax type and assign it to an asset type
        $taxType = TaxType::where('type', 'realization')->first();
        $assetType = AssetType::where('type', 'stock')->first();

        $this->assertNotNull($taxType);
        $this->assertNotNull($assetType);

        // Assign tax type to asset type
        $assetType->update(['tax_type_id' => $taxType->id]);

        // Test the relationship
        $this->assertEquals($taxType->id, $assetType->tax_type_id);
        $this->assertEquals($taxType->name, $assetType->taxType->name);
        $this->assertTrue($taxType->assetTypes->contains($assetType));
    }

    public function test_asset_type_form_includes_tax_type_selection()
    {
        $this->artisan('db:seed', ['--class' => 'TaxTypeSeeder']);

        // Test that tax types are available for selection
        $taxTypes = TaxType::active()->ordered()->pluck('name', 'id');
        $this->assertGreaterThan(0, $taxTypes->count());

        // Verify specific tax types exist
        $this->assertTrue($taxTypes->contains('Income Tax'));
        $this->assertTrue($taxTypes->contains('Capital Gains Tax'));
    }

    public function test_asset_type_table_displays_tax_type()
    {
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);

        // Assign tax types to some asset types
        $realizationTax = TaxType::where('type', 'realization')->first();
        $incomeTax = TaxType::where('type', 'income')->first();

        $this->assertNotNull($realizationTax, 'Realization tax type should exist');
        $this->assertNotNull($incomeTax, 'Income tax type should exist');

        // Just test with one asset type that definitely exists
        AssetType::where('type', 'equityfund')->update(['tax_type_id' => $realizationTax->id]);

        // Test that asset types with tax types can be retrieved
        $equityAsset = AssetType::with('taxType')->where('type', 'equityfund')->first();

        $this->assertNotNull($equityAsset, 'Equity fund asset type should exist');
        $this->assertNotNull($equityAsset->taxType, 'Equity fund should have tax type');

        // With 1:1 mapping from config, the name comes from config/tax/tax_types.json
        $this->assertEquals('realization', $equityAsset->taxType->type);
        $this->assertEquals('Realization', $equityAsset->taxType->name);
    }

    public function test_tax_type_has_asset_types_relationship()
    {
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);

        $taxType = TaxType::where('type', 'realization')->first();

        // Assign multiple asset types to this tax type
        AssetType::whereIn('type', ['stock', 'equityfund', 'crypto'])->update(['tax_type_id' => $taxType->id]);

        // Test the reverse relationship
        $assetTypes = $taxType->assetTypes;
        $this->assertGreaterThanOrEqual(3, $assetTypes->count());

        $types = $assetTypes->pluck('type')->toArray();
        $this->assertContains('stock', $types);
        $this->assertContains('equityfund', $types);
        $this->assertContains('crypto', $types);
    }

    public function test_asset_type_can_exist_without_tax_type()
    {
        // Create an asset type without tax type to test nullable relationship
        $assetType = AssetType::factory()->create([
            'type' => 'test_no_tax',
            'name' => 'Test Asset Without Tax',
            'tax_type_id' => null,
        ]);

        $this->assertNotNull($assetType);
        $this->assertNull($assetType->tax_type_id);
        $this->assertNull($assetType->taxType);
    }

    public function test_asset_type_model_has_tax_type_field()
    {
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);

        $assetType = AssetType::factory()->create([
            'type' => 'test_asset',
            'name' => 'Test Asset',
            'category' => 'Test Category',
            'tax_type_id' => null,
        ]);

        // Test that tax_type_id field exists and is fillable
        $this->assertTrue(in_array('tax_type_id', $assetType->getFillable()));
        $this->assertNull($assetType->tax_type_id);
    }
}

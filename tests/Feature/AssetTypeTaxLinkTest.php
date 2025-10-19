<?php

namespace Tests\Feature;

use App\Models\AssetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetTypeTaxLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_asset_types_have_tax_type(): void
    {
        // Ensure full seeding (uses DatabaseSeeder order)
        $this->seed();

        $total = AssetType::count();
        $withTax = AssetType::query()->whereNotNull('tax_type_id')->count();

        $this->assertSame($total, $withTax, 'All asset types should be linked to a tax type');
    }
}

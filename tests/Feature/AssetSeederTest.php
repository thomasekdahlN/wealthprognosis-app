<?php

namespace Tests\Feature;

use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_base_assets_have_asset_type(): void
    {
        // Run seeders since RefreshDatabase only runs migrations
        $this->seed();

        $this->assertGreaterThan(0, Asset::count());

        $asset = Asset::first();
        $this->assertNotNull($asset->asset_type);
    }
}

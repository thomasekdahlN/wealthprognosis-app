<?php

namespace Tests\Feature\Pages;

use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetYear;
use App\Models\PrognosisType as Prognosis;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrilldownPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user and a prognosis to satisfy FKs
        $this->user = User::factory()->create();

        // Create a prognosis for this specific user
        $this->prognosis = Prognosis::create([
            'user_id' => $this->user->id,
            'code' => 'realistic',
            'label' => 'Realistic',
            'icon' => 'heroicon-o-check-badge',
            'color' => 'success',
            'description' => 'Standard market expectations based on historical data and current indicators.',
            'public' => true,
            'is_active' => true,
            'team_id' => null,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
        $this->actingAs($this->user);
        Filament::setCurrentPanel('admin');

        $this->owner = AssetConfiguration::create([
            'name' => 'Owner A',
            'user_id' => $this->user->id,
        ]);

        $this->asset = Asset::factory()->create([
            'name' => 'Test Asset',
            'asset_type' => 'salary',
            'group' => 'private',
            'tax_country' => 'no',
            'is_active' => true,
            'user_id' => $this->user->id,
            'asset_owner_id' => $this->owner->id,
        ]);

        $this->year = AssetYear::create([
            'user_id' => $this->user->id,
            'asset_id' => $this->asset->id,
            'asset_owner_id' => $this->owner->id,
            'year' => 2024,
        ]);
    }

    public function test_owner_index_is_accessible(): void
    {
        $url = \App\Filament\Resources\AssetOwners\AssetOwnerResource::getUrl('index');
        $response = $this->withoutMiddleware()->get($url);
        $response->assertStatus(200);
    }

    public function test_owner_row_links_to_assets_list(): void
    {
        $url = route('filament.admin.resources.asset-owners.assets', ['record' => $this->owner->getKey()]);
        $response = $this->withoutMiddleware()->get($url);
        $response->assertStatus(200);
    }

    public function test_asset_row_links_to_asset_years_list(): void
    {
        $url = route('filament.admin.resources.asset-years.index', ['owner' => $this->owner->getKey(), 'asset' => $this->asset->getKey()]);
        $response = $this->withoutMiddleware()->get($url);
        $response->assertStatus(200);
    }

    public function test_asset_year_row_links_to_edit(): void
    {
        $url = route('filament.admin.resources.asset-years.edit', ['record' => $this->year->getKey()]);
        $response = $this->withoutMiddleware()->get($url);
        $response->assertStatus(200);
    }
}

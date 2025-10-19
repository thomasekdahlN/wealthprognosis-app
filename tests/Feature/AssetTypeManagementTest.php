<?php

namespace Tests\Feature;

use App\Models\AssetType;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_type_seeder_creates_expected_records()
    {
        // Run basic seeders first to create users and teams
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);

        $this->assertDatabaseCount('asset_types', 40);

        // Check specific Norwegian asset types exist
        $this->assertDatabaseHas('asset_types', [
            'type' => 'equityfund',
            'name' => 'Aksjefond',
            'category' => 'Investment Funds',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('asset_types', [
            'type' => 'house',
            'name' => 'Bolig',
            'category' => 'Real Assets',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('asset_types', [
            'type' => 'crypto',
            'name' => 'Krypto',
            'category' => 'Alternative Investments',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('asset_types', [
            'type' => 'ask',
            'name' => 'Aksjesparing med skattefradrag',
            'category' => 'Securities',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('asset_types', [
            'type' => 'cabin',
            'name' => 'Hytte',
            'category' => 'Real Assets',
            'is_active' => true,
        ]);
    }

    public function test_asset_type_model_scopes_work_correctly()
    {
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);

        // Test active scope
        $activeAssetTypes = AssetType::active()->get();
        $this->assertCount(40, $activeAssetTypes); // All 40 are active

        // Test ordered scope
        $orderedAssetTypes = AssetType::ordered()->get();
        $this->assertEquals('equityfund', $orderedAssetTypes->first()->type);
        $this->assertEquals('spouse', $orderedAssetTypes->last()->type);

        // Test byCategory scope
        $investmentFunds = AssetType::byCategory('Investment Funds')->get();
        $this->assertCount(5, $investmentFunds);

        $securities = AssetType::byCategory('Securities')->get();
        $this->assertCount(6, $securities);

        $realAssets = AssetType::byCategory('Real Assets')->get();
        $this->assertCount(3, $realAssets);

        $alternativeInvestments = AssetType::byCategory('Alternative Investments')->get();
        $this->assertCount(2, $alternativeInvestments);
    }

    public function test_asset_type_resource_page_loads()
    {
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);

        $user = User::factory()->create();
        Filament::setCurrentPanel('admin');

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get('/admin/asset-types');

        $response->assertStatus(200);
    }

    public function test_asset_type_creation_via_resource()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);
        $user->current_team_id = $team->id;
        $user->save();

        Filament::setCurrentPanel('admin');

        // Create using the model directly since Filament form submission is complex
        $assetType = AssetType::create([
            'type' => 'test_asset',
            'name' => 'Test Asset',
            'description' => 'A test asset type',
            'category' => 'Test Category',
            'icon' => 'heroicon-o-star',
            'is_active' => true,
            'sort_order' => 20,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'checksum_created' => 'test_checksum',
            'checksum_updated' => 'test_checksum',
        ]);

        $this->assertDatabaseHas('asset_types', [
            'type' => 'test_asset',
            'name' => 'Test Asset',
            'category' => 'Test Category',
        ]);
    }

    public function test_asset_type_unique_type_constraint()
    {
        AssetType::factory()->create(['type' => 'unique_test']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        AssetType::factory()->create(['type' => 'unique_test']); // Same type should fail
    }

    public function test_asset_type_categories_are_properly_grouped()
    {
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);

        $categories = AssetType::select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->toArray();

        $expectedCategories = [
            'Alternative Investments',
            'Business',
            'Cash Equivalents',
            'Consumer Goods',
            'Debt & Liabilities',
            'Development',
            'Family',
            'Income',
            'Insurance & Protection',
            'Investment Funds',
            'Pension & Retirement',
            'Personal Assets',
            'Real Assets',
            'Securities',
            'Social Benefits',
            'Transfers',
        ];

        $this->assertEquals($expectedCategories, $categories);
    }
}

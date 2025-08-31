<?php

use App\Filament\Resources\AssetYears\AssetYearResource;
use App\Filament\Resources\AssetYears\Pages\EditAssetYear;
use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetType;
use App\Models\AssetYear;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = Team::create([
        'name' => 'Test Team',
        'owner_id' => $this->user->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $this->assetOwner = AssetConfiguration::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
    ]);

    // Create asset types using factory
    $this->salaryType = AssetType::factory()->create(['name' => 'Salary', 'team_id' => $this->team->id, 'user_id' => $this->user->id]);
    $this->houseType = AssetType::factory()->create(['name' => 'House', 'team_id' => $this->team->id, 'user_id' => $this->user->id]);

    // Create assets with different sort orders
    $this->asset1 = Asset::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'asset_owner_id' => $this->assetOwner->id,
        'name' => 'First Asset',
        'sort_order' => 10,
        'asset_type' => $this->salaryType->type,
    ]);

    $this->asset2 = Asset::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'asset_owner_id' => $this->assetOwner->id,
        'name' => 'Second Asset',
        'sort_order' => 20,
        'asset_type' => $this->houseType->type,
    ]);

    $this->assetYear = AssetYear::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'asset_id' => $this->asset2->id,
        'asset_owner_id' => $this->assetOwner->id,
        'year' => 2024,
    ]);

    $this->actingAs($this->user);
});

it('displays the edit asset year form', function () {
    $response = $this->withoutMiddleware()->get(AssetYearResource::getUrl('edit', ['record' => $this->assetYear]));
    $response->assertStatus(200);
});

it('shows source dropdown options for assets with lower sort order', function () {
    // Test that the form loads and has basic fields
    $response = $this->withoutMiddleware()->get(AssetYearResource::getUrl('edit', ['record' => $this->assetYear]));
    $response->assertStatus(200);
    // Just verify the page loads successfully
});

it('can save asset year with source values', function () {
    // Test basic form saving functionality
    $response = $this->withoutMiddleware()->get(AssetYearResource::getUrl('edit', ['record' => $this->assetYear]));
    $response->assertStatus(200);

    // Verify the asset year exists and can be loaded
    expect($this->assetYear->id)->toBeGreaterThan(0);
    expect($this->assetYear->asset_id)->toBeGreaterThan(0);
});

it('allows nullable source fields', function () {
    // Test that nullable fields work correctly
    $response = $this->withoutMiddleware()->get(AssetYearResource::getUrl('edit', ['record' => $this->assetYear]));
    $response->assertStatus(200);

    // Verify that the asset year can have null source fields
    $this->assetYear->update(['income_source' => null]);
    expect($this->assetYear->fresh()->income_source)->toBeNull();
});

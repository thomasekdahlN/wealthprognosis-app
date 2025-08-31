<?php

use App\Filament\Resources\AssetYears\AssetYearResource;
use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetYear;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('asset year form loads correctly', function () {
    // Create user and team
    $user = User::factory()->create();
    $team = Team::create([
        'name' => 'Test Team',
        'owner_id' => $user->id,
        'is_active' => true,
    ]);

    // Create asset owner
    $owner = AssetConfiguration::create([
        'name' => 'Test Owner',
        'birth_year' => 1985,
        'user_id' => $user->id,
        'team_id' => $team->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    // Create asset using factory
    $asset = Asset::factory()->create([
        'asset_owner_id' => $owner->id,
        'user_id' => $user->id,
        'team_id' => $team->id,
        'name' => 'Test Asset',
        'asset_type' => 'salary',
        'sort_order' => 100,
    ]);

    // Create asset year
    $assetYear = AssetYear::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'asset_id' => $asset->id,
        'asset_owner_id' => $owner->id,
        'year' => 2024,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user);

    // Test that the edit form loads
    $response = $this->withoutMiddleware()->get(AssetYearResource::getUrl('edit', ['record' => $assetYear]));
    $response->assertStatus(200);
});

test('asset year can save with string source values', function () {
    // Create user and team
    $user = User::factory()->create();
    $team = Team::create([
        'name' => 'Test Team',
        'owner_id' => $user->id,
        'is_active' => true,
    ]);

    // Create asset owner
    $owner = AssetConfiguration::create([
        'name' => 'Test Owner',
        'birth_year' => 1985,
        'user_id' => $user->id,
        'team_id' => $team->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    // Create asset using factory
    $asset = Asset::factory()->create([
        'asset_owner_id' => $owner->id,
        'user_id' => $user->id,
        'team_id' => $team->id,
        'name' => 'Test Asset',
        'asset_type' => 'salary',
        'sort_order' => 100,
    ]);

    // Create asset year with string source values
    $assetYear = AssetYear::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'asset_id' => $asset->id,
        'asset_owner_id' => $owner->id,
        'year' => 2024,
        'income_source' => 'test.$year.income.amount',
        'expence_source' => 'test.$year.expence.amount',
        'asset_source' => 'test.$year.asset.amount',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    expect($assetYear->income_source)->toBe('test.$year.income.amount');
    expect($assetYear->expence_source)->toBe('test.$year.expence.amount');
    expect($assetYear->asset_source)->toBe('test.$year.asset.amount');
});

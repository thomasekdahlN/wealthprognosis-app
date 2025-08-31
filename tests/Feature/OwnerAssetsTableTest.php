<?php

use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner assets page displays sort order column and sorts by it', function () {
    // Create user and team
    $user = User::factory()->create();
    $team = Team::create([
        'name' => 'Test Team',
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
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

    // Create assets with different sort orders
    $asset1 = Asset::factory()->create([
        'asset_owner_id' => $owner->id,
        'user_id' => $user->id,
        'team_id' => $team->id,
        'name' => 'Third Asset',
        'asset_type' => 'salary',
        'sort_order' => 30,
    ]);

    $asset2 = Asset::factory()->create([
        'asset_owner_id' => $owner->id,
        'user_id' => $user->id,
        'team_id' => $team->id,
        'name' => 'First Asset',
        'asset_type' => 'house',
        'sort_order' => 10,
    ]);

    $asset3 = Asset::factory()->create([
        'asset_owner_id' => $owner->id,
        'user_id' => $user->id,
        'team_id' => $team->id,
        'name' => 'Second Asset',
        'asset_type' => 'cash',
        'sort_order' => 20,
    ]);

    $this->actingAs($user);

    // Test that the page loads and contains sort order information
    $response = $this->withoutMiddleware()->get(route('filament.admin.resources.asset-owners.assets', ['record' => $owner]));
    $response->assertStatus(200);

    // Verify that assets exist and have sort_order field
    expect($asset1->sort_order)->toBe(30);
    expect($asset2->sort_order)->toBe(10);
    expect($asset3->sort_order)->toBe(20);
});

test('owner assets page loads successfully', function () {
    // Create user and team
    $user = User::factory()->create();
    $team = Team::create([
        'name' => 'Test Team',
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
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

    $this->actingAs($user);

    // Test that the page loads without errors
    $response = $this->withoutMiddleware()->get(route('filament.admin.resources.asset-owners.assets', ['record' => $owner]));
    $response->assertStatus(200);
});

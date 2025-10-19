<?php

use App\Models\AssetType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->actingAs($this->user);

    // Create some asset types with different liquid values
    AssetType::factory()->create([
        'type' => 'bank',
        'name' => 'Bank Account',
        'is_liquid' => true,
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    AssetType::factory()->create([
        'type' => 'house',
        'name' => 'House',
        'is_liquid' => false,
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    AssetType::factory()->create([
        'type' => 'equityfund',
        'name' => 'Equity Fund',
        'is_liquid' => true,
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    AssetType::factory()->create([
        'type' => 'car',
        'name' => 'Car',
        'is_liquid' => false,
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);
});

it('correctly identifies liquid asset types using AssetType model', function () {
    // Test liquid asset types directly from the database
    $bankType = AssetType::where('type', 'bank')->first();
    expect($bankType->is_liquid)->toBeTrue();

    $equityfundType = AssetType::where('type', 'equityfund')->first();
    expect($equityfundType->is_liquid)->toBeTrue();

    // Test non-liquid asset types
    $houseType = AssetType::where('type', 'house')->first();
    expect($houseType->is_liquid)->toBeFalse();

    $carType = AssetType::where('type', 'car')->first();
    expect($carType->is_liquid)->toBeFalse();
});

it('returns null for non-existent asset types', function () {
    // Test non-existent asset type
    $nonExistent = AssetType::where('type', 'nonexistent')->first();
    expect($nonExistent)->toBeNull();
});

<?php

use App\Models\AssetType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\TestablePrognosis;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->actingAs($this->user);

    // Seed two AssetTypes with different is_saving flags
    AssetType::factory()->create([
        'type' => 'equityfund',
        'name' => 'Equity Fund',
        'is_saving' => true,
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    AssetType::factory()->create([
        'type' => 'house',
        'name' => 'House',
        'is_saving' => false,
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);
});

it('uses asset_types.is_saving instead of hardcoded list', function () {
    // Minimal config with only meta so constructor does not iterate assets
    $config = [
        'meta' => [
            'birthYear' => 1990,
            'deathYear' => 90,
        ],
    ];

    // Prognosis gets all singletons (Tax, Changerate, Helper, Rules) from service container
    $prognosis = new TestablePrognosis($config);

    expect($prognosis->isSavingPublic('equityfund'))->toBeTrue();
    expect($prognosis->isSavingPublic('house'))->toBeFalse();

    // Non-existent types should default to false
    expect($prognosis->isSavingPublic('nonexistent'))->toBeFalse();
});

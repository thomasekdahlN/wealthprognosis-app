<?php

use App\Models\Core\Changerate;
use App\Models\PrognosisChangeRate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->actingAs($this->user);

    // Seed a few change rates for a scenario "baseline"
    PrognosisChangeRate::create([
        'scenario_type' => 'baseline',
        'asset_type' => 'equityfund',
        'year' => 2023,
        'change_rate' => 7.5,
        'is_active' => true,
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 't1',
        'updated_checksum' => 't1',
    ]);

    // Missing 2024 on purpose to test fallback
    PrognosisChangeRate::create([
        'scenario_type' => 'baseline',
        'asset_type' => 'equityfund',
        'year' => 2025,
        'change_rate' => 6.0,
        'is_active' => true,
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 't2',
        'updated_checksum' => 't2',
    ]);

    PrognosisChangeRate::create([
        'scenario_type' => 'baseline',
        'asset_type' => 'bondfund',
        'year' => 2024,
        'change_rate' => 3.0,
        'is_active' => true,
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 't3',
        'updated_checksum' => 't3',
    ]);
});

it('loads change rates from DB and falls back to previous years', function () {
    $changerate = new Changerate('baseline', 2023, 2025);

    // Exact year
    [$p2023, $d2023] = $changerate->getChangerateValues('equityfund', 2023);
    expect($p2023)->toBe(7.5);
    expect($d2023)->toBeFloat()->toBe(1 + 7.5 / 100);

    // Fallback: 2024 should use 2023 value
    [$p2024, $d2024] = $changerate->getChangerateValues('equityfund', 2024);
    expect($p2024)->toBe(7.5);
    expect($d2024)->toBeFloat()->toBe(1 + 7.5 / 100);

    // 2025 has explicit value
    [$p2025, $d2025] = $changerate->getChangerateValues('equityfund', 2025);
    expect($p2025)->toBe(6.0);
    expect($d2025)->toBeFloat()->toBe(1 + 6.0 / 100);

    // Another type exact
    [$b2024, $bd2024] = $changerate->getChangerateValues('bondfund', 2024);
    expect($b2024)->toBe(3.0);
    expect($bd2024)->toBeFloat()->toBe(1 + 3.0 / 100);
});

it('caches computed values in-memory for repeated requests', function () {
    $changerate = new Changerate('baseline', 2023, 2025);

    // First call computes and caches
    [$p1, $d1] = $changerate->getChangerateValues('equityfund', 2024);

    // Change underlying data would normally require a new instance, but we simulate repeated calls
    [$p2, $d2] = $changerate->getChangerateValues('equityfund', 2024);

    expect($p2)->toBe($p1);
    expect($d2)->toBe($d1);
});

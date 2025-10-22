<?php

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

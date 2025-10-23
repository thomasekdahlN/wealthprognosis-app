<?php

use App\Models\TaxConfiguration;
use App\Models\TaxType;
use App\Models\Team;
use App\Models\User;
use App\Services\Tax\TaxConfigRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['current_team_id' => $this->team->id]);

    foreach (['salary', 'fortune', 'property_holmestrand'] as $code) {
        TaxType::create([
            'type' => $code,
            'name' => ucfirst(str_replace('_', ' ', $code)),
            'description' => $code.' tax type',
            'is_active' => true,
            'sort_order' => 10,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => 'tc_'.$code,
            'updated_checksum' => 'tu_'.$code,
        ]);
    }
});

it('falls back to previous year when exact year is missing', function () {
    TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2023,
        'tax_type' => 'salary',
        'description' => 'Salary 2023',
        'is_active' => true,
        'configuration' => ['salary' => ['common' => ['percent' => 21]]],
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'c',
        'updated_checksum' => 'u',
    ]);

    $repo = new TaxConfigRepository('no');
    $cfg = $repo->getTaxConfig(2024, 'salary');
    expect($cfg)->toBeArray()->and($cfg['salary']['common']['percent'] ?? null)->toBe(21);
});

it('caches repeated lookups within the same run', function () {
    TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2025,
        'tax_type' => 'fortune',
        'description' => 'Fortune 2025',
        'is_active' => true,
        'configuration' => ['standardDeduction' => 1700000],
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'c2',
        'updated_checksum' => 'u2',
    ]);

    $repo = new TaxConfigRepository('no');
    DB::enableQueryLog();
    $a = $repo->getTaxConfig(2025, 'fortune');
    $b = $repo->getTaxConfig(2025, 'fortune');
    $queries = DB::getQueryLog();
    $count = collect($queries)->filter(fn ($q) => str_contains(strtolower($q['query']), 'tax_configurations'))->count();

    expect($a)->toBeArray()->and($b)->toBeArray();
    expect($count)->toBe(1);
});

it('loads property config via tax configuration lookup', function () {
    TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2025,
        'tax_type' => 'property_holmestrand',
        'description' => 'Holmestrand 2025',
        'is_active' => true,
        'configuration' => ['income' => 0.2, 'standardDeduction' => 50000],
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'c3',
        'updated_checksum' => 'u3',
    ]);

    $repo = new TaxConfigRepository('no');
    $cfg = $repo->getTaxConfig(2025, 'property_holmestrand');
    expect($cfg['income'] ?? null)->toBe(0.2)->and($cfg['standardDeduction'] ?? null)->toBe(50000);
});

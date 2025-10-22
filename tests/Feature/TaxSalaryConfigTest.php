<?php

use App\Models\TaxConfiguration;
use App\Models\TaxType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a team and user for foreign keys
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['current_team_id' => $this->team->id]);

    // Ensure required tax type exists for FK on tax_configurations
    TaxType::create([
        'type' => 'salary',
        'name' => 'Salary',
        'description' => 'Salary tax type',
        'is_active' => true,
        'sort_order' => 10,
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 't1',
        'updated_checksum' => 't1',
    ]);
});

function makeSalaryConfig(int $commonRate = 22): array
{
    return [
        'common' => ['rate' => $commonRate],
        'socialsecurity' => ['rate' => 8.2, 'deduction' => 0],
        'deduction' => ['min' => 0, 'max' => 1000000, 'percent' => 10],
        'bracket' => [
            ['limit' => 200000, 'rate' => 0],
            ['limit' => 400000, 'rate' => 1.7],
            ['rate' => 16.2], // last, no limit
        ],
    ];
}

it('loads salary tax from tax_configurations and falls back to previous years when missing', function () {
    // Only insert 2023. 2024 should fallback to 2023.
    TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2023,
        'tax_type' => 'salary',
        'description' => 'NO Salary 2023',
        'is_active' => true,
        'configuration' => makeSalaryConfig(20),
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'c1',
        'updated_checksum' => 'u1',
    ]);

    $repo = new \App\Services\Tax\TaxConfigRepository('no');

    // Ask for 2024: should use 2023 config (20%) and convert to decimal 0.20
    $rate = $repo->getSalaryTaxCommonRate(2024);
    expect($rate)->toBe(0.20);

    // Ask for 2022: no record at or before -> default 0
    $rateMissing = $repo->getSalaryTaxCommonRate(2022);
    expect($rateMissing)->toEqual(0.0);
});

it('caches salary tax configuration per request to avoid duplicate queries', function () {
    TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2025,
        'tax_type' => 'salary',
        'description' => 'NO Salary 2025',
        'is_active' => true,
        'configuration' => makeSalaryConfig(23),
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'c2',
        'updated_checksum' => 'u2',
    ]);

    $repo = new \App\Services\Tax\TaxConfigRepository('no');

    DB::enableQueryLog();
    $cfg1 = $repo->getTaxConfig(2025, 'salary'); // should query once
    $cfg2 = $repo->getTaxConfig(2025, 'salary'); // should be cached
    $queries = DB::getQueryLog();

    // Convert to decimal like getSalaryTaxCommonRate would
    $rate = ($cfg1['common']['rate'] ?? 0) / 100;
    expect($rate)->toBe(0.23);
    // Expect only one query against tax_configurations table
    $count = collect($queries)->filter(fn ($q) => str_contains(strtolower($q['query']), 'tax_configurations'))->count();
    expect($count)->toBe(1);
});

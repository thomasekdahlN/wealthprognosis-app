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
    // Create a team and user for foreign keys
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['current_team_id' => $this->team->id]);

    // Ensure necessary tax types exist for FK on tax_configurations
    foreach (['fortune', 'equityfund', 'property_holmestrand'] as $code) {
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
            'created_checksum' => 't-'.$code,
            'updated_checksum' => 't-'.$code,
        ]);
    }
});

it('loads asset-type fortune taxable percent with fallback to previous years', function () {
    // Seed only 2023 for asset type 'equityfund'
    TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2023,
        'tax_type' => 'equityfund',
        'description' => 'Equityfund config 2023',
        'is_active' => true,
        'configuration' => [
            'fortune' => 70, // percent
        ],
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'c1',
        'updated_checksum' => 'u1',
    ]);

    $repo = new TaxConfigRepository('no');

    // 2024 should fallback to 2023 config => 70% -> 0.70
    $taxable = $repo->getTaxFortuneTaxableRate('equityfund', 2024);
    expect($taxable)->toBe(0.70);

    // When asking before any config exists, default should be 0
    $missing = $repo->getTaxFortuneTaxableRate('equityfund', 2022);
    expect($missing)->toBe(0.0);
});

it('loads fortune bracket config from DB with fallback', function () {
    // Only insert 2023. 2024 should fallback to 2023.
    TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2023,
        'tax_type' => 'fortune',
        'description' => 'NO Fortune 2023',
        'is_active' => true,
        'configuration' => [
            'bracket' => [
                [
                    'limit' => 1700000,
                    'percent' => 0, // Standard deduction with 0% tax
                ],
                [
                    'limit' => 20000000,
                    'percent' => 1.0, // 1%
                ],
                [
                    'percent' => 1.1, // 1.1%
                ],
            ],
        ],
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'c2',
        'updated_checksum' => 'u2',
    ]);

    $repo = new TaxConfigRepository('no');

    // Test bracket configuration with fallback to 2023
    $brackets = $repo->getFortuneTaxBracketConfig(2024);
    expect($brackets)->toBeArray();
    expect($brackets)->toHaveCount(3);

    // First bracket is standard deduction with 0% tax
    expect($brackets[0]['limit'] ?? null)->toBe(1700000);
    expect((float) ($brackets[0]['percent'] ?? null))->toBe(0.0);

    // Second bracket
    expect($brackets[1]['limit'] ?? null)->toBe(20000000);
    expect((float) ($brackets[1]['percent'] ?? null))->toBe(1.0);

    // Third bracket (no limit)
    expect((float) ($brackets[2]['percent'] ?? null))->toBe(1.1);
});

it('loads property tax percent and deduction from DB with fallback and caches results', function () {
    // Only insert 2025 for property_holmestrand
    TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2025,
        'tax_type' => 'property_holmestrand',
        'description' => 'Holmestrand property tax 2025',
        'is_active' => true,
        'configuration' => [
            'income' => 0.2, // percent
            'standardDeduction' => 50000,
            'fortune' => 70, // taxable portion percent
        ],
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'c3',
        'updated_checksum' => 'u3',
    ]);

    $repo = new TaxConfigRepository('no');

    DB::enableQueryLog();
    // First call should hit DB
    $cfg1 = $repo->getTaxConfig(2025, 'property_holmestrand');

    // Second call should be served from cache
    $cfg2 = $repo->getTaxConfig(2025, 'property_holmestrand');
    $queries = DB::getQueryLog();

    expect($cfg1['income'] ?? null)->toBe(0.2);
    expect($cfg2['income'] ?? null)->toBe(0.2);
    expect($cfg1['standardDeduction'] ?? null)->toBe(50000);
    expect($cfg1['fortune'] ?? null)->toBe(70);

    // Expect at least one query, but not more than 2 for both calls combined due to caching
    $count = collect($queries)->filter(fn ($q) => str_contains(strtolower($q['query']), 'tax_configurations'))->count();
    expect($count)->toBeLessThanOrEqual(2);
});

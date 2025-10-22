<?php

use App\Models\AssetType;
use App\Models\TaxConfiguration;
use App\Models\Core\TaxRealization;
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

    // Ensure necessary tax types exist for FK on tax_configurations
    foreach (['equityfund', 'shareholdershield'] as $code) {
        TaxType::create([
            'type' => $code,
            'name' => ucfirst($code),
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

    // Asset types to control tax_shield support
    AssetType::factory()->create([
        'type' => 'equityfund',
        'name' => 'Equity Fund',
        'tax_shield' => true,
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    AssetType::factory()->create([
        'type' => 'house',
        'name' => 'House',
        'tax_shield' => false,
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);
});

it('loads realization tax from tax_configurations and falls back to previous years', function () {
    // Only insert 2023; 2024 should fallback to 2023
    TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2023,
        'tax_type' => 'equityfund',
        'description' => 'Equity Fund realization 2023',
        'is_active' => true,
        'configuration' => [
            'realization' => 35, // percent
        ],
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'c1',
        'updated_checksum' => 'u1',
    ]);

    $repo = new \App\Services\Tax\TaxConfigRepository('no');

    // DB value 35% -> 0.35 via repository rate
    $p2024 = $repo->getTaxRealizationRate('equityfund', 2024);
    expect($p2024)->toBe(0.35);

    // Group-specific overrides are handled in computation code, not in repository rates.
});

it('loads shareholdershield percent from DB with fallback and respects tax_shield capability', function () {
    // Only insert 2023; 2024 should fallback
    TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2023,
        'tax_type' => 'shareholdershield',
        'description' => 'Shareholder shield 2023',
        'is_active' => true,
        'configuration' => [
            'all' => 22, // percent
        ],
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'c2',
        'updated_checksum' => 'u2',
    ]);

    $repo = new \App\Services\Tax\TaxConfigRepository('no');

    // Asset with tax_shield true -> 22% -> 0.22
    $shield = $repo->getTaxShieldRealizationRate('equityfund', 2024);
    expect($shield)->toBe(0.22);

    // Asset without tax_shield -> 0
    $noShield = $repo->getTaxShieldRealizationRate('house', 2024);
    expect($noShield)->toBe(0.0);
});

it('caches configuration lookups for repeated calls', function () {
    TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2025,
        'tax_type' => 'equityfund',
        'description' => 'Equity Fund realization 2025',
        'is_active' => true,
        'configuration' => [
            'realization' => 31,
        ],
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'c3',
        'updated_checksum' => 'u3',
    ]);

    $service = new TaxRealization('no/no-tax-2025', 2020, 2030);

    DB::enableQueryLog();
    $first = $service->getTaxRealization('private', 'equityfund', 2025); // should query
    $second = $service->getTaxRealization('private', 'equityfund', 2025); // cached
    $queries = DB::getQueryLog();

    expect($first)->toBe(0.31);
    expect($second)->toBe(0.31);

    $count = collect($queries)->filter(fn ($q) => str_contains(strtolower($q['query']), 'tax_configurations'))->count();
    expect($count)->toBe(1);
});

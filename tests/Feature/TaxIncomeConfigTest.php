<?php

use App\Models\TaxConfiguration;
use App\Models\Core\TaxIncome;
use App\Models\TaxType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a team and user for foreign keys
    $this->team = Team::factory()->create();

    $this->user = User::factory()->create([
        'current_team_id' => $this->team->id,
    ]);

    // Ensure required tax types exist for FK on tax_configurations
    foreach (['income' => 'Other Income', 'airbnb' => 'Airbnb'] as $code => $name) {
        TaxType::create([
            'type' => $code,
            'name' => $name,
            'description' => $name.' tax type',
            'is_active' => true,
            'sort_order' => 10,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => 'ck_'.$code,
            'updated_checksum' => 'uk_'.$code,
        ]);
    }
});

function makeIncomeConfig(array $override = []): array
{
    return array_merge([
        'income' => 22, // percent
        'standardDeduction' => 0,
    ], $override);
}

it('loads income tax from tax_configurations with fallback to previous years', function () {
    // Only insert 2023 for 'income'. 2024 should fallback to 2023.
    TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2023,
        'tax_type' => 'income',
        'description' => 'NO Income 2023',
        'is_active' => true,
        'configuration' => makeIncomeConfig(['income' => 20]),
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'c1',
        'updated_checksum' => 'u1',
    ]);

    // Signature matches legacy: pass a path-like config where first part is country code
    $service = new TaxIncome('no/no-tax-2024', 2023, 2025);

    $rateDecimal = $service->getTaxIncome('private', 'income', 2024);
    expect($rateDecimal)->toBe(0.20);

    // If nothing at or before 2022, expect 0
    $rateMissing = $service->getTaxIncome('private', 'income', 2022);
    expect($rateMissing)->toBe(0.0);
});

it('reads standard deduction and income for specific types (airbnb) and uses caching', function () {
    // Insert Airbnb 2025
    TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2025,
        'tax_type' => 'airbnb',
        'description' => 'NO Airbnb 2025',
        'is_active' => true,
        'configuration' => makeIncomeConfig(['income' => 28, 'standardDeduction' => 10000]),
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'c2',
        'updated_checksum' => 'u2',
    ]);

    $service = new TaxIncome('no/no-tax-2025', 2025, 2025);

    DB::enableQueryLog();
    $deduction1 = $service->getTaxStandardDeduction('private', 'airbnb', 2025);
    $rate1 = $service->getTaxIncome('private', 'airbnb', 2025);
    // Repeated calls should be served from in-memory cache
    $deduction2 = $service->getTaxStandardDeduction('private', 'airbnb', 2025);
    $rate2 = $service->getTaxIncome('private', 'airbnb', 2025);
    $queries = DB::getQueryLog();

    expect($deduction1)->toBe(10000.0);
    expect($rate1)->toBe(0.28);
    expect($deduction2)->toBe($deduction1);
    expect($rate2)->toBe($rate1);

    $count = collect($queries)->filter(fn ($q) => str_contains(strtolower($q['query']), 'tax_configurations'))->count();
    expect($count)->toBe(1);
});

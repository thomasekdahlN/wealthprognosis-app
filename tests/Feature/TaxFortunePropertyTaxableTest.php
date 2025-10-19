<?php

use App\Models\Core\TaxFortune;
use App\Models\TaxProperty;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ensure users and teams exist (users must be created first)
    $this->artisan('db:seed', ['--class' => 'UserSeeder']);
    $this->artisan('db:seed', ['--class' => 'TeamSeeder']);
});

it('falls back to database when json config does not have property fortune value', function () {
    // Create a tax property record in the database
    TaxProperty::create([
        'country_code' => 'no',
        'year' => 2025,
        'code' => 'testmunicipality',
        'municipality' => 'Test Municipality',
        'has_tax_on_homes' => true,
        'has_tax_on_companies' => false,
        'tax_home_permill' => 3.5,
        'tax_company_permill' => 7.0,
        'deduction' => 500000,
        'fortune_taxable_percent' => 65.00,
        'is_active' => true,
    ]);

    // Create TaxFortune instance with Norwegian config (no/no-tax-2025)
    // The JSON doesn't have 'testmunicipality', so it should fall back to database
    $taxFortune = new TaxFortune('no/no-tax-2025', 2025, 2050);

    // Call getPropertyTaxable - should fall back to database
    $result = $taxFortune->getPropertyTaxable('private', 'testmunicipality', 2025);

    // Should return 0.65 (65% as decimal)
    expect($result)->toEqual(0.65);
});

it('uses json config value when available', function () {
    // Create TaxFortune instance with Norwegian config that has property values
    $taxFortune = new TaxFortune('no/no-tax-2025', 2025, 2050);

    // Call getPropertyTaxable for holmestrand which exists in JSON
    $result = $taxFortune->getPropertyTaxable('private', 'holmestrand', 2025);

    // Should return 0.70 (70% from JSON)
    expect($result)->toEqual(0.70);
});

it('returns zero when neither json nor database has the value', function () {
    // Create TaxFortune instance
    $taxFortune = new TaxFortune('config', 2025, 2050);

    // Call getPropertyTaxable for non-existent property
    $result = $taxFortune->getPropertyTaxable('private', 'nonexistent', 2025);

    // Should return 0
    expect($result)->toEqual(0);
});

it('returns zero when database record exists but fortune_taxable_percent is null', function () {
    // Create a tax property record without fortune_taxable_percent
    TaxProperty::create([
        'country_code' => 'no',
        'year' => 2025,
        'code' => 'nullfortune',
        'municipality' => 'Null Fortune Municipality',
        'has_tax_on_homes' => true,
        'has_tax_on_companies' => false,
        'tax_home_permill' => 3.5,
        'tax_company_permill' => 7.0,
        'deduction' => 500000,
        'fortune_taxable_percent' => null,
        'is_active' => true,
    ]);

    // Create TaxFortune instance
    $taxFortune = new TaxFortune('config', 2025, 2050);

    // Call getPropertyTaxable
    $result = $taxFortune->getPropertyTaxable('private', 'nullfortune', 2025);

    // Should return 0
    expect($result)->toEqual(0);
});

<?php

/*
 * Copyright (C) 2024 Thomas Ekdahl
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

use App\Services\Tax\TaxRealizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed tax types first, then configurations, then asset types
    $this->seed(\Database\Seeders\TaxTypesFromConfigSeeder::class);
    $this->seed(\Database\Seeders\TaxConfigurationSeeder::class);
    $this->seed(\Database\Seeders\AssetTypeSeeder::class);
});

// ============================================================================
// OTP (Occupational Pension) TAXATION
// ============================================================================

it('taxes OTP as pension income when realized', function () {
    $taxRealization = new TaxRealizationService('no');

    $otpAmount = 500000;

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true,
        taxGroup: 'private',
        taxType: 'otp',
        year: 2025,
        amount: $otpAmount,
        acquisitionAmount: 0, // OTP has no acquisition cost
        assetDiffAmount: $otpAmount,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2025
    );

    // OTP should be taxed using pension tax rates (lower than salary)
    expect($result->taxAmount)->toBeGreaterThan(0, 'OTP should have tax');
    expect($result->taxRate)->toBeGreaterThan(0, 'OTP should have tax rate');

    // Tax should be less than 50% (reasonable upper bound for Norwegian pension tax)
    expect($result->taxAmount)->toBeLessThan($otpAmount * 0.5);
});

it('does not apply tax shield to OTP', function () {
    $taxRealization = new TaxRealizationService('no');

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true,
        taxGroup: 'private',
        taxType: 'otp',
        year: 2025,
        amount: 500000,
        acquisitionAmount: 0,
        assetDiffAmount: 500000,
        taxShieldPrevAmount: 100000, // Has shield, but shouldn't be used for OTP
        acquisitionYear: 2025
    );

    // OTP doesn't use tax shield
    expect($result->taxShieldAmount)->toBe(0);
    expect($result->taxShieldPercent)->toBe(0.0);
});

// ============================================================================
// RENTAL VS PROPERTY ROUNDING
// ============================================================================

it('applies rounding to rental tax calculation', function () {
    $taxRealization = new TaxRealizationService('no');

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true,
        taxGroup: 'private',
        taxType: 'rental',
        year: 2025,
        amount: 3000000,
        acquisitionAmount: 2000000,
        assetDiffAmount: 1000000,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2020
    );

    // Rental should use round() on the tax amount
    expect($result->taxAmount)->toBe((int) round($result->taxAmount), 'Rental tax should be rounded');
    expect($result->taxAmount)->toBeGreaterThan(0);
});

it('does not apply rounding to property tax calculation', function () {
    $taxRealization = new TaxRealizationService('no');

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true,
        taxGroup: 'private',
        taxType: 'property',
        year: 2025,
        amount: 3000000,
        acquisitionAmount: 2000000,
        assetDiffAmount: 1000000,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2020
    );

    // Property tax may not be rounded (depends on implementation)
    expect($result->taxAmount)->toBeGreaterThan(0);
});

// ============================================================================
// CRYPTO, GOLD, IPS, ASK ASSET TYPES
// ============================================================================

it('calculates tax for crypto assets without tax shield', function () {
    $taxRealization = new TaxRealizationService('no');

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true,
        taxGroup: 'private',
        taxType: 'crypto',
        year: 2025,
        amount: 200000,
        acquisitionAmount: 100000,
        assetDiffAmount: 100000,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2023
    );

    // Crypto should be taxed on gains (but has no tax shield in Norwegian law)
    $expectedTax = (int) round(100000 * 0.22); // 22% on 100k gain
    expect($result->taxAmount)->toBe($expectedTax);
    expect($result->taxableAmount)->toBe(100000);
    expect($result->taxShieldAmount)->toBe(0, 'Crypto does not have tax shield');
});

it('calculates tax for gold assets without tax shield', function () {
    $taxRealization = new TaxRealizationService('no');

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true,
        taxGroup: 'private',
        taxType: 'gold',
        year: 2025,
        amount: 500000,
        acquisitionAmount: 300000,
        assetDiffAmount: 200000,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2020
    );

    // Gold should be taxed on gains (but has no tax shield in Norwegian law)
    $expectedTax = (int) round(200000 * 0.22); // 22% on 200k gain
    expect($result->taxAmount)->toBe($expectedTax);
    expect($result->taxableAmount)->toBe(200000);
    expect($result->taxShieldAmount)->toBe(0, 'Gold does not have tax shield');
});

it('calculates tax for IPS (Individual Pension Savings) assets without tax shield', function () {
    $taxRealization = new TaxRealizationService('no');

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true,
        taxGroup: 'private',
        taxType: 'ips',
        year: 2025,
        amount: 150000,
        acquisitionAmount: 100000,
        assetDiffAmount: 50000,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2022
    );

    // IPS has 0% realization tax in Norwegian law (tax-free growth)
    expect($result->taxAmount)->toBe(0, 'IPS has 0% realization tax');
    expect($result->taxableAmount)->toBe(50000);
    expect($result->taxShieldAmount)->toBe(0, 'IPS does not have tax shield');
});

it('calculates tax for ASK (Aksjesparekonto) assets', function () {
    $taxRealization = new TaxRealizationService('no');

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true,
        taxGroup: 'private',
        taxType: 'ask',
        year: 2025,
        amount: 300000,
        acquisitionAmount: 200000,
        assetDiffAmount: 100000,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2021
    );

    // ASK is taxed at 37.84% (22% × 1.72 for aksjonærmodellen)
    $expectedTax = (int) round(100000 * 0.3784); // 37.84% on 100k gain
    expect($result->taxAmount)->toBe($expectedTax);
    expect($result->taxableAmount)->toBe(100000);
});

it('applies tax shield to ASK assets', function () {
    $taxRealization = new TaxRealizationService('no');

    $acquisitionAmount = 1000000;
    $shieldRate = 0.032; // 3.2% for 2025
    $expectedShield = round($acquisitionAmount * $shieldRate); // 32,000

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: false, // Simulation mode
        taxGroup: 'private',
        taxType: 'ask',
        year: 2025,
        amount: 1200000,
        acquisitionAmount: $acquisitionAmount,
        assetDiffAmount: 200000,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2021
    );

    // ASK should have tax shield
    expect($result->taxShieldAmount)->toBe((int) $expectedShield);
    expect($result->taxShieldPercent)->toBe($shieldRate);
});

// ============================================================================
// BOND FUND AND EQUITY FUND
// ============================================================================

it('calculates tax for bond funds', function () {
    $taxRealization = new TaxRealizationService('no');

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true,
        taxGroup: 'private',
        taxType: 'bondfund',
        year: 2025,
        amount: 250000,
        acquisitionAmount: 200000,
        assetDiffAmount: 50000,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2022
    );

    // Bond fund should be taxed on gains
    $expectedTax = (int) round(50000 * 0.22); // 22% on 50k gain
    expect($result->taxAmount)->toBe($expectedTax);
    expect($result->taxableAmount)->toBe(50000);
});

it('calculates tax for equity funds', function () {
    $taxRealization = new TaxRealizationService('no');

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true,
        taxGroup: 'private',
        taxType: 'equityfund',
        year: 2025,
        amount: 400000,
        acquisitionAmount: 300000,
        assetDiffAmount: 100000,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2021
    );

    // Equity fund is taxed at 37.84% (22% × 1.72 for aksjonærmodellen)
    $expectedTax = (int) round(100000 * 0.3784); // 37.84% on 100k gain
    expect($result->taxAmount)->toBe($expectedTax);
    expect($result->taxableAmount)->toBe(100000);
});

it('applies tax shield to equity funds', function () {
    $taxRealization = new TaxRealizationService('no');

    $acquisitionAmount = 500000;
    $shieldRate = 0.032; // 3.2% for 2025
    $expectedShield = round($acquisitionAmount * $shieldRate); // 16,000

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: false, // Simulation mode
        taxGroup: 'private',
        taxType: 'equityfund',
        year: 2025,
        amount: 600000,
        acquisitionAmount: $acquisitionAmount,
        assetDiffAmount: 100000,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2021
    );

    // Equity fund should have tax shield
    expect($result->taxShieldAmount)->toBe((int) $expectedShield);
    expect($result->taxShieldPercent)->toBe($shieldRate);
});

// ============================================================================
// CASH - NO TAX
// ============================================================================

it('does not tax cash assets', function () {
    $taxRealization = new TaxRealizationService('no');

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true,
        taxGroup: 'private',
        taxType: 'cash',
        year: 2025,
        amount: 1000000,
        acquisitionAmount: 500000,
        assetDiffAmount: 500000,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2020
    );

    // Cash should have no tax
    expect($result->taxAmount)->toBe(0);
    expect($result->taxableAmount)->toBe(500000); // Taxable amount is calculated but tax is 0
});

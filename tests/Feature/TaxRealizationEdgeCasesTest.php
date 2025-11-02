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
// LOSS SCENARIOS (Negative Gains)
// ============================================================================

it('calculates zero tax when selling at a loss', function () {
    $taxRealization = new TaxRealizationService('no');

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true,
        taxGroup: 'private',
        taxType: 'stock',
        year: 2025,
        amount: 50000, // Selling for 50k
        acquisitionAmount: 100000, // Bought for 100k
        assetDiffAmount: -50000, // Loss of 50k
        taxShieldPrevAmount: 0,
        acquisitionYear: 2020
    );

    expect($result->taxAmount)->toBe(0, 'No tax on losses');
    expect($result->taxableAmount)->toBe(0, 'No taxable amount on losses');
});

it('still accumulates tax shield even when asset has a loss', function () {
    $taxRealization = new TaxRealizationService('no');

    $acquisitionAmount = 1000000;
    $currentValue = 800000; // Asset declined 20%
    $shieldRate = 0.032; // 3.2%

    // Shield should be calculated on acquisition cost, not market value
    $expectedShield = (int) round($acquisitionAmount * $shieldRate); // 32,000

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: false, // Simulation mode
        taxGroup: 'private',
        taxType: 'stock',
        year: 2025,
        amount: $currentValue,
        acquisitionAmount: $acquisitionAmount,
        assetDiffAmount: -200000,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2020
    );

    expect($result->taxShieldAmount)->toBe($expectedShield, 'Shield calculated on acquisition cost, not market value');
    expect($result->taxAmount)->toBe(0, 'No tax when no gain');
});

it('handles large losses without negative tax', function () {
    $taxRealization = new TaxRealizationService('no');

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true,
        taxGroup: 'private',
        taxType: 'equityfund',
        year: 2025,
        amount: 10000, // Selling for 10k
        acquisitionAmount: 500000, // Bought for 500k
        assetDiffAmount: -490000, // Massive loss
        taxShieldPrevAmount: 50000,
        acquisitionYear: 2020
    );

    expect($result->taxAmount)->toBe(0, 'Tax cannot be negative');
    expect($result->taxAmount)->toBeGreaterThanOrEqual(0);
});

// ============================================================================
// ZERO AND NULL SCENARIOS
// ============================================================================

it('handles zero amount correctly', function () {
    $taxRealization = new TaxRealizationService('no');

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true,
        taxGroup: 'private',
        taxType: 'stock',
        year: 2025,
        amount: 0,
        acquisitionAmount: 100000,
        assetDiffAmount: 0,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2020
    );

    expect($result->taxAmount)->toBe(0);
    expect($result->taxableAmount)->toBe(0);
});

it('handles null tax type gracefully', function () {
    $taxRealization = new TaxRealizationService('no');

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: false,
        taxGroup: 'private',
        taxType: null,
        year: 2025,
        amount: 100000,
        acquisitionAmount: 50000,
        assetDiffAmount: 50000,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2020
    );

    // Should handle gracefully without throwing exception
    expect($result)->toBeInstanceOf(\App\Support\ValueObjects\RealizationTaxResult::class);
});

it('handles zero shield rate with positive previous shield', function () {
    $taxRealization = new TaxRealizationService('no');

    // Test with asset type that has no tax shield (house)
    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: false,
        taxGroup: 'private',
        taxType: 'house', // House has no tax shield
        year: 2025,
        amount: 1000000,
        acquisitionAmount: 1000000,
        assetDiffAmount: 0,
        taxShieldPrevAmount: 50000, // Has previous shield (shouldn't happen, but testing edge case)
        acquisitionYear: 2015
    );

    // Shield should be 0 when asset type has no shield capability
    expect($result->taxShieldAmount)->toBe(0, 'Shield is 0 when asset type has no shield capability');
});

// ============================================================================
// DECLINING ASSET VALUE SCENARIOS
// ============================================================================

it('calculates shield on acquisition cost not declining market value', function () {
    $taxRealization = new TaxRealizationService('no');

    $acquisitionAmount = 2000000;
    $shieldRate = 0.037; // 3.7% for 2024
    $expectedShieldPerYear = (int) round($acquisitionAmount * $shieldRate); // 74,000

    // Year 1: Asset worth 2M
    $result1 = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: false,
        taxGroup: 'private',
        taxType: 'stock',
        year: 2024,
        amount: 2000000,
        acquisitionAmount: $acquisitionAmount,
        assetDiffAmount: 0,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2024
    );

    expect($result1->taxShieldAmount)->toBe($expectedShieldPerYear);

    // Year 2: Asset declined to 1.5M, but shield still based on acquisition cost
    // 2025 has 3.2% shield rate
    $shieldRate2025 = 0.032;
    $expectedShieldYear2 = (int) round($acquisitionAmount * $shieldRate2025); // 64,000

    $result2 = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: false,
        taxGroup: 'private',
        taxType: 'stock',
        year: 2025,
        amount: 1500000, // Market value declined
        acquisitionAmount: $acquisitionAmount, // Acquisition cost unchanged
        assetDiffAmount: -500000,
        taxShieldPrevAmount: $result1->taxShieldAmount,
        acquisitionYear: 2024
    );

    // Shield should be: 2,000,000 × 0.032 + 74,000 = 138,000
    $expectedTotalShieldYear2 = $expectedShieldPerYear + $expectedShieldYear2; // 74,000 + 64,000 = 138,000
    expect($result2->taxShieldAmount)->toBe($expectedTotalShieldYear2);

    // Year 3: Asset declined further to 1M
    // 2026 also has 3.2% shield rate
    $shieldRate2026 = 0.032;
    $expectedShieldYear3 = (int) round($acquisitionAmount * $shieldRate2026); // 64,000

    $result3 = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: false,
        taxGroup: 'private',
        taxType: 'stock',
        year: 2026,
        amount: 1000000, // Market value declined further
        acquisitionAmount: $acquisitionAmount, // Acquisition cost unchanged
        assetDiffAmount: -1000000,
        taxShieldPrevAmount: $result2->taxShieldAmount,
        acquisitionYear: 2024
    );

    // Shield should be: 2,000,000 × 0.032 + 138,000 = 202,000
    $expectedTotalShieldYear3 = $expectedTotalShieldYear2 + $expectedShieldYear3; // 138,000 + 64,000 = 202,000
    expect($result3->taxShieldAmount)->toBe($expectedTotalShieldYear3);
});

it('uses accumulated shield when selling declining asset at a gain', function () {
    $taxRealization = new TaxRealizationService('no');

    $acquisitionAmount = 1000000;
    $accumulatedShield = 150000; // Built up over years

    // Asset declined to 800k, then recovered to 1.2M
    $saleAmount = 1200000;
    $gain = $saleAmount - $acquisitionAmount; // 200,000 gain
    $taxOnGain = (int) round($gain * 0.3784); // 75,680 (37.84% for stock in 2026)

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true, // Actual sale
        taxGroup: 'private',
        taxType: 'stock',
        year: 2029, // Falls back to 2026 config
        amount: $saleAmount,
        acquisitionAmount: $acquisitionAmount,
        assetDiffAmount: $gain,
        taxShieldPrevAmount: $accumulatedShield,
        acquisitionYear: 2024
    );

    // Tax should be 0 because shield (150k) > tax (75,680)
    expect($result->taxAmount)->toBe(0);
    // Remaining shield: 150,000 - 75,680 = 74,320
    $expectedRemainingShield = $accumulatedShield - $taxOnGain;
    expect($result->taxShieldAmount)->toBe($expectedRemainingShield);
});

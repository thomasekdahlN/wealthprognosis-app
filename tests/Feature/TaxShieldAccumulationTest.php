<?php

use App\Models\Team;
use App\Models\User;
use App\Services\Tax\TaxRealizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create users first
    $this->user = User::factory()->create([
        'name' => 'Thomas Ekdahl',
        'email' => 'thomas@ekdahl.no',
    ]);

    // Create team
    $this->team = Team::factory()->create([
        'name' => 'Test Team',
        'owner_id' => $this->user->id,
    ]);

    // Attach user to team
    $this->user->teams()->attach($this->team->id);
    $this->user->update(['current_team_id' => $this->team->id]);

    // Seed asset types
    $this->seed(\Database\Seeders\TaxTypesFromConfigSeeder::class);
    $this->seed(\Database\Seeders\AssetTypeSeeder::class);

    // Seed shareholdershield config for all years (2024-2029)
    // Using 3.2% rate for consistency
    foreach (range(2024, 2029) as $year) {
        \App\Models\TaxConfiguration::create([
            'country_code' => 'no',
            'year' => $year,
            'tax_type' => 'shareholdershield',
            'description' => "Shield rate $year",
            'is_active' => true,
            'configuration' => [
                'percent' => 3.2,
            ],
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => "shield-$year",
            'updated_checksum' => "shield-$year",
        ]);

        // Also seed stock realization tax config (22% rate)
        \App\Models\TaxConfiguration::create([
            'country_code' => 'no',
            'year' => $year,
            'tax_type' => 'stock',
            'description' => "Stock tax $year",
            'is_active' => true,
            'configuration' => [
                'income' => 0,
                'realization' => 22,
                'fortune' => 100,
            ],
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => "stock-$year",
            'updated_checksum' => "stock-$year",
        ]);
    }
});

it('accumulates tax shield over 5 years with annual investments', function () {
    $taxRealization = new TaxRealizationService('no');

    // Initial acquisition: 3,000,000 NOK in 2024
    $initialAcquisition = 3000000;
    $annualInvestment = 100000;
    $shieldRate = 0.032; // 3.2%

    // When you invest more money, BOTH acquisition cost AND market value increase
    // Shield is calculated on acquisition cost (inngangsverdi), not market value
    $expectedShields = [
        2024 => 96000,   // 3,000,000 × 0.032 = 96,000
        2025 => 195200,  // 3,100,000 × 0.032 + 96,000 = 99,200 + 96,000 = 195,200
        2026 => 297600,  // 3,200,000 × 0.032 + 195,200 = 102,400 + 195,200 = 297,600
        2027 => 403200,  // 3,300,000 × 0.032 + 297,600 = 105,600 + 297,600 = 403,200
        2028 => 512000,  // 3,400,000 × 0.032 + 403,200 = 108,800 + 403,200 = 512,000
    ];

    $accumulatedShield = 0;
    $acquisitionAmount = $initialAcquisition;
    $currentValue = $initialAcquisition;

    foreach (range(2024, 2028) as $year) {
        // Simulate year-end (no transfer, just accumulation)
        $result = $taxRealization->taxCalculationRealization(
            debug: false,
            transfer: false, // Simulation mode - accumulate shield
            taxGroup: 'private',
            taxType: 'stock',
            year: $year,
            amount: $currentValue,
            acquisitionAmount: $acquisitionAmount,
            assetDiffAmount: 0,
            taxShieldPrevAmount: $accumulatedShield,
            acquisitionYear: 2024
        );

        // Verify shield accumulation
        expect($result->taxShieldAmount)->toBe($expectedShields[$year], "Year $year shield should be {$expectedShields[$year]}");
        expect($result->taxShieldPercent)->toBe($shieldRate, "Year $year shield rate should be $shieldRate");

        // Update for next year - both acquisition cost and market value increase with new investment
        $accumulatedShield = $result->taxShieldAmount;
        $acquisitionAmount += $annualInvestment;
        $currentValue += $annualInvestment;
    }

    // Final accumulated shield should be 512,000
    expect($accumulatedShield)->toBe(512000);
});

it('uses accumulated tax shield when realizing 50% of market value', function () {
    $taxRealization = new TaxRealizationService('no');

    // After 5 years of accumulation (from previous test scenario)
    $acquisitionAmount = 3000000;
    $totalInvested = 3000000 + (100000 * 5); // 3,500,000
    $marketValue = 3500000; // Assume no market gain for simplicity
    $accumulatedShield = 512000; // From 5 years of accumulation

    // Realize 50% of the market value
    $realizationAmount = $marketValue * 0.5; // 1,750,000
    $realizationAcquisitionAmount = $totalInvested * 0.5; // 1,750,000

    // Calculate gain
    $gain = $realizationAmount - $realizationAcquisitionAmount; // 0 (no gain in this scenario)

    // But let's add a market gain to make it interesting
    $marketValue = 4000000; // Market value increased
    $realizationAmount = $marketValue * 0.5; // 2,000,000
    $gain = $realizationAmount - $realizationAcquisitionAmount; // 2,000,000 - 1,750,000 = 250,000

    // Tax on gain without shield: 250,000 × 0.22 = 55,000
    // With shield: 55,000 - 512,000 = 0 (shield exceeds tax)
    // Remaining shield: 512,000 - 55,000 = 457,000

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true, // Actual transfer - use shield
        taxGroup: 'private',
        taxType: 'stock',
        year: 2029,
        amount: $realizationAmount,
        acquisitionAmount: $realizationAcquisitionAmount,
        assetDiffAmount: $gain,
        taxShieldPrevAmount: $accumulatedShield,
        acquisitionYear: 2024
    );

    // Tax should be 0 because shield exceeds tax
    expect($result->taxAmount)->toBe(0, 'Tax should be 0 because shield exceeds tax amount');

    // Remaining shield should be 512,000 - 55,000 = 457,000
    expect($result->taxShieldAmount)->toBe(457000, 'Remaining shield should be 457,000');
});

it('partially uses tax shield when tax exceeds shield', function () {
    $taxRealization = new TaxRealizationService('no');

    // Scenario: Large gain, small shield
    $acquisitionAmount = 1000000;
    $realizationAmount = 3000000;
    $gain = 2000000; // Large gain
    $accumulatedShield = 100000; // Small shield

    // Tax on gain: 2,000,000 × 0.22 = 440,000
    // With shield: 440,000 - 100,000 = 340,000
    // Remaining shield: 0

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true,
        taxGroup: 'private',
        taxType: 'stock',
        year: 2029,
        amount: $realizationAmount,
        acquisitionAmount: $acquisitionAmount,
        assetDiffAmount: $gain,
        taxShieldPrevAmount: $accumulatedShield,
        acquisitionYear: 2024
    );

    // Tax should be 340,000 (440,000 - 100,000)
    expect($result->taxAmount)->toBe(340000, 'Tax should be reduced by shield amount');

    // Shield should be fully used (0 remaining)
    expect($result->taxShieldAmount)->toBe(0, 'Shield should be fully used');
});

it('does not use tax shield for company assets', function () {
    $taxRealization = new TaxRealizationService('no');

    $acquisitionAmount = 1000000;
    $realizationAmount = 2000000;
    $gain = 1000000;
    $accumulatedShield = 200000;

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: true,
        taxGroup: 'company', // Company asset
        taxType: 'stock',
        year: 2029,
        amount: $realizationAmount,
        acquisitionAmount: $acquisitionAmount,
        assetDiffAmount: $gain,
        taxShieldPrevAmount: $accumulatedShield,
        acquisitionYear: 2024
    );

    // For company stocks, fritaksmodellen applies (no tax)
    expect($result->taxAmount)->toBe(0, 'Company stocks have no realization tax (fritaksmodellen)');

    // Shield should remain unchanged (not used for company assets)
    expect($result->taxShieldAmount)->toBe($accumulatedShield, 'Shield should not be used for company assets');
});

it('accumulates shield in simulation mode but does not reduce it', function () {
    $taxRealization = new TaxRealizationService('no');

    $currentValue = 1000000;
    $acquisitionAmount = 1000000;
    $accumulatedShield = 50000;

    // Simulation mode (transfer = false)
    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: false, // Simulation - don't use shield
        taxGroup: 'private',
        taxType: 'stock',
        year: 2024,
        amount: $currentValue,
        acquisitionAmount: $acquisitionAmount,
        assetDiffAmount: 0,
        taxShieldPrevAmount: $accumulatedShield,
        acquisitionYear: 2024
    );

    // Shield should accumulate: 1,000,000 × 0.032 + 50,000 = 82,000
    expect($result->taxShieldAmount)->toBe(82000, 'Shield should accumulate in simulation mode');
});

it('handles zero shield rate correctly', function () {
    $taxRealization = new TaxRealizationService('no');

    // Create a year with 0% shield rate
    \App\Models\TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2030,
        'tax_type' => 'shareholdershield',
        'description' => 'Shield rate 2030',
        'is_active' => true,
        'configuration' => [
            'percent' => 0,
        ],
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'shield-2030',
        'updated_checksum' => 'shield-2030',
    ]);

    \App\Models\TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2030,
        'tax_type' => 'stock',
        'description' => 'Stock tax 2030',
        'is_active' => true,
        'configuration' => [
            'income' => 0,
            'realization' => 22,
            'fortune' => 100,
        ],
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'stock-2030',
        'updated_checksum' => 'stock-2030',
    ]);

    $currentValue = 1000000;
    $acquisitionAmount = 1000000;
    $accumulatedShield = 50000;

    $result = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: false,
        taxGroup: 'private',
        taxType: 'stock',
        year: 2030,
        amount: $currentValue,
        acquisitionAmount: $acquisitionAmount,
        assetDiffAmount: 0,
        taxShieldPrevAmount: $accumulatedShield,
        acquisitionYear: 2024
    );

    // Shield should remain unchanged (no new accumulation)
    expect($result->taxShieldAmount)->toBe($accumulatedShield, 'Shield should not accumulate with 0% rate');
    expect($result->taxShieldPercent)->toBe(0.0, 'Shield rate should be 0');
});

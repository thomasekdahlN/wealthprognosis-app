<?php

use App\Models\AssetType;
use App\Models\Core\TaxRealization;
use App\Models\Team;
use App\Models\User;
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
});

it('has tax_shield method that returns true for stock asset type', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    expect($taxRealization->hasTaxShield('stock'))->toBeTrue();
});

it('has tax_shield method that returns true for equityfund asset type', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    expect($taxRealization->hasTaxShield('equityfund'))->toBeTrue();
});

it('has tax_shield method that returns true for bondfund asset type', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    expect($taxRealization->hasTaxShield('bondfund'))->toBeTrue();
});

it('has tax_shield method that returns true for ask asset type', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    expect($taxRealization->hasTaxShield('ask'))->toBeTrue();
});

it('has tax_shield method that returns true for loantocompany asset type', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    expect($taxRealization->hasTaxShield('loantocompany'))->toBeTrue();
});

it('has tax_shield method that returns true for soleproprietorship asset type', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    expect($taxRealization->hasTaxShield('soleproprietorship'))->toBeTrue();
});

it('has tax_shield method that returns false for house asset type', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    expect($taxRealization->hasTaxShield('house'))->toBeFalse();
});

it('has tax_shield method that returns false for car asset type', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    expect($taxRealization->hasTaxShield('car'))->toBeFalse();
});

it('has tax_shield method that returns false for boat asset type', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    expect($taxRealization->hasTaxShield('boat'))->toBeFalse();
});

it('has tax_shield method that returns false for bank asset type', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    expect($taxRealization->hasTaxShield('bank'))->toBeFalse();
});

it('has tax_shield method that returns false for cash asset type', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    expect($taxRealization->hasTaxShield('cash'))->toBeFalse();
});

it('has tax_shield method that returns false for nonexistent asset type', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    expect($taxRealization->hasTaxShield('nonexistent'))->toBeFalse();
});

it('getTaxShieldRealization returns percentage for asset types with tax shield', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    $percent = $taxRealization->getTaxShieldRealization('private', 'stock', 2025);

    expect($percent)->toBeGreaterThan(0);
});

it('getTaxShieldRealization returns zero for asset types without tax shield', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    $percent = $taxRealization->getTaxShieldRealization('private', 'house', 2025);

    expect($percent)->toEqual(0);
});

it('can dynamically update asset type tax_shield and hasTaxShield reflects the change', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    // Initially house should not have tax shield
    expect($taxRealization->hasTaxShield('house'))->toBeFalse();

    // Update house to have tax shield
    $houseType = AssetType::where('type', 'house')->first();
    $houseType->update(['tax_shield' => true]);

    // Create a new instance to test (simulating a new request)
    $taxRealization2 = new TaxRealization('no/no-tax-2025', 2025, 2100);
    expect($taxRealization2->hasTaxShield('house'))->toBeTrue();
});

it('taxCalculationRealization uses tax shield for stock asset type', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    // Test with stock which has tax shield
    [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldPercent, $explanation] = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: false,
        taxGroup: 'private',
        taxType: 'stock',
        year: 2025,
        amount: 100000,
        acquisitionAmount: 50000,
        assetDiffAmount: 50000,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2020
    );

    // Should have tax shield amount calculated
    expect($realizationTaxShieldAmount)->toBeGreaterThan(0);
    expect($realizationTaxShieldPercent)->toBeGreaterThan(0);
});

it('taxCalculationRealization does not use tax shield for house asset type', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    // Test with house which does not have tax shield
    [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldPercent, $explanation] = $taxRealization->taxCalculationRealization(
        debug: false,
        transfer: false,
        taxGroup: 'private',
        taxType: 'house',
        year: 2025,
        amount: 5000000,
        acquisitionAmount: 3000000,
        assetDiffAmount: 2000000,
        taxShieldPrevAmount: 0,
        acquisitionYear: 2020
    );

    // Should not have tax shield
    expect($realizationTaxShieldAmount)->toEqual(0);
    expect($realizationTaxShieldPercent)->toEqual(0);
});

it('taxCalculationRealization uses tax shield for all six tax shield asset types', function () {
    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);

    $taxShieldTypes = ['stock', 'equityfund', 'bondfund', 'ask', 'loantocompany', 'soleproprietorship'];

    foreach ($taxShieldTypes as $assetType) {
        [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldPercent, $explanation] = $taxRealization->taxCalculationRealization(
            debug: false,
            transfer: false,
            taxGroup: 'private',
            taxType: $assetType,
            year: 2025,
            amount: 100000,
            acquisitionAmount: 50000,
            assetDiffAmount: 50000,
            taxShieldPrevAmount: 0,
            acquisitionYear: 2020
        );

        // Each should have tax shield amount calculated
        expect($realizationTaxShieldAmount)->toBeGreaterThan(0, "Asset type $assetType should have tax shield amount");
        expect($realizationTaxShieldPercent)->toBeGreaterThan(0, "Asset type $assetType should have tax shield percent");
    }
});

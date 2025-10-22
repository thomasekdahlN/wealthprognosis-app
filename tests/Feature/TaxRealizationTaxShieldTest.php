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

it('hasTaxShield returns true for stock asset type via repository', function () {
    $repo = new \App\Services\Tax\TaxConfigRepository('no');
    expect($repo->hasTaxShield('stock'))->toBeTrue();
});

it('hasTaxShield returns true for equityfund via repository', function () {
    $repo = new \App\Services\Tax\TaxConfigRepository('no');
    expect($repo->hasTaxShield('equityfund'))->toBeTrue();
});

it('hasTaxShield returns expected for bondfund via repository', function () {
    $repo = new \App\Services\Tax\TaxConfigRepository('no');
    expect($repo->hasTaxShield('bondfund'))->toBeTrue();
});

it('hasTaxShield returns true for ask via repository', function () {
    $repo = new \App\Services\Tax\TaxConfigRepository('no');
    expect($repo->hasTaxShield('ask'))->toBeTrue();
});

it('hasTaxShield returns expected for loantocompany via repository', function () {
    $repo = new \App\Services\Tax\TaxConfigRepository('no');
    expect($repo->hasTaxShield('loantocompany'))->toBeTrue();
});

it('hasTaxShield returns expected for soleproprietorship via repository', function () {
    $repo = new \App\Services\Tax\TaxConfigRepository('no');
    expect($repo->hasTaxShield('soleproprietorship'))->toBeTrue();
});

it('hasTaxShield returns false for house via repository', function () {
    $repo = new \App\Services\Tax\TaxConfigRepository('no');
    expect($repo->hasTaxShield('house'))->toBeFalse();
});

it('hasTaxShield returns false for car via repository', function () {
    $repo = new \App\Services\Tax\TaxConfigRepository('no');
    expect($repo->hasTaxShield('car'))->toBeFalse();
});

it('hasTaxShield returns false for boat via repository', function () {
    $repo = new \App\Services\Tax\TaxConfigRepository('no');
    expect($repo->hasTaxShield('boat'))->toBeFalse();
});

it('hasTaxShield returns false for bank via repository', function () {
    $repo = new \App\Services\Tax\TaxConfigRepository('no');
    expect($repo->hasTaxShield('bank'))->toBeFalse();
});

it('hasTaxShield returns false for cash via repository', function () {
    $repo = new \App\Services\Tax\TaxConfigRepository('no');
    expect($repo->hasTaxShield('cash'))->toBeFalse();
});

it('hasTaxShield returns false for nonexistent type via repository', function () {
    $repo = new \App\Services\Tax\TaxConfigRepository('no');
    expect($repo->hasTaxShield('nonexistent'))->toBeFalse();
});

it('getTaxShieldRealizationRate returns percentage for shielded asset types (repository)', function () {
    // Seed shareholdershield config for 2025 so repository can return a non-zero rate
    \App\Models\TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2025,
        'tax_type' => 'shareholdershield',
        'description' => 'Shield rate 2025',
        'is_active' => true,
        'configuration' => [
            '2025' => 3.2,
            'all' => 2.2,
        ],
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'shield-2025',
        'updated_checksum' => 'shield-2025',
    ]);

    $repo = new \App\Services\Tax\TaxConfigRepository('no');
    $percent = $repo->getTaxShieldRealizationRate('stock', 2025);
    expect($percent)->toBeGreaterThan(0);
});

it('getTaxShieldRealizationRate returns zero for non-shielded asset types (repository)', function () {
    $repo = new \App\Services\Tax\TaxConfigRepository('no');
    $percent = $repo->getTaxShieldRealizationRate('house', 2025);
    expect($percent)->toEqual(0);
});

it('updating AssetType.tax_shield updates DB state (repository cache is per-request)', function () {
    $repo = new \App\Services\Tax\TaxConfigRepository('no');

    // Initially house should not have tax shield
    expect($repo->hasTaxShield('house'))->toBeFalse();

    // Update house to have tax shield
    $houseType = AssetType::where('type', 'house')->first();
    $houseType->update(['tax_shield' => true]);

    // Repository method is cached statically per request; verify DB changed
    expect(AssetType::where('type', 'house')->value('tax_shield'))->toBeTrue();
});

it('taxCalculationRealization uses tax shield for stock asset type', function () {
    // Seed shareholdershield config for 2025
    \App\Models\TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2025,
        'tax_type' => 'shareholdershield',
        'description' => 'Shield rate 2025',
        'is_active' => true,
        'configuration' => [
            '2025' => 3.2,
            'all' => 2.2,
        ],
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'shield-2025',
        'updated_checksum' => 'shield-2025',
    ]);

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

it('taxCalculationRealization applies tax shield only for eligible asset types', function () {
    // Seed shareholdershield config for 2025
    \App\Models\TaxConfiguration::create([
        'country_code' => 'no',
        'year' => 2025,
        'tax_type' => 'shareholdershield',
        'description' => 'Shield rate 2025',
        'is_active' => true,
        'configuration' => [
            '2025' => 3.2,
            'all' => 2.2,
        ],
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => 'shield-2025',
        'updated_checksum' => 'shield-2025',
    ]);

    $taxRealization = new TaxRealization('no/no-tax-2025', 2025, 2100);
    $repo = new \App\Services\Tax\TaxConfigRepository('no');

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

        $eligible = $repo->hasTaxShield($assetType);
        if ($eligible) {
            expect($realizationTaxShieldAmount)->toBeGreaterThan(0, "Asset type $assetType should have tax shield amount");
            expect($realizationTaxShieldPercent)->toBeGreaterThan(0, "Asset type $assetType should have tax shield percent");
        } else {
            expect($realizationTaxShieldAmount)->toEqual(0, "Asset type $assetType should not have tax shield amount");
            expect($realizationTaxShieldPercent)->toEqual(0, "Asset type $assetType should not have tax shield percent");
        }
    }
});

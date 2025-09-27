<?php

use App\Models\AssetConfiguration;
use App\Models\AssetType;
use App\Models\TaxType;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();

    // Create required asset types and tax types
    AssetType::factory()->create(['type' => 'cash', 'name' => 'Cash']);
    AssetType::factory()->create(['type' => 'equity', 'name' => 'Equity']);
    AssetType::factory()->create(['type' => 'real_estate', 'name' => 'Real Estate']);

    TaxType::factory()->create(['type' => 'none', 'name' => 'None']);
    TaxType::factory()->create(['type' => 'capital_gains', 'name' => 'Capital Gains']);

    $this->assetConfiguration = AssetConfiguration::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Transfer Fields Configuration',
    ]);

    $this->asset = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'test_transfer_asset',
        'name' => 'Test Transfer Asset',
        'description' => 'Asset for transfer fields testing',
        'asset_type' => 'cash',
        'group' => 'private',

        'tax_country' => 'no',
        'is_active' => true,
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'transfer_created'),
        'updated_checksum' => hash('sha256', 'transfer_updated'),
    ]);
});

it('can handle income transfer fields', function () {
    $this->actingAs($this->user);

    $assetYear = $this->asset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 100000,
        'asset_acquisition_amount' => 100000,
        'asset_equity_amount' => 100000,
        'income_amount' => 5000,
        'income_factor' => 'yearly',
        'income_transfer' => 'reinvest',
        'expence_amount' => 1000,
        'expence_factor' => 'yearly',
        'expence_transfer' => 'deduct',
        'change_rate_type' => 'cash',
        'start_year' => date('Y'),
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'income_transfer_created'),
        'updated_checksum' => hash('sha256', 'income_transfer_updated'),
    ]);

    expect($assetYear->income_transfer)->toBe('reinvest');
    expect($assetYear->expence_transfer)->toBe('deduct');
});

it('can handle expense transfer fields', function () {
    $this->actingAs($this->user);

    $assetYear = $this->asset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 150000,
        'asset_acquisition_amount' => 150000,
        'asset_equity_amount' => 150000,
        'income_amount' => 3000,
        'income_factor' => 'yearly',
        'income_transfer' => 'withdraw',
        'expence_amount' => 2000,
        'expence_factor' => 'yearly',
        'expence_transfer' => 'external',
        'change_rate_type' => 'equity',
        'start_year' => date('Y'),
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'expense_transfer_created'),
        'updated_checksum' => hash('sha256', 'expense_transfer_updated'),
    ]);

    expect($assetYear->income_transfer)->toBe('withdraw');
    expect($assetYear->expence_transfer)->toBe('external');
});

it('can handle asset transfer fields', function () {
    $this->actingAs($this->user);

    $assetYear = $this->asset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 200000,
        'asset_acquisition_amount' => 180000,
        'asset_equity_amount' => 200000,
        'asset_transfer' => 'hold',
        'income_amount' => 8000,
        'income_factor' => 'yearly',
        'income_transfer' => 'compound',
        'expence_amount' => 3000,
        'expence_factor' => 'yearly',
        'expence_transfer' => 'capitalize',
        'change_rate_type' => 'equity',
        'start_year' => date('Y'),
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'asset_transfer_created'),
        'updated_checksum' => hash('sha256', 'asset_transfer_updated'),
    ]);

    expect($assetYear->asset_transfer)->toBe('hold');
    expect($assetYear->income_transfer)->toBe('compound');
    expect($assetYear->expence_transfer)->toBe('capitalize');
});

it('handles different transfer combinations', function () {
    $this->actingAs($this->user);

    // Test none transfer
    $noneTransferYear = $this->asset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 50000,
        'asset_acquisition_amount' => 50000,
        'asset_equity_amount' => 50000,
        'income_amount' => 1000,
        'income_factor' => 'yearly',
        'income_transfer' => 'none',
        'expence_amount' => 200,
        'expence_factor' => 'yearly',
        'expence_transfer' => 'none',
        'asset_transfer' => 'none',
        'change_rate_type' => 'cash',
        'start_year' => date('Y'),
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'none_transfer_created'),
        'updated_checksum' => hash('sha256', 'none_transfer_updated'),
    ]);

    expect($noneTransferYear->income_transfer)->toBe('none');
    expect($noneTransferYear->expence_transfer)->toBe('none');
    expect($noneTransferYear->asset_transfer)->toBe('none');

    // Test automatic transfer
    $autoTransferYear = $this->asset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y') + 1,
        'asset_market_amount' => 75000,
        'asset_acquisition_amount' => 75000,
        'asset_equity_amount' => 75000,
        'income_amount' => 2500,
        'income_factor' => 'yearly',
        'income_transfer' => 'automatic',
        'expence_amount' => 500,
        'expence_factor' => 'yearly',
        'expence_transfer' => 'automatic',
        'asset_transfer' => 'automatic',
        'change_rate_type' => 'cash',
        'start_year' => date('Y') + 1,
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'auto_transfer_created'),
        'updated_checksum' => hash('sha256', 'auto_transfer_updated'),
    ]);

    expect($autoTransferYear->income_transfer)->toBe('automatic');
    expect($autoTransferYear->expence_transfer)->toBe('automatic');
    expect($autoTransferYear->asset_transfer)->toBe('automatic');
});

it('validates transfer field logic', function () {
    $this->actingAs($this->user);

    // Create real estate asset for more complex transfer testing
    $realEstateAsset = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'real_estate_transfer',
        'name' => 'Real Estate Transfer Asset',
        'asset_type' => 'real_estate',
        'group' => 'private',

        'is_active' => true,
        'sort_order' => 2,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'real_estate_transfer_created'),
        'updated_checksum' => hash('sha256', 'real_estate_transfer_updated'),
    ]);

    $realEstateYear = $realEstateAsset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 500000,
        'asset_acquisition_amount' => 400000,
        'asset_equity_amount' => 300000, // With mortgage
        'income_amount' => 24000, // Rental income
        'income_factor' => 'yearly',
        'income_transfer' => 'collect',
        'expence_amount' => 12000, // Maintenance, taxes, etc.
        'expence_factor' => 'yearly',
        'expence_transfer' => 'pay',
        'asset_transfer' => 'appreciate',
        'change_rate_type' => 'real_estate',
        'start_year' => date('Y'),
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'real_estate_year_created'),
        'updated_checksum' => hash('sha256', 'real_estate_year_updated'),
    ]);

    expect($realEstateYear->income_transfer)->toBe('collect');
    expect($realEstateYear->expence_transfer)->toBe('pay');
    expect($realEstateYear->asset_transfer)->toBe('appreciate');
    expect($realEstateYear->asset_equity_amount)->toBeLessThan($realEstateYear->asset_market_amount);
});

it('maintains consistency across transfer fields', function () {
    $this->actingAs($this->user);

    $assetYear = $this->asset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 125000,
        'asset_acquisition_amount' => 125000,
        'asset_equity_amount' => 125000,
        'income_amount' => 6000,
        'income_factor' => 'yearly',
        'income_transfer' => 'reinvest',
        'expence_amount' => 1500,
        'expence_factor' => 'yearly',
        'expence_transfer' => 'deduct',
        'asset_transfer' => 'grow',
        'change_rate_type' => 'equity',
        'start_year' => date('Y'),
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'consistency_created'),
        'updated_checksum' => hash('sha256', 'consistency_updated'),
    ]);

    // Test that all transfer fields are set consistently
    expect($assetYear->income_transfer)->not()->toBeNull();
    expect($assetYear->expence_transfer)->not()->toBeNull();
    expect($assetYear->asset_transfer)->not()->toBeNull();

    // Test that the combination makes logical sense
    expect($assetYear->income_transfer)->toBe('reinvest');
    expect($assetYear->expence_transfer)->toBe('deduct');
    expect($assetYear->asset_transfer)->toBe('grow');
});

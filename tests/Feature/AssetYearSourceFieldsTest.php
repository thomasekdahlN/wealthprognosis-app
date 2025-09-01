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
        'name' => 'Test Source Fields Configuration',
    ]);

    $this->asset = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'test_source_asset',
        'name' => 'Test Source Asset',
        'description' => 'Asset for source fields testing',
        'asset_type' => 'cash',
        'group' => 'private',
        'tax_type' => 'none',
        'tax_country' => 'no',
        'is_active' => true,
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'source_created'),
        'updated_checksum' => hash('sha256', 'source_updated'),
    ]);
});

it('can set income source fields', function () {
    $this->actingAs($this->user);

    $assetYear = $this->asset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 100000,
        'asset_acquisition_amount' => 100000,
        'asset_equity_amount' => 100000,
        'income_amount' => 3000,
        'income_factor' => 'yearly',
        'income_source' => 'salary',
        'income_rule' => 'standard',
        'income_transfer' => 'none',
        'income_changerate' => 'inflation',
        'income_repeat' => true,
        'expence_amount' => 500,
        'expence_factor' => 'yearly',
        'asset_changerate' => 'cash',
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'income_source_created'),
        'updated_checksum' => hash('sha256', 'income_source_updated'),
    ]);

    expect($assetYear->income_source)->toBe('salary');
    expect($assetYear->income_rule)->toBe('standard');
    expect($assetYear->income_transfer)->toBe('none');
    expect($assetYear->income_changerate)->toBe('inflation');
    expect($assetYear->income_repeat)->toBe(true);
});

it('can set expense source fields', function () {
    $this->actingAs($this->user);

    $assetYear = $this->asset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 100000,
        'asset_acquisition_amount' => 100000,
        'asset_equity_amount' => 100000,
        'income_amount' => 2000,
        'income_factor' => 'yearly',
        'expence_amount' => 1200,
        'expence_factor' => 'yearly',
        'expence_source' => 'maintenance',
        'expence_rule' => 'standard',
        'expence_transfer' => 'none',
        'expence_changerate' => 'inflation',
        'expence_repeat' => true,
        'asset_changerate' => 'cash',
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'expense_source_created'),
        'updated_checksum' => hash('sha256', 'expense_source_updated'),
    ]);

    expect($assetYear->expence_source)->toBe('maintenance');
    expect($assetYear->expence_rule)->toBe('standard');
    expect($assetYear->expence_transfer)->toBe('none');
    expect($assetYear->expence_changerate)->toBe('inflation');
    expect($assetYear->expence_repeat)->toBe(true);
});

it('can set asset source fields', function () {
    $this->actingAs($this->user);

    $assetYear = $this->asset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 150000,
        'asset_acquisition_amount' => 150000,
        'asset_equity_amount' => 150000,
        'asset_source' => 'investment',
        'asset_rule' => 'standard',
        'asset_transfer' => 'none',
        'asset_changerate' => 'market',
        'asset_repeat' => true,
        'income_amount' => 2500,
        'income_factor' => 'yearly',
        'expence_amount' => 600,
        'expence_factor' => 'yearly',
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'asset_source_created'),
        'updated_checksum' => hash('sha256', 'asset_source_updated'),
    ]);

    expect($assetYear->asset_source)->toBe('investment');
    expect($assetYear->asset_rule)->toBe('standard');
    expect($assetYear->asset_transfer)->toBe('none');
    expect($assetYear->asset_changerate)->toBe('market');
    expect($assetYear->asset_repeat)->toBe(true);
});

it('handles different change rate types', function () {
    $this->actingAs($this->user);

    // Test cash change rate
    $cashAssetYear = $this->asset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 50000,
        'asset_acquisition_amount' => 50000,
        'asset_equity_amount' => 50000,
        'income_amount' => 1000,
        'income_factor' => 'yearly',
        'expence_amount' => 200,
        'expence_factor' => 'yearly',
        'asset_changerate' => 'cash',
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'cash_rate_created'),
        'updated_checksum' => hash('sha256', 'cash_rate_updated'),
    ]);

    expect($cashAssetYear->asset_changerate)->toBe('cash');

    // Create equity asset for equity change rate test
    $equityAsset = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'equity_source_asset',
        'name' => 'Equity Source Asset',
        'asset_type' => 'equity',
        'group' => 'private',
        'tax_type' => 'capital_gains',
        'is_active' => true,
        'sort_order' => 2,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'equity_source_created'),
        'updated_checksum' => hash('sha256', 'equity_source_updated'),
    ]);

    $equityAssetYear = $equityAsset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 100000,
        'asset_acquisition_amount' => 80000,
        'asset_equity_amount' => 100000,
        'income_amount' => 4000,
        'income_factor' => 'yearly',
        'expence_amount' => 1000,
        'expence_factor' => 'yearly',
        'asset_changerate' => 'equity',
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'equity_rate_created'),
        'updated_checksum' => hash('sha256', 'equity_rate_updated'),
    ]);

    expect($equityAssetYear->asset_changerate)->toBe('equity');
});

it('validates source field combinations', function () {
    $this->actingAs($this->user);

    $assetYear = $this->asset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 75000,
        'asset_acquisition_amount' => 75000,
        'asset_equity_amount' => 75000,
        'income_amount' => 1800,
        'income_factor' => 'yearly',
        'income_source' => 'dividend',
        'income_rule' => 'percentage',
        'income_transfer' => 'reinvest',
        'expence_amount' => 400,
        'expence_factor' => 'yearly',
        'expence_source' => 'fees',
        'expence_rule' => 'fixed',
        'expence_transfer' => 'deduct',
        'asset_changerate' => 'equity',
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'validation_created'),
        'updated_checksum' => hash('sha256', 'validation_updated'),
    ]);

    // Test that complex source field combinations work
    expect($assetYear->income_source)->toBe('dividend');
    expect($assetYear->income_rule)->toBe('percentage');
    expect($assetYear->income_transfer)->toBe('reinvest');
    expect($assetYear->expence_source)->toBe('fees');
    expect($assetYear->expence_rule)->toBe('fixed');
    expect($assetYear->expence_transfer)->toBe('deduct');
});

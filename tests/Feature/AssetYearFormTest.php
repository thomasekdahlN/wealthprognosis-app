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

    TaxType::factory()->create(['type' => 'none', 'name' => 'None']);
    TaxType::factory()->create(['type' => 'capital_gains', 'name' => 'Capital Gains']);

    $this->assetConfiguration = AssetConfiguration::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Form Configuration',
    ]);

    $this->asset = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'test_form_asset',
        'name' => 'Test Form Asset',
        'description' => 'Asset for form testing',
        'asset_type' => 'cash',
        'group' => 'private',
        'tax_country' => 'no',
        'is_active' => true,
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'form_created'),
        'updated_checksum' => hash('sha256', 'form_updated'),
    ]);
});

it('can create asset year with valid data', function () {
    $this->actingAs($this->user);

    $assetYear = $this->asset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 100000,
        'asset_acquisition_amount' => 100000,
        'asset_equity_amount' => 100000,
        'asset_paid_amount' => 0,
        'asset_taxable_initial_amount' => 0,
        'income_amount' => 2000,
        'income_factor' => 'yearly',
        'expence_amount' => 500,
        'expence_factor' => 'yearly',
        'change_rate_type' => 'cash',
        'start_year' => date('Y'),
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'form_year_created'),
        'updated_checksum' => hash('sha256', 'form_year_updated'),
    ]);

    expect($assetYear)->toBeInstanceOf(\App\Models\AssetYear::class);
    expect((float) $assetYear->asset_market_amount)->toBe(100000.0);
    expect((float) $assetYear->income_amount)->toBe(2000.0);
    expect((float) $assetYear->expence_amount)->toBe(500.0);
});

it('validates required fields', function () {
    $this->actingAs($this->user);

    // Test that required fields are enforced
    expect(function () {
        $this->asset->years()->create([
            'asset_configuration_id' => $this->assetConfiguration->id,
            'user_id' => $this->user->id,
            // Missing required fields
        ]);
    })->toThrow(\Illuminate\Database\QueryException::class);
});

it('handles different income factors', function () {
    $this->actingAs($this->user);

    // Test yearly factor
    $yearlyAssetYear = $this->asset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 50000,
        'asset_acquisition_amount' => 50000,
        'asset_equity_amount' => 50000,
        'income_amount' => 5000,
        'income_factor' => 'yearly',
        'expence_amount' => 1000,
        'expence_factor' => 'yearly',
        'change_rate_type' => 'cash',
        'start_year' => date('Y'),
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'yearly_created'),
        'updated_checksum' => hash('sha256', 'yearly_updated'),
    ]);

    expect($yearlyAssetYear->income_factor)->toBe('yearly');

    // Test monthly factor
    $monthlyAssetYear = $this->asset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y') + 1,
        'asset_market_amount' => 50000,
        'asset_acquisition_amount' => 50000,
        'asset_equity_amount' => 50000,
        'income_amount' => 500,
        'income_factor' => 'monthly',
        'expence_amount' => 100,
        'expence_factor' => 'monthly',
        'change_rate_type' => 'cash',
        'start_year' => date('Y') + 1,
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'monthly_created'),
        'updated_checksum' => hash('sha256', 'monthly_updated'),
    ]);

    expect($monthlyAssetYear->income_factor)->toBe('monthly');
});

it('can handle different asset types', function () {
    $this->actingAs($this->user);

    // Create equity asset
    $equityAsset = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'equity_form_asset',
        'name' => 'Equity Form Asset',
        'asset_type' => 'equity',
        'group' => 'private',
        'is_active' => true,
        'sort_order' => 2,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'equity_form_created'),
        'updated_checksum' => hash('sha256', 'equity_form_updated'),
    ]);

    $equityAssetYear = $equityAsset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 200000,
        'asset_acquisition_amount' => 150000,
        'asset_equity_amount' => 200000,
        'income_amount' => 8000,
        'income_factor' => 'yearly',
        'expence_amount' => 2000,
        'expence_factor' => 'yearly',
        'asset_changerate' => 'changerates.equity',
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'equity_year_created'),
        'updated_checksum' => hash('sha256', 'equity_year_updated'),
    ]);

    // change_rate_type field doesn't exist, checking asset_changerate instead
    expect($equityAssetYear->asset_changerate)->toBe('changerates.equity');
    expect($equityAssetYear->asset_market_amount)->toBeGreaterThan($equityAssetYear->asset_acquisition_amount);
});

it('maintains audit trail', function () {
    $this->actingAs($this->user);

    $assetYear = $this->asset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 75000,
        'asset_acquisition_amount' => 75000,
        'asset_equity_amount' => 75000,
        'income_amount' => 1500,
        'income_factor' => 'yearly',
        'expence_amount' => 300,
        'expence_factor' => 'yearly',
        'change_rate_type' => 'cash',
        'start_year' => date('Y'),
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'audit_created'),
        'updated_checksum' => hash('sha256', 'audit_updated'),
    ]);

    expect($assetYear->created_by)->toBe($this->user->id);
    expect($assetYear->updated_by)->toBe($this->user->id);
    expect($assetYear->created_checksum)->not()->toBeNull();
    expect($assetYear->updated_checksum)->not()->toBeNull();
    expect($assetYear->created_at)->not()->toBeNull();
    expect($assetYear->updated_at)->not()->toBeNull();
});

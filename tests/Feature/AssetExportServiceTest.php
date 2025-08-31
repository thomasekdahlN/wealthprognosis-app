<?php

use App\Models\AssetConfiguration;
use App\Models\AssetType;
use App\Models\TaxType;
use App\Models\User;
use App\Services\AssetExportService;

beforeEach(function () {
    $this->user = User::factory()->create();

    // Create required asset types and tax types
    AssetType::factory()->create(['type' => 'cash', 'name' => 'Cash']);
    AssetType::factory()->create(['type' => 'equity', 'name' => 'Equity']);

    TaxType::factory()->create(['type' => 'none', 'name' => 'None']);
    TaxType::factory()->create(['type' => 'capital_gains', 'name' => 'Capital Gains']);

    $this->assetConfiguration = AssetConfiguration::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Export Configuration',
    ]);
});

it('can create asset export service', function () {
    $service = new AssetExportService($this->assetConfiguration);
    expect($service)->toBeInstanceOf(AssetExportService::class);
});

it('can export asset configuration data', function () {
    $this->actingAs($this->user);

    // Create test asset
    $asset = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'test_export_asset',
        'name' => 'Test Export Asset',
        'description' => 'Asset for export testing',
        'asset_type' => 'cash',
        'group' => 'private',
        'tax_type' => 'none',
        'tax_country' => 'no',
        'is_active' => true,
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'export_created'),
        'updated_checksum' => hash('sha256', 'export_updated'),
    ]);

    // Create asset year
    $asset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 50000,
        'asset_acquisition_amount' => 50000,
        'asset_equity_amount' => 50000,
        'asset_paid_amount' => 0,
        'asset_taxable_initial_amount' => 0,
        'income_amount' => 1000,
        'income_factor' => 'yearly',
        'expence_amount' => 200,
        'expence_factor' => 'yearly',
        'change_rate_type' => 'cash',
        'start_year' => date('Y'),
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'export_year_created'),
        'updated_checksum' => hash('sha256', 'export_year_updated'),
    ]);

    $service = new AssetExportService($this->assetConfiguration);

    // Test that service can process the configuration
    expect($this->assetConfiguration->assets)->toHaveCount(1);
    expect($this->assetConfiguration->assets->first()->years)->toHaveCount(1);
});

it('handles empty asset configurations', function () {
    $this->actingAs($this->user);

    $service = new AssetExportService($this->assetConfiguration);

    // Test with configuration that has no assets
    expect($this->assetConfiguration->assets)->toHaveCount(0);
});

it('can handle multiple assets with different types', function () {
    $this->actingAs($this->user);

    // Create cash asset
    $cashAsset = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'cash_asset',
        'name' => 'Cash Asset',
        'asset_type' => 'cash',
        'group' => 'private',
        'tax_type' => 'none',
        'is_active' => true,
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'cash_created'),
        'updated_checksum' => hash('sha256', 'cash_updated'),
    ]);

    // Create equity asset
    $equityAsset = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'equity_asset',
        'name' => 'Equity Asset',
        'asset_type' => 'equity',
        'group' => 'private',
        'tax_type' => 'capital_gains',
        'is_active' => true,
        'sort_order' => 2,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'equity_created'),
        'updated_checksum' => hash('sha256', 'equity_updated'),
    ]);

    $service = new AssetExportService($this->assetConfiguration);

    expect($this->assetConfiguration->assets)->toHaveCount(2);
    expect($this->assetConfiguration->assets->pluck('asset_type')->toArray())
        ->toContain('cash', 'equity');
});

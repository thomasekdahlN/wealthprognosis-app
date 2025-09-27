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
    AssetType::factory()->create(['type' => 'bond', 'name' => 'Bond']);

    TaxType::factory()->create(['type' => 'none', 'name' => 'None']);
    TaxType::factory()->create(['type' => 'capital_gains', 'name' => 'Capital Gains']);
    TaxType::factory()->create(['type' => 'income_tax', 'name' => 'Income Tax']);

    $this->assetConfiguration = AssetConfiguration::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Configuration Assets',
    ]);
});

it('can display assets table for configuration', function () {
    $this->actingAs($this->user);

    // Create test assets
    $cashAsset = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'cash_table_asset',
        'name' => 'Cash Table Asset',
        'description' => 'Cash asset for table testing',
        'asset_type' => 'cash',
        'group' => 'private',

        'tax_country' => 'no',
        'is_active' => true,
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'cash_table_created'),
        'updated_checksum' => hash('sha256', 'cash_table_updated'),
    ]);

    $equityAsset = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'equity_table_asset',
        'name' => 'Equity Table Asset',
        'description' => 'Equity asset for table testing',
        'asset_type' => 'equity',
        'group' => 'private',

        'tax_country' => 'no',
        'is_active' => true,
        'sort_order' => 2,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'equity_table_created'),
        'updated_checksum' => hash('sha256', 'equity_table_updated'),
    ]);

    expect($this->assetConfiguration->assets)->toHaveCount(2);
    expect($this->assetConfiguration->assets->pluck('name')->toArray())
        ->toContain('Cash Table Asset', 'Equity Table Asset');
});

it('can filter assets by type', function () {
    $this->actingAs($this->user);

    // Create assets of different types
    $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'filter_cash',
        'name' => 'Filter Cash Asset',
        'asset_type' => 'cash',
        'group' => 'private',

        'is_active' => true,
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'filter_cash_created'),
        'updated_checksum' => hash('sha256', 'filter_cash_updated'),
    ]);

    $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'filter_equity',
        'name' => 'Filter Equity Asset',
        'asset_type' => 'equity',
        'group' => 'private',

        'is_active' => true,
        'sort_order' => 2,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'filter_equity_created'),
        'updated_checksum' => hash('sha256', 'filter_equity_updated'),
    ]);

    $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'filter_real_estate',
        'name' => 'Filter Real Estate Asset',
        'asset_type' => 'real_estate',
        'group' => 'private',

        'is_active' => true,
        'sort_order' => 3,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'filter_real_estate_created'),
        'updated_checksum' => hash('sha256', 'filter_real_estate_updated'),
    ]);

    // Test filtering by asset type
    $cashAssets = $this->assetConfiguration->assets()->where('asset_type', 'cash')->get();
    $equityAssets = $this->assetConfiguration->assets()->where('asset_type', 'equity')->get();
    $realEstateAssets = $this->assetConfiguration->assets()->where('asset_type', 'real_estate')->get();

    expect($cashAssets)->toHaveCount(1);
    expect($equityAssets)->toHaveCount(1);
    expect($realEstateAssets)->toHaveCount(1);

    expect($cashAssets->first()->name)->toBe('Filter Cash Asset');
    expect($equityAssets->first()->name)->toBe('Filter Equity Asset');
    expect($realEstateAssets->first()->name)->toBe('Filter Real Estate Asset');
});

it('can filter assets by group', function () {
    $this->actingAs($this->user);

    // Create private and business assets
    $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'private_asset',
        'name' => 'Private Asset',
        'asset_type' => 'cash',
        'group' => 'private',

        'is_active' => true,
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'private_created'),
        'updated_checksum' => hash('sha256', 'private_updated'),
    ]);

    $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'business_asset',
        'name' => 'Business Asset',
        'asset_type' => 'equity',
        'group' => 'business',

        'is_active' => true,
        'sort_order' => 2,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'business_created'),
        'updated_checksum' => hash('sha256', 'business_updated'),
    ]);

    // Test filtering by group
    $privateAssets = $this->assetConfiguration->assets()->where('group', 'private')->get();
    $businessAssets = $this->assetConfiguration->assets()->where('group', 'business')->get();

    expect($privateAssets)->toHaveCount(1);
    expect($businessAssets)->toHaveCount(1);

    expect($privateAssets->first()->name)->toBe('Private Asset');
    expect($businessAssets->first()->name)->toBe('Business Asset');
});

it('can sort assets by different criteria', function () {
    $this->actingAs($this->user);

    // Create assets with different sort orders
    $asset1 = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'sort_asset_3',
        'name' => 'Sort Asset C',
        'asset_type' => 'cash',
        'group' => 'private',

        'is_active' => true,
        'sort_order' => 3,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'sort_3_created'),
        'updated_checksum' => hash('sha256', 'sort_3_updated'),
    ]);

    $asset2 = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'sort_asset_1',
        'name' => 'Sort Asset A',
        'asset_type' => 'equity',
        'group' => 'private',

        'is_active' => true,
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'sort_1_created'),
        'updated_checksum' => hash('sha256', 'sort_1_updated'),
    ]);

    $asset3 = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'sort_asset_2',
        'name' => 'Sort Asset B',
        'asset_type' => 'bond',
        'group' => 'private',

        'is_active' => true,
        'sort_order' => 2,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'sort_2_created'),
        'updated_checksum' => hash('sha256', 'sort_2_updated'),
    ]);

    // Test sorting by sort_order
    $sortedAssets = $this->assetConfiguration->assets()->orderBy('sort_order')->get();
    expect($sortedAssets->first()->name)->toBe('Sort Asset A');
    expect($sortedAssets->last()->name)->toBe('Sort Asset C');

    // Test sorting by name
    $nameSortedAssets = $this->assetConfiguration->assets()->orderBy('name')->get();
    expect($nameSortedAssets->first()->name)->toBe('Sort Asset A');
    expect($nameSortedAssets->last()->name)->toBe('Sort Asset C');
});

it('can handle active and inactive assets', function () {
    $this->actingAs($this->user);

    // Create active and inactive assets
    $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'active_asset',
        'name' => 'Active Asset',
        'asset_type' => 'cash',
        'group' => 'private',

        'is_active' => true,
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'active_created'),
        'updated_checksum' => hash('sha256', 'active_updated'),
    ]);

    $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'inactive_asset',
        'name' => 'Inactive Asset',
        'asset_type' => 'equity',
        'group' => 'private',

        'is_active' => false,
        'sort_order' => 2,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'inactive_created'),
        'updated_checksum' => hash('sha256', 'inactive_updated'),
    ]);

    // Test filtering by active status
    $activeAssets = $this->assetConfiguration->assets()->where('is_active', true)->get();
    $inactiveAssets = $this->assetConfiguration->assets()->where('is_active', false)->get();

    expect($activeAssets)->toHaveCount(1);
    expect($inactiveAssets)->toHaveCount(1);

    expect($activeAssets->first()->name)->toBe('Active Asset');
    expect($inactiveAssets->first()->name)->toBe('Inactive Asset');
});

it('displays asset details correctly', function () {
    $this->actingAs($this->user);

    $asset = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'detail_asset',
        'name' => 'Detail Asset',
        'description' => 'Detailed asset for testing display',
        'asset_type' => 'real_estate',
        'group' => 'private',

        'tax_country' => 'no',
        'tax_property' => 'residential',
        'is_active' => true,
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'detail_created'),
        'updated_checksum' => hash('sha256', 'detail_updated'),
    ]);

    // code field doesn't exist in Asset model, checking name instead
    expect($asset->name)->toBe('Detail Asset');
    expect($asset->description)->toBe('Detailed asset for testing display');
    expect($asset->asset_type)->toBe('real_estate');
    expect($asset->group)->toBe('private');
    // tax_type removed; verify relation is available via $asset->assetType->taxType when needed
    expect($asset->tax_country)->toBe('no');
    expect($asset->tax_property)->toBe('residential');
    expect($asset->is_active)->toBe(true);
});

<?php

use App\Models\AssetConfiguration;
use App\Models\AssetType;
use App\Models\TaxType;
use App\Models\User;
use App\Models\PrognosisNew;
use App\Services\PrognosisSimulationService;

beforeEach(function () {
    $this->user = User::factory()->create();

    // Create required asset types and tax types
    AssetType::factory()->create(['type' => 'cash', 'name' => 'Cash']);
    AssetType::factory()->create(['type' => 'equity', 'name' => 'Equity']);
    AssetType::factory()->create(['type' => 'real_estate', 'name' => 'Real Estate']);

    TaxType::factory()->create(['type' => 'none', 'name' => 'None']);
    TaxType::factory()->create(['type' => 'capital_gains', 'name' => 'Capital Gains']);

    // Create asset configuration with realistic data
    $this->assetConfiguration = AssetConfiguration::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Portfolio',
        'birth_year' => 1985,
        'death_age' => 85,
        'prognose_age' => 65,
        'pension_official_age' => 67,
        'pension_wish_age' => 65,
        'export_start_age' => 25,
    ]);

    // Create test assets with realistic data
    // Create savings account
    $savingsAsset = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'savings_account',
        'name' => 'Savings Account',
        'description' => 'Primary savings account',
        'asset_type' => 'cash',
        'group' => 'private',
        'tax_type' => 'none',
        'tax_country' => 'no',
        'is_active' => true,
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'savings_created'),
        'updated_checksum' => hash('sha256', 'savings_updated'),
    ]);

    $savingsAsset->years()->create([
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
        'end_year' => null,
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'savings_year_created'),
        'updated_checksum' => hash('sha256', 'savings_year_updated'),
    ]);

    // Create stock portfolio
    $stockAsset = $this->assetConfiguration->assets()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'code' => 'stock_portfolio',
        'name' => 'Stock Portfolio',
        'description' => 'Diversified stock investments',
        'asset_type' => 'equity',
        'group' => 'private',
        'tax_type' => 'capital_gains',
        'tax_country' => 'no',
        'is_active' => true,
        'sort_order' => 2,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'stock_created'),
        'updated_checksum' => hash('sha256', 'stock_updated'),
    ]);

    $stockAsset->years()->create([
        'asset_configuration_id' => $this->assetConfiguration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->user->currentTeam?->id,
        'year' => date('Y'),
        'asset_market_amount' => 200000,
        'asset_acquisition_amount' => 150000,
        'asset_equity_amount' => 200000,
        'asset_paid_amount' => 0,
        'asset_taxable_initial_amount' => 0,
        'income_amount' => 8000,
        'income_factor' => 'yearly',
        'expence_amount' => 2000,
        'expence_factor' => 'yearly',
        'change_rate_type' => 'equity',
        'start_year' => date('Y'),
        'end_year' => null,
        'sort_order' => 1,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
        'created_checksum' => hash('sha256', 'stock_year_created'),
        'updated_checksum' => hash('sha256', 'stock_year_updated'),
    ]);
});


it('can run simulation with realistic data', function () {
        $prognosis = new PrognosisNew($this->assetConfiguration, 'realistic', 'both');
        $results = $prognosis->runSimulation();

    expect($results)->toBeArray();
    expect($results)->toHaveKey('configuration');
    expect($results)->toHaveKey('summary');
    expect($results)->toHaveKey('yearly_data');
    expect($results)->toHaveKey('asset_breakdown');

    // Check that we have meaningful data
    $summary = $results['summary'];
    expect($summary['total_assets_start'])->toBeGreaterThan(0);
    expect($summary['total_income'])->toBeGreaterThan(0);
    expect($summary['total_assets_start'])->toBe(300000.0); // 100k + 200k

    // Check yearly data progression
    $yearlyData = $results['yearly_data'];
    expect($yearlyData)->not()->toBeEmpty();

    $firstYear = $yearlyData[array_key_first($yearlyData)];
    $lastYear = $yearlyData[array_key_last($yearlyData)];

    // Assets should grow over time with realistic scenario
    expect($lastYear['total_assets'])->toBeGreaterThan($firstYear['total_assets']);

    // Check asset breakdown
    $assetBreakdown = $results['asset_breakdown'];
    expect($assetBreakdown)->toHaveCount(2); // Should have 2 assets

    foreach ($assetBreakdown as $breakdown) {
        expect($breakdown)->toHaveKey('asset_name');
        expect($breakdown)->toHaveKey('start_value');
        expect($breakdown)->toHaveKey('end_value');
        expect($breakdown)->toHaveKey('growth_rate');
    }
});

it('can integrate with simulation service', function () {
        $this->actingAs($this->user);

        $simulationData = [
            'asset_configuration_id' => $this->assetConfiguration->id,
            'prognosis_type' => 'positive',
            'asset_scope' => 'private',
            'start_year' => date('Y'),
            'end_year' => $this->assetConfiguration->birth_year + $this->assetConfiguration->death_age,
        ];

        $service = new PrognosisSimulationService();
        $results = $service->runSimulation($simulationData);

    expect($results)->toBeArray();
    expect($results)->toHaveKey('simulation_configuration_id');
    expect($results)->toHaveKey('results');
    expect($results)->toHaveKey('summary');

    // Verify simulation configuration was created
    $simulationConfigId = $results['simulation_configuration_id'];
    expect($simulationConfigId)->toBeInt();

    $simulationConfig = \App\Models\SimulationConfiguration::find($simulationConfigId);
    expect($simulationConfig)->not()->toBeNull();
    expect($simulationConfig->asset_configuration_id)->toBe($this->assetConfiguration->id);
    expect($simulationConfig->user_id)->toBe($this->user->id);
    expect($simulationConfig->name)->toContain('positive');

    // Verify simulation assets were created
    expect($simulationConfig->simulationAssets()->count())->toBeGreaterThan(0);

    // Verify simulation asset years were created
    foreach ($simulationConfig->simulationAssets as $simulationAsset) {
        expect($simulationAsset->simulationAssetYears()->count())->toBeGreaterThan(0);
    }
});

it('produces different results for different prognosis types', function () {
        $prognosisRealistic = new PrognosisNew($this->assetConfiguration, 'realistic', 'both');
        $resultsRealistic = $prognosisRealistic->runSimulation();

        $prognosisPositive = new PrognosisNew($this->assetConfiguration, 'positive', 'both');
        $resultsPositive = $prognosisPositive->runSimulation();

        $prognosisNegative = new PrognosisNew($this->assetConfiguration, 'negative', 'both');
        $resultsNegative = $prognosisNegative->runSimulation();

    // Positive scenario should result in higher end values than realistic
    expect($resultsPositive['summary']['total_assets_end'])
        ->toBeGreaterThan($resultsRealistic['summary']['total_assets_end']);

    // Realistic scenario should result in higher end values than negative
    expect($resultsRealistic['summary']['total_assets_end'])
        ->toBeGreaterThan($resultsNegative['summary']['total_assets_end']);

    // All should have the same starting values
    expect($resultsPositive['summary']['total_assets_start'])
        ->toBe($resultsRealistic['summary']['total_assets_start']);
    expect($resultsNegative['summary']['total_assets_start'])
        ->toBe($resultsRealistic['summary']['total_assets_start']);
});

it('calculates FIRE metrics correctly', function () {
        // Create a configuration that should achieve FIRE
        $richConfig = AssetConfiguration::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Rich Portfolio',
            'birth_year' => 1990,
            'death_age' => 85,
        ]);

        // Create high-value asset
        $richAsset = $richConfig->assets()->create([
            'user_id' => $this->user->id,
            'team_id' => $this->user->currentTeam?->id,
            'code' => 'rich_portfolio',
            'name' => 'Rich Portfolio',
            'description' => 'High value investment portfolio',
            'asset_type' => 'equity',
            'group' => 'private',
            'tax_type' => 'capital_gains',
            'tax_country' => 'no',
            'is_active' => true,
            'sort_order' => 1,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', 'rich_created'),
            'updated_checksum' => hash('sha256', 'rich_updated'),
        ]);

        $richAsset->years()->create([
            'asset_configuration_id' => $richConfig->id,
            'user_id' => $this->user->id,
            'team_id' => $this->user->currentTeam?->id,
            'year' => date('Y'),
            'asset_market_amount' => 2000000, // 2M starting value
            'asset_acquisition_amount' => 1500000,
            'asset_equity_amount' => 2000000,
            'asset_paid_amount' => 0,
            'asset_taxable_initial_amount' => 0,
            'income_amount' => 50000,
            'income_factor' => 'yearly',
            'expence_amount' => 80000, // 80k yearly expenses
            'expence_factor' => 'yearly',
            'change_rate_type' => 'equity',
            'start_year' => date('Y'),
            'end_year' => null,
            'sort_order' => 1,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', 'rich_year_created'),
            'updated_checksum' => hash('sha256', 'rich_year_updated'),
        ]);

        $prognosis = new PrognosisNew($richConfig, 'realistic', 'both');
        $results = $prognosis->runSimulation();

        $summary = $results['summary'];

    // Should achieve FIRE (25x expenses = 25 * 80k = 2M, which we already have)
    expect($summary['fire_achieved'])->toBe(true);
    expect($summary['fire_year'])->not()->toBeNull();
    expect($summary['fire_amount_needed'])->toBe(2000000.0); // 25 * 80k
});

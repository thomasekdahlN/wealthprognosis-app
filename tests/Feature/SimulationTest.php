<?php

namespace Tests\Feature;

use App\Models\AssetConfiguration;
use App\Models\AssetType;
use App\Models\TaxType;
use App\Models\User;
use App\Models\PrognosisNew;
use App\Services\PrognosisSimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AssetConfiguration $assetConfiguration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create required asset types and tax types
        AssetType::factory()->create(['type' => 'cash', 'name' => 'Cash']);
        AssetType::factory()->create(['type' => 'equity', 'name' => 'Equity']);
        AssetType::factory()->create(['type' => 'real_estate', 'name' => 'Real Estate']);

        TaxType::factory()->create(['type' => 'none', 'name' => 'None']);
        TaxType::factory()->create(['type' => 'capital_gains', 'name' => 'Capital Gains']);

        // Create asset configuration with assets
        $this->assetConfiguration = AssetConfiguration::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Configuration',
            'birth_year' => 1985,
            'death_age' => 85,
            'prognose_age' => 65,
        ]);

        // Create some test assets
        $asset = $this->assetConfiguration->assets()->create([
            'user_id' => $this->user->id,
            'team_id' => $this->user->currentTeam?->id,
            'code' => 'test_savings',
            'name' => 'Test Savings',
            'description' => 'Test savings account',
            'asset_type' => 'cash',
            'group' => 'private',
            'tax_type' => 'none',
            'tax_country' => 'no',
            'is_active' => true,
            'sort_order' => 1,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', 'test_created'),
            'updated_checksum' => hash('sha256', 'test_updated'),
        ]);

        // Create asset year data
        $asset->years()->create([
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
            'asset_changerate' => 'cash',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', 'test_year_created'),
            'updated_checksum' => hash('sha256', 'test_year_updated'),
        ]);
    }

    public function test_prognosis_new_can_be_created(): void
    {
        $prognosis = new PrognosisNew($this->assetConfiguration, 'realistic', 'both');

        $this->assertInstanceOf(PrognosisNew::class, $prognosis);
    }

    public function test_prognosis_new_can_run_simulation(): void
    {
        $prognosis = new PrognosisNew($this->assetConfiguration, 'realistic', 'both');
        $results = $prognosis->runSimulation();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('configuration', $results);
        $this->assertArrayHasKey('summary', $results);
        $this->assertArrayHasKey('yearly_data', $results);
        $this->assertArrayHasKey('asset_breakdown', $results);

        // Check configuration
        $this->assertEquals($this->assetConfiguration->id, $results['configuration']['asset_configuration_id']);
        $this->assertEquals('realistic', $results['configuration']['prognosis_type']);
        $this->assertEquals('both', $results['configuration']['asset_scope']);

        // Check that we have yearly data
        $this->assertNotEmpty($results['yearly_data']);

        // Check that we have asset breakdown
        $this->assertNotEmpty($results['asset_breakdown']);

        // Check summary has required fields
        $summary = $results['summary'];
        $this->assertArrayHasKey('total_assets_start', $summary);
        $this->assertArrayHasKey('total_assets_end', $summary);
        $this->assertArrayHasKey('total_income', $summary);
        $this->assertArrayHasKey('total_expenses', $summary);
        $this->assertArrayHasKey('fire_achieved', $summary);
    }

    public function test_prognosis_simulation_service_can_run_simulation(): void
    {
        $this->actingAs($this->user);

        $simulationData = [
            'asset_configuration_id' => $this->assetConfiguration->id,
            'prognosis_type' => 'realistic',
            'asset_scope' => 'private',
            'start_year' => date('Y'),
            'end_year' => $this->assetConfiguration->birth_year + $this->assetConfiguration->death_age,
        ];

        $service = new PrognosisSimulationService();
        $results = $service->runSimulation($simulationData);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('simulation_configuration_id', $results);
        $this->assertArrayHasKey('results', $results);
        $this->assertArrayHasKey('summary', $results);

        // Verify simulation configuration was created
        $simulationConfigId = $results['simulation_configuration_id'];
        $this->assertIsInt($simulationConfigId);

        $simulationConfig = \App\Models\SimulationConfiguration::find($simulationConfigId);
        $this->assertNotNull($simulationConfig);
        $this->assertEquals($this->assetConfiguration->id, $simulationConfig->asset_configuration_id);
        $this->assertEquals($this->user->id, $simulationConfig->user_id);
    }

    public function test_different_prognosis_types_produce_different_results(): void
    {
        $prognosisRealistic = new PrognosisNew($this->assetConfiguration, 'realistic', 'both');
        $resultsRealistic = $prognosisRealistic->runSimulation();

        $prognosisPositive = new PrognosisNew($this->assetConfiguration, 'positive', 'both');
        $resultsPositive = $prognosisPositive->runSimulation();

        // Positive scenario should generally result in higher end values
        $this->assertGreaterThan(
            $resultsRealistic['summary']['total_assets_end'],
            $resultsPositive['summary']['total_assets_end']
        );
    }

    public function test_asset_scope_filtering_works(): void
    {
        // Create a business asset
        $businessAsset = $this->assetConfiguration->assets()->create([
            'user_id' => $this->user->id,
            'team_id' => $this->user->currentTeam?->id,
            'code' => 'business_asset',
            'name' => 'Business Asset',
            'description' => 'Test business asset',
            'asset_type' => 'equity',
            'group' => 'business',
            'tax_type' => 'capital_gains',
            'tax_country' => 'no',
            'is_active' => true,
            'sort_order' => 2,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', 'business_created'),
            'updated_checksum' => hash('sha256', 'business_updated'),
        ]);

        $businessAsset->years()->create([
            'asset_configuration_id' => $this->assetConfiguration->id,
            'user_id' => $this->user->id,
            'team_id' => $this->user->currentTeam?->id,
            'year' => date('Y'),
            'asset_market_amount' => 50000,
            'asset_acquisition_amount' => 50000,
            'asset_equity_amount' => 50000,
            'asset_paid_amount' => 0,
            'asset_taxable_initial_amount' => 0,
            'income_amount' => 5000,
            'income_factor' => 'yearly',
            'expence_amount' => 1000,
            'expence_factor' => 'yearly',
            'asset_changerate' => 'equity',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', 'business_year_created'),
            'updated_checksum' => hash('sha256', 'business_year_updated'),
        ]);

        // Test private only
        $prognosisPrivate = new PrognosisNew($this->assetConfiguration, 'realistic', 'private');
        $resultsPrivate = $prognosisPrivate->runSimulation();

        // Test business only
        $prognosisBusiness = new PrognosisNew($this->assetConfiguration, 'realistic', 'business');
        $resultsBusiness = $prognosisBusiness->runSimulation();

        // Test both
        $prognosisBoth = new PrognosisNew($this->assetConfiguration, 'realistic', 'both');
        $resultsBoth = $prognosisBoth->runSimulation();

        // Both should have higher total than either private or business alone
        $this->assertGreaterThan(
            $resultsPrivate['summary']['total_assets_start'],
            $resultsBoth['summary']['total_assets_start']
        );

        $this->assertGreaterThan(
            $resultsBusiness['summary']['total_assets_start'],
            $resultsBoth['summary']['total_assets_start']
        );
    }
}

<?php

namespace Tests\Feature;

use App\Models\AssetConfiguration;
use App\Models\AssetType;
use App\Models\TaxType;
use App\Models\User;
use App\Services\AiConfigurationAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistedConfigurationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create some basic asset types and tax types for testing
        AssetType::factory()->create(['type' => 'cash', 'name' => 'Cash']);
        AssetType::factory()->create(['type' => 'equity', 'name' => 'Equity']);
        AssetType::factory()->create(['type' => 'real_estate', 'name' => 'Real Estate']);
        AssetType::factory()->create(['type' => 'other', 'name' => 'Other']);

        TaxType::factory()->create(['type' => 'none', 'name' => 'None']);
        TaxType::factory()->create(['type' => 'capital_gains', 'name' => 'Capital Gains']);
    }

    public function test_ai_assisted_configuration_creates_complete_setup_with_fallback(): void
    {
        // Mock API failure to trigger fallback
        Http::fake([
            'api.openai.com/*' => Http::response([], 500),
        ]);

        // Set a fake API key to avoid the "no API key" exception
        config(['services.openai.api_key' => 'fake-api-key']);

        $this->actingAs($this->user);

        // Test the service directly first
        $service = new AiConfigurationAnalysisService;
        $result = $service->analyzeEconomicSituation('I have $50,000 in savings and want to retire at 65.');

        // Verify fallback configuration structure
        $this->assertArrayHasKey('configuration', $result);
        $this->assertArrayHasKey('assets', $result);
        $this->assertEquals('Basic Configuration', $result['configuration']['name']);
        $this->assertCount(1, $result['assets']);

        // Verify no AssetConfiguration exists yet
        $this->assertEquals(0, AssetConfiguration::count());

        // Now test that we can create a configuration using the analysis result
        $configData = $result['configuration'];
        $assetsData = $result['assets'];

        $assetConfiguration = AssetConfiguration::create([
            'user_id' => $this->user->id,
            'team_id' => $this->user->currentTeam?->id,
            'name' => $configData['name'],
            'description' => $configData['description'],
            'birth_year' => $configData['birth_year'],
            'prognose_age' => $configData['prognose_age'],
            'pension_official_age' => $configData['pension_official_age'],
            'pension_wish_age' => $configData['pension_wish_age'],
            'death_age' => $configData['death_age'],
            'export_start_age' => $configData['export_start_age'],
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', json_encode($configData).'_created'),
            'updated_checksum' => hash('sha256', json_encode($configData).'_updated'),
        ]);

        // Verify the configuration was created
        $this->assertEquals(1, AssetConfiguration::count());
        $this->assertEquals('Basic Configuration', $assetConfiguration->name);
        $this->assertEquals($this->user->id, $assetConfiguration->user_id);

        // Verify we can create assets for this configuration
        $assetData = $assetsData[0];
        $assetType = AssetType::where('type', $assetData['asset_type'])->first();

        $asset = $assetConfiguration->assets()->create([
            'user_id' => $this->user->id,
            'team_id' => $this->user->currentTeam?->id,
            'code' => $assetData['code'],
            'name' => $assetData['name'],
            'description' => $assetData['description'],
            'asset_type' => $assetType->type,
            'group' => $assetData['group'],
            'tax_country' => $assetData['tax_country'],
            'is_active' => true,
            'sort_order' => $assetData['sort_order'],
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', json_encode($assetData).'_created'),
            'updated_checksum' => hash('sha256', json_encode($assetData).'_updated'),
        ]);

        // Verify the asset was created and linked correctly
        $this->assertEquals(1, $assetConfiguration->assets()->count());
        $this->assertEquals('Basic Savings', $asset->name);
        $this->assertEquals('cash', $asset->asset_type);
        $this->assertEquals($assetConfiguration->id, $asset->asset_configuration_id);
    }

    public function test_ai_assisted_configuration_works_with_successful_api_response(): void
    {
        // Mock successful API response
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'configuration' => [
                                    'name' => 'Software Engineer Portfolio',
                                    'description' => 'Portfolio for a 35-year-old software engineer',
                                    'birth_year' => 1989,
                                    'prognose_age' => 65,
                                    'pension_official_age' => 67,
                                    'pension_wish_age' => 65,
                                    'death_age' => 85,
                                    'export_start_age' => 25,
                                ],
                                'assets' => [
                                    [
                                        'name' => 'Emergency Fund',
                                        'description' => 'Emergency savings account',
                                        'code' => 'emergency_fund',
                                        'asset_type' => 'cash',

                                        'group' => 'private',
                                        'tax_country' => 'no',
                                        'sort_order' => 1,
                                        'years' => [
                                            [
                                                'year' => 2024,
                                                'market_amount' => 50000,
                                                'acquisition_amount' => 50000,
                                                'equity_amount' => 50000,
                                                'paid_amount' => 0,
                                                'taxable_initial_amount' => 0,
                                                'income_amount' => 500,
                                                'income_factor' => 'yearly',
                                                'expence_amount' => 0,
                                                'expence_factor' => 'yearly',
                                                'change_rate_type' => 'cash',
                                                'start_year' => 2024,
                                                'end_year' => null,
                                                'sort_order' => 1,
                                            ],
                                        ],
                                    ],
                                    [
                                        'name' => 'Stock Portfolio',
                                        'description' => 'Diversified stock investments',
                                        'code' => 'stock_portfolio',
                                        'asset_type' => 'equity',

                                        'group' => 'private',
                                        'tax_country' => 'no',
                                        'sort_order' => 2,
                                        'years' => [
                                            [
                                                'year' => 2024,
                                                'market_amount' => 20000,
                                                'acquisition_amount' => 18000,
                                                'equity_amount' => 20000,
                                                'paid_amount' => 0,
                                                'taxable_initial_amount' => 0,
                                                'income_amount' => 1000,
                                                'income_factor' => 'yearly',
                                                'expence_amount' => 100,
                                                'expence_factor' => 'yearly',
                                                'change_rate_type' => 'equity',
                                                'start_year' => 2024,
                                                'end_year' => null,
                                                'sort_order' => 1,
                                            ],
                                        ],
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        config(['services.openai.api_key' => 'fake-api-key']);

        $service = new AiConfigurationAnalysisService;
        $result = $service->analyzeEconomicSituation('I am a 35-year-old software engineer with $50,000 in savings and $20,000 in stocks. I want to retire at 65.');

        // Verify AI-generated configuration structure
        $this->assertArrayHasKey('configuration', $result);
        $this->assertArrayHasKey('assets', $result);
        $this->assertEquals('Software Engineer Portfolio', $result['configuration']['name']);
        $this->assertCount(2, $result['assets']);
        $this->assertEquals('Emergency Fund', $result['assets'][0]['name']);
        $this->assertEquals('Stock Portfolio', $result['assets'][1]['name']);

        // Verify asset types are valid
        $this->assertEquals('cash', $result['assets'][0]['asset_type']);
        $this->assertEquals('equity', $result['assets'][1]['asset_type']);

        // Verify years data structure
        $this->assertArrayHasKey('years', $result['assets'][0]);
        $this->assertCount(1, $result['assets'][0]['years']);
        $this->assertEquals(2024, $result['assets'][0]['years'][0]['year']);
        $this->assertEquals(50000, $result['assets'][0]['years'][0]['market_amount']);
    }
}

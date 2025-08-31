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

class AiAssistedConfigurationTest extends TestCase
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

    public function test_ai_configuration_analysis_service_validates_input(): void
    {
        // Mock the OpenAI API response
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'configuration' => [
                                    'name' => 'Test Configuration',
                                    'description' => 'Test description',
                                    'birth_year' => 1988,
                                    'prognose_age' => 65,
                                    'pension_official_age' => 67,
                                    'pension_wish_age' => 65,
                                    'death_age' => 85,
                                    'export_start_age' => 25,
                                ],
                                'assets' => [
                                    [
                                        'name' => 'Savings Account',
                                        'description' => 'Primary savings',
                                        'code' => 'savings_account',
                                        'asset_type' => 'cash',
                                        'tax_type' => 'none',
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
                                                'income_amount' => 1000,
                                                'income_factor' => 'yearly',
                                                'expence_amount' => 100,
                                                'expence_factor' => 'yearly',
                                                'change_rate_type' => 'cash',
                                                'start_year' => 2024,
                                                'end_year' => null,
                                                'sort_order' => 1,
                                            ]
                                        ]
                                    ]
                                ]
                            ])
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Set a fake OpenAI API key
        config(['services.openai.api_key' => 'fake-api-key']);

        $service = new AiConfigurationAnalysisService();
        $result = $service->analyzeEconomicSituation('I have $50,000 in savings and earn $80,000 per year.');

        $this->assertArrayHasKey('configuration', $result);
        $this->assertArrayHasKey('assets', $result);
        $this->assertEquals('Test Configuration', $result['configuration']['name']);
        $this->assertCount(1, $result['assets']);
        $this->assertEquals('Savings Account', $result['assets'][0]['name']);
    }

    public function test_ai_configuration_analysis_service_handles_invalid_asset_types(): void
    {
        // Mock the OpenAI API response with invalid asset type
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'configuration' => [
                                    'name' => 'Test Configuration',
                                    'description' => 'Test description',
                                ],
                                'assets' => [
                                    [
                                        'name' => 'Invalid Asset',
                                        'asset_type' => 'invalid_type', // This should be corrected to 'other'
                                        'tax_type' => 'invalid_tax_type', // This should be corrected to 'none'
                                        'years' => []
                                    ]
                                ]
                            ])
                        ]
                    ]
                ]
            ], 200)
        ]);

        config(['services.openai.api_key' => 'fake-api-key']);

        $service = new AiConfigurationAnalysisService();
        $result = $service->analyzeEconomicSituation('Test description');

        // Should fallback to valid types
        $this->assertEquals('other', $result['assets'][0]['asset_type']);
        $this->assertEquals('none', $result['assets'][0]['tax_type']);
    }

    public function test_ai_configuration_analysis_service_provides_fallback_when_api_fails(): void
    {
        // Mock API failure
        Http::fake([
            'api.openai.com/*' => Http::response([], 500)
        ]);

        config(['services.openai.api_key' => 'fake-api-key']);

        $service = new AiConfigurationAnalysisService();
        $result = $service->analyzeEconomicSituation('Test description');

        // Should return fallback configuration
        $this->assertArrayHasKey('configuration', $result);
        $this->assertArrayHasKey('assets', $result);
        $this->assertEquals('Basic Configuration', $result['configuration']['name']);
        $this->assertCount(1, $result['assets']);
        $this->assertEquals('Basic Savings', $result['assets'][0]['name']);
    }

    public function test_ai_configuration_analysis_service_throws_exception_when_no_api_key(): void
    {
        config(['services.openai.api_key' => null]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OpenAI API key not configured');

        new AiConfigurationAnalysisService();
    }

    public function test_fallback_configuration_has_valid_structure(): void
    {
        config(['services.openai.api_key' => 'fake-api-key']);
        
        $service = new AiConfigurationAnalysisService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getFallbackConfiguration');
        $method->setAccessible(true);
        
        $result = $method->invoke($service, 'test description');

        $this->assertArrayHasKey('configuration', $result);
        $this->assertArrayHasKey('assets', $result);
        
        // Validate configuration structure
        $config = $result['configuration'];
        $this->assertArrayHasKey('name', $config);
        $this->assertArrayHasKey('description', $config);
        $this->assertArrayHasKey('birth_year', $config);
        $this->assertArrayHasKey('prognose_age', $config);
        
        // Validate assets structure
        $this->assertCount(1, $result['assets']);
        $asset = $result['assets'][0];
        $this->assertArrayHasKey('name', $asset);
        $this->assertArrayHasKey('asset_type', $asset);
        $this->assertArrayHasKey('tax_type', $asset);
        $this->assertArrayHasKey('years', $asset);
        
        // Validate years structure
        $this->assertCount(1, $asset['years']);
        $year = $asset['years'][0];
        $this->assertArrayHasKey('year', $year);
        $this->assertArrayHasKey('market_amount', $year);
        $this->assertArrayHasKey('income_amount', $year);
        $this->assertArrayHasKey('income_factor', $year);
    }
}

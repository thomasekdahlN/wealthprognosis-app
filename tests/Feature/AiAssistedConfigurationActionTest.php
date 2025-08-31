<?php

namespace Tests\Feature;

use App\Models\AssetConfiguration;
use App\Models\AssetType;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistedConfigurationActionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create required asset types and tax types
        AssetType::factory()->create(['type' => 'cash', 'name' => 'Cash']);
        AssetType::factory()->create(['type' => 'equity', 'name' => 'Equity']);
        AssetType::factory()->create(['type' => 'real_estate', 'name' => 'Real Estate']);
        AssetType::factory()->create(['type' => 'other', 'name' => 'Other']);

        TaxType::factory()->create(['type' => 'none', 'name' => 'None']);
        TaxType::factory()->create(['type' => 'capital_gains', 'name' => 'Capital Gains']);
    }

    public function test_ai_assisted_configuration_action_can_be_created(): void
    {
        $action = \App\Filament\Resources\AssetConfigurations\Actions\CreateAiAssistedConfigurationAction::make();

        $this->assertEquals('create_ai_assisted_configuration', $action->getName());
        $this->assertEquals('New AI Assisted Configuration', $action->getLabel());
        $this->assertEquals('heroicon-o-sparkles', $action->getIcon());
    }

    public function test_ai_assisted_configuration_creates_fallback_when_ai_fails(): void
    {
        // Mock HTTP to simulate AI service failure
        Http::fake([
            'api.openai.com/*' => Http::response([], 500)
        ]);

        // Set fake API key to avoid the "no API key" exception in constructor
        config(['services.openai.api_key' => 'fake-api-key']);

        $this->actingAs($this->user);

        // Verify no configurations exist initially
        $this->assertEquals(0, AssetConfiguration::count());

        // Simulate the action execution with fallback
        $service = new \App\Services\AiConfigurationAnalysisService();
        $result = $service->analyzeEconomicSituation('I have $50,000 in savings and want to retire at 65.');

        // Verify fallback configuration is returned
        $this->assertArrayHasKey('configuration', $result);
        $this->assertArrayHasKey('assets', $result);
        $this->assertEquals('Basic Configuration', $result['configuration']['name']);

        // Now test that we can create an AssetConfiguration from this result
        $configData = $result['configuration'];

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
            'created_checksum' => hash('sha256', json_encode($configData) . '_created'),
            'updated_checksum' => hash('sha256', json_encode($configData) . '_updated'),
        ]);

        // Verify the configuration was created
        $this->assertEquals(1, AssetConfiguration::count());
        $this->assertEquals('Basic Configuration', $assetConfiguration->name);
        $this->assertEquals($this->user->id, $assetConfiguration->user_id);
    }

    public function test_ai_assisted_configuration_handles_missing_api_key_gracefully(): void
    {
        // Don't set API key to test graceful handling
        config(['services.openai.api_key' => null]);

        $this->actingAs($this->user);

        // The service should throw an exception when no API key is provided
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OpenAI API key not configured');

        new \App\Services\AiConfigurationAnalysisService();
    }

    public function test_action_has_correct_properties(): void
    {
        $action = \App\Filament\Resources\AssetConfigurations\Actions\CreateAiAssistedConfigurationAction::make();

        // Test that the action has the correct modal properties
        $this->assertEquals('Create AI Assisted Asset Configuration', $action->getModalHeading());
        $this->assertEquals('3xl', $action->getModalWidth());
        $this->assertEquals('success', $action->getColor());
        $this->assertEquals('lg', $action->getSize());
    }

    public function test_asset_configuration_can_have_assets_created(): void
    {
        $this->actingAs($this->user);

        // Create an asset configuration
        $assetConfiguration = AssetConfiguration::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Configuration',
        ]);

        // Verify we can create assets for this configuration
        $assetType = AssetType::where('type', 'cash')->first();
        $taxType = TaxType::where('type', 'none')->first();

        $asset = $assetConfiguration->assets()->create([
            'user_id' => $this->user->id,
            'team_id' => $this->user->currentTeam?->id,
            'code' => 'test_asset',
            'name' => 'Test Asset',
            'description' => 'Test asset description',
            'asset_type' => $assetType->type,
            'group' => 'private',
            'tax_type' => $taxType->type,
            'tax_country' => 'no',
            'is_active' => true,
            'sort_order' => 1,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', 'test_created'),
            'updated_checksum' => hash('sha256', 'test_updated'),
        ]);

        // Verify the asset was created and linked correctly
        $this->assertEquals(1, $assetConfiguration->assets()->count());
        $this->assertEquals('Test Asset', $asset->name);
        $this->assertEquals($assetConfiguration->id, $asset->asset_configuration_id);
    }
}

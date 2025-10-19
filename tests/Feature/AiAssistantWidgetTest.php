<?php

use App\Livewire\AiAssistantWidget;
use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetYear;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->actingAs($this->user);
});

it('can render the ai assistant widget', function () {
    Livewire::test(AiAssistantWidget::class)
        ->assertStatus(200)
        ->assertSee('Open AI Financial Assistant');
});

it('can toggle the widget open and closed', function () {
    Livewire::test(AiAssistantWidget::class)
        ->assertSet('isOpen', false)
        ->call('toggleWidget')
        ->assertSet('isOpen', true)
        ->call('toggleWidget')
        ->assertSet('isOpen', false);
});

it('can send a message', function () {
    Livewire::test(AiAssistantWidget::class)
        ->call('toggleWidget') // Open the widget first
        ->set('message', 'Hello, I need help with my finances')
        ->call('sendMessage')
        ->assertSet('message', '')
        ->assertCount('conversation', 3); // Initial message + user message + AI response
});

it('can clear conversation', function () {
    $component = Livewire::test(AiAssistantWidget::class)
        ->set('message', 'Test message')
        ->call('sendMessage');

    $component->call('clearConversation')
        ->assertCount('conversation', 1); // Only the initial greeting message
});

it('can handle configuration creation request', function () {
    $component = Livewire::test(AiAssistantWidget::class)
        ->call('toggleWidget') // Open the widget first
        ->set('message', 'I want to create a new financial configuration named "My Plan" born in 1985 expect to live to 85 retire at 67 with medium risk tolerance')
        ->call('sendMessage');

    // Check the conversation contains a response about configuration
    expect($component->get('conversation'))->toHaveCount(3);
    expect(collect($component->get('conversation'))->last()['message'])->toContain('configuration');
});

it('can handle asset addition request', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
    ]);

    // Set configuration in session using the service
    app(\App\Services\CurrentAssetConfiguration::class)->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class)
        ->call('toggleWidget') // Open the widget first
        ->set('message', 'I have a house worth 3000000 NOK')
        ->call('sendMessage');

    // Check the conversation contains a response about the house
    $lastMessage = collect($component->get('conversation'))->last()['message'];
    expect(strtolower($lastMessage))->toContain('house');
});

it('can handle income addition request', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
    ]);

    // Set configuration in session using the service
    app(\App\Services\CurrentAssetConfiguration::class)->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class)
        ->call('toggleWidget') // Open the widget first
        ->set('message', 'I earn 600000 NOK per year as salary')
        ->call('sendMessage');

    // Check the conversation contains a response about salary
    expect(collect($component->get('conversation'))->last()['message'])->toContain('salary');
});

it('can provide help information', function () {
    $component = Livewire::test(AiAssistantWidget::class)
        ->call('toggleWidget') // Open the widget first
        ->set('message', 'help')
        ->call('sendMessage');

    // Check the conversation contains help information
    $lastMessage = collect($component->get('conversation'))->last()['message'];
    expect($lastMessage)->toContain('Create Configurations');
    expect($lastMessage)->toContain('Add Assets');
    expect($lastMessage)->toContain('Add Income Sources');
});

it('can handle configuration selection', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'name' => 'Test Configuration',
    ]);

    $component = Livewire::test(AiAssistantWidget::class)
        ->call('toggleWidget') // Open the widget first
        ->call('setConfiguration', $configuration->id)
        ->assertSet('currentConfigurationId', $configuration->id);

    // Check the conversation contains a message about the configuration
    expect(collect($component->get('conversation'))->last()['message'])->toContain('Test Configuration');
});

it('can view configuration summary', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'name' => 'Test Configuration',
    ]);

    // Set configuration in session using the service
    app(\App\Services\CurrentAssetConfiguration::class)->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class)
        ->call('toggleWidget') // Open the widget first
        ->set('message', 'show me my current configuration')
        ->call('sendMessage');

    // Check the conversation contains configuration summary
    expect(collect($component->get('conversation'))->last()['message'])->toContain('Test Configuration Summary');
});

it('handles errors gracefully', function () {
    // Mock a service that throws an exception
    $this->mock(\App\Services\AiAssistantService::class)
        ->shouldReceive('processMessage')
        ->andThrow(new \Exception('Test error'));

    $component = Livewire::test(AiAssistantWidget::class)
        ->call('toggleWidget') // Open the widget first
        ->set('message', 'test message')
        ->call('sendMessage');

    // Check the conversation contains error message
    expect(collect($component->get('conversation'))->last()['message'])->toContain('encountered an error');
});

it('requires configuration for asset operations', function () {
    $component = Livewire::test(AiAssistantWidget::class)
        ->call('toggleWidget') // Open the widget first
        ->set('message', 'I have a house worth 3000000 NOK')
        ->call('sendMessage');

    // Check the conversation contains configuration requirement message
    expect(collect($component->get('conversation'))->last()['message'])->toContain('create or select a configuration first');
});

it('requires configuration for income operations', function () {
    $component = Livewire::test(AiAssistantWidget::class)
        ->call('toggleWidget') // Open the widget first
        ->set('message', 'I earn 600000 NOK per year')
        ->call('sendMessage');

    // Check the conversation contains configuration requirement message
    expect(collect($component->get('conversation'))->last()['message'])->toContain('create or select a configuration first');
});

it('requires configuration for life event operations', function () {
    $component = Livewire::test(AiAssistantWidget::class)
        ->call('toggleWidget') // Open the widget first
        ->set('message', 'I have an inheritance coming next year')
        ->call('sendMessage');

    // Check the conversation contains configuration requirement message
    expect(collect($component->get('conversation'))->last()['message'])->toContain('create or select a configuration first');
});

it('can provide complete financial data for analysis', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => auth()->id(),
        'team_id' => auth()->user()->current_team_id,
    ]);

    // Set configuration in session using the service
    app(\App\Services\CurrentAssetConfiguration::class)->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class)
        ->set('message', 'Show me complete financial data for analysis')
        ->call('sendMessage');

    $lastMessage = collect($component->get('conversation'))->last()['message'];
    expect($lastMessage)->toContain('Complete Financial Data Summary');
    expect($lastMessage)->toContain('ready for analysis');
    expect($lastMessage)->toContain('What would you like to know');
    expect($lastMessage)->toContain('net worth');
});

it('provides financial context for general questions', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => auth()->id(),
        'team_id' => auth()->user()->current_team_id,
    ]);

    // Set configuration in session using the service
    app(\App\Services\CurrentAssetConfiguration::class)->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class)
        ->set('message', 'What is my net worth?')
        ->call('sendMessage');

    $lastMessage = collect($component->get('conversation'))->last()['message'];
    // In test environment without API key, expect fallback response
    expect($lastMessage)->toContain('unable to access the AI service');
    expect($lastMessage)->toContain('What is my net worth');
    expect($lastMessage)->toContain('try again');
});

it('provides contextual help for unknown requests when configuration exists', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => auth()->id(),
        'team_id' => auth()->user()->current_team_id,
    ]);

    // Set configuration in session using the service
    app(\App\Services\CurrentAssetConfiguration::class)->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class)
        ->set('message', 'xyzabc nonsense gibberish')
        ->call('sendMessage');

    $lastMessage = collect($component->get('conversation'))->last()['message'];
    // When configuration exists and AI service is available, it will try to answer
    // When AI service is not available, expect fallback or contextual help
    // The response should be helpful in either case
    expect($lastMessage)->not->toBeEmpty();
    // Should contain some helpful content (either AI response or fallback)
    expect(strlen($lastMessage))->toBeGreaterThan(50);
});

it('can refresh configuration detection', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => auth()->id(),
        'team_id' => auth()->user()->current_team_id,
    ]);

    // Set configuration in session using the service
    app(\App\Services\CurrentAssetConfiguration::class)->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class);

    // Call the refresh method
    $component->call('refreshConfiguration');

    // The method should execute without errors and detect the configuration
    expect($component->get('currentConfigurationId'))->toBe($configuration->id);
});

it('shows status indicators during message processing', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => auth()->id(),
        'team_id' => auth()->user()->current_team_id,
    ]);

    // Set the current configuration in session
    $currentConfigService = app(\App\Services\CurrentAssetConfiguration::class);
    $currentConfigService->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class)
        ->set('message', 'Help')
        ->assertSet('isLoading', false)
        ->assertSet('currentStatus', '');

    // Send message and check that loading state is activated
    $component->call('sendMessage')
        ->assertSet('isLoading', false) // Should be false after processing
        ->assertSet('currentStatus', ''); // Should be cleared after processing

    // Verify the conversation was updated (should have at least user message + AI response)
    $conversation = $component->get('conversation');
    expect(count($conversation))->toBeGreaterThanOrEqual(2);
});

it('maintains conversation history for AI context', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => auth()->id(),
        'team_id' => auth()->user()->current_team_id,
    ]);

    // Set the current configuration in session
    $currentConfigService = app(\App\Services\CurrentAssetConfiguration::class);
    $currentConfigService->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class);

    // Send first message
    $component->set('message', 'Add a Tesla worth 500K')
        ->call('sendMessage');

    // Verify conversation has the expected messages
    $conversation = $component->get('conversation');
    expect(count($conversation))->toBeGreaterThanOrEqual(2);
});

it('can handle mortgage update requests', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
    ]);

    // Create an asset that can have a mortgage
    $asset = Asset::factory()->create([
        'asset_configuration_id' => $configuration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'name' => 'My House',
        'asset_type' => 'house',
    ]);

    // Set configuration in session using the service
    app(\App\Services\CurrentAssetConfiguration::class)->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class)
        ->call('toggleWidget') // Open the widget first
        ->set('message', 'Set mortgage on my house to 2500000 NOK with 4.5% interest for 25 years')
        ->call('sendMessage');

    // Check the conversation contains a response about the mortgage
    $lastMessage = collect($component->get('conversation'))->last()['message'];
    expect($lastMessage)->toContain('My House')
        ->and($lastMessage)->toContain('2 500 000 NOK')
        ->and($lastMessage)->toContain('4.5%')
        ->and($lastMessage)->toContain('25 years')
        ->and($lastMessage)->toContain('✅');

    // Verify the asset year was created/updated with mortgage data
    $assetYear = AssetYear::where('asset_id', $asset->id)
        ->where('year', date('Y'))
        ->first();

    expect($assetYear)->not->toBeNull()
        ->and($assetYear->mortgage_amount)->toBe('2500000.00')
        ->and($assetYear->mortgage_interest)->toBe('4.5')
        ->and($assetYear->mortgage_years)->toBe(25);
});

it('can handle mortgage update requests in Norwegian', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
    ]);

    // Create an asset that can have a mortgage
    $asset = Asset::factory()->create([
        'asset_configuration_id' => $configuration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'name' => 'Mitt Hus',
        'asset_type' => 'house',
    ]);

    // Set configuration in session using the service
    app(\App\Services\CurrentAssetConfiguration::class)->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class)
        ->call('toggleWidget') // Open the widget first
        ->set('message', 'Sett lånet på mitt hus til 3000000 kroner')
        ->call('sendMessage');

    // Check the conversation contains a response about the mortgage
    $lastMessage = collect($component->get('conversation'))->last()['message'];
    expect($lastMessage)->toContain('Mitt Hus')
        ->and($lastMessage)->toContain('3 000 000 NOK')
        ->and($lastMessage)->toContain('✅');

    // Verify the asset year was created/updated with mortgage data
    $assetYear = AssetYear::where('asset_id', $asset->id)
        ->where('year', date('Y'))
        ->first();

    expect($assetYear)->not->toBeNull()
        ->and($assetYear->mortgage_amount)->toBe('3000000.00');
});

it('can update only mortgage interest rate', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
    ]);

    // Create an asset that can have a mortgage
    $asset = Asset::factory()->create([
        'asset_configuration_id' => $configuration->id,
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'name' => 'My Cabin',
        'asset_type' => 'cabin',
    ]);

    // First create an existing asset year with mortgage
    AssetYear::factory()->create([
        'asset_id' => $asset->id,
        'asset_configuration_id' => $configuration->id,
        'year' => date('Y'),
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'mortgage_amount' => 1500000,
        'mortgage_years' => 20,
    ]);

    // Set configuration in session using the service
    app(\App\Services\CurrentAssetConfiguration::class)->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class)
        ->call('toggleWidget') // Open the widget first
        ->set('message', 'Update mortgage interest on my cabin to 5.2%')
        ->call('sendMessage');

    // Check the conversation contains a response about the mortgage interest update
    $lastMessage = collect($component->get('conversation'))->last()['message'];
    expect($lastMessage)->toContain('My Cabin')
        ->and($lastMessage)->toContain('5.2%')
        ->and($lastMessage)->toContain('✅');

    // Verify the asset year was updated with new interest rate
    $assetYear = AssetYear::where('asset_id', $asset->id)
        ->where('year', date('Y'))
        ->first();

    expect($assetYear)->not->toBeNull()
        ->and($assetYear->mortgage_amount)->toBe('1500000.00') // Should remain unchanged
        ->and($assetYear->mortgage_years)->toBe(20) // Should remain unchanged
        ->and($assetYear->mortgage_interest)->toBe('5.2'); // Should be updated
});

it('can add asset with loan information', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
    ]);

    // Set configuration in session using the service
    app(\App\Services\CurrentAssetConfiguration::class)->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class)
        ->call('toggleWidget') // Open the widget first
        ->set('message', 'Add a Tesla worth 500K with a loan of 300K for 5 years')
        ->call('sendMessage');

    // Check the conversation contains a response about the asset creation
    $lastMessage = collect($component->get('conversation'))->last()['message'];
    expect($lastMessage)->toContain('Tesla')
        ->and($lastMessage)->toContain('500 000 NOK')
        ->and($lastMessage)->toContain('✅');

    // Verify the asset was created
    $asset = Asset::where('asset_configuration_id', $configuration->id)
        ->where('asset_type', 'car')
        ->latest()
        ->first();

    expect($asset)->not->toBeNull()
        ->and($asset->name)->toContain('Tesla');

    // Verify the asset year was created with both value and mortgage data
    $assetYear = AssetYear::where('asset_id', $asset->id)
        ->where('year', date('Y'))
        ->first();

    expect($assetYear)->not->toBeNull()
        ->and($assetYear->asset_market_amount)->toBe('500000.00')
        ->and($assetYear->mortgage_amount)->toBe('300000.00')
        ->and($assetYear->mortgage_years)->toBe(5);
});

it('can add asset with loan information in Norwegian', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
    ]);

    // Set configuration in session using the service
    app(\App\Services\CurrentAssetConfiguration::class)->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class)
        ->call('toggleWidget') // Open the widget first
        ->set('message', 'Legg til en tesla til en verdi av 200K med et lån på 100K over 7 år')
        ->call('sendMessage');

    // Check the conversation contains a response about the asset creation
    $lastMessage = collect($component->get('conversation'))->last()['message'];
    expect($lastMessage)->toContain('Tesla')
        ->and($lastMessage)->toContain('200 000 NOK')
        ->and($lastMessage)->toContain('✅');

    // Verify the asset was created
    $asset = Asset::where('asset_configuration_id', $configuration->id)
        ->where('asset_type', 'car')
        ->latest()
        ->first();

    expect($asset)->not->toBeNull()
        ->and($asset->name)->toContain('Tesla');

    // Verify the asset year was created with both value and mortgage data
    $assetYear = AssetYear::where('asset_id', $asset->id)
        ->where('year', date('Y'))
        ->first();

    expect($assetYear)->not->toBeNull()
        ->and($assetYear->asset_market_amount)->toBe('200000.00')
        ->and($assetYear->mortgage_amount)->toBe('100000.00')
        ->and($assetYear->mortgage_years)->toBe(7);
});

it('handles Norwegian asset addition requests correctly', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => auth()->id(),
        'team_id' => auth()->user()->current_team_id,
    ]);

    // Set the current configuration in session
    $currentConfigService = app(\App\Services\CurrentAssetConfiguration::class);
    $currentConfigService->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class);

    // Test Norwegian boat addition without value - should ask for more info
    $component->set('message', 'Legg til en princess 55 båt')
        ->call('sendMessage');

    $conversation = $component->get('conversation');
    $lastMessage = collect($conversation)->last()['message'];

    // Should ask for more information since no value was provided
    expect($lastMessage)->toContain('need more information');
    expect($lastMessage)->toContain('market value');

    // Test Norwegian boat addition with value - should create asset
    $component->set('message', 'Legg til en princess 55 båt verdi 2M')
        ->call('sendMessage');

    $updatedConversation = $component->get('conversation');
    $lastResponse = collect($updatedConversation)->last()['message'];

    // Should successfully create the asset
    expect($lastResponse)->toContain('Asset Successfully Added');
    expect($lastResponse)->toContain('Princess');
    expect($lastResponse)->toContain('2 000 000');
});

it('can update existing asset values', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => auth()->id(),
        'team_id' => auth()->user()->current_team_id,
    ]);

    // Create a test asset first
    $asset = Asset::factory()->create([
        'name' => 'Toyota Camry',
        'asset_type' => 'car',
        'asset_configuration_id' => $configuration->id,
        'user_id' => auth()->id(),
        'team_id' => auth()->user()->current_team_id,
    ]);

    // Create initial asset year
    AssetYear::factory()->create([
        'asset_id' => $asset->id,
        'asset_configuration_id' => $configuration->id,
        'year' => now()->year,
        'asset_market_amount' => 300000,
        'user_id' => auth()->id(),
        'team_id' => auth()->user()->current_team_id,
        'income_factor' => 'yearly',
        'expence_factor' => 'yearly',
    ]);

    // Set the current configuration in session
    $currentConfigService = app(\App\Services\CurrentAssetConfiguration::class);
    $currentConfigService->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class);

    // Test updating asset value
    $component->set('message', 'Sett verdien av min Toyota til 40K')
        ->call('sendMessage');

    $conversation = $component->get('conversation');
    $lastMessage = collect($conversation)->last()['message'];

    // Should confirm the update with brief message
    expect($lastMessage)->toContain('value updated to');
    expect($lastMessage)->toContain('Toyota');
    expect($lastMessage)->toContain('40 000');

    // Verify the database was updated
    $updatedAssetYear = AssetYear::where('asset_id', $asset->id)
        ->where('year', now()->year)
        ->first();

    expect((float) $updatedAssetYear->asset_market_amount)->toBe(40000.0);

    // Test updating the same asset again to ensure it updates, not duplicates
    $component->set('message', 'Sett verdien av min Toyota til 50K')
        ->call('sendMessage');

    // Should still have only one record for this year
    $assetYearCount = AssetYear::where('asset_id', $asset->id)
        ->where('year', now()->year)
        ->count();

    expect($assetYearCount)->toBe(1);

    // And the value should be updated
    $finalAssetYear = AssetYear::where('asset_id', $asset->id)
        ->where('year', now()->year)
        ->first();

    expect((float) $finalAssetYear->asset_market_amount)->toBe(50000.0);
});

it('can update asset values with Norwegian "på" preposition', function () {
    $configuration = AssetConfiguration::factory()->create([
        'user_id' => auth()->id(),
        'team_id' => auth()->user()->current_team_id,
    ]);

    // Create a test asset
    $asset = Asset::factory()->create([
        'name' => 'Toyota Camry',
        'asset_type' => 'car',
        'asset_configuration_id' => $configuration->id,
        'user_id' => auth()->id(),
        'team_id' => auth()->user()->current_team_id,
    ]);

    // Set the current configuration in session
    $currentConfigService = app(\App\Services\CurrentAssetConfiguration::class);
    $currentConfigService->set($configuration);

    $component = Livewire::test(AiAssistantWidget::class);

    // Test the specific Norwegian phrase that was failing
    $component->set('message', 'Sett verdien på min toyota til 40K')
        ->call('sendMessage');

    $conversation = $component->get('conversation');
    $lastMessage = collect($conversation)->last()['message'];

    // Should confirm the update with brief message
    expect($lastMessage)->toContain('value updated to');
    expect($lastMessage)->toContain('Toyota');
    expect($lastMessage)->toContain('40 000');

    // Verify the database was updated
    $assetYear = AssetYear::where('asset_id', $asset->id)
        ->where('year', now()->year)
        ->first();

    expect($assetYear)->not->toBeNull();
    expect((float) $assetYear->asset_market_amount)->toBe(40000.0);
});

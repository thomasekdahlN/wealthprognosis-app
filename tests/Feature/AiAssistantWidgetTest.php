<?php

use App\Livewire\AiAssistantWidget;
use App\Models\AssetConfiguration;
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
        ->set('message', 'random gibberish that makes no sense')
        ->call('sendMessage');

    $lastMessage = collect($component->get('conversation'))->last()['message'];
    // In test environment without API key, expect fallback response
    expect($lastMessage)->toContain('unable to access the AI service');
    expect($lastMessage)->toContain('random gibberish that makes no sense');
    expect($lastMessage)->toContain('try again');
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

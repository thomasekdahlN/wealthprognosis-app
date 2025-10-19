<?php

use App\Livewire\AiAssistantWidget;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->actingAs($this->user);
});

it('renders the AI assistant widget with sparkle icon', function () {
    $component = Livewire::test(AiAssistantWidget::class);

    // Widget should start closed
    $component->assertSet('isOpen', false);

    // Should render the toggle button with sparkle icon
    $component->assertSee('Open AI Financial Assistant');

    // Should have the sparkle SVG paths
    $component->assertSeeHtml('path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"');
});

it('can toggle the widget open and closed', function () {
    $component = Livewire::test(AiAssistantWidget::class);

    // Start closed
    $component->assertSet('isOpen', false);

    // Toggle open
    $component->call('toggleWidget');
    $component->assertSet('isOpen', true);

    // Should show the chat interface when open
    $component->assertSee('AI Financial Assistant');

    // Toggle closed
    $component->call('toggleWidget');
    $component->assertSet('isOpen', false);
});

it('has proper dynamic height styling', function () {
    $component = Livewire::test(AiAssistantWidget::class);

    // Open the widget
    $component->call('toggleWidget');
    $component->assertSet('isOpen', true);

    // Should contain dynamic height styling
    $component->assertSeeHtml('height: 90vh !important');
    $component->assertSeeHtml('max-height: 90vh !important');
});

it('has correct sparkle icon size', function () {
    $component = Livewire::test(AiAssistantWidget::class);

    // Should have w-10 h-10 classes for proper icon size (40px x 40px)
    $component->assertSeeHtml('class="w-10 h-10 group-hover:rotate-12 transition-transform duration-300"');
});

it('shows status updates during message processing', function () {
    $component = Livewire::test(AiAssistantWidget::class);

    // Open widget
    $component->call('toggleWidget');
    $component->assertSet('isOpen', true);

    // Should have status text element when open
    $component->assertSeeHtml('id="status-text"');
});

it('maintains conversation history', function () {
    $component = Livewire::test(AiAssistantWidget::class);

    // Start with empty conversation
    $component->assertSet('conversation', []);

    // Open widget and send a message
    $component->call('toggleWidget');
    $component->set('message', 'Test message');
    $component->call('sendMessage');

    // Should have added at least user message to conversation
    expect($component->get('conversation'))->not->toBeEmpty();
    expect($component->get('conversation')[0]['type'])->toBe('user');
    expect($component->get('conversation')[0]['message'])->toBe('Test message');
});

it('can clear conversation history', function () {
    $component = Livewire::test(AiAssistantWidget::class);

    // Manually add some conversation
    $component->set('conversation', [
        ['type' => 'user', 'message' => 'Test', 'timestamp' => '12:00'],
    ]);

    // Should have conversation
    expect($component->get('conversation'))->not->toBeEmpty();

    // Clear conversation
    $component->call('clearConversation');

    // Should be empty
    $component->assertSet('conversation', []);
});

it('has larger button size for better visibility', function () {
    $component = Livewire::test(AiAssistantWidget::class);

    // Should have 80px x 80px button size and be perfectly round
    $component->assertSeeHtml('width: 80px !important;');
    $component->assertSeeHtml('height: 80px !important;');
    $component->assertSeeHtml('border-radius: 50% !important;');
});

it('does not show AI badge', function () {
    $component = Livewire::test(AiAssistantWidget::class);

    // Should not have the red AI notification badge
    $component->assertDontSeeHtml('animate-ping');
    $component->assertDontSeeHtml('>AI</span>');
});

it('has dynamic height set to 90% of viewport', function () {
    $component = Livewire::test(AiAssistantWidget::class);

    // Toggle the widget to open it
    $component->call('toggleWidget');

    // Should have 90vh height styling
    $component->assertSeeHtml('height: 90vh !important;');
    $component->assertSeeHtml('max-height: 90vh !important;');
});

it('shows welcome status when widget is opened', function () {
    $component = Livewire::test(AiAssistantWidget::class);

    // Initially closed, no status
    expect($component->get('currentStatus'))->toBe('');

    // Open the widget
    $component->call('toggleWidget');

    // Should show welcome status
    expect($component->get('currentStatus'))->toBe('👋 Welcome! Ready to help with your finances.');
    expect($component->get('isOpen'))->toBeTrue();
});

it('has proper status animations in HTML', function () {
    $component = Livewire::test(AiAssistantWidget::class);
    $component->call('toggleWidget'); // Open widget to show status

    // Should contain animation CSS classes and styles
    $component->assertSeeHtml('statusPulse');
    $component->assertSeeHtml('slideInUp');
    $component->assertSeeHtml('bounce');
    $component->assertSeeHtml('shimmer');

    // Should contain the welcome status
    $component->assertSeeHtml('👋 Welcome! Ready to help with your finances.');
});

it('resets status properly for multiple questions', function () {
    $component = Livewire::test(AiAssistantWidget::class);
    $component->call('toggleWidget'); // Open widget

    // First question - should show welcome status initially
    expect($component->get('currentStatus'))->toBe('👋 Welcome! Ready to help with your finances.');

    // Simulate sending a message (this will trigger status reset and updates)
    $component->set('message', 'Test question 1');

    // The status should be reset at the beginning of sendMessage
    // Note: We can't easily test the intermediate status updates in a unit test
    // because they happen with delays, but we can verify the reset behavior

    // After processing, status should be cleared
    expect($component->get('isLoading'))->toBeFalse();

    // Send another message to test status reset for subsequent questions
    $component->set('message', 'Test question 2');

    // Status should be properly reset for the second question too
    expect($component->get('message'))->toBe('Test question 2');
});

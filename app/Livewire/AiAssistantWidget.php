<?php

namespace App\Livewire;

use App\Helpers\MarkdownHelper;
use App\Models\AssetConfiguration;
use App\Services\AiAssistantService;
use App\Services\CurrentAssetConfiguration;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class AiAssistantWidget extends Component
{
    public bool $isOpen = false;

    public string $message = '';

    public array $conversation = [];

    public bool $isLoading = false;

    public string $currentStatus = '';

    public int $elapsedSeconds = 0;

    public ?float $startTime = null;

    public ?int $currentConfigurationId = null;

    protected AiAssistantService $aiService;

    public function boot(AiAssistantService $aiService): void
    {
        $this->aiService = $aiService;
    }

    public function mount(): void
    {
        // Ensure user is authenticated and has a team
        if (! Auth::check() || ! Auth::user()->current_team_id) {
            \Log::warning('AiAssistantWidget: Attempted to mount without authentication or team');

            return;
        }

        // Get current configuration from session
        $this->detectCurrentConfiguration();

        $this->conversation = [];
    }

    protected function detectCurrentConfiguration(): void
    {
        // Ensure user is authenticated
        if (! Auth::check() || ! Auth::user()) {
            $this->currentConfigurationId = null;

            return;
        }

        // Get current configuration from session using the service
        $currentConfigService = app(CurrentAssetConfiguration::class);
        $config = $currentConfigService->get();

        if ($config) {
            // Verify this configuration belongs to the current user and team for security
            if ($config->user_id === Auth::id() && $config->team_id === Auth::user()->current_team_id) {
                $this->currentConfigurationId = $config->id;
                \Log::info('AiAssistantWidget: Detected configuration from session', [
                    'config_id' => $config->id,
                    'config_name' => $config->name,
                    'user_id' => $config->user_id,
                    'team_id' => $config->team_id,
                ]);
            } else {
                \Log::warning('AiAssistantWidget: Configuration in session does not belong to current user/team', [
                    'config_id' => $config->id,
                    'config_user_id' => $config->user_id,
                    'config_team_id' => $config->team_id,
                    'current_user_id' => Auth::id(),
                    'current_team_id' => Auth::user()->current_team_id,
                ]);
                // Clear invalid configuration from session
                $currentConfigService->set(null);
                $this->currentConfigurationId = null;
            }
        } else {
            $this->currentConfigurationId = null;
            \Log::info('AiAssistantWidget: No configuration found in session');
        }
    }

    public function toggleWidget(): void
    {
        $this->isOpen = ! $this->isOpen;

        // Refresh configuration detection when widget is opened
        if ($this->isOpen) {
            $this->detectCurrentConfiguration();
            // Set a test status to verify the display works
            $this->currentStatus = '👋 Welcome! Ready to help with your finances.';
        } else {
            $this->currentStatus = '';
        }
    }

    public function sendMessage(): void
    {
        \Log::info('AiAssistantWidget: sendMessage called', ['message' => $this->message]);

        if (empty(trim($this->message))) {
            \Log::info('AiAssistantWidget: Message is empty, returning');

            return;
        }

        // Add user message to conversation first
        $this->conversation[] = [
            'type' => 'user',
            'message' => trim($this->message),
            'timestamp' => now()->format('H:i'),
        ];

        $userMessage = $this->message;
        $this->message = '';

        // Start the loading process
        $this->isLoading = true;
        $this->currentStatus = '🔍 Analyzing your question...';
        $this->startTime = microtime(true);
        $this->elapsedSeconds = 0;

        // Always refresh configuration detection before processing
        $this->detectCurrentConfiguration();

        \Log::info('AiAssistantWidget: Processing message', [
            'message' => $userMessage,
            'current_config_id' => $this->currentConfigurationId,
            'isLoading' => $this->isLoading,
        ]);

        try {
            // Process message with AI service with real-time status updates
            $response = $this->aiService->processMessage(
                $userMessage,
                $this->conversation,
                Auth::user(),
                $this->currentConfigurationId,
                function ($status) {
                    // Update the status property
                    $this->currentStatus = $status;

                    // Update elapsed time
                    if ($this->startTime !== null) {
                        $this->elapsedSeconds = (int) floor(microtime(true) - $this->startTime);
                    }

                    \Log::info('AiAssistantWidget: Status updated', [
                        'status' => $status,
                        'elapsed' => $this->elapsedSeconds,
                    ]);

                    // Use JavaScript to update UI immediately during processing
                    $this->js(sprintf(
                        "
                        const statusElements = document.querySelectorAll('[data-ai-status]');
                        const elapsedElements = document.querySelectorAll('[data-ai-elapsed]');
                        statusElements.forEach(el => el.textContent = %s);
                        elapsedElements.forEach(el => el.textContent = %s + 's');
                        console.log('AI Status updated:', %s, 'Elapsed:', %s);
                        ",
                        json_encode($status),
                        $this->elapsedSeconds,
                        json_encode($status),
                        $this->elapsedSeconds
                    ));
                }
            );

            \Log::info('AiAssistantWidget: Got AI response', ['response' => $response]);

            // Add assistant response to conversation
            $this->conversation[] = [
                'type' => 'assistant',
                'message' => $response['message'],
                'timestamp' => now()->format('H:i'),
                'data' => $response['data'] ?? null,
            ];

            // Update current configuration if one was created/selected
            if (isset($response['configuration_id'])) {
                $this->currentConfigurationId = $response['configuration_id'];
            }

        } catch (\Exception $e) {
            \Log::error('AiAssistantWidget: Error processing message', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            $this->conversation[] = [
                'type' => 'assistant',
                'message' => 'I apologize, but I encountered an error processing your request. Please try again or contact support if the issue persists.',
                'timestamp' => now()->format('H:i'),
                'error' => true,
            ];
        }

        $this->isLoading = false;
        $this->currentStatus = '';
        $this->startTime = null;
        $this->elapsedSeconds = 0;

        \Log::info('AiAssistantWidget: Finished processing, conversation count: '.count($this->conversation));
    }

    public function updateElapsedTime(): void
    {
        if ($this->startTime !== null && $this->isLoading) {
            $this->elapsedSeconds = (int) floor(microtime(true) - $this->startTime);
        }
    }

    public function refreshConfiguration(): void
    {
        $this->detectCurrentConfiguration();
        \Log::info('AiAssistantWidget: Configuration refreshed', ['config_id' => $this->currentConfigurationId]);
    }

    public function clearConversation(): void
    {
        $this->conversation = [];
        $this->currentConfigurationId = null;
    }

    #[On('configuration-selected')]
    public function setConfiguration(int $configurationId): void
    {
        $this->currentConfigurationId = $configurationId;

        $configuration = AssetConfiguration::find($configurationId);
        if ($configuration) {
            $this->conversation[] = [
                'type' => 'assistant',
                'message' => "I've switched to working with your '{$configuration->name}' configuration. What would you like to do with it?",
                'timestamp' => now()->format('H:i'),
            ];
        }
    }

    /**
     * Format a message with markdown support for AI assistant display
     */
    public function formatMessage(string $message): string
    {
        return MarkdownHelper::aiContentToHtml($message);
    }

    /**
     * Get conversation with formatted messages for display
     */
    public function getFormattedConversationProperty(): array
    {
        return collect($this->conversation)->map(function ($message) {
            if ($message['type'] === 'assistant') {
                $message['formatted_message'] = $this->formatMessage($message['message']);
            }

            return $message;
        })->toArray();
    }

    public function render()
    {
        // Don't render if user is not authenticated or doesn't have a team
        if (! Auth::check() || ! Auth::user()?->current_team_id) {
            return view('livewire.empty');
        }

        return view('livewire.ai-assistant-widget');
    }
}

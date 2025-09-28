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

    public ?int $currentConfigurationId = null;

    protected AiAssistantService $aiService;

    public function boot(AiAssistantService $aiService): void
    {
        $this->aiService = $aiService;
    }

    public function mount(): void
    {
        // Get current configuration from session
        $this->detectCurrentConfiguration();

        $this->conversation = [
            [
                'type' => 'assistant',
                'message' => 'Hello! I\'m your AI financial assistant. I can help you create and manage your financial configurations, assets, and plan for your future. How can I help you today?',
                'timestamp' => now()->format('H:i'),
            ],
        ];
    }

    protected function detectCurrentConfiguration(): void
    {
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
        }
    }

    public function sendMessage(): void
    {
        \Log::info('AiAssistantWidget: sendMessage called', ['message' => $this->message]);

        if (empty(trim($this->message))) {
            \Log::info('AiAssistantWidget: Message is empty, returning');

            return;
        }

        // Start the loading process with status updates
        $this->isLoading = true;
        $this->updateStatus('🔍 Interpreting your question...');

        // Always refresh configuration detection before processing
        $this->detectCurrentConfiguration();

        \Log::info('AiAssistantWidget: Processing message', [
            'message' => $this->message,
            'current_config_id' => $this->currentConfigurationId,
        ]);

        // Add user message to conversation
        $this->conversation[] = [
            'type' => 'user',
            'message' => trim($this->message),
            'timestamp' => now()->format('H:i'),
        ];

        $userMessage = $this->message;
        $this->message = '';

        \Log::info('AiAssistantWidget: Added user message, starting AI processing');

        try {
            // Process message with AI service
            $response = $this->aiService->processMessage(
                $userMessage,
                $this->conversation,
                Auth::user(),
                $this->currentConfigurationId,
                function ($status) {
                    $this->updateStatus($status);
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
            $this->updateStatus('❌ Processing error...');

            $this->conversation[] = [
                'type' => 'assistant',
                'message' => 'I apologize, but I encountered an error processing your request. Please try again or contact support if the issue persists.',
                'timestamp' => now()->format('H:i'),
                'error' => true,
            ];
        }

        $this->isLoading = false;
        $this->currentStatus = '';
        \Log::info('AiAssistantWidget: Finished processing, conversation count: '.count($this->conversation));
    }

    public function refreshConfiguration(): void
    {
        $this->detectCurrentConfiguration();
        \Log::info('AiAssistantWidget: Configuration refreshed', ['config_id' => $this->currentConfigurationId]);
    }

    public function clearConversation(): void
    {
        $this->conversation = [
            [
                'type' => 'assistant',
                'message' => 'Conversation cleared. How can I help you today?',
                'timestamp' => now()->format('H:i'),
            ],
        ];
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
     * Update the current status and refresh the UI
     */
    protected function updateStatus(string $status): void
    {
        $this->currentStatus = $status;
        // Force Livewire to update the UI immediately
        $this->dispatch('status-updated', status: $status);
        // Add a small delay to make status visible
        usleep(200000); // 0.2 seconds for better visibility
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
        return view('livewire.ai-assistant-widget');
    }
}

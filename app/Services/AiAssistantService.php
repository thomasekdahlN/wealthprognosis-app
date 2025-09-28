<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetType;
use App\Models\AssetYear;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAssistantService
{
    protected array $conversationState = [];

    protected array $pendingConfiguration = [];

    protected string $aiProvider;

    protected string $aiModel;

    protected ?string $apiKey;

    public function __construct()
    {
        $this->aiProvider = config('ai.provider', 'openai');
        $this->aiModel = config('ai.model', 'gpt-4');
        $this->apiKey = config('ai.api_key', env('OPENAI_API_KEY'));
    }

    public function processMessage(string $message, array $conversation, User $user, ?int $currentConfigurationId = null, ?callable $statusCallback = null): array
    {
        $originalMessage = trim($message);
        $message = trim(strtolower($message));

        // Log the incoming request
        $this->logAiInteraction('request', [
            'user_id' => $user->id,
            'configuration_id' => $currentConfigurationId,
            'message' => $originalMessage,
            'timestamp' => now()->toISOString(),
        ]);

        // Analyze the message intent
        if ($statusCallback) {
            $statusCallback('🧠 Analyzing intent and context...');
        }
        $intent = $this->analyzeIntent($message, $conversation);

        // For general question intents, include financial context and use real AI
        $needsContext = in_array($intent['type'], ['general_question', 'unknown']);

        if ($needsContext && $currentConfigurationId) {
            if ($statusCallback) {
                $statusCallback('📊 Loading your financial data...');
            }
            $contextData = $this->getFinancialContextForAI($currentConfigurationId, $user);
            if ($contextData) {
                // Log the context being sent to AI
                $this->logAiInteraction('context', [
                    'user_id' => $user->id,
                    'configuration_id' => $currentConfigurationId,
                    'context_data' => $contextData,
                    'timestamp' => now()->toISOString(),
                ]);

                if ($statusCallback) {
                    $statusCallback('🤖 Sending request to AI...');
                }
                // Use real AI service for contextual responses
                $aiResponse = $this->callAiService($originalMessage, $contextData, $user);

                if ($statusCallback) {
                    $statusCallback('✨ Formatting response...');
                }
                // Log the AI response
                $this->logAiInteraction('response', [
                    'user_id' => $user->id,
                    'configuration_id' => $currentConfigurationId,
                    'ai_response' => $aiResponse,
                    'timestamp' => now()->toISOString(),
                ]);

                return [
                    'message' => $aiResponse,
                ];
            }
        }

        // Process based on intent
        return match ($intent['type']) {
            'switch_configuration' => $this->handleSwitchConfiguration($message, $intent, $user),
            'create_configuration' => $this->handleCreateConfiguration($message, $intent, $user),
            'add_asset' => $this->handleAddAsset($message, $intent, $user, $currentConfigurationId),
            'add_income' => $this->handleAddIncome($message, $intent, $user, $currentConfigurationId),
            'add_life_event' => $this->handleAddLifeEvent($message, $intent, $user, $currentConfigurationId),
            'view_data' => $this->handleViewData($message, $intent, $user, $currentConfigurationId),
            'create_simulation' => $this->handleCreateSimulation($message, $intent, $user, $currentConfigurationId),
            'general_help' => $this->handleGeneralHelp($message, $intent),
            default => $this->handleUnknown($message, $currentConfigurationId, $user),
        };
    }

    protected function analyzeIntent(string $message, array $conversation): array
    {
        // Check for configuration switching keywords
        if (preg_match('/switch|change|select|use.*configuration|config.*(\d+)/i', $message)) {
            return ['type' => 'switch_configuration', 'confidence' => 0.9];
        }

        // Check for configuration creation keywords
        if (preg_match('/create|new|start|setup.*configuration|profile|plan/i', $message)) {
            return ['type' => 'create_configuration', 'confidence' => 0.9];
        }

        // Check for asset-related keywords first (higher priority than general questions)
        if (preg_match('/(?:add|create|have|own).*(?:asset|house|car|boat|cabin|fund|crypto|property|investment|tesla|bmw|mercedes|audi|volvo|toyota|honda|ford|volkswagen|porsche|ferrari|lamborghini|maserati|bentley|rolls.?royce|bitcoin|ethereum)/i', $message)) {
            return ['type' => 'add_asset', 'confidence' => 0.9];
        }

        // Check for explicit asset addition with value patterns
        if (preg_match('/(?:house|car|boat|cabin|fund|crypto|property|investment|tesla|bmw|mercedes|audi|volvo|toyota|honda|ford|volkswagen|porsche|ferrari|lamborghini|maserati|bentley|rolls.?royce|bitcoin|ethereum).*(?:worth|value|cost|price).*\d+/i', $message)) {
            return ['type' => 'add_asset', 'confidence' => 0.95];
        }

        // Check for income-related keywords
        if (preg_match('/income|salary|wage|pension|benefit|barnetrygd|earn/i', $message)) {
            return ['type' => 'add_income', 'confidence' => 0.8];
        }

        // Check for life events
        if (preg_match('/retirement|retire|kids|children|inheritance|move|house.*change|plan.*for/i', $message)) {
            return ['type' => 'add_life_event', 'confidence' => 0.7];
        }

        // Check for viewing data (higher priority than general questions)
        if (preg_match('/show.*complete|show.*financial.*data|show.*analysis|view.*complete|view.*financial.*data|show|view|list|what.*have|current.*assets|my.*configuration|current.*configuration|configuration.*summary/i', $message)) {
            return ['type' => 'view_data', 'confidence' => 0.9];
        }

        // Check for simulation
        if (preg_match('/simulation|simulate|forecast|predict|future|scenario/i', $message)) {
            return ['type' => 'create_simulation', 'confidence' => 0.8];
        }

        // Check for general questions about finances (needs context) - lower priority
        if (preg_match('/how.*much|what.*value|when.*retire|can.*afford|should.*invest|recommend|advice|analyze|analysis|compare|best|worst|risk|return|yield|profit|loss|tax|expense|income|cash.*flow|net.*worth|fire|financial.*independence|total|summary|balance|overview/i', $message)) {
            return ['type' => 'general_question', 'confidence' => 0.8];
        }

        // Check for help
        if (preg_match('/help|what.*can.*do|how.*work|guide/i', $message)) {
            return ['type' => 'general_help', 'confidence' => 0.9];
        }

        return ['type' => 'unknown', 'confidence' => 0.0];
    }

    protected function getFinancialContextForAI(int $configurationId, User $user): ?string
    {
        try {
            $configuration = AssetConfiguration::where('id', $configurationId)
                ->where('team_id', $user->current_team_id)
                ->with(['assets.years', 'assets.assetType'])
                ->first();

            if (! $configuration) {
                return null;
            }

            return $this->getCompleteFinancialData($configuration->id, $user);
        } catch (\Exception $e) {
            Log::error('Failed to get financial context for AI', [
                'configuration_id' => $configurationId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function generateContextualResponse(string $originalMessage, string $contextData, array $intent): string
    {
        // This is where you would integrate with an actual AI service like OpenAI
        // For now, we'll provide a helpful response indicating we have the context

        $responses = [
            "I can see your complete financial situation and I'm analyzing your question: **{$originalMessage}**\n\n".
            "Based on your current asset configuration, I have access to:\n".
            "• **All your assets** and their current values\n".
            "• **Income and expense projections** by year\n".
            "• **Asset growth rates** and change patterns\n".
            "• **Complete financial timeline** from now until your planned death age\n\n".
            "**💡 To get the most detailed analysis, you can:**\n".
            "• Ask specific questions about your net worth, retirement readiness, or asset allocation\n".
            "• Request comparisons between different assets or scenarios\n".
            "• Get recommendations for optimizing your financial plan\n\n".
            '**🔍 Your financial data is ready for analysis!** What specific aspect would you like me to focus on?',

            "Perfect! I have your complete financial profile loaded and ready for analysis.\n\n".
            "**Your question:** *{$originalMessage}*\n\n".
            "**📊 Available for analysis:**\n".
            "• Complete asset portfolio with market values\n".
            "• Income streams and expense patterns\n".
            "• Growth projections and change rates\n".
            "• Multi-year financial timeline\n\n".
            "**🎯 I can help you with:**\n".
            "• **Net worth calculations** and projections\n".
            "• **Retirement planning** and FIRE analysis\n".
            "• **Asset allocation** recommendations\n".
            "• **Risk assessment** and optimization strategies\n\n".
            'What specific financial insights would you like me to provide?',
        ];

        return $responses[array_rand($responses)];
    }

    protected function handleCreateConfiguration(string $message, array $intent, User $user): array
    {
        // Check if we have all required information
        $requiredFields = ['name', 'birth_year', 'death_age', 'pension_wish_age', 'risk_tolerance'];
        $missingFields = [];

        // Extract information from message
        $extractedData = $this->extractConfigurationData($message);

        foreach ($requiredFields as $field) {
            if (! isset($extractedData[$field])) {
                $missingFields[] = $field;
            }
        }

        if (! empty($missingFields)) {
            return [
                'message' => $this->askForMissingConfigurationData($missingFields, $extractedData),
            ];
        }

        // Create the configuration
        try {
            $configuration = $this->createAssetConfiguration($extractedData, $user);

            return [
                'message' => "🎉 **Great! I've created your configuration '{$configuration->name}'.**\n\n".
                           "Now let's add your income sources. What sources of income do you have and how much do you earn from each?\n\n".
                           "For example: *'I earn 500,000 NOK per year from my job as a software developer'*",
                'configuration_id' => $configuration->id,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create configuration', ['error' => $e->getMessage(), 'data' => $extractedData]);

            return [
                'message' => '❌ **I encountered an error creating your configuration.**\n\n'.
                           'Please try again or provide the information in a different format. Make sure to include your name, birth year, expected lifespan, retirement age, and risk tolerance.',
            ];
        }
    }

    protected function handleSwitchConfiguration(string $message, array $intent, User $user): array
    {
        // Extract configuration ID from message
        if (preg_match('/(\d+)/', $message, $matches)) {
            $configId = (int) $matches[1];

            $configuration = AssetConfiguration::where('id', $configId)
                ->where('user_id', $user->id)
                ->where('team_id', $user->current_team_id)
                ->first();

            if ($configuration) {
                return [
                    'message' => "✅ **Switched to configuration '{$configuration->name}'.**\n\n".
                               'You can now add assets, income sources, or ask me about this configuration. What would you like to do?',
                    'configuration_id' => $configuration->id,
                ];
            }
        }

        return [
            'message' => "❌ **Configuration not found or invalid ID.**\n\n".
                       'Please provide a valid configuration ID. You can only switch to configurations that belong to you.',
        ];
    }

    protected function handleAddAsset(string $message, array $intent, User $user, ?int $configurationId): array
    {
        if (! $configurationId) {
            return [
                'message' => '⚠️ **Please create or select a configuration first before adding assets.**\n\n'.
                           "You can create a new configuration by saying: *'Create a new financial configuration'*",
            ];
        }

        $assetData = $this->extractAssetData($message);

        if (empty($assetData['type']) || empty($assetData['value'])) {
            return [
                'message' => '📝 **I need more information about the asset.**\n\n'.
                           'Please tell me:\n'.
                           '• **Type of asset** (house, car, fund, crypto, etc.)\n'.
                           '• **Current market value** in NOK\n\n'.
                           "For example: *'I have a house worth 3,500,000 NOK'*",
            ];
        }

        try {
            $asset = $this->createAsset($assetData, $configurationId, $user);

            // Generate intelligent response based on extracted data
            $assetName = $this->generateAssetName($assetData);
            $formattedValue = number_format($assetData['value'], 0, ',', ' ');

            $response = "## ✅ Asset Successfully Added!\n\n";
            $response .= "I've successfully added your **{$assetName}** worth **{$formattedValue} NOK** to your configuration.";

            // Add specific details based on asset type
            if ($assetData['type'] === 'car' && isset($assetData['brand'])) {
                $response .= "\n\n### 🚗 Asset Details:\n\n";
                $response .= "- **Brand:** {$assetData['brand']}\n";
                if (isset($assetData['model'])) {
                    $response .= "- **Model:** {$assetData['model']}\n";
                }
                $response .= "- **Current Value:** {$formattedValue} NOK\n";
            }

            if (in_array($assetData['type'], ['house', 'car', 'boat', 'cabin'])) {
                $response .= "\n\n### 🏦 Next Steps\n\n";
                $response .= "**Do you have a mortgage or loan on this asset?**\n\n";
                $response .= "If so, please tell me the loan amount and repayment period.\n\n";
                $response .= "> **Example:** *'I have a 2,500,000 NOK mortgage with 20 years remaining'*";
            }

            return [
                'message' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create asset', ['error' => $e->getMessage(), 'data' => $assetData]);

            return [
                'message' => 'I encountered an error adding your asset. Please try again with more specific information.',
                'data' => null,
            ];
        }
    }

    protected function handleAddIncome(string $message, array $intent, User $user, ?int $configurationId): array
    {
        if (! $configurationId) {
            return [
                'message' => 'Please create or select a configuration first before adding income sources.',
                'data' => null,
            ];
        }

        $incomeData = $this->extractIncomeData($message);

        if (empty($incomeData['amount'])) {
            return [
                'message' => 'Please specify the income amount. For example: "I earn 500,000 NOK per year as salary" or "I receive 20,000 NOK monthly in pension".',
                'data' => null,
            ];
        }

        try {
            $asset = $this->createIncomeAsset($incomeData, $configurationId, $user);

            return [
                'message' => "I've added your {$incomeData['type']} income of {$incomeData['amount']} to your configuration. Do you have any other income sources?",
                'data' => ['income_asset_created' => $asset->id],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create income asset', ['error' => $e->getMessage(), 'data' => $incomeData]);

            return [
                'message' => 'I encountered an error adding your income. Please try again with more specific information.',
                'data' => null,
            ];
        }
    }

    protected function handleAddLifeEvent(string $message, array $intent, User $user, ?int $configurationId): array
    {
        if (! $configurationId) {
            return [
                'message' => 'Please create or select a configuration first before adding life events.',
                'data' => null,
            ];
        }

        $eventData = $this->extractLifeEventData($message);

        if (empty($eventData['type'])) {
            return [
                'message' => 'I can help you plan for various life events like retirement, children leaving home, inheritance, or property changes. What specific event would you like to plan for?',
                'data' => null,
            ];
        }

        try {
            $events = $this->createLifeEvents($eventData, $configurationId, $user);

            return [
                'message' => "I've added your {$eventData['type']} event to your financial plan. This will be factored into your future projections.",
                'data' => ['life_events_created' => count($events)],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create life event', ['error' => $e->getMessage(), 'data' => $eventData]);

            return [
                'message' => '❌ **I encountered an error adding your life event.**\n\n'.
                           'Please try again with more specific information about the event, timing, and financial impact.',
            ];
        }
    }

    protected function handleViewData(string $message, array $intent, User $user, ?int $configurationId): array
    {
        if (! $configurationId) {
            return [
                'message' => '⚠️ **Please select a configuration first.**\n\n'.
                           "You need to have an active configuration to view financial data. You can create a new one by saying: *'Create a new financial configuration'*",
            ];
        }

        // Check if asking for complete financial data for AI analysis
        if (preg_match('/complete|full|json|export|analysis|analyze/i', $message)) {
            return $this->getCompleteFinancialDataForDisplay($configurationId, $user);
        }

        return $this->getConfigurationSummary($configurationId, $user);
    }

    protected function handleCreateSimulation(string $message, array $intent, User $user, ?int $configurationId): array
    {
        if (! $configurationId) {
            return [
                'message' => 'Please select a configuration first before creating a simulation.',
                'data' => null,
            ];
        }

        // This would integrate with existing simulation logic
        return [
            'message' => '🔮 **Simulation creation is coming soon!**\n\n'.
                       'For now, you can create simulations through the main interface. '.
                       'This feature will allow you to run financial projections and scenarios directly from the chat.',
        ];
    }

    protected function handleGeneralHelp(string $message, array $intent): array
    {
        return [
            'message' => "# 👋 AI Financial Assistant\n\n".
                        "I'm here to help you manage your wealth and plan for the future! Here's what I can do:\n\n".
                        "## 🆕 Create Configurations\n".
                        "- *'Create a new financial configuration for John, born 1985'*\n\n".
                        "## 🔄 Switch Configurations\n".
                        "- *'Switch to configuration 2'* or *'Use config 1'*\n\n".
                        "## 🏠 Add Assets\n".
                        "- *'I have a house worth 3,500,000 NOK'*\n".
                        "- *'Add my Tesla Model S worth 800,000 NOK'*\n".
                        "- *'I own Bitcoin worth 100K NOK'*\n\n".
                        "## 💰 Add Income Sources\n".
                        "- *'I earn 650,000 NOK per year as a developer'*\n\n".
                        "## 🎯 Plan Life Events\n".
                        "- *'I'm planning to retire at 62'*\n".
                        "- *'I'm expecting a child next year'*\n\n".
                        "## 📊 View Your Data\n".
                        "- *'Show me my financial summary'*\n".
                        "- *'What is my net worth?'*\n".
                        "- *'When can I retire?'*\n\n".
                        "---\n\n".
                        "**Just tell me what you'd like to do in natural language - I'll understand!** 🚀",
        ];
    }

    protected function handleUnknown(string $message, ?int $currentConfigurationId = null, ?User $user = null): array
    {
        // If we have a configuration, try to provide contextual help
        if ($currentConfigurationId && $user) {
            $contextData = $this->getFinancialContextForAI($currentConfigurationId, $user);
            if ($contextData) {
                return [
                    'message' => "🤔 **I'm not sure I understand that specific request, but I have your complete financial data ready for analysis!**\n\n".
                                "**💡 You can ask me questions like:**\n".
                                "• *'What is my current net worth?'*\n".
                                "• *'When can I retire?'*\n".
                                "• *'How much should I invest?'*\n".
                                "• *'What are my biggest expenses?'*\n".
                                "• *'Show me my asset allocation'*\n".
                                "• *'Analyze my financial situation'*\n\n".
                                "**🎯 Or I can help you:**\n".
                                "• Add new assets or income sources\n".
                                "• Plan for life events like retirement or children\n".
                                "• Create financial simulations\n\n".
                                '**Just ask me anything about your finances in natural language!** 🚀',
                ];
            }
        }

        return [
            'message' => "🤔 **I'm not sure I understand that request.**\n\n".
                        "Could you please rephrase it? I can help you with:\n".
                        "• Creating financial configurations\n".
                        "• Managing assets and income\n".
                        "• Planning for life events\n".
                        "• Viewing your financial data\n\n".
                        "Type **'help'** to see examples of what I can do! 💡",
        ];
    }

    protected function extractConfigurationData(string $message): array
    {
        $data = [];

        // Extract name
        if (preg_match('/name.*?(?:is|:)\s*([^,.\n]+)/i', $message, $matches)) {
            $data['name'] = trim($matches[1]);
        } elseif (preg_match('/(?:call|named?)\s+([^,.\n]+)/i', $message, $matches)) {
            $data['name'] = trim($matches[1]);
        }

        // Extract birth year
        if (preg_match('/born.*?(\d{4})|birth.*?year.*?(\d{4})|(\d{4}).*?born/i', $message, $matches)) {
            $data['birth_year'] = (int) ($matches[1] ?: $matches[2] ?: $matches[3]);
        }

        // Extract death age
        if (preg_match('/live.*?(\d{2,3})|death.*?age.*?(\d{2,3})|die.*?(\d{2,3})/i', $message, $matches)) {
            $data['death_age'] = (int) ($matches[1] ?: $matches[2] ?: $matches[3]);
        }

        // Extract pension wish age
        if (preg_match('/retire.*?(\d{2})|pension.*?age.*?(\d{2})|retirement.*?(\d{2})/i', $message, $matches)) {
            $data['pension_wish_age'] = (int) ($matches[1] ?: $matches[2] ?: $matches[3]);
        }

        // Extract risk tolerance
        if (preg_match('/risk.*?(low|medium|high|conservative|moderate|aggressive)/i', $message, $matches)) {
            $riskMap = [
                'low' => 'low', 'conservative' => 'low',
                'medium' => 'medium', 'moderate' => 'medium',
                'high' => 'high', 'aggressive' => 'high',
            ];
            $data['risk_tolerance'] = $riskMap[strtolower($matches[1])];
        }

        return $data;
    }

    protected function askForMissingConfigurationData(array $missingFields, array $extractedData): string
    {
        $questions = [
            'name' => 'What would you like to name this configuration?',
            'birth_year' => 'What year were you born?',
            'death_age' => 'How old do you expect to live to?',
            'pension_wish_age' => 'At what age would you like to retire?',
            'risk_tolerance' => 'What is your risk tolerance? (low, medium, or high)',
        ];

        $currentInfo = '';
        if (! empty($extractedData)) {
            $currentInfo = "\n\nI already have:\n";
            foreach ($extractedData as $key => $value) {
                $currentInfo .= '- '.ucfirst(str_replace('_', ' ', $key)).": {$value}\n";
            }
        }

        $nextQuestion = $questions[$missingFields[0]] ?? 'Please provide more information.';

        return "To create your financial configuration, I need some basic information.{$currentInfo}\n{$nextQuestion}";
    }

    protected function createAssetConfiguration(array $data, User $user): AssetConfiguration
    {
        return DB::transaction(function () use ($data, $user) {
            return AssetConfiguration::create([
                'name' => $data['name'],
                'birth_year' => $data['birth_year'],
                'death_age' => $data['death_age'],
                'pension_wish_age' => $data['pension_wish_age'],
                'risk_tolerance' => $data['risk_tolerance'],
                'user_id' => $user->id,
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => md5(json_encode($data)),
                'updated_checksum' => md5(json_encode($data)),
            ]);
        });
    }

    protected function extractAssetData(string $message): array
    {
        $data = [];

        // Enhanced asset type detection with brand names and specific models
        $assetTypePatterns = [
            'car' => [
                'patterns' => [
                    '/\b(?:tesla|bmw|mercedes|audi|volvo|toyota|honda|ford|volkswagen|porsche|ferrari|lamborghini|maserati|bentley|rolls.?royce)\b/i',
                    '/\b(?:car|vehicle|automobile|auto|bil)\b/i',
                    '/\b(?:model\s+[a-z0-9]+|series\s+\d+)\b/i',
                ],
                'brands' => ['tesla', 'bmw', 'mercedes', 'audi', 'volvo', 'toyota', 'honda', 'ford', 'volkswagen', 'porsche', 'ferrari', 'lamborghini', 'maserati', 'bentley', 'rolls royce'],
            ],
            'house' => [
                'patterns' => [
                    '/\b(?:house|home|residence|villa|mansion|townhouse|apartment|flat|condo|condominium|hus|bolig|leilighet)\b/i',
                ],
            ],
            'boat' => [
                'patterns' => [
                    '/\b(?:boat|yacht|sailboat|motorboat|vessel|ship|båt|seilbåt|motorbåt)\b/i',
                ],
            ],
            'cabin' => [
                'patterns' => [
                    '/\b(?:cabin|cottage|chalet|summer.?house|vacation.?home|hytte|fritidsbolig)\b/i',
                ],
            ],
            'fund' => [
                'patterns' => [
                    '/\b(?:fund|mutual.?fund|index.?fund|etf|investment.?fund|fond|aksjefond|indeksfond)\b/i',
                ],
            ],
            'crypto' => [
                'patterns' => [
                    '/\b(?:crypto|cryptocurrency|bitcoin|ethereum|btc|eth|dogecoin|litecoin|ripple|cardano|polkadot|chainlink|stellar|krypto)\b/i',
                ],
            ],
            'stock' => [
                'patterns' => [
                    '/\b(?:stock|share|equity|aksje|aksjer|shares)\b/i',
                ],
            ],
            'bond' => [
                'patterns' => [
                    '/\b(?:bond|government.?bond|corporate.?bond|obligasjon|statsobligasjon)\b/i',
                ],
            ],
            'investment' => [
                'patterns' => [
                    '/\b(?:investment|portfolio|asset|investering|portefølje)\b/i',
                ],
            ],
            'property' => [
                'patterns' => [
                    '/\b(?:property|real.?estate|land|plot|eiendom|tomt|grunn)\b/i',
                ],
            ],
        ];

        // Extract asset type with enhanced detection
        foreach ($assetTypePatterns as $type => $config) {
            foreach ($config['patterns'] as $pattern) {
                if (preg_match($pattern, $message)) {
                    $data['type'] = $type;
                    break 2;
                }
            }
        }

        // Extract specific asset name/brand for better naming
        if (isset($data['type']) && $data['type'] === 'car') {
            foreach ($assetTypePatterns['car']['brands'] as $brand) {
                if (preg_match("/\b{$brand}\b/i", $message, $matches)) {
                    $data['brand'] = ucfirst(strtolower($matches[0]));

                    // Try to extract model with better patterns
                    $modelPatterns = [
                        "/\b{$brand}\s+(model\s+[a-z0-9]+|[a-z0-9\-]+(?:\s+[a-z0-9\-]+)?)(?:\s+(?:worth|value|cost|price|to|as|\d+)|$)/i",
                        "/\b{$brand}\s+([a-z0-9\s\-]+?)(?:\s+(?:car|vehicle|worth|value|cost|price|\d+)|$)/i",
                    ];

                    foreach ($modelPatterns as $pattern) {
                        if (preg_match($pattern, $message, $modelMatches)) {
                            $model = trim($modelMatches[1]);
                            // Clean up common extraction issues
                            $model = preg_replace('/\s+(car|vehicle|to|as|worth|value|cost|price).*$/i', '', $model);
                            if (strlen($model) > 0 && strlen($model) < 30) {
                                $data['model'] = $model;
                                break;
                            }
                        }
                    }
                    break;
                }
            }
        }

        // Enhanced value extraction with better number parsing
        $valuePatterns = [
            // Handle various number formats: 500K, 500k, 500,000, 500 000, 500.000
            '/(\d+(?:[,.\s]\d+)*)\s*k(?:r|roner)?\b/i' => 1000, // 500K, 500kr
            '/(\d+(?:[,.\s]\d+)*)\s*(?:thousand|tusen)\b/i' => 1000,
            '/(\d+(?:[,.\s]\d+)*)\s*m(?:illion)?\b/i' => 1000000, // 2M, 2 million
            '/(\d+(?:[,.\s]\d+)*)\s*(?:million|millioner)\b/i' => 1000000,
            '/(?:worth|value|cost|price|verdi).*?(\d+(?:[,.\s]\d+)*)\s*(?:nok|kr|kroner)?\b/i' => 1,
            '/(\d+(?:[,.\s]\d+)*)\s*(?:nok|kr|kroner)\b/i' => 1,
            '/(\d+(?:[,.\s]\d+)*)\b/i' => 1, // Plain numbers as fallback
        ];

        foreach ($valuePatterns as $pattern => $multiplier) {
            if (preg_match($pattern, $message, $matches)) {
                $value = preg_replace('/[,\s]/', '', $matches[1]);
                $data['value'] = (int) ($value * $multiplier);
                break;
            }
        }

        // Extract mortgage information
        if (preg_match('/mortgage.*?(\d+(?:[,.\s]\d+)*)/i', $message, $matches)) {
            $data['mortgage'] = (int) preg_replace('/[,\s]/', '', $matches[1]);
        }

        if (preg_match('/(\d+)\s*years?.*?mortgage|mortgage.*?(\d+)\s*years?/i', $message, $matches)) {
            $data['mortgage_years'] = (int) ($matches[1] ?: $matches[2]);
        }

        return $data;
    }

    protected function createAsset(array $data, int $configurationId, User $user): Asset
    {
        return DB::transaction(function () use ($data, $configurationId, $user) {
            // Get or create asset type
            $assetType = AssetType::firstOrCreate(
                ['type' => $data['type']],
                [
                    'name' => ucfirst($data['type']),
                    'description' => "Auto-created {$data['type']} asset type",
                    'is_fire_sellable' => in_array($data['type'], ['fund', 'crypto', 'investment', 'stock', 'bond']),
                    'team_id' => $user->current_team_id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]
            );

            // Generate intelligent asset name
            $assetName = $this->generateAssetName($data);

            // Create asset
            $asset = Asset::create([
                'name' => $assetName,
                'asset_type' => $assetType->type,
                'asset_configuration_id' => $configurationId,
                'user_id' => $user->id,
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => md5(json_encode($data)),
                'updated_checksum' => md5(json_encode($data)),
            ]);

            // Create asset year with current value
            AssetYear::create([
                'asset_id' => $asset->id,
                'asset_configuration_id' => $configurationId,
                'year' => now()->year,
                'asset_market_amount' => $data['value'],
                'user_id' => $user->id,
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => md5(json_encode(['asset_market_amount' => $data['value']])),
                'updated_checksum' => md5(json_encode(['asset_market_amount' => $data['value']])),
            ]);

            return $asset;
        });
    }

    protected function generateAssetName(array $data): string
    {
        $type = $data['type'];
        $brand = $data['brand'] ?? null;
        $model = $data['model'] ?? null;

        switch ($type) {
            case 'car':
                if ($brand && $model) {
                    return "{$brand} {$model}";
                } elseif ($brand) {
                    return "{$brand} Car";
                } else {
                    return 'Car';
                }

            case 'house':
                return 'House';

            case 'boat':
                if ($brand && $model) {
                    return "{$brand} {$model} Boat";
                } elseif ($brand) {
                    return "{$brand} Boat";
                } else {
                    return 'Boat';
                }

            case 'cabin':
                return 'Cabin';

            case 'fund':
                return 'Investment Fund';

            case 'crypto':
                if ($brand) {
                    return ucfirst($brand);
                } else {
                    return 'Cryptocurrency';
                }

            case 'stock':
                if ($brand) {
                    return "{$brand} Stock";
                } else {
                    return 'Stock Investment';
                }

            case 'bond':
                return 'Bond Investment';

            case 'investment':
                return 'Investment';

            case 'property':
                return 'Property';

            default:
                return ucfirst($type).' Asset';
        }
    }

    protected function extractIncomeData(string $message): array
    {
        $data = [];

        // Extract income type
        $incomeTypes = ['salary', 'wage', 'pension', 'benefit', 'barnetrygd', 'dividend', 'rental'];
        foreach ($incomeTypes as $type) {
            if (preg_match("/\b{$type}\b/i", $message)) {
                $data['type'] = $type;
                break;
            }
        }

        if (! isset($data['type'])) {
            $data['type'] = 'salary'; // Default
        }

        // Extract amount
        if (preg_match('/(\d+(?:[,.\s]\d+)*)\s*(?:nok|kr|kroner)/i', $message, $matches)) {
            $amount = (int) preg_replace('/[,\s]/', '', $matches[1]);

            // Determine if monthly or yearly
            if (preg_match('/month|monthly|per month/i', $message)) {
                $data['amount'] = $amount * 12; // Convert to yearly
                $data['frequency'] = 'monthly';
            } else {
                $data['amount'] = $amount;
                $data['frequency'] = 'yearly';
            }
        }

        return $data;
    }

    protected function createIncomeAsset(array $data, int $configurationId, User $user): Asset
    {
        return DB::transaction(function () use ($data, $configurationId, $user) {
            // Get or create income asset type
            $assetType = AssetType::firstOrCreate(
                ['type' => $data['type']],
                [
                    'name' => ucfirst($data['type']),
                    'description' => "Auto-created {$data['type']} income type",
                    'is_fire_sellable' => false,
                    'team_id' => $user->current_team_id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]
            );

            // Create income asset
            $asset = Asset::create([
                'name' => ucfirst($data['type']).' Income',
                'asset_type' => $assetType->type,
                'asset_configuration_id' => $configurationId,
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => md5(json_encode($data)),
                'updated_checksum' => md5(json_encode($data)),
            ]);

            // Create asset year with income amount
            AssetYear::create([
                'asset_id' => $asset->id,
                'year' => now()->year,
                'income' => $data['amount'],
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => md5(json_encode(['income' => $data['amount']])),
                'updated_checksum' => md5(json_encode(['income' => $data['amount']])),
            ]);

            return $asset;
        });
    }

    protected function extractLifeEventData(string $message): array
    {
        $data = [];

        // Extract event type
        if (preg_match('/retirement|retire/i', $message)) {
            $data['type'] = 'retirement';
        } elseif (preg_match('/kids?|children/i', $message)) {
            $data['type'] = 'children';
        } elseif (preg_match('/inheritance/i', $message)) {
            $data['type'] = 'inheritance';
        } elseif (preg_match('/move|house.*change|property.*change/i', $message)) {
            $data['type'] = 'property_change';
        }

        // Extract timing
        if (preg_match('/(\d{4})|in\s+(\d+)\s+years?|at\s+age\s+(\d+)/i', $message, $matches)) {
            if ($matches[1]) {
                $data['year'] = (int) $matches[1];
            } elseif ($matches[2]) {
                $data['year'] = now()->year + (int) $matches[2];
            } elseif ($matches[3]) {
                // Would need birth year from configuration to calculate
                $data['age'] = (int) $matches[3];
            }
        }

        // Extract amounts
        if (preg_match('/(\d+(?:[,.\s]\d+)*)\s*(?:nok|kr|kroner)/i', $message, $matches)) {
            $data['amount'] = (int) preg_replace('/[,\s]/', '', $matches[1]);
        }

        return $data;
    }

    protected function createLifeEvents(array $data, int $configurationId, User $user): array
    {
        $events = [];

        switch ($data['type']) {
            case 'retirement':
                $events = $this->createRetirementEvents($data, $configurationId, $user);
                break;
            case 'children':
                $events = $this->createChildrenEvents($data, $configurationId, $user);
                break;
            case 'inheritance':
                $events = $this->createInheritanceEvent($data, $configurationId, $user);
                break;
            case 'property_change':
                $events = $this->createPropertyChangeEvent($data, $configurationId, $user);
                break;
        }

        return $events;
    }

    protected function createRetirementEvents(array $data, int $configurationId, User $user): array
    {
        $configuration = AssetConfiguration::find($configurationId);
        $retirementYear = $data['year'] ?? (now()->year + ($configuration->pension_wish_age - (now()->year - $configuration->birth_year)));

        $events = [];

        // Stop salary income
        $salaryAssets = Asset::where('asset_configuration_id', $configurationId)
            ->where('asset_type', 'salary')
            ->get();

        foreach ($salaryAssets as $asset) {
            AssetYear::create([
                'asset_id' => $asset->id,
                'year' => $retirementYear,
                'income' => 0,
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => md5('retirement_stop'),
                'updated_checksum' => md5('retirement_stop'),
            ]);
            $events[] = 'Stopped salary income';
        }

        return $events;
    }

    protected function createChildrenEvents(array $data, int $configurationId, User $user): array
    {
        $planningService = app(FinancialPlanningService::class);

        return $planningService->createChildrenEvents($data, $configurationId, $user);
    }

    protected function createInheritanceEvent(array $data, int $configurationId, User $user): array
    {
        $planningService = app(FinancialPlanningService::class);

        return $planningService->createInheritanceEvent($data, $configurationId, $user);
    }

    protected function createPropertyChangeEvent(array $data, int $configurationId, User $user): array
    {
        $planningService = app(FinancialPlanningService::class);

        return $planningService->createPropertyChangeEvent($data, $configurationId, $user);
    }

    protected function getConfigurationSummary(int $configurationId, User $user): array
    {
        $configuration = AssetConfiguration::with(['assets.years'])
            ->where('id', $configurationId)
            ->where('user_id', $user->id)
            ->where('team_id', $user->current_team_id)
            ->first();

        if (! $configuration) {
            return [
                'message' => 'Configuration not found.',
                'data' => null,
            ];
        }

        $currentYear = now()->year;

        $totalAssets = $configuration->assets->sum(function ($asset) use ($currentYear) {
            // Try current year first, then fall back to most recent year
            $currentYearData = $asset->years->where('year', $currentYear);
            if ($currentYearData->isNotEmpty()) {
                return $currentYearData->sum('asset_market_amount');
            }
            // Fall back to most recent year
            $mostRecentYear = $asset->years->sortByDesc('year')->first();

            return $mostRecentYear ? $mostRecentYear->asset_market_amount : 0;
        });

        $totalIncome = $configuration->assets->sum(function ($asset) use ($currentYear) {
            // Try current year first, then fall back to most recent year
            $currentYearData = $asset->years->where('year', $currentYear);
            if ($currentYearData->isNotEmpty()) {
                return $currentYearData->sum('income_amount');
            }
            // Fall back to most recent year
            $mostRecentYear = $asset->years->sortByDesc('year')->first();

            return $mostRecentYear ? $mostRecentYear->income_amount : 0;
        });

        $assetCount = $configuration->assets->count();

        $summary = "**{$configuration->name} Summary:**\n\n";
        $summary .= "👤 **Personal Info:**\n";
        $summary .= "- Born: {$configuration->birth_year}\n";
        $summary .= "- Expected lifespan: {$configuration->death_age} years\n";
        $summary .= "- Retirement age: {$configuration->pension_wish_age}\n";
        $summary .= "- Risk tolerance: {$configuration->risk_tolerance}\n\n";

        $summary .= "💰 **Financial Overview:**\n";
        $summary .= '- Total assets: '.number_format($totalAssets, 0, ',', ' ')." NOK\n";
        $summary .= '- Annual income: '.number_format($totalIncome, 0, ',', ' ')." NOK\n";
        $summary .= "- Number of assets: {$assetCount}\n\n";

        $summary .= 'What would you like to do next?';

        return [
            'message' => $summary,
        ];
    }

    protected function getCompleteFinancialData(int $configurationId, User $user): ?string
    {
        $configuration = AssetConfiguration::with([
            'assets.years',
            'assets.assetType',
        ])
            ->where('id', $configurationId)
            ->where('user_id', $user->id)
            ->where('team_id', $user->current_team_id)
            ->first();

        if (! $configuration) {
            return null;
        }

        // Build the complete financial data in the same format as import/export
        $financialData = [
            'asset_owner' => [
                'name' => $configuration->name,
                'birth_year' => $configuration->birth_year,
                'death_age' => $configuration->death_age,
                'pension_wish_age' => $configuration->pension_wish_age,
                'risk_tolerance' => $configuration->risk_tolerance,
            ],
            'assets' => [],
        ];

        foreach ($configuration->assets as $asset) {
            $assetData = [
                'asset_name' => $asset->name ?? $asset->assetType->name ?? 'Unknown Asset',
                'asset_type' => $asset->assetType->type ?? 'unknown',
                'asset_category' => $asset->assetType->asset_category ?? 'other',
                'tax_type' => $asset->assetType->tax_type ?? 'private',
                'years' => [],
            ];

            foreach ($asset->years->sortBy('year') as $assetYear) {
                $assetData['years'][] = [
                    'year' => $assetYear->year,
                    'market_value' => $assetYear->asset_market_amount ?? 0,
                    'income' => $assetYear->income_amount ?? 0,
                    'expense' => $assetYear->expence_amount ?? 0,
                    'change_rate' => $assetYear->asset_changerate,
                ];
            }

            $financialData['assets'][] = $assetData;
        }

        return json_encode($financialData, JSON_PRETTY_PRINT);
    }

    protected function getCompleteFinancialDataForDisplay(int $configurationId, User $user): array
    {
        $jsonData = $this->getCompleteFinancialData($configurationId, $user);

        if (! $jsonData) {
            return [
                'message' => '❌ **Configuration not found.**',
            ];
        }

        return [
            'message' => "📊 **Complete Financial Data Summary**\n\n".
                       'Your financial configuration has been loaded and is ready for analysis. '.
                       'This includes all your assets, their yearly values, income, expenses, and growth rates. '.
                       "\n\n**What would you like to know about your finances?**\n\n".
                       "- *'What is my current net worth?'*\n".
                       "- *'When can I retire?'*\n".
                       "- *'How should I optimize my investments?'*\n".
                       "- *'What are my biggest financial risks?'*",
        ];
    }

    protected function callAiService(string $message, string $contextData, User $user): string
    {
        try {
            if (! $this->apiKey) {
                return $this->getFallbackResponse($message);
            }

            $systemPrompt = $this->buildSystemPrompt($contextData, $user);

            $response = $this->makeAiApiCall($systemPrompt, $message);

            return $response ?: $this->getFallbackResponse($message);
        } catch (\Exception $e) {
            Log::error('AI Service call failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'message' => $message,
            ]);

            return $this->getFallbackResponse($message);
        }
    }

    protected function makeAiApiCall(string $systemPrompt, string $userMessage): ?string
    {
        $endpoint = $this->getApiEndpoint();
        $headers = $this->getApiHeaders();
        $payload = $this->buildApiPayload($systemPrompt, $userMessage);

        $timeout = config("ai.settings.{$this->aiModel}.timeout", 30);

        $response = Http::withHeaders($headers)
            ->timeout($timeout)
            ->post($endpoint, $payload);

        if ($response->successful()) {
            return $this->extractResponseContent($response->json());
        }

        Log::error('AI API call failed', [
            'status' => $response->status(),
            'response' => $response->body(),
        ]);

        return null;
    }

    protected function getApiEndpoint(): string
    {
        return match ($this->aiProvider) {
            'openai' => 'https://api.openai.com/v1/chat/completions',
            default => 'https://api.openai.com/v1/chat/completions',
        };
    }

    protected function getApiHeaders(): array
    {
        return match ($this->aiProvider) {
            'openai' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ],
            default => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ],
        };
    }

    protected function buildApiPayload(string $systemPrompt, string $userMessage): array
    {
        $settings = config("ai.settings.{$this->aiModel}", [
            'max_tokens' => 1000,
            'temperature' => 0.7,
        ]);

        $payload = [
            'model' => $this->aiModel,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $userMessage,
                ],
            ],
            'max_tokens' => $settings['max_tokens'],
            'temperature' => $settings['temperature'],
        ];

        // Special handling for o1 models which don't support system messages
        if (str_starts_with($this->aiModel, 'o1-')) {
            $payload['messages'] = [
                [
                    'role' => 'user',
                    'content' => $systemPrompt."\n\nUser Question: ".$userMessage,
                ],
            ];
            // o1 models don't support temperature parameter
            unset($payload['temperature']);
        }

        return $payload;
    }

    protected function extractResponseContent(array $responseData): ?string
    {
        return match ($this->aiProvider) {
            'openai' => $responseData['choices'][0]['message']['content'] ?? null,
            default => $responseData['choices'][0]['message']['content'] ?? null,
        };
    }

    protected function buildSystemPrompt(string $contextData, User $user): string
    {
        return 'You are an expert financial advisor AI assistant for the Wealth Prognosis application. '.
               "You have access to the user's complete financial configuration data in JSON format. ".
               "Use this data to provide accurate, personalized financial advice and analysis.\n\n".
               "USER'S FINANCIAL DATA:\n{$contextData}\n\n".
               "INSTRUCTIONS:\n".
               "- Analyze the provided financial data thoroughly\n".
               "- Provide specific, actionable advice based on the actual numbers\n".
               "- Reference specific assets, values, and projections from the data\n".
               "- Be conversational but professional\n".
               "- Use Norwegian currency formatting (NOK) when discussing amounts\n".
               "- Focus on practical financial planning and wealth building strategies\n".
               "- If asked about assets, list them specifically with their current values\n".
               "- Calculate net worth, income, expenses based on the actual data provided\n\n".
               'Respond in a helpful, knowledgeable manner as a personal financial advisor would.';
    }

    protected function getFallbackResponse(string $message): string
    {
        return "I apologize, but I'm currently unable to access the AI service to provide a detailed analysis of your financial situation. ".
               "However, I can see that you asked: \"{$message}\"\n\n".
               'Please try again in a moment, or contact support if the issue persists. '.
               "In the meantime, you can:\n".
               "• View your asset configurations directly in the application\n".
               "• Check your dashboard for financial summaries\n".
               '• Use the simulation features for planning scenarios';
    }

    protected function logAiInteraction(string $type, array $data): void
    {
        $logData = [
            'type' => $type,
            'timestamp' => now()->toISOString(),
            'ai_provider' => $this->aiProvider,
            'ai_model' => $this->aiModel,
            ...$data,
        ];

        // Log to dedicated AI interaction log file
        Log::channel('single')->info("AI_INTERACTION_{$type}", $logData);

        // Also log to a dedicated AI log file if configured
        if (config('logging.channels.ai')) {
            Log::channel('ai')->info("AI_INTERACTION_{$type}", $logData);
        }
    }
}

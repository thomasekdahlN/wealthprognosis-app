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
    /** @var array<string, mixed> */
    protected array $conversationState = [];

    /** @var array<string, mixed> */
    protected array $pendingConfiguration = [];

    protected string $aiProvider;

    protected string $aiModel;

    protected ?string $apiKey;

    public function __construct()
    {
        $this->aiProvider = config('ai.provider', 'openai');
        $this->aiModel = config('ai.model', 'gpt-4');
        $this->apiKey = config('ai.api_key', '');
    }

    /**
     * @param  array<int, array<string, mixed>>  $conversation
     * @return array<string, mixed>
     */
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
                $aiResponse = $this->callAiService($originalMessage, $contextData, $user, $conversation);

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
            'update_mortgage' => $this->handleUpdateMortgage($message, $intent, $user, $currentConfigurationId, $statusCallback),
            'update_asset_value' => $this->handleUpdateAssetValue($message, $intent, $user, $currentConfigurationId, $statusCallback),
            'add_asset' => $this->handleAddAsset($message, $intent, $user, $currentConfigurationId),
            'add_income' => $this->handleAddIncome($message, $intent, $user, $currentConfigurationId),
            'add_life_event' => $this->handleAddLifeEvent($message, $intent, $user, $currentConfigurationId),
            'view_data' => $this->handleViewData($message, $intent, $user, $currentConfigurationId),
            'create_simulation' => $this->handleCreateSimulation($message, $intent, $user, $currentConfigurationId),
            'general_help' => $this->handleGeneralHelp($message, $intent),
            default => $this->handleUnknown($message, $currentConfigurationId, $user),
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $conversation
     * @return array<string, mixed>
     */
    protected function analyzeIntent(string $message, array $conversation): array
    {
        // Check for configuration switching keywords - Enhanced Norwegian support
        if (preg_match('/(?:switch|change|select|use|bytt|endre|velg|bruk).*(?:configuration|config|konfigurasjon).*(\d+)/i', $message)) {
            return ['type' => 'switch_configuration', 'confidence' => 0.9];
        }

        // Check for configuration creation keywords - Enhanced Norwegian support
        if (preg_match('/(?:create|new|start|setup|opprett|ny|start|sett\s+opp).*(?:configuration|profile|plan|konfigurasjon|profil|finansiell)/i', $message)) {
            return ['type' => 'create_configuration', 'confidence' => 0.9];
        }

        // Check for asset creation with loan (higher priority than mortgage updates)
        // Patterns for "add/create asset with loan"
        if (preg_match('/(?:add|create|legg.*til|opprett).*(?:en|et|a)?.*(?:asset|house|car|boat|båt|cabin|hytte|hus|bil|tesla|bmw|mercedes|audi|volvo|toyota|honda|ford|volkswagen|porsche|ferrari|lamborghini|maserati|bentley|rolls.?royce|bitcoin|ethereum|princess).*(?:with|med).*(?:loan|mortgage|lån|boliglån)/i', $message) ||
            preg_match('/(?:add|create|legg.*til|opprett).*(?:en|et|a)?.*(?:asset|house|car|boat|båt|cabin|hytte|hus|bil|tesla|bmw|mercedes|audi|volvo|toyota|honda|ford|volkswagen|porsche|ferrari|lamborghini|maserati|bentley|rolls.?royce|bitcoin|ethereum|princess).*(?:worth|value|verdi|verdt).*(?:with|med).*(?:loan|mortgage|lån|boliglån)/i', $message) ||
            preg_match('/(?:legg.*til|add|opprett).*(?:en|et|a).*(?:house|car|boat|båt|cabin|hytte|hus|bil|tesla|bmw|mercedes|audi|volvo|toyota|honda|ford|volkswagen|porsche|ferrari|lamborghini|maserati|bentley|rolls.?royce|bitcoin|ethereum|princess).*(?:til|to).*(?:en|a).*(?:verdi|value).*(?:med|with).*(?:et|a).*(?:lån|loan|boliglån)/i', $message)) {
            return ['type' => 'add_asset', 'confidence' => 0.95];
        }

        // Check for mortgage updates on existing assets (lower priority than asset creation)
        // Patterns for both English and Norwegian
        if (preg_match('/(?:set|update|change|add|sett|oppdater|endre|legg.*til).*(?:mortgage|loan|lån|boliglån).*(?:on|på|to|til).*(?:my|min|mitt)/i', $message) ||
            preg_match('/(?:sett|set).*(?:lån|lånet|mortgage|loan).*(?:på|av|of).*(?:my|min|mitt)/i', $message) ||
            preg_match('/(?:mortgage|loan|lån|boliglån).*(?:interest|rente).*(?:on|på).*(?:my|min|mitt)/i', $message) ||
            preg_match('/(?:update|oppdater).*(?:my|min|mitt).*(?:house|hus|cabin|hytte|car|bil).*(?:mortgage|loan|lån|boliglån)/i', $message) ||
            preg_match('/(?:add|legg.*til).*(?:mortgage|loan|lån|boliglån).*(?:to|til).*(?:my|min|mitt)/i', $message) ||
            preg_match('/(?:oppdater|update).*(?:boliglån|boliglånet|mortgage|loan|lån|lånet).*(?:mitt|my)/i', $message)) {
            return ['type' => 'update_mortgage', 'confidence' => 0.95];
        }

        // Check for asset value updates (higher priority than adding new assets, but exclude mortgage-related)
        // Simplified patterns for both English and Norwegian
        if ((preg_match('/(?:set|update|change|sett|oppdater|endre).*(?:value|worth|price|verdi|pris|verdien).*(?:to|til).*\d+/i', $message) ||
            preg_match('/(?:sett|set).*(?:verdien|value).*(?:på|av|of).*(?:til|to).*\d+/i', $message) ||
            preg_match('/(?:update|oppdater).*(?:my|min|mine|mitt).*(?:to|til).*\d+/i', $message)) &&
            ! preg_match('/(?:mortgage|loan|lån|boliglån)/i', $message)) {
            return ['type' => 'update_asset_value', 'confidence' => 0.95];
        }

        // Check for asset-related keywords first (higher priority than general questions)
        // Enhanced Norwegian support with more patterns
        if (preg_match('/(?:add|create|have|own|legg.*til|har|eier|jeg\s+har|jeg\s+eier).*(?:asset|house|car|boat|båt|cabin|hytte|fund|crypto|property|investment|eiendom|tesla|bmw|mercedes|audi|volvo|toyota|honda|ford|volkswagen|porsche|ferrari|lamborghini|maserati|bentley|rolls.?royce|bitcoin|ethereum|princess)/i', $message)) {
            return ['type' => 'add_asset', 'confidence' => 0.9];
        }

        // Check for explicit asset addition with value patterns - Enhanced Norwegian support with "verdt"
        if (preg_match('/(?:house|car|boat|båt|cabin|hytte|fund|crypto|property|investment|eiendom|tesla|bmw|mercedes|audi|volvo|toyota|honda|ford|volkswagen|porsche|ferrari|lamborghini|maserati|bentley|rolls.?royce|bitcoin|ethereum|princess).*(?:worth|value|cost|price|verdi|verdt|koster|pris).*\d+/i', $message)) {
            return ['type' => 'add_asset', 'confidence' => 0.95];
        }

        // Additional pattern for Norwegian "har/eier + asset + verdt + amount"
        if (preg_match('/(?:har|eier|jeg\s+har|jeg\s+eier).*(?:et|en|ett).*(?:house|car|boat|båt|cabin|hytte|fund|crypto|property|investment|eiendom|hus|bil|tesla|bmw|mercedes|audi|volvo|toyota|honda|ford|volkswagen|porsche|ferrari|lamborghini|maserati|bentley|rolls.?royce|bitcoin|ethereum|princess).*(?:verdt|worth).*\d+/i', $message)) {
            return ['type' => 'add_asset', 'confidence' => 0.95];
        }

        // Check for income-related keywords - Enhanced Norwegian support
        if (preg_match('/(?:income|salary|wage|pension|benefit|barnetrygd|earn|inntekt|lønn|pensjon|trygd|tjener)/i', $message)) {
            return ['type' => 'add_income', 'confidence' => 0.8];
        }

        // Check for life events - Enhanced Norwegian support
        if (preg_match('/(?:retirement|retire|kids|children|inheritance|move|house.*change|plan.*for|pensjon|pensjonering|barn|arv|flytte|planlegge)/i', $message)) {
            return ['type' => 'add_life_event', 'confidence' => 0.7];
        }

        // High-priority: explicit requests for complete financial data for analysis should show static summary view
        // Catch phrases like 'complete financial data', 'full financial data', or 'financial data for analysis'
        if (preg_match('/(?:complete|full)\s+financial\s+data/i', $message) || preg_match('/financial\s+data.*(?:analysis|analyze)/i', $message)) {
            return ['type' => 'view_data', 'confidence' => 0.95];
        }

        // Check for general questions about finances FIRST (needs AI context) - Enhanced Norwegian support
        // These should use AI for intelligent analysis, not just return static summaries
        if (preg_match('/(?:how.*much|what.*value|when.*retire|can.*afford|should.*invest|recommend|advice|analyze|analysis|compare|best|worst|risk|return|yield|profit|loss|tax|expense|income|cash.*flow|net.*worth|fire|financial.*independence|hvor.*mye|hva.*verdi|når.*pensjon|kan.*råd|bør.*investere|anbefal|råd|analyser|sammenlign|best|verst|risiko|avkastning|profitt|tap|skatt|utgift|inntekt|kontantstrøm|nettoformue)/i', $message)) {
            return ['type' => 'general_question', 'confidence' => 0.9];
        }

        // Check for questions about quantity/count of assets (needs AI analysis)
        if (preg_match('/(?:hvor.*mange|how.*many|count|antall).*(?:asset|eiendel|eiendom)/i', $message)) {
            return ['type' => 'general_question', 'confidence' => 0.9];
        }

        // Check for viewing ASSETS data explicitly (English and Norwegian)
        if (preg_match('/(?:show|view|display|vis|se).*?(?:my|min|mine).*?(?:assets?|eiendeler)/i', $message)) {
            return ['type' => 'view_data', 'confidence' => 0.8];
        }

        // Check for viewing CONFIGURATION data (static summary only) - Enhanced Norwegian support
        // Only match explicit requests for configuration summary, not general asset questions
        if (preg_match('/(?:show|view|display|vis|se).*(?:my.*configuration|current.*configuration|configuration.*summary|min.*konfigurasjon|nåværende.*konfigurasjon|konfigurasjon.*sammendrag)/i', $message)) {
            return ['type' => 'view_data', 'confidence' => 0.9];
        }

        // Check for simulation - Enhanced Norwegian support
        if (preg_match('/(?:simulation|simulate|forecast|predict|future|scenario|simulering|simulere|prognose|fremtid|scenario)/i', $message)) {
            return ['type' => 'create_simulation', 'confidence' => 0.8];
        }

        // Check for help - Enhanced Norwegian support
        if (preg_match('/(?:help|what.*can.*do|how.*work|guide|hjelp|hva.*kan.*gjøre|hvordan.*fungerer|veiledning|jeg\s+trenger\s+hjelp)/i', $message)) {
            return ['type' => 'general_help', 'confidence' => 0.9];
        }

        // Default to general_question for anything else that might need AI analysis
        // This ensures questions get intelligent responses instead of "unknown" fallback
        return ['type' => 'general_question', 'confidence' => 0.5];
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

    /**
     * @param  array<string, mixed>  $intent
     */
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

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
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
                'message' => "✅ **Configuration '{$configuration->name}' created**",
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

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
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
                    'message' => "✅ **Switched to configuration '{$configuration->name}'**",
                    'configuration_id' => $configuration->id,
                ];
            }
        }

        return [
            'message' => "❌ **Configuration not found or invalid ID.**\n\n".
                       'Please provide a valid configuration ID. You can only switch to configurations that belong to you.',
        ];
    }

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
    protected function handleUpdateAssetValue(string $message, array $intent, User $user, ?int $configurationId, ?callable $statusCallback = null): array
    {
        if (! $configurationId) {
            return [
                'message' => '⚠️ **Please create or select a configuration first before updating asset values.**\n\n'.
                           "You can create a new configuration by saying: *'Create a new financial configuration'*",
            ];
        }

        if ($statusCallback) {
            $statusCallback('🔍 Extracting asset information...');
        }

        // Extract asset identifier and new value from message
        $updateData = $this->extractAssetUpdateData($message);

        if (empty($updateData['asset_identifier']) || empty($updateData['value'])) {
            return [
                'message' => '📝 **I need more information to update the asset value.**\n\n'.
                           'Please specify:\n'.
                           '• **Which asset** to update (e.g., "my Toyota", "my house")\n'.
                           '• **New value** in NOK\n\n'.
                           "For example: *'Set the value of my Toyota to 400,000 NOK'*",
            ];
        }

        try {
            if ($statusCallback) {
                $statusCallback('🔎 Finding your asset...');
            }

            // Find the asset to update
            $asset = $this->findAssetByIdentifier($updateData['asset_identifier'], $configurationId, $user);

            if (! $asset) {
                return [
                    'message' => "❌ **Asset not found.**\n\n".
                               "I couldn't find an asset matching '{$updateData['asset_identifier']}' in your configuration.\n\n".
                               'Please check the asset name or add it first if it doesn\'t exist.',
                ];
            }

            if ($statusCallback) {
                $statusCallback('💾 Updating asset value...');
            }

            // Update or create asset year record
            $this->updateAssetYearValue($asset, $updateData['value'], $user);

            if ($statusCallback) {
                $statusCallback('✨ Formatting response...');
            }

            // Generate brief confirmation response
            $formattedValue = number_format($updateData['value'], 0, ',', ' ');
            $response = "✅ **{$asset->name}** value updated to **{$formattedValue} NOK**";

            return ['message' => $response];
        } catch (\Exception $e) {
            Log::error('Error updating asset value', [
                'message' => $message,
                'user_id' => $user->id,
                'configuration_id' => $configurationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'message' => '❌ **Error updating asset value.**\n\n'.
                           'There was an error updating the asset value. Please try again or contact support if the problem persists.',
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
    protected function handleUpdateMortgage(string $message, array $intent, User $user, ?int $configurationId, ?callable $statusCallback = null): array
    {
        if (! $configurationId) {
            return [
                'message' => '⚠️ **Please create or select a configuration first before updating mortgage information.**\n\n'.
                           "You can create a new configuration by saying: *'Create a new financial configuration'*",
            ];
        }

        if ($statusCallback) {
            $statusCallback('🔍 Extracting mortgage information...');
        }

        // Extract asset identifier and mortgage data from message
        $mortgageData = $this->extractMortgageUpdateData($message);

        if (empty($mortgageData['asset_identifier'])) {
            return [
                'message' => '📝 **I need more information to update the mortgage.**\n\n'.
                           'Please specify:\n'.
                           '• **Which asset** has the mortgage (e.g., "my house", "my cabin")\n'.
                           '• **Mortgage amount** in NOK\n'.
                           '• **Optional**: Interest rate, years, etc.\n\n'.
                           "For example: *'Set mortgage on my house to 2,500,000 NOK'*",
            ];
        }

        if (empty($mortgageData['amount']) && empty($mortgageData['interest_rate']) && empty($mortgageData['years'])) {
            return [
                'message' => '📝 **I need mortgage details to update.**\n\n'.
                           'Please specify at least one of:\n'.
                           '• **Mortgage amount** in NOK\n'.
                           '• **Interest rate** (e.g., 4.5%)\n'.
                           '• **Loan term** in years\n\n'.
                           "For example: *'Set mortgage on my house to 2,500,000 NOK with 4.5% interest for 25 years'*",
            ];
        }

        try {
            if ($statusCallback) {
                $statusCallback('🔎 Finding your asset...');
            }

            // Find the asset to update
            $asset = $this->findAssetByIdentifier($mortgageData['asset_identifier'], $configurationId, $user);

            if (! $asset) {
                return [
                    'message' => "❌ **Asset not found.**\n\n".
                               "I couldn't find an asset matching '{$mortgageData['asset_identifier']}' in your configuration.\n\n".
                               'Please check the asset name or add it first if it doesn\'t exist.',
                ];
            }

            if ($statusCallback) {
                $statusCallback('💾 Updating mortgage information...');
            }

            // Update or create asset year record with mortgage data
            $this->updateAssetYearMortgage($asset, $mortgageData, $user);

            if ($statusCallback) {
                $statusCallback('✨ Formatting response...');
            }

            // Generate confirmation response
            $response = $this->generateMortgageUpdateResponse($asset, $mortgageData);

            return ['message' => $response];
        } catch (\Exception $e) {
            Log::error('Error updating mortgage', [
                'message' => $message,
                'user_id' => $user->id,
                'configuration_id' => $configurationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'message' => '❌ **Error updating mortgage information.**\n\n'.
                           'There was an error updating the mortgage. Please try again or contact support if the problem persists.',
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
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
            $response = "✅ Asset Successfully Added: **{$assetName}** added with value **{$formattedValue} NOK**";

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

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
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

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
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

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
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

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
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

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
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

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @return array<string, mixed>
     */
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

        // Extract expected death age
        if (preg_match('/live.*?(\d{2,3})|death.*?age.*?(\d{2,3})|die.*?(\d{2,3})/i', $message, $matches)) {
            $data['expected_death_age'] = (int) ($matches[1] ?: $matches[2] ?: $matches[3]);
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

    /**
     * @param  array<int, string>  $missingFields
     * @param  array<string, mixed>  $extractedData
     */
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

    /**
     * @param  array<string, mixed>  $data
     */
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

    /**
     * @return array<string, mixed>
     */
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
                    '/\b(?:princess|sunseeker|azimut|ferretti|pershing|riva|chris.?craft|sea.?ray|boston.?whaler|jeanneau|beneteau)\b/i',
                ],
                'brands' => ['princess', 'sunseeker', 'azimut', 'ferretti', 'pershing', 'riva', 'chris craft', 'sea ray', 'boston whaler', 'jeanneau', 'beneteau'],
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
        if (isset($data['type'])) {
            $assetType = $data['type'];

            // Extract brand for cars
            if ($assetType === 'car') {
                $brands = $assetTypePatterns['car']['brands'];
                foreach ($brands as $brand) {
                    if (preg_match("/\b{$brand}\b/i", $message, $matches)) {
                        $data['brand'] = ucfirst(strtolower($matches[0]));

                        // Try to extract model with better patterns (avoiding Norwegian prepositions)
                        $modelPatterns = [
                            "/\b{$brand}\s+(model\s+[a-z0-9]+|[a-z0-9\-]+(?:\s+[a-z0-9\-]+)?)(?:\s+(?:worth|value|cost|price|to|as|til|verdi|med|\d+)|$)/i",
                            "/\b{$brand}\s+([a-z0-9\s\-]+?)(?:\s+(?:car|vehicle|bil|worth|value|cost|price|til|verdi|med|\d+)|$)/i",
                        ];

                        foreach ($modelPatterns as $pattern) {
                            if (preg_match($pattern, $message, $modelMatches)) {
                                $model = trim($modelMatches[1]);
                                // Clean up common extraction issues (including Norwegian words)
                                $model = preg_replace('/\s+(car|vehicle|bil|to|as|til|worth|value|cost|price|verdi|med|av|på|en|et).*$/i', '', $model);
                                // Skip if model contains Norwegian prepositions or common words
                                if (preg_match('/\b(til|av|på|en|et|med|verdi|value|worth|cost|price)\b/i', $model)) {
                                    continue;
                                }
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

            // Extract brand for boats
            if ($assetType === 'boat') {
                $brands = $assetTypePatterns['boat']['brands'];
                foreach ($brands as $brand) {
                    if (preg_match("/\b{$brand}\b/i", $message, $matches)) {
                        $data['brand'] = ucfirst(strtolower($matches[0]));

                        // Try to extract model with better patterns for boats
                        $modelPatterns = [
                            "/\b{$brand}\s+([a-z0-9\-]+(?:\s+[a-z0-9\-]+)?)(?:\s+(?:boat|båt|yacht|worth|value|cost|price|verdi|\d+)|$)/i",
                            "/\b{$brand}\s+([a-z0-9\s\-]+?)(?:\s+(?:boat|båt|yacht|worth|value|cost|price|verdi|\d+)|$)/i",
                        ];

                        foreach ($modelPatterns as $pattern) {
                            if (preg_match($pattern, $message, $modelMatches)) {
                                $model = trim($modelMatches[1]);
                                // Clean up common extraction issues
                                $model = preg_replace('/\s+(boat|båt|yacht|worth|value|cost|price|verdi).*$/i', '', $model);
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
        }

        // Enhanced value extraction with better number parsing
        // Only extract values that have clear monetary indicators
        $valuePatterns = [
            // Handle various number formats with explicit monetary indicators
            '/(?:worth|value|cost|price|verdi).*?(\d+(?:[,.\s]\d+)*)\s*k(?:r|roner)?\b/i' => 1000, // worth 500K, verdi 500kr
            '/(?:worth|value|cost|price|verdi).*?(\d+(?:[,.\s]\d+)*)\s*(?:thousand|tusen)\b/i' => 1000,
            '/(?:worth|value|cost|price|verdi).*?(\d+(?:[,.\s]\d+)*)\s*m(?:illion)?\b/i' => 1000000, // worth 2M
            '/(?:worth|value|cost|price|verdi).*?(\d+(?:[,.\s]\d+)*)\s*(?:million|millioner)\b/i' => 1000000,
            '/(?:worth|value|cost|price|verdi).*?(\d+(?:[,.\s]\d+)*)\s*(?:nok|kr|kroner)?\b/i' => 1,
            '/(\d+(?:[,.\s]\d+)*)\s*(?:nok|kr|kroner)\b/i' => 1, // Direct currency indicators
            '/(\d+(?:[,.\s]\d+)*)\s*k(?:r|roner)?\s*(?:worth|value|verdi)\b/i' => 1000, // 500K worth
            '/(\d+(?:[,.\s]\d+)*)\s*m(?:illion)?\s*(?:worth|value|verdi)\b/i' => 1000000, // 2M worth
            // Norwegian specific patterns that don't conflict with "legg til"
            '/(?<!legg\s)til\s+(\d+(?:[,.\s]\d+)*)\s*(?:nok|kr|kroner)\b/i' => 1, // "til 500000 kr" but not "legg til"
        ];

        foreach ($valuePatterns as $pattern => $multiplier) {
            if (preg_match($pattern, $message, $matches)) {
                $value = preg_replace('/[,\s]/', '', $matches[1]);
                $data['value'] = (int) ($value * $multiplier);
                break;
            }
        }

        // Extract mortgage information (enhanced for Norwegian and various patterns)
        // Pattern 1: "med et lån på X" or "with a loan of X" (with K/M multipliers)
        if (preg_match('/(?:med|with).*(?:et|a).*(?:lån|loan).*(?:på|of).*?(\d+(?:[,.\s]\d+)*)\s*([KkMm])?/i', $message, $matches)) {
            $amount = (int) preg_replace('/[,\s]/', '', $matches[1]);
            $multiplier = isset($matches[2]) ? (strtolower($matches[2]) === 'k' ? 1000 : (strtolower($matches[2]) === 'm' ? 1000000 : 1)) : 1;
            $data['mortgage'] = $amount * $multiplier;
        }
        // Pattern 2: "lån på X" or "loan of X" (with K/M multipliers)
        elseif (preg_match('/(?:lån|loan).*(?:på|of).*?(\d+(?:[,.\s]\d+)*)\s*([KkMm])?/i', $message, $matches)) {
            $amount = (int) preg_replace('/[,\s]/', '', $matches[1]);
            $multiplier = isset($matches[2]) ? (strtolower($matches[2]) === 'k' ? 1000 : (strtolower($matches[2]) === 'm' ? 1000000 : 1)) : 1;
            $data['mortgage'] = $amount * $multiplier;
        }
        // Pattern 3: Original mortgage pattern (with K/M multipliers)
        elseif (preg_match('/mortgage.*?(\d+(?:[,.\s]\d+)*)\s*([KkMm])?/i', $message, $matches)) {
            $amount = (int) preg_replace('/[,\s]/', '', $matches[1]);
            $multiplier = isset($matches[2]) ? (strtolower($matches[2]) === 'k' ? 1000 : (strtolower($matches[2]) === 'm' ? 1000000 : 1)) : 1;
            $data['mortgage'] = $amount * $multiplier;
        }

        // Extract mortgage years (enhanced for Norwegian)
        if (preg_match('/(?:over|for).*?(\d+)\s*(?:years?|år)/i', $message, $matches)) {
            $data['mortgage_years'] = (int) $matches[1];
        } elseif (preg_match('/(\d+)\s*(?:years?|år).*?(?:mortgage|loan|lån)/i', $message, $matches)) {
            $data['mortgage_years'] = (int) $matches[1];
        } elseif (preg_match('/(?:mortgage|loan|lån).*?(\d+)\s*(?:years?|år)/i', $message, $matches)) {
            $data['mortgage_years'] = (int) $matches[1];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
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

            // Create asset year with current value and mortgage data
            $assetYearData = [
                'asset_id' => $asset->id,
                'asset_configuration_id' => $configurationId,
                'year' => now()->year,
                'asset_market_amount' => $data['value'],
                'user_id' => $user->id,
                'team_id' => $user->current_team_id,
                // Required fields with defaults
                'income_amount' => 0,
                'income_factor' => 'yearly',
                'income_repeat' => false,
                'expence_amount' => 0,
                'expence_factor' => 'yearly',
                'expence_repeat' => false,
                'asset_acquisition_amount' => 0,
                'asset_equity_amount' => 0,
                'asset_taxable_initial_amount' => 0,
                'asset_paid_amount' => 0,
                'asset_repeat' => true,
                'mortgage_amount' => $data['mortgage'] ?? 0,
                'mortgage_years' => $data['mortgage_years'] ?? 0,
                'mortgage_gebyr' => 0,
                'mortgage_tax' => 0,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ];

            $assetYearData['created_checksum'] = md5(json_encode($assetYearData));
            $assetYearData['updated_checksum'] = md5(json_encode($assetYearData));

            AssetYear::create($assetYearData);

            return $asset;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
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

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @param  array<string, mixed>  $data
     */
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

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
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

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function createChildrenEvents(array $data, int $configurationId, User $user): array
    {
        $planningService = app(FinancialPlanningService::class);

        return $planningService->createChildrenEvents($data, $configurationId, $user);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function createInheritanceEvent(array $data, int $configurationId, User $user): array
    {
        $planningService = app(FinancialPlanningService::class);

        return $planningService->createInheritanceEvent($data, $configurationId, $user);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    protected function createPropertyChangeEvent(array $data, int $configurationId, User $user): array
    {
        $planningService = app(FinancialPlanningService::class);

        return $planningService->createPropertyChangeEvent($data, $configurationId, $user);
    }

    /**
     * @return array<string, mixed>
     */
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
            /** @var \App\Models\Asset $asset */
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
            /** @var \App\Models\Asset $asset */
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
        $summary .= "- Expected lifespan: {$configuration->expected_death_age} years\n";
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
                'death_age' => $configuration->expected_death_age,
                'pension_wish_age' => $configuration->pension_wish_age,
                'risk_tolerance' => $configuration->risk_tolerance,
            ],
            'assets' => [],
        ];

        /** @var \App\Models\Asset $asset */
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

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @param  array<int, array<string, mixed>>  $conversation
     */
    protected function callAiService(string $message, string $contextData, User $user, array $conversation = []): string
    {
        try {
            if (! $this->apiKey) {
                return $this->getFallbackResponse($message);
            }

            $systemPrompt = $this->buildSystemPrompt($contextData, $user);

            $response = $this->makeAiApiCall($systemPrompt, $message, $conversation);

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

    /**
     * @param  array<int, array<string, mixed>>  $conversation
     */
    protected function makeAiApiCall(string $systemPrompt, string $userMessage, array $conversation = []): ?string
    {
        $endpoint = $this->getApiEndpoint();
        $headers = $this->getApiHeaders();
        $payload = $this->buildApiPayload($systemPrompt, $userMessage, $conversation);

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

    /**
     * @return array<string, string>
     */
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

    /**
     * @param  array<int, array<string, mixed>>  $conversation
     * @return array<string, mixed>
     */
    protected function buildApiPayload(string $systemPrompt, string $userMessage, array $conversation = []): array
    {
        $settings = config("ai.settings.{$this->aiModel}", [
            'max_tokens' => 1000,
            'temperature' => 0.7,
        ]);

        // Build messages array starting with system prompt
        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
        ];

        // Add conversation history (excluding the current message)
        foreach ($conversation as $msg) {
            if (isset($msg['type']) && isset($msg['message'])) {
                $role = $msg['type'] === 'user' ? 'user' : 'assistant';
                $messages[] = [
                    'role' => $role,
                    'content' => $msg['message'],
                ];
            }
        }

        // Add the current user message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        $payload = [
            'model' => $this->aiModel,
            'messages' => $messages,
            'max_tokens' => $settings['max_tokens'],
            'temperature' => $settings['temperature'],
        ];

        // Special handling for o1 models which don't support system messages
        if (str_starts_with($this->aiModel, 'o1-')) {
            // For o1 models, combine system prompt with conversation history
            $conversationText = '';
            foreach ($conversation as $msg) {
                if (isset($msg['type']) && isset($msg['message'])) {
                    $role = $msg['type'] === 'user' ? 'User' : 'Assistant';
                    $conversationText .= "\n{$role}: {$msg['message']}";
                }
            }

            $payload['messages'] = [
                [
                    'role' => 'user',
                    'content' => $systemPrompt.$conversationText."\n\nUser Question: ".$userMessage,
                ],
            ];
            // o1 models don't support temperature parameter
            unset($payload['temperature']);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $responseData
     */
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
        // Provide a more helpful, context-aware fallback response
        $lowerMessage = strtolower($message);

        // Check for specific topics and provide relevant guidance
        if (str_contains($lowerMessage, 'asset') || str_contains($lowerMessage, 'eiendel')) {
            return "**About Your Assets**\n\n".
                   "I can see you're asking about assets. While I don't have AI analysis available right now, here's what you can do:\n\n".
                   "• **View Assets**: Navigate to the Assets page to see all your assets\n".
                   "• **Add Assets**: Use the 'Create Asset' button to add new assets\n".
                   "• **Edit Values**: Click on any asset to update its current market value\n".
                   "• **Asset Years**: Track how your assets change over time\n\n".
                   '💡 **Tip**: The dashboard shows your total asset value and allocation by type.';
        }

        if (str_contains($lowerMessage, 'simulation') || str_contains($lowerMessage, 'simulering')) {
            return "**About Simulations**\n\n".
                   "Simulations help you plan different financial scenarios. Here's how to use them:\n\n".
                   "• **Create Simulation**: Go to Simulations and click 'Create'\n".
                   "• **Choose Prognosis**: Select realistic, positive, negative, or custom scenarios\n".
                   "• **Run Analysis**: See how your wealth grows over time\n".
                   "• **Compare Scenarios**: Run multiple simulations to compare outcomes\n\n".
                   '💡 **Tip**: Simulations use your current assets and project them into the future.';
        }

        if (str_contains($lowerMessage, 'retire') || str_contains($lowerMessage, 'pensjon') || str_contains($lowerMessage, 'fire')) {
            return "**About Retirement Planning**\n\n".
                   "Planning for retirement is crucial. Here's what the system can help you with:\n\n".
                   "• **FIRE Metrics**: Check your dashboard for Financial Independence progress\n".
                   "• **Passive Income**: See how much income your assets can generate\n".
                   "• **Crossover Point**: When your passive income exceeds expenses\n".
                   "• **Simulations**: Model different retirement scenarios\n\n".
                   '💡 **Tip**: The FIRE widgets show your progress toward financial independence.';
        }

        if (str_contains($lowerMessage, 'tax') || str_contains($lowerMessage, 'skatt')) {
            return "**About Tax Planning**\n\n".
                   "The system includes comprehensive tax calculations:\n\n".
                   "• **Tax Types**: Different tax rates for different asset types\n".
                   "• **Tax Configurations**: Country-specific tax rules (Norway, Sweden, Switzerland)\n".
                   "• **Tax Projections**: See estimated taxes in simulations\n".
                   "• **Optimization**: Structure assets to minimize tax burden\n\n".
                   '💡 **Tip**: Check the Tax Configuration page for detailed tax rules.';
        }

        if (str_contains($lowerMessage, 'help') || str_contains($lowerMessage, 'hjelp')) {
            return "**How I Can Help**\n\n".
                   "I'm your AI financial assistant! Here's what I can help you with:\n\n".
                   "**Asset Management**\n".
                   "• Add, update, and track your assets\n".
                   "• Monitor asset values over time\n".
                   "• Manage mortgages and loans\n\n".
                   "**Financial Planning**\n".
                   "• Create financial configurations\n".
                   "• Run simulations for different scenarios\n".
                   "• Track progress toward financial goals\n\n".
                   "**Analysis & Insights**\n".
                   "• View net worth and cash flow\n".
                   "• Monitor FIRE progress\n".
                   "• Analyze asset allocation\n\n".
                   '💡 **Note**: Full AI analysis requires an OpenAI API key to be configured.';
        }

        // Default fallback for general questions
        return "**I'm Here to Help!**\n\n".
               "You asked: *\"{$message}\"*\n\n".
               "I'm currently unable to access the AI service to generate a detailed answer. Please try again later, or configure an API key to enable AI-powered analysis.\n\n".
               "**Quick Actions**\n".
               "• 📊 **Dashboard**: View your financial overview\n".
               "• 💰 **Assets**: Manage your assets and their values\n".
               "• 🎯 **Simulations**: Run financial scenarios\n".
               "• 📈 **Reports**: Analyze your wealth over time\n\n".
               "**Try asking me about:**\n".
               "• 'How do I add an asset?'\n".
               "• 'Tell me about simulations'\n".
               "• 'How do I plan for retirement?'\n".
               "• 'What are the tax features?'\n\n".
               '💡 **Tip**: I can help you with specific features even without AI - just ask!';
    }

    /**
     * @param  array<string, mixed>  $data
     */
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

    /**
     * @return array<string, mixed>
     */
    protected function extractAssetUpdateData(string $message): array
    {
        $data = [];

        // Extract asset identifier (brand, type, or name)
        // Support patterns like "my toyota", "verdien på toyota", "the BMW", etc.

        // Pattern 1: Norwegian "verdien på [asset]" pattern
        if (preg_match('/(?:verdien|value)\s+(?:på|av|of)\s+(house|car|boat|båt|cabin|hytte|fund|crypto|property|investment|eiendom|tesla|bmw|mercedes|audi|volvo|toyota|honda|ford|volkswagen|porsche|ferrari|lamborghini|maserati|bentley|rolls.?royce|bitcoin|ethereum|princess)(?:\s+[a-zA-Z0-9\s\-]*)?/i', $message, $matches)) {
            $data['asset_identifier'] = trim($matches[1]);
        }
        // Pattern 2: Possessive patterns "my toyota", "min bil"
        elseif (preg_match('/(?:my|min|mine|the|den)\s+(house|car|boat|båt|cabin|hytte|fund|crypto|property|investment|eiendom|tesla|bmw|mercedes|audi|volvo|toyota|honda|ford|volkswagen|porsche|ferrari|lamborghini|maserati|bentley|rolls.?royce|bitcoin|ethereum|princess)(?:\s+[a-zA-Z0-9\s\-]*)?/i', $message, $matches)) {
            $data['asset_identifier'] = trim($matches[1]);
        }
        // Pattern 3: General fallback for Norwegian "verdien på [anything]"
        elseif (preg_match('/(?:verdien|value)\s+(?:på|av|of)\s+([a-zA-Z0-9\s\-]+?)(?:\s+(?:to|til))/i', $message, $matches)) {
            $identifier = trim($matches[1]);
            // Remove common words that shouldn't be part of the asset identifier
            $identifier = preg_replace('/\b(?:value|verdi|worth|price|pris)\b/i', '', $identifier);
            $identifier = trim($identifier);
            if (! empty($identifier)) {
                $data['asset_identifier'] = $identifier;
            }
        }
        // Pattern 4: Possessive fallback
        elseif (preg_match('/(?:my|min|mine|the|den)\s+([a-zA-Z0-9\s\-]+?)(?:\s+(?:to|til))/i', $message, $matches)) {
            $identifier = trim($matches[1]);
            // Remove common words that shouldn't be part of the asset identifier
            $identifier = preg_replace('/\b(?:value|verdi|worth|price|pris)\b/i', '', $identifier);
            $identifier = trim($identifier);
            if (! empty($identifier)) {
                $data['asset_identifier'] = $identifier;
            }
        }

        // Extract new value using the same patterns as asset creation
        $valuePatterns = [
            '/(?:to|til)\s+(\d+(?:[,.\s]\d+)*)\s*k(?:r|roner)?\b/i' => 1000, // to 40K
            '/(?:to|til)\s+(\d+(?:[,.\s]\d+)*)\s*(?:thousand|tusen)\b/i' => 1000,
            '/(?:to|til)\s+(\d+(?:[,.\s]\d+)*)\s*m(?:illion)?\b/i' => 1000000, // to 2M
            '/(?:to|til)\s+(\d+(?:[,.\s]\d+)*)\s*(?:million|millioner)\b/i' => 1000000,
            '/(?:to|til)\s+(\d+(?:[,.\s]\d+)*)\s*(?:nok|kr|kroner)\b/i' => 1, // to 400000 NOK
            '/(\d+(?:[,.\s]\d+)*)\s*(?:nok|kr|kroner)\b/i' => 1, // Direct currency indicators
        ];

        foreach ($valuePatterns as $pattern => $multiplier) {
            if (preg_match($pattern, $message, $matches)) {
                $value = preg_replace('/[,\s]/', '', $matches[1]);
                $data['value'] = (int) ($value * $multiplier);
                break;
            }
        }

        return $data;
    }

    protected function findAssetByIdentifier(string $identifier, int $configurationId, User $user): ?Asset
    {
        // Clean up the identifier
        $identifier = trim(strtolower($identifier));

        // Try to find asset by exact name match first
        $asset = Asset::where('asset_configuration_id', $configurationId)
            ->where('team_id', $user->current_team_id)
            ->whereRaw('LOWER(name) LIKE ?', ["%{$identifier}%"])
            ->first();

        if ($asset) {
            return $asset;
        }

        // Try to find by asset type
        $asset = Asset::where('asset_configuration_id', $configurationId)
            ->where('team_id', $user->current_team_id)
            ->whereRaw('LOWER(asset_type) = ?', [$identifier])
            ->first();

        if ($asset) {
            return $asset;
        }

        // Try to find by brand/model in name
        $assets = Asset::where('asset_configuration_id', $configurationId)
            ->where('team_id', $user->current_team_id)
            ->get();

        foreach ($assets as $asset) {
            $assetName = strtolower($asset->name);
            if (str_contains($assetName, $identifier)) {
                return $asset;
            }
        }

        return null;
    }

    protected function updateAssetYearValue(Asset $asset, int $newValue, User $user): void
    {
        $currentYear = now()->year;

        // Try to find existing asset year record for current year
        $assetYear = AssetYear::where('asset_id', $asset->id)
            ->where('year', $currentYear)
            ->first();

        if ($assetYear) {
            // Update existing record
            $assetYear->update([
                'asset_market_amount' => $newValue,
                'asset_configuration_id' => $asset->asset_configuration_id, // Ensure this is set
                'updated_by' => $user->id,
                'updated_checksum' => md5(json_encode(['asset_market_amount' => $newValue])),
            ]);
        } else {
            // Create new record for current year
            AssetYear::create([
                'asset_id' => $asset->id,
                'asset_configuration_id' => $asset->asset_configuration_id,
                'year' => $currentYear,
                'asset_market_amount' => $newValue,
                'user_id' => $user->id,
                'team_id' => $user->current_team_id,
                // Required fields with defaults
                'income_amount' => 0,
                'income_factor' => 'yearly',
                'income_repeat' => false,
                'expence_amount' => 0,
                'expence_factor' => 'yearly',
                'expence_repeat' => false,
                'asset_acquisition_amount' => 0,
                'asset_equity_amount' => 0,
                'asset_taxable_initial_amount' => 0,
                'asset_paid_amount' => 0,
                'asset_repeat' => true,
                'mortgage_amount' => 0,
                'mortgage_years' => 0,
                'mortgage_gebyr' => 0,
                'mortgage_tax' => 0,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => md5(json_encode(['asset_market_amount' => $newValue])),
                'updated_checksum' => md5(json_encode(['asset_market_amount' => $newValue])),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractMortgageUpdateData(string $message): array
    {
        $data = [];

        // Extract asset identifier (similar to asset value updates)
        // Pattern 1: Norwegian "lån på [asset]" pattern
        if (preg_match('/(?:lån|lånet|mortgage|loan)\s+(?:på|av|of|for)\s+(house|car|boat|båt|cabin|hytte|fund|crypto|property|investment|eiendom|hus|bil|tesla|bmw|mercedes|audi|volvo|toyota|honda|ford|volkswagen|porsche|ferrari|lamborghini|maserati|bentley|rolls.?royce|bitcoin|ethereum|princess|mitt?\s+\w+|my\s+\w+)(?:\s+[a-zA-Z0-9\s\-]*)?/i', $message, $matches)) {
            $data['asset_identifier'] = trim($matches[1]);
        }
        // Pattern 2: "set/update mortgage [interest] on [asset]" pattern
        elseif (preg_match('/(?:set|sett|update|oppdater).*(?:mortgage|loan|lån|boliglån)(?:\s+interest|\s+rente)?\s+(?:on|på|for|til)\s+(house|car|boat|båt|cabin|hytte|fund|crypto|property|investment|eiendom|hus|bil|tesla|bmw|mercedes|audi|volvo|toyota|honda|ford|volkswagen|porsche|ferrari|lamborghini|maserati|bentley|rolls.?royce|bitcoin|ethereum|princess|mitt?\s+\w+|my\s+\w+)(?:\s+[a-zA-Z0-9\s\-]*)?/i', $message, $matches)) {
            $data['asset_identifier'] = trim($matches[1]);
        }
        // Pattern 3: "[asset] mortgage" pattern
        elseif (preg_match('/(house|car|boat|båt|cabin|hytte|fund|crypto|property|investment|eiendom|hus|bil|tesla|bmw|mercedes|audi|volvo|toyota|honda|ford|volkswagen|porsche|ferrari|lamborghini|maserati|bentley|rolls.?royce|bitcoin|ethereum|princess|mitt?\s+\w+|my\s+\w+)(?:\s+[a-zA-Z0-9\s\-]*)?.*(?:mortgage|loan|lån|boliglån)/i', $message, $matches)) {
            $data['asset_identifier'] = trim($matches[1]);
        }

        // Extract mortgage amount (but not interest rates)
        if (preg_match('/(?:mortgage|loan|lån|boliglån).*?(?:amount|beløp|sum).*?(\d+(?:[,.\s]\d+)*)/i', $message, $matches)) {
            $data['amount'] = (float) preg_replace('/[,\s]/', '', $matches[1]);
        }
        // Pattern for "to X NOK" or "til X kroner" (but not percentages)
        elseif (preg_match('/(?:to|til)\s+(\d+(?:[,.\s]\d+)*)\s*(?:nok|kroner|kr)\b/i', $message, $matches)) {
            $data['amount'] = (float) preg_replace('/[,\s]/', '', $matches[1]);
        }
        // Pattern for large numbers with mortgage context (exclude percentages)
        elseif (preg_match('/(?:mortgage|loan|lån|boliglån).*?(\d{6,}(?:[,.\s]\d+)*)/i', $message, $matches)) {
            $data['amount'] = (float) preg_replace('/[,\s]/', '', $matches[1]);
        }

        // Extract interest rate
        if (preg_match('/(?:interest|rente).*?(\d+(?:[,.]\d+)?)\s*%/i', $message, $matches)) {
            $data['interest_rate'] = str_replace(',', '.', $matches[1]);
        } elseif (preg_match('/(\d+(?:[,.]\d+)?)\s*%.*(?:interest|rente)/i', $message, $matches)) {
            $data['interest_rate'] = str_replace(',', '.', $matches[1]);
        }

        // Extract loan term in years
        if (preg_match('/(\d+)\s*(?:years?|år).*?(?:mortgage|loan|lån)/i', $message, $matches)) {
            $data['years'] = (int) $matches[1];
        } elseif (preg_match('/(?:mortgage|loan|lån).*?(\d+)\s*(?:years?|år)/i', $message, $matches)) {
            $data['years'] = (int) $matches[1];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $mortgageData
     */
    protected function updateAssetYearMortgage(Asset $asset, array $mortgageData, User $user): void
    {
        $currentYear = (int) date('Y');

        // Find or create asset year record
        $assetYear = AssetYear::where('asset_id', $asset->id)
            ->where('year', $currentYear)
            ->first();

        $updateData = [];

        // Prepare update data
        if (! empty($mortgageData['amount'])) {
            $updateData['mortgage_amount'] = $mortgageData['amount'];
        }

        if (! empty($mortgageData['interest_rate'])) {
            $updateData['mortgage_interest'] = $mortgageData['interest_rate'];
        }

        if (! empty($mortgageData['years'])) {
            $updateData['mortgage_years'] = $mortgageData['years'];
        }

        // Always update audit fields
        $updateData['updated_by'] = $user->id;
        $updateData['updated_checksum'] = md5(json_encode($updateData));

        if ($assetYear) {
            // Update existing record
            $updateData['asset_configuration_id'] = $asset->asset_configuration_id; // Ensure this is set
            $assetYear->update($updateData);
        } else {
            // Create new record for current year with mortgage data
            AssetYear::create(array_merge([
                'asset_id' => $asset->id,
                'asset_configuration_id' => $asset->asset_configuration_id,
                'year' => $currentYear,
                'user_id' => $user->id,
                'team_id' => $user->current_team_id,
                // Required fields with defaults
                'income_amount' => 0,
                'income_factor' => 'yearly',
                'income_repeat' => false,
                'expence_amount' => 0,
                'expence_factor' => 'yearly',
                'expence_repeat' => false,
                'asset_market_amount' => 0,
                'asset_acquisition_amount' => 0,
                'asset_equity_amount' => 0,
                'asset_taxable_initial_amount' => 0,
                'asset_paid_amount' => 0,
                'asset_repeat' => true,
                'mortgage_amount' => 0,
                'mortgage_years' => 0,
                'mortgage_gebyr' => 0,
                'mortgage_tax' => 0,
                'created_by' => $user->id,
                'created_checksum' => md5(json_encode($updateData)),
            ], $updateData));
        }
    }

    /**
     * @param  array<string, mixed>  $mortgageData
     */
    protected function generateMortgageUpdateResponse(Asset $asset, array $mortgageData): string
    {
        $response = "✅ **{$asset->name}** mortgage updated:\n\n";

        if (! empty($mortgageData['amount'])) {
            $formattedAmount = number_format($mortgageData['amount'], 0, ',', ' ');
            $response .= "💰 **Amount**: {$formattedAmount} NOK\n";
        }

        if (! empty($mortgageData['interest_rate'])) {
            $response .= "📈 **Interest Rate**: {$mortgageData['interest_rate']}%\n";
        }

        if (! empty($mortgageData['years'])) {
            $response .= "📅 **Term**: {$mortgageData['years']} years\n";
        }

        return $response;
    }
}

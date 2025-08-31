<?php

namespace App\Services;

use App\Models\AssetType;
use App\Models\TaxType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AiConfigurationAnalysisService
{
    protected ?string $openaiApiKey;
    protected string $model = 'gpt-4';

    public function __construct()
    {
        $this->openaiApiKey = config('services.openai.api_key');

        if (!$this->openaiApiKey) {
            throw new \Exception('OpenAI API key not configured. Please set OPENAI_API_KEY in your environment.');
        }
    }

    /**
     * Analyze economic situation description and generate asset configuration
     */
    public function analyzeEconomicSituation(string $description): array
    {
        // Cache the analysis for 1 hour to avoid duplicate API calls for same input
        $cacheKey = 'ai_analysis_' . hash('sha256', $description);

        return Cache::remember($cacheKey, 3600, function () use ($description) {
            return $this->performAiAnalysis($description);
        });
    }

    /**
     * Perform the actual AI analysis
     */
    protected function performAiAnalysis(string $description): array
    {
        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($description);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->timeout(20)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 4000,
            ]);

            if (!$response->successful()) {
                throw new \Exception('OpenAI API request failed: ' . $response->body());
            }

            $responseData = $response->json();
            $content = $responseData['choices'][0]['message']['content'] ?? '';

            // Parse the JSON response
            $analysisResult = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to parse AI response as JSON: ' . json_last_error_msg());
            }

            // Validate and sanitize the result
            return $this->validateAndSanitizeResult($analysisResult);

        } catch (\Exception $e) {
            Log::error('AI analysis failed', [
                'error' => $e->getMessage(),
                'description_length' => strlen($description),
            ]);

            // Return a fallback configuration
            return $this->getFallbackConfiguration($description);
        }
    }

    /**
     * Build the system prompt for AI analysis
     */
    protected function buildSystemPrompt(): string
    {
        $assetTypes = AssetType::pluck('type')->toArray();
        $taxTypes = TaxType::pluck('type')->toArray();

        return "You are a financial analysis AI that converts natural language descriptions of economic situations into structured asset configurations.

AVAILABLE ASSET TYPES: " . implode(', ', $assetTypes) . "
AVAILABLE TAX TYPES: " . implode(', ', $taxTypes) . "

Your task is to analyze the user's economic description and create a JSON structure with:
1. A main configuration object with personal details and timeline
2. An array of assets with their yearly data

RESPONSE FORMAT (JSON only, no other text):
{
  \"configuration\": {
    \"name\": \"Descriptive name for this configuration\",
    \"description\": \"Brief summary of the financial situation\",
    \"birth_year\": 1988,
    \"prognose_age\": 65,
    \"pension_official_age\": 67,
    \"pension_wish_age\": 65,
    \"death_age\": 85,
    \"export_start_age\": 25
  },
  \"assets\": [
    {
      \"name\": \"Asset name\",
      \"description\": \"Asset description\",
      \"code\": \"unique_code\",
      \"asset_type\": \"one of the available asset types\",
      \"tax_type\": \"one of the available tax types\",
      \"group\": \"private or business\",
      \"tax_country\": \"no\",
      \"sort_order\": 1,
      \"years\": [
        {
          \"year\": 2024,
          \"market_amount\": 50000,
          \"acquisition_amount\": 45000,
          \"equity_amount\": 50000,
          \"paid_amount\": 0,
          \"taxable_initial_amount\": 0,
          \"income_amount\": 2000,
          \"income_factor\": \"yearly\",
          \"expence_amount\": 500,
          \"expence_factor\": \"yearly\",
          \"change_rate_type\": \"same as asset_type\",
          \"start_year\": 2024,
          \"end_year\": null,
          \"sort_order\": 1
        }
      ]
    }
  ]
}

GUIDELINES:
- Use current year (2024) unless user specifies otherwise
- Map financial items to appropriate asset types (savings->cash, house->real_estate, stocks->equity, etc.)
- Set realistic amounts based on description
- Include income and expenses where mentioned
- Use 'yearly' for income_factor and expence_factor
- Set appropriate age ranges and retirement goals
- Create separate assets for different financial items
- Use Norwegian tax country code 'no' by default";
    }

    /**
     * Build the user prompt with the economic description
     */
    protected function buildUserPrompt(string $description): string
    {
        return "Analyze this economic situation and create a structured asset configuration:\n\n" . $description;
    }

    /**
     * Validate and sanitize the AI analysis result
     */
    protected function validateAndSanitizeResult(array $result): array
    {
        // Ensure required structure exists
        if (!isset($result['configuration']) || !isset($result['assets'])) {
            throw new \Exception('AI response missing required configuration or assets structure');
        }

        // Validate configuration
        $config = $result['configuration'];
        $config['name'] = $config['name'] ?? 'AI Generated Configuration';
        $config['description'] = $config['description'] ?? 'Generated from economic situation analysis';
        $config['birth_year'] = (int) ($config['birth_year'] ?? (date('Y') - 35));
        $config['prognose_age'] = (int) ($config['prognose_age'] ?? 65);
        $config['pension_official_age'] = (int) ($config['pension_official_age'] ?? 67);
        $config['pension_wish_age'] = (int) ($config['pension_wish_age'] ?? 65);
        $config['death_age'] = (int) ($config['death_age'] ?? 85);
        $config['export_start_age'] = (int) ($config['export_start_age'] ?? 25);

        // Validate assets
        $validAssetTypes = AssetType::pluck('type')->toArray();
        $validTaxTypes = TaxType::pluck('type')->toArray();

        foreach ($result['assets'] as &$asset) {
            $asset['name'] = $asset['name'] ?? 'Unnamed Asset';
            $asset['description'] = $asset['description'] ?? 'AI generated asset';
            $asset['code'] = $asset['code'] ?? \Illuminate\Support\Str::slug($asset['name']);

            // Validate asset type
            if (!in_array($asset['asset_type'] ?? '', $validAssetTypes)) {
                $asset['asset_type'] = 'other';
            }

            // Validate tax type
            if (!in_array($asset['tax_type'] ?? '', $validTaxTypes)) {
                $asset['tax_type'] = 'none';
            }

            $asset['group'] = in_array($asset['group'] ?? '', ['private', 'business']) ? $asset['group'] : 'private';
            $asset['tax_country'] = $asset['tax_country'] ?? 'no';
            $asset['sort_order'] = (int) ($asset['sort_order'] ?? 1);

            // Validate years data
            if (!isset($asset['years']) || !is_array($asset['years'])) {
                $asset['years'] = [];
            }

            foreach ($asset['years'] as &$year) {
                $year['year'] = (int) ($year['year'] ?? date('Y'));
                $year['market_amount'] = (float) ($year['market_amount'] ?? 0);
                $year['acquisition_amount'] = (float) ($year['acquisition_amount'] ?? 0);
                $year['equity_amount'] = (float) ($year['equity_amount'] ?? 0);
                $year['paid_amount'] = (float) ($year['paid_amount'] ?? 0);
                $year['taxable_initial_amount'] = (float) ($year['taxable_initial_amount'] ?? 0);
                $year['income_amount'] = (float) ($year['income_amount'] ?? 0);
                $year['income_factor'] = in_array($year['income_factor'] ?? '', ['monthly', 'yearly']) ? $year['income_factor'] : 'yearly';
                $year['expence_amount'] = (float) ($year['expence_amount'] ?? 0);
                $year['expence_factor'] = in_array($year['expence_factor'] ?? '', ['monthly', 'yearly']) ? $year['expence_factor'] : 'yearly';
                $year['change_rate_type'] = $year['change_rate_type'] ?? $asset['asset_type'];
                $year['start_year'] = (int) ($year['start_year'] ?? date('Y'));
                $year['end_year'] = isset($year['end_year']) ? (int) $year['end_year'] : null;
                $year['sort_order'] = (int) ($year['sort_order'] ?? 1);
            }
        }

        return [
            'configuration' => $config,
            'assets' => $result['assets']
        ];
    }

    /**
     * Get a fallback configuration when AI analysis fails
     */
    protected function getFallbackConfiguration(string $description): array
    {
        return [
            'configuration' => [
                'name' => 'Basic Configuration',
                'description' => 'Fallback configuration created when AI analysis was unavailable',
                'birth_year' => date('Y') - 35,
                'prognose_age' => 65,
                'pension_official_age' => 67,
                'pension_wish_age' => 65,
                'death_age' => 85,
                'export_start_age' => 25,
            ],
            'assets' => [
                [
                    'name' => 'Basic Savings',
                    'description' => 'Basic savings account',
                    'code' => 'basic_savings',
                    'asset_type' => 'cash',
                    'tax_type' => 'none',
                    'group' => 'private',
                    'tax_country' => 'no',
                    'sort_order' => 1,
                    'years' => [
                        [
                            'year' => (int) date('Y'),
                            'market_amount' => 10000,
                            'acquisition_amount' => 10000,
                            'equity_amount' => 10000,
                            'paid_amount' => 0,
                            'taxable_initial_amount' => 0,
                            'income_amount' => 0,
                            'income_factor' => 'yearly',
                            'expence_amount' => 0,
                            'expence_factor' => 'yearly',
                            'change_rate_type' => 'cash',
                            'start_year' => (int) date('Y'),
                            'end_year' => null,
                            'sort_order' => 1,
                        ]
                    ]
                ]
            ]
        ];
    }
}

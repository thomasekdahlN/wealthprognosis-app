<?php

namespace App\Services;

use App\Models\AiInstruction;
use App\Models\AssetConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiEvaluationService
{
    protected string $apiKey;

    protected string $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');

        if (! $this->apiKey) {
            throw new \RuntimeException('OpenAI API key not configured. Please set OPENAI_API_KEY in your environment.');
        }
    }

    /**
     * Evaluate an asset owner using a specific AI instruction
     */
    public function evaluateAssetOwner(AssetConfiguration $assetOwner, AiInstruction $instruction): array
    {
        // Export asset owner to JSON
        $jsonData = AssetExportService::toJsonString($assetOwner);

        // Build the user prompt with the JSON data
        $userPrompt = $instruction->buildUserPrompt(['json_data' => $jsonData]);

        // Make the API call
        $response = $this->callOpenAI(
            $instruction->system_prompt,
            $userPrompt,
            $instruction->model,
            $instruction->max_tokens,
            $instruction->temperature
        );

        return [
            'asset_configuration_id' => $assetOwner->id,
            'asset_configuration_name' => $assetOwner->name,
            'instruction_id' => $instruction->id,
            'instruction_name' => $instruction->name,
            'model' => $instruction->model,
            'evaluation' => $response['content'],
            'tokens_used' => $response['usage'] ?? null,
            'success' => $response['success'],
            'error' => $response['error'] ?? null,
        ];
    }

    /**
     * Evaluate an asset owner using multiple AI instructions
     */
    public function evaluateWithMultipleInstructions(AssetConfiguration $assetOwner, array $instructionIds = []): array
    {
        $query = AiInstruction::active()->ordered();

        if (! empty($instructionIds)) {
            $query->whereIn('id', $instructionIds);
        }

        $instructions = $query->get();

        if ($instructions->isEmpty()) {
            throw new \InvalidArgumentException('No active AI instructions found');
        }

        $results = [];

        foreach ($instructions as $instruction) {
            try {
                $result = $this->evaluateAssetOwner($assetOwner, $instruction);
                $results[] = $result;

                Log::info('AI evaluation completed', [
                    'asset_configuration_id' => $assetOwner->id,
                    'instruction_id' => $instruction->id,
                    'success' => $result['success'],
                ]);

            } catch (\Exception $e) {
                $errorResult = [
                    'asset_configuration_id' => $assetOwner->id,
                    'asset_configuration_name' => $assetOwner->name,
                    'instruction_id' => $instruction->id,
                    'instruction_name' => $instruction->name,
                    'model' => $instruction->model,
                    'evaluation' => null,
                    'tokens_used' => null,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                $results[] = $errorResult;

                Log::error('AI evaluation failed', [
                    'asset_configuration_id' => $assetOwner->id,
                    'instruction_id' => $instruction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Call OpenAI API
     */
    protected function callOpenAI(
        string $systemPrompt,
        string $userPrompt,
        string $model = 'gpt-4',
        int $maxTokens = 2000,
        float $temperature = 0.7
    ): array {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->baseUrl.'/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt,
                    ],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'content' => $data['choices'][0]['message']['content'] ?? 'No response content',
                    'usage' => $data['usage'] ?? null,
                ];
            } else {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';

                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'error' => $errorMessage,
                    'response' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'content' => null,
                    'error' => "API Error ({$response->status()}): {$errorMessage}",
                ];
            }

        } catch (\Exception $e) {
            Log::error('OpenAI API call failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'content' => null,
                'error' => 'API call failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Test the OpenAI API connection
     */
    public function testConnection(): array
    {
        try {
            $response = $this->callOpenAI(
                'You are a helpful assistant.',
                'Say "Hello, the API connection is working!" in a friendly way.',
                'gpt-3.5-turbo',
                50,
                0.5
            );

            return $response;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'content' => null,
                'error' => 'Connection test failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get available models
     */
    public function getAvailableModels(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])->get($this->baseUrl.'/models');

            if ($response->successful()) {
                $data = $response->json();
                $models = collect($data['data'])
                    ->filter(fn ($model) => str_starts_with($model['id'], 'gpt-'))
                    ->pluck('id')
                    ->sort()
                    ->values()
                    ->toArray();

                return [
                    'success' => true,
                    'models' => $models,
                ];
            }

            return [
                'success' => false,
                'models' => [],
                'error' => 'Failed to fetch models: '.$response->body(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'models' => [],
                'error' => 'Failed to fetch models: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Static method for easy usage
     */
    public static function evaluate(AssetConfiguration $assetOwner, AiInstruction $instruction): array
    {
        $service = new static;

        return $service->evaluateAssetOwner($assetOwner, $instruction);
    }

    /**
     * Static method for multiple evaluations
     */
    public static function evaluateMultiple(AssetConfiguration $assetOwner, array $instructionIds = []): array
    {
        $service = new static;

        return $service->evaluateWithMultipleInstructions($assetOwner, $instructionIds);
    }
}

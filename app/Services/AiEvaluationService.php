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
        $apiKey = config('ai.api_key');

        if (! $apiKey || ! is_string($apiKey)) {
            throw new \RuntimeException('OpenAI API key not configured. Please set AI_API_KEY or OPENAI_API_KEY in your environment.');
        }

        $this->apiKey = $apiKey;
    }

    /**
     * Evaluate an asset configuration using a specific AI instruction
     *
     * @return array<string, mixed>
     */
    public function evaluateAssetConfiguration(AssetConfiguration $assetConfiguration, AiInstruction $instruction): array
    {
        // Export asset configuration to JSON
        $jsonData = AssetExportService::toJsonString($assetConfiguration);

        // Build the user prompt with the JSON data
        $userPrompt = $instruction->buildUserPrompt(['json_data' => $jsonData]);

        // Make the API call
        $response = $this->callOpenAI(
            $instruction->system_prompt,
            $userPrompt,
            $instruction->model,
            $instruction->max_tokens,
            (float) $instruction->temperature
        );

        return [
            'asset_configuration_id' => $assetConfiguration->id,
            'asset_configuration_name' => $assetConfiguration->name,
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
     * Evaluate an asset configuration using multiple AI instructions
     *
     * @param  array<int, int>  $instructionIds
     * @return list<array<string, mixed>>
     */
    public function evaluateWithMultipleInstructions(AssetConfiguration $assetConfiguration, array $instructionIds = []): array
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
                $result = $this->evaluateAssetConfiguration($assetConfiguration, $instruction);
                $results[] = $result;

                Log::info('AI evaluation completed', [
                    'asset_configuration_id' => $assetConfiguration->id,
                    'instruction_id' => $instruction->id,
                    'success' => $result['success'],
                ]);

            } catch (\Exception $e) {
                $errorResult = [
                    'asset_configuration_id' => $assetConfiguration->id,
                    'asset_configuration_name' => $assetConfiguration->name,
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
                    'asset_configuration_id' => $assetConfiguration->id,
                    'instruction_id' => $instruction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Call OpenAI API
     *
     * @return array<string, mixed>
     */
    public function callOpenAI(
        string $systemPrompt,
        string $userPrompt,
        string $model = 'gpt-4',
        int $maxTokens = 2000,
        float $temperature = 0.7
    ): array {
        try {
            $payload = [
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
                'max_completion_tokens' => $maxTokens,
            ];

            // Only add temperature if it's not the default value of 1
            // Some models (like gpt-5) only support temperature = 1
            if ($temperature !== 1.0) {
                $payload['temperature'] = $temperature;
            }

            // Log the complete request details including full payload (using dedicated ai channel)
            Log::channel('ai')->info('OpenAI API Request - Summary', [
                'model' => $model,
                'max_completion_tokens' => $maxTokens,
                'temperature' => $temperature,
                'system_prompt_length' => strlen($systemPrompt),
                'user_prompt_length' => strlen($userPrompt),
                'estimated_input_tokens' => (int) ((strlen($systemPrompt) + strlen($userPrompt)) / 4),
            ]);

            // Log the complete payload being sent to OpenAI
            Log::channel('ai')->info('OpenAI API Request - Complete Payload', [
                'payload' => $payload,
                'endpoint' => $this->baseUrl.'/chat/completions',
            ]);

            // Log the full prompts separately for easier reading
            Log::channel('ai')->info('OpenAI API Request - System Prompt', [
                'system_prompt' => $systemPrompt,
            ]);

            Log::channel('ai')->info('OpenAI API Request - User Prompt', [
                'user_prompt' => $userPrompt,
            ]);

            Log::channel('ai')->info('🚀 Starting OpenAI API HTTP Request', [
                'timestamp' => now()->toDateTimeString(),
                'timeout' => 300,
            ]);

            $startTime = microtime(true);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(300)->post($this->baseUrl.'/chat/completions', $payload);
            $duration = microtime(true) - $startTime;

            Log::channel('ai')->info('✅ OpenAI API HTTP Request Completed', [
                'timestamp' => now()->toDateTimeString(),
                'duration_seconds' => round($duration, 2),
                'status_code' => $response->status(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('OpenAI API Response Success', [
                    'model' => $model,
                    'usage' => $data['usage'] ?? null,
                    'response_length' => strlen($data['choices'][0]['message']['content'] ?? ''),
                    'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
                ]);

                return [
                    'success' => true,
                    'content' => $data['choices'][0]['message']['content'] ?? 'No response content',
                    'usage' => $data['usage'] ?? null,
                ];
            } else {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';

                Log::error('OpenAI API error', [
                    'model' => $model,
                    'status' => $response->status(),
                    'error' => $errorMessage,
                    'response' => $response->body(),
                    'system_prompt_length' => strlen($systemPrompt),
                    'user_prompt_length' => strlen($userPrompt),
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
     *
     * @return array<string, mixed>
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
     *
     * @return array<string, mixed>
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
     *
     * @return array<string, mixed>
     */
    public static function evaluate(AssetConfiguration $assetConfiguration, AiInstruction $instruction): array
    {
        $service = new self;

        return $service->evaluateAssetConfiguration($assetConfiguration, $instruction);
    }

    /**
     * Static method for multiple evaluations
     *
     * @param  array<int, int>  $instructionIds
     * @return list<array<string, mixed>>
     */
    public static function evaluateMultiple(AssetConfiguration $assetConfiguration, array $instructionIds = []): array
    {
        $service = new self;

        return $service->evaluateWithMultipleInstructions($assetConfiguration, $instructionIds);
    }
}

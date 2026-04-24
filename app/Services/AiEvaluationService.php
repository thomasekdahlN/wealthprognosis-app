<?php

namespace App\Services;

use App\Models\AiInstruction;
use App\Models\AssetConfiguration;
use Illuminate\Support\Facades\Log;

class AiEvaluationService
{
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
        $response = $this->callAi(
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
     * Call AI API using Laravel AI SDK
     *
     * @return array<string, mixed>
     */
    public function callAi(
        string $systemPrompt,
        string $userPrompt,
        string $model = '',
        int $maxTokens = 2000,
        float $temperature = 0.7
    ): array {
        try {
            if ($model === '') {
                $defaultModel = config('ai.default_model');
                $model = is_string($defaultModel) ? $defaultModel : 'gemini-3.1-flash-lite-preview';
            }

            Log::channel('ai')->info('AI API Request - Summary', [
                'model' => $model,
                'max_completion_tokens' => $maxTokens,
                'temperature' => $temperature,
                'system_prompt_length' => strlen($systemPrompt),
                'user_prompt_length' => strlen($userPrompt),
            ]);

            Log::channel('ai')->info('🚀 Starting AI API Request', [
                'timestamp' => now()->toDateTimeString(),
                'timeout' => 300,
            ]);

            $startTime = microtime(true);

            /** @var \Laravel\Ai\Responses\AgentResponse $response */
            $response = (new \Laravel\Ai\AnonymousAgent($systemPrompt, [], []))->prompt(
                $userPrompt,
                model: $model,
                timeout: 300,
            );

            $duration = microtime(true) - $startTime;

            Log::channel('ai')->info('✅ AI API Request Completed', [
                'timestamp' => now()->toDateTimeString(),
                'duration_seconds' => round($duration, 2),
            ]);

            $usage = $response->usage;

            return [
                'success' => true,
                'content' => (string) $response,
                'usage' => [
                    'prompt_tokens' => $usage->promptTokens,
                    'completion_tokens' => $usage->completionTokens,
                    'total_tokens' => $usage->promptTokens + $usage->completionTokens,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('AI API call failed', [
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
            $response = $this->callAi(
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
        // Return Gemini models directly as AI SDK abstract models listing might vary
        return [
            'success' => true,
            'models' => [
                'gemini-3.1-flash-lite-preview',
                'gemini-2.0-flash',
                'gemini-1.5-pro',
            ],
        ];
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

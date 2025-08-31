<?php

use App\Models\AiInstruction;
use App\Models\AssetConfiguration;
use App\Models\User;
use App\Services\AiEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    $this->assetOwner = AssetConfiguration::factory()->create([
        'name' => 'Test Owner',
        'user_id' => $this->user->id,
    ]);

    $this->instruction = AiInstruction::factory()->create([
        'name' => 'Test Instruction',
        'system_prompt' => 'You are a test assistant.',
        'user_prompt_template' => 'Analyze this data: {json_data}',
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => 100,
        'temperature' => 0.5,
        'is_active' => true,
        'user_id' => $this->user->id,
    ]);
});

test('throws exception when api key not configured', function () {
    config(['services.openai.api_key' => null]);

    expect(fn () => new AiEvaluationService)
        ->toThrow(TypeError::class);
});

test('can evaluate asset owner with mocked api', function () {
    config(['services.openai.api_key' => 'test-key']);

    // Mock successful OpenAI API response
    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'This is a test evaluation response.',
                    ],
                ],
            ],
            'usage' => [
                'total_tokens' => 50,
            ],
        ], 200),
    ]);

    $service = new AiEvaluationService;
    $result = $service->evaluateAssetOwner($this->assetOwner, $this->instruction);

    expect($result)->toBeArray();
    expect($result['success'])->toBeTrue();
    expect($result['asset_configuration_id'])->toBe($this->assetOwner->id);
    expect($result['instruction_id'])->toBe($this->instruction->id);
    expect($result['evaluation'])->toBe('This is a test evaluation response.');
    expect($result)->toHaveKey('tokens_used');
});

test('handles api error gracefully', function () {
    config(['services.openai.api_key' => 'test-key']);

    // Mock API error response
    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::response([
            'error' => [
                'message' => 'Invalid API key',
            ],
        ], 401),
    ]);

    $service = new AiEvaluationService;
    $result = $service->evaluateAssetOwner($this->assetOwner, $this->instruction);

    expect($result)->toBeArray();
    expect($result['success'])->toBeFalse();
    expect($result['evaluation'])->toBeNull();
    expect($result['error'])->toContain('Invalid API key');
});

test('can evaluate with multiple instructions', function () {
    config(['services.openai.api_key' => 'test-key']);

    // Create additional instruction
    $instruction2 = AiInstruction::factory()->create([
        'name' => 'Second Instruction',
        'system_prompt' => 'You are another test assistant.',
        'user_prompt_template' => 'Review this: {json_data}',
        'is_active' => true,
        'user_id' => $this->user->id,
    ]);

    // Mock successful responses
    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::sequence()
            ->push(['choices' => [['message' => ['content' => 'First evaluation']]], 'usage' => ['total_tokens' => 30]], 200)
            ->push(['choices' => [['message' => ['content' => 'Second evaluation']]], 'usage' => ['total_tokens' => 40]], 200),
    ]);

    $service = new AiEvaluationService;
    $results = $service->evaluateWithMultipleInstructions($this->assetOwner, [$this->instruction->id, $instruction2->id]);

    expect($results)->toBeArray()->toHaveCount(2);

    expect($results[0]['success'])->toBeTrue();
    expect($results[0]['evaluation'])->toBe('First evaluation');

    expect($results[1]['success'])->toBeTrue();
    expect($results[1]['evaluation'])->toBe('Second evaluation');
});

test('filters inactive instructions', function () {
    config(['services.openai.api_key' => 'test-key']);

    // Make instruction inactive
    $this->instruction->update(['is_active' => false]);

    $service = new AiEvaluationService;

    expect(fn () => $service->evaluateWithMultipleInstructions($this->assetOwner))
        ->toThrow(InvalidArgumentException::class, 'No active AI instructions found');
});

test('builds user prompt with json data', function () {
    $jsonData = '{"test": "data"}';
    $prompt = $this->instruction->buildUserPrompt(['json_data' => $jsonData]);

    expect($prompt)->toBe('Analyze this data: {"test": "data"}');
});

test('static evaluate method', function () {
    config(['services.openai.api_key' => 'test-key']);

    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Static method test']]],
            'usage' => ['total_tokens' => 25],
        ], 200),
    ]);

    $result = AiEvaluationService::evaluate($this->assetOwner, $this->instruction);

    expect($result)->toBeArray();
    expect($result['success'])->toBeTrue();
    expect($result['evaluation'])->toBe('Static method test');
});

test('connection test', function () {
    config(['services.openai.api_key' => 'test-key']);

    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Hello, the API connection is working!']]],
            'usage' => ['total_tokens' => 15],
        ], 200),
    ]);

    $service = new AiEvaluationService;
    $result = $service->testConnection();

    expect($result['success'])->toBeTrue();
    expect($result['content'])->toContain('Hello, the API connection is working!');
});

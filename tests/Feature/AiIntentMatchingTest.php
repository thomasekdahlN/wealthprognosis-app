<?php

use App\Models\Team;
use App\Models\User;
use App\Services\AiAssistantService;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->actingAs($this->user);
});

it('correctly identifies intents for asset creation requests', function () {
    $testData = json_decode(file_get_contents(base_path('tests/Data/ai-intent-test-cases.json')), true);
    $service = new AiAssistantService;

    // Use reflection to access the protected analyzeIntent method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('analyzeIntent');
    $method->setAccessible(true);

    foreach ($testData['create_assets']['test_cases'] as $testCase) {
        $intent = $method->invoke($service, $testCase['message'], []);

        expect($intent['type'])
            ->toBe($testCase['expected_intent'])
            ->and($intent['confidence'])
            ->toBeGreaterThanOrEqual($testCase['expected_confidence'] - 0.1) // Allow small variance
            ->and($intent['confidence'])
            ->toBeLessThanOrEqual(1.0);

        // Log for debugging
        if ($intent['type'] !== $testCase['expected_intent']) {
            dump("FAILED: '{$testCase['message']}' -> Expected: {$testCase['expected_intent']}, Got: {$intent['type']}");
        }
    }
});

it('correctly identifies intents for asset update requests', function () {
    $testData = json_decode(file_get_contents(base_path('tests/Data/ai-intent-test-cases.json')), true);
    $service = new AiAssistantService;

    // Use reflection to access the protected analyzeIntent method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('analyzeIntent');
    $method->setAccessible(true);

    foreach ($testData['update_assets']['test_cases'] as $testCase) {
        $intent = $method->invoke($service, $testCase['message'], []);

        expect($intent['type'])
            ->toBe($testCase['expected_intent'])
            ->and($intent['confidence'])
            ->toBeGreaterThanOrEqual($testCase['expected_confidence'] - 0.1) // Allow small variance
            ->and($intent['confidence'])
            ->toBeLessThanOrEqual(1.0);

        // Log for debugging
        if ($intent['type'] !== $testCase['expected_intent']) {
            dump("FAILED: '{$testCase['message']}' -> Expected: {$testCase['expected_intent']}, Got: {$intent['type']}");
        }
    }
});

it('correctly identifies intents for configuration creation requests', function () {
    $testData = json_decode(file_get_contents(base_path('tests/Data/ai-intent-test-cases.json')), true);
    $service = new AiAssistantService;

    // Use reflection to access the protected analyzeIntent method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('analyzeIntent');
    $method->setAccessible(true);

    foreach ($testData['create_configurations']['test_cases'] as $testCase) {
        $intent = $method->invoke($service, $testCase['message'], []);

        expect($intent['type'])
            ->toBe($testCase['expected_intent'])
            ->and($intent['confidence'])
            ->toBeGreaterThanOrEqual($testCase['expected_confidence'] - 0.1) // Allow small variance
            ->and($intent['confidence'])
            ->toBeLessThanOrEqual(1.0);

        // Log for debugging
        if ($intent['type'] !== $testCase['expected_intent']) {
            dump("FAILED: '{$testCase['message']}' -> Expected: {$testCase['expected_intent']}, Got: {$intent['type']}");
        }
    }
});

it('correctly handles edge cases and potential misclassifications', function () {
    $testData = json_decode(file_get_contents(base_path('tests/Data/ai-intent-test-cases.json')), true);
    $service = new AiAssistantService;

    // Use reflection to access the protected analyzeIntent method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('analyzeIntent');
    $method->setAccessible(true);

    foreach ($testData['edge_cases']['test_cases'] as $testCase) {
        $intent = $method->invoke($service, $testCase['message'], []);

        expect($intent['type'])
            ->toBe($testCase['expected_intent'])
            ->and($intent['confidence'])
            ->toBeGreaterThanOrEqual($testCase['expected_confidence'] - 0.1) // Allow small variance
            ->and($intent['confidence'])
            ->toBeLessThanOrEqual(1.0);

        // Log for debugging
        if ($intent['type'] !== $testCase['expected_intent']) {
            dump("FAILED: '{$testCase['message']}' -> Expected: {$testCase['expected_intent']}, Got: {$intent['type']}");
        }
    }
});

it('correctly handles complex parsing cases', function () {
    $testData = json_decode(file_get_contents(base_path('tests/Data/ai-intent-test-cases.json')), true);
    $service = new AiAssistantService;

    // Use reflection to access the protected analyzeIntent method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('analyzeIntent');
    $method->setAccessible(true);

    foreach ($testData['complex_cases']['test_cases'] as $testCase) {
        $intent = $method->invoke($service, $testCase['message'], []);

        expect($intent['type'])
            ->toBe($testCase['expected_intent'])
            ->and($intent['confidence'])
            ->toBeGreaterThanOrEqual($testCase['expected_confidence'] - 0.1) // Allow small variance
            ->and($intent['confidence'])
            ->toBeLessThanOrEqual(1.0);

        // Log for debugging
        if ($intent['type'] !== $testCase['expected_intent']) {
            dump("FAILED: '{$testCase['message']}' -> Expected: {$testCase['expected_intent']}, Got: {$intent['type']}");
        }
    }
});

it('provides detailed test results summary', function () {
    $testData = json_decode(file_get_contents(base_path('tests/Data/ai-intent-test-cases.json')), true);
    $service = new AiAssistantService;

    // Use reflection to access the protected analyzeIntent method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('analyzeIntent');
    $method->setAccessible(true);

    $totalTests = 0;
    $passedTests = 0;
    $failedTests = [];

    foreach ($testData as $category => $categoryData) {
        foreach ($categoryData['test_cases'] as $testCase) {
            $totalTests++;
            $intent = $method->invoke($service, $testCase['message'], []);

            if ($intent['type'] === $testCase['expected_intent'] &&
                $intent['confidence'] >= ($testCase['expected_confidence'] - 0.1)) {
                $passedTests++;
            } else {
                $failedTests[] = [
                    'category' => $category,
                    'message' => $testCase['message'],
                    'language' => $testCase['language'],
                    'expected_intent' => $testCase['expected_intent'],
                    'expected_confidence' => $testCase['expected_confidence'],
                    'actual_intent' => $intent['type'],
                    'actual_confidence' => $intent['confidence'],
                    'description' => $testCase['description'],
                ];
            }
        }
    }

    // Output summary
    echo "\n=== AI Intent Matching Test Summary ===\n";
    echo "Total tests: {$totalTests}\n";
    echo "Passed: {$passedTests}\n";
    echo 'Failed: '.count($failedTests)."\n";
    echo 'Success rate: '.round(($passedTests / $totalTests) * 100, 2)."%\n";

    if (! empty($failedTests)) {
        echo "\n=== Failed Tests ===\n";
        foreach ($failedTests as $failure) {
            echo "❌ [{$failure['category']}] {$failure['language']}: '{$failure['message']}'\n";
            echo "   Expected: {$failure['expected_intent']} (confidence: {$failure['expected_confidence']})\n";
            echo "   Actual: {$failure['actual_intent']} (confidence: {$failure['actual_confidence']})\n";
            echo "   Description: {$failure['description']}\n\n";
        }
    }

    // The test should pass if all intents are correctly identified
    expect(count($failedTests))->toBe(0, 'All intent matching tests should pass');
});

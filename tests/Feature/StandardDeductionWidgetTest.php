<?php

use App\Filament\Resources\TaxConfigurations\Widgets\StandardDeductionWidget;
use App\Models\TaxConfiguration;
use App\Models\TaxType;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Create tax types that will be referenced by tax configurations
    TaxType::create(['type' => 'salary', 'name' => 'Salary', 'is_active' => true, 'sort_order' => 1]);
    TaxType::create(['type' => 'airbnb', 'name' => 'Airbnb', 'is_active' => true, 'sort_order' => 2]);
});

it('can instantiate the standard deduction widget', function () {
    $widget = new StandardDeductionWidget;

    expect($widget)->toBeInstanceOf(StandardDeductionWidget::class);
});

it('displays standard deduction trends for a specific country and tax type', function () {
    // Create tax configurations for multiple years
    TaxConfiguration::factory()->create([
        'country_code' => 'no',
        'year' => 2023,
        'tax_type' => 'airbnb',
        'configuration' => [
            'income' => 22,
            'standardDeduction' => 10000,
            'realization' => 0,
            'fortune' => 0,
        ],
    ]);

    TaxConfiguration::factory()->create([
        'country_code' => 'no',
        'year' => 2024,
        'tax_type' => 'airbnb',
        'configuration' => [
            'income' => 22,
            'standardDeduction' => 11000,
            'realization' => 0,
            'fortune' => 0,
        ],
    ]);

    TaxConfiguration::factory()->create([
        'country_code' => 'no',
        'year' => 2025,
        'tax_type' => 'airbnb',
        'configuration' => [
            'income' => 22,
            'standardDeduction' => 12000,
            'realization' => 0,
            'fortune' => 0,
        ],
    ]);

    $widget = new StandardDeductionWidget;
    $widget->mount(['country' => 'no', 'tax_type' => 'airbnb']);

    // Use reflection to access protected getData method
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getData');
    $method->setAccessible(true);
    $data = $method->invoke($widget);

    expect($data)->toHaveKey('datasets')
        ->and($data)->toHaveKey('labels')
        ->and($data['labels'])->toBe(['2023', '2024', '2025'])
        ->and($data['datasets'])->toHaveCount(1)
        ->and($data['datasets'][0]['label'])->toBe('Standard Deduction')
        ->and($data['datasets'][0]['data'])->toBe([10000.0, 11000.0, 12000.0]);
});

it('handles missing context gracefully', function () {
    $widget = new StandardDeductionWidget;
    $widget->mount([]);

    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getData');
    $method->setAccessible(true);
    $data = $method->invoke($widget);

    expect($data['datasets'])->toHaveCount(1)
        ->and($data['datasets'][0]['label'])->toBe('No Data')
        ->and($data['labels'])->toBe(['Missing context']);
});

it('handles no data for country and tax type', function () {
    $widget = new StandardDeductionWidget;
    $widget->mount(['country' => 'se', 'tax_type' => 'salary']);

    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getData');
    $method->setAccessible(true);
    $data = $method->invoke($widget);

    expect($data['datasets'])->toHaveCount(1)
        ->and($data['datasets'][0]['label'])->toBe('No data available')
        ->and($data['labels'])->toBe(['No data']);
});

it('handles zero standard deduction values', function () {
    TaxConfiguration::factory()->create([
        'country_code' => 'no',
        'year' => 2023,
        'tax_type' => 'salary',
        'configuration' => [
            'income' => 22,
            'standardDeduction' => 0,
            'realization' => 0,
            'fortune' => 0,
        ],
    ]);

    TaxConfiguration::factory()->create([
        'country_code' => 'no',
        'year' => 2024,
        'tax_type' => 'salary',
        'configuration' => [
            'income' => 23,
            'standardDeduction' => 0,
            'realization' => 0,
            'fortune' => 0,
        ],
    ]);

    $widget = new StandardDeductionWidget;
    $widget->mount(['country' => 'no', 'tax_type' => 'salary']);

    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getData');
    $method->setAccessible(true);
    $data = $method->invoke($widget);

    expect($data['datasets'])->toHaveCount(1)
        ->and($data['datasets'][0]['label'])->toBe('No standard deduction data available');
});

it('resolves context from record property', function () {
    $taxConfig = TaxConfiguration::factory()->create([
        'country_code' => 'no',
        'year' => 2023,
        'tax_type' => 'airbnb',
        'configuration' => [
            'income' => 22,
            'standardDeduction' => 10000,
            'realization' => 0,
            'fortune' => 0,
        ],
    ]);

    $widget = new StandardDeductionWidget;
    $widget->mount(['record' => $taxConfig]);

    expect($widget->getHeading())->toContain('Airbnb')
        ->and($widget->getHeading())->toContain('NO');
});

it('generates correct heading with tax type and country', function () {
    $widget = new StandardDeductionWidget;
    $widget->mount(['country' => 'no', 'tax_type' => 'airbnb']);

    $heading = $widget->getHeading();

    expect($heading)->toContain('Airbnb')
        ->and($heading)->toContain('NO');
});

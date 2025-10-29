<?php

use App\Filament\Resources\TaxConfigurations\Widgets\TaxRateTrendWidget;
use App\Models\TaxConfiguration;
use App\Models\TaxType;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Create tax types that will be referenced by tax configurations
    TaxType::create(['type' => 'salary', 'name' => 'Salary', 'is_active' => true, 'sort_order' => 1]);
    TaxType::create(['type' => 'stock', 'name' => 'Stock', 'is_active' => true, 'sort_order' => 2]);
    TaxType::create(['type' => 'equityfund', 'name' => 'Equity Fund', 'is_active' => true, 'sort_order' => 3]);
    TaxType::create(['type' => 'bondfund', 'name' => 'Bond Fund', 'is_active' => true, 'sort_order' => 4]);
});

it('can instantiate the tax rate trend widget', function () {
    $widget = new TaxRateTrendWidget;

    expect($widget)->toBeInstanceOf(TaxRateTrendWidget::class);
});

it('displays tax rate trends for a specific country and tax type', function () {
    // Create tax configurations for multiple years
    TaxConfiguration::factory()->create([
        'country_code' => 'no',
        'year' => 2023,
        'tax_type' => 'salary',
        'configuration' => [
            'income' => 22,
            'standardDeduction' => 5,
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
            'standardDeduction' => 5.5,
            'realization' => 0,
            'fortune' => 0,
        ],
    ]);

    TaxConfiguration::factory()->create([
        'country_code' => 'no',
        'year' => 2025,
        'tax_type' => 'salary',
        'configuration' => [
            'income' => 24,
            'standardDeduction' => 6,
            'realization' => 0,
            'fortune' => 0,
        ],
    ]);

    $widget = new TaxRateTrendWidget;
    $widget->mount(['country' => 'no', 'tax_type' => 'salary']);

    // Use reflection to access protected getData method
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getData');
    $method->setAccessible(true);
    $data = $method->invoke($widget);

    expect($data)->toHaveKey('datasets')
        ->and($data)->toHaveKey('labels')
        ->and($data['labels'])->toBe(['2023', '2024', '2025'])
        ->and($data['datasets'])->toHaveCount(1); // Only income (standardDeduction moved to separate widget)
});

it('shows only non-zero tax rate types', function () {
    // Create tax configurations with only income and fortune
    TaxConfiguration::factory()->create([
        'country_code' => 'no',
        'year' => 2023,
        'tax_type' => 'equityfund',
        'configuration' => [
            'income' => 35,
            'standardDeduction' => 0,
            'realization' => 0,
            'fortune' => 0.3,
        ],
    ]);

    TaxConfiguration::factory()->create([
        'country_code' => 'no',
        'year' => 2024,
        'tax_type' => 'equityfund',
        'configuration' => [
            'income' => 35,
            'standardDeduction' => 0,
            'realization' => 0,
            'fortune' => 0.4,
        ],
    ]);

    $widget = new TaxRateTrendWidget;
    $widget->mount(['country' => 'no', 'tax_type' => 'equityfund']);

    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getData');
    $method->setAccessible(true);
    $data = $method->invoke($widget);

    expect($data['datasets'])->toHaveCount(2); // Only income and fortune

    $labels = array_column($data['datasets'], 'label');
    expect($labels)->toContain('Income Tax %')
        ->and($labels)->toContain('Fortune Tax %')
        ->and($labels)->not->toContain('Standard Deduction %')
        ->and($labels)->not->toContain('Realization Tax %');
});

it('displays all three tax rate types when present', function () {
    TaxConfiguration::factory()->create([
        'country_code' => 'no',
        'year' => 2023,
        'tax_type' => 'stock',
        'configuration' => [
            'income' => 35,
            'standardDeduction' => 10,
            'realization' => 22,
            'fortune' => 0.95,
        ],
    ]);

    TaxConfiguration::factory()->create([
        'country_code' => 'no',
        'year' => 2024,
        'tax_type' => 'stock',
        'configuration' => [
            'income' => 37,
            'standardDeduction' => 12,
            'realization' => 24,
            'fortune' => 1.0,
        ],
    ]);

    $widget = new TaxRateTrendWidget;
    $widget->mount(['country' => 'no', 'tax_type' => 'stock']);

    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getData');
    $method->setAccessible(true);
    $data = $method->invoke($widget);

    expect($data['datasets'])->toHaveCount(3); // Income, Realization, Fortune (standardDeduction moved to separate widget)

    $labels = array_column($data['datasets'], 'label');
    expect($labels)->toContain('Income Tax %')
        ->and($labels)->toContain('Realization Tax %')
        ->and($labels)->toContain('Fortune Tax %')
        ->and($labels)->not->toContain('Standard Deduction %');
});

it('handles missing context gracefully', function () {
    $widget = new TaxRateTrendWidget;
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
    $widget = new TaxRateTrendWidget;
    $widget->mount(['country' => 'se', 'tax_type' => 'salary']);

    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getData');
    $method->setAccessible(true);
    $data = $method->invoke($widget);

    expect($data['datasets'])->toHaveCount(1)
        ->and($data['datasets'][0]['label'])->toBe('No data available')
        ->and($data['labels'])->toBe(['No data']);
});

it('resolves context from route parameters', function () {
    $taxConfig = TaxConfiguration::factory()->create([
        'country_code' => 'no',
        'year' => 2023,
        'tax_type' => 'salary',
        'configuration' => [
            'income' => 22,
            'standardDeduction' => 5,
            'realization' => 0,
            'fortune' => 0,
        ],
    ]);

    // Simulate route parameters
    request()->setRouteResolver(function () use ($taxConfig) {
        $route = Mockery::mock(\Illuminate\Routing\Route::class);
        $route->shouldReceive('parameter')->with('country')->andReturn('no');
        $route->shouldReceive('parameter')->with('record', null)->andReturn($taxConfig);

        return $route;
    });

    $widget = new TaxRateTrendWidget;
    $widget->mount([]);

    expect($widget->getHeading())->toContain('Salary')
        ->and($widget->getHeading())->toContain('NO');
});

it('generates correct heading with tax type and country', function () {
    $widget = new TaxRateTrendWidget;
    $widget->mount(['country' => 'no', 'tax_type' => 'equity_fund']);

    $heading = $widget->getHeading();

    expect($heading)->toContain('Equity fund')
        ->and($heading)->toContain('NO');
});

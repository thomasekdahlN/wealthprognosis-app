<?php

namespace Tests\Feature;

use App\Services\Tax\TaxCashflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxCashflowServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaxCashflowService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // Seed the database with tax configurations
        $this->service = app(TaxCashflowService::class);
    }

    public function test_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(TaxCashflowService::class, $this->service);
    }

    public function test_cashflow_calculation_result_structure(): void
    {
        // Test the CashflowCalculationResult value object
        $result = new \App\Support\ValueObjects\CashflowCalculationResult(
            beforeTaxAmount: 36000.0,
            afterTaxAmount: 28000.0,
            taxAmount: 8000.0,
            taxPercent: 22.22,
            taxRate: 0.2222,
            description: 'Test calculation'
        );

        $this->assertInstanceOf(\App\Support\ValueObjects\CashflowCalculationResult::class, $result);
        $this->assertEquals(36000.0, $result->beforeTaxAmount);
        $this->assertEquals(28000.0, $result->afterTaxAmount);
        $this->assertEquals(8000.0, $result->taxAmount);
        $this->assertEquals(22.22, $result->taxPercent);
        $this->assertEquals(0.2222, $result->taxRate);
        $this->assertEquals('Test calculation', $result->description);
    }

    public function test_recalculate_cashflow_returns_correct_structure(): void
    {
        $dataH = [
            'test.2024.income.amount' => 50000,
            'test.2024.income.transferedAmount' => 1000,
            'test.2024.expence.amount' => 10000,
            'test.2024.expence.transferedAmount' => 500,
            'test.2024.mortgage.termAmount' => 5000,
            'test.2024.mortgage.extraDownpaymentAmount' => 2000,
            'test.2024.mortgage.gebyrAmount' => 600,
            'test.2024.mortgage.taxDeductableAmount' => 2000,
            'test.2024.cashflow.taxAmount' => 8000,
            'test.2024.asset.taxFortuneAmount' => 1500,
            'test.2024.asset.taxPropertyAmount' => 800,
            'test.2023.cashflow.beforeTaxAggregatedAmount' => 30000,
            'test.2023.cashflow.afterTaxAggregatedAmount' => 25000,
        ];

        $result = $this->service->recalculateCashflow($dataH, 'test.2024', 2024);

        // Verify the result structure
        $this->assertInstanceOf(\App\Support\ValueObjects\CashflowCalculationResult::class, $result);
        $this->assertIsInt($result->beforeTaxAmount);
        $this->assertIsInt($result->afterTaxAmount);
        $this->assertIsInt($result->taxAmount);

        // Verify recalculation includes extra mortgage payments
        // Before tax: income + income_transfers - expenses - expense_transfers - term - extra - gebyr
        $expectedBeforeTax = 50000 + 1000 - 10000 - 500 - 5000 - 2000 - 600; // = 31900
        $this->assertEquals($expectedBeforeTax, $result->beforeTaxAmount);
    }

    public function test_recalculate_cashflow_basic_calculation(): void
    {
        $dataH = [
            'test.2024.income.amount' => 50000,
            'test.2024.income.transferedAmount' => 0,
            'test.2024.expence.amount' => 10000,
            'test.2024.expence.transferedAmount' => 0,
            'test.2024.mortgage.termAmount' => 5000,
            'test.2024.mortgage.extraDownpaymentAmount' => 0,
            'test.2024.mortgage.gebyrAmount' => 0,
            'test.2024.mortgage.taxDeductableAmount' => 2000,
            'test.2024.cashflow.taxAmount' => 8000,
            'test.2024.asset.taxFortuneAmount' => 0,
            'test.2024.asset.taxPropertyAmount' => 0,
            'test.2023.cashflow.beforeTaxAggregatedAmount' => 30000,
            'test.2023.cashflow.afterTaxAggregatedAmount' => 25000,
        ];

        $result = $this->service->recalculateCashflow($dataH, 'test.2024', 2024);

        // Verify basic calculation: income - expenses - mortgage term
        $expectedBeforeTax = 50000 - 10000 - 5000; // = 35000
        $this->assertEquals($expectedBeforeTax, $result->beforeTaxAmount);
    }

    public function test_service_is_registered_in_container(): void
    {
        $service = app(TaxCashflowService::class);
        $this->assertInstanceOf(TaxCashflowService::class, $service);
    }
}

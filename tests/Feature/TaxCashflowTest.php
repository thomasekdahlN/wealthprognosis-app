<?php

namespace Tests\Feature;

use App\Models\TaxCashflow;
use Tests\TestCase;

class TaxCashflowTest extends TestCase
{
    public function it_loads_tax_configurations_from_json_file()
    {
        Storage::shouldReceive('disk->get')->andReturn(json_encode([
            'salary' => ['yearly' => 20],
            'pension' => ['yearly' => 20],
        ]));

        $taxCashflow = new TaxCashflow('config', 2022, 2023);

        $this->assertEquals(['salary' => ['yearly' => 20], 'pension' => ['yearly' => 20]], $taxCashflow->taxH);
    }

    public function it_throws_exception_when_config_file_not_found()
    {
        Storage::shouldReceive('disk->get')->andThrow(new \Exception('File not found'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File not found');

        new TaxCashflow('config', 2022, 2023);
    }

    public function it_handles_empty_config_file()
    {
        Storage::shouldReceive('disk->get')->andReturn(json_encode([]));

        $taxCashflow = new TaxCashflow('config', 2022, 2023);

        $this->assertEquals([], $taxCashflow->taxH);
    }

    /**
     * A basic feature test example.
     */
    public function test_it_calculates_tax_for_salary_type()
    {
        $taxCashflow = new TaxCashflow('config', 2022, 2023);
        [$cashflowTaxAmount, $cashflowTaxPercent] = $taxCashflow->taxCalculationCashflow(false, 'group', 'salary', 2022, 50000, 10000);
        $this->assertEquals(0.2, $cashflowTaxPercent);
        $this->assertEquals(10000, $cashflowTaxAmount);
    }

    public function test_it_calculates_tax_for_pension_type()
    {
        $taxCashflow = new TaxCashflow('config', 2022, 2023);
        [$cashflowTaxAmount, $cashflowTaxPercent] = $taxCashflow->taxCalculationCashflow(false, 'group', 'pension', 2022, 50000, 10000);
        $this->assertEquals(0.2, $cashflowTaxPercent);
        $this->assertEquals(10000, $cashflowTaxAmount);
    }

    public function test_it_calculates_tax_for_unknown_type()
    {
        $taxCashflow = new TaxCashflow('config', 2022, 2023);
        [$cashflowTaxAmount, $cashflowTaxPercent] = $taxCashflow->taxCalculationCashflow(false, 'group', 'unknown', 2022, 50000, 10000);
        $this->assertEquals(0.2, $cashflowTaxPercent);
        $this->assertEquals(8000, $cashflowTaxAmount);
    }

    public function test_it_calculates_tax_for_negative_income()
    {
        $taxCashflow = new TaxCashflow('config', 2022, 2023);
        [$cashflowTaxAmount, $cashflowTaxPercent] = $taxCashflow->taxCalculationCashflow(false, 'group', 'salary', 2022, -50000, 10000);
        $this->assertEquals(0.2, $cashflowTaxPercent);
        $this->assertEquals(-10000, $cashflowTaxAmount);
    }

    public function test_it_calculates_tax_for_zero_income()
    {
        $taxCashflow = new TaxCashflow('config', 2022, 2023);
        [$cashflowTaxAmount, $cashflowTaxPercent] = $taxCashflow->taxCalculationCashflow(false, 'group', 'salary', 2022, 0, 10000);
        $this->assertEquals(0.2, $cashflowTaxPercent);
        $this->assertEquals(0, $cashflowTaxAmount);
    }
}

<?php

namespace Tests\Feature;

use App\Models\TaxFortune;
use Tests\TestCase;

class TaxFortuneTest extends TestCase
{
    public function test_it_calculates_fortune_tax_for_company_group()
    {
        $taxFortune = new TaxFortune('config', 2022, 2023);
        [$taxableAmount, $taxablePercent, $taxAmount, $taxPercent, $taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $explanation] = $taxFortune->taxCalculationFortune('company', 'low', null, 2022, 50000, 10000, 0);
        $this->assertEquals(1.0, $taxablePercent);  // Company has 100% taxable
        $this->assertEquals(50000, $taxableAmount);  // Full market amount for company
    }

    public function test_it_calculates_fortune_tax_for_individual_group()
    {
        $taxFortune = new TaxFortune('config', 2022, 2023);
        [$taxableAmount, $taxablePercent, $taxAmount, $taxPercent, $taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $explanation] = $taxFortune->taxCalculationFortune('individual', 'low', null, 2022, 50000, 10000, 0);
        $this->assertEquals(0.2, $taxablePercent);
        $this->assertEquals(10000, $taxableAmount);
    }

    public function test_it_calculates_fortune_tax_for_unknown_group()
    {
        $taxFortune = new TaxFortune('config', 2022, 2023);
        [$taxableAmount, $taxablePercent, $taxAmount, $taxPercent, $taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $explanation] = $taxFortune->taxCalculationFortune('unknown', 'low', null, 2022, 50000, 10000, 0);
        $this->assertEquals(0.2, $taxablePercent);
        $this->assertEquals(10000, $taxableAmount);
    }

    public function test_it_calculates_fortune_tax_for_negative_income()
    {
        $taxFortune = new TaxFortune('config', 2022, 2023);
        [$taxableAmount, $taxablePercent, $taxAmount, $taxPercent, $taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $explanation] = $taxFortune->taxCalculationFortune('company', 'low', null, 2022, -50000, 10000, 0);
        $this->assertEquals(1.0, $taxablePercent);  // Company has 100% taxable
        $this->assertEquals(-50000, $taxableAmount);  // Full negative market amount for company
    }

    public function test_it_calculates_fortune_tax_for_zero_income()
    {
        $taxFortune = new TaxFortune('config', 2022, 2023);
        [$taxableAmount, $taxablePercent, $taxAmount, $taxPercent, $taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $explanation] = $taxFortune->taxCalculationFortune('company', 'low', null, 2022, 0, 10000, 0);
        $this->assertEquals(1.0, $taxablePercent);  // Company has 100% taxable
        $this->assertEquals(0, $taxableAmount);  // Zero market amount
    }
}

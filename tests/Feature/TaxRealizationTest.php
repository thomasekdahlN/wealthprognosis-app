<?php

namespace Tests\Feature;

use App\Models\TaxRealization;
use Tests\TestCase;

class TaxRealizationTest extends TestCase
{
    public function test_it_calculates_tax_realization_for_company_group()
    {
        $taxRealization = new TaxRealization('config', 2022, 2023);
        [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldPercent, $explanation] = $taxRealization->taxCalculationRealization(false, false, 'company', 'stock', 2022, 50000, 10000, 0, 0);
        $this->assertEquals(0.22, $realizationTaxPercent);
        $this->assertEquals(0, $realizationTaxAmount);
    }

    public function test_it_calculates_tax_realization_for_individual_group()
    {
        $taxRealization = new TaxRealization('config', 2022, 2023);
        [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldPercent, $explanation] = $taxRealization->taxCalculationRealization(false, false, 'individual', 'stock', 2022, 50000, 10000, 0, 0);
        $this->assertEquals(0.22, $realizationTaxPercent);
        $this->assertEquals(8800, $realizationTaxAmount);
    }

    public function test_it_calculates_tax_realization_for_unknown_group()
    {
        $taxRealization = new TaxRealization('config', 2022, 2023);
        [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldPercent, $explanation] = $taxRealization->taxCalculationRealization(false, false, 'unknown', 'stock', 2022, 50000, 10000, 0, 0);
        $this->assertEquals(0.22, $realizationTaxPercent);
        $this->assertEquals(8800, $realizationTaxAmount);
    }

    public function test_it_calculates_tax_realization_for_negative_income()
    {
        $taxRealization = new TaxRealization('config', 2022, 2023);
        [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldPercent, $explanation] = $taxRealization->taxCalculationRealization(false, false, 'company', 'stock', 2022, -50000, 10000, 0, 0);
        $this->assertEquals(0.22, $realizationTaxPercent);
        $this->assertEquals(0, $realizationTaxAmount);
    }

    public function test_it_calculates_tax_realization_for_zero_income()
    {
        $taxRealization = new TaxRealization('config', 2022, 2023);
        [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldPercent, $explanation] = $taxRealization->taxCalculationRealization(false, false, 'company', 'stock', 2022, 0, 10000, 0, 0);
        $this->assertEquals(0.22, $realizationTaxPercent);
        $this->assertEquals(0, $realizationTaxAmount);
    }
}

<?php

namespace Tests\Feature;

use App\Services\Tax\TaxRealization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxRealizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed asset types for tax shield functionality
        $this->seed(\Database\Seeders\TaxTypesFromConfigSeeder::class);
        $this->seed(\Database\Seeders\AssetTypeSeeder::class);
    }

    public function test_it_calculates_tax_realization_for_company_group()
    {
        $taxRealization = new TaxRealization('no');
        [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldPercent, $explanation] = $taxRealization->taxCalculationRealization(false, false, 'company', 'stock', 2022, 50000, 10000, 0, 0);
        $repo = new \App\Services\Tax\TaxConfigRepository('no');
        $expectedRate = $repo->getTaxRealizationRate('stock', 2022);
        $this->assertEquals($expectedRate, $realizationTaxPercent);
        $this->assertEquals(0, $realizationTaxAmount);
    }

    public function test_it_calculates_tax_realization_for_individual_group()
    {
        $taxRealization = new TaxRealization('no');
        [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldPercent, $explanation] = $taxRealization->taxCalculationRealization(false, false, 'individual', 'stock', 2022, 50000, 10000, 0, 0);
        $repo = new \App\Services\Tax\TaxConfigRepository('no');
        $expectedRate = $repo->getTaxRealizationRate('stock', 2022);
        $this->assertEquals($expectedRate, $realizationTaxPercent);
        $this->assertEquals(round((50000 - 10000) * $expectedRate), $realizationTaxAmount);
    }

    public function test_it_calculates_tax_realization_for_unknown_group()
    {
        $taxRealization = new TaxRealization('no');
        [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldPercent, $explanation] = $taxRealization->taxCalculationRealization(false, false, 'unknown', 'stock', 2022, 50000, 10000, 0, 0);
        $repo = new \App\Services\Tax\TaxConfigRepository('no');
        $expectedRate = $repo->getTaxRealizationRate('stock', 2022);
        $this->assertEquals($expectedRate, $realizationTaxPercent);
        $this->assertEquals(round((50000 - 10000) * $expectedRate), $realizationTaxAmount);
    }

    public function test_it_calculates_tax_realization_for_negative_income()
    {
        $taxRealization = new TaxRealization('no');
        [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldPercent, $explanation] = $taxRealization->taxCalculationRealization(false, false, 'company', 'stock', 2022, -50000, 10000, 0, 0);
        $repo = new \App\Services\Tax\TaxConfigRepository('no');
        $expectedRate = $repo->getTaxRealizationRate('stock', 2022);
        $this->assertEquals($expectedRate, $realizationTaxPercent);
        $this->assertEquals(0, $realizationTaxAmount);
    }

    public function test_it_calculates_tax_realization_for_zero_income()
    {
        $taxRealization = new TaxRealization('no');
        [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldPercent, $explanation] = $taxRealization->taxCalculationRealization(false, false, 'company', 'stock', 2022, 0, 10000, 0, 0);
        $repo = new \App\Services\Tax\TaxConfigRepository('no');
        $expectedRate = $repo->getTaxRealizationRate('stock', 2022);
        $this->assertEquals($expectedRate, $realizationTaxPercent);
        $this->assertEquals(0, $realizationTaxAmount);
    }
}

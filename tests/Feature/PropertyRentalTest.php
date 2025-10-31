<?php

namespace Tests\Feature;

use App\Services\Tax\TaxPropertyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyRentalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed tax property data
        $this->seed(\Database\Seeders\TaxPropertySeeder::class);
    }

    public function test_it_calculates_property_tax_for_ringerike_rental_private(): void
    {
        // Test data based on Ringerike 2025:
        // - tax_home_permill: 2.4 (0.24%)
        // - deduction: 400,000 NOK
        // - taxable_percent: 70.00 (70% of market value is taxable)
        // - market value: 3,000,000 NOK

        $taxPropertyService = new TaxPropertyService('no');
        $result = $taxPropertyService->calculatePropertyTax(
            year: 2025,
            taxGroup: 'private',
            taxPropertyArea: 'ringerike',
            amount: 3000000
        );

        // Expected calculation (following Norwegian property tax rules):
        // 1. Market value: 3,000,000 kr
        // 2. Reduction (70% of market value): 2,100,000 kr (taxable portion)
        // 3. Deduction (bunnfradrag): -400,000 kr
        // 4. Tax base (skattegrunnlag): 1,700,000 kr
        // 5. Tax rate (skattesats): × 2.4‰ (× 0.0024)
        // 6. Property tax: 4,080 kr

        $this->assertEquals('ringerike', $result->taxPropertyArea); // Municipality code
        $this->assertEquals(1700000, $result->taxablePropertyAmount); // 70% of 3,000,000
        $this->assertEquals(70.00, $result->taxablePropertyPercent); // 70%
        $this->assertEquals(0.70, $result->taxablePropertyRate); // 70% as decimal
        $this->assertEquals(400000, $result->taxPropertyDeductionAmount); // Deduction (bunnfradrag)
        $this->assertEquals(0.0024, $result->taxPropertyRate); // 2.4 permille
        $this->assertEquals(0.24, $result->taxPropertyPercent); // 0.24%
        $this->assertEquals(4080, $result->taxPropertyAmount); // (2,100,000 - 400,000) × 0.0024
        $this->assertStringContainsString('Property tax:', $result->explanation);
    }

    public function test_it_calculates_property_tax_for_ringerike_rental_company(): void
    {
        // Test data based on Ringerike 2025:
        // - tax_company_permill: 3.7 (0.37%)
        // - deduction: 400,000 NOK
        // - taxable_percent: 70.00 (70% of market value is taxable)
        // - market value: 3,000,000 NOK

        $taxPropertyService = new TaxPropertyService('no');
        $result = $taxPropertyService->calculatePropertyTax(
            year: 2025,
            taxGroup: 'company',
            taxPropertyArea: 'ringerike',
            amount: 3000000.0
        );

        // Expected calculation (following Norwegian property tax rules):
        // 1. Market value: 3,000,000 kr
        // 2. Reduction (70% of market value): 2,100,000 kr (taxable portion)
        // 3. Deduction (bunnfradrag): -400,000 kr
        // 4. Tax base (skattegrunnlag): 1,700,000 kr
        // 5. Tax rate (skattesats): × 3.7‰ (× 0.0037)
        // 6. Property tax: 6,290 kr

        $this->assertEquals('ringerike', $result->taxPropertyArea); // Municipality code
        $this->assertEquals(1700000, $result->taxablePropertyAmount); // 70% of 3,000,000
        $this->assertEquals(70.00, $result->taxablePropertyPercent); // 70%
        $this->assertEquals(0.70, $result->taxablePropertyRate); // 70% as decimal
        $this->assertEquals(400000, $result->taxPropertyDeductionAmount); // Deduction (bunnfradrag)
        $this->assertEquals(0.0037, $result->taxPropertyRate); // 3.7 permille
        $this->assertEquals(0.37, $result->taxPropertyPercent); // 0.37%
        $this->assertEquals(6290, $result->taxPropertyAmount); // (2,100,000 - 400,000) × 0.0037
        $this->assertStringContainsString('Property tax:', $result->explanation);
    }

    public function test_it_handles_property_value_below_deduction(): void
    {
        // Test with property value below deduction threshold
        // Market value: 300,000 kr
        // 70% of 300,000 = 210,000 kr (taxable portion)
        // 210,000 - 400,000 deduction = 0 (below deduction)
        $taxPropertyService = new TaxPropertyService('no');
        $result = $taxPropertyService->calculatePropertyTax(
            year: 2025,
            taxGroup: 'private',
            taxPropertyArea: 'ringerike',
            amount: 300000.0 // Below 400,000 deduction after 70% reduction
        );

        // Expected: No tax because taxable amount (210,000) is below deduction (400,000)
        $this->assertEquals('ringerike', $result->taxPropertyArea); // Municipality code
        // After deduction, the taxable base should be 0 since 70% of 300,000 (210,000) is below the 400,000 deduction
        $this->assertEquals(0, $result->taxablePropertyAmount);
        $this->assertEquals(400000, $result->taxPropertyDeductionAmount); // Deduction (bunnfradrag)
        $this->assertEquals(0, $result->taxPropertyAmount); // No tax
        $this->assertStringContainsString('No property tax', $result->explanation);
    }

    public function test_it_handles_exact_deduction_amount(): void
    {
        // Test with property value where taxable amount exactly equals deduction
        // To have taxable amount = 400,000, we need market value = 400,000 / 0.70 = 571,428.57
        $taxPropertyService = new TaxPropertyService('no');
        $result = $taxPropertyService->calculatePropertyTax(
            year: 2025,
            taxGroup: 'private',
            taxPropertyArea: 'ringerike',
            amount: 571428.57 // 70% of this ≈ 400,000 (approximately the deduction)
        );

        // Expected: No tax because taxable base is 0 after deduction
        $this->assertEquals('ringerike', $result->taxPropertyArea); // Municipality code
        // After applying the 400,000 deduction, the taxable base is 0
        $this->assertEquals(0, $result->taxablePropertyAmount);
        $this->assertEquals(400000, $result->taxPropertyDeductionAmount); // Deduction (bunnfradrag)
        $this->assertEquals(0, $result->taxPropertyAmount); // No tax after deduction
    }

    public function test_tax_property_model_has_taxable_percent_field(): void
    {
        // Verify that the TaxProperty model has the taxable_percent field (70% for residential properties in Norway)
        $taxProperty = \App\Models\TaxProperty::where('code', 'ringerike')
            ->where('country_code', 'no')
            ->where('year', 2025)
            ->first();

        $this->assertNotNull($taxProperty);
        $this->assertNotNull($taxProperty->taxable_percent);
        $this->assertEquals(70.00, $taxProperty->taxable_percent);
    }
}

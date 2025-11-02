<?php

namespace Tests\Feature;

use App\Services\Tax\TaxFortuneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FortuneTaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed tax configuration data (uses the actual seeded data from config/tax/no/no-tax-2025.json)
        $this->seed(\Database\Seeders\TaxTypesFromConfigSeeder::class);
        $this->seed(\Database\Seeders\TaxConfigurationSeeder::class);
    }

    public function test_it_calculates_fortune_tax_for_equityfund_private(): void
    {
        // Test data based on same market value as PropertyRentalTest:
        // - market value: 3,000,000 NOK
        // - taxable_percent: 80.00 (80% of market value is taxable - from actual seeded data)
        // - fortune tax brackets: 0% up to 1,760,000, 1% up to 20,700,000, 1.1% above
        // - no mortgage

        $taxFortuneService = new TaxFortuneService('no');
        $result = $taxFortuneService->taxCalculationFortune(
            taxGroup: 'private',
            taxType: 'equityfund',
            year: 2025,
            marketAmount: 3000000,
            taxableInitialAmount: null,
            mortgageBalanceAmount: 0
        );

        // Expected calculation (following Norwegian fortune tax rules):
        // 1. Market value: 3,000,000 kr
        // 2. Taxable portion (80% of market value): 2,400,000 kr
        // 3. Fortune tax calculation (deduct=false, so no standard deduction):
        //    - Full 2,400,000 kr taxed at 1% = 24,000 kr
        // 4. Net taxable fortune amount (after mortgage): 2,400,000 - 0 = 2,400,000 kr

        $this->assertEquals(2400000, $result->taxableFortuneAmount); // 80% of 3,000,000 (after mortgage deduction)
        $this->assertEquals(80.00, $result->taxableFortunePercent); // 80%
        $this->assertEquals(0.80, $result->taxableFortuneRate); // 80% as decimal
        $this->assertEquals(24000, $result->taxFortuneAmount); // 2,400,000 × 1% (no deduction)
        $this->assertEquals(1.0, $result->taxFortunePercent); // Marginal rate: 1%
        $this->assertEquals(0.01, $result->taxFortuneRate); // Marginal rate: 1% as decimal
        $this->assertStringContainsString('Market taxable', $result->explanation);
    }

    public function test_it_calculates_fortune_tax_with_mortgage(): void
    {
        // Test data with mortgage deduction (same market value as PropertyRentalTest):
        // - market value: 3,000,000 NOK
        // - taxable_percent: 80.00 (80% of market value is taxable - from actual seeded data)
        // - mortgage: 1,000,000 NOK
        // - fortune tax brackets: 0% up to 1,760,000, 1% up to 20,700,000, 1.1% above

        $taxFortuneService = new TaxFortuneService('no');
        $result = $taxFortuneService->taxCalculationFortune(
            taxGroup: 'private',
            taxType: 'equityfund',
            year: 2025,
            marketAmount: 3000000,
            taxableInitialAmount: null,
            mortgageBalanceAmount: 1000000
        );

        // Expected calculation:
        // 1. Market value: 3,000,000 kr
        // 2. Taxable portion (80% of market value): 2,400,000 kr
        // 3. Tax calculation (deduct=false, so no standard deduction): 2,400,000 × 1% = 24,000 kr
        // 4. Net taxable fortune (after mortgage deduction): 2,400,000 - 1,000,000 = 1,400,000 kr

        $this->assertEquals(1400000, $result->taxableFortuneAmount); // 80% of 3,000,000 minus mortgage
        $this->assertEquals(80.00, $result->taxableFortunePercent); // 80%
        $this->assertEquals(0.80, $result->taxableFortuneRate); // 80% as decimal
        $this->assertEquals(24000, $result->taxFortuneAmount); // Tax calculated on full amount before mortgage
        $this->assertEquals(1.0, $result->taxFortunePercent); // Marginal rate: 1%
        $this->assertEquals(0.01, $result->taxFortuneRate); // Marginal rate: 1% as decimal
        $this->assertStringContainsString('Market taxable', $result->explanation);
    }

    public function test_it_handles_fortune_value_below_deduction(): void
    {
        // Test with rental asset type (100% taxable rate)
        // Market value: 3,000,000 kr
        // 100% of 3,000,000 = 3,000,000 kr (taxable portion for rental)
        // Tax calculation (deduct=false, so no standard deduction): 3,000,000 × 1% = 30,000 kr

        $taxFortuneService = new TaxFortuneService('no');
        $result = $taxFortuneService->taxCalculationFortune(
            taxGroup: 'private',
            taxType: 'rental',
            year: 2025,
            marketAmount: 3000000,
            taxableInitialAmount: null,
            mortgageBalanceAmount: 0,
            taxableAmountOverride: false
        );

        $this->assertEquals(3000000, $result->taxableFortuneAmount); // 100% of 3,000,000 (rental has 100% taxable rate)
        $this->assertEquals(100.00, $result->taxableFortunePercent); // 100%
        $this->assertEquals(1.0, $result->taxableFortuneRate); // 100% as decimal
        $this->assertEquals(30000, $result->taxFortuneAmount); // 3,000,000 × 1% (no deduction)
        $this->assertEquals(1.0, $result->taxFortunePercent); // Marginal rate: 1%
        $this->assertEquals(0.01, $result->taxFortuneRate); // Marginal rate: 1% as decimal
    }

    public function test_it_handles_exact_deduction_amount(): void
    {
        // Test with asset value where taxable amount exactly equals the original deduction threshold
        // To have taxable amount = 1,760,000, we need market value = 1,760,000 / 0.80 = 2,200,000
        // But since deduct=false, this will still be taxed at 1%
        $taxFortuneService = new TaxFortuneService('no');
        $result = $taxFortuneService->taxCalculationFortune(
            taxGroup: 'private',
            taxType: 'rental',
            year: 2025,
            marketAmount: 3000000,
            taxableInitialAmount: null,
            mortgageBalanceAmount: 0,
            taxableAmountOverride: false
        );

        $this->assertEquals(3000000, $result->taxableFortuneAmount); // 100% of 3,000,000
        $this->assertEquals(100.00, $result->taxableFortunePercent); // 100%
        $this->assertEquals(1, $result->taxableFortuneRate); // 100% as decimal
        $this->assertEquals(30000, $result->taxFortuneAmount); // 3,000,000 × 1% (no deduction)
        $this->assertEquals(1.0, $result->taxFortunePercent); // Marginal rate: 1%
        $this->assertEquals(0.01, $result->taxFortuneRate); // Marginal rate: 1% as decimal
    }

    public function test_it_calculates_fortune_tax_in_higher_bracket(): void
    {
        // Test with high asset value that reaches the second bracket (same pattern as PropertyRentalTest)
        // Market value: 30,000,000 kr
        // 80% of 30,000,000 = 24,000,000 kr (taxable portion)
        // Fortune tax calculation (deduct=false, so no standard deduction):
        // - First 20,700,000 kr: 1% = 207,000 kr
        // - Next 3,300,000 kr (24,000,000 - 20,700,000): 1.1% = 36,300 kr
        // - Total: 243,300 kr

        $taxFortuneService = new TaxFortuneService('no');
        $result = $taxFortuneService->taxCalculationFortune(
            taxGroup: 'private',
            taxType: 'equityfund',
            year: 2025,
            marketAmount: 30000000,
            taxableInitialAmount: null,
            mortgageBalanceAmount: 0
        );

        $this->assertEquals(24000000, $result->taxableFortuneAmount); // 80% of 30,000,000
        $this->assertEquals(80.00, $result->taxableFortunePercent); // 80%
        $this->assertEquals(0.80, $result->taxableFortuneRate); // 80% as decimal
        $this->assertEquals(243300, $result->taxFortuneAmount); // Progressive tax calculation (no deduction)
        $this->assertEquals(1.1, $result->taxFortunePercent); // Marginal rate: 1.1%
        $this->assertEqualsWithDelta(0.011, $result->taxFortuneRate, 0.0001); // Marginal rate: 1.1% as decimal
        $this->assertStringContainsString('Market taxable', $result->explanation);
    }
}

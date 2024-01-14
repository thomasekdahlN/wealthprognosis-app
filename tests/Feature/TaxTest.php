<?php

namespace Tests\Feature;
use App\Models\Tax;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaxTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function testGetTaxYearlyReturnsCorrectValue()
    {
        Storage::disk('local')->put('tax/config.json', json_encode(['income' => ['yearly' => 22]]));
        $tax = new Tax('config', 2022, 2023);

        $this->assertEquals(0.22, $tax->getTaxYearly('income', 2022));
    }

    public function testGetTaxYearlyReturnsZeroForNonExistingType()
    {
        Storage::disk('local')->put('tax/config.json', json_encode(['income' => ['yearly' => 22]]));
        $tax = new Tax('config', 2022, 2023);

        $this->assertEquals(0, $tax->getTaxYearly('nonExistingType', 2022));
    }

    public function testGetTaxRealizationReturnsCorrectValue()
    {
        Storage::disk('local')->put('tax/config.json', json_encode(['income' => ['realization' => 22]]));
        $tax = new Tax('config', 2022, 2023);

        $this->assertEquals(0.22, $tax->getTaxRealization('income', 2022));
    }

    public function testGetTaxRealizationReturnsZeroForNonExistingType()
    {
        Storage::disk('local')->put('tax/config.json', json_encode(['income' => ['realization' => 22]]));
        $tax = new Tax('config', 2022, 2023);

        $this->assertEquals(0, $tax->getTaxRealization('nonExistingType', 2022));
    }

    public function testGetTaxShieldRealizationReturnsCorrectValueForTaxShieldType()
    {
        Storage::disk('local')->put('tax/config.json', json_encode(['shareholdershield' => ['2022' => 22]]));
        $tax = new Tax('config', 2022, 2023);

        $this->assertEquals(0.22, $tax->getTaxShieldRealization('stock', 2022));
    }

    public function testGetTaxShieldRealizationReturnsZeroForNonTaxShieldType()
    {
        Storage::disk('local')->put('tax/config.json', json_encode(['shareholdershield' => ['2022' => 22]]));
        $tax = new Tax('config', 2022, 2023);

        $this->assertEquals(0, $tax->getTaxShieldRealization('nonTaxShieldType', 2022));
    }

    public function testGetTaxableFortuneReturnsCorrectValue()
    {
        Storage::disk('local')->put('tax/config.json', json_encode(['income' => ['fortune' => 22]]));
        $tax = new Tax('config', 2022, 2023);

        $this->assertEquals(0.22, $tax->getTaxableFortune('income', 2022));
    }

    public function testGetTaxableFortuneReturnsZeroForNonExistingType()
    {
        Storage::disk('local')->put('tax/config.json', json_encode(['income' => ['fortune' => 22]]));
        $tax = new Tax('config', 2022, 2023);

        $this->assertEquals(0, $tax->getTaxableFortune('nonExistingType', 2022));
    }

    public function testGetFortuneTaxReturnsCorrectValue()
    {
        Storage::disk('local')->put('tax/config.json', json_encode(['fortune' => ['yearly' => 22]]));
        $tax = new Tax('config', 2022, 2023);

        $this->assertEquals(0.22, $tax->getFortuneTax(2022));
    }

    public function testGetFortuneTaxStandardDeductionReturnsCorrectValue()
    {
        Storage::disk('local')->put('tax/config.json', json_encode(['fortune' => ['standardDeduction' => 10000]]));
        $tax = new Tax('config', 2022, 2023);

        $this->assertEquals(10000, $tax->getFortuneTaxStandardDeduction(2022));
    }

    public function testGetPropertyTaxableReturnsCorrectValue()
    {
        Storage::disk('local')->put('tax/config.json', json_encode(['property' => ['fortune' => 22]]));
        $tax = new Tax('config', 2022, 2023);

        $this->assertEquals(0.22, $tax->getPropertyTaxable(2022));
    }

    public function testGetPropertyTaxStandardDeductionReturnsCorrectValue()
    {
        Storage::disk('local')->put('tax/config.json', json_encode(['property' => ['standardDeduction' => 10000]]));
        $tax = new Tax('config', 2022, 2023);

        $this->assertEquals(10000, $tax->getPropertyTaxStandardDeduction(2022));
    }

    public function testGetPropertyTaxReturnsCorrectValue()
    {
        Storage::disk('local')->put('tax/config.json', json_encode(['property' => ['yearly' => 22]]));
        $tax = new Tax('config', 2022, 2023);

        $this->assertEquals(0.22, $tax->getPropertyTax(2022));
    }

    public function taxCalculationRealizationHandlesSalaryTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'salary', 2022, 10000);

        $this->assertEquals([0, 0, 0, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesPensionTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'pension', 2022, 10000);

        $this->assertEquals([0, 0, 0, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesIncomeTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'income', 2022, 10000);

        $this->assertEquals([0, 0, 0, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesHouseTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'house', 2022, 10000);

        $this->assertEquals([0, 0, 0, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesCabinTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'cabin', 2022, 10000);

        $this->assertEquals([0, 0, 0, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesPropertyTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'property', 2022, 10000, 5000);

        $this->assertEquals([5000, 0, -5000, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesRentalTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'rental', 2022, 10000, 5000);

        $this->assertEquals([5000, 0, -5000, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesStockTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'stock', 2022, 10000, 5000);

        $this->assertEquals([5000, 0, -5000, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesBondFundTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'bondfund', 2022, 10000, 5000);

        $this->assertEquals([5000, 0, -5000, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesEquityFundTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'equityfund', 2022, 10000, 5000);

        $this->assertEquals([5000, 0, -5000, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesAskTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'ask', 2022, 10000, 5000);

        $this->assertEquals([5000, 0, -5000, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesOtpTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'otp', 2022, 10000, 5000);

        $this->assertEquals([5000, 0, -5000, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesIpsTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'ips', 2022, 10000, 5000);

        $this->assertEquals([5000, 0, -5000, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesCryptoTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'crypto', 2022, 10000, 5000);

        $this->assertEquals([5000, 0, -5000, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesGoldTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'gold', 2022, 10000, 5000);

        $this->assertEquals([5000, 0, -5000, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesBankTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'bank', 2022, 10000, 5000);

        $this->assertEquals([5000, 0, -5000, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesCashTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'cash', 2022, 10000, 5000);

        $this->assertEquals([5000, 0, -5000, 0, 0, 0], $result);
    }

    public function taxCalculationRealizationHandlesUnknownTypeCorrectly()
    {
        $tax = new Tax('config', 2022, 2023);
        $result = $tax->taxCalculationRealization(false, 'unknown', 2022, 10000, 5000);

        $this->assertEquals([5000, 0, -5000, 0, 0, 0], $result);
    }
}

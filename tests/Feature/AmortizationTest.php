<?php

namespace Tests\Feature;

use App\Models\TaxConfiguration;
use App\Models\TaxType;
use App\Models\Team;
use App\Models\User;
use App\Services\Prognosis\AmortizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmortizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user and team for tax configurations
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        // Seed tax types needed for tests
        TaxType::create(['type' => 'rental', 'name' => 'Rental Property']);
        TaxType::create(['type' => 'house', 'name' => 'House']);
        TaxType::create(['type' => 'none', 'name' => 'None']);

        // Create tax configurations for testing (Norway 2020-2025)
        $years = [2020, 2021, 2022, 2023, 2024, 2025];
        foreach ($years as $year) {
            // Rental property - 22% income tax
            TaxConfiguration::create([
                'country_code' => 'no',
                'year' => $year,
                'tax_type' => 'rental',
                'is_active' => true,
                'description' => 'Rental Property',
                'configuration' => [
                    'income' => 22,
                    'realization' => 22,
                    'fortune' => 100,
                ],
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => hash('sha256', "rental_{$year}"),
                'updated_checksum' => hash('sha256', "rental_{$year}"),
            ]);

            // House - 0% income tax
            TaxConfiguration::create([
                'country_code' => 'no',
                'year' => $year,
                'tax_type' => 'house',
                'is_active' => true,
                'description' => 'House',
                'configuration' => [
                    'income' => 0,
                    'realization' => 0,
                    'fortune' => 25,
                ],
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => hash('sha256', "house_{$year}"),
                'updated_checksum' => hash('sha256', "house_{$year}"),
            ]);

            // None - 0% income tax
            TaxConfiguration::create([
                'country_code' => 'no',
                'year' => $year,
                'tax_type' => 'none',
                'is_active' => true,
                'description' => 'None',
                'configuration' => [
                    'income' => 0,
                    'realization' => 0,
                    'fortune' => 0,
                ],
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => hash('sha256', "none_{$year}"),
                'updated_checksum' => hash('sha256', "none_{$year}"),
            ]);
        }
    }

    /**
     * Test the Amortization class with realistic mortgage values
     */
    public function test_amortization()
    {
        // Mock changerate service that returns 5% interest
        $changerate = new class
        {
            public function getChangerate()
            {
                return [5, 0.05, null, ''];
            }
        };

        $config = [
            'rental_property' => [
                'meta' => [
                    'tax_type' => 'rental',
                    'taxCountry' => 'no',
                ],
            ],
        ];

        $dataH = [];

        // Realistic mortgage: 2,000,000 NOK over 20 years at 5% interest
        $mortgages = [
            'years' => 20,
            'amount' => 2000000,
        ];
        $assettname = 'rental_property';

        // Instantiate the Amortization class (starting year 2021)
        $amortization = new AmortizationService(false, $config, $changerate, $dataH, $mortgages, $assettname, 2021);

        // Call the get method
        $dataH = $amortization->get();

        // Assert that the result is an array
        $this->assertIsArray($dataH);

        // Assert mortgage data exists
        $this->assertArrayHasKey('rental_property', $dataH);
        $this->assertArrayHasKey(2021, $dataH['rental_property']);
        $this->assertArrayHasKey('mortgage', $dataH['rental_property'][2021]);

        // Assert tax deduction is calculated (22% for rental property)
        $mortgage = $dataH['rental_property'][2021]['mortgage'];
        $this->assertEquals(0.22, $mortgage['taxDeductableRate']);
        $this->assertEquals(22, $mortgage['taxDeductablePercent']);
        $this->assertGreaterThan(0, $mortgage['taxDeductableAmount']);

        // Verify the tax deductable amount is 22% of interest
        $expectedTaxDeduction = $mortgage['interestAmount'] * 0.22;
        $this->assertEqualsWithDelta($expectedTaxDeduction, $mortgage['taxDeductableAmount'], 0.01);

        // Verify realistic mortgage calculations
        $this->assertEquals(2000000, $mortgage['amount']);
        $this->assertEqualsWithDelta(160485, $mortgage['termAmount'], 10);
        $this->assertEqualsWithDelta(100000, $mortgage['interestAmount'], 10);
    }

    public function test_it_calculates_amortization_schedule_with_positive_balance()
    {
        $changerate = new class
        {
            public function getChangerate()
            {
                return [5, 0.05, null, ''];
            }
        };

        $config = [
            'rental_property' => [
                'meta' => [
                    'tax_type' => 'rental',
                    'taxCountry' => 'no',
                ],
            ],
        ];

        // Realistic mortgage: 2,000,000 NOK over 20 years at 5% interest
        $amortization = new AmortizationService(false, $config, $changerate, [], ['amount' => 2000000, 'years' => 20, 'interest' => '5%'], 'rental_property', 2022);
        $data = $amortization->get();

        $this->assertEquals(2000000, $data['rental_property'][2022]['mortgage']['amount']);
        // Annual payment for 2M over 20 years at 5% is approximately 160,485
        $this->assertEqualsWithDelta(160485, $data['rental_property'][2022]['mortgage']['termAmount'], 10);

        // Assert tax deduction is calculated (22% for rental property)
        $this->assertEquals(0.22, $data['rental_property'][2022]['mortgage']['taxDeductableRate']);
        $this->assertEquals(22, $data['rental_property'][2022]['mortgage']['taxDeductablePercent']);
        $this->assertGreaterThan(0, $data['rental_property'][2022]['mortgage']['taxDeductableAmount']);
    }

    public function test_it_calculates_amortization_schedule_with_zero_balance()
    {
        $changerate = new class
        {
            public function getChangerate()
            {
                return [5, 0.05, null, ''];
            }
        };

        $config = [
            'house' => [
                'meta' => [
                    'tax_type' => 'house',
                    'taxCountry' => 'no',
                ],
            ],
        ];

        $amortization = new AmortizationService(false, $config, $changerate, [], ['amount' => 0, 'years' => 20, 'interest' => '5%'], 'house', 2022);
        $data = $amortization->get();
        $this->assertEmpty($data); // When amount is 0, no data is generated
    }

    public function test_it_calculates_amortization_schedule_with_interest_only_years()
    {
        $changerate = new class
        {
            public function getChangerate()
            {
                return [5, 0.05, null, ''];
            }
        };

        $config = [
            'rental_property' => [
                'meta' => [
                    'tax_type' => 'rental',
                    'taxCountry' => 'no',
                ],
            ],
        ];

        // Realistic mortgage: 2,000,000 NOK over 20 years at 5% interest with 5 interest-only years
        $amortization = new AmortizationService(false, $config, $changerate, [], ['amount' => 2000000, 'years' => 20, 'interest' => '5%', 'interestOnlyYears' => 5], 'rental_property', 2022);
        $data = $amortization->get();

        // During interest-only years, termAmount should equal interestAmount (no principal payment)
        $this->assertEqualsWithDelta(100000, $data['rental_property'][2022]['mortgage']['termAmount'], 10);
        $this->assertEqualsWithDelta(100000, $data['rental_property'][2022]['mortgage']['interestAmount'], 10);

        // Assert tax deduction is calculated
        $this->assertEquals(0.22, $data['rental_property'][2022]['mortgage']['taxDeductableRate']);
    }

    public function test_it_calculates_amortization_schedule_with_extra_downpayment()
    {
        $changerate = new class
        {
            public function getChangerate()
            {
                return [5, 0.05, null, ''];
            }
        };

        $config = [
            'rental_property' => [
                'meta' => [
                    'tax_type' => 'rental',
                    'taxCountry' => 'no',
                ],
            ],
        ];

        // Realistic mortgage: 2,000,000 NOK over 20 years at 5% interest with 100,000 extra downpayment
        $amortization = new AmortizationService(false, $config, $changerate, [], ['amount' => 2000000, 'years' => 20, 'interest' => '5%', 'extraDownpaymentAmount' => 100000], 'rental_property', 2022);
        $data = $amortization->get();

        // Balance should be reduced by extra downpayment plus principal
        $this->assertLessThan(2000000, $data['rental_property'][2022]['mortgage']['balanceAmount']);
        $this->assertEquals(100000, $data['rental_property'][2022]['mortgage']['extraDownpaymentAmount']);

        // Assert tax deduction is calculated
        $this->assertEquals(0.22, $data['rental_property'][2022]['mortgage']['taxDeductableRate']);
    }

    public function test_it_handles_negative_interest_without_exception()
    {
        $changerate = new class
        {
            public function getChangerate()
            {
                return [-1, -0.01, null, ''];
            }
        };

        $config = [
            'house' => [
                'meta' => [
                    'tax_type' => 'house',
                    'taxCountry' => 'no',
                ],
            ],
        ];

        // Realistic mortgage with negative interest (edge case)
        $amortization = new AmortizationService(false, $config, $changerate, [], ['amount' => 2000000, 'years' => 20, 'interest' => '-1%'], 'house', 2022);
        $data = $amortization->get();

        // Should handle negative interest without throwing exception
        $this->assertIsArray($data);

        // Assert tax deduction is calculated (0% for house)
        if (isset($data['house'][2022]['mortgage'])) {
            $this->assertEquals(0.0, $data['house'][2022]['mortgage']['taxDeductableRate']);
        }
    }
}

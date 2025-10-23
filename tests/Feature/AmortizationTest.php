<?php

namespace Tests\Feature;

use App\Models\Core\Amortization;
use App\Models\Core\Changerate;
use Tests\TestCase;

class AmortizationTest extends TestCase
{
    /**
     * Test the Amortization class
     *
     * @return void
     */
    public function test_amortization()
    {
        // Define the config, changerate, dataH, mortgages, and assetname

        $changerate = new \App\Models\Core\Changerate('tenpercent');

        $config = [
            'test' => [
                'mortgage' => [
                    '2020' => [
                        'interest' => 5,
                        'terms' => 1,
                        'currency' => 'NOK',
                        'period' => 10,
                    ],
                ],
            ],
        ];

        $dataH = [];

        $mortgages = [
            'years' => 10,
            'amount' => 1000000,
        ];
        $assettname = 'test';

        // Instantiate the Amortization class
        $amortization = new Amortization(false, $config, $changerate, $dataH, $mortgages, $assettname, 2020);

        // Call the getSchedule method
        $dataH = $amortization->get();

        // Assert that the summary is an array
        $this->assertIsArray($dataH);
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

        $amortization = new Amortization(false, [], $changerate, [], ['amount' => 100000, 'years' => 10, 'interest' => '5%'], 'asset', 2022);
        $data = $amortization->get();
        $this->assertEquals(100000, $data['asset'][2022]['mortgage']['amount']);
        $this->assertEquals(12950, $data['asset'][2022]['mortgage']['termAmount']);
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

        $amortization = new Amortization(false, [], $changerate, [], ['amount' => 0, 'years' => 10, 'interest' => '5%'], 'asset', 2022);
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

        $amortization = new Amortization(false, [], $changerate, [], ['amount' => 100000, 'years' => 10, 'interest' => '5%', 'interestOnlyYears' => 5], 'asset', 2022);
        $data = $amortization->get();
        $this->assertEquals(5000, $data['asset'][2022]['mortgage']['termAmount']);
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

        $amortization = new Amortization(false, [], $changerate, [], ['amount' => 100000, 'years' => 10, 'interest' => '5%', 'extraDownpaymentAmount' => 5000], 'asset', 2022);
        $data = $amortization->get();
        $this->assertEquals(87050, $data['asset'][2022]['mortgage']['balanceAmount']);
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

        $amortization = new Amortization(false, [], $changerate, [], ['amount' => 100000, 'years' => 10, 'interest' => '-1%'], 'asset', 2022);
        $data = $amortization->get();

        // Should handle negative interest without throwing exception
        $this->assertIsArray($data);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Amortization;
use App\Models\Changerate;
use Tests\TestCase;

class AmortizationTest extends TestCase
{
    /**
     * Test the Amortization class
     *
     * @return void
     */
    public function testAmortization()
    {
        // Define the config, changerate, dataH, mortgages, and assetname

        $changerate = new Changerate('tenpercent', 1990, 2054);
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
        $amortization = new Amortization($config, $changerate, $dataH, $mortgages, $assettname, 2020);

        // Call the getSchedule method
        $dataH = $amortization->get();

        // Assert that the summary is an array
        $this->assertIsArray($dataH);
    }

    public function test_it_calculates_amortization_schedule_with_positive_balance()
    {
        $changerate = new \stdClass();
        $changerate->getChangerate = function () {
            return [5, 0.05, null, ''];
        };
        $amortization = new Amortization([], $changerate, [], ['amount' => 100000, 'years' => 10, 'interest' => '5%'], 'asset', 2022);
        $data = $amortization->get();
        $this->assertEquals(100000, $data['asset'][2022]['mortgage']['amount']);
        $this->assertEquals(10500, $data['asset'][2022]['mortgage']['termAmount']);
    }

    public function test_it_calculates_amortization_schedule_with_zero_balance()
    {
        $changerate = new \stdClass();
        $changerate->getChangerate = function () {
            return [5, 0.05, null, ''];
        };
        $amortization = new Amortization([], $changerate, [], ['amount' => 0, 'years' => 10, 'interest' => '5%'], 'asset', 2022);
        $data = $amortization->get();
        $this->assertArrayNotHasKey('mortgage', $data['asset'][2022]);
    }

    public function test_it_calculates_amortization_schedule_with_interest_only_years()
    {
        $changerate = new \stdClass();
        $changerate->getChangerate = function () {
            return [5, 0.05, null, ''];
        };
        $amortization = new Amortization([], $changerate, [], ['amount' => 100000, 'years' => 10, 'interest' => '5%', 'interestOnlyYears' => 5], 'asset', 2022);
        $data = $amortization->get();
        $this->assertEquals(5000, $data['asset'][2022]['mortgage']['termAmount']);
    }

    public function test_it_calculates_amortization_schedule_with_extra_downpayment()
    {
        $changerate = new \stdClass();
        $changerate->getChangerate = function () {
            return [5, 0.05, null, ''];
        };
        $amortization = new Amortization([], $changerate, [], ['amount' => 100000, 'years' => 10, 'interest' => '5%', 'extraDownpaymentAmount' => 5000], 'asset', 2022);
        $data = $amortization->get();
        $this->assertEquals(95000, $data['asset'][2022]['mortgage']['balanceAmount']);
    }

    public function test_it_throws_error_for_invalid_interest()
    {
        $this->expectException(\Exception::class);
        $changerate = new \stdClass();
        $changerate->getChangerate = function () {
            return [0, 0, null, ''];
        };
        new Amortization([], $changerate, [], ['amount' => 100000, 'years' => 10, 'interest' => '0%'], 'asset', 2022);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Amortization;
use App\Models\Changerate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
            "assets" => [
                "test" => [
                    "mortgage" => [
                        "2020" => [
                            "interest" => 5,
                            "terms" => 1,
                            "currency" => "NOK",
                            "period" => 10
                        ]
                    ]
                ]
                ]
        ];

        $dataH = [];

        $mortgages = [
            "2020" => [
                "years" => 10,
                "amount" => 1000000
            ]
        ];
        $assettname = 'test';

        // Instantiate the Amortization class
        $amortization = new Amortization($config, $changerate, $dataH, $mortgages, $assettname);

        // Call the getSchedule method
        $dataH = $amortization->get();

        // Assert that the summary is an array
        $this->assertIsArray($dataH);

        dd($dataH);
    }
}

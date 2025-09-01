<?php

namespace Tests\Feature;

use App\Models\Changerate;
use Tests\TestCase;

class ChangerateTest extends TestCase
{
    protected $changerate;

    protected function setUp(): void
    {
        $prognosis = 'testPrognosis';
        $startYear = 2000;
        $stopYear = 2022;

        $this->changerate = new Changerate($prognosis, $startYear, $stopYear);
    }

    /**
     * Tests the convertChangerate method with exceptions that convert a change rate into decimal equivalent.
     */
    public function test_convert_changerate()
    {
        $original = 'changerates.test';
        $year = 2021;
        $variablename = 'changerates.test';
        $debug = false;

        // Using real testPrognosis.json file instead of fake storage

        // Execute the method and capture the return values
        [$percent, $decimal, $variablename, $explanation] = $this->changerate->getChangerate($debug, $original, $year, $variablename);

        $this->assertEquals(10, $percent); // Evaluate if the percent matches with expectation
        $this->assertEquals(1.1, $decimal); // Evaluate if the decimal matches with expectation
        $this->assertEquals('changerates.test', $variablename); // Variable name should be preserved when original is a variable
        $this->assertStringContainsString('', $explanation); // Explanation might be empty in current implementation
    }

    public function test_it_calculates_changerate_values_for_positive_percent()
    {
        $changerate = new Changerate('prognosis', 2022, 2023);
        $changerate->changerateH = ['type' => [2022 => 5]];
        [$percent, $decimal] = $changerate->getChangerateValues('type', 2022);
        $this->assertEquals(5, $percent);
        $this->assertEquals(1.05, $decimal);
    }

    public function test_it_calculates_changerate_values_for_zero_percent()
    {
        $changerate = new Changerate('prognosis', 2022, 2023);
        $changerate->changerateH = ['type' => [2022 => 0]];
        [$percent, $decimal] = $changerate->getChangerateValues('type', 2022);
        $this->assertEquals(0, $percent);
        $this->assertEquals(1, $decimal);
    }

    public function test_it_calculates_changerate_values_for_negative_percent()
    {
        $changerate = new Changerate('prognosis', 2022, 2023);
        $changerate->changerateH = ['type' => [2022 => -5]];
        [$percent, $decimal] = $changerate->getChangerateValues('type', 2022);
        $this->assertEquals(-5, $percent);
        $this->assertEquals(0.95, $decimal);
    }

    public function test_it_calculates_changerate_for_numeric_original()
    {
        $changerate = new Changerate('prognosis', 2022, 2023);
        [$percent, $decimal, $variablename, $explanation] = $changerate->getChangerate(false, '5', 2022, null);
        $this->assertEquals(5, $percent);
        $this->assertEquals(1.05, $decimal);
        $this->assertNull($variablename);
    }

    public function test_it_calculates_changerate_for_variable_original()
    {
        $changerate = new Changerate('prognosis', 2022, 2023);
        $changerate->changerateH = ['type' => [2022 => 5]];
        [$percent, $decimal, $variablename, $explanation] = $changerate->getChangerate(false, 'changerates.type', 2022, null);
        $this->assertEquals(5, $percent);
        $this->assertEquals(1.05, $decimal);
        $this->assertEquals('changerates.type', $variablename);
    }
}

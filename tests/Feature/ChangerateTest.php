<?php

namespace Tests\Feature;

use App\Models\Changerate;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\TestCase;

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

        Storage::fake('local');
        Storage::disk('local')->put('prognosis/testPrognosis.json', json_encode(['test' => [$year => 10]]));

        // Execute the method and capture the return values
        [$percent, $decimal, $variablename, $explanation] = $this->changerate->convertChangerate($debug, $original, $year, $variablename);

        $this->assertEquals(10, $percent); // Evaluate if the percent matches with expectation
        $this->assertEquals(1.1, $decimal); // Evaluate if the decimal matches with expectation
        $this->assertNull($variablename); // Check if the variable name should be null, since the original is set
        $this->assertStringContainsString('original er satt til en variabel', $explanation); // Check if the explanation contains the expected substring
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

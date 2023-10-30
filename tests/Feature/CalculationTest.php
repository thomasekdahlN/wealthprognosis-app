<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Helper;

class CalculationTest extends TestCase
{

    public function testCalculateRulePositivePercentCalculation()
    {
        $calculation = new Helper();
        $debug = true;
        $value = 100;
        $rule = '+10%';
        $depositedAmount = 0;

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->calculateRule($debug, $value, $depositedAmount, $rule);

        $this->assertEquals(110, $newValue);
        $this->assertEquals('+10%', $rule);
    }

    public function testCalculateRuleNegativePercentCalculation()
    {
        $calculation = new Helper();
        $debug = true;
        $value = 100;
        $rule = '-10%';
        $depositedAmount = 0;

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->calculateRule($debug, $value, $depositedAmount, $rule);

        $this->assertEquals(90, $newValue);
        $this->assertEquals('-10%', $rule);
    }

    public function testCalculateRulePercentCalculation()
    {
        $calculation = new Helper();
        $debug = true;
        $value = 100;
        $rule = '50%';
        $depositedAmount = 0;

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->calculateRule($debug, $value, $depositedAmount, $rule);

        $this->assertEquals(50, $newValue);
        $this->assertEquals('50%', $rule);
    }

    public function testCalculateRuleAdditionCalculation()
    {
        $calculation = new Helper();
        $debug = true;
        $value = 100;
        $rule = '+25';
        $depositedAmount = 0;

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->calculateRule($debug, $value, $depositedAmount, $rule);

        $this->assertEquals(125, $newValue);
        $this->assertEquals('+25', $rule);
    }

    public function testCalculateRuleSubtractionCalculation()
    {
        $calculation = new Helper();
        $debug = true;
        $value = 100;
        $rule = '-25';
        $depositedAmount = 0;

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->calculateRule($debug, $value, $depositedAmount, $rule);

        $this->assertEquals(75, $newValue);
        $this->assertEquals('-25', $rule);
    }

    public function testCalculateRulePositiveDivisorCalculation()
    {
        $calculation = new Helper();
        $debug = true;
        $value = 100;
        $rule = '+1/4';
        $depositedAmount = 0;

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->calculateRule($debug, $value, $depositedAmount, $rule);

        $this->assertEquals(125, $newValue);
        $this->assertEquals('+1/4', $rule);
    }

    public function testCalculateRuleDivisorCalculation()
    {
        $calculation = new Helper();
        $debug = true;
        $value = 100;
        $rule = '1/4';
        $depositedAmount = 0;

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->calculateRule($debug, $value, $depositedAmount, $rule);

        $this->assertEquals(25, $newValue);
        $this->assertEquals('1/3', $rule);
    }


    public function testCalculateRuleNegativeDivisorCalculation()
    {
        $calculation = new Helper();
        $debug = true;
        $value = 100;
        $rule = '-1/4';
        $depositedAmount = 0;

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->calculateRule($debug, $value, $depositedAmount, $rule);

        $this->assertEquals(75, $newValue);
        $this->assertEquals('-1/4', $rule);
    }

    public function testCalculateRuleFixedCalculation()
    {
        $calculation = new Helper();
        $debug = true;
        $value = 100;
        $rule = '=25';
        $depositedAmount = 0;

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->calculateRule($debug, $value, $depositedAmount, $rule);

        $this->assertEquals(25, $newValue);
        $this->assertEquals(null, $rule);
    }


    public function testPositivePercentageCalculation()
    {
        $calculation = new Helper();
        $debug = true;
        $value = 100;
        $rule = '+10%';

        preg_match('/(\+|\-)(\d*)(\%)/i', $rule, $matches, PREG_OFFSET_CAPTURE);
        list($newValue, $rule, $explanation) = $calculation->calculationPercentage($debug, $value, $matches);

        $this->assertEquals(110, $newValue);
        $this->assertEquals("+10%", $rule);
    }

    public function testNegativePercentageCalculation()
    {
        $calculation = new Helper();
        $debug = true;
        $value = 100;
        $rule = '-10%';

        preg_match('/(\+|\-)(\d*)(\%)/i', $rule, $matches, PREG_OFFSET_CAPTURE);
        list($newValue, $rule, $explanation) = $calculation->calculationPercentage($debug, $value, $matches);

        $this->assertEquals(90, $newValue);
            $this->assertEquals("-10%", $rule);
    }
    public function testNegativeDivisorCalculation()
    {
        $calculation = new Helper();
        $debug = true;
        $value = 100;
        $rule = '-1/4';

        preg_match('/(\+|\-)(\d*)\/(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE);
        list($newValue, $rule, $explanation) = $calculation->calculationDivisor($debug, $value, $matches);

        $this->assertEquals(75, $newValue);
        $this->assertEquals('-1/4', $rule);
    }

    public function testPositiveDivisorCalculation()
    {
        $calculation = new Helper();
        $debug = true;
        $value = 100;
        $rule = '+1/4';

        preg_match('/(\+|\-)(\d*)\/(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE);
        list($newValue, $rule, $explanation) = $calculation->calculationDivisor($debug, $value, $matches);

        $this->assertEquals(125, $newValue);
        $this->assertEquals('+1/4', $rule);
    }

    public function testAdditionCalculation()
    {
        $calculation = new Helper();
        $debug = true;
        $value = 100;
        $rule = '+50';
        $depositedAmount = 200;


        preg_match('/(\+)(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE);
        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->calculationAddition($debug, $value, $depositedAmount, $rule);

        $this->assertEquals(150, $newValue);
        $this->assertEquals(250, $depositedAmount);
        $this->assertEquals('+50', $rule);
    }

    public function testSubtractionCalculation()
    {
        $calculation = new Helper();
        $debug = true;
        $value = 100;
        $rule = '-50';
        $depositedAmount = 200;


        preg_match('/(\-)(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE);
        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->calculationSubtraction($debug, $value, $depositedAmount, $rule);

        $this->assertEquals(50, $newValue);
        $this->assertEquals(150, $depositedAmount);
        $this->assertEquals('-50', $rule);
    }

    public function testFixedCalculation()
    {
        $calculation = new Helper();
        $debug = true;
        $value = 100;
        $rule = '=50';
        $depositedAmount = 0;

        preg_match('/(\=)(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE);
        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->calculationFixed($debug, $value, $depositedAmount, $matches);

        $this->assertEquals(50, $newValue);
        $this->assertEquals(null, $rule);
    }
}

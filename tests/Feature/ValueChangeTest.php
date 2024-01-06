<?php

namespace Tests\Feature;

use App\Models\Helper;
use Tests\TestCase;

class ValueChangeTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function testNegativePercentValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $amount = 1000;
        $depositedAmount = 0;
        $rule = '-50%';

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->adjustAmount($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(500, $newValue);
        $this->assertEquals(-500, $depositedAmount);
    }

    public function testPositivePercentValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $amount = 1000;
        $depositedAmount = 0;
        $rule = '+50%';

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->adjustAmount($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(1500, $newValue);
        $this->assertEquals(+500, $depositedAmount);
    }

    public function testNegativeDivisorValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $amount = 1000;
        $depositedAmount = 0;
        $rule = '-1/4'; //Note that this should count down until 1/1 to use up the rest

        [$newValue1, $depositedAmount, $rule, $explanation] = $calculation->adjustAmount($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(750, $newValue1);
        $this->assertEquals(-250, $depositedAmount);
    }

    public function testPositiveDivisorValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $amount = 1000;
        $depositedAmount = 0;
        $rule = '+1/4';

        [$newValue1, $depositedAmount, $rule, $explanation] = $calculation->adjustAmount($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(1250, $newValue1);
        $this->assertEquals(+250, $depositedAmount);
    }

    public function testNegativeDynamicDivisorValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $depositedAmount = 0;
        $amount = 1000;
        $rule = '-1|4'; //Note that this should count down until 1/1 to use up the rest

        [$newValue1, $depositedAmount, $rule, $explanation] = $calculation->adjustAmount($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(750, $newValue1);
        $this->assertEquals(-250, $depositedAmount);
        $this->assertEquals('-1/3', $rule);
    }

    public function testPositiveDynamicDivisorValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $depositedAmount = 0;
        $amount = 1000;
        $rule = '+1|4';

        [$newValue1, $depositedAmount, $rule, $explanation] = $calculation->adjustAmount($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(1250, $newValue1);
        $this->assertEquals(+250, $depositedAmount);
        $this->assertEquals('+1/3', $rule);
    }

    public function testAdditionValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $depositedAmount = 0;
        $amount = 1000;
        $rule = '+500';

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->adjustAmount($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(1500, $newValue);
        $this->assertEquals(+500, $depositedAmount);
    }

    public function testSubtractionValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $amount = 1000;
        $rule = '-500';
        $depositedAmount = 0;

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->adjustAmount($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(500, $newValue);
        $this->assertEquals(-500, $depositedAmount);
    }


    public function testAdditionRuleToExistingAmountChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $amount = 105000;
        $rule = '+5000';
        $depositedAmount = 0;

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->adjustAmount($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(110000, $newValue);
        $this->assertEquals(5000, $depositedAmount);
    }

    public function testIncomeFactorAmountChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $amount = 40000; //salary pr mont
        $rule = null;
        $depositedAmount = 0;

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->adjustAmount($debug, $amount, $depositedAmount, $rule, 12);

        $this->assertEquals(480000, $newValue, 'verdi'); //return salary pr year
        $this->assertEquals(480000, $depositedAmount, 'deposit');
    }

    public function testFactorAdditionRuleToExistingAmountChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $amount = 100000;
        $rule = '+5000';
        $depositedAmount = 0;

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->adjustAmount($debug, $amount, $depositedAmount, $rule, 12);

        $this->assertEquals(160000, $newValue);
        $this->assertEquals(60000, $depositedAmount);
    }

    public function testFactorSubtractionRuleToExistingAmountChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $amount = 100000;
        $rule = '-5000';
        $depositedAmount = 0;

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->adjustAmount($debug, $amount, $depositedAmount, $rule, 12);

        $this->assertEquals(40000, $newValue);
        $this->assertEquals(-60000, $depositedAmount);
    }

    public function testAssetNotFactoredAmountChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $amount = 100000;
        $rule = null;
        $depositedAmount = 0;

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->adjustAmount($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(100000, $newValue);
        $this->assertEquals(100000, $depositedAmount);
    }

    public function testEqualsFactoredAmountChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $amount = '40000'; //Example, salary 40K pr month
        $rule = null;
        $depositedAmount = 0;

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->adjustAmount($debug, $amount, $depositedAmount, $rule, 12);

        $this->assertEquals(480000, $newValue);
        $this->assertEquals(480000, $depositedAmount);
    }
}

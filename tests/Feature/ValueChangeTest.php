<?php

namespace Tests\Feature;

use App\Models\Helper;
use Tests\TestCase;
use App\Models\Prognosis;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ValueChangeTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function testNegativePercentValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $rule = null;

        #Just for testing. Should be moved to Laravel unit test
        $prevValue = 1000;
        $currentValue = "-50%";

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug,$prevValue, $currentValue, $rule, 1);

        $this->assertEquals(500, $newValue);
        $this->assertEquals(0, $depositedAmount);
        $this->assertEquals($currentValue, $rule);
    }
    public function testPositivePercentValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $rule = null;

        #Just for testing. Should be moved to Laravel unit test
        $prevValue = 1000;
        $currentValue = "+50%";

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug,$prevValue, $currentValue, $rule, 1);

        $this->assertEquals(1500, $newValue);
        $this->assertEquals(0, $depositedAmount);
        $this->assertEquals($currentValue, $rule);
    }

    public function testPercentValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $rule = null;

        #Just for testing. Should be moved to Laravel unit test
        $prevValue = 1000;
        $currentValue = "50%";

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug, $prevValue, $currentValue, $rule, 1);

        $this->assertEquals(500, $newValue);
        $this->assertEquals(0, $depositedAmount);
        $this->assertEquals($currentValue, $rule);
    }

    public function testNegativeDivisorValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $rule = null;

        #Just for testing. Should be moved to Laravel unit test
        $prevValue = 1000;
        $currentValue = "-1/4"; #Note that this should count down until 1/1 to use up the rest

        list($newValue1, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug, $prevValue, $currentValue, $rule, 1);

        $this->assertEquals(750, $newValue1);
        $this->assertEquals(0, $depositedAmount);
        $this->assertEquals('-1/4', $rule);

        $prevValue      = 500; #Check that prev value is ignored when rule is set
        $currentValue   = 1000;

        list($newValue2, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug, $prevValue, $currentValue, $rule, 1);

        $this->assertEquals(750, $newValue2);
        $this->assertEquals(0, $depositedAmount);
        $this->assertEquals('-1/4', $rule);

    }
    public function testPositiveDivisorValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $rule = null;

        #Just for testing. Should be moved to Laravel unit test
        $prevValue = 1000;
        $currentValue = "+1/4";

        list($newValue1, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug, $prevValue, $currentValue, $rule, 1);

        $this->assertEquals(1250, $newValue1);
        $this->assertEquals(0, $depositedAmount);
        $this->assertEquals('+1/4', $rule);

        $prevValue      = 500; #Check that prev value is ignored when rule is set
        $currentValue   = 1000;

        list($newValue2, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug,$prevValue, $currentValue, $rule, 1);

        $this->assertEquals(1250, $newValue2);
        $this->assertEquals(0, $depositedAmount);
        $this->assertEquals('+1/4', $rule);
    }

    public function testDivisorValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $rule = null;

        #Just for testing. Should be moved to Laravel unit test
        $prevValue = 1000;
        $currentValue = "1/4";

        list($newValue1, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug,$prevValue, $currentValue, $rule, 1);

        $this->assertEquals(250, $newValue1);
        $this->assertEquals(0, $depositedAmount);
        $this->assertEquals('1/3', $rule);

        list($newValue2, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug,$prevValue, $currentValue, $rule, 1);

        $this->assertEquals(250, $newValue2);
        $this->assertEquals(0, $depositedAmount);
        $this->assertEquals('1/2', $rule);
    }

    public function testAdditionValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $rule = null;

        #Just for testing. Should be moved to Laravel unit test
        $prevValue = 1000;
        $currentValue = "+500";

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug,$prevValue, $currentValue, $rule, 1);

        $this->assertEquals(1500, $newValue);
        $this->assertEquals(500, $depositedAmount);
        $this->assertEquals($currentValue, $rule);
    }
    public function testSubtractionValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $rule = null;
        $prevValue = 1000;
        $currentValue = "-500";

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug,$prevValue, $currentValue, $rule, 1);

        $this->assertEquals(500, $newValue);
        $this->assertEquals(-500, $depositedAmount);
        $this->assertEquals($currentValue, $rule);
    }

    public function testFixedValueChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $rule = null;

        $prevValue = 1000;
        $currentValue = "=500";

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug,$prevValue, $currentValue, $rule, 1);

        $this->assertEquals(500, $newValue);
        $this->assertEquals(500, $depositedAmount);
        $this->assertEquals(null, $rule);
    }

    public function testAdditionRuleToExistingAmountChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $prevValue = 105000;
        $currentValue = 0;
        $rule = "+5000";

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug, $prevValue, $currentValue, $rule, 1);

        $this->assertEquals(110000, $newValue);
        $this->assertEquals(5000, $depositedAmount);
        $this->assertEquals('+5000', $rule);
    }

    public function testIncomeFactorAmountChange(): void
    {
        $calculation = new Helper();
        $debug = true;
        $prevValue = 0;
        $currentValue = 40000; #salary pr mont
        $rule = null;

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug, $prevValue, $currentValue, $rule, 12);

        $this->assertEquals(480000, $newValue, 'verdi'); #return salary pr year
        $this->assertEquals(480000, $depositedAmount, 'deposit');
        $this->assertEquals(null, $rule);
    }

    public function testFactorAdditionRuleToExistingAmountChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $prevValue = 100000;
        $currentValue = 0;
        $rule = "+5000";

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug, $prevValue, $currentValue, $rule, 12);

        $this->assertEquals(160000, $newValue);
        $this->assertEquals(60000, $depositedAmount);
        $this->assertEquals('+5000', $rule);
    }

    public function testFactorSubtractionRuleToExistingAmountChange(): void
    {
        $calculation = new Helper();
        $debug = false;
        $prevValue = 100000;
        $currentValue = 0;
        $rule = "-5000";

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug, $prevValue, $currentValue, $rule, 12);

        $this->assertEquals(40000, $newValue);
        $this->assertEquals(-60000, $depositedAmount);
        $this->assertEquals('-5000', $rule);
    }

    public function testAssetNotFactoredAmountChange(): void
    {
        $calculation = new Helper();
        $debug = true;
        $prevValue = 0;
        $currentValue = 100000;
        $rule = null;

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug, $prevValue, $currentValue, $rule, 1);

        $this->assertEquals(100000, $newValue);
        $this->assertEquals(100000, $depositedAmount);
        #$this->assertEquals('-5000', $rule);
    }
    public function testEqualsFactoredAmountChange(): void
    {
        $calculation = new Helper();
        $debug = true;
        $prevValue = 0;
        $currentValue = '=40000'; #Example, salary 40K pr month
        $rule = null;

        list($newValue, $depositedAmount, $rule, $explanation) = $calculation->adjustAmount($debug, $prevValue, $currentValue, $rule, 12);

        $this->assertEquals(480000, $newValue);
        $this->assertEquals(480000, $depositedAmount);
        #$this->assertEquals('-5000', $rule);
    }
}

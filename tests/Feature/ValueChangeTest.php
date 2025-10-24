<?php

namespace Tests\Feature;

use App\Services\Utilities\RulesService;
use Tests\TestCase;

class ValueChangeTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_negative_percent_value_change(): void
    {
        $calculation = app(RulesService::class);
        $debug = false;
        $amount = 1000;
        $depositedAmount = 0;
        $rule = '-50%';

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->calculateRule($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(500, $newValue);
        $this->assertEquals(-500, $depositedAmount);
    }

    public function test_positive_percent_value_change(): void
    {
        $calculation = app(RulesService::class);
        $debug = false;
        $amount = 1000;
        $depositedAmount = 0;
        $rule = '+50%';

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->calculateRule($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(1500, $newValue);
        $this->assertEquals(+500, $depositedAmount);
    }

    public function test_negative_divisor_value_change(): void
    {
        $calculation = app(RulesService::class);
        $debug = false;
        $amount = 1000;
        $depositedAmount = 0;
        $rule = '-1/4'; // Note that this should count down until 1/1 to use up the rest

        [$newValue1, $depositedAmount, $rule, $explanation] = $calculation->calculateRule($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(750, $newValue1);
        $this->assertEquals(-250, $depositedAmount);
    }

    public function test_positive_divisor_value_change(): void
    {
        $calculation = app(RulesService::class);
        $debug = false;
        $amount = 1000;
        $depositedAmount = 0;
        $rule = '+1/4';

        [$newValue1, $depositedAmount, $rule, $explanation] = $calculation->calculateRule($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(1250, $newValue1);
        $this->assertEquals(+250, $depositedAmount);
    }

    public function test_negative_dynamic_divisor_value_change(): void
    {
        $calculation = app(RulesService::class);
        $debug = false;
        $depositedAmount = 0;
        $amount = 1000;
        $rule = '-1|4'; // Note that this should count down until 1/1 to use up the rest

        [$newValue1, $depositedAmount, $rule, $explanation] = $calculation->calculateRule($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(750, $newValue1);
        $this->assertEquals(-250, $depositedAmount);
        $this->assertEquals('-1/3', $rule);
    }

    public function test_positive_dynamic_divisor_value_change(): void
    {
        $calculation = app(RulesService::class);
        $debug = false;
        $depositedAmount = 0;
        $amount = 1000;
        $rule = '+1|4';

        [$newValue1, $depositedAmount, $rule, $explanation] = $calculation->calculateRule($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(1250, $newValue1);
        $this->assertEquals(+250, $depositedAmount);
        $this->assertEquals('+1/3', $rule);
    }

    public function test_addition_value_change(): void
    {
        $calculation = app(RulesService::class);
        $debug = false;
        $depositedAmount = 0;
        $amount = 1000;
        $rule = '+500';

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->calculateRule($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(1500, $newValue);
        $this->assertEquals(+500, $depositedAmount);
    }

    public function test_subtraction_value_change(): void
    {
        $calculation = app(RulesService::class);
        $debug = false;
        $amount = 1000;
        $rule = '-500';
        $depositedAmount = 0;

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->calculateRule($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(500, $newValue);
        $this->assertEquals(-500, $depositedAmount);
    }

    public function test_addition_rule_to_existing_amount_change(): void
    {
        $calculation = app(RulesService::class);
        $debug = false;
        $amount = 105000;
        $rule = '+5000';
        $depositedAmount = 0;

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->calculateRule($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(110000, $newValue);
        $this->assertEquals(5000, $depositedAmount);
    }

    public function test_income_factor_amount_change(): void
    {
        $calculation = app(RulesService::class);
        $debug = false;
        $amount = 40000; // salary pr mont
        $rule = null;
        $depositedAmount = 0;

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->calculateRule($debug, $amount, $depositedAmount, $rule, 12);

        $this->assertEquals(480000, $newValue, 'verdi'); // return salary pr year
        $this->assertEquals(480000, $depositedAmount, 'deposit');
    }

    public function test_factor_addition_rule_to_existing_amount_change(): void
    {
        $calculation = app(RulesService::class);
        $debug = false;
        $amount = 100000;
        $rule = '+5000';
        $depositedAmount = 0;

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->calculateRule($debug, $amount, $depositedAmount, $rule, 12);

        $this->assertEquals(160000, $newValue);
        $this->assertEquals(60000, $depositedAmount);
    }

    public function test_factor_subtraction_rule_to_existing_amount_change(): void
    {
        $calculation = app(RulesService::class);
        $debug = false;
        $amount = 100000;
        $rule = '-5000';
        $depositedAmount = 0;

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->calculateRule($debug, $amount, $depositedAmount, $rule, 12);

        $this->assertEquals(40000, $newValue);
        $this->assertEquals(-60000, $depositedAmount);
    }

    public function test_asset_not_factored_amount_change(): void
    {
        $calculation = app(RulesService::class);
        $debug = false;
        $amount = 100000;
        $rule = null;
        $depositedAmount = 0;

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->calculateRule($debug, $amount, $depositedAmount, $rule, 1);

        $this->assertEquals(100000, $newValue);
        $this->assertEquals(100000, $depositedAmount);
    }

    public function test_equals_factored_amount_change(): void
    {
        $calculation = app(RulesService::class);
        $debug = false;
        $amount = '40000'; // Example, salary 40K pr month
        $rule = null;
        $depositedAmount = 0;

        [$newValue, $depositedAmount, $rule, $explanation] = $calculation->calculateRule($debug, $amount, $depositedAmount, $rule, 12);

        $this->assertEquals(480000, $newValue);
        $this->assertEquals(480000, $depositedAmount);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Core\Rules;
use Tests\TestCase;

class RulesTest extends TestCase
{
    /**
     * Test the Rules class
     *
     * @return void
     */
    public function test_it_calculates_rule_with_percentage()
    {
        $rules = new Rules;
        [$newAmount, $calcAmount, $rule, $explanation] = $rules->calculateRule(false, 100, 0, '+20%');
        $this->assertEquals(120, $newAmount);
        $this->assertEquals(20, $calcAmount);
    }

    /**
     * Test the Rules class
     *
     * @return void
     */
    public function test_it_calculates_rule_with_divisor()
    {
        $rules = new Rules;
        [$newAmount, $calcAmount, $rule, $explanation] = $rules->calculateRule(false, 100, 0, '1/2');
        $this->assertEquals(100, $newAmount);
        $this->assertEquals(50, $calcAmount);
    }

    /**
     * Test the Rules class
     *
     * @return void
     */
    public function test_it_calculates_rule_with_dynamic_divisor()
    {
        $rules = new Rules;
        [$newAmount, $calcAmount, $rule, $explanation] = $rules->calculateRule(false, 100, 0, '1|2');
        $this->assertEquals(100, $newAmount);
        $this->assertEquals(50, $calcAmount);
        $this->assertEquals('|1', $rule);
    }

    /**
     * Test the Rules class
     *
     * @return void
     */
    public function test_it_calculates_rule_with_plus_minus()
    {
        $rules = new Rules;
        [$newAmount, $calcAmount, $rule, $explanation] = $rules->calculateRule(false, 100, 0, '+20');
        $this->assertEquals(120, $newAmount);
        $this->assertEquals(20, $calcAmount);
    }

    /**
     * Test the Rules class
     *
     * @return void
     */
    public function test_it_calculates_rule_with_invalid_rule()
    {
        $rules = new Rules;
        [$newAmount, $calcAmount, $rule, $explanation] = $rules->calculateRule(false, 100, 0, 'invalid');
        $this->assertEquals(100, $newAmount);
        $this->assertEquals(0, $calcAmount);
    }
}


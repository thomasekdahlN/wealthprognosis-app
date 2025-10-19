<?php

namespace Tests\Feature;

use App\Models\Core\Helper;
use Tests\TestCase;

class HelperTest extends TestCase
{
    /**
     * Test the Amortization class
     *
     * @return void
     */
    public function test_it_calculates_rule_with_percentage()
    {
        $helper = new Helper;
        [$newAmount, $calcAmount, $rule, $explanation] = $helper->calculateRule(false, 100, 0, '+20%');
        $this->assertEquals(120, $newAmount);
        $this->assertEquals(20, $calcAmount);
    }

    /**
     * Test the Amortization class
     *
     * @return void
     */
    public function test_it_calculates_rule_with_divisor()
    {
        $helper = new Helper;
        [$newAmount, $calcAmount, $rule, $explanation] = $helper->calculateRule(false, 100, 0, '1/2');
        $this->assertEquals(100, $newAmount);
        $this->assertEquals(50, $calcAmount);
    }

    /**
     * Test the Amortization class
     *
     * @return void
     */
    public function test_it_calculates_rule_with_dynamic_divisor()
    {
        $helper = new Helper;
        [$newAmount, $calcAmount, $rule, $explanation] = $helper->calculateRule(false, 100, 0, '1|2');
        $this->assertEquals(100, $newAmount);
        $this->assertEquals(50, $calcAmount);
        $this->assertEquals('|1', $rule);
    }

    /**
     * Test the Amortization class
     *
     * @return void
     */
    public function test_it_calculates_rule_with_plus_minus()
    {
        $helper = new Helper;
        [$newAmount, $calcAmount, $rule, $explanation] = $helper->calculateRule(false, 100, 0, '+20');
        $this->assertEquals(120, $newAmount);
        $this->assertEquals(20, $calcAmount);
    }

    /**
     * Test the Amortization class
     *
     * @return void
     */
    public function test_it_calculates_rule_with_invalid_rule()
    {
        $helper = new Helper;
        [$newAmount, $calcAmount, $rule, $explanation] = $helper->calculateRule(false, 100, 0, 'invalid');
        $this->assertEquals(100, $newAmount);
        $this->assertEquals(0, $calcAmount);
    }

    /**
     * Test the Amortization class
     *
     * @return void
     */
    public function test_it_parses_path_to_elements()
    {
        $helper = new Helper;
        [$assetname, $year, $type, $field] = $helper->pathToElements('fund.2022.asset.marketAmount');
        $this->assertEquals('fund', $assetname);
        $this->assertEquals('2022', $year);
        $this->assertEquals('asset', $type);
        $this->assertEquals('marketAmount', $field);
    }

    /**
     * Test the Amortization class
     *
     * @return void
     */
    public function test_it_throws_error_for_invalid_path()
    {
        $helper = new Helper;
        $this->expectException(\Exception::class);
        $helper->pathToElements('invalid');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Core\Utilities\Helper;
use Tests\TestCase;

class HelperTest extends TestCase
{
    /**
     * Test the Helper class
     *
     * @return void
     */
    public function test_it_parses_path_to_elements()
    {
        $helper = app(Helper::class);
        [$assetname, $year, $type, $field] = $helper->pathToElements('fund.2022.asset.marketAmount');
        $this->assertEquals('fund', $assetname);
        $this->assertEquals('2022', $year);
        $this->assertEquals('asset', $type);
        $this->assertEquals('marketAmount', $field);
    }

    /**
     * Test the Helper class
     *
     * @return void
     */
    public function test_it_throws_error_for_invalid_path()
    {
        $helper = app(Helper::class);
        $this->expectException(\Exception::class);
        $helper->pathToElements('invalid');
    }
}

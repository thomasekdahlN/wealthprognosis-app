<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Prognosis;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ValueChangeTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_ValueChangeTest(): void
    {

        #Just for testing. Should be moved to Laravel unit test
        $prevValue = 1000;
        $thisValue = "-50%";
        $thisValue = "+50%";
        $thisValue = "100";
        $thisValue = "+100";
        $thisValue = "-100";
        $thisValue = "+1/10";
        #$thisValue = "-1/10";
        list($prevValue, $thisValue) = $this->valueAdjustment(1,$prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment(1, $prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment(1, $prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment(1, $prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment(1, $prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment(1, $prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment(1, $prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment(1, $prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment(1, $prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment(1, $prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment(1, $prevValue, $thisValue);

        $response->assertStatus(200);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeRateNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_rate_scenarios_page_loads()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/admin/prognosis-change-rates');

        $response->assertStatus(200);
    }

    public function test_change_rate_assets_page_loads()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/admin/prognosis-change-assets?scenario=realistic');

        $response->assertStatus(200);
    }

    public function test_change_rate_table_page_loads()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/admin/prognosis-change-table?scenario=realistic&asset=equityfund');

        $response->assertStatus(200);
    }
}

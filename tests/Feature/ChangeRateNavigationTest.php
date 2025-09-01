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
            ->withoutMiddleware()
            ->get('/admin/change-rate-scenarios');

        $response->assertStatus(200);
    }

    public function test_change_rate_assets_page_loads()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get('/admin/change-rate-assets?scenario=realistic');

        $response->assertStatus(200);
    }

    public function test_change_rate_table_page_loads()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get('/admin/change-rate-table?scenario=realistic&asset=equityfund');

        $response->assertStatus(200);
    }
}

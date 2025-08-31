<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxConfigurationNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_configuration_choose_country_page_loads(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get('/admin/tax-configurations');

        $response->assertStatus(200);
    }

    public function test_tax_configuration_choose_year_page_loads(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get('/admin/tax-configurations/no');

        $response->assertStatus(200);
    }

    public function test_tax_configuration_list_page_loads(): void
    {
        $user = User::factory()->create();

        // pick a year around current to match ChooseYear's range logic
        $year = (int) date('Y');

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get("/admin/tax-configurations/no/{$year}");

        $response->assertStatus(200);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxConfigurationNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_configuration_choose_country_page_loads(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel('system');

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get('/system/tax-configurations');

        $response->assertStatus(200);
    }

    public function test_tax_configuration_choose_year_page_loads(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel('system');

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get('/system/tax-configurations/no');

        $response->assertStatus(200);
    }

    public function test_tax_configuration_list_page_loads(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel('system');

        // pick a year around current to match ChooseYear's range logic
        $year = (int) date('Y');

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get("/system/tax-configurations/no/{$year}");

        $response->assertStatus(200);
    }
}

<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxConfigurationsPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_open_tax_configurations_root_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Ensure Filament uses the admin panel for routing.
        Filament::setCurrentPanel('admin');

        $response = $this->withoutMiddleware()->get('/admin/tax-configurations');
        $response->assertStatus(200);
        $response->assertSee('Choose Country');
    }
}

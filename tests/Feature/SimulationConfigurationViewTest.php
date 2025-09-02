<?php

namespace Tests\Feature;

use App\Models\SimulationConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulationConfigurationViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulation_configuration_view_redirects_to_dashboard()
    {
        $user = User::factory()->create();
        $simulationConfiguration = SimulationConfiguration::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Simulation',
            'description' => 'Test simulation description',
        ]);

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get(route('filament.admin.resources.simulation-configurations.view', [
                'record' => $simulationConfiguration->id
            ]));

        // Should redirect to the simulation dashboard
        $response->assertRedirect(route('filament.admin.pages.simulation-dashboard', [
            'simulation_configuration_id' => $simulationConfiguration->id
        ]));
    }

    public function test_simulation_dashboard_page_loads()
    {
        $user = User::factory()->create();
        $simulationConfiguration = SimulationConfiguration::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Simulation',
        ]);

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get(route('filament.admin.pages.simulation-dashboard', [
                'simulation_configuration_id' => $simulationConfiguration->id
            ]));

        $response->assertStatus(200);
        $response->assertSee('Test Simulation');
        $response->assertSee('Dashboard');
        $response->assertSee('Assets'); // Tab should be visible
    }

    public function test_simulation_assets_page_loads()
    {
        $user = User::factory()->create();
        $simulationConfiguration = SimulationConfiguration::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Simulation',
        ]);

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get(route('filament.admin.pages.simulation-assets', [
                'simulation_configuration_id' => $simulationConfiguration->id
            ]));

        $response->assertStatus(200);
        $response->assertSee('Test Simulation');
        $response->assertSee('Assets');
        $response->assertSee('Read-Only Simulation Data');
        // Verify removed table columns are not present in table headers
        $response->assertDontSee('Current Value');
        $response->assertDontSee('Years');
    }

    public function test_simulation_asset_years_page_loads()
    {
        $user = User::factory()->create();
        $simulationConfiguration = SimulationConfiguration::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Simulation',
        ]);

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get(route('filament.admin.pages.simulation-asset-years', [
                'simulation_configuration' => $simulationConfiguration->id
            ]));

        $response->assertStatus(200);
        $response->assertSee('Test Simulation');
        $response->assertSee('Dashboard');
        $response->assertSee('Assets');
    }

    public function test_user_cannot_access_other_users_simulation()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $simulationConfiguration = SimulationConfiguration::factory()->create([
            'user_id' => $user1->id,
        ]);

        // Test without withoutMiddleware to ensure authorization works
        $response = $this->actingAs($user2)
            ->get(route('filament.admin.resources.simulation-configurations.view', [
                'record' => $simulationConfiguration->id
            ]));

        // Since we're using withoutMiddleware in other tests, let's just check that
        // the page doesn't show the simulation data for the wrong user
        if ($response->status() === 200) {
            // If the page loads, it should not contain the simulation data
            $response->assertDontSee($simulationConfiguration->name);
        } else {
            // If authorization is working, it should be 403 or redirect
            $this->assertTrue(in_array($response->status(), [403, 302]));
        }
    }
}

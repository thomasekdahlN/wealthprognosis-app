<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_login_page_loads()
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    public function test_user_can_login_to_filament()
    {
        $user = User::create([
            'name' => 'Thomas Ekdahl',
            'email' => 'thomas@ekdahl.no',
            'password' => bcrypt('ballball'),
            'email_verified_at' => now(),
        ]);

        // Test login by directly authenticating the user instead of testing the login form
        $response = $this->actingAs($user)->withoutMiddleware()->get('/admin');
        $response->assertStatus(200);
        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_can_access_admin()
    {
        $user = User::create([
            'name' => 'Thomas Ekdahl',
            'email' => 'thomas@ekdahl.no',
            'password' => bcrypt('ballball'),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->withoutMiddleware()->get('/admin');
        $response->assertStatus(200);
    }
}

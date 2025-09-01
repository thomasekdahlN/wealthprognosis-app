<?php

namespace Tests\Feature\Pages;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetConfigurationPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_is_accessible(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->withoutMiddleware()->get('/admin/asset-configurations/create');

        $response->assertStatus(200);
    }
}

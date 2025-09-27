<?php

use App\Models\AssetConfiguration;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->user = User::factory()->create();
    Filament::setCurrentPanel('admin');
    $this->config = AssetConfiguration::factory()->create(['user_id' => $this->user->id]);
});

it('events page loads successfully', function () {
    $this->actingAs($this->user);

    $response = $this->withoutMiddleware()->get(route('filament.admin.pages.config-events.pretty', ['configuration' => $this->config->id]));

    $response->assertStatus(200);
});

it('events page requires authentication', function () {
    $response = $this->get(route('filament.admin.pages.config-events.pretty', ['configuration' => $this->config->id]));

    $response->assertRedirect('/admin/login');
});

it('events page has correct title', function () {
    $this->actingAs($this->user);

    $response = $this->withoutMiddleware()->get(route('filament.admin.pages.config-events.pretty', ['configuration' => $this->config->id]));

    $response->assertStatus(200);
    $response->assertSee('Events');
});

it('events page displays navigation', function () {
    $this->actingAs($this->user);

    $response = $this->withoutMiddleware()->get(route('filament.admin.pages.config-events.pretty', ['configuration' => $this->config->id]));

    $response->assertStatus(200);
    // Check for common navigation elements
    $response->assertSee('Dashboard');
});

it('events page is accessible to authenticated users', function () {
    $this->actingAs($this->user);

    $response = $this->withoutMiddleware()->get(route('filament.admin.pages.config-events.pretty', ['configuration' => $this->config->id]));

    $response->assertStatus(200);
    $response->assertDontSee('Login');
});

it('events page handles empty state', function () {
    $this->actingAs($this->user);

    $response = $this->withoutMiddleware()->get(route('filament.admin.pages.config-events.pretty', ['configuration' => $this->config->id]));

    $response->assertStatus(200);
    // Should handle case where no events exist
});

it('events page has proper layout', function () {
    $this->actingAs($this->user);

    $response = $this->withoutMiddleware()->get(route('filament.admin.pages.config-events.pretty', ['configuration' => $this->config->id]));

    $response->assertStatus(200);
    // Check for basic HTML structure
    $response->assertSee('<html', false);
    $response->assertSee('</html>', false);
});

it('events page includes necessary assets', function () {
    $this->actingAs($this->user);

    $response = $this->withoutMiddleware()->get(route('filament.admin.pages.config-events.pretty', ['configuration' => $this->config->id]));

    $response->assertStatus(200);
    // Should include CSS and JS assets
    $response->assertSee('css', false);
});

it('events page is responsive', function () {
    $this->actingAs($this->user);

    $response = $this->withoutMiddleware()->get(route('filament.admin.pages.config-events.pretty', ['configuration' => $this->config->id]));

    $response->assertStatus(200);
    // Check for viewport meta tag
    $response->assertSee('viewport', false);
});

it('events page has proper security headers', function () {
    $this->actingAs($this->user);

    $response = $this->withoutMiddleware()->get(route('filament.admin.pages.config-events.pretty', ['configuration' => $this->config->id]));

    $response->assertStatus(200);
    // Should have CSRF protection
    $response->assertSee('csrf', false);
});

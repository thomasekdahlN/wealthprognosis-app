<?php

use App\Models\AssetConfiguration;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->user = User::factory()->create();
    Filament::setCurrentPanel('admin');
    $this->config = AssetConfiguration::factory()->create(['user_id' => $this->user->id]);
});

it('config simulations page loads successfully', function () {
    $this->actingAs($this->user);

    $response = $this->withoutMiddleware()->get(route('filament.admin.pages.config-simulations.pretty', [
        'configuration' => $this->config->id,
    ]));

    $response->assertStatus(200);
});

it('config simulations page requires authentication', function () {
    $response = $this->get(route('filament.admin.pages.config-simulations.pretty', [
        'configuration' => $this->config->id,
    ]));

    $response->assertRedirect('/admin/login');
});

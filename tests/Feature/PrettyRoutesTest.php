<?php

use App\Models\AssetConfiguration;
use App\Models\User;
use Filament\Facades\Filament;

uses(Tests\TestCase::class)->in('Feature');

it('pretty config assets URL renders directly and loads', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Filament::setCurrentPanel('admin');

    $config = AssetConfiguration::factory()->create(['user_id' => $user->id]);

    $url = route('filament.admin.resources.assets.index.pretty', ['configuration' => $config->id]);
    $response = $this->get($url);
    $response->assertStatus(200);
});

it('pretty config dashboard URL renders directly and loads', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Filament::setCurrentPanel('admin');

    $config = AssetConfiguration::factory()->create(['user_id' => $user->id]);

    $url = route('filament.admin.pages.dashboard', ['configuration' => $config->id]);
    $response = $this->get($url);
    $response->assertStatus(200);
});

it('pretty simulation asset years URL renders directly and loads', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Filament::setCurrentPanel('admin');

    // We don't need actual records for route resolution
    $url = route('filament.admin.pages.simulation-asset-years', ['configuration' => 1, 'simulation' => 1, 'asset' => 1]);
    $response = $this->get($url);
    $response->assertStatus(200);
});

it('pretty nested simulation asset years URL renders directly and loads', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Filament::setCurrentPanel('admin');

    $url = route('filament.admin.pages.simulation-asset-years', ['configuration' => 1, 'simulation' => 1, 'asset' => 1]);
    $response = $this->get($url);
    $response->assertStatus(200);
});

it('pretty nested simulation assets URL returns 404 when records are missing', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Filament::setCurrentPanel('admin');

    $url = route('filament.admin.pages.simulation-assets', ['configuration' => 1, 'simulation' => 1]);
    $response = $this->get($url);
    $response->assertNotFound();
});

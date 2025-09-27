<?php

use App\Models\User;

uses(Tests\TestCase::class)->in('Feature');
use Filament\Facades\Filament;

it('renders pretty asset-years URL directly (no redirect) and loads', function () {
    // Ensure we have an authenticated user
    $user = User::query()->first() ?? User::factory()->create();
    $this->actingAs($user);

    Filament::setCurrentPanel('admin');

    $configurationId = 18;
    $assetId = 48;

    // Hit the pretty URL via Filament page route
    $url = route('filament.admin.pages.config-asset-years', ['configuration' => $configurationId, 'asset' => $assetId]);
    $response = $this->get($url);

    // Assert the page renders directly (no redirect)
    $response->assertStatus(200);
});

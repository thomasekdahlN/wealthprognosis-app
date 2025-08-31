<?php

use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetYear;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('events page loads successfully', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    Filament::setCurrentPanel('admin');

    $response = $this->withoutMiddleware()->get('/admin/events');

    $response->assertStatus(200);
});

test('events page shows assets with future years', function () {
    $user = User::factory()->create();
    $owner = AssetConfiguration::factory()->create(['user_id' => $user->id]);
    $asset = Asset::factory()->create([
        'user_id' => $user->id,
        'asset_owner_id' => $owner->id,
        'name' => 'Test Future Asset',
    ]);

    $currentYear = (int) date('Y');
    $futureYear = $currentYear + 1;

    // Create a future asset year
    AssetYear::factory()->create([
        'user_id' => $user->id,
        'asset_id' => $asset->id,
        'asset_owner_id' => $owner->id,
        'year' => $futureYear,
        'income_amount' => 50000,
        'expence_amount' => 10000,
        'asset_market_amount' => 500000,
        'mortgage_amount' => 200000,
        'income_description' => 'Future income description',
        'asset_description' => 'Future asset description',
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('admin');

    $response = $this->withoutMiddleware()->get('/admin/events');

    $response->assertStatus(200);
    $response->assertSee('Test Future Asset');
    $response->assertSee((string) $futureYear);
});

test('events page does not show assets with only current or past years', function () {
    $user = User::factory()->create();
    $owner = AssetConfiguration::factory()->create(['user_id' => $user->id]);
    $asset = Asset::factory()->create([
        'user_id' => $user->id,
        'asset_owner_id' => $owner->id,
        'name' => 'Test Past Asset',
    ]);

    $currentYear = (int) date('Y');
    $pastYear = $currentYear - 1;

    // Create a past asset year
    AssetYear::factory()->create([
        'user_id' => $user->id,
        'asset_id' => $asset->id,
        'asset_owner_id' => $owner->id,
        'year' => $pastYear,
        'income_amount' => 30000,
        'asset_description' => 'Past asset description',
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('admin');

    $response = $this->withoutMiddleware()->get('/admin/events');

    $response->assertStatus(200);
    $response->assertDontSee('Test Past Asset');
});

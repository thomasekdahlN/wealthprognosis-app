<?php

declare(strict_types=1);

use App\Models\AssetType;
use App\Support\TaxTypeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\artisan;

uses(TestCase::class, RefreshDatabase::class);

it('resolves tax type for a known asset type', function (): void {
    // Seed base data (users, teams, tax types)
    artisan('db:seed', ['--class' => Database\Seeders\TaxTypesFromConfigSeeder::class]);

    $user = \App\Models\User::first();
    $team = \App\Models\Team::first();

    // Create an asset type that points to an existing tax type from config (e.g., 'income')
    AssetType::create([
        'user_id' => $user->id,
        'team_id' => $team?->id,
        'type' => 'bank',
        'name' => 'Bank',
        'tax_type' => 'income',
    ]);

    $resolved = TaxTypeResolver::resolve('bank');
    expect($resolved)->toBe('income');
});

it('returns null for unknown asset type or missing relation', function (): void {
    // Ensure app is booted; no records required
    $resolved = TaxTypeResolver::resolve('nonexistent');
    expect($resolved)->toBeNull();
});

it('returns null for empty input', function (): void {
    expect(TaxTypeResolver::resolve(null))->toBeNull();
    expect(TaxTypeResolver::resolve(''))->toBeNull();
});

<?php

use App\Models\AssetType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create users first
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    // Create team
    $this->team = Team::factory()->create([
        'name' => 'Test Team',
        'owner_id' => $this->user->id,
    ]);

    // Attach user to team
    $this->user->teams()->attach($this->team->id);
    $this->user->update(['current_team_id' => $this->team->id]);

    // Seed asset types
    $this->seed(\Database\Seeders\TaxTypesFromConfigSeeder::class);
    $this->seed(\Database\Seeders\AssetTypeSeeder::class);
});

it('has tax_shield set to true for stock asset type', function () {
    $assetType = AssetType::where('type', 'stock')->first();

    expect($assetType)->not->toBeNull()
        ->and($assetType->tax_shield)->toBeTrue();
});

it('has tax_shield set to true for equityfund asset type', function () {
    $assetType = AssetType::where('type', 'equityfund')->first();

    expect($assetType)->not->toBeNull()
        ->and($assetType->tax_shield)->toBeTrue();
});

it('has tax_shield set to true for bondfund asset type', function () {
    $assetType = AssetType::where('type', 'bondfund')->first();

    expect($assetType)->not->toBeNull()
        ->and($assetType->tax_shield)->toBeTrue();
});

it('has tax_shield set to true for ask asset type', function () {
    $assetType = AssetType::where('type', 'ask')->first();

    expect($assetType)->not->toBeNull()
        ->and($assetType->tax_shield)->toBeTrue();
});

it('has tax_shield set to true for loantocompany asset type', function () {
    $assetType = AssetType::where('type', 'loantocompany')->first();

    expect($assetType)->not->toBeNull()
        ->and($assetType->tax_shield)->toBeTrue();
});

it('has tax_shield set to true for soleproprietorship asset type', function () {
    $assetType = AssetType::where('type', 'soleproprietorship')->first();

    expect($assetType)->not->toBeNull()
        ->and($assetType->tax_shield)->toBeTrue();
});

it('has tax_shield set to false for house asset type', function () {
    $assetType = AssetType::where('type', 'house')->first();

    expect($assetType)->not->toBeNull()
        ->and($assetType->tax_shield)->toBeFalse();
});

it('has tax_shield set to false for car asset type', function () {
    $assetType = AssetType::where('type', 'car')->first();

    expect($assetType)->not->toBeNull()
        ->and($assetType->tax_shield)->toBeFalse();
});

it('has tax_shield set to false for boat asset type', function () {
    $assetType = AssetType::where('type', 'boat')->first();

    expect($assetType)->not->toBeNull()
        ->and($assetType->tax_shield)->toBeFalse();
});

it('has tax_shield set to false for rental asset type', function () {
    $assetType = AssetType::where('type', 'rental')->first();

    expect($assetType)->not->toBeNull()
        ->and($assetType->tax_shield)->toBeFalse();
});

it('has tax_shield set to false for cabin asset type', function () {
    $assetType = AssetType::where('type', 'cabin')->first();

    expect($assetType)->not->toBeNull()
        ->and($assetType->tax_shield)->toBeFalse();
});

it('can query asset types with tax_shield using scope', function () {
    $taxShieldTypes = AssetType::taxShield()->pluck('type')->toArray();

    expect($taxShieldTypes)->toContain('stock')
        ->and($taxShieldTypes)->toContain('equityfund')
        ->and($taxShieldTypes)->toContain('bondfund')
        ->and($taxShieldTypes)->toContain('ask')
        ->and($taxShieldTypes)->toContain('loantocompany')
        ->and($taxShieldTypes)->toContain('soleproprietorship')
        ->and($taxShieldTypes)->not->toContain('house')
        ->and($taxShieldTypes)->not->toContain('car')
        ->and($taxShieldTypes)->not->toContain('boat');
});

it('can create an asset type with tax_shield set to true', function () {
    $assetType = AssetType::create([
        'type' => 'test_tax_shield',
        'name' => 'Test Tax Shield Asset',
        'description' => 'Test asset type with tax shield',
        'category' => 'Test',
        'icon' => 'heroicon-o-beaker',
        'color' => 'gray',
        'is_active' => true,
        'is_private' => true,
        'is_company' => false,
        'is_tax_optimized' => false,
        'is_liquid' => false,
        'tax_shield' => true,
        'can_generate_income' => false,
        'can_generate_expenses' => false,
        'can_have_mortgage' => false,
        'can_have_market_value' => false,
        'sort_order' => 999,
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    expect($assetType->tax_shield)->toBeTrue();
});

it('can create an asset type with tax_shield set to false', function () {
    $assetType = AssetType::create([
        'type' => 'test_no_tax_shield',
        'name' => 'Test No Tax Shield Asset',
        'description' => 'Test asset type without tax shield',
        'category' => 'Test',
        'icon' => 'heroicon-o-beaker',
        'color' => 'gray',
        'is_active' => true,
        'is_private' => true,
        'is_company' => false,
        'is_tax_optimized' => false,
        'is_liquid' => false,
        'tax_shield' => false,
        'can_generate_income' => false,
        'can_generate_expenses' => false,
        'can_have_mortgage' => false,
        'can_have_market_value' => false,
        'sort_order' => 999,
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    expect($assetType->tax_shield)->toBeFalse();
});

<?php

use App\Filament\System\Resources\Users\Pages\CreateUser;
use App\Filament\System\Resources\Users\Pages\EditUser;
use App\Filament\System\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('system');
});

it('lists all registered users for an admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $others = User::factory()->count(3)->create();

    actingAs($admin);

    livewire(ListUsers::class)
        ->assertCanSeeTableRecords(collect([$admin])->merge($others));
});

it('filters by system access (is_admin)', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $regular = User::factory()->create(['is_admin' => false]);

    actingAs($admin);

    livewire(ListUsers::class)
        ->filterTable('is_admin', true)
        ->assertCanSeeTableRecords([$admin])
        ->assertCanNotSeeTableRecords([$regular]);
});

it('creates a user with a hashed password', function () {
    actingAs(User::factory()->create(['is_admin' => true]));

    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Test User',
            'email' => 'new@example.com',
            'password' => 'secret-password',
            'is_admin' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(User::class, [
        'email' => 'new@example.com',
        'name' => 'Test User',
        'is_admin' => true,
    ]);

    $created = User::where('email', 'new@example.com')->first();
    expect($created)->not->toBeNull()
        ->and(Hash::check('secret-password', $created->password))->toBeTrue();
});

it('updates a user without changing the password when left blank', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create([
        'name' => 'Original',
        'is_admin' => false,
        'password' => Hash::make('original-password'),
    ]);

    actingAs($admin);

    livewire(EditUser::class, ['record' => $target->id])
        ->fillForm([
            'name' => 'Updated',
            'email' => $target->email,
            'password' => null,
            'is_admin' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $target->refresh();

    expect($target->name)->toBe('Updated')
        ->and($target->is_admin)->toBeTrue()
        ->and(Hash::check('original-password', $target->password))->toBeTrue();
});

it('validates required name, email, and password on create', function () {
    actingAs(User::factory()->create(['is_admin' => true]));

    livewire(CreateUser::class)
        ->fillForm([
            'name' => null,
            'email' => null,
            'password' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);
});

it('returns 200 on the users list page for an admin', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
        'app_authentication_secret' => 'test-secret',
    ]);

    actingAs($admin)
        ->get('/system/users')
        ->assertOk();
});

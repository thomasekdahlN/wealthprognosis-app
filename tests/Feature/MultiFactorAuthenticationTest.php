<?php

use App\Models\User;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('users table has app authentication columns', function (): void {
    $user = User::factory()->create([
        'app_authentication_secret' => 'secret-value',
        'app_authentication_recovery_codes' => 'code-1,code-2',
    ]);

    expect($user->fresh())
        ->app_authentication_secret->toBe('secret-value')
        ->app_authentication_recovery_codes->toBe('code-1,code-2');
});

it('user model implements filament MFA contracts', function (): void {
    $user = new User;

    expect($user)
        ->toBeInstanceOf(HasAppAuthentication::class)
        ->toBeInstanceOf(HasAppAuthenticationRecovery::class);
});

it('admin profile page is reachable for authenticated users', function (): void {
    $user = User::factory()->create();

    actingAs($user);

    $this->get('/admin/profile')->assertSuccessful();
});

it('system profile page is reachable for admin users with MFA configured', function (): void {
    $admin = User::factory()->create([
        'is_admin' => true,
        'app_authentication_secret' => 'test-secret',
    ]);

    actingAs($admin);

    $this->get('/system/profile')->assertSuccessful();
});

it('system panel forces admin without MFA to the setup flow', function (): void {
    $admin = User::factory()->create([
        'is_admin' => true,
        'app_authentication_secret' => null,
    ]);

    actingAs($admin);

    $this->get('/system/users')->assertRedirect();
});

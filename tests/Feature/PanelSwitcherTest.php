<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the system portal link in the admin user menu for admins', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertStatus(200)
        ->assertSee('System portal');
});

it('hides the system portal link from non-admins in the admin user menu', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertStatus(200)
        ->assertDontSee('System portal');
});

it('shows the user portal link in the system user menu', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->followingRedirects()
        ->get('/system')
        ->assertStatus(200)
        ->assertSee('User portal');
});

it('forbids non-admins from accessing the system panel', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/system')
        ->assertForbidden();
});

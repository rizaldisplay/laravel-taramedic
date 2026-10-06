<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests from admin to login', function () {
    $this->get('/admin')->assertRedirect('/login');
});

it('redirects guests from operator to login', function () {
    $this->get('/operator')->assertRedirect('/login');
});

it('allows public access to kiosk and display', function () {
    $this->get('/kiosk')->assertSuccessful();
    $this->get('/display')->assertSuccessful();
});

it('allows admin to access admin dashboard', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful()
        ->assertSee('data-queue-realtime="admin"', false);
});

it('forbids operator from admin dashboard', function () {
    $operator = User::factory()->create();

    $this->actingAs($operator)
        ->get('/admin')
        ->assertForbidden();
});

it('allows operator to access operator panel', function () {
    $operator = User::factory()->create();

    $this->actingAs($operator)
        ->get('/operator')
        ->assertSuccessful()
        ->assertSee('data-queue-realtime="operator"', false);
});

it('allows admin to access operator panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/operator')
        ->assertSuccessful();
});

it('logs in admin and redirects to dashboard', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin@example.test',
        'password' => 'password',
    ]);

    Livewire\Livewire::test('auth.login')
        ->set('email', 'admin@example.test')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($admin);
});

it('logs in operator and redirects to operator panel', function () {
    $operator = User::factory()->create([
        'email' => 'operator@example.test',
        'password' => 'password',
    ]);

    Livewire\Livewire::test('auth.login')
        ->set('email', 'operator@example.test')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect('/operator');

    $this->assertAuthenticatedAs($operator);
});

it('allows admin to access admin crud pages', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $this->get('/admin/settings')->assertSuccessful();
    $this->get('/admin/display-tv')->assertSuccessful();
    $this->get('/admin/services')->assertSuccessful();
    $this->get('/admin/counters')->assertSuccessful();
});

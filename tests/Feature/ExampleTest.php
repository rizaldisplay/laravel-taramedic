<?php

use App\Models\User;

test('the application redirects home to admin dashboard', function () {
    $this->get('/')->assertRedirect('/admin');
});

test('guest visiting admin is redirected to login', function () {
    $this->get('/admin')->assertRedirect('/login');
});

test('admin dashboard is accessible for authenticated admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful();
});

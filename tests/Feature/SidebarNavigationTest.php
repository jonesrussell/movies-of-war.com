<?php

declare(strict_types=1);

use App\Models\User;

test('admin dashboard page includes northcloud navigation props', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('northcloud')
        ->has('northcloud.navigation')
    );
});

test('non-admin dashboard page includes northcloud navigation props', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('northcloud')
    );
});

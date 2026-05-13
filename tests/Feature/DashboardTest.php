<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->withSession([
            'tahun_anggaran' => 2026,
        ]);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
});
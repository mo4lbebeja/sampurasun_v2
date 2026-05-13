<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;
use App\Models\Role;
use App\Models\UnitKerja;
use Illuminate\Support\Facades\Hash;

function createActiveUserForAuthTest(): User
{
    $role = Role::query()->where('name', 'admin')->first();

    if (! $role) {
        $role = new Role();
        $role->name = 'admin';
        $role->display_name = 'Administrator';
        $role->description = 'Administrator';
        $role->save();
    }

    $unitKerja = UnitKerja::query()->where('kode', 'TEST')->first();

    if (! $unitKerja) {
        $unitKerja = new UnitKerja();
        $unitKerja->kode = 'TEST';
        $unitKerja->nama = 'Unit Test';
        $unitKerja->is_active = true;
        $unitKerja->save();
    }

    return User::factory()->create([
        'role_id' => $role->id,
        'unit_kerja_id' => $unitKerja->id,
        'email_verified_at' => now(),
        'password' => Hash::make('password'),
        'is_active' => true,
    ]);
}

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = createActiveUserForAuthTest();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);

    $response->assertRedirect();
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    //
})->skip('Two-factor authentication belum digunakan pada aplikasi ini.');

test('users can not authenticate with invalid password', function () {
    $user = createActiveUserForAuthTest();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = createActiveUserForAuthTest();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();

    $response->assertRedirect();
});

test('users are rate limited', function () {
    $user = createActiveUserForAuthTest();

    for ($i = 0; $i < 6; $i++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429);
});

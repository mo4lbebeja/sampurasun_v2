<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@evpengadaan.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role_id' => 1,
                'unit_kerja_id' => 1,
            ]
        );

        User::updateOrCreate(
            ['email' => 'pptk@evpengadaan.test'],
            [
                'name' => 'PPTK',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role_id' => 2,
                'unit_kerja_id' => 1,
            ]
        );

        User::updateOrCreate(
            ['email' => 'sarana@evpengadaan.test'],
            [
                'name' => 'Sarana Umum',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role_id' => 3,
                'unit_kerja_id' => 1,
            ]
        );
    }
}
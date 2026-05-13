<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    public function definition(): array
    {
        $name = 'role_' . fake()->unique()->numberBetween(100000, 999999);

        return [
            'name' => $name,
            'display_name' => ucwords(str_replace('_', ' ', $name)),
        ];
    }
}
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UnitKerjaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => 'UK-' . fake()->unique()->numberBetween(1000, 999999),
            'nama' => fake()->company(),
        ];
    }
}
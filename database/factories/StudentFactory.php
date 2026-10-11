<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'codigo' => fake()->unique()->numerify('202######'),
            'documento' => fake()->unique()->numerify('1#########'),
            'cohorte' => fake()->randomElement(['2020-1', '2021-1', '2022-1', '2023-1']),
            'estado' => fake()->randomElement(['Activo', 'Activo', 'Activo', 'En riesgo', 'Intercambio']),
            'fecha_ingreso' => fake()->date(),
        ];
    }
}

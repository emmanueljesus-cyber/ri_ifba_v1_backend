<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'matricula'         => fake()->unique()->numerify('####'),
            'nome'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'perfil'            => fake()->randomElement(['estudante', 'admin']),
            'bolsista'          => false,
            'limite_faltas_mes' => 3,
            'desligado'         => false,
            'curso'             => fake()->word(),
            'turno'             => fake()->randomElement(['matutino', 'vespertino', 'noturno']),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Cria um usuário admin
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'perfil' => 'admin',
            'bolsista' => false,
        ]);
    }

    /**
     * Cria um estudante bolsista
     */
    public function bolsista(): static
    {
        return $this->state(fn (array $attributes) => [
            'perfil' => 'estudante',
            'bolsista' => true,
        ]);
    }

    /**
     * Cria um estudante não bolsista
     */
    public function naoBolsista(): static
    {
        return $this->state(fn (array $attributes) => [
            'perfil' => 'estudante',
            'bolsista' => false,
        ]);
    }
}

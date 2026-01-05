<?php

namespace Database\Factories;

use App\Models\Cardapio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cardapio>
 */
class CardapioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'data_do_cardapio'      => fake()->dateTimeBetween('-1 month', '+1 month'),
            'prato_principal_ptn01' => fake()->word(),
            'prato_principal_ptn02' => fake()->word(),
            'guarnicao'             => fake()->word(),
            'acompanhamento_01'     => fake()->word(),
            'acompanhamento_02'     => fake()->word(),
            'salada'                => fake()->word(),
            'ovo_lacto_vegetariano' => fake()->word(),
            'suco'                  => fake()->word(),
            'sobremesa'             => fake()->word(),
            'criado_por'            => User::factory(),
        ];
    }
}

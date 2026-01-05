<?php

namespace Database\Factories;

use App\Models\Refeicao;
use App\Models\Cardapio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Refeicao>
 */
class RefeicaoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cardapio = Cardapio::factory()->create();

        return [
            'cardapio_id'      => $cardapio->id,
            'data_do_cardapio' => $cardapio->data_do_cardapio, // ✅ Usa a data do cardápio vinculado
            'turno'            => fake()->randomElement(['almoco', 'jantar']),
            'capacidade'       => fake()->numberBetween(50, 200),
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\Refeicao;
use App\Models\Cardapio;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class RefeicaoSeeder extends Seeder
{
    public function run(): void
    {
        $cardapios = Cardapio::all();

        foreach ($cardapios as $cardapio) {
            // Almoço - usa firstOrCreate para evitar duplicatas
            Refeicao::firstOrCreate(
                [
                    'cardapio_id' => $cardapio->id,
                    'turno' => 'almoco',
                ],
                [
                    'data_do_cardapio' => $cardapio->data_do_cardapio,
                    'capacidade' => 100,
                ]
            );

            // Jantar - usa firstOrCreate para evitar duplicatas
            Refeicao::firstOrCreate(
                [
                    'cardapio_id' => $cardapio->id,
                    'turno' => 'jantar',
                ],
                [
                    'data_do_cardapio' => $cardapio->data_do_cardapio,
                    'capacidade' => 100,
                ]
            );
        }
    }
}

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
            // Almoço
            Refeicao::create([
                'cardapio_id' => $cardapio->id,
                'data_do_cardapio' => $cardapio->data_do_cardapio,
                'turno' => 'almoco',
                'capacidade' => 100,
            ]);

            // Jantar
            Refeicao::create([
                'cardapio_id' => $cardapio->id,
                'data_do_cardapio' => $cardapio->data_do_cardapio,
                'turno' => 'jantar',
                'capacidade' => 100,
            ]);
        }
    }
}

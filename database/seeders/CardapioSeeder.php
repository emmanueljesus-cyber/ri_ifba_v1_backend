<?php

namespace Database\Seeders;

use App\Models\Cardapio;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CardapioSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('perfil', 'admin')->first();

        // Cardápios da semana (segunda a sexta)
        $cardapios = [
            [
                'data' => Carbon::now()->startOfWeek()->addDay(), // Segunda
                'prato1' => 'Frango Grelhado',
                'prato2' => 'Carne Moída',
                'guarnicao' => 'Batata Corada',
                'acomp1' => 'Arroz Branco',
                'acomp2' => 'Feijão Preto',
                'salada' => 'Salada de Alface e Tomate',
                'vegetariano' => 'Strogonoff de Legumes',
                'suco' => 'Suco de Laranja',
                'sobremesa' => 'Banana',
            ],
            [
                'data' => Carbon::now()->startOfWeek()->addDays(2), // Terça
                'prato1' => 'Filé de Peixe',
                'prato2' => 'Almôndega ao Molho',
                'guarnicao' => 'Purê de Batata',
                'acomp1' => 'Arroz Branco',
                'acomp2' => 'Feijão Carioca',
                'salada' => 'Salada de Repolho',
                'vegetariano' => 'Lasanha de Legumes',
                'suco' => 'Suco de Maracujá',
                'sobremesa' => 'Maçã',
            ],
            [
                'data' => Carbon::now()->startOfWeek()->addDays(3), // Quarta
                'prato1' => 'Bife Acebolado',
                'prato2' => 'Frango ao Molho',
                'guarnicao' => 'Farofa',
                'acomp1' => 'Arroz Branco',
                'acomp2' => 'Feijão Preto',
                'salada' => 'Salada de Cenoura',
                'vegetariano' => 'Hambúrguer de Grão de Bico',
                'suco' => 'Suco de Acerola',
                'sobremesa' => 'Melancia',
            ],
            [
                'data' => Carbon::now()->startOfWeek()->addDays(4), // Quinta
                'prato1' => 'Frango Xadrez',
                'prato2' => 'Carne de Panela',
                'guarnicao' => 'Macarrão ao Alho e Óleo',
                'acomp1' => 'Arroz Branco',
                'acomp2' => 'Feijão Carioca',
                'salada' => 'Salada de Beterraba',
                'vegetariano' => 'Risoto de Legumes',
                'suco' => 'Suco de Goiaba',
                'sobremesa' => 'Laranja',
            ],
            [
                'data' => Carbon::now()->startOfWeek()->addDays(5), // Sexta
                'prato1' => 'Tilápia Grelhada',
                'prato2' => 'Frango Assado',
                'guarnicao' => 'Polenta Frita',
                'acomp1' => 'Arroz Branco',
                'acomp2' => 'Feijão Preto',
                'salada' => 'Salada Verde Mista',
                'vegetariano' => 'Torta de Legumes',
                'suco' => 'Suco de Caju',
                'sobremesa' => 'Gelatina',
            ],
        ];

        foreach ($cardapios as $cardapio) {
            Cardapio::create([
                'data_do_cardapio' => $cardapio['data'],
                'prato_principal_ptn01' => $cardapio['prato1'],
                'prato_principal_ptn02' => $cardapio['prato2'],
                'guarnicao' => $cardapio['guarnicao'],
                'acompanhamento_01' => $cardapio['acomp1'],
                'acompanhamento_02' => $cardapio['acomp2'],
                'salada' => $cardapio['salada'],
                'ovo_lacto_vegetariano' => $cardapio['vegetariano'],
                'suco' => $cardapio['suco'],
                'sobremesa' => $cardapio['sobremesa'],
                'criado_por' => $admin->id,
            ]);
        }
    }
}

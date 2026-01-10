<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Cardapio;
use App\Models\Refeicao;
use App\Models\Presenca;
use App\Enums\TurnoRefeicao;
use App\Enums\StatusPresenca;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PresencaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Busca o admin já criado
        $admin = User::where('perfil', 'admin')->first();

        // Busca bolsistas existentes
        $bolsistas = User::where('bolsista', true)->where('desligado', false)->get();
        
        if ($bolsistas->isEmpty()) {
            $this->command->warn('⚠️ Nenhum bolsista encontrado. Execute UserSeeder primeiro.');
            return;
        }

        // Cria cardápios para os próximos 7 dias se não existirem
        $dataInicio = now();
        for ($i = 0; $i < 7; $i++) {
            $data = $dataInicio->copy()->addDays($i);

            // Verificar se cardápio já existe
            $cardapio = Cardapio::where('data_do_cardapio', $data->format('Y-m-d'))->first();

            if (!$cardapio) {
                $cardapio = Cardapio::create([
                    'data_do_cardapio' => $data->format('Y-m-d'),
                    'prato_principal_ptn01' => $this->getPratoPrincipal($i),
                    'prato_principal_ptn02' => $this->getPratoVegetariano($i),
                    'guarnicao' => $this->getGuarnicao($i),
                    'acompanhamento_01' => 'Arroz Branco',
                    'acompanhamento_02' => 'Feijão Carioca',
                    'salada' => 'Salada Verde',
                    'ovo_lacto_vegetariano' => $this->getPratoVegetariano($i),
                    'suco' => $this->getSuco($i),
                    'sobremesa' => $this->getSobremesa($i),
                ]);
            }

            // Buscar ou criar refeições
            $refeicaoAlmoco = Refeicao::where('cardapio_id', $cardapio->id)
                ->where('turno', TurnoRefeicao::ALMOCO)
                ->first();

            if (!$refeicaoAlmoco) {
                $refeicaoAlmoco = Refeicao::create([
                    'cardapio_id' => $cardapio->id,
                    'data_do_cardapio' => $data->format('Y-m-d'),
                    'turno' => TurnoRefeicao::ALMOCO,
                    'capacidade' => 100,
                ]);
            }

            $refeicaoJantar = Refeicao::where('cardapio_id', $cardapio->id)
                ->where('turno', TurnoRefeicao::JANTAR)
                ->first();

            if (!$refeicaoJantar) {
                $refeicaoJantar = Refeicao::create([
                    'cardapio_id' => $cardapio->id,
                    'data_do_cardapio' => $data->format('Y-m-d'),
                    'turno' => TurnoRefeicao::JANTAR,
                    'capacidade' => 80,
                ]);
            }

            // Para os últimos 3 dias e hoje, criar presenças
            if ($i <= 3) {
                foreach ($bolsistas as $bolsista) {
                    // Almoço - 90% de chance
                    if (rand(0, 100) > 10) {
                        Presenca::create([
                            'user_id' => $bolsista->id,
                            'refeicao_id' => $refeicaoAlmoco->id,
                            'status_da_presenca' => $this->getStatusAleatorio($i),
                            'registrado_em' => $data->copy()->setTime(10, rand(0, 59)),
                            'validado_em' => $i >= 1 ? $data->copy()->setTime(11, rand(30, 59)) : null,
                            'validado_por' => $i >= 1 ? $admin->id : null,
                        ]);
                    }

                    // Jantar - 80% de chance
                    if (rand(0, 100) > 20) {
                        Presenca::create([
                            'user_id' => $bolsista->id,
                            'refeicao_id' => $refeicaoJantar->id,
                            'status_da_presenca' => $this->getStatusAleatorio($i),
                            'registrado_em' => $data->copy()->setTime(16, rand(0, 59)),
                            'validado_em' => $i >= 1 ? $data->copy()->setTime(17, rand(30, 59)) : null,
                            'validado_por' => $i >= 1 ? $admin->id : null,
                        ]);
                    }
                }
            }
        }

        $totalPresencas = Presenca::count();
        $this->command->info('✅ Presenças criadas com sucesso!');
        $this->command->info("📊 Total de presenças: {$totalPresencas}");
    }

    private function getPratoPrincipal($dia): string
    {
        $pratos = [
            'Feijoada com Linguiça',
            'Frango Grelhado',
            'Peixe Assado',
            'Carne de Panela com Batata',
            'Lasanha de Frango',
            'Estrogonofe de Carne',
            'Filé de Frango ao Molho Branco',
        ];

        return $pratos[$dia] ?? 'Prato Principal';
    }

    private function getGuarnicao($dia): string
    {
        $guarnicoes = [
            'Couve Refogada',
            'Batata Frita',
            'Legumes ao Vapor',
            'Farofa',
            'Purê de Batata',
            'Batata Sauté',
            'Brócolis no Alho',
        ];

        return $guarnicoes[$dia] ?? 'Guarnição';
    }

    private function getSobremesa($dia): string
    {
        $sobremesas = [
            'Laranja',
            'Banana',
            'Melancia',
            'Maçã',
            'Pudim',
            'Gelatina',
            'Salada de Frutas',
        ];

        return $sobremesas[$dia] ?? 'Fruta da Época';
    }

    private function getPratoVegetariano($dia): string
    {
        $pratos = [
            'Proteína de Soja',
            'Omelete de Legumes',
            'Tofu Grelhado',
            'Hambúrguer de Grão de Bico',
            'Quibe de Berinjela',
            'Lasanha de Berinjela',
            'Estrogonofe de Cogumelos',
        ];

        return $pratos[$dia] ?? 'Opção Vegetariana';
    }

    private function getSuco($dia): string
    {
        $sucos = [
            'Suco de Laranja',
            'Suco de Limão',
            'Suco de Maracujá',
            'Suco de Abacaxi',
            'Suco de Acerola',
            'Suco de Caju',
            'Suco de Goiaba',
        ];

        return $sucos[$dia] ?? 'Suco Natural';
    }

    private function getStatusAleatorio($dia): StatusPresenca
    {
        // Para dias passados (dia > 0), a maioria já foi processada
        if ($dia > 0) {
            $rand = rand(0, 100);
            if ($rand < 70) return StatusPresenca::PRESENTE;
            if ($rand < 85) return StatusPresenca::FALTA_JUSTIFICADA;
            if ($rand < 95) return StatusPresenca::FALTA_INJUSTIFICADA;
            return StatusPresenca::CANCELADO;
        }

        // Para hoje (dia 0), a maioria está presente
        $rand = rand(0, 100);
        if ($rand < 90) return StatusPresenca::PRESENTE;
        return StatusPresenca::CANCELADO;
    }
}

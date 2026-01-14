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

        if (!$admin) {
            $this->command->warn('⚠️ Admin não encontrado. Execute UserSeeder primeiro.');
            return;
        }

        // Busca bolsistas existentes
        $bolsistas = User::where('bolsista', true)->where('desligado', false)->get();

        if ($bolsistas->isEmpty()) {
            $this->command->warn('⚠️ Nenhum bolsista encontrado. Execute UserSeeder primeiro.');
            return;
        }

        // Busca refeições existentes (criadas pelo CardapioMensalSeeder ou CardapioSeeder + RefeicaoSeeder)
        $refeicoes = Refeicao::with('cardapio')
            ->orderBy('data_do_cardapio', 'asc')
            ->take(7) // Pega as próximas 7 refeições
            ->get();

        if ($refeicoes->isEmpty()) {
            $this->command->warn('⚠️ Nenhuma refeição encontrada. Execute CardapioSeeder/CardapioMensalSeeder e RefeicaoSeeder primeiro.');
            return;
        }

        $this->command->info("📋 Criando presenças para {$refeicoes->count()} refeições...");

        $presencasCriadas = 0;
        $diasProcessados = [];

        // Agrupa refeições por data
        foreach ($refeicoes as $refeicao) {
            $data = $refeicao->data_do_cardapio->format('Y-m-d'); // Converter para string

            if (!isset($diasProcessados[$data])) {
                $diasProcessados[$data] = [
                    'almoco' => null,
                    'jantar' => null,
                ];
            }

            if ($refeicao->turno->value === 'almoco') {
                $diasProcessados[$data]['almoco'] = $refeicao;
            } else {
                $diasProcessados[$data]['jantar'] = $refeicao;
            }
        }

        // Criar presenças para os 4 últimos dias (simulando histórico)
        $diasComPresenca = array_slice($diasProcessados, 0, 4, true);
        $i = 0;

        foreach ($diasComPresenca as $data => $refeicoesDoDia) {
            $dataCarbon = now()->parse($data);

            foreach ($bolsistas as $bolsista) {
                // Almoço - 90% de chance de comparecer
                if ($refeicoesDoDia['almoco'] && rand(0, 100) > 10) {
                    Presenca::create([
                        'user_id' => $bolsista->id,
                        'refeicao_id' => $refeicoesDoDia['almoco']->id,
                        'status_da_presenca' => $this->getStatusAleatorio($i),
                        'registrado_em' => $dataCarbon->copy()->setTime(10, rand(0, 59)),
                        'validado_em' => $i >= 1 ? $dataCarbon->copy()->setTime(11, rand(30, 59)) : null,
                        'validado_por' => $i >= 1 ? $admin->id : null,
                    ]);
                    $presencasCriadas++;
                }

                // Jantar - 80% de chance de comparecer
                if ($refeicoesDoDia['jantar'] && rand(0, 100) > 20) {
                    Presenca::create([
                        'user_id' => $bolsista->id,
                        'refeicao_id' => $refeicoesDoDia['jantar']->id,
                        'status_da_presenca' => $this->getStatusAleatorio($i),
                        'registrado_em' => $dataCarbon->copy()->setTime(16, rand(0, 59)),
                        'validado_em' => $i >= 1 ? $dataCarbon->copy()->setTime(17, rand(30, 59)) : null,
                        'validado_por' => $i >= 1 ? $admin->id : null,
                    ]);
                    $presencasCriadas++;
                }
            }

            $i++;
        }

        $this->command->info("✅ {$presencasCriadas} presenças criadas com sucesso!");
        $this->command->info("📊 Distribuição:");
        $this->command->info("   - {$bolsistas->count()} bolsistas");
        $this->command->info("   - " . count($diasComPresenca) . " dias com presenças");
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

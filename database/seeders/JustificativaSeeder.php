<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Refeicao;
use App\Models\Justificativa;
use App\Models\Presenca;
use Illuminate\Database\Seeder;

class JustificativaSeeder extends Seeder
{
    public function run(): void
    {
        $faltasJustificadas = Presenca::where('status_da_presenca', 'falta_justificada')->get();

        $motivosAntecipada = [
            'Consulta médica agendada, conforme atestado médico anexo',
            'Participação em atividade curricular fora do campus',
            'Visita técnica organizada pelo curso',
            'Participação em evento acadêmico externo',
            'Atividade de extensão do curso realizada externamente',
            'Compromisso religioso inadiável',
        ];

        $motivosPosterior = [
            'Ausência relativa à saúde, estava com febre e mal estar geral',
            'Problema de saúde súbito que impediu comparecimento',
            'Atendimento médico de emergência',
            'Falecimento de familiar, conforme atestado de óbito',
            'Luto familiar - falecimento de parente próximo',
            'Ausência devido ao velório de familiar',
            'Cancelamento de todas as aulas do dia conforme declaração da coordenação',
            'Não houve aula no dia da falta conforme comunicado oficial',
        ];

        foreach ($faltasJustificadas as $falta) {
            $tipo = rand(0, 1) == 0 ? 'antecipada' : 'posterior';
            $motivos = $tipo == 'antecipada' ? $motivosAntecipada : $motivosPosterior;

            Justificativa::create([
                'user_id' => $falta->user_id,
                'refeicao_id' => $falta->refeicao_id,
                'tipo' => $tipo,
                'motivo' => $motivos[array_rand($motivos)],
                'anexo' => rand(1, 100) <= 80 ? 'justificativa_' . rand(1000, 9999) . '.pdf' : null,
                'enviado_em' => now()->subHours(rand(1, 48)),
            ]);
        }
    }
}

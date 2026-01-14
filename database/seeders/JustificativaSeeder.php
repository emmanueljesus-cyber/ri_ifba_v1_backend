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

        // ========================================
        // JUSTIFICATIVAS OFICIAIS - AUSÊNCIA POSTERIOR
        // (Informadas posteriormente à data da ausência)
        // Obs: Todas devem conter nome completo e data coincidente
        // Prazo: até penúltimo dia letivo do mês (ou 14h do último dia via DEPAE)
        // ========================================
        $motivosPosterior = [
            'Ausência relativa à saúde discente, estava com febre e mal-estar geral. Atestado médico anexo.',
            'Ausência relativa à saúde discente devido a consulta médica de emergência. Atestado médico anexo.',
            'Ausência relativa à saúde discente por motivo de doença. Atestado médico anexo comprovando a condição.',
            'Ausência devido ao cancelamento de todas as aulas referente ao mesmo dia da falta no refeitório. Declaração da coordenação do curso anexa.',
            'Ausência devido à atividade curricular realizada fora do campus (visita técnica). Declaração da coordenação do curso anexa.',
            'Ausência devido à atividade curricular realizada fora do campus (aula de campo). Declaração da coordenação do curso anexa.',
            'Ausência devido ao falecimento de familiar (avô/avó). Atestado de óbito anexo.',
            'Ausência devido ao falecimento de familiar (tio/tia). Certidão de óbito anexa.',
            'Ausência devido à atividade religiosa em evento da igreja. Declaração da entidade religiosa anexa.',
            'Ausência devido à atividade religiosa em cerimônia especial. Declaração da entidade religiosa anexa comprovando participação.',
        ];

        // ========================================
        // JUSTIFICATIVAS OFICIAIS - AUSÊNCIA PROGRAMADA
        // (Informadas com antecedência - NÃO PRECISA DE ATESTADO)
        // Prazo: até horário de funcionamento do refeitório
        //   - Almoço: até 13:30h do dia da ausência
        //   - Jantar: até 19:00h do dia da ausência
        // ========================================
        $motivosAntecipada = [
            'Não poderei comparecer ao refeitório devido a consulta médica agendada.',
            'Não poderei comparecer ao refeitório devido a compromisso familiar inadiável.',
            'Não poderei comparecer ao refeitório pois participarei de atividade externa programada.',
            'Não poderei comparecer ao refeitório devido a visita técnica agendada pelo curso.',
            'Não poderei comparecer ao refeitório pois estarei em atividade curricular fora do campus.',
            'Não poderei comparecer ao refeitório devido a compromisso religioso programado.',
            'Não poderei comparecer ao refeitório pois estarei em evento acadêmico externo.',
            'Não poderei comparecer ao refeitório devido a viagem familiar programada.',
        ];

        foreach ($faltasJustificadas as $falta) {
            // 60% posterior (com anexo), 40% antecipada (sem anexo)
            $tipo = rand(1, 100) <= 60 ? 'posterior' : 'antecipada';
            $motivos = $tipo == 'posterior' ? $motivosPosterior : $motivosAntecipada;

            // Data de envio baseada no tipo
            if ($tipo == 'posterior') {
                // Enviada entre 1 a 48 horas APÓS a falta
                $enviadoEm = now()->subHours(rand(1, 48));
            } else {
                // Enviada entre 1 a 24 horas ANTES da falta
                $enviadoEm = now()->subHours(rand(25, 48));
            }

            Justificativa::create([
                'user_id' => $falta->user_id,
                'refeicao_id' => $falta->refeicao_id,
                'tipo' => $tipo,
                'motivo' => $motivos[array_rand($motivos)],
                // Posterior: sempre tem anexo (atestado/declaração)
                // Antecipada: não precisa de anexo
                'anexo' => $tipo == 'posterior' ? 'justificativa_' . rand(1000, 9999) . '.pdf' : null,
                'enviado_em' => $enviadoEm,
            ]);
        }

        $total = Justificativa::count();
        $this->command->info("✅ {$total} justificativas criadas (baseadas nas regras oficiais DEPAE)");
    }
}

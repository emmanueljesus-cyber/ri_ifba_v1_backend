<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Refeicao;
use App\Models\Justificativa;
use App\Models\Presenca;
use App\Enums\StatusJustificativa;
use App\Enums\TipoJustificativa;
use App\Enums\StatusPresenca;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class JustificativaSeeder extends Seeder
{
    private array $motivosPosterior = [
        ['motivo' => 'Ausência relativa à saúde discente, estava com febre e mal-estar geral.', 'tipo_doc' => 'atestado_medico'],
        ['motivo' => 'Ausência relativa à saúde discente devido a consulta médica de emergência.', 'tipo_doc' => 'atestado_medico'],
        ['motivo' => 'Ausência relativa à saúde discente por motivo de doença.', 'tipo_doc' => 'atestado_medico'],
        ['motivo' => 'Ausência devido ao cancelamento de todas as aulas referente ao mesmo dia da falta no refeitório.', 'tipo_doc' => 'declaracao_coordenacao'],
        ['motivo' => 'Ausência devido à atividade curricular realizada fora do campus (visita técnica).', 'tipo_doc' => 'declaracao_coordenacao'],
        ['motivo' => 'Ausência devido à atividade curricular realizada fora do campus (aula de campo).', 'tipo_doc' => 'declaracao_coordenacao'],
        ['motivo' => 'Ausência devido ao falecimento de familiar (avô/avó).', 'tipo_doc' => 'atestado_obito'],
        ['motivo' => 'Ausência devido ao falecimento de familiar (tio/tia).', 'tipo_doc' => 'atestado_obito'],
        ['motivo' => 'Ausência devido à atividade religiosa em evento da igreja.', 'tipo_doc' => 'declaracao_religiosa'],
        ['motivo' => 'Ausência devido à atividade religiosa em cerimônia especial.', 'tipo_doc' => 'declaracao_religiosa'],
    ];

    private array $motivosAntecipada = [
        'Não poderei comparecer ao refeitório devido a consulta médica agendada.',
        'Não poderei comparecer ao refeitório devido a compromisso familiar inadiável.',
        'Não poderei comparecer ao refeitório pois participarei de atividade externa programada.',
        'Não poderei comparecer ao refeitório devido a visita técnica agendada pelo curso.',
        'Não poderei comparecer ao refeitório pois estarei em atividade curricular fora do campus.',
        'Não poderei comparecer ao refeitório devido a compromisso religioso programado.',
        'Não poderei comparecer ao refeitório pois estarei em evento acadêmico externo.',
        'Não poderei comparecer ao refeitório devido a viagem familiar programada.',
    ];

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('📄 CRIANDO JUSTIFICATIVAS COM DOCUMENTOS');
        $this->command->info('=========================================');

        // Criar pasta de justificativas se não existir (no disco padrão - local)
        Storage::makeDirectory('justificativas');

        // Buscar admin para avaliação
        $admin = User::where('perfil', 'admin')->first();

        // Buscar faltas justificadas
        $faltasJustificadas = Presenca::where('status_da_presenca', StatusPresenca::FALTA_JUSTIFICADA)
            ->with(['user', 'refeicao.cardapio'])
            ->get();

        $this->command->info("   📋 {$faltasJustificadas->count()} faltas justificadas encontradas");

        $justificativasCriadas = 0;
        $anexosCriados = 0;
        $pendentes = 0;
        $aprovadas = 0;
        $rejeitadas = 0;

        foreach ($faltasJustificadas as $falta) {
            // Verificar se já existe justificativa
            $existente = Justificativa::where('user_id', $falta->user_id)
                ->where('refeicao_id', $falta->refeicao_id)
                ->exists();

            if ($existente) {
                continue;
            }

            $user = $falta->user;
            $refeicao = $falta->refeicao;
            $dataFalta = $refeicao?->cardapio?->data_do_cardapio;

            if (!$user || !$refeicao || !$dataFalta) {
                continue;
            }

            // 70% posterior (com anexo), 30% antecipada (sem anexo)
            $tipo = rand(1, 100) <= 70 ? TipoJustificativa::POSTERIOR : TipoJustificativa::ANTECIPADA;

            if ($tipo === TipoJustificativa::POSTERIOR) {
                $motivoData = $this->motivosPosterior[array_rand($this->motivosPosterior)];
                $motivo = $motivoData['motivo'] . ' Atestado/declaração anexo.';
                $tipoDoc = $motivoData['tipo_doc'];

                // Criar PDF de documento
                $nomeArquivo = $this->criarPdfReal($user, $dataFalta->format('Y-m-d'), $tipoDoc);
                $anexosCriados++;

                // Enviado entre 24h e 72h após a falta
                $enviadoEm = Carbon::parse($dataFalta)->addHours(rand(24, 72));
            } else {
                $motivo = $this->motivosAntecipada[array_rand($this->motivosAntecipada)];
                $nomeArquivo = null;

                // Enviado entre 2h e 24h antes da refeição
                $enviadoEm = Carbon::parse($dataFalta)->subHours(rand(2, 24));
            }

            // Determinar status da justificativa
            // 60% aprovada, 30% pendente, 10% rejeitada
            $dataLimite = Carbon::create(2026, 1, 28);
            $rand = rand(1, 100);

            if ($rand <= 60) {
                $status = StatusJustificativa::APROVADA;
                $avaliadoPor = $admin?->id;
                $avaliadoEm = $enviadoEm->copy()->addHours(rand(1, 48));
                $motivoRejeicao = null;
                $aprovadas++;
            } elseif ($rand <= 90) {
                $status = StatusJustificativa::PENDENTE;
                $avaliadoPor = null;
                $avaliadoEm = null;
                $motivoRejeicao = null;
                $pendentes++;
            } else {
                $status = StatusJustificativa::REJEITADA;
                $avaliadoPor = $admin?->id;
                $avaliadoEm = $enviadoEm->copy()->addHours(rand(1, 48));
                $motivoRejeicao = $this->gerarMotivoRejeicao();
                $rejeitadas++;
            }

            Justificativa::create([
                'user_id' => $falta->user_id,
                'refeicao_id' => $falta->refeicao_id,
                'tipo' => $tipo,
                'motivo' => $motivo,
                'anexo' => $nomeArquivo,
                'enviado_em' => $enviadoEm,
                'status' => $status,
                'avaliado_por' => $avaliadoPor,
                'avaliado_em' => $avaliadoEm,
                'motivo_rejeicao' => $motivoRejeicao,
            ]);

            $justificativasCriadas++;

            // Log a cada 50 justificativas
            if ($justificativasCriadas % 50 === 0) {
                $this->command->info("   📄 {$justificativasCriadas} justificativas criadas...");
            }
        }

        $this->command->info('');
        $this->command->info('✅ Justificativas criadas com sucesso!');
        $this->command->info("📊 Resumo:");
        $this->command->info("   - Total de justificativas: {$justificativasCriadas}");
        $this->command->info("   - ✅ Aprovadas: {$aprovadas}");
        $this->command->info("   - ⏳ Pendentes: {$pendentes}");
        $this->command->info("   - ❌ Rejeitadas: {$rejeitadas}");
        $this->command->info("   - 📎 Arquivos PDF gerados: {$anexosCriados}");
        $this->command->info("   - 📁 Local: storage/app/public/justificativas/");
    }

    private function gerarMotivoRejeicao(): string
    {
        $motivos = [
            'Documento ilegível ou incompleto.',
            'Atestado sem assinatura médica válida.',
            'Data do documento não corresponde à data da falta.',
            'Documento não comprova a necessidade de ausência.',
            'Justificativa apresentada fora do prazo permitido.',
        ];
        return $motivos[array_rand($motivos)];
    }

    private function criarPdfReal(?User $user, string $dataFalta, string $tipoDoc): string
    {
        $nomeCompleto = $user?->nome ?? 'NOME DO ESTUDANTE';
        $matricula = $user?->matricula ?? '00000000000';
        $dataFormatada = Carbon::parse($dataFalta)->format('d/m/Y');
        $dataEmissao = Carbon::parse($dataFalta)->addDays(rand(1, 3))->format('d/m/Y');

        $html = match($tipoDoc) {
            'atestado_medico' => $this->gerarHtmlAtestadoMedico($nomeCompleto, $matricula, $dataFormatada, $dataEmissao),
            'declaracao_coordenacao' => $this->gerarHtmlDeclaracaoCoordenacao($nomeCompleto, $matricula, $dataFormatada, $dataEmissao),
            'atestado_obito' => $this->gerarHtmlAtestadoObito($nomeCompleto, $dataFormatada, $dataEmissao),
            'declaracao_religiosa' => $this->gerarHtmlDeclaracaoReligiosa($nomeCompleto, $dataFormatada, $dataEmissao),
            default => $this->gerarHtmlDocumentoGenerico($nomeCompleto, $dataFormatada),
        };

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');

        // Nome do arquivo SEM o prefixo "justificativas/" (o controller adiciona)
        $nomeArquivo = "{$tipoDoc}_{$matricula}_" . Carbon::parse($dataFalta)->format('Ymd') . "_" . uniqid() . ".pdf";

        // Salvar no disco padrão (local), na pasta justificativas
        Storage::put("justificativas/{$nomeArquivo}", $pdf->output());

        return $nomeArquivo;
    }

    private function gerarHtmlAtestadoMedico(string $nome, string $matricula, string $dataFalta, string $dataEmissao): string
    {
        $crm = rand(10000, 99999);
        $medicos = ['Dr. Carlos Eduardo Silva', 'Dra. Maria Fernanda Santos', 'Dr. João Pedro Oliveira', 'Dra. Ana Beatriz Costa'];
        $medico = $medicos[array_rand($medicos)];
        $clinicas = ['Clínica São Lucas', 'Hospital Municipal de Vitória da Conquista', 'UPA 24 Horas - Centro', 'Centro Médico Vida'];
        $clinica = $clinicas[array_rand($clinicas)];
        $cids = [
            ['codigo' => 'R50.9', 'descricao' => 'Febre não especificada'],
            ['codigo' => 'J06.9', 'descricao' => 'Infecção aguda das vias aéreas superiores'],
            ['codigo' => 'K30', 'descricao' => 'Dispepsia funcional'],
            ['codigo' => 'R51', 'descricao' => 'Cefaleia'],
            ['codigo' => 'A09', 'descricao' => 'Gastroenterite infecciosa'],
        ];
        $cid = $cids[array_rand($cids)];

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #333; line-height: 1.6; }
        .header { text-align: center; border-bottom: 3px solid #2c5aa0; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #2c5aa0; }
        .clinica-info { font-size: 12px; color: #666; margin-top: 5px; }
        .titulo { text-align: center; font-size: 22px; font-weight: bold; color: #2c5aa0; margin: 30px 0; text-transform: uppercase; letter-spacing: 2px; }
        .conteudo { text-align: justify; font-size: 14px; margin: 20px 0; }
        .dados-paciente { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .dados-paciente p { margin: 5px 0; }
        .cid { background: #e8f4f8; padding: 10px; border-left: 4px solid #2c5aa0; margin: 20px 0; }
        .assinatura { margin-top: 60px; text-align: center; }
        .linha-assinatura { border-top: 1px solid #333; width: 300px; margin: 0 auto; padding-top: 10px; }
        .rodape { margin-top: 50px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">' . $clinica . '</div>
        <div class="clinica-info">Rua das Flores, 123 - Centro - Vitória da Conquista - BA<br>CNPJ: 12.345.678/0001-90 | Tel: (77) 3424-0000</div>
    </div>
    <div class="titulo">Atestado Médico</div>
    <div class="conteudo">
        <p>Atesto, para os devidos fins, que o(a) paciente abaixo identificado(a) esteve sob cuidados médicos nesta unidade de saúde, necessitando de afastamento de suas atividades escolares.</p>
    </div>
    <div class="dados-paciente">
        <p><strong>Nome do Paciente:</strong> ' . $nome . '</p>
        <p><strong>Matrícula Escolar:</strong> ' . $matricula . '</p>
        <p><strong>Data do Atendimento:</strong> ' . $dataFalta . '</p>
    </div>
    <div class="cid">
        <strong>CID-10:</strong> ' . $cid['codigo'] . ' - ' . $cid['descricao'] . '
    </div>
    <div class="conteudo">
        <p>O(A) paciente necessita de repouso por 01 (um) dia, estando dispensado(a) de suas atividades escolares na data acima mencionada.</p>
    </div>
    <div class="assinatura">
        <div class="linha-assinatura">
            <strong>' . $medico . '</strong><br>CRM-BA ' . $crm . '<br>Médico(a) Clínico(a) Geral
        </div>
    </div>
    <div class="rodape">' . $clinica . ' - Vitória da Conquista/BA<br>Documento emitido em ' . $dataEmissao . '</div>
</body>
</html>';
    }

    private function gerarHtmlDeclaracaoCoordenacao(string $nome, string $matricula, string $dataFalta, string $dataEmissao): string
    {
        $cursos = ['Técnico em Informática', 'Técnico em Eletrotécnica', 'Técnico em Mecânica', 'Técnico em Edificações'];
        $curso = $cursos[array_rand($cursos)];
        $coordenadores = ['Prof. Dr. Roberto Almeida', 'Profa. Dra. Luciana Ferreira', 'Prof. Me. Marcos Vinícius', 'Profa. Ma. Cláudia Regina'];
        $coordenador = $coordenadores[array_rand($coordenadores)];
        $atividades = ['visita técnica à empresa XPTO', 'aula de campo no Parque Tecnológico', 'participação na Feira de Ciências', 'atividade prática externa'];
        $atividade = $atividades[array_rand($atividades)];
        $siape = rand(1000000, 9999999);

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #333; line-height: 1.8; }
        .header { text-align: center; margin-bottom: 30px; }
        .instituicao { font-size: 14px; color: #1a5f2a; font-weight: bold; text-transform: uppercase; }
        .campus { font-size: 12px; color: #666; }
        .titulo { text-align: center; font-size: 20px; font-weight: bold; color: #1a5f2a; margin: 40px 0; text-transform: uppercase; }
        .conteudo { text-align: justify; font-size: 14px; margin: 20px 0; }
        .dados-aluno { background: #f0f7f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .assinatura { margin-top: 60px; text-align: center; }
        .linha-assinatura { border-top: 1px solid #333; width: 350px; margin: 0 auto; padding-top: 10px; }
        .rodape { margin-top: 50px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <div class="instituicao">Instituto Federal da Bahia</div>
        <div class="campus">Campus Vitória da Conquista</div>
    </div>
    <div class="titulo">Declaração</div>
    <div class="conteudo">
        <p>Declaramos que o(a) estudante abaixo não compareceu às atividades regulares devido à participação em atividade curricular programada.</p>
    </div>
    <div class="dados-aluno">
        <p><strong>Nome:</strong> ' . $nome . '</p>
        <p><strong>Matrícula:</strong> ' . $matricula . '</p>
        <p><strong>Curso:</strong> ' . $curso . '</p>
        <p><strong>Data:</strong> ' . $dataFalta . '</p>
        <p><strong>Atividade:</strong> ' . $atividade . '</p>
    </div>
    <div class="assinatura">
        <p>Vitória da Conquista - BA, ' . $dataEmissao . '</p>
        <br><br>
        <div class="linha-assinatura">
            <strong>' . $coordenador . '</strong><br>Coordenador(a) de Curso<br>SIAPE: ' . $siape . '
        </div>
    </div>
    <div class="rodape">IFBA - Campus Vitória da Conquista<br>Av. Amazonas, 3150 - Zabelê</div>
</body>
</html>';
    }

    private function gerarHtmlAtestadoObito(string $nome, string $dataFalta, string $dataEmissao): string
    {
        $parentescos = ['avô paterno', 'avó materna', 'tio', 'tia'];
        $parentesco = $parentescos[array_rand($parentescos)];
        $nomesFalecido = ['José Almeida', 'Maria Aparecida', 'Antônio Carlos', 'Francisca das Chagas'];
        $nomeFalecido = $nomesFalecido[array_rand($nomesFalecido)];

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: "Times New Roman", serif; margin: 40px; color: #333; line-height: 1.8; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px double #333; padding-bottom: 20px; }
        .titulo { text-align: center; font-size: 18px; font-weight: bold; margin: 30px 0; text-transform: uppercase; }
        .conteudo { text-align: justify; font-size: 13px; margin: 20px 0; }
        .dados { margin: 20px 0; padding: 15px; background: #fafafa; border: 1px solid #ddd; }
        .assinatura { margin-top: 50px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div style="font-size: 16px; font-weight: bold;">Cartório do Registro Civil</div>
        <div style="font-size: 12px; color: #666;">Vitória da Conquista - Bahia</div>
    </div>
    <div class="titulo">Certidão de Óbito</div>
    <div class="dados">
        <p><strong>FALECIDO(A):</strong> ' . $nomeFalecido . '</p>
        <p><strong>DATA DO ÓBITO:</strong> ' . $dataFalta . '</p>
        <p><strong>PARENTESCO:</strong> ' . $parentesco . ' do(a) estudante ' . $nome . '</p>
    </div>
    <div class="conteudo">
        <p>Esta certidão é expedida para fins de justificativa de ausência escolar.</p>
    </div>
    <div class="assinatura">
        <p>Emitida em: ' . $dataEmissao . '</p>
        <br>
        <p>_______________________________</p>
        <p><strong>Oficial do Registro Civil</strong></p>
    </div>
</body>
</html>';
    }

    private function gerarHtmlDeclaracaoReligiosa(string $nome, string $dataFalta, string $dataEmissao): string
    {
        $igrejas = ['Igreja Batista Central', 'Paróquia São José', 'Igreja Assembleia de Deus', 'Centro Espírita Luz e Caridade'];
        $igreja = $igrejas[array_rand($igrejas)];
        $lideres = ['Pastor João Marcos', 'Padre Antônio Carlos', 'Pastora Maria Helena', 'Dirigente Carlos Eduardo'];
        $lider = $lideres[array_rand($lideres)];
        $eventos = ['Retiro Espiritual da Juventude', 'Celebração de Batismo', 'Encontro Regional de Jovens', 'Cerimônia de Consagração'];
        $evento = $eventos[array_rand($eventos)];

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Georgia, serif; margin: 40px; color: #333; line-height: 1.8; }
        .header { text-align: center; margin-bottom: 30px; }
        .nome-igreja { font-size: 20px; font-weight: bold; color: #8b4513; margin: 10px 0; }
        .titulo { text-align: center; font-size: 22px; font-weight: bold; color: #8b4513; margin: 40px 0; }
        .conteudo { text-align: justify; font-size: 14px; margin: 20px 0; }
        .evento { text-align: center; font-size: 16px; font-weight: bold; font-style: italic; color: #8b4513; margin: 20px 0; padding: 15px; border-top: 1px solid #8b4513; border-bottom: 1px solid #8b4513; }
        .assinatura { margin-top: 60px; text-align: center; }
        .linha-assinatura { border-top: 1px solid #333; width: 300px; margin: 0 auto; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="nome-igreja">' . $igreja . '</div>
        <div style="font-size: 12px; color: #666;">Vitória da Conquista - BA</div>
    </div>
    <div class="titulo">Declaração para Fins Escolares</div>
    <div class="conteudo">
        <p>Declaramos que <strong>' . $nome . '</strong> é membro desta comunidade e esteve presente no dia <strong>' . $dataFalta . '</strong> participando de:</p>
    </div>
    <div class="evento">"' . $evento . '"</div>
    <div class="assinatura">
        <p>Vitória da Conquista - BA, ' . $dataEmissao . '</p>
        <br><br>
        <div class="linha-assinatura">
            <strong>' . $lider . '</strong><br>Líder Religioso
        </div>
    </div>
</body>
</html>';
    }

    private function gerarHtmlDocumentoGenerico(string $nome, string $dataFalta): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.8; }
        .titulo { text-align: center; font-size: 20px; font-weight: bold; margin: 40px 0; }
        .conteudo { margin: 20px 0; }
    </style>
</head>
<body>
    <div class="titulo">Documento Comprobatório</div>
    <div class="conteudo">
        <p><strong>Nome:</strong> ' . $nome . '</p>
        <p><strong>Data da Ausência:</strong> ' . $dataFalta . '</p>
        <p>Este documento serve como comprovação de ausência justificada.</p>
    </div>
</body>
</html>';
    }
}

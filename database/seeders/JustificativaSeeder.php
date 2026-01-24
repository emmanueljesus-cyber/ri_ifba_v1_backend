<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Refeicao;
use App\Models\Justificativa;
use App\Models\Presenca;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class JustificativaSeeder extends Seeder
{
    public function run(): void
    {
        // Criar pasta de justificativas se não existir
        Storage::disk('public')->makeDirectory('justificativas');

        $faltasJustificadas = Presenca::where('status_da_presenca', 'falta_justificada')->get();

        $motivosPosterior = [
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

        $anexosCriados = 0;

        foreach ($faltasJustificadas as $falta) {
            $tipo = rand(1, 100) <= 60 ? 'posterior' : 'antecipada';

            $user = User::find($falta->user_id);
            $refeicao = Refeicao::with('cardapio')->find($falta->refeicao_id);
            $dataFalta = $refeicao?->cardapio?->data_do_cardapio ?? now()->format('Y-m-d');

            if ($tipo == 'posterior') {
                $motivoData = $motivosPosterior[array_rand($motivosPosterior)];
                $motivo = $motivoData['motivo'] . ' Atestado/declaração anexo.';
                $tipoDoc = $motivoData['tipo_doc'];

                $nomeArquivo = $this->criarPdfReal($user, $dataFalta, $tipoDoc);
                $anexosCriados++;

                $enviadoEm = Carbon::parse($dataFalta)->addHours(rand(24, 72));
            } else {
                $motivo = $motivosAntecipada[array_rand($motivosAntecipada)];
                $nomeArquivo = null;
                $enviadoEm = Carbon::parse($dataFalta)->subHours(rand(2, 24));
            }

            Justificativa::create([
                'user_id' => $falta->user_id,
                'refeicao_id' => $falta->refeicao_id,
                'tipo' => $tipo,
                'motivo' => $motivo,
                'anexo' => $nomeArquivo,
                'enviado_em' => $enviadoEm,
            ]);
        }

        $total = Justificativa::count();
        $this->command->info("✅ {$total} justificativas criadas");
        $this->command->info("📎 {$anexosCriados} arquivos PDF gerados em storage/app/public/justificativas/");
    }

    private function criarPdfReal(?User $user, string $dataFalta, string $tipoDoc): string
    {
        $nomeCompleto = $user?->name ?? 'NOME DO ESTUDANTE';
        $matricula = $user?->bolsista?->matricula ?? '00000000000';
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

        $nomeArquivo = "justificativas/{$tipoDoc}_{$matricula}_" . Carbon::parse($dataFalta)->format('Ymd') . "_" . uniqid() . ".pdf";

        Storage::disk('public')->put($nomeArquivo, $pdf->output());

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
        .carimbo { border: 2px dashed #ccc; width: 150px; height: 80px; margin: 20px auto; text-align: center; padding-top: 30px; color: #999; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">✚ ' . $clinica . '</div>
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
        <p>Este documento é válido para fins de justificativa de ausência escolar, conforme legislação vigente.</p>
    </div>
    <div class="assinatura">
        <div class="carimbo">[CARIMBO MÉDICO]</div>
        <div class="linha-assinatura">
            <strong>' . $medico . '</strong><br>CRM-BA ' . $crm . '<br>Médico(a) Clínico(a) Geral
        </div>
    </div>
    <div class="rodape">' . $clinica . ' - Vitória da Conquista/BA<br>Documento emitido em ' . $dataEmissao . ' | Este documento é válido sem rasuras</div>
</body>
</html>';
    }

    private function gerarHtmlDeclaracaoCoordenacao(string $nome, string $matricula, string $dataFalta, string $dataEmissao): string
    {
        $cursos = ['Técnico em Informática Integrado ao Ensino Médio', 'Técnico em Eletrotécnica Integrado ao Ensino Médio', 'Técnico em Mecânica Integrado ao Ensino Médio', 'Técnico em Edificações Integrado ao Ensino Médio'];
        $curso = $cursos[array_rand($cursos)];
        $coordenadores = ['Prof. Dr. Roberto Almeida Silva', 'Profa. Dra. Luciana Ferreira Santos', 'Prof. Me. Marcos Vinícius Costa', 'Profa. Ma. Cláudia Regina Oliveira'];
        $coordenador = $coordenadores[array_rand($coordenadores)];
        $atividades = ['visita técnica à empresa XPTO Tecnologia', 'aula de campo no Parque Tecnológico da Bahia', 'participação na Feira de Ciências e Tecnologia', 'atividade prática externa no Laboratório Regional'];
        $atividade = $atividades[array_rand($atividades)];
        $siape = rand(1000000, 9999999);

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #333; line-height: 1.8; }
        .header { text-align: center; margin-bottom: 30px; }
        .brasao { font-size: 40px; color: #1a5f2a; }
        .instituicao { font-size: 14px; color: #1a5f2a; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .campus { font-size: 12px; color: #666; }
        .titulo { text-align: center; font-size: 20px; font-weight: bold; color: #1a5f2a; margin: 40px 0; text-transform: uppercase; border-bottom: 2px solid #1a5f2a; border-top: 2px solid #1a5f2a; padding: 10px; }
        .conteudo { text-align: justify; font-size: 14px; margin: 20px 0; }
        .dados-aluno { background: #f0f7f0; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #1a5f2a; }
        .atividade { background: #fff; padding: 15px; border: 1px solid #1a5f2a; margin: 20px 0; font-style: italic; }
        .assinatura { margin-top: 60px; text-align: center; }
        .linha-assinatura { border-top: 1px solid #333; width: 350px; margin: 0 auto; padding-top: 10px; }
        .rodape { margin-top: 50px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brasao">🏛️</div>
        <div class="instituicao">Ministério da Educação</div>
        <div class="instituicao">Instituto Federal de Educação, Ciência e Tecnologia da Bahia</div>
        <div class="campus">Campus Vitória da Conquista</div>
    </div>
    <div class="titulo">Declaração</div>
    <div class="conteudo">
        <p>Declaramos, para os devidos fins, que o(a) estudante abaixo identificado(a) está regularmente matriculado(a) neste Instituto Federal e não compareceu às atividades regulares na data especificada devido à participação em atividade curricular programada.</p>
    </div>
    <div class="dados-aluno">
        <p><strong>Nome do(a) Estudante:</strong> ' . $nome . '</p>
        <p><strong>Matrícula:</strong> ' . $matricula . '</p>
        <p><strong>Curso:</strong> ' . $curso . '</p>
        <p><strong>Data da Ausência:</strong> ' . $dataFalta . '</p>
    </div>
    <div class="conteudo"><p><strong>Motivo da Ausência:</strong></p></div>
    <div class="atividade">"' . $atividade . '"</div>
    <div class="conteudo">
        <p>Esta atividade foi devidamente registrada e autorizada pela coordenação do curso, conforme planejamento pedagógico do semestre letivo.</p>
    </div>
    <div class="assinatura">
        <p>Vitória da Conquista - BA, ' . $dataEmissao . '</p>
        <br><br><br>
        <div class="linha-assinatura">
            <strong>' . $coordenador . '</strong><br>Coordenador(a) de Curso<br>SIAPE: ' . $siape . '
        </div>
    </div>
    <div class="rodape">Instituto Federal da Bahia - Campus Vitória da Conquista<br>Av. Amazonas, 3150 - Zabelê - CEP: 45078-900<br>www.ifba.edu.br</div>
</body>
</html>';
    }

    private function gerarHtmlAtestadoObito(string $nome, string $dataFalta, string $dataEmissao): string
    {
        $parentescos = ['avô paterno', 'avó materna', 'tio', 'tia'];
        $parentesco = $parentescos[array_rand($parentescos)];
        $nomesFalecido = ['José Almeida da Silva', 'Maria Aparecida Santos', 'Antônio Carlos Oliveira', 'Francisca das Chagas Lima'];
        $nomeFalecido = $nomesFalecido[array_rand($nomesFalecido)];
        $numRegistro = rand(100000, 999999);
        $livro = rand(10, 99);
        $folha = rand(1, 200);

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: "Times New Roman", serif; margin: 40px; color: #333; line-height: 1.8; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px double #333; padding-bottom: 20px; }
        .brasao { font-size: 36px; }
        .titulo-cartorio { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .subtitulo { font-size: 12px; color: #666; }
        .titulo { text-align: center; font-size: 18px; font-weight: bold; margin: 30px 0; text-transform: uppercase; background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
        .registro { text-align: center; font-size: 12px; color: #666; margin-bottom: 20px; }
        .conteudo { text-align: justify; font-size: 13px; margin: 20px 0; }
        .dados { margin: 20px 0; padding: 15px; background: #fafafa; border: 1px solid #ddd; }
        .dados p { margin: 8px 0; }
        .observacao { font-size: 11px; color: #666; font-style: italic; margin-top: 30px; padding: 10px; border-top: 1px solid #ddd; }
        .assinatura { margin-top: 50px; text-align: center; }
        .selo { border: 2px solid #333; width: 100px; height: 60px; margin: 20px auto; font-size: 10px; text-align: center; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brasao">⚖️</div>
        <div class="titulo-cartorio">Cartório do Registro Civil</div>
        <div class="titulo-cartorio">das Pessoas Naturais</div>
        <div class="subtitulo">Vitória da Conquista - Bahia</div>
    </div>
    <div class="titulo">Certidão de Óbito</div>
    <div class="registro">Registro nº ' . $numRegistro . ' | Livro C-' . $livro . ' | Folha ' . $folha . '</div>
    <div class="conteudo">
        <p>CERTIFICO que, às fls. do Livro de Registro de Óbitos deste Cartório, consta o assento de óbito com os seguintes dados:</p>
    </div>
    <div class="dados">
        <p><strong>FALECIDO(A):</strong> ' . $nomeFalecido . '</p>
        <p><strong>DATA DO ÓBITO:</strong> ' . $dataFalta . '</p>
        <p><strong>GRAU DE PARENTESCO:</strong> ' . $parentesco . ' do(a) estudante</p>
        <p><strong>NOME DO FAMILIAR (ESTUDANTE):</strong> ' . $nome . '</p>
    </div>
    <div class="observacao">
        <strong>OBSERVAÇÃO:</strong> Esta certidão é expedida exclusivamente para fins de justificativa de ausência escolar do familiar acima identificado, que necessitou ausentar-se de suas atividades para acompanhar os ritos fúnebres e prestar apoio familiar.
    </div>
    <div class="assinatura">
        <div class="selo">SELO<br>DIGITAL</div>
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
        $igrejas = [
            ['nome' => 'Igreja Batista Central', 'endereco' => 'Rua São Paulo, 234 - Centro'],
            ['nome' => 'Paróquia São José', 'endereco' => 'Praça da Matriz, 01 - Centro'],
            ['nome' => 'Igreja Assembleia de Deus', 'endereco' => 'Av. Brumado, 567 - Recreio'],
            ['nome' => 'Centro Espírita Luz e Caridade', 'endereco' => 'Rua Sergipe, 89 - Brasil']
        ];
        $igreja = $igrejas[array_rand($igrejas)];
        $lideres = ['Pastor João Marcos da Silva', 'Padre Antônio Carlos Oliveira', 'Pastora Maria Helena Santos', 'Dirigente Carlos Eduardo Lima'];
        $lider = $lideres[array_rand($lideres)];
        $eventos = ['Retiro Espiritual Anual da Juventude', 'Celebração Solene de Batismo', 'Encontro Regional de Jovens Cristãos', 'Cerimônia Especial de Consagração'];
        $evento = $eventos[array_rand($eventos)];

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Georgia, serif; margin: 40px; color: #333; line-height: 1.8; }
        .header { text-align: center; margin-bottom: 30px; }
        .simbolo { font-size: 48px; color: #8b4513; }
        .nome-igreja { font-size: 20px; font-weight: bold; color: #8b4513; margin: 10px 0; }
        .endereco { font-size: 12px; color: #666; }
        .titulo { text-align: center; font-size: 22px; font-weight: bold; color: #8b4513; margin: 40px 0 30px 0; font-style: italic; }
        .conteudo { text-align: justify; font-size: 14px; margin: 20px 0; }
        .nome-destaque { text-align: center; font-weight: bold; font-size: 16px; margin: 20px 0; }
        .evento { text-align: center; font-size: 16px; font-weight: bold; font-style: italic; color: #8b4513; margin: 20px 0; padding: 15px; border-top: 1px solid #8b4513; border-bottom: 1px solid #8b4513; }
        .assinatura { margin-top: 60px; text-align: center; }
        .carimbo { border: 2px dashed #8b4513; width: 120px; height: 70px; margin: 20px auto; border-radius: 10px; font-size: 10px; color: #8b4513; text-align: center; padding-top: 25px; }
        .linha-assinatura { border-top: 1px solid #333; width: 300px; margin: 0 auto; padding-top: 10px; }
        .bencao { text-align: center; font-style: italic; color: #666; margin-top: 40px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="simbolo">✝</div>
        <div class="nome-igreja">' . $igreja['nome'] . '</div>
        <div class="endereco">' . $igreja['endereco'] . ' - Vitória da Conquista - BA</div>
    </div>
    <div class="titulo">Declaração para Fins Escolares</div>
    <div class="conteudo">
        <p>Declaramos, para os devidos fins e a quem possa interessar, que:</p>
        <p class="nome-destaque">' . $nome . '</p>
        <p>É membro regular e participante ativo(a) desta comunidade religiosa, e esteve presente no dia <strong>' . $dataFalta . '</strong> participando de atividade oficial de nossa instituição:</p>
    </div>
    <div class="evento">"' . $evento . '"</div>
    <div class="conteudo">
        <p>Esta atividade religiosa é parte integrante do calendário oficial de nossa comunidade e contou com a participação integral do(a) declarado(a).</p>
        <p>O evento ocorreu durante o período letivo, justificando assim a ausência do(a) fiel às suas atividades escolares regulares.</p>
    </div>
    <div class="assinatura">
        <p>Vitória da Conquista - BA, ' . $dataEmissao . '</p>
        <div class="carimbo">[CARIMBO DA<br>INSTITUIÇÃO]</div>
        <div class="linha-assinatura">
            <strong>' . $lider . '</strong><br>Líder Religioso
        </div>
    </div>
    <div class="bencao">"A paz esteja convosco"</div>
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

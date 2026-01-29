<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Bolsista;
use App\Enums\PerfilUsuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ========================================
        // ADMIN
        // ========================================
        $admin = User::updateOrCreate(
            ['matricula' => '10000000001'],
            [
                'nome' => 'Administrador do Sistema',
                'email' => 'admin@ifba.edu.br',
                'password' => Hash::make('password'),
                'perfil' => PerfilUsuario::ADMIN,
                'bolsista' => false,
                'desligado' => false,
                'email_verified_at' => now(),
            ]
        );
        
        $this->command->info('👤 Admin: ' . $admin->matricula . ($admin->wasRecentlyCreated ? ' (criado)' : ' (atualizado)'));

        // ========================================
        // 100 ESTUDANTES BOLSISTAS (50 almoço + 50 jantar)
        // ========================================
        $cursos = [
            'Técnico em Informática',
            'Técnico em Eletrotécnica',
            'Técnico em Mecânica',
            'Técnico em Edificações',
            'Técnico em Química',
            'Técnico em Meio Ambiente',
            'Técnico em Alimentos',
            'Técnico em Segurança do Trabalho',
        ];

        $bolsistasCriados = 0;

        // Buscar bolsistas da tabela de aprovados (exceto os pendentes: 101-110)
        // Matrículas 20232360001 até 20232360100 serão cadastrados
        // Matrículas 20232360101 até 20232360110 ficam pendentes
        $matriculasPendentes = [];
        for ($i = 101; $i <= 110; $i++) {
            $matriculasPendentes[] = '20232360' . str_pad($i, 3, '0', STR_PAD_LEFT);
        }

        $bolsistasAprovados = Bolsista::whereNotNull('matricula')
            ->whereNotIn('matricula', $matriculasPendentes)
            ->orderBy('matricula')
            ->get();

        // Restrições alimentares possíveis
        $restricoesPossiveis = [
            'Alergia a amendoim',
            'Intolerância à lactose',
            'Alergia a frutos do mar',
            'Alergia a glúten',
            'Alergia a ovos',
            'Alergia a soja',
            'Intolerância a frutose',
        ];

        foreach ($bolsistasAprovados as $index => $bolsista) {
            // Gerar email baseado no nome
            $nomeLimpo = $this->gerarEmailDoNome($bolsista->nome);

            // 20% dos bolsistas são ovolactovegetarianos
            $preferencia = ($index % 5 === 0) ? 'ovolactovegetariano' : 'comum';

            // 15% dos bolsistas têm restrições alimentares (1 a 2 restrições)
            $restricoes = null;
            if ($index % 7 === 0) {
                $numRestricoes = rand(1, 2);
                $restricoes = array_slice($restricoesPossiveis, rand(0, count($restricoesPossiveis) - $numRestricoes), $numRestricoes);
            }

            $user = User::updateOrCreate(
                ['matricula' => $bolsista->matricula],
                [
                    'nome' => $bolsista->nome,
                    'email' => $nomeLimpo . '@aluno.ifba.edu.br',
                    'password' => Hash::make('password'),
                    'perfil' => PerfilUsuario::ESTUDANTE,
                    'bolsista' => true,
                    'desligado' => false,
                    'curso' => $bolsista->curso,
                    'turno_refeicao' => $bolsista->turno_refeicao,
                    'limite_faltas_mes' => 3,
                    'preferencia_alimentar' => $preferencia,
                    'restricoes_alimentares' => $restricoes,
                    'email_verified_at' => now(),
                ]
            );

            // Vincular com registro na tabela bolsistas
            $bolsista->update([
                'user_id' => $user->id,
                'vinculado_em' => now(),
            ]);

            $bolsistasCriados++;
        }

        $this->command->info("✅ {$bolsistasCriados} estudantes bolsistas criados");

        // ========================================
        // 20 ESTUDANTES NÃO-BOLSISTAS
        // ========================================
        $naoBolsistasData = [
            ['nome' => 'Ana Carolina Lima', 'turno_aula' => 'matutino'],
            ['nome' => 'Diego Santos Costa', 'turno_aula' => 'vespertino'],
            ['nome' => 'Camila Pereira Oliveira', 'turno_aula' => 'noturno'],
            ['nome' => 'Ricardo Almeida Silva', 'turno_aula' => 'matutino'],
            ['nome' => 'Paula Ferreira Santos', 'turno_aula' => 'vespertino'],
            ['nome' => 'Henrique Costa Lima', 'turno_aula' => 'noturno'],
            ['nome' => 'Bruna Oliveira Mendes', 'turno_aula' => 'matutino'],
            ['nome' => 'Thiago Souza Pereira', 'turno_aula' => 'vespertino'],
            ['nome' => 'Juliana Alves Costa', 'turno_aula' => 'noturno'],
            ['nome' => 'Marcos Paulo Santos', 'turno_aula' => 'matutino'],
            ['nome' => 'Fernanda Lima Rodrigues', 'turno_aula' => 'vespertino'],
            ['nome' => 'Lucas Ferreira Almeida', 'turno_aula' => 'noturno'],
            ['nome' => 'Isabela Costa Silva', 'turno_aula' => 'matutino'],
            ['nome' => 'Rafael Mendes Oliveira', 'turno_aula' => 'vespertino'],
            ['nome' => 'Amanda Santos Lima', 'turno_aula' => 'noturno'],
            ['nome' => 'Gabriel Pereira Costa', 'turno_aula' => 'matutino'],
            ['nome' => 'Letícia Alves Ferreira', 'turno_aula' => 'vespertino'],
            ['nome' => 'Pedro Lima Santos', 'turno_aula' => 'noturno'],
            ['nome' => 'Mariana Costa Oliveira', 'turno_aula' => 'matutino'],
            ['nome' => 'Bruno Ferreira Lima', 'turno_aula' => 'vespertino'],
        ];

        $naoBolsistasCriados = 0;
        foreach ($naoBolsistasData as $index => $dados) {
            $numero = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            $matricula = '20242460' . $numero;
            $nomeLimpo = $this->gerarEmailDoNome($dados['nome']);

            User::updateOrCreate(
                ['matricula' => $matricula],
                [
                    'nome' => $dados['nome'],
                    'email' => $nomeLimpo . '@aluno.ifba.edu.br',
                    'password' => Hash::make('password'),
                    'perfil' => PerfilUsuario::ESTUDANTE,
                    'bolsista' => false,
                    'desligado' => false,
                    'curso' => $cursos[array_rand($cursos)],
                    'turno_aula' => $dados['turno_aula'],
                    'limite_faltas_mes' => 0,
                    'email_verified_at' => now(),
                ]
            );
            $naoBolsistasCriados++;
        }

        $this->command->info("✅ {$naoBolsistasCriados} estudantes não-bolsistas criados");

        // ========================================
        // 5 ESTUDANTES DESLIGADOS (para teste)
        // ========================================
        $desligados = [
            [
                'matricula' => '20221160099',
                'nome' => 'Roberto Silva Desligado',
                'email' => 'roberto.desligado@aluno.ifba.edu.br',
                'desligado_em' => now()->subDays(30),
                'desligado_motivo' => 'Excesso de faltas injustificadas',
                'curso' => 'Técnico em Informática',
                'turno_refeicao' => 'almoco',
            ],
            [
                'matricula' => '20221160100',
                'nome' => 'Xavier Zanetti Zorzi',
                'email' => 'xavier.zanetti@aluno.ifba.edu.br',
                'desligado_em' => now()->subDays(15),
                'desligado_motivo' => 'Desistência do curso',
                'curso' => 'Técnico em Eletrônica',
                'turno_refeicao' => 'almoco',
            ],
            [
                'matricula' => '20221160101',
                'nome' => 'Yasmin Yamaguchi',
                'email' => 'yasmin.yamaguchi@aluno.ifba.edu.br',
                'desligado_em' => now()->subDays(20),
                'desligado_motivo' => 'Problemas pessoais',
                'curso' => 'Técnico em Química',
                'turno_refeicao' => 'jantar',
            ],
            [
                'matricula' => '20221160102',
                'nome' => 'Yuri Yunes',
                'email' => 'yuri.yunes@aluno.ifba.edu.br',
                'desligado_em' => now()->subDays(45),
                'desligado_motivo' => 'Transferência para outra instituição',
                'curso' => 'Técnico em Mecânica',
                'turno_refeicao' => 'almoco',
            ],
            [
                'matricula' => '20221160103',
                'nome' => 'Zelia Zanetti',
                'email' => 'zelia.zanetti@aluno.ifba.edu.br',
                'desligado_em' => now()->subDays(60),
                'desligado_motivo' => 'Motivos acadêmicos',
                'curso' => 'Técnico em Edificações',
                'turno_refeicao' => 'almoco',
            ],
        ];

        foreach ($desligados as $dados) {
            User::updateOrCreate(
                ['matricula' => $dados['matricula']],
                array_merge($dados, [
                    'password' => Hash::make('password'),
                    'perfil' => PerfilUsuario::ESTUDANTE,
                    'bolsista' => true,
                    'desligado' => true,
                    'email_verified_at' => now(),
                ])
            );
        }

        $this->command->info('✅ 5 estudantes desligados criados (para teste)');

        $this->command->info('');
        $this->command->info('📊 RESUMO DOS USUÁRIOS:');
        $this->command->info('   👤 Admin: matrícula 10000000001, senha: password');
        $this->command->info("   🎓 {$bolsistasCriados} Bolsistas ativos (matrículas 20232360001-100)");
        $this->command->info("   📚 {$naoBolsistasCriados} Não-bolsistas (matrículas 20242460001-020)");
        $this->command->info('   ❌ 5 Desligados (matrículas 20221160099-103)');
        $this->command->info('   ⏳ 10 Bolsistas pendentes na lista (matrículas 20232360101-110)');
    }

    /**
     * Gera um email limpo baseado no nome
     */
    private function gerarEmailDoNome(string $nome): string
    {
        // Remove acentos usando substituição
        $acentos = ['á','à','ã','â','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','õ','ô','ö','ú','ù','û','ü','ç','Á','À','Ã','Â','Ä','É','È','Ê','Ë','Í','Ì','Î','Ï','Ó','Ò','Õ','Ô','Ö','Ú','Ù','Û','Ü','Ç'];
        $semAcento = ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','A','A','A','A','A','E','E','E','E','I','I','I','I','O','O','O','O','O','U','U','U','U','C'];
        $nome = str_replace($acentos, $semAcento, $nome);

        // Pega primeiro e último nome
        $partes = explode(' ', strtolower($nome));
        $primeiro = $partes[0] ?? 'usuario';
        $ultimo = $partes[count($partes) - 1] ?? '';

        // Cria email único
        return $primeiro . '.' . $ultimo . rand(1, 99);
    }
}

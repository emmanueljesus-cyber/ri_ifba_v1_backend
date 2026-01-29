<?php

namespace Database\Seeders;

use App\Models\Bolsista;
use Illuminate\Database\Seeder;

class BolsistasAprovadosSeeder extends Seeder
{
    /**
     * Cria os registros na tabela bolsistas (lista de aprovados)
     * Simula a importação de planilha Excel pelo admin
     *
     * Total: 110 bolsistas aprovados
     * - 100 que vão se cadastrar (50 almoço, 50 jantar)
     * - 10 pendentes (ainda não se cadastraram)
     */
    public function run(): void
    {
        $this->command->info('📋 Importando lista de bolsistas aprovados...');

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

        $nomes = $this->gerarNomes();
        $bolsistasAprovados = [];

        // =====================================================
        // 100 BOLSISTAS QUE VÃO SE CADASTRAR
        // =====================================================

        // 50 bolsistas para ALMOÇO (matrículas 20232360001 até 20232360050)
        for ($i = 1; $i <= 50; $i++) {
            $matricula = '20232360' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $bolsistasAprovados[] = [
                'matricula' => $matricula,
                'nome' => $nomes[$i - 1],
                'curso' => $cursos[array_rand($cursos)],
                'turno_refeicao' => 'almoco',
                'dias_semana' => $this->gerarDiasSemana(),
            ];
        }

        // 50 bolsistas para JANTAR (matrículas 20232360051 até 20232360100)
        for ($i = 51; $i <= 100; $i++) {
            $matricula = '20232360' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $bolsistasAprovados[] = [
                'matricula' => $matricula,
                'nome' => $nomes[$i - 1],
                'curso' => $cursos[array_rand($cursos)],
                'turno_refeicao' => 'jantar',
                'dias_semana' => $this->gerarDiasSemana(),
            ];
        }

        // =====================================================
        // 10 BOLSISTAS PENDENTES (ainda não se cadastraram)
        // =====================================================
        $nomesPendentes = [
            'Roberto Silva Neto',
            'Camila Araújo Santos',
            'Diego Pereira Lima',
            'Patrícia Gomes Costa',
            'André Carvalho Silva',
            'Vanessa Ribeiro Alves',
            'Gustavo Mendes Ferreira',
            'Tatiana Oliveira Santos',
            'Renato Costa Lima',
            'Priscila Fernandes Souza',
        ];

        for ($i = 0; $i < 10; $i++) {
            $numero = 101 + $i;
            $matricula = '20232360' . str_pad($numero, 3, '0', STR_PAD_LEFT);
            $turno = $i < 5 ? 'almoco' : 'jantar';

            $bolsistasAprovados[] = [
                'matricula' => $matricula,
                'nome' => $nomesPendentes[$i],
                'curso' => $cursos[array_rand($cursos)],
                'turno_refeicao' => $turno,
                'dias_semana' => $this->gerarDiasSemana(),
            ];
        }

        // Inserir todos os bolsistas
        foreach ($bolsistasAprovados as $dados) {
            Bolsista::updateOrCreate(
                ['matricula' => $dados['matricula']],
                [
                    'nome' => $dados['nome'],
                    'curso' => $dados['curso'],
                    'turno_refeicao' => $dados['turno_refeicao'],
                    'dias_semana' => $dados['dias_semana'],
                    'ativo' => true,
                ]
            );
        }

        $total = count($bolsistasAprovados);
        $this->command->info("✅ {$total} bolsistas importados na lista de aprovados");
        $this->command->info('   📌 100 serão vinculados a usuários (50 almoço + 50 jantar)');
        $this->command->info('   📌 10 estão pendentes (para testar registro de novos usuários)');
        $this->command->info('   📌 Matrículas pendentes: 20232360101 até 20232360110');
    }

    /**
     * Gera array de dias da semana aleatórios (1-5)
     */
    private function gerarDiasSemana(): array
    {
        $opcoes = [
            [1, 2, 3, 4, 5],         // Segunda a sexta (mais comum)
            [1, 2, 3, 4, 5],         // Segunda a sexta
            [1, 2, 3, 4, 5],         // Segunda a sexta
            [1, 3, 5],               // Segunda, quarta, sexta
            [2, 4],                  // Terça e quinta
            [1, 2, 3, 4],            // Segunda a quinta
            [2, 3, 4, 5],            // Terça a sexta
            [1, 2, 3],               // Segunda a quarta
            [3, 4, 5],               // Quarta a sexta
        ];

        return $opcoes[array_rand($opcoes)];
    }

    /**
     * Gera lista de 100 nomes realistas
     */
    private function gerarNomes(): array
    {
        return [
            // 1-10
            'João Silva Santos',
            'Maria Oliveira Costa',
            'Pedro Henrique Souza',
            'Ana Paula Rodrigues',
            'Lucas Ferreira Alves',
            'Juliana Lima Pereira',
            'Rafael Costa Martins',
            'Fernanda Rodrigues Silva',
            'Gabriel Almeida Santos',
            'Beatriz Martins Costa',

            // 11-20
            'Thiago Ferreira Lima',
            'Amanda Dias Oliveira',
            'Bruno Nascimento Costa',
            'Isabela Cardoso Silva',
            'Vítor Monteiro Santos',
            'Letícia Rocha Pereira',
            'Carlos Eduardo Mendes',
            'Mariana Souza Lima',
            'Felipe Barbosa Costa',
            'Larissa Ribeiro Alves',

            // 21-30
            'Matheus Gomes Ferreira',
            'Camila Araújo Neto',
            'Leonardo Santos Oliveira',
            'Júlia Fernandes Costa',
            'Rodrigo Almeida Pereira',
            'Natália Costa Mendes',
            'Diego Pereira Santos',
            'Aline Souza Rodrigues',
            'André Lima Carvalho',
            'Bianca Ferreira Alves',

            // 31-40
            'Guilherme Martins Silva',
            'Carolina Oliveira Santos',
            'Marcos Vinícius Costa',
            'Patrícia Ribeiro Lima',
            'Renato Alves Ferreira',
            'Sabrina Santos Pereira',
            'Gustavo Rodrigues Mendes',
            'Daniela Carvalho Souza',
            'Fábio Costa Oliveira',
            'Vanessa Lima Fernandes',

            // 41-50
            'Eduardo Pereira Silva',
            'Priscila Almeida Costa',
            'Alexandre Santos Lima',
            'Tatiana Ferreira Rodrigues',
            'Ricardo Oliveira Mendes',
            'Mônica Souza Carvalho',
            'Fernando Lima Santos',
            'Adriana Costa Pereira',
            'Leandro Alves Oliveira',
            'Simone Rodrigues Silva',

            // 51-60
            'Paulo Henrique Costa',
            'Cristina Santos Ferreira',
            'Marcelo Lima Almeida',
            'Luciana Pereira Souza',
            'Roberto Carlos Oliveira',
            'Elaine Santos Costa',
            'Anderson Silva Lima',
            'Carla Ferreira Rodrigues',
            'Cláudio Mendes Pereira',
            'Sandra Oliveira Santos',

            // 61-70
            'Wagner Costa Alves',
            'Patrícia Lima Ferreira',
            'Ronaldo Souza Silva',
            'Denise Rodrigues Costa',
            'Sérgio Almeida Santos',
            'Flávia Pereira Lima',
            'José Ricardo Costa',
            'Michele Santos Oliveira',
            'Antônio Carlos Ferreira',
            'Rosana Lima Souza',

            // 71-80
            'Marcio Rodrigues Alves',
            'Cláudia Costa Santos',
            'Jorge Ferreira Lima',
            'Andréia Oliveira Costa',
            'Márcio Souza Pereira',
            'Kátia Lima Rodrigues',
            'César Santos Ferreira',
            'Silvia Costa Almeida',
            'Rogério Pereira Santos',
            'Regina Lima Costa',

            // 81-90
            'Luís Fernando Silva',
            'Márcia Ferreira Oliveira',
            'Edson Costa Lima',
            'Débora Santos Rodrigues',
            'Reinaldo Alves Costa',
            'Valéria Pereira Santos',
            'Sandro Lima Ferreira',
            'Érica Costa Oliveira',
            'Nilton Santos Lima',
            'Luciane Rodrigues Costa',

            // 91-100
            'Emerson Ferreira Santos',
            'Raquel Lima Pereira',
            'Everton Costa Alves',
            'Gisele Santos Oliveira',
            'Rogério Lima Costa',
            'Jéssica Ferreira Santos',
            'Tiago Oliveira Lima',
            'Bárbara Costa Rodrigues',
            'Vinícius Santos Pereira',
            'Aline Lima Ferreira',
        ];
    }
}

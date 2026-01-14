<?php

namespace Database\Seeders;

use App\Models\Bolsista;
use Illuminate\Database\Seeder;

class BolsistasAprovadosSeeder extends Seeder
{
    /**
     * Cria os registros na tabela bolsistas (lista de aprovados)
     * Simula a importação de planilha Excel pelo admin
     */
    public function run(): void
    {
        $this->command->info('📋 Importando lista de bolsistas aprovados...');

        // Lista de estudantes aprovados para bolsa alimentação
        // Matrículas seguem padrão IFBA: 11 dígitos (ex: 20212160036)
$bolsistasAprovados = [
    // Bolsistas que vão se cadastrar (existem na lista E vão criar conta)
    ['matricula' => '20231160001', 'nome' => 'João Silva Santos', 'curso' => 'Técnico em Informática', 'turno' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],
    ['matricula' => '20231160002', 'nome' => 'Maria Oliveira Costa', 'curso' => 'Técnico em Química', 'turno' => 'almoco', 'dias_semana' => [1, 3, 5]],
    ['matricula' => '20231160003', 'nome' => 'Pedro Henrique Souza', 'curso' => 'Técnico em Eletrônica', 'turno' => 'almoco', 'dias_semana' => [2, 4]],
    ['matricula' => '20231160004', 'nome' => 'Ana Paula Rodrigues', 'curso' => 'Técnico em Mecânica', 'turno' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],
    ['matricula' => '20231160005', 'nome' => 'Lucas Ferreira Alves', 'curso' => 'Técnico em Edificações', 'turno' => 'almoco', 'dias_semana' => [1, 3, 5]],
    ['matricula' => '20231160006', 'nome' => 'Juliana Lima Pereira', 'curso' => 'Técnico em Informática', 'turno' => 'jantar', 'dias_semana' => [2, 4]],
    ['matricula' => '20231160007', 'nome' => 'Rafael Costa Martins', 'curso' => 'Técnico em Química', 'turno' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],
    ['matricula' => '20231160008', 'nome' => 'Fernanda Rodrigues Silva', 'curso' => 'Técnico em Eletrônica', 'turno' => 'almoco', 'dias_semana' => [1, 3, 5]],
    ['matricula' => '20231160009', 'nome' => 'Gabriel Almeida Santos', 'curso' => 'Técnico em Mecânica', 'turno' => 'almoco', 'dias_semana' => [2, 4]],
    ['matricula' => '20231160010', 'nome' => 'Beatriz Martins Costa', 'curso' => 'Técnico em Edificações', 'turno' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],

            // Mais 10 bolsistas que também serão cadastrados
        ['matricula' => '20231160011', 'nome' => 'Thiago Ferreira Lima', 'curso' => 'Técnico em Informática', 'turno' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],
        ['matricula' => '20231160012', 'nome' => 'Amanda Dias Oliveira', 'curso' => 'Técnico em Química', 'turno' => 'jantar', 'dias_semana' => [1, 3, 5]],
        ['matricula' => '20231160013', 'nome' => 'Bruno Nascimento Costa', 'curso' => 'Técnico em Eletrônica', 'turno' => 'almoco', 'dias_semana' => [2, 4]],
        ['matricula' => '20231160014', 'nome' => 'Isabela Cardoso Silva', 'curso' => 'Técnico em Mecânica', 'turno' => 'almoco', 'dias_semana' => [1, 2, 3, 4]],
        ['matricula' => '20231160015', 'nome' => 'Vitor Monteiro Santos', 'curso' => 'Técnico em Edificações', 'turno' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],
        ['matricula' => '20231160016', 'nome' => 'Letícia Rocha Pereira', 'curso' => 'Técnico em Informática', 'turno' => 'almoco', 'dias_semana' => [1, 3, 5]],
        ['matricula' => '20231160017', 'nome' => 'Carlos Eduardo Mendes', 'curso' => 'Técnico em Química', 'turno' => 'almoco', 'dias_semana' => [2, 4]],
        ['matricula' => '20231160018', 'nome' => 'Mariana Souza Lima', 'curso' => 'Técnico em Eletrônica', 'turno' => 'jantar', 'dias_semana' => [1, 2, 3, 4]],
        ['matricula' => '20231160019', 'nome' => 'Felipe Barbosa Costa', 'curso' => 'Técnico em Mecânica', 'turno' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],
        ['matricula' => '20231160020', 'nome' => 'Larissa Ribeiro Alves', 'curso' => 'Técnico em Edificações', 'turno' => 'almoco', 'dias_semana' => [1, 3, 5]],

         // Bolsistas PENDENTES (estão na lista mas ainda NÃO se cadastraram)
         // Útil para testar o fluxo de registro de novos estudantes
         ['matricula' => '20231160021', 'nome' => 'Roberto Silva Neto', 'curso' => 'Técnico em Informática', 'turno' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],
         ['matricula' => '20231160022', 'nome' => 'Camila Araújo Santos', 'curso' => 'Técnico em Química', 'turno' => 'almoco', 'dias_semana' => [2, 4]],
         ['matricula' => '20231160023', 'nome' => 'Diego Pereira Lima', 'curso' => 'Técnico em Eletrônica', 'turno' => 'jantar', 'dias_semana' => [1, 3, 5]],
         ['matricula' => '20231160024', 'nome' => 'Patrícia Gomes Costa', 'curso' => 'Técnico em Mecânica', 'turno' => 'almoco', 'dias_semana' => [1, 2, 3, 4]],
         ['matricula' => '20231160025', 'nome' => 'André Carvalho Silva', 'curso' => 'Técnico em Edificações', 'turno' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],
];

        foreach ($bolsistasAprovados as $dados) {
            Bolsista::updateOrCreate(
                ['matricula' => $dados['matricula']],
                [
                    'nome' => $dados['nome'],
                    'curso' => $dados['curso'],
                    'turno' => $dados['turno'],
                    'dias_semana' => $dados['dias_semana'],
                    'ativo' => true,
                ]
            );
        }

        $this->command->info('✅ ' . count($bolsistasAprovados) . ' bolsistas importados na lista de aprovados');
        $this->command->info('   📌 20 serão vinculados a usuários');
        $this->command->info('   📌 5 estão pendentes (para testar registro de novos usuários)');
    }
}

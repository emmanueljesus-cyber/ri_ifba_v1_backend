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
        $this->command->info('Importando lista de bolsistas aprovados...');

	// Lista de estudantes aprovados para bolsa alimentacao
	// Matriculas seguem padrao IFBA: 11 digitos (ex: 20212160036)
$bolsistasAprovados = [
    // Bolsistas que vao se cadastrar (existem na lista E vao criar conta)
    ['matricula' => '20232360001', 'nome' => 'Joao Silva Santos', 'curso' => 'Tecnico em Informatica', 'turno_refeicao' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],
    ['matricula' => '20232360002', 'nome' => 'Maria Oliveira Costa', 'curso' => 'Tecnico em Quimica', 'turno_refeicao' => 'almoco', 'dias_semana' => [1, 3, 5]],
    ['matricula' => '20232360003', 'nome' => 'Pedro Henrique Souza', 'curso' => 'Tecnico em Eletronica', 'turno_refeicao' => 'almoco', 'dias_semana' => [2, 4]],
    ['matricula' => '20232360004', 'nome' => 'Ana Paula Rodrigues', 'curso' => 'Tecnico em Mecanica', 'turno_refeicao' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],
    ['matricula' => '20232360005', 'nome' => 'Lucas Ferreira Alves', 'curso' => 'Tecnico em Edificacoes', 'turno_refeicao' => 'almoco', 'dias_semana' => [1, 3, 5]],
    ['matricula' => '20232360006', 'nome' => 'Juliana Lima Pereira', 'curso' => 'Tecnico em Informatica', 'turno_refeicao' => 'jantar', 'dias_semana' => [2, 4]],
    ['matricula' => '20232360007', 'nome' => 'Rafael Costa Martins', 'curso' => 'Tecnico em Quimica', 'turno_refeicao' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],
    ['matricula' => '20232360008', 'nome' => 'Fernanda Rodrigues Silva', 'curso' => 'Tecnico em Eletronica', 'turno_refeicao' => 'almoco', 'dias_semana' => [1, 3, 5]],
    ['matricula' => '20232360009', 'nome' => 'Gabriel Almeida Santos', 'curso' => 'Tecnico em Mecanica', 'turno_refeicao' => 'almoco', 'dias_semana' => [2, 4]],
    ['matricula' => '20232360010', 'nome' => 'Beatriz Martins Costa', 'curso' => 'Tecnico em Edificacoes', 'turno_refeicao' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],

	// Mais 10 bolsistas que tambem serao cadastrados
	['matricula' => '20232360011', 'nome' => 'Thiago Ferreira Lima', 'curso' => 'Tecnico em Informatica', 'turno_refeicao' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],
	['matricula' => '20232360012', 'nome' => 'Amanda Dias Oliveira', 'curso' => 'Tecnico em Quimica', 'turno_refeicao' => 'jantar', 'dias_semana' => [1, 3, 5]],
	['matricula' => '20232360013', 'nome' => 'Bruno Nascimento Costa', 'curso' => 'Tecnico em Eletronica', 'turno_refeicao' => 'almoco', 'dias_semana' => [2, 4]],
	['matricula' => '20232360014', 'nome' => 'Isabela Cardoso Silva', 'curso' => 'Tecnico em Mecanica', 'turno_refeicao' => 'almoco', 'dias_semana' => [1, 2, 3, 4]],
	['matricula' => '20232360015', 'nome' => 'Vitor Monteiro Santos', 'curso' => 'Tecnico em Edificacoes', 'turno_refeicao' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],
	['matricula' => '20232360016', 'nome' => 'Leticia Rocha Pereira', 'curso' => 'Tecnico em Informatica', 'turno_refeicao' => 'almoco', 'dias_semana' => [1, 3, 5]],
	['matricula' => '20232360017', 'nome' => 'Carlos Eduardo Mendes', 'curso' => 'Tecnico em Quimica', 'turno_refeicao' => 'almoco', 'dias_semana' => [2, 4]],
	['matricula' => '20232360018', 'nome' => 'Mariana Souza Lima', 'curso' => 'Tecnico em Eletronica', 'turno_refeicao' => 'jantar', 'dias_semana' => [1, 2, 3, 4]],
	['matricula' => '20232360019', 'nome' => 'Felipe Barbosa Costa', 'curso' => 'Tecnico em Mecanica', 'turno_refeicao' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],
	['matricula' => '20232360020', 'nome' => 'Larissa Ribeiro Alves', 'curso' => 'Tecnico em Edificacoes', 'turno_refeicao' => 'almoco', 'dias_semana' => [1, 3, 5]],

	 // Bolsistas PENDENTES (estao na lista mas ainda NAO se cadastraram)
	 ['matricula' => '20232360021', 'nome' => 'Roberto Silva Neto', 'curso' => 'Tecnico em Informatica', 'turno_refeicao' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],
	 ['matricula' => '20232360022', 'nome' => 'Camila Araujo Santos', 'curso' => 'Tecnico em Quimica', 'turno_refeicao' => 'almoco', 'dias_semana' => [2, 4]],
	 ['matricula' => '20232360023', 'nome' => 'Diego Pereira Lima', 'curso' => 'Tecnico em Eletronica', 'turno_refeicao' => 'jantar', 'dias_semana' => [1, 3, 5]],
	 ['matricula' => '20232360024', 'nome' => 'Patricia Gomes Costa', 'curso' => 'Tecnico em Mecanica', 'turno_refeicao' => 'almoco', 'dias_semana' => [1, 2, 3, 4]],
	 ['matricula' => '20232360025', 'nome' => 'Andre Carvalho Silva', 'curso' => 'Tecnico em Edificacoes', 'turno_refeicao' => 'almoco', 'dias_semana' => [1, 2, 3, 4, 5]],
];

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

        $this->command->info(count($bolsistasAprovados) . ' bolsistas importados na lista de aprovados');
        $this->command->info('   20 serao vinculados a usuarios');
        $this->command->info('   5 estao pendentes (para testar registro de novos usuarios)');
    }
}

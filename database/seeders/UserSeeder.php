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
        $admin = User::create([
            'matricula' => '10000000001',
            'nome' => 'Administrador do Sistema',
            'email' => 'admin@ifba.edu.br',
            'password' => Hash::make('password'),
            'perfil' => PerfilUsuario::ADMIN,
            'bolsista' => false,
            'desligado' => false,
            'email_verified_at' => now(),
        ]);
        
        $this->command->info('Admin criado: ' . $admin->matricula);

        // ========================================
        // ESTUDANTES BOLSISTAS
        // ========================================
        $bolsistasData = [
            ['matricula' => '20232360001', 'nome' => 'Joao Silva Santos', 'email' => 'joao.silva@aluno.ifba.edu.br', 'curso' => 'Tecnico em Informatica', 'turno_refeicao' => 'almoco'],
            ['matricula' => '20232360002', 'nome' => 'Maria Oliveira Costa', 'email' => 'maria.oliveira@aluno.ifba.edu.br', 'curso' => 'Tecnico em Quimica', 'turno_refeicao' => 'almoco'],
            ['matricula' => '20232360003', 'nome' => 'Pedro Henrique Souza', 'email' => 'pedro.souza@aluno.ifba.edu.br', 'curso' => 'Tecnico em Eletronica', 'turno_refeicao' => 'almoco'],
            ['matricula' => '20232360004', 'nome' => 'Ana Paula Rodrigues', 'email' => 'ana.rodrigues@aluno.ifba.edu.br', 'curso' => 'Tecnico em Mecanica', 'turno_refeicao' => 'almoco'],
            ['matricula' => '20232360005', 'nome' => 'Lucas Ferreira Alves', 'email' => 'lucas.alves@aluno.ifba.edu.br', 'curso' => 'Tecnico em Edificacoes', 'turno_refeicao' => 'almoco'],
            ['matricula' => '20232360006', 'nome' => 'Juliana Lima Pereira', 'email' => 'juliana.lima@aluno.ifba.edu.br', 'curso' => 'Tecnico em Informatica', 'turno_refeicao' => 'jantar'],
            ['matricula' => '20232360007', 'nome' => 'Rafael Costa Martins', 'email' => 'rafael.costa@aluno.ifba.edu.br', 'curso' => 'Tecnico em Quimica', 'turno_refeicao' => 'almoco'],
            ['matricula' => '20232360008', 'nome' => 'Fernanda Rodrigues Silva', 'email' => 'fernanda.rodrigues@aluno.ifba.edu.br', 'curso' => 'Tecnico em Eletronica', 'turno_refeicao' => 'almoco'],
            ['matricula' => '20232360009', 'nome' => 'Gabriel Almeida Santos', 'email' => 'gabriel.almeida@aluno.ifba.edu.br', 'curso' => 'Tecnico em Mecanica', 'turno_refeicao' => 'almoco'],
            ['matricula' => '20232360010', 'nome' => 'Beatriz Martins Costa', 'email' => 'beatriz.martins@aluno.ifba.edu.br', 'curso' => 'Tecnico em Edificacoes', 'turno_refeicao' => 'almoco'],
            ['matricula' => '20232360011', 'nome' => 'Thiago Ferreira Lima', 'email' => 'thiago.ferreira@aluno.ifba.edu.br', 'curso' => 'Tecnico em Informatica', 'turno_refeicao' => 'almoco'],
            ['matricula' => '20232360012', 'nome' => 'Amanda Dias Oliveira', 'email' => 'amanda.dias@aluno.ifba.edu.br', 'curso' => 'Tecnico em Quimica', 'turno_refeicao' => 'jantar'],
            ['matricula' => '20232360013', 'nome' => 'Bruno Nascimento Costa', 'email' => 'bruno.nascimento@aluno.ifba.edu.br', 'curso' => 'Tecnico em Eletronica', 'turno_refeicao' => 'almoco'],
            ['matricula' => '20232360014', 'nome' => 'Isabela Cardoso Silva', 'email' => 'isabela.cardoso@aluno.ifba.edu.br', 'curso' => 'Tecnico em Mecanica', 'turno_refeicao' => 'almoco'],
            ['matricula' => '20232360015', 'nome' => 'Vitor Monteiro Santos', 'email' => 'vitor.monteiro@aluno.ifba.edu.br', 'curso' => 'Tecnico em Edificacoes', 'turno_refeicao' => 'almoco'],
            ['matricula' => '20232360016', 'nome' => 'Leticia Rocha Pereira', 'email' => 'leticia.rocha@aluno.ifba.edu.br', 'curso' => 'Tecnico em Informatica', 'turno_refeicao' => 'almoco'],
            ['matricula' => '20232360017', 'nome' => 'Carlos Eduardo Mendes', 'email' => 'carlos.mendes@aluno.ifba.edu.br', 'curso' => 'Tecnico em Quimica', 'turno_refeicao' => 'almoco'],
            ['matricula' => '20232360018', 'nome' => 'Mariana Souza Lima', 'email' => 'mariana.souza@aluno.ifba.edu.br', 'curso' => 'Tecnico em Eletronica', 'turno_refeicao' => 'jantar'],
            ['matricula' => '20232360019', 'nome' => 'Felipe Barbosa Costa', 'email' => 'felipe.barbosa@aluno.ifba.edu.br', 'curso' => 'Tecnico em Mecanica', 'turno_refeicao' => 'almoco'],
            ['matricula' => '20232360020', 'nome' => 'Larissa Ribeiro Alves', 'email' => 'larissa.ribeiro@aluno.ifba.edu.br', 'curso' => 'Tecnico em Edificacoes', 'turno_refeicao' => 'almoco'],
        ];

        foreach ($bolsistasData as $dados) {
            $user = User::create([
                'matricula' => $dados['matricula'],
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'password' => Hash::make('password'),
                'perfil' => PerfilUsuario::ESTUDANTE,
                'bolsista' => true,
                'desligado' => false,
                'curso' => $dados['curso'],
                'turno_refeicao' => $dados['turno_refeicao'],
                'limite_faltas_mes' => 3,
                'email_verified_at' => now(),
            ]);

            // Vincular com registro na tabela bolsistas
            $bolsista = Bolsista::where('matricula', $dados['matricula'])->first();
            if ($bolsista) {
                $bolsista->update([
                    'user_id' => $user->id,
                    'vinculado_em' => now(),
                ]);
            }
        }

        $this->command->info(count($bolsistasData) . ' estudantes bolsistas criados');

        // ========================================
        // ESTUDANTES NAO-BOLSISTAS
        // ========================================
        $naoBolsistasData = [
            ['matricula' => '20242460001', 'nome' => 'Ana Carolina Lima', 'email' => 'ana.lima@aluno.ifba.edu.br', 'curso' => 'Tecnico em Informatica', 'turno_aula' => 'matutino'],
            ['matricula' => '20242460002', 'nome' => 'Diego Santos Costa', 'email' => 'diego.santos@aluno.ifba.edu.br', 'curso' => 'Tecnico em Quimica', 'turno_aula' => 'vespertino'],
            ['matricula' => '20242460003', 'nome' => 'Camila Pereira Oliveira', 'email' => 'camila.pereira@aluno.ifba.edu.br', 'curso' => 'Tecnico em Eletronica', 'turno_aula' => 'noturno'],
            ['matricula' => '20242460004', 'nome' => 'Ricardo Almeida Silva', 'email' => 'ricardo.almeida@aluno.ifba.edu.br', 'curso' => 'Tecnico em Mecanica', 'turno_aula' => 'matutino'],
            ['matricula' => '20242460005', 'nome' => 'Paula Ferreira Santos', 'email' => 'paula.ferreira@aluno.ifba.edu.br', 'curso' => 'Tecnico em Edificacoes', 'turno_aula' => 'vespertino'],
        ];

        foreach ($naoBolsistasData as $dados) {
            User::create([
                'matricula' => $dados['matricula'],
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'password' => Hash::make('password'),
                'perfil' => PerfilUsuario::ESTUDANTE,
                'bolsista' => false,
                'desligado' => false,
                'curso' => $dados['curso'],
                'turno_aula' => $dados['turno_aula'],
                'limite_faltas_mes' => 0,
                'email_verified_at' => now(),
            ]);
        }

        $this->command->info(count($naoBolsistasData) . ' estudantes nao-bolsistas criados');

        // ========================================
        // ESTUDANTE DESLIGADO (para teste)
        // ========================================
        $desligados = [
            [
                'matricula' => '20221160099',
                'nome' => 'Roberto Silva Desligado',
                'email' => 'roberto.desligado@aluno.ifba.edu.br',
                'desligado_em' => now()->subDays(30),
                'desligado_motivo' => 'Excesso de faltas injustificadas',
                'curso' => 'Tecnico em Informatica',
                'turno_refeicao' => 'almoco',
            ],
            [
                'matricula' => '20221160100',
                'nome' => 'Xavier Zanetti Zorzi',
                'email' => 'xavier.zanetti@aluno.ifba.edu.br',
                'desligado_em' => now()->subDays(15),
                'desligado_motivo' => 'Desistencia do curso',
                'curso' => 'Tecnico em Eletronica',
                'turno_refeicao' => 'almoco',
            ],
            [
                'matricula' => '20221160101',
                'nome' => 'Yasmin Yamaguchi',
                'email' => 'yasmin.yamaguchi@aluno.ifba.edu.br',
                'desligado_em' => now()->subDays(20),
                'desligado_motivo' => 'Problemas pessoais',
                'curso' => 'Tecnico em Quimica',
                'turno_refeicao' => 'jantar',
            ],
            [
                'matricula' => '20221160102',
                'nome' => 'Yuri Yunes',
                'email' => 'yuri.yunes@aluno.ifba.edu.br',
                'desligado_em' => now()->subDays(45),
                'desligado_motivo' => 'Transferencia para outra instituicao',
                'curso' => 'Tecnico em Mecanica',
                'turno_refeicao' => 'almoco',
            ],
            [
                'matricula' => '20221160103',
                'nome' => 'Zelia Zanetti',
                'email' => 'zelia.zanetti@aluno.ifba.edu.br',
                'desligado_em' => now()->subDays(60),
                'desligado_motivo' => 'Motivos academicos',
                'curso' => 'Tecnico em Edificacoes',
                'turno_refeicao' => 'almoco',
            ],
        ];

        foreach ($desligados as $dados) {
            User::create(array_merge($dados, [
                'password' => Hash::make('password'),
                'perfil' => PerfilUsuario::ESTUDANTE,
                'bolsista' => true,
                'desligado' => true,
                'email_verified_at' => now(),
            ]));
        }

        $this->command->info('5 estudante desligado criado (para teste)');
        
        $this->command->info('');
        $this->command->info('RESUMO DOS USUARIOS:');
        $this->command->info('   Admin: matricula 10000000001, senha: password');
        $this->command->info('   20 Bolsistas ativos (matriculas 20232360001-020)');
        $this->command->info('   5 Nao-bolsistas (matriculas 20232160001-005)');
        $this->command->info('   5 Desligado (matricula 20221160099)');
        $this->command->info('   5 Bolsistas pendentes na lista (matriculas 20232360021-025)');
    }
}

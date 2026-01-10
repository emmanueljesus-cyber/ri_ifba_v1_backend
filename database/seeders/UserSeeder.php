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
        
        $this->command->info('✅ Admin criado: ' . $admin->matricula);

        // ========================================
        // ESTUDANTES BOLSISTAS
        // Matrículas padrão IFBA: 11 dígitos (ex: 20212160036)
        // ========================================
        $bolsistasData = [
            ['matricula' => '20231160001', 'nome' => 'João Silva Santos', 'email' => 'joao.silva@aluno.ifba.edu.br', 'curso' => 'Técnico em Informática', 'turno' => 'matutino'],
            ['matricula' => '20231160002', 'nome' => 'Maria Oliveira Costa', 'email' => 'maria.oliveira@aluno.ifba.edu.br', 'curso' => 'Técnico em Química', 'turno' => 'vespertino'],
            ['matricula' => '20231160003', 'nome' => 'Pedro Henrique Souza', 'email' => 'pedro.souza@aluno.ifba.edu.br', 'curso' => 'Técnico em Eletrônica', 'turno' => 'matutino'],
            ['matricula' => '20231160004', 'nome' => 'Ana Paula Rodrigues', 'email' => 'ana.rodrigues@aluno.ifba.edu.br', 'curso' => 'Técnico em Mecânica', 'turno' => 'vespertino'],
            ['matricula' => '20231160005', 'nome' => 'Lucas Ferreira Alves', 'email' => 'lucas.alves@aluno.ifba.edu.br', 'curso' => 'Técnico em Edificações', 'turno' => 'matutino'],
            ['matricula' => '20231160006', 'nome' => 'Juliana Lima Pereira', 'email' => 'juliana.lima@aluno.ifba.edu.br', 'curso' => 'Técnico em Informática', 'turno' => 'noturno'],
            ['matricula' => '20231160007', 'nome' => 'Rafael Costa Martins', 'email' => 'rafael.costa@aluno.ifba.edu.br', 'curso' => 'Técnico em Química', 'turno' => 'matutino'],
            ['matricula' => '20231160008', 'nome' => 'Fernanda Rodrigues Silva', 'email' => 'fernanda.rodrigues@aluno.ifba.edu.br', 'curso' => 'Técnico em Eletrônica', 'turno' => 'vespertino'],
            ['matricula' => '20231160009', 'nome' => 'Gabriel Almeida Santos', 'email' => 'gabriel.almeida@aluno.ifba.edu.br', 'curso' => 'Técnico em Mecânica', 'turno' => 'matutino'],
            ['matricula' => '20231160010', 'nome' => 'Beatriz Martins Costa', 'email' => 'beatriz.martins@aluno.ifba.edu.br', 'curso' => 'Técnico em Edificações', 'turno' => 'vespertino'],
            ['matricula' => '20231160011', 'nome' => 'Thiago Ferreira Lima', 'email' => 'thiago.ferreira@aluno.ifba.edu.br', 'curso' => 'Técnico em Informática', 'turno' => 'matutino'],
            ['matricula' => '20231160012', 'nome' => 'Amanda Dias Oliveira', 'email' => 'amanda.dias@aluno.ifba.edu.br', 'curso' => 'Técnico em Química', 'turno' => 'noturno'],
            ['matricula' => '20231160013', 'nome' => 'Bruno Nascimento Costa', 'email' => 'bruno.nascimento@aluno.ifba.edu.br', 'curso' => 'Técnico em Eletrônica', 'turno' => 'matutino'],
            ['matricula' => '20231160014', 'nome' => 'Isabela Cardoso Silva', 'email' => 'isabela.cardoso@aluno.ifba.edu.br', 'curso' => 'Técnico em Mecânica', 'turno' => 'vespertino'],
            ['matricula' => '20231160015', 'nome' => 'Vitor Monteiro Santos', 'email' => 'vitor.monteiro@aluno.ifba.edu.br', 'curso' => 'Técnico em Edificações', 'turno' => 'matutino'],
            ['matricula' => '20231160016', 'nome' => 'Letícia Rocha Pereira', 'email' => 'leticia.rocha@aluno.ifba.edu.br', 'curso' => 'Técnico em Informática', 'turno' => 'vespertino'],
            ['matricula' => '20231160017', 'nome' => 'Carlos Eduardo Mendes', 'email' => 'carlos.mendes@aluno.ifba.edu.br', 'curso' => 'Técnico em Química', 'turno' => 'matutino'],
            ['matricula' => '20231160018', 'nome' => 'Mariana Souza Lima', 'email' => 'mariana.souza@aluno.ifba.edu.br', 'curso' => 'Técnico em Eletrônica', 'turno' => 'noturno'],
            ['matricula' => '20231160019', 'nome' => 'Felipe Barbosa Costa', 'email' => 'felipe.barbosa@aluno.ifba.edu.br', 'curso' => 'Técnico em Mecânica', 'turno' => 'matutino'],
            ['matricula' => '20231160020', 'nome' => 'Larissa Ribeiro Alves', 'email' => 'larissa.ribeiro@aluno.ifba.edu.br', 'curso' => 'Técnico em Edificações', 'turno' => 'vespertino'],
        ];

        foreach ($bolsistasData as $index => $dados) {
            $user = User::create([
                'matricula' => $dados['matricula'],
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'password' => Hash::make('password'),
                'perfil' => PerfilUsuario::ESTUDANTE,
                'bolsista' => true,
                'desligado' => false,
                'curso' => $dados['curso'],
                'turno' => $dados['turno'],
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

        $this->command->info('✅ ' . count($bolsistasData) . ' estudantes bolsistas criados');

        // ========================================
        // ESTUDANTES NÃO-BOLSISTAS
        // (para testar fila extra)
        // ========================================
        $naoBolsistasData = [
            ['matricula' => '20232160001', 'nome' => 'Ana Carolina Lima', 'email' => 'ana.lima@aluno.ifba.edu.br', 'curso' => 'Técnico em Informática', 'turno' => 'matutino'],
            ['matricula' => '20232160002', 'nome' => 'Diego Santos Costa', 'email' => 'diego.santos@aluno.ifba.edu.br', 'curso' => 'Técnico em Química', 'turno' => 'vespertino'],
            ['matricula' => '20232160003', 'nome' => 'Camila Pereira Oliveira', 'email' => 'camila.pereira@aluno.ifba.edu.br', 'curso' => 'Técnico em Eletrônica', 'turno' => 'matutino'],
            ['matricula' => '20232160004', 'nome' => 'Ricardo Almeida Silva', 'email' => 'ricardo.almeida@aluno.ifba.edu.br', 'curso' => 'Técnico em Mecânica', 'turno' => 'vespertino'],
            ['matricula' => '20232160005', 'nome' => 'Paula Ferreira Santos', 'email' => 'paula.ferreira@aluno.ifba.edu.br', 'curso' => 'Técnico em Edificações', 'turno' => 'noturno'],
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
                'turno' => $dados['turno'],
                'limite_faltas_mes' => 0,
                'email_verified_at' => now(),
            ]);
        }

        $this->command->info('✅ ' . count($naoBolsistasData) . ' estudantes não-bolsistas criados');

        // ========================================
        // ESTUDANTE DESLIGADO (para teste)
        // ========================================
        User::create([
            'matricula' => '20221160099',
            'nome' => 'Roberto Silva Desligado',
            'email' => 'roberto.desligado@aluno.ifba.edu.br',
            'password' => Hash::make('password'),
            'perfil' => PerfilUsuario::ESTUDANTE,
            'bolsista' => true,
            'desligado' => true,
            'desligado_em' => now()->subDays(30),
            'desligado_motivo' => 'Excesso de faltas injustificadas',
            'curso' => 'Técnico em Informática',
            'turno' => 'matutino',
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ 1 estudante desligado criado (para teste)');
        
        // ========================================
        // RESUMO
        // ========================================
        $this->command->info('');
        $this->command->info('📊 RESUMO DOS USUÁRIOS:');
        $this->command->info('   🔑 Admin: matrícula 10000000001, senha: password');
        $this->command->info('   📚 20 Bolsistas ativos (matrículas 20231160001-020)');
        $this->command->info('   📋 5 Não-bolsistas (matrículas 20232160001-005)');
        $this->command->info('   ❌ 1 Desligado (matrícula 20221160099)');
        $this->command->info('   📌 5 Bolsistas pendentes na lista (matrículas 20231160021-025)');
    }
}

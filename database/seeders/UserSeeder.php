<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'matricula' => '100001',
            'nome' => 'Administrador do Sistema',
            'email' => 'admin@ifba.edu.br',
            'password' => Hash::make('password'),
            'perfil' => 'admin',
            'bolsista' => false,
            'email_verified_at' => now(),
        ]);

        // Estudantes Bolsistas
        User::create([
            'matricula' => '202301001',
            'nome' => 'João Silva Santos',
            'email' => 'joao.silva@aluno.ifba.edu.br',
            'password' => Hash::make('password'),
            'perfil' => 'estudante',
            'bolsista' => true,
            'curso' => 'Técnico em Informática',
            'turno' => 'Matutino',
            'limite_faltas_mes' => 3,
            'email_verified_at' => now(),
        ]);

        User::create([
            'matricula' => '202301002',
            'nome' => 'Maria Oliveira Costa',
            'email' => 'maria.oliveira@aluno.ifba.edu.br',
            'password' => Hash::make('password'),
            'perfil' => 'estudante',
            'bolsista' => true,
            'curso' => 'Técnico em Química',
            'turno' => 'Vespertino',
            'limite_faltas_mes' => 3,
            'email_verified_at' => now(),
        ]);

        User::create([
            'matricula' => '202301003',
            'nome' => 'Pedro Henrique Souza',
            'email' => 'pedro.souza@aluno.ifba.edu.br',
            'password' => Hash::make('password'),
            'perfil' => 'estudante',
            'bolsista' => true,
            'curso' => 'Técnico em Eletrônica',
            'turno' => 'Matutino',
            'limite_faltas_mes' => 3,
            'email_verified_at' => now(),
        ]);

        User::create([
            'matricula' => '202301004',
            'nome' => 'Ana Paula Rodrigues',
            'email' => 'ana.rodrigues@aluno.ifba.edu.br',
            'password' => Hash::make('password'),
            'perfil' => 'estudante',
            'bolsista' => true,
            'curso' => 'Técnico em Mecânica',
            'turno' => 'Vespertino',
            'limite_faltas_mes' => 3,
            'email_verified_at' => now(),
        ]);

        User::create([
            'matricula' => '202301005',
            'nome' => 'Lucas Ferreira Alves',
            'email' => 'lucas.alves@aluno.ifba.edu.br',
            'password' => Hash::make('password'),
            'perfil' => 'estudante',
            'bolsista' => true,
            'curso' => 'Técnico em Edificações',
            'turno' => 'Matutino',
            'limite_faltas_mes' => 3,
            'email_verified_at' => now(),
        ]);

        // Estudantes Não-Bolsistas
        User::create([
            'matricula' => '202301006',
            'nome' => 'Ana Carolina Lima',
            'email' => 'ana.lima@aluno.ifba.edu.br',
            'password' => Hash::make('password'),
            'perfil' => 'estudante',
            'bolsista' => false,
            'curso' => 'Técnico em Informática',
            'turno' => 'Matutino',
            'limite_faltas_mes' => 3,
            'email_verified_at' => now(),
        ]);

        User::create([
            'matricula' => '202301007',
            'nome' => 'Carlos Eduardo Mendes',
            'email' => 'carlos.mendes@aluno.ifba.edu.br',
            'password' => Hash::make('password'),
            'perfil' => 'estudante',
            'bolsista' => false,
            'curso' => 'Técnico em Química',
            'turno' => 'Vespertino',
            'limite_faltas_mes' => 3,
            'email_verified_at' => now(),
        ]);

        User::create([
            'matricula' => '202301008',
            'nome' => 'Fernanda Santos Oliveira',
            'email' => 'fernanda.santos@aluno.ifba.edu.br',
            'password' => Hash::make('password'),
            'perfil' => 'estudante',
            'bolsista' => false,
            'curso' => 'Técnico em Eletrônica',
            'turno' => 'Matutino',
            'limite_faltas_mes' => 3,
            'email_verified_at' => now(),
        ]);

        User::create([
            'matricula' => '202301009',
            'nome' => 'Rafael Costa Silva',
            'email' => 'rafael.costa@aluno.ifba.edu.br',
            'password' => Hash::make('password'),
            'perfil' => 'estudante',
            'bolsista' => false,
            'curso' => 'Técnico em Mecânica',
            'turno' => 'Vespertino',
            'limite_faltas_mes' => 3,
            'email_verified_at' => now(),
        ]);

        // Estudante Desligado
        User::create([
            'matricula' => '202201010',
            'nome' => 'Roberto Silva Desligado',
            'email' => 'roberto.desligado@aluno.ifba.edu.br',
            'password' => Hash::make('password'),
            'perfil' => 'estudante',
            'bolsista' => true,
            'curso' => 'Técnico em Informática',
            'turno' => 'Matutino',
            'desligado' => true,
            'desligado_em' => now()->subDays(30),
            'desligado_motivo' => 'Excesso de faltas injustificadas',
            'email_verified_at' => now(),
        ]);
    }
}

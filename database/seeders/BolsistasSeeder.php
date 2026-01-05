<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\PerfilUsuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BolsistasSeeder extends Seeder
{
    public function run(): void
    {
        // Criar admin
        $admin = User::updateOrCreate(
            ['matricula' => '999999999'],
            [
                'nome' => 'Administrador do Sistema',
                'email' => 'admin@ifba.edu.br',
                'password' => Hash::make('password'),
                'perfil' => PerfilUsuario::ADMIN,
                'bolsista' => false,
                'desligado' => false,
                'curso' => 'Administração',
                'turno' => 'integral',
            ]
        );

        $this->command->info('✅ Admin criado: ' . $admin->matricula);

        // Criar 20 bolsistas
        $nomes = [
            'João Silva', 'Maria Santos', 'Pedro Costa', 'Ana Oliveira', 'Carlos Souza',
            'Juliana Lima', 'Rafael Pereira', 'Fernanda Rodrigues', 'Lucas Almeida', 'Beatriz Martins',
            'Gabriel Ferreira', 'Camila Araújo', 'Felipe Barbosa', 'Larissa Ribeiro', 'Thiago Carvalho',
            'Amanda Dias', 'Bruno Nascimento', 'Isabela Cardoso', 'Vitor Monteiro', 'Letícia Rocha'
        ];

        $cursos = ['Informática', 'Edificações', 'Química', 'Mecânica', 'Eletrotécnica'];
        $turnos = ['matutino', 'vespertino', 'noturno'];

        foreach ($nomes as $index => $nome) {
            $matricula = str_pad(20230000 + $index + 1, 9, '0', STR_PAD_LEFT);

            $user = User::updateOrCreate(
                ['matricula' => $matricula],
                [
                    'nome' => $nome,
                    'email' => 'estudante' . ($index + 1) . '@ifba.edu.br',
                    'password' => Hash::make('password'),
                    'perfil' => PerfilUsuario::ESTUDANTE,
                    'bolsista' => true,
                    'desligado' => false,
                    'curso' => $cursos[$index % count($cursos)],
                    'turno' => $turnos[$index % count($turnos)],
                ]
            );

            $numero = $index + 1;
            $this->command->info("✅ Bolsista {$numero}: {$user->matricula} - {$user->nome}");
        }

        $this->command->info('');
        $this->command->info('✅ Bolsistas criados com sucesso!');
        $this->command->info('📊 Total: 1 Admin + 20 Bolsistas');
        $this->command->info('🔑 Senha padrão para todos: password');
    }
}


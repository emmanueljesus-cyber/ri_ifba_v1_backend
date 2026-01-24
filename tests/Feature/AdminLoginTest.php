<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\PerfilUsuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_seeder_credentials(): void
    {
        // Simular o que o UserSeeder faz
        User::create([
            'matricula' => '10000000001',
            'nome' => 'Administrador do Sistema',
            'email' => 'admin@ifba.edu.br',
            'password' => Hash::make('password'),
            'perfil' => PerfilUsuario::ADMIN,
            'bolsista' => false,
            'desligado' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'matricula' => '10000000001',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Login realizado com sucesso')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'nome', 'matricula', 'perfil']
                ]
            ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::create([
            'matricula' => '10000000001',
            'nome' => 'Administrador do Sistema',
            'email' => 'admin@ifba.edu.br',
            'password' => Hash::make('password'),
            'perfil' => PerfilUsuario::ADMIN,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'matricula' => '10000000001',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Matrícula ou senha incorretos');
    }
}

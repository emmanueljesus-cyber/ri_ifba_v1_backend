<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_create_user_with_duplicate_email()
    {
        // Criar um admin inicial
        User::create([
            'nome' => 'Admin Existente',
            'email' => 'admin@ifba.edu.br',
            'perfil' => 'admin',
            'matricula' => '10000000001',
            'password' => bcrypt('password')
        ]);

        $admin = User::create([
            'nome' => 'Super Admin',
            'email' => 'super@ifba.edu.br',
            'perfil' => 'admin',
            'matricula' => '10000000000',
            'password' => bcrypt('password')
        ]);

        // Tentar criar outro com o mesmo email via API
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/usuarios', [
                'nome' => 'Novo Admin',
                'email' => 'admin@ifba.edu.br',
                'matricula' => '10000000002',
                'perfil' => 'admin'
            ]);

        $response->assertStatus(422);
        // O Laravel retorna os erros dentro da chave 'errors' no 422
        $response->assertJsonStructure(['errors' => ['email']]);
    }
}

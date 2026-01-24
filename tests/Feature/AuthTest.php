<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa login com credenciais válidas.
     */
    public function test_usuario_pode_logar_com_credenciais_validas(): void
    {
        $user = User::factory()->create([
            'matricula' => '20230000001',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'matricula' => '20230000001',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'nome',
                        'matricula',
                        'perfil',
                    ]
                ],
                'message'
            ]);
    }

    /**
     * Testa login com senha inválida.
     */
    public function test_usuario_nao_pode_logar_com_senha_invalida(): void
    {
        $user = User::factory()->create([
            'matricula' => '20230000002',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'matricula' => '20230000002',
            'password' => 'senha-errada',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Testa logout.
     */
    public function test_usuario_pode_fazer_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
        $this->assertEmpty($user->tokens);
    }
}

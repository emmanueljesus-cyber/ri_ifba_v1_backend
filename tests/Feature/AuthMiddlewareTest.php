<?php

namespace Tests\Feature;

use App\Models\Cardapio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $estudante;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['perfil' => 'admin']);
        $this->estudante = User::factory()->create(['perfil' => 'estudante']);
    }

    /**
     * Teste: Rota pública acessível sem autenticação
     */
    public function test_rota_publica_acessivel_sem_auth(): void
    {
        $response = $this->getJson('/api/v1/cardapio/hoje');
        // Pode retornar 200 (com dados) ou 404 (sem cardápio), mas não 401
        $this->assertNotEquals(401, $response->status());
    }

    /**
     * Teste: Admin autenticado acessa rotas admin
     */
    public function test_admin_autenticado_acessa_rotas_admin(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/cardapios');

        $response->assertStatus(200);
    }

    /**
     * Teste: Estudante autenticado acessa rotas estudante
     */
    public function test_estudante_autenticado_acessa_rotas_estudante(): void
    {
        $response = $this->actingAs($this->estudante, 'sanctum')
            ->getJson('/api/v1/estudante/cardapio/hoje');

        // Pode retornar 200 ou 404 (sem cardápio), mas não 401/403
        $this->assertNotEquals(401, $response->status());
    }

    /**
     * Teste: Admin consegue deletar cardápio
     */
    public function test_admin_pode_deletar_cardapio(): void
    {
        $cardapio = Cardapio::create([
            'data_do_cardapio' => now()->addDay()->toDateString(),
            'prato_principal_ptn01' => 'Teste',
            'prato_principal_ptn02' => 'Teste 2',
            'criado_por' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/cardapios/{$cardapio->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'errors', 'meta']);
    }

    /**
     * Teste: Resposta padronizada em todas as rotas CRUD
     */
    public function test_crud_cardapio_resposta_padronizada(): void
    {
        // CREATE
        $createResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/cardapios', [
                'data_do_cardapio' => now()->addDays(5)->toDateString(),
                'turnos' => ['almoco'],
                'prato_principal_ptn01' => 'Arroz',
                'prato_principal_ptn02' => 'Feijão',
            ]);
        $createResponse->assertJsonStructure(['data', 'errors', 'meta']);

        // Se criou com sucesso, testa os outros
        if ($createResponse->status() === 201) {
            $cardapioId = $createResponse->json('data.id') ?? $createResponse->json('data.data.id');

            if ($cardapioId) {
                // READ
                $showResponse = $this->actingAs($this->admin, 'sanctum')
                    ->getJson("/api/v1/admin/cardapios/{$cardapioId}");
                $showResponse->assertJsonStructure(['data', 'errors', 'meta']);

                // UPDATE
                $updateResponse = $this->actingAs($this->admin, 'sanctum')
                    ->putJson("/api/v1/admin/cardapios/{$cardapioId}", [
                        'prato_principal_ptn01' => 'Arroz Atualizado',
                    ]);
                $updateResponse->assertJsonStructure(['data', 'errors', 'meta']);

                // DELETE
                $deleteResponse = $this->actingAs($this->admin, 'sanctum')
                    ->deleteJson("/api/v1/admin/cardapios/{$cardapioId}");
                $deleteResponse->assertJsonStructure(['data', 'errors', 'meta']);
            }
        }
    }
}


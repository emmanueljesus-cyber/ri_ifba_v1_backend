<?php

namespace Tests\Feature;

use App\Models\Cardapio;
use App\Models\Refeicao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardapioControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $estudante;
    private Cardapio $cardapio;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar usuários de teste
        $this->admin = User::factory()->create(['perfil' => 'admin']);
        $this->estudante = User::factory()->create(['perfil' => 'estudante']);

        // Criar um cardápio de teste
        $this->cardapio = Cardapio::create([
            'data_do_cardapio'      => now()->toDateString(),
            'prato_principal_ptn01' => 'Arroz com Feijão',
            'prato_principal_ptn02' => 'Feijão Tropeiro',
            'acompanhamento_01'     => 'Abóbora',
            'acompanhamento_02'     => 'Batata',
            'criado_por'            => $this->admin->id,
        ]);

        Refeicao::create([
            'cardapio_id'      => $this->cardapio->id,
            'data_do_cardapio' => $this->cardapio->data_do_cardapio,
            'turno'            => 'almoco',
            'capacidade'       => 100,
        ]);
    }

    /**
     * Teste: Rota pública consegue listar cardápios
     */
    public function test_publico_pode_listar_cardapios_semanais()
    {
        $response = $this->getJson('/api/v1/cardapio/semanal');
        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    /**
     * Teste: Rota pública consegue buscar cardápio de hoje
     */
    public function test_publico_pode_buscar_cardapio_hoje()
    {
        $response = $this->getJson('/api/v1/cardapio/hoje');
        $response->assertStatus(200);
    }

    /**
     * Teste: Estudante autenticado consegue buscar cardápio de hoje
     */
    public function test_estudante_autenticado_pode_buscar_cardapio_hoje()
    {
        $response = $this->actingAs($this->estudante, 'sanctum')
            ->getJson('/api/v1/estudante/cardapio/hoje');
        
        $response->assertStatus(200);
    }

    /**
     * Teste: Estudante não autenticado não consegue acessar rota protegida
     */
    public function test_estudante_nao_autenticado_nao_acessa_rota_protegida()
    {
        $response = $this->getJson('/api/v1/estudante/cardapio/hoje');
        $response->assertStatus(401); // Unauthorized
    }

    /**
     * Teste: Admin consegue listar cardápios
     */
    public function test_admin_pode_listar_cardapios()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/cardapios');
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'links', 'meta']);
    }

    /**
     * Teste: Admin consegue criar cardápio
     */
    public function test_admin_pode_criar_cardapio()
    {
        $data = [
            'data_do_cardapio'      => now()->addDay()->toDateString(),
            'prato_principal_ptn01' => 'Bife à Milanesa',
            'prato_principal_ptn02' => 'Frango Assado',
            'acompanhamento_01'     => 'Arroz',
            'acompanhamento_02'     => 'Batata Frita',
            'turno'                 => 'almoco',
            'capacidade'            => 150,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/cardapios', $data);

        $response->assertStatus(201);
        $response->assertJsonPath('data.prato_principal_ptn01', 'Bife à Milanesa');
    }

    /**
     * Teste: Admin consegue visualizar cardápio específico
     */
    public function test_admin_pode_visualizar_cardapio()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/admin/cardapios/{$this->cardapio->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $this->cardapio->id);
    }

    /**
     * Teste: Admin consegue atualizar cardápio
     */
    public function test_admin_pode_atualizar_cardapio()
    {
        $data = [
            'prato_principal_ptn01' => 'Bisteca com Batata',
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/cardapios/{$this->cardapio->id}", $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('cardapios', [
            'id' => $this->cardapio->id,
            'prato_principal_ptn01' => 'Bisteca com Batata',
        ]);
    }

    /**
     * Teste: Admin consegue deletar cardápio
     */
    public function test_admin_pode_deletar_cardapio()
    {
        $cardapioId = $this->cardapio->id;

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/cardapios/{$cardapioId}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('cardapios', ['id' => $cardapioId]);
    }

    /**
     * Teste: Usuário não admin não consegue acessar rotas protegidas
     */
    public function test_usuario_nao_admin_nao_acessa_rotas_admin()
    {
        $response = $this->actingAs($this->estudante, 'sanctum')
            ->getJson('/api/v1/admin/cardapios');

        $response->assertStatus(403); // Forbidden
    }

    /**
     * Teste: Cardápio não encontrado retorna 404
     */
    public function test_cardapio_nao_encontrado_retorna_404()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/cardapios/99999');

        $response->assertStatus(404);
    }
}

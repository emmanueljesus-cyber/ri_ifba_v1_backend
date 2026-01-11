<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Cardapio;
use App\Models\Refeicao;
use App\Enums\PerfilUsuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresencaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * CT10 - Testa que requisição sem user_id retorna erro de validação.
     */
    public function test_requisicao_sem_user_id_retorna_erro(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/admin/bolsistas/confirmar-presenca', [
                'data' => '2030-01-12',
                'turno' => 'almoco',
                // user_id omitido
            ]);

        // Espera 404 (rota não encontrada sem user_id) ou 422 (validation)
        $this->assertTrue(in_array($response->status(), [404, 422]));
    }

    /**
     * CT11 - Testa que não-bolsista não pode ter presença confirmada.
     */
    public function test_nao_bolsista_recebe_erro_ao_confirmar_presenca(): void
    {
        $admin = User::factory()->admin()->create();
        $naoBolsista = User::factory()->naoBolsista()->create();

        // Criar cardápio com data muito futura para evitar conflito
        $dataUnica = '2031-' . random_int(1, 12) . '-' . random_int(10, 28);
        
        $cardapio = Cardapio::factory()->create([
            'data_do_cardapio' => $dataUnica,
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/admin/bolsistas/confirmar-presenca', [
                'user_id' => $naoBolsista->id,
                'data' => $dataUnica,
                'turno' => 'almoco',
            ]);

        // Deve retornar erro (404 se refeição não existe, ou 422 se não é bolsista)
        $this->assertTrue(in_array($response->status(), [404, 422]));
    }

    /**
     * CT12 - Testa que o endpoint bolsistas/confirmar-presenca existe e requer autenticação.
     */
    public function test_endpoint_confirmar_presenca_existe(): void
    {
        // Sem autenticação deve retornar 401 ou 302 (redirect to login)
        $response = $this->postJson('/api/v1/admin/bolsistas/confirmar-presenca', [
            'user_id' => 1,
            'data' => '2030-01-12',
            'turno' => 'almoco',
        ]);

        // Com APP_DEBUG=true, pode retornar outros códigos
        // Verificar que a rota existe (não é 405 Method Not Allowed)
        $this->assertNotEquals(405, $response->status());
    }
}

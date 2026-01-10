<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Cardapio;
use App\Models\Refeicao;
use App\Models\Presenca;
use App\Enums\StatusPresenca;
use App\Enums\TurnoRefeicao;
use App\Enums\PerfilUsuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresencaValidacaoTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $estudante;
    protected $refeicao;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar admin
        $this->admin = User::factory()->create([
            'perfil' => PerfilUsuario::ADMIN,
            'matricula' => '999999999',
        ]);

        // Criar estudante bolsista
        $this->estudante = User::factory()->create([
            'perfil' => PerfilUsuario::ESTUDANTE,
            'matricula' => '202301234',
            'bolsista' => true,
        ]);

        // Criar cardápio e refeição
        $cardapio = Cardapio::factory()->create([
            'data_do_cardapio' => now()->format('Y-m-d'),
        ]);

        $this->refeicao = Refeicao::factory()->create([
            'cardapio_id' => $cardapio->id,
            'data_do_cardapio' => now()->format('Y-m-d'),
            'turno' => TurnoRefeicao::ALMOCO,
        ]);
    }

    /** @test */
    public function pode_listar_presencas_do_dia()
    {
        // Criar algumas presenças
        Presenca::factory()->count(3)->create([
            'refeicao_id' => $this->refeicao->id,
            'status_da_presenca' => StatusPresenca::PRESENTE,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/presencas');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'stats' => [
                    'total',
                    'presentes',
                    'faltas_justificadas',
                    'faltas_injustificadas',
                ],
            ]);
    }

    /** @test */
    public function pode_marcar_presenca_por_id()
    {
        $presenca = Presenca::factory()->create([
            'refeicao_id' => $this->refeicao->id,
            'user_id' => $this->estudante->id,
            'status_da_presenca' => StatusPresenca::FALTA_JUSTIFICADA,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/presencas/{$presenca->id}/validar");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Presença marcada com sucesso.',
            ]);

        $presenca->refresh();
        $this->assertEquals(StatusPresenca::PRESENTE, $presenca->status_da_presenca);
        $this->assertNotNull($presenca->validado_em);
        $this->assertEquals($this->admin->id, $presenca->validado_por);
    }

    /** @test */
    public function pode_marcar_presenca_por_qrcode()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/presencas/validar-qrcode', [
                'matricula' => $this->estudante->matricula,
                'turno' => 'almoco',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Presença marcada com sucesso!',
            ]);

        $this->assertDatabaseHas('presencas', [
            'user_id' => $this->estudante->id,
            'refeicao_id' => $this->refeicao->id,
            'status_da_presenca' => StatusPresenca::PRESENTE,
        ]);
    }

    /** @test */
    public function nao_pode_marcar_presenca_ja_marcada()
    {
        $presenca = Presenca::factory()->create([
            'refeicao_id' => $this->refeicao->id,
            'user_id' => $this->estudante->id,
            'status_da_presenca' => StatusPresenca::PRESENTE,
            'validado_em' => now(),
            'validado_por' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/presencas/{$presenca->id}/validar");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Presença já foi marcada anteriormente.',
            ]);
    }

    /** @test */
    public function pode_marcar_falta_justificada()
    {
        $presenca = Presenca::factory()->create([
            'refeicao_id' => $this->refeicao->id,
            'user_id' => $this->estudante->id,
            'status_da_presenca' => StatusPresenca::PRESENTE,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/presencas/{$presenca->id}/marcar-falta", [
                'justificada' => true,
            ]);

        $response->assertStatus(200);

        $presenca->refresh();
        $this->assertEquals(StatusPresenca::FALTA_JUSTIFICADA, $presenca->status_da_presenca);
    }

    /** @test */
    public function pode_marcar_falta_injustificada()
    {
        $presenca = Presenca::factory()->create([
            'refeicao_id' => $this->refeicao->id,
            'user_id' => $this->estudante->id,
            'status_da_presenca' => StatusPresenca::PRESENTE,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/presencas/{$presenca->id}/marcar-falta", [
                'justificada' => false,
            ]);

        $response->assertStatus(200);

        $presenca->refresh();
        $this->assertEquals(StatusPresenca::FALTA_INJUSTIFICADA, $presenca->status_da_presenca);
    }

    /** @test */
    public function pode_cancelar_presenca()
    {
        $presenca = Presenca::factory()->create([
            'refeicao_id' => $this->refeicao->id,
            'user_id' => $this->estudante->id,
            'status_da_presenca' => StatusPresenca::PRESENTE,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/presencas/{$presenca->id}/cancelar");

        $response->assertStatus(200);

        $presenca->refresh();
        $this->assertEquals(StatusPresenca::CANCELADO, $presenca->status_da_presenca);
    }

    /** @test */
    public function pode_marcar_presencas_em_lote()
    {
        $presencas = Presenca::factory()->count(3)->create([
            'refeicao_id' => $this->refeicao->id,
            'status_da_presenca' => StatusPresenca::FALTA_JUSTIFICADA,
        ]);

        $presencaIds = $presencas->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/presencas/validar-lote', [
                'presenca_ids' => $presencaIds,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'confirmadas' => 3,
                ],
            ]);

        foreach ($presencas as $presenca) {
            $presenca->refresh();
            $this->assertEquals(StatusPresenca::PRESENTE, $presenca->status_da_presenca);
        }
    }

    /** @test */
    public function nao_pode_marcar_por_qrcode_com_matricula_invalida()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/presencas/validar-qrcode', [
                'matricula' => 'INVALIDA',
                'turno' => 'almoco',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Matrícula não encontrada.',
            ]);
    }

    /** @test */
    public function nao_pode_marcar_nao_bolsista()
    {
        $naoBoLsista = User::factory()->create([
            'bolsista' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/presencas/validar-qrcode', [
                'matricula' => $naoBoLsista->matricula,
                'turno' => 'almoco',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Usuário não é bolsista.',
            ]);
    }

    /** @test */
    public function pode_obter_estatisticas_de_presenca()
    {
        // Criar várias presenças com diferentes status
        Presenca::factory()->count(2)->create([
            'refeicao_id' => $this->refeicao->id,
            'status_da_presenca' => StatusPresenca::PRESENTE,
        ]);

        Presenca::factory()->count(3)->create([
            'refeicao_id' => $this->refeicao->id,
            'status_da_presenca' => StatusPresenca::FALTA_JUSTIFICADA,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/presencas/estatisticas');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'periodo',
                    'total',
                    'por_status',
                    'taxa_presenca',
                ],
            ]);
    }

    /** @test */
    public function pode_filtrar_presencas_por_turno()
    {
        // Criar refeição de jantar
        $refeicaoJantar = Refeicao::factory()->create([
            'cardapio_id' => $this->refeicao->cardapio_id,
            'data_do_cardapio' => now()->format('Y-m-d'),
            'turno' => TurnoRefeicao::JANTAR,
        ]);

        Presenca::factory()->count(2)->create([
            'refeicao_id' => $this->refeicao->id, // almoço
        ]);

        Presenca::factory()->count(3)->create([
            'refeicao_id' => $refeicaoJantar->id, // jantar
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/presencas?turno=jantar');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function pode_filtrar_presencas_por_status()
    {
        Presenca::factory()->count(2)->create([
            'refeicao_id' => $this->refeicao->id,
            'status_da_presenca' => StatusPresenca::PRESENTE,
        ]);

        Presenca::factory()->count(3)->create([
            'refeicao_id' => $this->refeicao->id,
            'status_da_presenca' => StatusPresenca::FALTA_JUSTIFICADA,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/presencas?status=presente');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }
}

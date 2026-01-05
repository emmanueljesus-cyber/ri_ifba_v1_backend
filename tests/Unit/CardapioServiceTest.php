<?php

namespace Tests\Unit;

use App\Models\Cardapio;
use App\Models\Refeicao;
use App\Models\User;
use App\Services\CardapioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardapioServiceTest extends TestCase
{
    use RefreshDatabase;

    private CardapioService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CardapioService();
        $this->user = User::factory()->create();
    }

    /**
     * Teste: Criar um cardápio com dados válidos
     */
    public function test_criar_cardapio_com_dados_validos()
    {
        $data = [
            'data_do_cardapio'      => now()->toDateString(),
            'prato_principal_ptn01' => 'Arroz',
            'prato_principal_ptn02' => 'Feijão',
            'acompanhamento_01'     => 'Abóbora',
            'acompanhamento_02'     => 'Batata',
            'turno'                 => 'almoco',
            'capacidade'            => 100,
        ];

        $cardapio = $this->service->create($data, $this->user->id);

        $this->assertInstanceOf(Cardapio::class, $cardapio);
        $this->assertEquals('Arroz', $cardapio->prato_principal_ptn01);
        $this->assertNotNull($cardapio->refeicao);
        $this->assertEquals('almoco', $cardapio->refeicao->turno);
    }

    /**
     * Teste: Buscar cardápio por ID
     */
    public function test_buscar_cardapio_por_id()
    {
        $cardapio = Cardapio::factory()->create(['criado_por' => $this->user->id]);
        Refeicao::factory()->create(['cardapio_id' => $cardapio->id]);

        $resultado = $this->service->find($cardapio->id);

        $this->assertEquals($cardapio->id, $resultado->id);
        $this->assertNotNull($resultado->refeicao);
    }

    /**
     * Teste: Buscar cardápio inexistente lança exceção
     */
    public function test_buscar_cardapio_inexistente_lanca_excecao()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->find(99999);
    }

    /**
     * Teste: Atualizar cardápio
     */
    public function test_atualizar_cardapio()
    {
        $cardapio = Cardapio::factory()->create(['criado_por' => $this->user->id]);
        Refeicao::factory()->create(['cardapio_id' => $cardapio->id]);

        $data = [
            'prato_principal_ptn01' => 'Bife',
            'turno'                 => 'janta',
        ];

        $atualizado = $this->service->update($cardapio, $data);

        $this->assertEquals('Bife', $atualizado->prato_principal_ptn01);
        $this->assertEquals('janta', $atualizado->refeicao->turno);
    }

    /**
     * Teste: Deletar cardápio
     */
    public function test_deletar_cardapio()
    {
        $cardapio = Cardapio::factory()->create(['criado_por' => $this->user->id]);
        Refeicao::factory()->create(['cardapio_id' => $cardapio->id]);

        $cardapioId = $cardapio->id;
        $this->service->delete($cardapio);

        $this->assertNull(Cardapio::find($cardapioId));
    }

    /**
     * Teste: Buscar cardápio de hoje
     */
    public function test_buscar_cardapio_de_hoje()
    {
        // Cria um cardápio para hoje
        $cardapio = Cardapio::factory()->create([
            'data_do_cardapio' => now()->toDateString(),
            'criado_por'       => $this->user->id,
        ]);
        Refeicao::factory()->create(['cardapio_id' => $cardapio->id]);

        $resultado = $this->service->cardapioDeHoje();

        $this->assertNotNull($resultado);
        $this->assertEquals($cardapio->id, $resultado->id);
    }

    /**
     * Teste: Retorna null quando não há cardápio hoje
     */
    public function test_cardapio_hoje_retorna_null_quando_nao_existe()
    {
        $resultado = $this->service->cardapioDeHoje();
        $this->assertNull($resultado);
    }

    /**
     * Teste: Buscar cardápios da semana
     */
    public function test_buscar_cardapios_semanais()
    {
        // Cria cardápios para a semana atual
        for ($i = 0; $i < 3; $i++) {
            $cardapio = Cardapio::factory()->create([
                'data_do_cardapio' => now()->addDays($i)->toDateString(),
                'criado_por'       => $this->user->id,
            ]);
            Refeicao::factory()->create(['cardapio_id' => $cardapio->id]);
        }

        $cardapios = $this->service->cardapioSemanal();

        $this->assertCount(3, $cardapios);
    }

    /**
     * Teste: Buscar cardápios do mês
     */
    public function test_buscar_cardapios_mensais()
    {
        // Cria cardápios para o mês atual
        for ($i = 0; $i < 5; $i++) {
            $cardapio = Cardapio::factory()->create([
                'data_do_cardapio' => now()->addDays($i)->toDateString(),
                'criado_por'       => $this->user->id,
            ]);
            Refeicao::factory()->create(['cardapio_id' => $cardapio->id]);
        }

        $cardapios = $this->service->cardapioMensal();

        $this->assertGreaterThanOrEqual(5, $cardapios->count());
    }

    /**
     * Teste: Paginar cardápios
     */
    public function test_paginar_cardapios()
    {
        // Cria vários cardápios
        for ($i = 0; $i < 20; $i++) {
            $cardapio = Cardapio::factory()->create(['criado_por' => $this->user->id]);
            Refeicao::factory()->create(['cardapio_id' => $cardapio->id]);
        }

        $resultado = $this->service->paginate([], 10);

        $this->assertCount(10, $resultado->items());
        $this->assertEquals(20, $resultado->total());
        $this->assertEquals(2, $resultado->lastPage());
    }
}

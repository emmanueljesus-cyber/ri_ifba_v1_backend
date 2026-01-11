<?php

namespace Tests\Unit\Services;

use App\Models\Cardapio;
use App\Models\User;
use App\Services\CardapioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardapioServiceTest extends TestCase
{
    use RefreshDatabase;

    private CardapioService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CardapioService();
    }

    /**
     * CT06 - Testa se CardapioService pode ser instanciado corretamente.
     */
    public function test_service_pode_ser_instanciado(): void
    {
        $this->assertInstanceOf(CardapioService::class, $this->service);
    }

    /**
     * CT07 - Testa se cardápio pode ser buscado por ID.
     */
    public function test_pode_buscar_cardapio_por_id(): void
    {
        $cardapio = Cardapio::factory()->create([
            'data_do_cardapio' => '2035-01-15',
            'prato_principal_ptn01' => 'Teste Por ID',
        ]);

        $encontrado = $this->service->find($cardapio->id);

        $this->assertEquals($cardapio->id, $encontrado->id);
        $this->assertEquals('Teste Por ID', $encontrado->prato_principal_ptn01);
    }

    /**
     * CT08 - Testa busca de cardápio por data (via filter no paginate).
     */
    public function test_pode_buscar_cardapio_por_data(): void
    {
        $dataEspecifica = '2035-02-20';
        
        Cardapio::factory()->create([
            'data_do_cardapio' => $dataEspecifica,
            'prato_principal_ptn01' => 'Cardápio Específico',
        ]);

        $resultado = $this->service->paginate(['data' => $dataEspecifica]);

        $this->assertGreaterThanOrEqual(1, $resultado->total());
        $this->assertEquals('Cardápio Específico', $resultado->first()->prato_principal_ptn01);
    }

    /**
     * CT09 - Testa busca de cardápio de hoje.
     */
    public function test_pode_buscar_cardapio_de_hoje(): void
    {
        // Criar cardápio para hoje
        Cardapio::factory()->create([
            'data_do_cardapio' => now()->format('Y-m-d'),
            'prato_principal_ptn01' => 'Cardápio de Hoje',
        ]);

        $resultado = $this->service->cardapioDeHoje();

        $this->assertNotNull($resultado);
        $this->assertEquals('Cardápio de Hoje', $resultado->prato_principal_ptn01);
    }

    /**
     * CT10 - Testa busca de cardápios da semana.
     */
    public function test_pode_buscar_cardapios_da_semana(): void
    {
        // Criar cardápio para um dia desta semana
        $diaDestaSemana = now()->startOfWeek()->addDays(2);
        
        Cardapio::factory()->create([
            'data_do_cardapio' => $diaDestaSemana->format('Y-m-d'),
            'prato_principal_ptn01' => 'Cardápio Semanal',
        ]);

        $resultado = $this->service->cardapioSemanal();

        $this->assertGreaterThanOrEqual(1, $resultado->count());
    }

    /**
     * CT11 - Testa busca de cardápios do mês (paginado).
     */
    public function test_pode_buscar_cardapios_do_mes(): void
    {
        // Criar cardápio para um dia deste mês
        $diaDesteMes = now()->startOfMonth()->addDays(10);
        
        Cardapio::factory()->create([
            'data_do_cardapio' => $diaDesteMes->format('Y-m-d'),
            'prato_principal_ptn01' => 'Cardápio Mensal',
        ]);

        $resultado = $this->service->cardapioMensal();

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $resultado);
        $this->assertGreaterThanOrEqual(1, $resultado->total());
    }

    /**
     * CT12 - Testa listagem paginada de todos os cardápios.
     */
    public function test_pode_listar_cardapios_paginados(): void
    {
        $resultado = $this->service->paginate();
        
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $resultado);
    }

    /**
     * CT13 - Testa que service possui todos os métodos de busca.
     */
    public function test_service_possui_metodos_de_busca(): void
    {
        $this->assertTrue(method_exists($this->service, 'find'));
        $this->assertTrue(method_exists($this->service, 'paginate'));
        $this->assertTrue(method_exists($this->service, 'cardapioDeHoje'));
        $this->assertTrue(method_exists($this->service, 'cardapioSemanal'));
        $this->assertTrue(method_exists($this->service, 'cardapioMensal'));
    }

    /**
     * CT14 - Testa criação de um novo cardápio.
     */
    public function test_pode_criar_cardapio(): void
    {
        $user = User::factory()->create();
        $data = [
            'data_do_cardapio' => '2035-03-01',
            'prato_principal_ptn01' => 'Frango Assado',
            'prato_principal_ptn02' => 'Omelete',
            'guarnicao' => 'Purê',
            'acompanhamento_01' => 'Arroz Branco',
            'acompanhamento_02' => 'Feijão Tropeiro',
            'salada' => 'Alface',
            'turno' => 'almoco',
            'capacidade' => 300
        ];

        $cardapio = $this->service->create($data, $user->id);

        $this->assertInstanceOf(Cardapio::class, $cardapio);
        $this->assertDatabaseHas('cardapios', [
            'data_do_cardapio' => '2035-03-01',
            'prato_principal_ptn01' => 'Frango Assado',
            'criado_por' => $user->id
        ]);
        $this->assertDatabaseHas('refeicoes', [
            'cardapio_id' => $cardapio->id,
            'turno' => 'almoco',
            'capacidade' => 300
        ]);
    }

    /**
     * CT15 - Testa atualização de um cardápio existente.
     */
    public function test_pode_atualizar_cardapio(): void
    {
        $cardapio = Cardapio::factory()->create([
            'prato_principal_ptn01' => 'Carne Cozida',
            'data_do_cardapio' => '2035-03-02'
        ]);

        // A refeição é criada automaticamente pelo observer do model
        // Garantimos que ela existe para o teste
        $this->assertDatabaseHas('refeicoes', [
            'cardapio_id' => $cardapio->id,
            'turno' => 'almoco'
        ]);

        $dadosAtualizacao = [
            'prato_principal_ptn01' => 'Carne Assada',
            'turno' => 'jantar', // Mudando turno para Jantar (que tb é criado por padrão)
            'capacidade' => 150
        ];

        // Se o factory cria almoco e jantar, e atualizamos para jantar, deve funcionar.
        $atualizado = $this->service->update($cardapio, $dadosAtualizacao);

        $this->assertEquals('Carne Assada', $atualizado->prato_principal_ptn01);
        $this->assertDatabaseHas('cardapios', [
            'id' => $cardapio->id,
            'prato_principal_ptn01' => 'Carne Assada'
        ]);
        // Verifica se a refeição do turno Jantar foi atualizada
        $this->assertDatabaseHas('refeicoes', [
            'cardapio_id' => $cardapio->id,
            'turno' => 'jantar',
            'capacidade' => 150
        ]);
    }

    /**
     * CT16 - Testa remoção de um cardápio.
     */
    public function test_pode_deletar_cardapio(): void
    {
        $cardapio = Cardapio::factory()->create();

        $this->service->delete($cardapio);

        $this->assertDatabaseMissing('cardapios', ['id' => $cardapio->id]);
    }

    /**
     * CT17 - Testa createOrUpdate criando novo registro.
     */
    public function test_create_or_update_cria_novo(): void
    {
        $user = User::factory()->create();
        $data = [
            'data_do_cardapio' => '2035-03-03',
            'prato_principal_ptn01' => 'Peixe Frito',
            'prato_principal_ptn02' => 'Ovos',
            'acompanhamento_01' => 'Arroz',
            'acompanhamento_02' => 'Feijão',
            'turno' => 'almoco',
            'capacidade' => 200
        ];

        $resultado = $this->service->createOrUpdate($data, $user->id);

        $this->assertTrue($resultado['created']);
        $this->assertInstanceOf(Cardapio::class, $resultado['cardapio']);
        $this->assertEquals('Peixe Frito', $resultado['cardapio']->prato_principal_ptn01);
    }

    /**
     * CT18 - Testa createOrUpdate atualizando registro existente.
     */
    public function test_create_or_update_atualiza_existente(): void
    {
        $user = User::factory()->create();
        $cardapio = Cardapio::factory()->create([
            'data_do_cardapio' => '2035-03-04',
            'prato_principal_ptn01' => 'Prato Antigo'
        ]);

        $data = [
            'data_do_cardapio' => '2035-03-04',
            'prato_principal_ptn01' => 'Prato Novo',
            'prato_principal_ptn02' => 'Opção 2', // Campos obrigatórios no array
            'acompanhamento_01' => 'Arroz',
            'acompanhamento_02' => 'Feijão',
            'turno' => 'almoco'
        ];

        $resultado = $this->service->createOrUpdate($data, $user->id);

        $this->assertFalse($resultado['created']);
        $this->assertEquals('Prato Novo', $resultado['cardapio']->prato_principal_ptn01);
        $this->assertEquals('Prato Novo', $cardapio->refresh()->prato_principal_ptn01);
    }
}

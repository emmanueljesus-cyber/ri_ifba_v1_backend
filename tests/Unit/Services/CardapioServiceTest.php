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
}

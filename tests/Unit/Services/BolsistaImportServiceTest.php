<?php

namespace Tests\Unit\Services;

use App\Models\Bolsista;
use App\Models\User;
use App\Models\UsuarioDiaSemana;
use App\Services\BolsistaImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BolsistaImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private BolsistaImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BolsistaImportService();
    }

    /**
     * Testa a normalização e mapeamento do turno.
     */
    public function test_mapeamento_de_turno_da_planilha(): void
    {
        $rows = [
            ['matricula', 'nome', 'turno'],
            ['2021001', 'João Silva', 'Manhã'],
            ['2021002', 'Maria Souza', 'Noturno'],
            ['2021003', 'Carlos Lima', 'Integral'],
            ['2021004', 'Ana Costa', 'Jantar'],
        ];

        $resultado = $this->service->import($rows);

        $this->assertCount(4, $resultado['created']);
        
        $this->assertDatabaseHas('bolsistas', ['matricula' => '2021001', 'turno_refeicao' => 'almoco']);
        $this->assertDatabaseHas('bolsistas', ['matricula' => '2021002', 'turno_refeicao' => 'jantar']);
        $this->assertDatabaseHas('bolsistas', ['matricula' => '2021003', 'turno_refeicao' => 'almoco']);
        $this->assertDatabaseHas('bolsistas', ['matricula' => '2021004', 'turno_refeicao' => 'jantar']);
    }

    /**
     * Testa se dados são sincronizados com o User já vinculado.
     */
    public function test_sincroniza_dados_com_usuario_vinculado(): void
    {
        // 1. Criar usuário e bolsista vinculado
        $user = User::factory()->create([
            'matricula' => '2021999',
            'nome' => 'Nome Antigo',
            'curso' => 'Curso Antigo',
            'turno_refeicao' => 'almoco',
            'bolsista' => true
        ]);

        $bolsista = Bolsista::create([
            'matricula' => '2021999',
            'nome' => 'Nome Antigo',
            'curso' => 'Curso Antigo',
            'turno_refeicao' => 'almoco',
            'user_id' => $user->id,
            'ativo' => true
        ]);

        // 2. Importar planilha com dados atualizados
        $rows = [
            ['matricula', 'nome', 'curso', 'turno', 'dias_semana'],
            ['2021999', 'Nome Novo', 'Engenharia', 'Noite', '1,2,3'],
        ];

        $this->service->import($rows, null, true);

        // 3. Verificar se sincronizou no User
        $user->refresh();
        $this->assertEquals('Nome Novo', $user->nome);
        $this->assertEquals('Engenharia', $user->curso);
        $this->assertEquals('jantar', $user->turno_refeicao);
        
        // Verificar dias da semana
        $dias = $user->diasSemana->pluck('dia_semana')->toArray();
        sort($dias);
        $this->assertEquals([1, 2, 3], $dias);

        // Verificar na tabela bolsistas
        $bolsista->refresh();
        $this->assertEquals('Nome Novo', $bolsista->nome);
        $this->assertEquals('jantar', $bolsista->turno_refeicao);
    }
}

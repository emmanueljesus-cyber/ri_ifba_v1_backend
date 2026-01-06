<?php

namespace Tests\Feature;

use App\Models\Cardapio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CardapioImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['perfil' => 'admin']);
    }

    /**
     * Teste: Import de cardápio requer arquivo
     */
    public function test_import_requer_arquivo(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/cardapios/import', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    /**
     * Teste: Import rejeita arquivo com tipo inválido
     */
    public function test_import_rejeita_arquivo_tipo_invalido(): void
    {
        $file = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/cardapios/import', [
                'file' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    /**
     * Teste: Import rejeita arquivo muito grande (>5MB)
     */
    public function test_import_rejeita_arquivo_muito_grande(): void
    {
        $file = UploadedFile::fake()->create('cardapio.xlsx', 6000, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/cardapios/import', [
                'file' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    /**
     * Teste: Import aceita arquivo CSV válido
     */
    public function test_import_aceita_arquivo_csv(): void
    {
        $csvContent = "data_do_cardapio,prato_principal_ptn01,prato_principal_ptn02,guarnicao,acompanhamento_01,acompanhamento_02,salada,suco,sobremesa\n";
        $csvContent .= "2026-01-10,Arroz com Frango,Feijão Tropeiro,Farofa,Abóbora,Batata,Alface,Laranja,Banana\n";

        $file = UploadedFile::fake()->createWithContent('cardapio.csv', $csvContent);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/cardapios/import', [
                'file' => $file,
                'turno' => ['almoco'],
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data',
            'errors',
            'meta' => ['total_criados', 'total_erros'],
        ]);
    }

    /**
     * Teste: Import com debug retorna informações do arquivo
     */
    public function test_import_com_debug_retorna_info(): void
    {
        $csvContent = "data_do_cardapio,prato_principal_ptn01,prato_principal_ptn02\n";
        $csvContent .= "2026-01-10,Arroz,Feijão\n";

        $file = UploadedFile::fake()->createWithContent('cardapio.csv', $csvContent);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/cardapios/import', [
                'file' => $file,
                'debug' => true,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['primeira_linha', 'segunda_linha', 'total_linhas'],
            'meta' => ['debug'],
        ]);
    }

    /**
     * Teste: Import valida turnos com enum
     */
    public function test_import_rejeita_turno_invalido(): void
    {
        $csvContent = "data_do_cardapio,prato_principal_ptn01,prato_principal_ptn02\n";
        $csvContent .= "2026-01-10,Arroz,Feijão\n";

        $file = UploadedFile::fake()->createWithContent('cardapio.csv', $csvContent);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/cardapios/import', [
                'file' => $file,
                'turno' => ['cafe_da_manha'], // turno inválido
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['turno.0']);
    }

    /**
     * Teste: Resposta padronizada {data, errors, meta}
     */
    public function test_resposta_padronizada(): void
    {
        $csvContent = "data_do_cardapio,prato_principal_ptn01,prato_principal_ptn02\n";
        $csvContent .= "2026-01-10,Arroz,Feijão\n";

        $file = UploadedFile::fake()->createWithContent('cardapio.csv', $csvContent);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/cardapios/import', [
                'file' => $file,
            ]);

        $response->assertJsonStructure(['data', 'errors', 'meta']);
    }
}


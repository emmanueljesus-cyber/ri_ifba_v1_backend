<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Enums\PerfilUsuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * CT01 - Testa se usuário pode ser identificado como bolsista.
     */
    public function test_usuario_pode_ser_identificado_como_bolsista(): void
    {
        $bolsista = User::factory()->create(['bolsista' => true]);
        $naoBolsista = User::factory()->create(['bolsista' => false]);

        $this->assertTrue($bolsista->isBolsista());
        $this->assertFalse($naoBolsista->isBolsista());
    }

    /**
     * CT02 - Testa se usuário pode ser identificado como administrador.
     */
    public function test_usuario_pode_ser_identificado_como_admin(): void
    {
        $admin = User::factory()->create(['perfil' => PerfilUsuario::ADMIN]);
        $estudante = User::factory()->create(['perfil' => PerfilUsuario::ESTUDANTE]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($estudante->isAdmin());
    }

    /**
     * CT02b - Testa se usuário pode ser identificado como estudante.
     */
    public function test_usuario_pode_ser_identificado_como_estudante(): void
    {
        $estudante = User::factory()->create(['perfil' => PerfilUsuario::ESTUDANTE]);
        $admin = User::factory()->create(['perfil' => PerfilUsuario::ADMIN]);

        $this->assertTrue($estudante->isEstudante());
        $this->assertFalse($admin->isEstudante());
    }

    /**
     * CT03 - Testa se o sistema pode desligar um usuário.
     */
    public function test_usuario_pode_ser_desligado(): void
    {
        $user = User::factory()->create(['desligado' => false]);

        $this->assertFalse($user->desligado);
        $this->assertTrue($user->podeUsarRefeitorio());

        $user->desligar('Excesso de faltas');

        $this->assertTrue($user->desligado);
        $this->assertEquals('Excesso de faltas', $user->desligado_motivo);
        $this->assertNotNull($user->desligado_em);
        $this->assertFalse($user->podeUsarRefeitorio());
    }

    /**
     * CT04 - Testa se o sistema pode reativar um usuário.
     */
    public function test_usuario_pode_ser_reativado(): void
    {
        $user = User::factory()->create([
            'desligado' => true,
            'desligado_em' => now(),
            'desligado_motivo' => 'Teste',
        ]);

        $this->assertTrue($user->desligado);

        $user->reativar();

        $this->assertFalse($user->desligado);
        $this->assertNull($user->desligado_em);
        $this->assertNull($user->desligado_motivo);
    }

    /**
     * CT05 - Testa scopes de busca por perfil.
     */
    public function test_scopes_filtram_usuarios_corretamente(): void
    {
        User::factory()->count(3)->create(['perfil' => PerfilUsuario::ESTUDANTE]);
        User::factory()->count(2)->create(['perfil' => PerfilUsuario::ADMIN]);
        User::factory()->count(2)->create(['bolsista' => true, 'perfil' => PerfilUsuario::ESTUDANTE]);

        $this->assertEquals(5, User::estudantes()->count());
        $this->assertEquals(2, User::admins()->count());
        $this->assertEquals(2, User::bolsistas()->count());
    }
}

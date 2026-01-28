<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Services\NotificacaoService;
use App\Enums\TipoNotificacao;

class NotificacoesSeeder extends Seeder
{
    /**
     * Cria notificações de teste para estudantes e admins
     */
    public function run(): void
    {
        $service = app(NotificacaoService::class);

        // Notificações para estudantes
        $estudantes = User::where('perfil', 'estudante')->get();

        foreach ($estudantes as $estudante) {
            // Notificação de boas-vindas
            $service->criar(
                userId: $estudante->id,
                tipo: TipoNotificacao::CADASTRO_CONFIRMADO,
                titulo: 'Bem-vindo ao RI-IFBA!',
                mensagem: 'Seu cadastro foi confirmado com sucesso. Você já pode acessar todos os recursos do sistema.'
            );

            // Notificações específicas para bolsistas
            if ($estudante->bolsista) {
                $service->criar(
                    userId: $estudante->id,
                    tipo: TipoNotificacao::SUCESSO,
                    titulo: 'Carteirinha Digital Disponível',
                    mensagem: 'Sua carteirinha digital está disponível! Acesse o menu para visualizar e imprimir seu QR Code de identificação.'
                );

                $service->criar(
                    userId: $estudante->id,
                    tipo: TipoNotificacao::AVISO,
                    titulo: 'Lembre-se de Justificar Faltas',
                    mensagem: 'Caso não possa comparecer a alguma refeição, não se esqueça de enviar sua justificativa através do sistema.'
                );
            } else {
                // Notificações específicas para não-bolsistas
                $service->criar(
                    userId: $estudante->id,
                    tipo: TipoNotificacao::GERAL,
                    titulo: 'Fila de Extras',
                    mensagem: 'Você pode se inscrever na fila de extras diariamente. As vagas são liberadas conforme disponibilidade.'
                );
            }

            // Notificação geral sobre o cardápio
            $service->criar(
                userId: $estudante->id,
                tipo: TipoNotificacao::GERAL,
                titulo: 'Cardápio Semanal Atualizado',
                mensagem: 'O cardápio da semana foi atualizado. Confira as refeições disponíveis!'
            );
        }

        // Notificações para administradores
        $admins = User::where('perfil', 'admin')->get();

        foreach ($admins as $admin) {
            $service->criar(
                userId: $admin->id,
                tipo: TipoNotificacao::GERAL,
                titulo: 'Bem-vindo ao Painel Administrativo',
                mensagem: 'Você tem acesso total ao sistema. Gerencie bolsistas, cardápios, presenças e justificativas.'
            );

            $service->criar(
                userId: $admin->id,
                tipo: TipoNotificacao::AVISO,
                titulo: 'Sistema Pronto para Uso',
                mensagem: 'O sistema RI-IFBA foi configurado com sucesso. Os bolsistas podem começar a utilizar suas carteirinhas digitais.'
            );
        }

        $this->command->info('Notificações de teste criadas com sucesso!');
        $this->command->info('  - ' . $estudantes->count() . ' estudantes notificados');
        $this->command->info('  - ' . $admins->count() . ' administradores notificados');
    }
}

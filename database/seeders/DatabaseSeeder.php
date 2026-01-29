<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🚀 INICIANDO POPULAÇÃO DO BANCO DE DADOS');
        $this->command->info('==========================================');
        $this->command->info('');

        $this->call([
            // 1. Primeiro importa a lista de bolsistas aprovados (110 total: 100 ativos + 10 pendentes)
            BolsistasAprovadosSeeder::class,

            // 2. Cria usuários (admin, 100 bolsistas, 20 não-bolsistas, 5 desligados)
            UserSeeder::class,

            // 3. Cria cardápios de 01/01/2026 até 31/03/2026 (apenas dias úteis)
            CardapioMensalSeeder::class,

            // 4. Vincula bolsistas aos dias da semana específicos
            UsuarioDiaSemanaSeeder::class,

            // 5. Cria notificações de teste
            NotificacoesSeeder::class,

            // 6. Cria histórico de presenças/faltas (01/01/2026 até 28/01/2026)
            PresencaSeeder::class,

            // 7. Cria inscrições na fila extra (não-bolsistas) - histórico até 28/01/2026
            FilaExtraSeeder::class,

            // 8. Cria justificativas com documentos PDF para faltas justificadas
            JustificativaSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('==========================================');
        $this->command->info('✅ BANCO DE DADOS POPULADO COM SUCESSO!');
        $this->command->info('==========================================');
        $this->command->info('');
        $this->command->info('🔐 CREDENCIAIS DE ACESSO (senha: password):');
        $this->command->info('   👤 Admin:             10000000001');
        $this->command->info('   🎓 Bolsistas Almoço:  20232360001 até 20232360050');
        $this->command->info('   🎓 Bolsistas Jantar:  20232360051 até 20232360100');
        $this->command->info('   📚 Não-bolsistas:     20242460001 até 20242460020');
        $this->command->info('');
        $this->command->info('📌 PARA TESTAR REGISTRO DE NOVO BOLSISTA:');
        $this->command->info('   Use matrículas 20232360101 até 20232360110 (pendentes na lista)');
        $this->command->info('');
        $this->command->info('📊 DADOS GERADOS:');
        $this->command->info('   - Cardápios: 01/01/2026 a 31/03/2026 (~65 dias úteis)');
        $this->command->info('   - Presenças: histórico até 28/01/2026');
        $this->command->info('   - Fila extras: histórico até 28/01/2026');
        $this->command->info('   - Justificativas: com documentos PDF anexados');
        $this->command->info('');
    }
}

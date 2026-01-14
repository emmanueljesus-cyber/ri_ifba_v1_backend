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
            // 1. Primeiro importa a lista de bolsistas aprovados
            BolsistasAprovadosSeeder::class,

            // 2. Cria usuários (admin, bolsistas, não-bolsistas)
            UserSeeder::class,

            // 3. Cria cardápios do mês inteiro + refeições (almoço/jantar)
            CardapioMensalSeeder::class,

            // 4. Vincula bolsistas aos dias da semana específicos
            UsuarioDiaSemanaSeeder::class,

            // 5. Cria presenças para simulação
            PresencaSeeder::class,

            // 6. Cria justificativas para faltas
            JustificativaSeeder::class,

            // 7. Cria inscrições na fila extra (não-bolsistas)
            FilaExtraSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('==========================================');
        $this->command->info('✅ BANCO DE DADOS POPULADO COM SUCESSO!');
        $this->command->info('==========================================');
        $this->command->info('');
        $this->command->info('🔐 CREDENCIAIS DE ACESSO (senha: password):');
        $this->command->info('   Admin:         10000000001');
        $this->command->info('   Bolsistas:     20231160001 até 20231160020');
        $this->command->info('   Não-bolsistas: 20232160001 até 20232160005');
        $this->command->info('');
        $this->command->info('📌 PARA TESTAR REGISTRO DE NOVO BOLSISTA:');
        $this->command->info('   Use matrículas 20231160021 até 20231160025 (pendentes na lista)');
        $this->command->info('');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CardapioSeeder::class,
            RefeicaoSeeder::class,
            UsuarioDiaSemanaSeeder::class,
            PresencaSeeder::class,
            JustificativaSeeder::class,
            FilaExtraSeeder::class,
        ]);
    }
}

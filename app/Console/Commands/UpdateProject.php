<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class UpdateProject extends Command
{
    /**
     * Nome do comando no Artisan
     */
    protected $signature = 'project:update';

    /**
     * Descrição apresentada no "php artisan list"
     */
    protected $description = 'Atualiza o projeto (migrate, seed, swagger, cache)';

    public function handle()
    {
        $this->info("🔄 Iniciando a atualização do projeto...");

        Artisan::call('optimize:clear');
        $this->info("🚮 Cache limpo");

        Artisan::call('migrate');
        $this->info("📦 Migrations executadas");

        Artisan::call('db:seed');
        $this->info("🌱 Seeds executadas");


        $this->info("✅ Projeto atualizado com sucesso!");
        return self::SUCCESS;
    }
}

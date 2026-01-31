<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupProject extends Command
{
    /**
     * Nome do comando no Artisan.
     */
    protected $signature = 'project:setup';

    /**
     * Descrição exibida no "php artisan list".
     */
    protected $description = 'Configura o projeto (key, cache, migrate, seed, swagger)';

    public function handle()
    {
        $this->info("🚀 Iniciando setup completo do projeto...");

        /**
         * 1. Gerar APP_KEY
         */
        $this->section("Gerando APP_KEY...");
        Artisan::call('key:generate');
        $this->success("APP_KEY gerada!");

        /**
         * 2. Rodar migrations
         */
        $this->section("Executando migrations...");
        Artisan::call('migrate:fresh');
        $this->success("Migrations executadas!");

        /**
         * 3. Rodar seeds
         */
        $this->section("Executando seeds...");
        Artisan::call('db:seed');
        $this->success("Seeds executadas!");

        /**
         * 4. Gerar documentação Swagger
         */
        $this->section("Gerando documentação Swagger...");
        Artisan::call('l5-swagger:generate');
        $this->success("Swagger gerado!");

        /**
         * Finalização
         */
        $this->info("\n✨ Projeto configurado com sucesso!");
        $this->info("📄 Acesse a documentação em: http://localhost:8889/api/v1/documentation");

        return self::SUCCESS;
    }

    /**
     * Exibe um título bonitinho para cada etapa.
     */
    private function section(string $text)
    {
        $this->info("\n🔧 $text");
    }

    /**
     * Marca o término de uma etapa.
     */
    private function success(string $text)
    {
        $this->info("✔ $text");
    }
}
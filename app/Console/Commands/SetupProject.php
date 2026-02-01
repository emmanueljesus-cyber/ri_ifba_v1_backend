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
		 * 0. Limpar cache de configuração para garantir leitura das variáveis de ambiente
		 */
		$this->section("Limpando cache de configuração...");
		Artisan::call('config:clear');
		$this->success("Cache limpo!");

		/**
		 * 1. Gerar APP_KEY (se não existir)
		 */
		if (empty(config('app.key'))) {
			$this->section("Gerando APP_KEY...");
			Artisan::call('key:generate', ['--force' => true]);
			$this->success("APP_KEY gerada!");
		} else {
			$this->info("✓ APP_KEY já existe");
		}

		/**
		 * 2. Rodar migrations
		 * Em produção, usa migrate --force (não apaga dados)
		 * Em desenvolvimento, pode usar migrate:fresh se necessário
		 */
		$this->section("Executando migrations...");

		try {
			// Sempre usa migrate --force para segurança em produção
			Artisan::call('migrate', ['--force' => true]);
			$this->success("Migrations executadas!");
		} catch (\Exception $e) {
			$this->error("Erro ao executar migrations: " . $e->getMessage());
			return self::FAILURE;
		}

		/**
		 * 3. Rodar seeds (apenas se o banco estiver vazio ou em desenvolvimento)
		 */
		$this->section("Verificando seeds...");

		try {
			// Verifica se já existem usuários no banco
			$userCount = \DB::table('users')->count();

			if ($userCount === 0) {
				$this->info("Banco vazio, executando seeds...");
				Artisan::call('db:seed', ['--force' => true]);
				$this->success("Seeds executadas!");
			} else {
				$this->info("✓ Banco já possui dados ($userCount usuários), pulando seeds");
			}
		} catch (\Exception $e) {
			$this->warning("Aviso ao verificar/executar seeds: " . $e->getMessage());
			// Não retorna erro, pois seeds podem falhar em alguns cenários
		}

		/**
		 * Finalização
		 */
		$this->info("\n✨ Projeto configurado com sucesso!");

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
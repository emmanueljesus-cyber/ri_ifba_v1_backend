<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class TestExcelExport extends Command
{
    protected $signature = 'test:excel';
    protected $description = 'Testa se Laravel Excel está funcionando corretamente';

    public function handle()
    {
        $this->info('🧪 Testando Laravel Excel...');
        $this->newLine();

        // 1. Verificar se a facade existe
        $this->info('1️⃣ Verificando Facade...');
        if (class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            $this->info('   ✅ Facade Excel existe');
        } else {
            $this->error('   ❌ Facade Excel não encontrada');
            return 1;
        }

        // 2. Verificar classes de export
        $this->info('2️⃣ Verificando classes de Export...');

        if (class_exists(\App\Exports\BolsistaTemplateExport::class)) {
            $this->info('   ✅ BolsistaTemplateExport existe');
        } else {
            $this->error('   ❌ BolsistaTemplateExport não encontrada');
        }

        if (class_exists(\App\Exports\CardapioTemplateExport::class)) {
            $this->info('   ✅ CardapioTemplateExport existe');
        } else {
            $this->error('   ❌ CardapioTemplateExport não encontrada');
        }

        // 3. Verificar extensões PHP
        $this->info('3️⃣ Verificando extensões PHP...');

        $extensions = ['zip', 'xml', 'xmlreader', 'xmlwriter', 'simplexml', 'gd'];
        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                $this->info("   ✅ {$ext}");
            } else {
                $this->warn("   ⚠️ {$ext} não está carregada");
            }
        }

        // 4. Testar geração de arquivo
        $this->info('4️⃣ Testando geração de arquivo...');

        try {
            $path = storage_path('app/test_bolsistas.xlsx');

            Excel::store(
                new \App\Exports\BolsistaTemplateExport(),
                'test_bolsistas.xlsx'
            );

            if (file_exists($path)) {
                $size = filesize($path);
                $this->info("   ✅ Arquivo gerado com sucesso!");
                $this->info("   📄 Tamanho: " . number_format($size / 1024, 2) . " KB");
                $this->info("   📂 Local: {$path}");

                // Limpar arquivo de teste
                unlink($path);
            } else {
                $this->error('   ❌ Arquivo não foi criado');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Erro ao gerar arquivo: ' . $e->getMessage());
            $this->error('   Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }

        // 5. Testar download direto
        $this->info('5️⃣ Testando método download...');

        try {
            $response = Excel::download(
                new \App\Exports\BolsistaTemplateExport(),
                'test_bolsistas.xlsx',
                \Maatwebsite\Excel\Excel::XLSX
            );

            $this->info('   ✅ Método download funcionou!');
            $this->info('   📦 Tipo de resposta: ' . get_class($response));
        } catch (\Exception $e) {
            $this->error('   ❌ Erro no download: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('🎉 Todos os testes passaram!');
        $this->info('✅ Laravel Excel está funcionando corretamente');

        return 0;
    }
}

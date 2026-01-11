<?php

namespace App\Console\Commands;

use App\Mail\JustificativaDecisaoMail;
use App\Models\Justificativa;
use App\Models\User;
use App\Enums\StatusJustificativa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Comando para testar envio de e-mail de decisão de justificativa
 */
class TestMailJustificativa extends Command
{
    protected $signature = 'mail:test-justificativa {email?}';
    protected $description = 'Testa o envio de e-mail de decisão de justificativa';

    public function handle()
    {
        $email = $this->argument('email') ?? 'emmanuelvitor.ti@gmail.com';
        
        $this->info("Testando envio de e-mail para: {$email}");
        $this->newLine();

        // Buscar ou criar dados fake para teste
        $justificativa = $this->getOrCreateFakeJustificativa($email);

        $this->info('Dados da justificativa:');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['ID', $justificativa->id ?? 'FAKE'],
                ['Status', $justificativa->status_justificativa->value ?? 'aprovada'],
                ['Estudante', $justificativa->usuario->name ?? 'Estudante Teste'],
                ['E-mail destino', $email],
                ['Motivo', substr($justificativa->motivo ?? 'Teste de envio', 0, 50)],
            ]
        );

        $this->newLine();
        $this->info('Enviando e-mail...');

        try {
            // Envia direto (sem queue) para ver resultado imediato
            Mail::to($email)->send(new JustificativaDecisaoMail($justificativa));
            
            $this->newLine();
            $this->info('✅ E-mail enviado com sucesso para: ' . $email);
            $this->info('Verifique a caixa de entrada (e spam).');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Erro ao enviar e-mail: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Verifique as configurações MAIL_* no .env');
            $this->warn('Dica: Para Gmail, você precisa de uma "Senha de App"');
            
            return 1;
        }
    }

    /**
     * Busca justificativa existente ou cria uma fake para teste
     */
    private function getOrCreateFakeJustificativa(string $email): Justificativa
    {
        // Primeiro, tenta buscar uma real
        $justificativa = Justificativa::with(['usuario', 'presenca.refeicao.cardapio'])->first();

        if ($justificativa) {
            return $justificativa;
        }

        // Se não existe, cria uma fake (não salva no banco)
        $this->warn('Nenhuma justificativa no banco. Criando dados fake para teste...');
        $this->newLine();

        // Cria usuário fake
        $fakeUser = new User([
            'name' => 'Estudante Teste',
            'email' => $email,
            'matricula' => '20231234567',
        ]);

        // Cria justificativa fake
        $fakeJustificativa = new Justificativa([
            'id' => 999,
            'motivo' => 'Consulta médica de rotina',
            'status_justificativa' => StatusJustificativa::APROVADA,
            'observacao_admin' => 'Documentação comprovada. Falta justificada.',
            'aprovado_em' => now(),
        ]);

        // Associa o usuário fake
        $fakeJustificativa->setRelation('usuario', $fakeUser);

        return $fakeJustificativa;
    }
}

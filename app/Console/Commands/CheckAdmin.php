<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CheckAdmin extends Command
{
    protected $signature = 'app:check-admin';
    protected $description = 'Verifica se o usuário admin padrão existe e se a senha confere';

    public function handle()
    {
        $matricula = '10000000001';
        $user = User::where('matricula', $matricula)->first();

        if (!$user) {
            $this->error("Usuário com matrícula {$matricula} não encontrado no banco de dados.");
            return 1;
        }

        $this->info("Usuário encontrado: {$user->nome}");
        $this->info("E-mail: {$user->email}");
        $this->info("Perfil: " . (is_object($user->perfil) ? $user->perfil->value : $user->perfil));
        $this->info("Desligado: " . ($user->desligado ? 'SIM' : 'NÃO'));

        $passwordMatch = Hash::check('password', $user->password);
        
        if ($passwordMatch) {
            $this->info("Senha 'password' confere com o hash no banco: SIM ✅");
        } else {
            $this->error("Senha 'password' confere com o hash no banco: NÃO ❌");
            $this->line("Hash atual: {$user->password}");
            
            if ($this->confirm('Deseja resetar a senha para "password"?')) {
                $user->password = Hash::make('password');
                $user->save();
                $this->info("Senha resetada com sucesso!");
            }
        }

        return 0;
    }
}

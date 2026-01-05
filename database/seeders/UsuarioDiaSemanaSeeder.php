<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsuarioDiaSemanaSeeder extends Seeder
{
    public function run(): void
    {
        $estudantes = User::where('perfil', 'estudante')
            ->where('desligado', false)
            ->get();

        foreach ($estudantes as $estudante) {
            // Segunda a Sexta (1, 2, 3, 4, 5)
            for ($dia = 1; $dia <= 5; $dia++) {
                DB::table('usuario_dias_semana')->insert([
                    'user_id' => $estudante->id,
                    'dia_semana' => $dia,
                ]);
            }
        }
    }
}

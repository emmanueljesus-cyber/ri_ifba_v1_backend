<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class BolsistaImportService
{
    /**
     * Processa linhas do Excel e retorna arrays de criados, atualizados e erros.
     *
     * @param array $rows    Linhas da planilha (primeira sheet)
     * @param int|null $userId Usuário que realizou a importação
     */
    public function import(array $rows, ?int $userId): array
    {
        if (empty($rows)) {
            return ['created' => [], 'updated' => [], 'errors' => [['erro' => 'Arquivo vazio']]];
        }

        // Primeira linha = cabeçalhos
        $headers = array_map(fn($h) => mb_strtolower(trim($h ?? '')), $rows[0]);

        // Mapear índices das colunas
        $colMap = [
            'matricula' => array_search('matricula', $headers),
            'nome' => array_search('nome', $headers),
            'email' => array_search('email', $headers),
            'turno' => array_search('turno', $headers),
            'curso' => array_search('curso', $headers),
        ];

        // Verificar se todas as colunas obrigatórias existem
        $required = ['matricula', 'nome', 'email', 'turno'];
        foreach ($required as $col) {
            if ($colMap[$col] === false) {
                return [
                    'created' => [],
                    'updated' => [],
                    'errors' => [['erro' => "Coluna obrigatória '{$col}' não encontrada no arquivo"]]
                ];
            }
        }

        $created = [];
        $updated = [];
        $errors = [];

        // Processar da linha 2 em diante (pular cabeçalho)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $lineNumber = $i + 1;

            try {
                $matricula = trim($row[$colMap['matricula']] ?? '');
                $nome = trim($row[$colMap['nome']] ?? '');
                $email = trim($row[$colMap['email']] ?? '');
                $turno = trim($row[$colMap['turno']] ?? '');
                $curso = trim($row[$colMap['curso']] ?? '');

                // Validar dados obrigatórios
                if (empty($matricula)) {
                    $errors[] = ['linha' => $lineNumber, 'erro' => 'Matrícula obrigatória'];
                    continue;
                }

                if (empty($nome)) {
                    $errors[] = ['linha' => $lineNumber, 'erro' => 'Nome obrigatório'];
                    continue;
                }

                if (empty($email)) {
                    $errors[] = ['linha' => $lineNumber, 'erro' => 'Email obrigatório'];
                    continue;
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = ['linha' => $lineNumber, 'erro' => 'Email inválido: ' . $email];
                    continue;
                }

                if (empty($turno)) {
                    $errors[] = ['linha' => $lineNumber, 'erro' => 'Turno obrigatório'];
                    continue;
                }

                // Normalizar turno
                $turnoNormalizado = $this->normalizarTurno($turno);
                if (!$turnoNormalizado) {
                    $errors[] = ['linha' => $lineNumber, 'erro' => 'Turno inválido: ' . $turno . '. Use: matutino, vespertino ou noturno'];
                    continue;
                }

                // Verificar se usuário já existe
                $user = User::where('matricula', $matricula)->first();

                if ($user) {
                    // Atualizar usuário existente
                    $user->update([
                        'nome' => $nome,
                        'email' => $email,
                        'turno' => $turnoNormalizado,
                        'curso' => $curso,
                        'bolsista' => true,
                        'perfil' => 'estudante',
                    ]);

                    $updated[] = [
                        'matricula' => $matricula,
                        'nome' => $nome,
                        'action' => 'updated'
                    ];

                    if (config('app.debug')) {
                        Log::info("Bolsista atualizado: {$matricula} - {$nome}");
                    }
                } else {
                    // Criar novo usuário
                    $user = User::create([
                        'matricula' => $matricula,
                        'nome' => $nome,
                        'email' => $email,
                        'password' => Hash::make($matricula), // senha padrão = matrícula
                        'turno' => $turnoNormalizado,
                        'curso' => $curso,
                        'bolsista' => true,
                        'perfil' => 'estudante',
                        'limite_faltas_mes' => 3,
                    ]);

                    $created[] = [
                        'matricula' => $matricula,
                        'nome' => $nome,
                        'action' => 'created'
                    ];

                    if (config('app.debug')) {
                        Log::info("Bolsista criado: {$matricula} - {$nome}");
                    }
                }

            } catch (\Exception $e) {
                $errors[] = [
                    'linha' => $lineNumber,
                    'erro' => $e->getMessage()
                ];

                if (config('app.debug')) {
                    Log::error("Erro ao importar linha {$lineNumber}: " . $e->getMessage());
                }
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * Normaliza o nome do turno
     */
    private function normalizarTurno(string $turno): ?string
    {
        $turno = mb_strtolower(trim($turno));

        $map = [
            'matutino' => 'matutino',
            'manhã' => 'matutino',
            'manha' => 'matutino',
            'vespertino' => 'vespertino',
            'tarde' => 'vespertino',
            'noturno' => 'noturno',
            'noite' => 'noturno',
        ];

        return $map[$turno] ?? null;
    }
}


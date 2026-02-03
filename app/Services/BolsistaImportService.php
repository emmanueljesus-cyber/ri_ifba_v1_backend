<?php

namespace App\Services;

use App\Models\Bolsista;
use App\Models\User;
use App\Models\UsuarioDiaSemana;
use Illuminate\Support\Facades\DB;

class BolsistaImportService
{
    /**
     * Importa bolsistas a partir de dados do Excel/CSV
     * OPÇÃO B: Apenas salva matrículas na tabela `bolsistas`
     * Quando estudante se cadastrar, sistema verifica automaticamente
     *
     * @param array $rows Linhas do arquivo
     * @param string|null $turnoPadrao Turno padrão caso não especificado
     * @param bool $atualizarExistentes Se deve atualizar bolsistas existentes
     * @return array {created: [], updated: [], errors: [], meta: []}
     */
    public function import(array $rows, ?string $turnoPadrao = null, bool $atualizarExistentes = true): array
    {
        $created = [];
        $updated = [];
        $errors = [];
        $processados = 0;

        \Log::info('Iniciando importação de bolsistas', [
            'total_linhas' => count($rows),
            'turno_padrao' => $turnoPadrao,
            'primeira_linha' => $rows[0] ?? null,
            'segunda_linha' => $rows[1] ?? null,
        ]);

        // Detectar formato e obter mapeamento de colunas
        $headers = $this->normalizeHeaders($rows[0] ?? []);
        $dataRows = array_slice($rows, 1);

        \Log::info('Headers normalizados', ['headers' => $headers, 'total_data_rows' => count($dataRows)]);

        foreach ($dataRows as $index => $row) {
            $linha = $index + 2; // +2 porque começa do 0 e pulamos o header

            // Ignorar linhas completamente vazias
            if (empty(array_filter($row))) {
                \Log::debug('Linha vazia ignorada', ['linha' => $linha]);
                continue;
            }

            try {
                $dados = $this->mapRowToData($row, $headers, $turnoPadrao);

                \Log::info('Linha mapeada', ['linha' => $linha, 'dados' => $dados, 'row_raw' => $row]);

                if (empty($dados['matricula'])) {
                    \Log::warning('Matrícula vazia', ['linha' => $linha, 'dados' => $dados]);
                    $errors[] = ['linha' => $linha, 'erro' => 'Matrícula é obrigatória'];
                    continue;
                }

                // Verificar se já existe na tabela bolsistas
                $existente = Bolsista::where('matricula', $dados['matricula'])->first();

                DB::beginTransaction();

                if ($existente) {
                    if ($atualizarExistentes) {
                        $existente->update([
                            'nome' => $dados['nome'] ?? $existente->nome,
                            'curso' => $dados['curso'] ?? $existente->curso,
                            'turno_refeicao' => $dados['turno_refeicao'] ?? $existente->turno_refeicao,
                            'dias_semana' => !empty($dados['dias_semana']) ? $dados['dias_semana'] : $existente->dias_semana,
                            'ativo' => true,
                        ]);

                        $existente->refresh();

                        // Sincronizar com usuário se já existir
                        $user = User::where('matricula', $existente->matricula)->first();
                        if ($user) {
                            $existente->vincularUsuario($user);
                        }

                        $updated[] = [
                            'id' => $existente->id,
                            'matricula' => $existente->matricula,
                            'nome' => $existente->nome,
                            'status' => $existente->user_id ? 'Já vinculado' : 'Atualizado',
                        ];

                        \Log::info('Bolsista atualizado', ['matricula' => $existente->matricula, 'id' => $existente->id]);
                    }
                } else {
                    // Criar novo registro de bolsista aprovado
                    $bolsista = Bolsista::create([
                        'matricula' => $dados['matricula'],
                        'nome' => $dados['nome'] ?? null,
                        'curso' => $dados['curso'] ?? null,
                        'turno_refeicao' => $dados['turno_refeicao'] ?? $turnoPadrao,
                        'dias_semana' => $dados['dias_semana'] ?? [1, 2, 3, 4, 5],
                        'ativo' => true,
                    ]);
                    $bolsista->refresh();

                    // Sincronizar com usuário se já existir
                    $user = User::where('matricula', $bolsista->matricula)->first();
                    if ($user) {
                        $bolsista->vincularUsuario($user);
                    }

                    $created[] = [
                        'id' => $bolsista->id,
                        'matricula' => $bolsista->matricula,
                        'nome' => $bolsista->nome,
                        'status' => 'Aguardando cadastro do estudante',
                    ];

                    \Log::info('Bolsista criado', ['matricula' => $bolsista->matricula, 'id' => $bolsista->id]);
                }

                DB::commit();
                $processados++;
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Erro ao processar linha', [
                    'linha' => $linha,
                    'erro' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'row' => $row,
                ]);
                $errors[] = [
                    'linha' => $linha,
                    'erro' => $e->getMessage(),
                ];
            }
        }

        \Log::info('Importação finalizada', [
            'total_criados' => count($created),
            'total_atualizados' => count($updated),
            'total_erros' => count($errors),
            'processados' => $processados,
        ]);

        return [
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
            'meta' => [
                'total_linhas' => count($dataRows),
                'total_processados' => $processados,
                'total_criados' => count($created),
                'total_atualizados' => count($updated),
                'total_erros' => count($errors),
                'mensagem' => 'Importação concluída. Dados sincronizados com usuários cadastrados.',
            ],
        ];
    }

    /**
     * Normaliza headers para lowercase sem acentos
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $index => $header) {
            $key = $this->normalizeString((string) $header);
            $normalized[$key] = $index;
        }
        return $normalized;
    }

    /**
     * Normaliza string removendo acentos e convertendo para lowercase
     */
    private function normalizeString(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[áàãâä]/u', 'a', $value);
        $value = preg_replace('/[éèêë]/u', 'e', $value);
        $value = preg_replace('/[íìîï]/u', 'i', $value);
        $value = preg_replace('/[óòõôö]/u', 'o', $value);
        $value = preg_replace('/[úùûü]/u', 'u', $value);
        $value = preg_replace('/[ç]/u', 'c', $value);
        $value = preg_replace('/[^a-z0-9_]/', '_', $value);
        // Remover underscores duplicados e nas bordas
        $value = preg_replace('/_+/', '_', $value);
        $value = trim($value, '_');
        return $value;
    }

    /**
     * Mapeia uma linha para dados estruturados
     */
    private function mapRowToData(array $row, array $headers, ?string $turnoPadrao): array
    {
        $getValue = function ($keys) use ($row, $headers) {
            foreach ((array) $keys as $key) {
                if (isset($headers[$key]) && isset($row[$headers[$key]])) {
                    $value = trim((string) $row[$headers[$key]]);
                    if ($value !== '') {
                        return $value;
                    }
                }
            }
            return null;
        };

        // Processar dias da semana
        $diasSemanaRaw = $getValue(['dias_semana', 'dias', 'dia_semana', 'dias_da_semana']);
        $diasSemana = $this->parseDiasSemana($diasSemanaRaw);

        // Processar turno
        $turnoRaw = $getValue(['turno', 'turno_refeicao', 'turno_almoco_jantar', 'periodo', 'shift']);
        $turnoRefeicao = $this->parseTurno($turnoRaw) ?? $turnoPadrao;

        return [
            'matricula' => $getValue(['matricula', 'mat', 'registro', 'ra', 'matricula_']),
            'nome' => $getValue(['nome', 'name', 'aluno', 'estudante', 'nome_completo']),
            'email' => $getValue(['email', 'e_mail', 'correio']),
            'curso' => $getValue(['curso', 'turma', 'classe']),
            'turno_refeicao' => $turnoRefeicao,
            'dias_semana' => $diasSemana,
        ];
    }

    /**
     * Converte string de turno para valor do enum
     */
    private function parseTurno(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = $this->normalizeString($value);

        if (str_contains($value, 'almoco') || str_contains($value, 'manha') || str_contains($value, 'integral')) {
            return 'almoco';
        }

        if (str_contains($value, 'jantar') || str_contains($value, 'noite') || str_contains($value, 'noturno')) {
            return 'jantar';
        }

        return null;
    }

    /**
     * Converte string de dias para array de inteiros
     * Aceita: "1,2,3,4,5" ou "segunda,terça,quarta"
     */
    private function parseDiasSemana(?string $value): array
    {
        if (empty($value)) {
            // Padrão: segunda a sexta
            return [1, 2, 3, 4, 5];
        }

        $diasNome = [
            'domingo' => 0, 'dom' => 0, 'sunday' => 0,
            'segunda' => 1, 'seg' => 1, 'segunda-feira' => 1, 'monday' => 1,
            'terca' => 2, 'ter' => 2, 'terça' => 2, 'terça-feira' => 2, 'tuesday' => 2,
            'quarta' => 3, 'qua' => 3, 'quarta-feira' => 3, 'wednesday' => 3,
            'quinta' => 4, 'qui' => 4, 'quinta-feira' => 4, 'thursday' => 4,
            'sexta' => 5, 'sex' => 5, 'sexta-feira' => 5, 'friday' => 5,
            'sabado' => 6, 'sab' => 6, 'sábado' => 6, 'saturday' => 6,
        ];

        $dias = [];
        $partes = preg_split('/[,;\s]+/', strtolower(trim($value)));

        foreach ($partes as $parte) {
            $parte = trim($parte);
            if (is_numeric($parte) && $parte >= 0 && $parte <= 6) {
                $dias[] = (int) $parte;
            } elseif (isset($diasNome[$parte])) {
                $dias[] = $diasNome[$parte];
            }
        }

        return array_unique($dias);
    }

    /**
     * Atualiza os dias da semana do usuário
     */
    private function atualizarDiasSemana(int $userId, array $dias): void
    {
        // Remove dias atuais
        UsuarioDiaSemana::where('user_id', $userId)->delete();

        // Adiciona novos dias
        foreach ($dias as $dia) {
            UsuarioDiaSemana::create([
                'user_id' => $userId,
                'dia_semana' => $dia,
            ]);
        }
    }

    /**
     * Gera email padrão baseado na matrícula
     */
    private function gerarEmail(string $matricula): string
    {
        return $matricula . '@aluno.ifba.edu.br';
    }
}

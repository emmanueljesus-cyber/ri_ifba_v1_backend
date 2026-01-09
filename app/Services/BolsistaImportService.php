<?php

namespace App\Services;

use App\Models\Bolsista;
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

        // Detectar formato e obter mapeamento de colunas
        $headers = $this->normalizeHeaders($rows[0] ?? []);
        $dataRows = array_slice($rows, 1);

        foreach ($dataRows as $index => $row) {
            $linha = $index + 2; // +2 porque começa do 0 e pulamos o header

            try {
                $dados = $this->mapRowToData($row, $headers, $turnoPadrao);

                if (empty($dados['matricula'])) {
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
                            'turno' => $dados['turno'] ?? $existente->turno,
                            'dias_semana' => !empty($dados['dias_semana']) ? $dados['dias_semana'] : $existente->dias_semana,
                            'ativo' => true,
                        ]);

                        $updated[] = [
                            'matricula' => $existente->matricula,
                            'nome' => $existente->nome,
                            'status' => $existente->user_id ? 'Já vinculado' : 'Atualizado',
                        ];
                    }
                } else {
                    // Criar novo registro de bolsista aprovado
                    $bolsista = Bolsista::create([
                        'matricula' => $dados['matricula'],
                        'nome' => $dados['nome'] ?? null,
                        'curso' => $dados['curso'] ?? null,
                        'turno' => $dados['turno'] ?? $turnoPadrao,
                        'dias_semana' => $dados['dias_semana'] ?? [1, 2, 3, 4, 5],
                        'ativo' => true,
                    ]);

                    $created[] = [
                        'matricula' => $bolsista->matricula,
                        'nome' => $bolsista->nome,
                        'status' => 'Aguardando cadastro do estudante',
                    ];
                }

                DB::commit();
                $processados++;
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = [
                    'linha' => $linha,
                    'erro' => $e->getMessage(),
                ];
            }
        }

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
                'mensagem' => 'Matrículas salvas. Estudantes serão marcados como bolsistas ao se cadastrarem.',
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

        return [
            'matricula' => $getValue(['matricula', 'mat', 'registro', 'ra']),
            'nome' => $getValue(['nome', 'name', 'aluno', 'estudante', 'nome_completo']),
            'email' => $getValue(['email', 'e_mail', 'correio']),
            'curso' => $getValue(['curso', 'turma', 'classe']),
            'turno' => $getValue(['turno', 'periodo', 'shift']) ?? $turnoPadrao,
            'dias_semana' => $diasSemana,
        ];
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

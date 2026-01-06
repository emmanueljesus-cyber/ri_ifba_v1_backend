<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ImportCardapio extends Command
{
    protected $signature = 'cardapio:import {file} {--user=1} {--sheet=0} {--debug}';
    protected $description = 'Importa cardápios de um arquivo Excel (detecta formato transposto automaticamente).';

    public function handle()
    {
        $file = $this->argument('file');
        $userId = (int) $this->option('user');
        $sheetIndex = (int) $this->option('sheet');

        if (!file_exists($file)) {
            $this->error("Arquivo não encontrado: {$file}");
            return 1;
        }

        try {
            $sheets = Excel::toArray(new \stdClass(), $file);
        } catch (\Throwable $e) {
            $this->error('Erro ao ler o arquivo: ' . $e->getMessage());
            return 1;
        }

        $sheet = $sheets[$sheetIndex] ?? $sheets[0] ?? [];
        if (empty($sheet)) {
            $this->error('Planilha vazia');
            return 1;
        }

        $this->info('Lendo planilha...');

        // normalizar string: remove acentos, converte para lowercase, substitui espaços/símbolos por underscore
        $normalize = function ($s) {
            $s = (string) ($s ?? '');
            $s = trim(mb_strtolower($s));
            $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
            $s = preg_replace('/[^a-z0-9]+/i', '_', $s);
            return trim($s, '_');
        };

        // transpor matriz: linhas <-> colunas
        $transpose = function (array $rows) {
            $out = [];
            $maxCols = max(array_map('count', $rows));
            for ($c = 0; $c < $maxCols; $c++) {
                $newRow = [];
                for ($r = 0; $r < count($rows); $r++) {
                    $newRow[] = $rows[$r][$c] ?? null;
                }
                $out[] = $newRow;
            }
            return $out;
        };

        // converter número Excel para data (Excel serial date)
        $excelToDate = function ($raw) {
            // números < 100 ou strings são ignorados; tenta parse direto
            if (is_string($raw)) {
                try {
                    return Carbon::parse($raw)->toDateString();
                } catch (\Throwable $e) {
                    return null;
                }
            }
            $num = (int) ($raw ?? 0);
            if ($num === 0 || $num < 30000) {
                return null; // números inválidos
            }
            // Excel: dia 0 = 1900-01-00, dia 1 = 1900-01-01; offset é 1900-01-01
            // mas PHP/Carbon usa timestamps, então: 25569 = 1970-01-01
            try {
                $date = Carbon::createFromDate(1900, 1, 1)->addDays($num - 1);
                return $date->toDateString();
            } catch (\Throwable $e) {
                return null;
            }
        };

        // normalizar turno: mapeia valores antigos para os do enum
        $normalizeTurno = function ($raw) {
            $v = (string) ($raw ?? '');
            $v = trim(mb_strtolower($v));
            $v = iconv('UTF-8', 'ASCII//TRANSLIT', $v);
            $v = preg_replace('/[^a-z0-9]+/i', '', $v);

            $map = [
                'almoco' => 'almoco',
                'manha'  => 'almoco',
                'tarde'  => 'almoco',
                'jantar' => 'jantar',
                'noite'  => 'jantar',
            ];

            if (isset($map[$v])) {
                return $map[$v];
            }

            // fallback: retorna null se não reconhecer
            return null;
        };

        // detectar formato: verifica primeira célula e primeira coluna por rótulos
        $firstCell = mb_strtolower(trim($sheet[0][0] ?? ''));
        $firstColumn = array_column($sheet, 0);
        $found = false;
        foreach ($firstColumn as $cell) {
            $cellStr = mb_strtolower(trim((string) ($cell ?? '')));
            if ($cellStr === '') {
                continue;
            }
            if (str_contains($cellStr, 'prato') || str_contains($cellStr, 'guarnicao') || str_contains($cellStr, 'acompanhamento') || str_contains($cellStr, 'salada') || str_contains($cellStr, 'suco') || str_contains($cellStr, 'sobremesa')) {
                $found = true;
                break;
            }
        }
        $transposed = $found || str_contains($firstCell, 'prato') || str_contains($firstCell, 'guarnicao') || str_contains($firstCell, 'acompanhamento');

        if ($this->option('debug')) {
            $this->info('DEBUG: Primeiras 6 linhas da planilha:');
            for ($i = 0; $i < min(6, count($sheet)); $i++) {
                $this->line(json_encode($sheet[$i], JSON_UNESCAPED_UNICODE));
            }
            $this->info("Formato detectado: " . ($transposed ? 'TRANSPOSTO' : 'LINHA-POR-LINHA'));
            $this->info("Primeira célula: '{$firstCell}'");
            $this->info('DEBUG: Primeira coluna:');
            $this->line(json_encode($firstColumn, JSON_UNESCAPED_UNICODE));
        }

        $service = app(\App\Services\CardapioService::class);
        $created = [];
        $errors = [];

        if ($transposed) {
            $this->info('Formato detectado: transposto (colunas = dias).');
            $cols = $transpose($sheet);
            // a primeira coluna transposta representa os rótulos (PRATO..., GUARNIÇÃO, ...)
            $rawLabels = $cols[0] ?? [];
            $labels = array_map($normalize, $rawLabels);

            // processar cada coluna (1..n-1) como um dia/cardápio
            for ($c = 1; $c < count($cols); $c++) {
                $col = $cols[$c];
                $dateRaw = $col[0] ?? null;
                $dataDoCardapio = null;

                // extrair data do cabeçalho (primeira célula da coluna)
                if ($dateRaw) {
                    // primeiro tenta converter se for número Excel
                    $dataDoCardapio = $excelToDate($dateRaw);
                    // se falhou, tenta extrair padrão dd/mm/yy ou dd/mm/yyyy
                    if (!$dataDoCardapio && preg_match('/(\d{1,2}\/\d{1,2}\/\d{2,4})/', $dateRaw, $m)) {
                        try {
                            $dataDoCardapio = Carbon::createFromFormat('d/m/y', $m[1])->toDateString();
                        } catch (\Throwable $e1) {
                            try {
                                $dataDoCardapio = Carbon::createFromFormat('d/m/Y', $m[1])->toDateString();
                            } catch (\Throwable $e2) {
                                // nada a fazer
                            }
                        }
                    }
                    // último recurso: parse direto (ex: "SEGUNDA 15/12/25")
                    if (!$dataDoCardapio) {
                        try {
                            $dataDoCardapio = Carbon::parse($dateRaw)->toDateString();
                        } catch (\Throwable $e) {
                            $dataDoCardapio = null;
                        }
                    }
                }

                // montar associativo: rótulo => valor
                $assoc = [];
                foreach ($labels as $i => $label) {
                    if ($label === '') {
                        continue;
                    }
                    $assoc[$label] = $col[$i] ?? null;
                }

                $turnoRaw = $assoc['turno'] ?? null;
                $turnoNorm = $normalizeTurno($turnoRaw);
                $turno = $turnoNorm ?? 'almoco';

                // montar payload para CardapioService
                $payload = [
                    'data_do_cardapio' => $dataDoCardapio,
                    'turno' => $turno,
                    'prato_principal_ptn01' => $assoc['prato_principal_ptn_01'] ?? $assoc['prato_principal_ptn01'] ?? $assoc['prato_principal_ptn_1'] ?? null,
                    'prato_principal_ptn02' => $assoc['prato_principal_ptn_02'] ?? $assoc['prato_principal_ptn02'] ?? $assoc['prato_principal_ptn_2'] ?? null,
                    'guarnicao' => $assoc['guarnicao'] ?? null,
                    'acompanhamento_01' => $assoc['acompanhamento_01'] ?? null,
                    'acompanhamento_02' => $assoc['acompanhamento_02'] ?? null,
                    'salada' => $assoc['salada'] ?? null,
                    'ovo_lacto_vegetariano' => $assoc['ovolactovegetariano'] ?? $assoc['ovo_lacto_vegetariano'] ?? null,
                    'suco' => $assoc['suco'] ?? null,
                    'sobremesa' => $assoc['sobremesa'] ?? null,
                    'capacidade' => $assoc['capacidade'] ?? null,
                ];

                try {
                    if ($this->option('debug')) {
                        $this->info('DEBUG PAYLOAD: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));
                    }
                    $card = $service->create($payload, $userId);
                    $created[] = $card->id;
                    $this->info("✓ Criado cardápio: {$card->id} - {$payload['data_do_cardapio']} ({$turno})");
                } catch (\Throwable $e) {
                    $errors[] = ['col' => $c, 'date' => $dataDoCardapio, 'message' => $e->getMessage()];
                    $this->error("✗ Erro coluna {$c} ({$dateRaw}): " . $e->getMessage());
                    report($e);
                }
            }
        } else {
            // formato linha-por-linha: primeira linha = cabeçalho
            $this->info('Formato detectado: linhas = cardápios (header na primeira linha).');
            $header = array_map($normalize, $sheet[0]);

            for ($r = 1; $r < count($sheet); $r++) {
                $row = $sheet[$r];
                if (empty(array_filter($row))) {
                    continue;
                }

                $assoc = array_combine($header, $row);

                $turnoRaw = $assoc['turno'] ?? null;
                $turnoNorm = $normalizeTurno($turnoRaw);
                $turno = $turnoNorm ?? 'almoco';

                $payload = [
                    'data_do_cardapio' => isset($assoc['data_do_cardapio']) ? Carbon::parse($assoc['data_do_cardapio'])->toDateString() : null,
                    'turno' => $turno,
                    'prato_principal_ptn01' => $assoc['prato_principal_ptn01'] ?? null,
                    'prato_principal_ptn02' => $assoc['prato_principal_ptn02'] ?? null,
                    'guarnicao' => $assoc['guarnicao'] ?? null,
                    'acompanhamento_01' => $assoc['acompanhamento_01'] ?? null,
                    'acompanhamento_02' => $assoc['acompanhamento_02'] ?? null,
                    'salada' => $assoc['salada'] ?? null,
                    'ovo_lacto_vegetariano' => $assoc['ovo_lacto_vegetariano'] ?? null,
                    'suco' => $assoc['suco'] ?? null,
                    'sobremesa' => $assoc['sobremesa'] ?? null,
                    'capacidade' => $assoc['capacidade'] ?? null,
                ];

                try {
                    $card = $service->create($payload, $userId);
                    $created[] = $card->id;
                    $this->info("✓ Criado cardápio: {$card->id} - {$payload['data_do_cardapio']} ({$turno})");
                } catch (\Throwable $e) {
                    $errors[] = ['row' => $r + 1, 'message' => $e->getMessage()];
                    $this->error("✗ Erro linha {$r}: " . $e->getMessage());
                    report($e);
                }
            }
        }

        $this->newLine();
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Import concluído.");
        $this->info("  Criados: " . count($created));
        $this->info("  Erros: " . count($errors));
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        if (!empty($errors)) {
            $this->newLine();
            $this->warn('Erros detalhados:');
            foreach ($errors as $err) {
                $this->line(json_encode($err, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        return 0;
    }
}

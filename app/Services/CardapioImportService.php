<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CardapioImportService
{
    public function __construct(private CardapioService $service)
    {
    }

    /**
     * Processa linhas do Excel e retorna arrays de criados e erros.
     *
     * @param array $rows    Linhas da planilha (primeira sheet)
     * @param array $turnos  Lista de turnos (ex.: ['almoco','jantar'])
     * @param int|null $userId Usuário que realizou a importação
     * @param bool $debug    Se true, retorna dados de debug em meta
     */
    public function import(array $rows, array $turnos, ?int $userId, bool $debug = false): array
    {
        Log::info('=== INICIANDO IMPORTAÇÃO DE CARDÁPIOS ===', [
            'total_linhas' => count($rows),
            'turnos' => $turnos,
            'userId' => $userId,
            'primeira_linha' => $rows[0] ?? null,
            'segunda_linha' => $rows[1] ?? null,
        ]);

        if (empty($rows)) {
            Log::warning('Arquivo vazio recebido');
            return ['created' => [], 'errors' => [['erro' => 'Arquivo vazio']], 'debug' => null];
        }

        $primeiraLinha = array_map(fn($h) => mb_strtolower(trim($h ?? '')), $rows[0]);
        $primeiraCelula = $primeiraLinha[0] ?? '';

        $segundaCelulaPrimeiraColuna = $rows[1][0] ?? null;
        $datasNaPrimeiraColuna = $this->parseDate($segundaCelulaPrimeiraColuna) !== null;

        $segundaCelulaPrimeiraLinha = $rows[0][1] ?? null;
        $datasNaPrimeiraLinha = $this->parseDate($segundaCelulaPrimeiraLinha) !== null;

        Log::info('Formato detectado na importação de cardápio', [
            'primeiraCelula' => $primeiraCelula,
            'segundaCelulaPrimeiraColuna' => $segundaCelulaPrimeiraColuna,
            'datasNaPrimeiraColuna' => $datasNaPrimeiraColuna,
            'segundaCelulaPrimeiraLinha' => $segundaCelulaPrimeiraLinha,
            'datasNaPrimeiraLinha' => $datasNaPrimeiraLinha,
        ]);

        if ($datasNaPrimeiraColuna) {
            Log::info('Usando método COLUNAR');
            $result = $this->importColunar($rows, $turnos, $userId);
        } elseif ($datasNaPrimeiraLinha || (empty($primeiraCelula) || !str_contains($primeiraCelula, 'data'))) {
            Log::info('Usando método TRANSPOSTO');
            $result = $this->importTransposto($rows, $turnos, $userId);
        } else {
            Log::info('Usando método NORMAL');
            $result = $this->importNormal($rows, $turnos, $userId);
        }

        Log::info('=== IMPORTAÇÃO FINALIZADA ===', [
            'total_criados' => count($result['created']),
            'total_erros' => count($result['errors']),
            'erros' => $result['errors'],
        ]);

        if ($debug) {
            $result['debug'] = [
                'primeira_linha' => $rows[0] ?? [],
                'segunda_linha' => $rows[1] ?? [],
                'terceira_linha' => $rows[2] ?? [],
                'total_linhas' => count($rows),
                'total_colunas' => count($rows[0] ?? []),
                'teste_parseDate_rows_1_0' => [
                    'valor_original' => $rows[1][0] ?? null,
                    'tipo' => gettype($rows[1][0] ?? null),
                    'resultado' => $this->parseDate($rows[1][0] ?? null),
                ],
                'teste_parseDate_rows_0_1' => [
                    'valor_original' => $rows[0][1] ?? null,
                    'tipo' => gettype($rows[0][1] ?? null),
                    'resultado' => $this->parseDate($rows[0][1] ?? null),
                ],
            ];
        } else {
            $result['debug'] = null;
        }

        return $result;
    }

    private function importTransposto(array $rows, array $turnos, ?int $userId): array
    {
        $created = [];
        $errors = [];
        $fieldMap = $this->getFieldMap();

        Log::info('ImportTransposto: Buscando datas na primeira linha', [
            'primeira_linha' => $rows[0] ?? [],
        ]);

        $datas = [];
        for ($col = 1; $col < count($rows[0]); $col++) {
            $dataValue = $rows[0][$col] ?? null;
            if (!empty($dataValue)) {
                $parsedDate = $this->parseDate($dataValue);
                Log::debug("Coluna {$col}: valor={$dataValue}, parsed={$parsedDate}");
                if ($parsedDate) {
                    $datas[$col] = $parsedDate;
                }
            }
        }

        Log::info('ImportTransposto: Datas encontradas', ['datas' => $datas]);

        if (empty($datas)) {
            $errors[] = ['linha' => 1, 'erro' => 'Nenhuma data válida encontrada na primeira linha'];
            Log::warning('Nenhuma data válida encontrada');
            return ['created' => $created, 'errors' => $errors];
        }

        $cardapiosPorData = [];
        foreach ($datas as $col => $data) {
            $cardapiosPorData[$data] = [];
        }

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $fieldName = mb_strtolower(trim($row[0] ?? ''));
            $fieldKey = $fieldMap[$fieldName] ?? null;
            if (!$fieldKey) {
                // Tentar ver se contém alguma das chaves
                foreach ($fieldMap as $key => $target) {
                    if (str_contains($fieldName, $key)) {
                        $fieldKey = $target;
                        break;
                    }
                }
            }

            if (!$fieldKey) {
                continue;
            }

            foreach ($datas as $col => $data) {
                $value = $row[$col] ?? null;
                if (!empty($value)) {
                    $cardapiosPorData[$data][$fieldKey] = preg_replace('/\s+/', ' ', trim($value));
                }
            }
        }

        foreach ($cardapiosPorData as $data => $campos) {
            if (empty($campos['prato_principal_ptn01']) && empty($campos['prato_principal_ptn02'])) {
                $errors[] = ['data' => $data, 'erro' => 'Nenhum prato principal informado'];
                continue;
            }
            foreach ($turnos as $turno) {
                $cardapioData = array_merge($campos, [
                    'data_do_cardapio' => $data,
                    'turno' => $turno,
                ]);
                try {
                    $result = $this->service->createOrUpdate($cardapioData, $userId);
                    $cardapio = $result['cardapio'];
                    $created[] = [
                        'id' => $cardapio->id,
                        'data' => $data,
                        'turno' => $turno,
                        'action' => $result['created'] ? 'created' : 'updated',
                    ];
                    \Log::info('Cardápio importado (transposto)', [
                        'id' => $cardapio->id,
                        'data' => $data,
                        'turno' => $turno,
                        'action' => $result['created'] ? 'created' : 'updated',
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('Erro ao importar cardápio (transposto)', [
                        'data' => $data,
                        'turno' => $turno,
                        'erro' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $errors[] = ['data' => $data, 'turno' => $turno, 'erro' => $e->getMessage()];
                }
            }
        }

        return ['created' => $created, 'errors' => $errors];
    }

    private function importColunar(array $rows, array $turnos, ?int $userId): array
    {
        $created = [];
        $errors = [];
        $fieldMap = $this->getFieldMap();

        $header = [];
        for ($col = 0; $col < count($rows[0]); $col++) {
            $headerValue = mb_strtolower(trim($rows[0][$col] ?? ''));
            $header[$col] = $col === 0 ? 'data' : ($fieldMap[$headerValue] ?? null);
        }

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty(array_filter($row))) {
                continue;
            }

            $dataValue = $row[0] ?? null;
            $parsedDate = $this->parseDate($dataValue);
            if (!$parsedDate) {
                $errors[] = ['linha' => $i + 1, 'erro' => 'Data inválida: ' . ($dataValue ?? 'vazio')];
                continue;
            }

            $segundoCampo = mb_strtoupper(trim($row[1] ?? ''));
            if (str_contains($segundoCampo, 'PRATO PRINCIPAL') || str_contains($segundoCampo, 'PTN 0') || str_contains($segundoCampo, 'PTN 1')) {
                continue;
            }

            $campos = [];
            for ($col = 1; $col < count($row); $col++) {
                $fieldKey = $header[$col] ?? null;
                $value = $row[$col] ?? null;
                if ($fieldKey && !empty($value)) {
                    $campos[$fieldKey] = preg_replace('/\s+/', ' ', trim($value));
                }
            }

            if (empty($campos['prato_principal_ptn01']) && empty($campos['prato_principal_ptn02'])) {
                $errors[] = ['linha' => $i + 1, 'data' => $parsedDate, 'erro' => 'Nenhum prato principal informado'];
                continue;
            }

            foreach ($turnos as $turno) {
                $cardapioData = array_merge($campos, [
                    'data_do_cardapio' => $parsedDate,
                    'turno' => $turno,
                ]);
                try {
                    $result = $this->service->createOrUpdate($cardapioData, $userId);
                    $cardapio = $result['cardapio'];
                    $created[] = [
                        'id' => $cardapio->id,
                        'data' => $parsedDate,
                        'turno' => $turno,
                        'action' => $result['created'] ? 'created' : 'updated',
                    ];
                    \Log::info('Cardápio importado (colunar)', [
                        'id' => $cardapio->id,
                        'data' => $parsedDate,
                        'turno' => $turno,
                        'action' => $result['created'] ? 'created' : 'updated',
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('Erro ao importar cardápio (colunar)', [
                        'linha' => $i + 1,
                        'data' => $parsedDate,
                        'turno' => $turno,
                        'erro' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $errors[] = ['linha' => $i + 1, 'data' => $parsedDate, 'turno' => $turno, 'erro' => $e->getMessage()];
                }
            }
        }

        return ['created' => $created, 'errors' => $errors];
    }

    private function importNormal(array $rows, array $turnos, ?int $userId): array
    {
        $created = [];
        $errors = [];
        $header = array_map(fn($h) => mb_strtolower(trim($h ?? '')), $rows[0]);

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty(array_filter($row))) {
                continue;
            }
            while (count($row) < count($header)) {
                $row[] = null;
            }
            $assoc = array_combine($header, $row);

            $dataCardapio = null;
            foreach (['data_do_cardapio', 'data'] as $key) {
                if (isset($assoc[$key]) && !empty($assoc[$key])) {
                    $dataCardapio = $this->parseDate($assoc[$key]);
                    if ($dataCardapio) break;
                }
            }

            if (!$dataCardapio) {
                $errors[] = ['linha' => $i + 1, 'erro' => 'Data inválida ou não informada'];
                continue;
            }

            foreach ($turnos as $turno) {
                $data = [
                    'data_do_cardapio' => $dataCardapio,
                    'turno' => $turno,
                    'prato_principal_ptn01' => $assoc['prato_principal_ptn01'] ?? $assoc['prato principal ptn 01'] ?? null,
                    'prato_principal_ptn02' => $assoc['prato_principal_ptn02'] ?? $assoc['prato principal ptn 02'] ?? null,
                    'guarnicao' => $assoc['guarnicao'] ?? $assoc['guarnição'] ?? null,
                    'acompanhamento_01' => $assoc['acompanhamento_01'] ?? $assoc['acompanhamento 01'] ?? null,
                    'acompanhamento_02' => $assoc['acompanhamento_02'] ?? $assoc['acompanhamento 02'] ?? null,
                    'salada' => $assoc['salada'] ?? null,
                    'ovo_lacto_vegetariano' => $assoc['ovo_lacto_vegetariano'] ?? $assoc['ovolactovegetariano'] ?? $assoc['ovo lacto vegetariano'] ?? null,
                    'suco' => $assoc['suco'] ?? null,
                    'sobremesa' => $assoc['sobremesa'] ?? null,
                    'capacidade' => $assoc['capacidade'] ?? null,
                ];
                try {
                    $result = $this->service->createOrUpdate($data, $userId);
                    $cardapio = $result['cardapio'];
                    $created[] = [
                        'id' => $cardapio->id,
                        'data' => $cardapio->data_do_cardapio,
                        'turno' => $turno,
                        'action' => $result['created'] ? 'created' : 'updated',
                        'linha' => $i + 1,
                    ];
                    \Log::info('Cardápio importado (normal)', [
                        'id' => $cardapio->id,
                        'data' => $cardapio->data_do_cardapio,
                        'turno' => $turno,
                        'action' => $result['created'] ? 'created' : 'updated',
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('Erro ao importar cardápio (normal)', [
                        'linha' => $i + 1,
                        'turno' => $turno,
                        'erro' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $errors[] = ['linha' => $i + 1, 'turno' => $turno, 'erro' => $e->getMessage()];
                }
            }
        }

        return ['created' => $created, 'errors' => $errors];
    }

    private function getFieldMap(): array
    {
        return [
            'prato principal ptn 01' => 'prato_principal_ptn01',
            'prato_principal_ptn01' => 'prato_principal_ptn01',
            'prato principal ptn 02' => 'prato_principal_ptn02',
            'prato_principal_ptn02' => 'prato_principal_ptn02',
            'guarnicao' => 'guarnicao',
            'guarnição' => 'guarnicao',
            'acompanhamento 01' => 'acompanhamento_01',
            'acompanhamento_01' => 'acompanhamento_01',
            'acompanhamento 02' => 'acompanhamento_02',
            'acompanhamento_02' => 'acompanhamento_02',
            'salada' => 'salada',
            'ovolactovegetariano' => 'ovo_lacto_vegetariano',
            'ovo_lacto_vegetariano' => 'ovo_lacto_vegetariano',
            'ovo lacto vegetariano' => 'ovo_lacto_vegetariano',
            'suco' => 'suco',
            'sobremesa' => 'sobremesa',
            'capacidade' => 'capacidade',
        ];
    }

    private function parseDate($value): ?string
    {
        if (empty($value) && $value !== 0) {
            return null;
        }
        if (is_numeric($value) && $value > 0) {
            try {
                $unixTimestamp = ($value - 25569) * 86400;
                $date = Carbon::createFromTimestamp($unixTimestamp);
                return $date->toDateString();
            } catch (\Exception $e) {
            }
        }
        $value = trim((string) $value);
        if (empty($value)) {
            return null;
        }
        $formats = [
            'd/m/y', 'j/n/y', 'd/m/Y', 'j/n/Y', 'Y-m-d', 'd-m-Y', 'd-m-y', 'j/m/y', 'd/n/y', 'j-n-y', 'd.m.y', 'd.m.Y'
        ];
        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date) {
                    return $date->toDateString();
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }
}

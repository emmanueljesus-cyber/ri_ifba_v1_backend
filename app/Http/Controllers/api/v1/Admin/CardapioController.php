<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CardapioStoreRequest;
use App\Http\Requests\Admin\CardapioUpdateRequest;
use App\Http\Resources\CardapioResource;
use App\Models\Cardapio;
use App\Services\CardapioService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class CardapioController extends Controller
{
    public function __construct(private CardapioService $service)
    {
        // proteja com sanctum + ensure.is.admin (ou policy/gate)
        //$this->middleware(['auth:sanctum','ensure.is.admin']);
    }

    public function index(Request $request)
    {
        $data = $this->service->paginate($request->only('data'), $request->integer('per_page', 15));
        return CardapioResource::collection($data);
    }

    public function store(CardapioStoreRequest $request)
    {
        $userId = $request->user()?->id ?? null;
        $cardapio = $this->service->create($request->validated(), $userId);
        return (new CardapioResource($cardapio))->response()->setStatusCode(201);
    }

    public function show(Cardapio $cardapio)
    {
        return new CardapioResource($cardapio);
    }

    public function update(CardapioUpdateRequest $request, Cardapio $cardapio)
    {
        $cardapio = $this->service->update($cardapio, $request->validated());
        return new CardapioResource($cardapio);
    }

    public function destroy(Cardapio $cardapio)
    {
        $this->service->delete($cardapio);
        return response()->noContent();
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'turno' => 'nullable|array',
            'turno.*' => [\Illuminate\Validation\Rule::enum(\App\Enums\TurnoRefeicao::class)]
        ]);

        $file = $request->file('file');
        $turnos = $request->input('turno', ['almoco']); // Default: apenas almoço

        $sheets = Excel::toArray(null, $file);

        if (empty($sheets) || empty($sheets[0])) {
            return response()->json(['message' => 'Arquivo vazio'], 422);
        }

        $rows = $sheets[0];

        // DEBUG: Retornar as primeiras linhas para ver o formato
        if ($request->has('debug')) {
            $testDate1 = $this->parseDate($rows[1][0] ?? null);
            $testDate2 = $this->parseDate($rows[0][1] ?? null);

            return response()->json([
                'primeira_linha' => $rows[0] ?? [],
                'segunda_linha' => $rows[1] ?? [],
                'terceira_linha' => $rows[2] ?? [],
                'total_linhas' => count($rows),
                'total_colunas' => count($rows[0] ?? []),
                'teste_parseDate_rows_1_0' => [
                    'valor_original' => $rows[1][0] ?? null,
                    'tipo' => gettype($rows[1][0] ?? null),
                    'resultado' => $testDate1,
                ],
                'teste_parseDate_rows_0_1' => [
                    'valor_original' => $rows[0][1] ?? null,
                    'tipo' => gettype($rows[0][1] ?? null),
                    'resultado' => $testDate2,
                ],
            ]);
        }

        // Detectar formato do Excel automaticamente
        $primeiraLinha = array_map(fn($h) => mb_strtolower(trim($h ?? '')), $rows[0]);
        $primeiraCelula = $primeiraLinha[0] ?? '';

        // Verificar se a primeira coluna (a partir da linha 2) contém datas
        // Isso indica FORMATO COLUNAR: datas na coluna A, campos na linha 1
        $segundaCelulaPrimeiraColuna = $rows[1][0] ?? null;
        $datasNaPrimeiraColuna = $this->parseDate($segundaCelulaPrimeiraColuna) !== null;

        // Verificar se a primeira linha (a partir da coluna 2) contém datas
        // Isso indica FORMATO TRANSPOSTO: campos na coluna A, datas na linha 1
        $segundaCelulaPrimeiraLinha = $rows[0][1] ?? null;
        $datasNaPrimeiraLinha = $this->parseDate($segundaCelulaPrimeiraLinha) !== null;

        // Log do formato detectado
        Log::info('Formato detectado', [
            'primeiraCelula' => $primeiraCelula,
            'segundaCelulaPrimeiraColuna' => $segundaCelulaPrimeiraColuna,
            'datasNaPrimeiraColuna' => $datasNaPrimeiraColuna,
            'segundaCelulaPrimeiraLinha' => $segundaCelulaPrimeiraLinha,
            'datasNaPrimeiraLinha' => $datasNaPrimeiraLinha,
        ]);

        if ($datasNaPrimeiraColuna) {
            // FORMATO COLUNAR: datas na coluna A, campos na linha 1
            $result = $this->importColunar($rows, $turnos, $request->user()?->id);
        } elseif ($datasNaPrimeiraLinha || (empty($primeiraCelula) || !str_contains($primeiraCelula, 'data'))) {
            // FORMATO TRANSPOSTO: campos na coluna A, datas na linha 1
            $result = $this->importTransposto($rows, $turnos, $request->user()?->id);
        } else {
            // FORMATO NORMAL: datas nas linhas
            $result = $this->importNormal($rows, $turnos, $request->user()?->id);
        }

        return response()->json([
            'message' => count($result['created']) . ' cardápio(s) importado(s) com sucesso',
            'created' => $result['created'],
            'errors' => $result['errors']
        ], 201);
    }

    /**
     * Import formato TRANSPOSTO (campos na coluna A, datas na linha 1)
     * Formato do Excel:
     * |                      | 15/12/25 | 16/12/25 | 17/12/25 |
     * | PRATO PRINCIPAL 01   | Fricassé | exemplo  | exemplo  |
     * | PRATO PRINCIPAL 02   | Lombo    | exemplo  | exemplo  |
     */
    private function importTransposto(array $rows, array $turnos, ?string $userId): array
    {
        $created = [];
        $errors = [];

        $fieldMap = $this->getFieldMap();

        // Primeira linha contém as datas (a partir da coluna 1)
        $datas = [];
        for ($col = 1; $col < count($rows[0]); $col++) {
            $dataValue = $rows[0][$col] ?? null;
            if (!empty($dataValue)) {
                $parsedDate = $this->parseDate($dataValue);
                if ($parsedDate) {
                    $datas[$col] = $parsedDate;
                }
            }
        }

        if (empty($datas)) {
            $errors[] = ['linha' => 1, 'erro' => 'Nenhuma data válida encontrada na primeira linha'];
            return ['created' => $created, 'errors' => $errors];
        }

        // Construir cardápios por data
        $cardapiosPorData = [];
        foreach ($datas as $col => $data) {
            $cardapiosPorData[$data] = [];
        }

        // Percorrer linhas (campos) e preencher valores
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $fieldName = mb_strtolower(trim($row[0] ?? ''));

            $fieldKey = $fieldMap[$fieldName] ?? null;
            if (!$fieldKey) {
                continue;
            }

            foreach ($datas as $col => $data) {
                $value = $row[$col] ?? null;
                if (!empty($value)) {
                    // Limpar quebras de linha e espaços extras
                    $cardapiosPorData[$data][$fieldKey] = preg_replace('/\s+/', ' ', trim($value));
                }
            }
        }

        // Criar cardápios
        foreach ($cardapiosPorData as $data => $campos) {
            if (empty($campos['prato_principal_ptn01']) || empty($campos['prato_principal_ptn02'])) {
                $errors[] = [
                    'data' => $data,
                    'erro' => 'Pratos principais não informados'
                ];
                continue;
            }

            foreach ($turnos as $turno) {
                $cardapioData = array_merge($campos, [
                    'data_do_cardapio' => $data,
                    'turno' => $turno,
                ]);

                try {
                    $result = $this->service->createOrUpdate($cardapioData, $userId);
                    $created[] = [
                        'id' => $result['cardapio']->id,
                        'data' => $data,
                        'turno' => $turno,
                        'action' => $result['created'] ? 'created' : 'updated',
                    ];
                } catch (\Throwable $e) {
                    $errors[] = [
                        'data' => $data,
                        'turno' => $turno,
                        'erro' => $e->getMessage()
                    ];
                }
            }
        }

        return ['created' => $created, 'errors' => $errors];
    }

    /**
     * Import formato COLUNAR (datas na coluna A, campos na linha 1)
     * Formato do Excel:
     * | DATA     | PRATO PTN 01 | PRATO PTN 02 | GUARNIÇÃO |
     * | 15/12/25 | Fricassé     | Lombo        | Farofa    |
     * | 16/12/25 | exemplo      | exemplo      | exemplo   |
     */
    private function importColunar(array $rows, array $turnos, ?string $userId): array
    {
        $created = [];
        $errors = [];

        $fieldMap = $this->getFieldMap();

        // Primeira linha contém os nomes dos campos (colunas)
        $header = [];
        for ($col = 0; $col < count($rows[0]); $col++) {
            $headerValue = mb_strtolower(trim($rows[0][$col] ?? ''));
            if ($col === 0) {
                $header[$col] = 'data';
            } else {
                $header[$col] = $fieldMap[$headerValue] ?? null;
            }
        }

        // Percorrer linhas (a partir da linha 2, índice 1)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty(array_filter($row))) continue;

            // Primeira coluna é a data
            $dataValue = $row[0] ?? null;
            $parsedDate = $this->parseDate($dataValue);

            if (!$parsedDate) {
                $errors[] = [
                    'linha' => $i + 1,
                    'erro' => 'Data inválida: ' . ($dataValue ?? 'vazio')
                ];
                continue;
            }

            // Verificar se a linha parece ser um cabeçalho repetido
            $segundoCampo = mb_strtoupper(trim($row[1] ?? ''));
            if (str_contains($segundoCampo, 'PRATO PRINCIPAL') ||
                str_contains($segundoCampo, 'PTN 0') ||
                str_contains($segundoCampo, 'PTN 1')) {
                continue;
            }

            // Construir dados do cardápio
            $campos = [];
            for ($col = 1; $col < count($row); $col++) {
                $fieldKey = $header[$col] ?? null;
                $value = $row[$col] ?? null;
                if ($fieldKey && !empty($value)) {
                    $campos[$fieldKey] = preg_replace('/\s+/', ' ', trim($value));
                }
            }

            if (empty($campos['prato_principal_ptn01']) || empty($campos['prato_principal_ptn02'])) {
                $errors[] = [
                    'linha' => $i + 1,
                    'data' => $parsedDate,
                    'erro' => 'Pratos principais não informados'
                ];
                continue;
            }

            foreach ($turnos as $turno) {
                $cardapioData = array_merge($campos, [
                    'data_do_cardapio' => $parsedDate,
                    'turno' => $turno,
                ]);

                try {
                    $result = $this->service->createOrUpdate($cardapioData, $userId);
                    $created[] = [
                        'id' => $result['cardapio']->id,
                        'data' => $parsedDate,
                        'turno' => $turno,
                        'action' => $result['created'] ? 'created' : 'updated',
                    ];
                } catch (\Throwable $e) {
                    $errors[] = [
                        'linha' => $i + 1,
                        'data' => $parsedDate,
                        'turno' => $turno,
                        'erro' => $e->getMessage()
                    ];
                }
            }
        }

        return ['created' => $created, 'errors' => $errors];
    }

    /**
     * Import formato NORMAL (linhas com data_do_cardapio na primeira coluna)
     */
    private function importNormal(array $rows, array $turnos, ?string $userId): array
    {
        $created = [];
        $errors = [];

        $header = array_map(fn($h) => mb_strtolower(trim($h ?? '')), $rows[0]);

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty(array_filter($row))) continue;

            while (count($row) < count($header)) {
                $row[] = null;
            }

            $assoc = array_combine($header, $row);

            $dataCardapio = null;
            if (isset($assoc['data_do_cardapio']) && !empty($assoc['data_do_cardapio'])) {
                $dataCardapio = $this->parseDate($assoc['data_do_cardapio']);
            }

            if (!$dataCardapio) {
                $errors[] = [
                    'linha' => $i + 1,
                    'erro' => 'Data inválida ou não informada'
                ];
                continue;
            }

            foreach ($turnos as $turno) {
                $data = [
                    'data_do_cardapio' => $dataCardapio,
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
                    $result = $this->service->createOrUpdate($data, $userId);
                    $created[] = [
                        'id' => $result['cardapio']->id,
                        'data' => $result['cardapio']->data_do_cardapio,
                        'turno' => $turno,
                        'action' => $result['created'] ? 'created' : 'updated',
                        'linha' => $i + 1
                    ];
                } catch (\Throwable $e) {
                    $errors[] = [
                        'linha' => $i + 1,
                        'turno' => $turno,
                        'erro' => $e->getMessage()
                    ];
                }
            }
        }

        return ['created' => $created, 'errors' => $errors];
    }

    /**
     * Deletar todos os cardápios
     */
    public function deleteAll()
    {
        try {
            $deleted = Cardapio::query()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Todos os cardápios foram deletados com sucesso',
                'deleted_count' => $deleted
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deletar múltiplos cardápios por ID
     */
    public function deleteMultiple(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'required|integer|exists:cardapios,id'
            ]);

            $deleted = Cardapio::whereIn('id', $request->input('ids'))->delete();

            return response()->json([
                'success' => true,
                'message' => $deleted . ' cardápio(s) deletado(s) com sucesso',
                'deleted_count' => $deleted
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deletar cardápios por período de datas
     */
    public function deleteByDateRange(Request $request)
    {
        try {
            $request->validate([
                'data_inicio' => 'required|date_format:Y-m-d',
                'data_fim' => 'required|date_format:Y-m-d|after_or_equal:data_inicio'
            ]);

            $dataInicio = $request->input('data_inicio');
            $dataFim = $request->input('data_fim');

            $deleted = Cardapio::whereBetween('data_do_cardapio', [$dataInicio, $dataFim])->delete();

            return response()->json([
                'success' => true,
                'message' => $deleted . ' cardápio(s) deletado(s) no período de ' . $dataInicio . ' a ' . $dataFim,
                'deleted_count' => $deleted
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mapear nomes dos campos para keys do sistema
     */
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

    /**
     * Parse de data em vários formatos
     * Aceita: 29/12/25, 6/1/26, 29/12/2025, 2025-12-29, números seriais do Excel
     */
    private function parseDate($value): ?string
    {
        if (empty($value) && $value !== 0) {
            return null;
        }

        // Se for número (Excel serial date)
        if (is_numeric($value) && $value > 0) {
            try {
                // Excel serial date: dias desde 1899-12-30
                $unixTimestamp = ($value - 25569) * 86400;
                $date = Carbon::createFromTimestamp($unixTimestamp);
                return $date->toDateString();
            } catch (\Exception $e) {
                // Continua para tentar outros formatos
            }
        }

        // Converter para string
        $value = trim((string) $value);

        if (empty($value)) {
            return null;
        }

        // Formatos para tentar
        $formats = [
            'd/m/y',      // 29/12/25
            'j/n/y',      // 6/1/26
            'd/m/Y',      // 29/12/2025
            'j/n/Y',      // 6/1/2026
            'Y-m-d',      // 2025-12-29
            'd-m-Y',      // 29-12-2025
            'd-m-y',      // 29-12-25
            'j/m/y',      // 6/12/25
            'd/n/y',      // 29/1/26
            'j-n-y',      // 6-1-26
            'd.m.y',      // 29.12.25
            'd.m.Y',      // 29.12.2025
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

        // Última tentativa
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }
}

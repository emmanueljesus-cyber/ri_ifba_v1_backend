<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CardapioImportRequest;
use App\Http\Requests\Admin\CardapioStoreRequest;
use App\Http\Requests\Admin\CardapioUpdateRequest;
use App\Http\Resources\CardapioResource;
use App\Models\Cardapio;
use App\Services\CardapioImportService;
use App\Services\CardapioService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CardapioController extends Controller
{
    public function __construct(
        private CardapioService $service,
        private CardapioImportService $importService,
    ) {
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
        return response()->json([
            'data' => new CardapioResource($cardapio),
            'errors' => [],
            'meta' => ['created' => true],
        ], 201);
    }

    public function show(Cardapio $cardapio)
    {
        $cardapio->load(['refeicoes', 'criador']);
        
        return response()->json([
            'data' => new CardapioResource($cardapio),
            'errors' => [],
            'meta' => [],
        ]);
    }

    public function update(CardapioUpdateRequest $request, Cardapio $cardapio)
    {
        $cardapio = $this->service->update($cardapio, $request->validated());
        return response()->json([
            'data' => new CardapioResource($cardapio),
            'errors' => [],
            'meta' => ['updated' => true],
        ]);
    }

    public function destroy(Cardapio $cardapio)
    {
        $this->service->delete($cardapio);
        return response()->json([
            'data' => null,
            'errors' => [],
            'meta' => ['deleted' => true],
        ]);
    }

    public function import(CardapioImportRequest $request)
    {
        $file = $request->file('file');
        $turnos = $request->validated('turno') ?? ['almoco'];
        $rows = Excel::toArray(null, $file)[0] ?? [];

        if (empty($rows)) {
            return response()->json([
                'data' => [],
                'errors' => ['file' => ['Arquivo vazio']],
                'meta' => [],
            ], 422);
        }

        $result = $this->importService->import(
            rows: $rows,
            turnos: $turnos,
            userId: $request->user()?->id,
            debug: $request->boolean('debug')
        );

        if ($request->boolean('debug') && $result['debug']) {
            return response()->json([
                'data' => $result['debug'],
                'errors' => [],
                'meta' => ['debug' => true],
            ]);
        }

        return response()->json([
            'data' => $result['created'],
            'errors' => $result['errors'],
            'meta' => [
                'total_criados' => count($result['created']),
                'total_erros' => count($result['errors']),
            ],
        ], 201);
    }

    /**
     * Deletar todos os cardápios
     */
    public function deleteAll()
    {
        $deleted = Cardapio::query()->delete();
        return response()->json([
            'data' => null,
            'errors' => [],
            'meta' => ['deleted_count' => $deleted],
        ]);
    }

    /**
     * Deletar múltiplos cardápios por ID
     */
    public function deleteMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:cardapios,id'
        ]);

        $deleted = Cardapio::whereIn('id', $request->input('ids'))->delete();

        return response()->json([
            'data' => null,
            'errors' => [],
            'meta' => ['deleted_count' => $deleted],
        ]);
    }

    /**
     * Deletar cardápios por período de datas
     */
    public function deleteByDateRange(Request $request)
    {
        $request->validate([
            'data_inicio' => 'required|date_format:Y-m-d',
            'data_fim' => 'required|date_format:Y-m-d|after_or_equal:data_inicio'
        ]);

        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');

        $deleted = Cardapio::whereBetween('data_do_cardapio', [$dataInicio, $dataFim])->delete();

        return response()->json([
            'data' => null,
            'errors' => [],
            'meta' => [
                'deleted_count' => $deleted,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
            ],
        ]);
    }
}

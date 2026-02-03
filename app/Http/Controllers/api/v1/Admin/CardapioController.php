<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CardapioImportRequest;
use App\Http\Requests\Admin\CardapioStoreRequest;
use App\Http\Requests\Admin\CardapioUpdateRequest;
use App\Http\Resources\CardapioResource;
use App\Http\Responses\ApiResponse;
use App\Models\Cardapio;
use App\Services\CardapioImportService;
use App\Services\CardapioService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller para gerenciamento de cardápios (RF08)
 * 
 * Responsabilidades:
 * - CRUD de cardápios
 * - Importação de cardápios via Excel e Manuel
 * - Operações em lote (deletar múltiplos, por período)
 */
class CardapioController extends Controller
{
    public function __construct(
        private CardapioService $service,
        private CardapioImportService $importService,
    ) {}

    /**
     * RF08 - Listar cardápios
     * GET /api/v1/admin/cardapios
     */
    public function index(Request $request)
    {
        $data = $this->service->paginate(
            $request->only('data'),
            $request->integer('per_page', 15)
        );
        
        return CardapioResource::collection($data);
    }

    /**
     * RF08 - Criar cardápio
     * POST /api/v1/admin/cardapios
     */
    public function store(CardapioStoreRequest $request)
    {
        $userId = $request->user()?->id;
        $cardapio = $this->service->create($request->validated(), $userId);
        
        return ApiResponse::standardCreated(
            new CardapioResource($cardapio),
            ['created' => true]
        );
    }

    /**
     * RF08 - Exibir cardápio
     * GET /api/v1/admin/cardapios/{cardapio}
     */
    public function show(Cardapio $cardapio)
    {
        $cardapio->load(['refeicoes', 'criador']);
        
        return ApiResponse::standardSuccess(
            new CardapioResource($cardapio)
        );
    }

    /**
     * RF08 - Atualizar cardápio
     * PUT/PATCH /api/v1/admin/cardapios/{cardapio}
     */
    public function update(CardapioUpdateRequest $request, Cardapio $cardapio)
    {
        $cardapio = $this->service->update($cardapio, $request->validated());
        
        return ApiResponse::standardSuccess(
            new CardapioResource($cardapio),
            ['updated' => true]
        );
    }

    /**
     * RF08 - Deletar cardápio
     * DELETE /api/v1/admin/cardapios/{cardapio}
     */
    public function destroy(Cardapio $cardapio)
    {
        $this->service->delete($cardapio);
        
        return ApiResponse::standardSuccess(
            null,
            ['deleted' => true]
        );
    }

    /**
     * RF08 - Importar cardápios via Excel/CSV
     * POST /api/v1/admin/cardapios/import
     */
    public function import(CardapioImportRequest $request)
    {
        $file = $request->file('file');
        $turnos = $request->validated('turno') ?? ['almoco'];
        $rows = Excel::toArray(null, $file)[0] ?? [];

        if (empty($rows)) {
            return ApiResponse::standardError('file', 'Arquivo vazio', 422);
        }

        $result = $this->importService->import(
            rows: $rows,
            turnos: $turnos,
            userId: $request->user()?->id,
            debug: $request->boolean('debug')
        );

        // Modo debug
        if ($request->boolean('debug') && $result['debug']) {
            return ApiResponse::standardResponse(
                data: $result['debug'],
                meta: ['debug' => true]
            );
        }

        // Resposta normal
        return ApiResponse::standardCreated(
            data: $result['created'],
            meta: [
                'total_criados' => count($result['created']),
                'total_erros' => count($result['errors']),
                'errors' => $result['errors'],
            ]
        );
    }

    /**
     * Deletar todos os cardápios
     * DELETE /api/v1/admin/cardapios/all
     */
    public function deleteAll()
    {
        $deleted = Cardapio::query()->delete();
        
        return ApiResponse::standardSuccess(
            null,
            ['deleted_count' => $deleted]
        );
    }

    /**
     * Deletar múltiplos cardápios por ID
     * POST /api/v1/admin/cardapios/delete-multiple
     */
    public function deleteMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:cardapios,id'
        ]);

        $deleted = Cardapio::whereIn('id', $request->input('ids'))->delete();

        return ApiResponse::standardSuccess(
            null,
            ['deleted_count' => $deleted]
        );
    }

    /**
     * Deletar cardápios por período de datas
     * POST /api/v1/admin/cardapios/delete-by-date-range
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

        return ApiResponse::standardSuccess(
            null,
            [
                'deleted_count' => $deleted,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
            ]
        );
    }

    /**
     * Exportar template Excel para importação de cardápios
     * GET /api/v1/admin/cardapios/template
     */
    public function exportTemplate()
    {
        try {
            // Verifica se as dependências estão disponíveis
            if (!class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
                throw new \Exception('Laravel Excel package não está instalado');
            }

            if (!class_exists(\App\Exports\CardapioTemplateExport::class)) {
                throw new \Exception('Classe CardapioTemplateExport não encontrada');
            }

            $filename = 'template_cardapios_' . now()->format('Y-m-d') . '.xlsx';

            return Excel::download(
                new \App\Exports\CardapioTemplateExport(),
                $filename,
                \Maatwebsite\Excel\Excel::XLSX,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                    'Cache-Control' => 'max-age=0',
                    'Pragma' => 'public',
                ]
            );
        } catch (\Exception $e) {
            \Log::error('Erro ao exportar template de cardápios', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'php_version' => PHP_VERSION,
                'extensions' => get_loaded_extensions(),
                'class_exists' => class_exists(\Maatwebsite\Excel\Facades\Excel::class),
                'export_class' => class_exists(\App\Exports\CardapioTemplateExport::class),
            ]);

            return ApiResponse::standardError(
                'export_error',
                'Erro ao gerar template Excel: ' . $e->getMessage(),
                500
            );
        }
    }
}

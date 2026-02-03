<?php

namespace App\Services;

use App\Models\Cardapio;
use App\Models\Refeicao;
use Illuminate\Support\Facades\DB;

class CardapioService
{
    public function paginate(array $filters = [], int $perPage = 15)
    {
        return Cardapio::with(['criador', 'refeicoes'])
            ->when(!empty($filters['data']), fn($q) => $q->whereDate('data_do_cardapio', $filters['data']))
            ->orderBy('data_do_cardapio', 'desc')
            ->paginate($perPage);
    }

    public function find(int $id): Cardapio
    {
        return Cardapio::with(['criador', 'refeicoes'])->findOrFail($id);
    }

    public function create(array $data, ?string $userId): Cardapio
    {
        return DB::transaction(function () use ($data, $userId) {
            $ptn01 = $data['prato_principal_ptn01'] ?? null;
            $ptn02 = $data['prato_principal_ptn02'] ?? null;
            // Garantir que pelo menos uma opção de prato principal exista e nunca gravar null (colunas NOT NULL)
            if (empty($ptn01) && !empty($ptn02)) {
                $ptn01 = $ptn02;
            }
            if (empty($ptn02) && !empty($ptn01)) {
                $ptn02 = $ptn01;
            }

            $cardapio = Cardapio::create([
                'data_do_cardapio'      => $data['data_do_cardapio'],
                'turnos'                => $data['turnos'] ?? ['almoco', 'jantar'],
                'prato_principal_ptn01' => $ptn01 ?? '',
                'prato_principal_ptn02' => $ptn02 ?? '',
                'guarnicao'             => $data['guarnicao'] ?? null,
                'acompanhamento_01'     => $data['acompanhamento_01'] ?? '',
                'acompanhamento_02'     => $data['acompanhamento_02'] ?? '',
                'salada'                => $data['salada'] ?? null,
                'ovo_lacto_vegetariano' => $data['ovo_lacto_vegetariano'] ?? null,
                'suco'                  => $data['suco'] ?? null,
                'sobremesa'             => $data['sobremesa'] ?? null,
                'criado_por'            => $userId,
            ]);

            // Se for fornecida capacidade no create, aplica às refeições criadas
            if (isset($data['capacidade'])) {
                $cardapio->refeicoes()->update(['capacidade' => $data['capacidade']]);
            }

            return $cardapio->load(['criador', 'refeicoes']);
        });
    }

    /**
     * Criar ou atualizar cardápio (upsert) - usado na importação
     * Se o cardápio já existir para a data, atualiza os dados
     * Se a refeição (turno) já existir, atualiza; senão, cria nova
     */
    public function createOrUpdate(array $data, ?string $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $dataCardapio = $data['data_do_cardapio'];
            $turno = $data['turno'] ?? 'almoco';

            // Buscar cardápio existente para a data
            $cardapio = Cardapio::where('data_do_cardapio', $dataCardapio)->first();
            $isNew = false;

            if ($cardapio) {
                // Mesclar turnos existentes com o novo turno
                $turnosExistentes = (array) ($cardapio->turnos ?? []);
                if (!in_array($turno, $turnosExistentes)) {
                    $turnosExistentes[] = $turno;
                }

                // Atualizar cardápio existente
                $cardapio->update([
                    'turnos'                => $turnosExistentes,
                    'prato_principal_ptn01' => $data['prato_principal_ptn01'] ?? $cardapio->prato_principal_ptn01,
                    'prato_principal_ptn02' => $data['prato_principal_ptn02'] ?? $cardapio->prato_principal_ptn02,
                    'guarnicao'             => $data['guarnicao'] ?? $cardapio->guarnicao,
                    'acompanhamento_01'     => $data['acompanhamento_01'] ?? $cardapio->acompanhamento_01,
                    'acompanhamento_02'     => $data['acompanhamento_02'] ?? $cardapio->acompanhamento_02,
                    'salada'                => $data['salada'] ?? $cardapio->salada,
                    'ovo_lacto_vegetariano' => $data['ovo_lacto_vegetariano'] ?? $cardapio->ovo_lacto_vegetariano,
                    'suco'                  => $data['suco'] ?? $cardapio->suco,
                    'sobremesa'             => $data['sobremesa'] ?? $cardapio->sobremesa,
                ]);
            } else {
                // Criar novo cardápio
                $cardapio = Cardapio::create([
                    'data_do_cardapio'      => $dataCardapio,
                    'turnos'                => [$turno],
                    'prato_principal_ptn01' => $data['prato_principal_ptn01'],
                    'prato_principal_ptn02' => $data['prato_principal_ptn02'],
                    'guarnicao'             => $data['guarnicao'] ?? null,
                    'acompanhamento_01'     => $data['acompanhamento_01'],
                    'acompanhamento_02'     => $data['acompanhamento_02'],
                    'salada'                => $data['salada'] ?? null,
                    'ovo_lacto_vegetariano' => $data['ovo_lacto_vegetariano'] ?? null,
                    'suco'                  => $data['suco'] ?? null,
                    'sobremesa'             => $data['sobremesa'] ?? null,
                    'criado_por'            => $userId,
                ]);
                $isNew = true;
            }

            // Buscar ou criar refeição para o turno
            $refeicao = Refeicao::where('cardapio_id', $cardapio->id)
                ->where('turno', $turno)
                ->first();

            if ($refeicao) {
                $refeicao->update([
                    'capacidade' => $data['capacidade'] ?? $refeicao->capacidade,
                ]);
            } else {
                Refeicao::create([
                    'cardapio_id'      => $cardapio->id,
                    'data_do_cardapio' => $cardapio->data_do_cardapio,
                    'turno'            => $turno,
                    'capacidade'       => $data['capacidade'] ?? null,
                ]);
            }

            return [
                'cardapio' => $cardapio->refresh()->load(['criador', 'refeicoes']),
                'created' => $isNew,
                'turno' => $turno,
            ];
        });
    }

    public function update(Cardapio $cardapio, array $data): Cardapio
    {
        return DB::transaction(function () use ($cardapio, $data) {
            $cardapio->update([
                'data_do_cardapio'      => $data['data_do_cardapio'] ?? $cardapio->data_do_cardapio,
                'turnos'                => $data['turnos'] ?? $cardapio->turnos,
                'prato_principal_ptn01' => $data['prato_principal_ptn01'] ?? $cardapio->prato_principal_ptn01,
                'prato_principal_ptn02' => $data['prato_principal_ptn02'] ?? $cardapio->prato_principal_ptn02,
                'guarnicao'             => $data['guarnicao'] ?? $cardapio->guarnicao,
                'acompanhamento_01'     => $data['acompanhamento_01'] ?? $cardapio->acompanhamento_01,
                'acompanhamento_02'     => $data['acompanhamento_02'] ?? $cardapio->acompanhamento_02,
                'salada'                => $data['salada'] ?? $cardapio->salada,
                'ovo_lacto_vegetariano' => $data['ovo_lacto_vegetariano'] ?? $cardapio->ovo_lacto_vegetariano,
                'suco'                  => $data['suco'] ?? $cardapio->suco,
                'sobremesa'             => $data['sobremesa'] ?? $cardapio->sobremesa,
            ]);

            // Se for fornecido um turno específico e capacidade, atualiza essa refeição
            if (isset($data['turno']) && isset($data['capacidade'])) {
                $cardapio->refeicoes()->where('turno', $data['turno'])->update([
                    'capacidade' => $data['capacidade']
                ]);
            } elseif (isset($data['capacidade'])) {
                // Se só tiver capacidade, aplica a todas
                $cardapio->refeicoes()->update(['capacidade' => $data['capacidade']]);
            }

            return $cardapio->load(['criador', 'refeicoes']);
        });
    }

    public function delete(Cardapio $cardapio): void
    {
        $cardapio->delete();
    }

    public function cardapioDeHoje(): ?Cardapio
    {
        return Cardapio::hoje()->with(['criador', 'refeicoes'])->first();
    }

    public function cardapioSemanal(?string $turno = null, ?string $data = null)
    {
        $dataRef = $data ? \Carbon\Carbon::parse($data) : now();
        $inicio = $dataRef->copy()->startOfWeek();
        $fim = $dataRef->copy()->endOfWeek();

        return Cardapio::with(['criador', 'refeicoes'])
            ->dataEntre($inicio->toDateString(), $fim->toDateString())
            ->when($turno, fn($q) => $q->whereHas('refeicoes', fn($r) => $r->where('turno', $turno)))
            ->orderBy('data_do_cardapio', 'asc')
            ->get();
    }

    public function cardapioMensal(?string $turno = null, int $perPage = 15)
    {
        $inicio = now()->startOfMonth();
        $fim = now()->endOfMonth();

        return Cardapio::with(['criador', 'refeicoes'])
            ->dataEntre($inicio->toDateString(), $fim->toDateString())
            ->when($turno, fn($q) => $q->whereHas('refeicoes', fn($r) => $r->where('turno', $turno)))
            ->orderBy('data_do_cardapio', 'asc')
            ->paginate($perPage);
    }
}

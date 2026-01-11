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
            $cardapio = Cardapio::create([
                'data_do_cardapio'      => $data['data_do_cardapio'],
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

            $turno = $data['turno'] ?? 'almoco';

            // Como o observer do model já cria as refeições padrão,
            // devemos buscar a existente ou criar se não existir (caso o turno não seja padrão)
            $refeicao = $cardapio->refeicoes()->where('turno', $turno)->first();

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
                // Atualizar cardápio existente
                $cardapio->update([
                    'prato_principal_ptn01' => $data['prato_principal_ptn01'],
                    'prato_principal_ptn02' => $data['prato_principal_ptn02'],
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
                'cardapio' => $cardapio->load(['criador', 'refeicoes']),
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

            $turno = $data['turno'] ?? 'almoco';
            
            // Busca refeição específica do turno ou usa a padrão 'almoco'
            $refeicao = $cardapio->refeicoes()->where('turno', $turno)->first();
            
            // Se mudou o turno na requisição, precisamos garantir que estamos atualizando a refeição correta
            // ou criando uma nova se não existir.
            // Para manter compatibilidade com o código anterior, se a refeição não existir, criamos.
            
            if ($refeicao) {
                 $refeicao->update([
                    'data_do_cardapio' => $cardapio->data_do_cardapio,
                    'capacidade'       => $data['capacidade'] ?? $refeicao->capacidade,
                ]);
            } else {
                 Refeicao::create([
                    'cardapio_id'      => $cardapio->id,
                    'data_do_cardapio' => $cardapio->data_do_cardapio,
                    'turno'            => $turno,
                    'capacidade'       => $data['capacidade'] ?? null,
                ]);
            }
            // Nota: Se a intenção do update era MUDAR o turno de uma refeição existente,
            // isso é complexo pois Refeicao é identificada por (cardapio_id, turno).
            // Vamos assumir que estamos atualizando a PROPRIEDADE da refeição daquele turno.

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

    public function cardapioSemanal(?string $turno = null)
    {
        $inicio = now()->startOfWeek();
        $fim = now()->endOfWeek();

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

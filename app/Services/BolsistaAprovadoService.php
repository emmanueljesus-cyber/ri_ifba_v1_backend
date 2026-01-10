<?php

namespace App\Services;

use App\Models\Bolsista;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Service para gerenciamento de matrículas aprovadas para bolsistas
 * 
 * Responsável por toda a lógica de negócio relacionada a:
 * - CRUD de matrículas aprovadas
 * - Validação de duplicatas
 * - Sincronização com usuários cadastrados
 */
class BolsistaAprovadoService
{
    /**
     * Lista bolsistas aprovados com filtros e paginação
     * 
     * @param array $filtros Filtros (turno, matricula, ativo, etc)
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listarBolsistas(array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Bolsista::query();

        // Filtro por turno
        if (isset($filtros['turno'])) {
            $query->where('turno', $filtros['turno']);
        }

        // Filtro por status ativo
        if (isset($filtros['ativo'])) {
            $query->where('ativo', $filtros['ativo']);
        } else {
            // Por padrão, mostrar apenas ativos
            $query->where('ativo', true);
        }

        // Busca por matrícula
        if (isset($filtros['matricula'])) {
            $query->where('matricula', 'LIKE', "%{$filtros['matricula']}%");
        }

        // Ordenação
        $sortBy = $filtros['sort_by'] ?? 'matricula';
        $sortOrder = $filtros['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Busca bolsista aprovado por ID
     * 
     * @param int $id
     * @return Bolsista
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function buscarBolsista(int $id): Bolsista
    {
        return Bolsista::findOrFail($id);
    }

    /**
     * Busca bolsista por matrícula
     * 
     * @param string $matricula
     * @return Bolsista|null
     */
    public function buscarPorMatricula(string $matricula): ?Bolsista
    {
        return Bolsista::where('matricula', $matricula)->first();
    }

    /**
     * Adiciona novo bolsista à lista de aprovados
     * 
     * @param string $matricula
     * @param string $turno (almoco|jantar)
     * @return Bolsista
     * @throws \Exception Se matrícula já existe
     */
    public function adicionarBolsista(string $matricula, string $turno): Bolsista
    {
        return DB::transaction(function () use ($matricula, $turno) {
            // Verificar se já existe
            $existente = $this->buscarPorMatricula($matricula);
            
            if ($existente && $existente->ativo) {
                throw new \Exception("Matrícula {$matricula} já está na lista de bolsistas aprovados.");
            }

            // Se existe mas está inativo, reativar
            if ($existente && !$existente->ativo) {
                return $this->reativarBolsista($existente->id);
            }

            // Criar novo
            $bolsista = Bolsista::create([
                'matricula' => $matricula,
                'turno' => $turno,
                'ativo' => true,
            ]);

            // Atualizar usuário se já existir
            $this->atualizarUsuarioSeExiste($matricula);

            return $bolsista;
        });
    }

    /**
     * Atualiza dados de um bolsista aprovado
     * 
     * @param int $id
     * @param array $data
     * @return Bolsista
     */
    public function atualizarBolsista(int $id, array $data): Bolsista
    {
        return DB::transaction(function () use ($id, $data) {
            $bolsista = $this->buscarBolsista($id);

            // Se mudou matrícula, validar duplicata
            if (isset($data['matricula']) && $data['matricula'] !== $bolsista->matricula) {
                $duplicata = Bolsista::where('matricula', $data['matricula'])
                    ->where('id', '!=', $id)
                    ->where('ativo', true)
                    ->first();

                if ($duplicata) {
                    throw new \Exception("Matrícula {$data['matricula']} já existe na lista.");
                }
            }

            $bolsista->update($data);

            // Sincronizar com usuário se necessário
            if (isset($data['matricula'])) {
                $this->atualizarUsuarioSeExiste($data['matricula']);
            }

            return $bolsista->fresh();
        });
    }

    /**
     * Desativa um bolsista (soft delete)
     * 
     * @param int $id
     * @return bool
     */
    public function desativarBolsista(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $bolsista = $this->buscarBolsista($id);

            // Desativar o registro
            $bolsista->update(['ativo' => false]);

            // Atualizar usuário se existir
            $user = User::where('matricula', $bolsista->matricula)->first();
            if ($user) {
                $user->update(['bolsista' => false]);
            }

            return true;
        });
    }

    /**
     * Reativa um bolsista
     * 
     * @param int $id
     * @return Bolsista
     */
    public function reativarBolsista(int $id): Bolsista
    {
        return DB::transaction(function () use ($id) {
            $bolsista = $this->buscarBolsista($id);

            $bolsista->update(['ativo' => true]);

            // Atualizar usuário se existir
            $this->atualizarUsuarioSeExiste($bolsista->matricula);

            return $bolsista->fresh();
        });
    }

    /**
     * Remove permanentemente um bolsista (não recomendado)
     * 
     * @param int $id
     * @return bool
     */
    public function removerPermanentemente(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $bolsista = $this->buscarBolsista($id);
            $matricula = $bolsista->matricula;

            $bolsista->delete();

            // Atualizar usuário se existir
            $user = User::where('matricula', $matricula)->first();
            if ($user) {
                $user->update(['bolsista' => false]);
            }

            return true;
        });
    }

    /**
     * Sincroniza status de bolsista com usuário cadastrado
     * 
     * Se um usuário com a matrícula já existe, atualiza flag bolsista=true
     * 
     * @param string $matricula
     * @return void
     */
    private function atualizarUsuarioSeExiste(string $matricula): void
    {
        $user = User::where('matricula', $matricula)->first();
        
        if ($user) {
            $bolsistaAprovado = Bolsista::where('matricula', $matricula)
                ->where('ativo', true)
                ->exists();

            $user->update(['bolsista' => $bolsistaAprovado]);
        }
    }

    /**
     * Verifica se uma matrícula está na lista de aprovados
     * 
     * @param string $matricula
     * @return bool
     */
    public function matriculaAprovada(string $matricula): bool
    {
        return Bolsista::where('matricula', $matricula)
            ->where('ativo', true)
            ->exists();
    }

    /**
     * Estatísticas de bolsistas aprovados
     * 
     * @return array
     */
    public function estatisticas(): array
    {
        $total = Bolsista::where('ativo', true)->count();
        $almoco = Bolsista::where('ativo', true)->where('turno', 'almoco')->count();
        $jantar = Bolsista::where('ativo', true)->where('turno', 'jantar')->count();
        $inativos = Bolsista::where('ativo', false)->count();

        return [
            'total_ativos' => $total,
            'almoco' => $almoco,
            'jantar' => $jantar,
            'inativos' => $inativos,
        ];
    }
}

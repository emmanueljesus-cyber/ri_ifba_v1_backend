<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Service para gerenciamento de usuários
 * 
 * Responsável por toda a lógica de negócio relacionada a:
 * - CRUD de usuários
 * - Gerenciamento de status (ativo/desligado)
 * - Validações e regras de negócio
 */
class UserService
{
    /**
     * Lista usuários com filtros e paginação
     * 
     * @param array $filtros Filtros (perfil, bolsista, desligado, busca, etc)
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listarUsuarios(array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query();

        // Filtro por perfil
        if (isset($filtros['perfil'])) {
            $query->where('perfil', $filtros['perfil']);
        }

        // Filtro por bolsista
        if (isset($filtros['bolsista'])) {
            $query->where('bolsista', $filtros['bolsista']);
        }

        // Filtro por status desligado
        if (isset($filtros['desligado'])) {
            $query->where('desligado', $filtros['desligado']);
        } else {
            // Por padrão, não mostrar desligados
            $query->where('desligado', false);
        }

        // Busca por nome, matrícula ou email
        if (isset($filtros['busca'])) {
            $busca = $filtros['busca'];
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'ILIKE', "%{$busca}%")
                  ->orWhere('matricula', 'ILIKE', "%{$busca}%")
                  ->orWhere('email', 'ILIKE', "%{$busca}%");
            });
        }

        // Ordenação
        $sortBy = $filtros['sort_by'] ?? 'nome';
        $sortOrder = $filtros['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Busca usuário por ID
     * 
     * @param int $id
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function buscarUsuario(int $id): User
    {
        return User::findOrFail($id);
    }

    /**
     * Busca usuário por matrícula
     * 
     * @param string $matricula
     * @return User|null
     */
    public function buscarPorMatricula(string $matricula): ?User
    {
        return User::where('matricula', $matricula)->first();
    }

    /**
     * Cria novo usuário
     * 
     * @param array $data Dados do usuário
     * @return User
     * @throws \Exception Se dados inválidos ou matrícula duplicada
     */
    public function criarUsuario(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Verificar duplicata de matrícula
            if (isset($data['matricula'])) {
                $existente = $this->buscarPorMatricula($data['matricula']);
                if ($existente) {
                    throw new \Exception("Matrícula {$data['matricula']} já está cadastrada.");
                }
            }

            // Verificar duplicata de email
            if (isset($data['email'])) {
                $emailExistente = User::where('email', $data['email'])->first();
                if ($emailExistente) {
                    throw new \Exception("Email {$data['email']} já está cadastrado.");
                }
            }

            // Hash da senha
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Definir valores padrão
            $data['desligado'] = $data['desligado'] ?? false;
            $data['perfil'] = $data['perfil'] ?? 'estudante';

            // Criar usuário
            $user = User::create($data);

            return $user;
        });
    }

    /**
     * Atualiza dados de um usuário
     * 
     * @param int $id
     * @param array $data
     * @return User
     * @throws \Exception Se dados inválidos
     */
    public function atualizarUsuario(int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {
            $user = $this->buscarUsuario($id);

            // Se mudou matrícula, validar duplicata
            if (isset($data['matricula']) && $data['matricula'] !== $user->matricula) {
                $duplicata = User::where('matricula', $data['matricula'])
                    ->where('id', '!=', $id)
                    ->first();

                if ($duplicata) {
                    throw new \Exception("Matrícula {$data['matricula']} já existe.");
                }
            }

            // Se mudou email, validar duplicata
            if (isset($data['email']) && $data['email'] !== $user->email) {
                $duplicata = User::where('email', $data['email'])
                    ->where('id', '!=', $id)
                    ->first();

                if ($duplicata) {
                    throw new \Exception("Email {$data['email']} já existe.");
                }
            }

            // Hash da senha se fornecida
            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']); // Não atualizar senha se não fornecida
            }

            $user->update($data);

            return $user->fresh();
        });
    }

    /**
     * Desativa usuário (soft delete)
     * 
     * @param int $id
     * @return bool
     */
    public function desativarUsuario(int $id): bool
    {
        $user = $this->buscarUsuario($id);
        
        if ($user->desligado) {
            throw new \Exception('Usuário já está desativado.');
        }

        $user->update(['desligado' => true]);

        return true;
    }

    /**
     * Reativa usuário
     * 
     * @param int $id
     * @return User
     */
    public function reativarUsuario(int $id): User
    {
        $user = User::where('id', $id)->where('desligado', true)->first();

        if (!$user) {
            throw new \Exception('Usuário não encontrado ou já está ativo.');
        }

        $user->update(['desligado' => false]);

        return $user->fresh();
    }

    /**
     * Lista apenas bolsistas ativos
     * 
     * @param array $filtros Filtros opcionais (turno, etc)
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listarBolsistas(array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::where('bolsista', true)
            ->where('desligado', false);

        // Filtro por turno
        if (isset($filtros['turno'])) {
            $query->where('turno', $filtros['turno']);
        }

        $query->orderBy('nome', 'asc');

        return $query->paginate($perPage);
    }

    /**
     * Estatísticas de usuários
     * 
     * @return array
     */
    public function estatisticas(): array
    {
        $totalUsuarios = User::count();
        $ativos = User::where('desligado', false)->count();
        $desligados = User::where('desligado', true)->count();
        $bolsistas = User::where('bolsista', true)->where('desligado', false)->count();
        $estudantes = User::where('perfil', 'estudante')->where('desligado', false)->count();
        $admins = User::where('perfil', 'admin')->where('desligado', false)->count();

        return [
            'total' => $totalUsuarios,
            'ativos' => $ativos,
            'desligados' => $desligados,
            'bolsistas' => $bolsistas,
            'estudantes' => $estudantes,
            'administradores' => $admins,
        ];
    }

    /**
     * Valida se usuário pode ser removido
     * 
     * Verifica se usuário não tem dependências (presenças, justificativas, etc)
     * 
     * @param int $id
     * @return array ['pode_remover' => bool, 'motivo' => string|null]
     */
    public function podeRemover(int $id): array
    {
        $user = $this->buscarUsuario($id);

        // Verificar se tem presenças registradas
        if ($user->presencas()->exists()) {
            return [
                'pode_remover' => false,
                'motivo' => 'Usuário possui presenças registradas. Use desativação ao invés de remoção.',
            ];
        }

        // Verificar se tem justificativas
        if ($user->justificativas()->exists()) {
            return [
                'pode_remover' => false,
                'motivo' => 'Usuário possui justificativas registradas. Use desativação ao invés de remoção.',
            ];
        }

        return ['pode_remover' => true, 'motivo' => null];
    }
}

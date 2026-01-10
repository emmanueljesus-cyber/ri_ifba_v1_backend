<?php

namespace App\Helpers;

use App\Models\Refeicao;
use App\Models\User;

/**
 * Helper para validações comuns reutilizáveis
 * 
 * Centraliza validações frequentemente usadas nos controllers,
 * eliminando duplicação e facilitando manutenção.
 */
class ValidationHelper
{
    /**
     * Busca refeição por data e turno
     * 
     * Retorna array com a refeição encontrada ou informações de erro.
     * Usado principalmente em controllers de presença e bolsistas.
     * 
     * @param string $data Data da refeição (Y-m-d)
     * @param string $turno Turno (almoco|jantar)
     * @return array ['refeicao' => Refeicao|null, 'erro' => array|null]
     */
    public static function buscarRefeicao(string $data, string $turno): array
    {
        $refeicao = Refeicao::where('data_do_cardapio', $data)
            ->where('turno', $turno)
            ->first();
        
        if (!$refeicao) {
            return [
                'refeicao' => null,
                'erro' => [
                    'message' => 'Refeição não encontrada para esta data e turno.',
                    'data' => $data,
                    'turno' => $turno,
                ],
            ];
        }
        
        return ['refeicao' => $refeicao, 'erro' => null];
    }
    
    /**
     * Busca usuário por ID
     * 
     * Retorna array com o usuário encontrado ou informações de erro.
     * 
     * @param int $userId ID do usuário
     * @return array ['user' => User|null, 'erro' => array|null]
     */
    public static function buscarUsuario(int $userId): array
    {
        $user = User::find($userId);
        
        if (!$user) {
            return [
                'user' => null,
                'erro' => [
                    'message' => 'Usuário não encontrado.',
                    'user_id' => $userId,
                ],
            ];
        }
        
        return ['user' => $user, 'erro' => null];
    }
    
    /**
     * Busca usuário por matrícula
     * 
     * @param string $matricula Matrícula do usuário
     * @return array ['user' => User|null, 'erro' => array|null]
     */
    public static function buscarUsuarioPorMatricula(string $matricula): array
    {
        $user = User::where('matricula', $matricula)->first();
        
        if (!$user) {
            return [
                'user' => null,
                'erro' => [
                    'message' => 'Usuário não encontrado com esta matrícula.',
                    'matricula' => $matricula,
                ],
            ];
        }
        
        return ['user' => $user, 'erro' => null];
    }
    
    /**
     * Valida se o turno é válido
     * 
     * @param string $turno Turno a validar
     * @return array ['valido' => bool, 'erro' => string|null]
     */
    public static function validarTurno(string $turno): array
    {
        $turnosValidos = ['almoco', 'jantar'];
        
        if (!in_array($turno, $turnosValidos)) {
            return [
                'valido' => false,
                'erro' => "Turno inválido. Valores aceitos: " . implode(', ', $turnosValidos),
            ];
        }
        
        return ['valido' => true, 'erro' => null];
    }
    
    /**
     * Valida se uma data está no formato correto (Y-m-d)
     * 
     * @param string $data Data a validar
     * @return array ['valido' => bool, 'erro' => string|null]
     */
    public static function validarFormatoData(string $data): array
    {
        $pattern = '/^\d{4}-\d{2}-\d{2}$/';
        
        if (!preg_match($pattern, $data)) {
            return [
                'valido' => false,
                'erro' => 'Data deve estar no formato Y-m-d (ex: 2026-01-10)',
            ];
        }
        
        return ['valido' => true, 'erro' => null];
    }

    /**
     * Valida se o bolsista pode acessar refeição (ativo, é bolsista, tem direito ao dia)
     * 
     * @param User $user Usuário a validar
     * @param int $diaSemana Dia da semana (0-6)
     * @return array ['valido' => bool, 'erro' => ?array]
     */
    public static function validarBolsistaAtivo(User $user, int $diaSemana): array
    {
        if (!$user->bolsista) {
            return [
                'valido' => false,
                'erro' => [
                    'chave' => 'bolsista',
                    'message' => 'Este usuário não é bolsista.',
                    'code' => 422
                ],
            ];
        }

        if ($user->desligado) {
            return [
                'valido' => false,
                'erro' => [
                    'chave' => 'bolsista',
                    'message' => 'Este bolsista está desligado.',
                    'code' => 422
                ],
            ];
        }

        $temDireito = $user->diasSemana()->where('dia_semana', $diaSemana)->exists();

        if (!$temDireito) {
            return [
                'valido' => false,
                'erro' => [
                    'chave' => 'permissao',
                    'message' => 'Bolsista não tem direito a refeição neste dia.',
                    'code' => 422
                ],
            ];
        }

        return ['valido' => true, 'erro' => null];
    }
}

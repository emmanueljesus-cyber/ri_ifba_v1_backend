<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Classe para padronizar respostas JSON da API
 * 
 * Suporta dois padrões de resposta:
 * 1. status/message/data - Padrão original
 * 2. data/errors/meta - Padrão usado nos controllers administrativos
 */
class ApiResponse
{
    /**
     * Resposta de sucesso (padrão status/message/data)
     */
    public static function success($data = null, string $message = 'Operação realizada com sucesso', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Resposta de criação (HTTP 201)
     */
    public static function created($data = null, string $message = 'Recurso criado com sucesso'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * Resposta de erro (padrão status/message/errors)
     */
    public static function error(string $message = 'Erro na operação', $errors = null, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    /**
     * Erro 404 - Recurso não encontrado
     */
    public static function notFound(string $message = 'Recurso não encontrado'): JsonResponse
    {
        return self::error($message, null, 404);
    }

    /**
     * Erro 401 - Não autenticado
     */
    public static function unauthorized(string $message = 'Não autenticado'): JsonResponse
    {
        return self::error($message, null, 401);
    }

    /**
     * Erro 403 - Acesso negado
     */
    public static function forbidden(string $message = 'Acesso negado'): JsonResponse
    {
        return self::error($message, null, 403);
    }

    /**
     * Erro 422 - Dados inválidos
     */
    public static function unprocessable($errors, string $message = 'Dados inválidos'): JsonResponse
    {
        return self::error($message, $errors, 422);
    }

    // ========================================================================
    // MÉTODOS PADRÃO DATA/ERRORS/META (usado nos controllers administrativos)
    // ========================================================================

    /**
     * Resposta padrão do sistema (formato data/errors/meta)
     * 
     * Este é o formato usado pela maioria dos controllers administrativos.
     * Mantém compatibilidade com o padrão existente do projeto.
     * 
     * @param mixed $data Dados da resposta
     * @param array $errors Erros (vazio se sucesso)
     * @param array $meta Metadados adicionais
     * @param int $statusCode Código HTTP
     * @return JsonResponse
     */
    public static function standardResponse(
        mixed $data = null,
        array $errors = [],
        array $meta = [],
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'data' => $data,
            'errors' => $errors,
            'meta' => $meta,
        ], $statusCode);
    }

    /**
     * Resposta padrão de sucesso (HTTP 200)
     */
    public static function standardSuccess($data = null, array $meta = []): JsonResponse
    {
        return self::standardResponse($data, [], $meta, 200);
    }

    /**
     * Resposta padrão de criação (HTTP 201)
     */
    public static function standardCreated($data = null, array $meta = []): JsonResponse
    {
        return self::standardResponse($data, [], $meta, 201);
    }

    /**
     * Resposta padrão de erro
     * 
     * @param string $errorKey Chave do erro (ex: 'user', 'validation', etc)
     * @param string|array $errorMessage Mensagem(ns) de erro
     * @param int $statusCode Código HTTP
     * @return JsonResponse
     */
    public static function standardError(
        string $errorKey,
        string|array $errorMessage,
        int $statusCode = 400
    ): JsonResponse {
        $errors = [
            $errorKey => is_array($errorMessage) ? $errorMessage : [$errorMessage]
        ];
        
        return self::standardResponse(null, $errors, [], $statusCode);
    }

    /**
     * Resposta padrão de erro 404
     */
    public static function standardNotFound(string $errorKey, string $message = 'Recurso não encontrado'): JsonResponse
    {
        return self::standardError($errorKey, $message, 404);
    }

    /**
     * Resposta padrão de validação (422)
     */
    public static function standardValidationError(array $errors): JsonResponse
    {
        return self::standardResponse(null, $errors, [], 422);
    }
}

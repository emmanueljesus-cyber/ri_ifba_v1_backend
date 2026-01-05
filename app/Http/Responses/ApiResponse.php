<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success($data = null, string $message = 'Operação realizada com sucesso', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public static function created($data = null, string $message = 'Recurso criado com sucesso'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    public static function error(string $message = 'Erro na operação', $errors = null, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    public static function notFound(string $message = 'Recurso não encontrado'): JsonResponse
    {
        return self::error($message, null, 404);
    }

    public static function unauthorized(string $message = 'Não autenticado'): JsonResponse
    {
        return self::error($message, null, 401);
    }

    public static function forbidden(string $message = 'Acesso negado'): JsonResponse
    {
        return self::error($message, null, 403);
    }

    public static function unprocessable($errors, string $message = 'Dados inválidos'): JsonResponse
    {
        return self::error($message, $errors, 422);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Responses\ApiResponse;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->desligado) {
            // Revoga o token atual para forçar logout
            $request->user()->currentAccessToken()->delete();
            
            return ApiResponse::standardError(
                'acesso_negado',
                'Sua conta está desativada/desligada do sistema. Entre em contato com a administração.',
                403
            );
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Enums\PerfilUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsBolsista
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Verifica se o usuário está autenticado
        if (!$user) {
            return response()->json([
                'message' => 'Não autenticado.'
            ], 401);
        }

        // Verifica se é estudante
        if ($user->perfil !== PerfilUsuario::ESTUDANTE && $user->perfil !== 'estudante') {
            return response()->json([
                'message' => 'Acesso negado. Apenas estudantes podem acessar este recurso.'
            ], 403);
        }

        // Verifica se é bolsista
        if (!$user->bolsista) {
            return response()->json([
                'message' => 'Acesso negado. Este recurso é exclusivo para estudantes bolsistas.'
            ], 403);
        }

        return $next($request);
    }
}


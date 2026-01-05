<?php

namespace App\Http\Middleware;

use App\Enums\PerfilUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se o usuário autenticado tem perfil de admin
        if (!$request->user() || !$this->isAdmin($request->user())) {
            return response()->json(['message' => 'Acesso negado. Usuário não possui permissão de administrador.'], 403);
        }

        return $next($request);
    }

    /**
     * Verifica se o usuário é administrador.
     */
    private function isAdmin($user): bool
    {
        // Suporta tanto enum quanto string
        return $user->perfil === PerfilUsuario::ADMIN || $user->perfil === 'admin';
    }
}

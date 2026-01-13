<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Controller de Autenticação
 * 
 * Gerencia login e logout via Laravel Sanctum
 */
class AuthController extends Controller
{
    /**
     * Login do usuário
     * POST /api/v1/login
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'matricula' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'matricula.required' => 'A matrícula é obrigatória',
            'password.required' => 'A senha é obrigatória',
        ]);

        $user = User::where('matricula', $request->matricula)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return ApiResponse::error(
                'Matrícula ou senha incorretos',
                ['matricula' => ['As credenciais fornecidas estão incorretas.']],
                401
            );
        }

        // Verifica se o usuário está desligado
        if ($user->desligado) {
            return ApiResponse::error(
                'Usuário desativado. Entre em contato com a administração.',
                null,
                403
            );
        }

        // Revoga tokens anteriores
        $user->tokens()->delete();

        // Cria novo token
        $token = $user->createToken('auth-token')->plainTextToken;

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'nome' => $user->nome,
                'email' => $user->email,
                'matricula' => $user->matricula,
                'perfil' => $user->perfil,
                'bolsista' => $user->bolsista,
                'curso' => $user->curso,
                'turno' => $user->turno,
                'foto' => $user->foto_url,
            ],
            'token' => $token,
        ], 'Login realizado com sucesso');
    }

    /**
     * Logout do usuário
     * POST /api/v1/logout
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoga o token atual
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logout realizado com sucesso');
    }

    /**
     * Retorna dados do usuário autenticado
     * GET /api/v1/me
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success([
            'id' => $user->id,
            'nome' => $user->nome,
            'email' => $user->email,
            'matricula' => $user->matricula,
            'perfil' => $user->perfil,
            'bolsista' => $user->bolsista,
            'curso' => $user->curso,
            'turno' => $user->turno,
            'foto' => $user->foto_url,
        ], 'Dados do usuário recuperados com sucesso');
    }

    /**
     * Cadastro de novo estudante
     * POST /api/v1/register
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'matricula' => ['required', 'string', 'max:20', 'unique:users,matricula'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'curso' => ['nullable', 'string', 'max:100'],
            'turno' => ['nullable', 'in:matutino,vespertino,noturno'],
        ], [
            'nome.required' => 'O nome é obrigatório',
            'email.required' => 'O e-mail é obrigatório',
            'email.email' => 'O e-mail deve ser válido',
            'email.unique' => 'Este e-mail já está cadastrado',
            'matricula.required' => 'A matrícula é obrigatória',
            'matricula.unique' => 'Esta matrícula já está cadastrada',
            'password.required' => 'A senha é obrigatória',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres',
            'password.confirmed' => 'As senhas não conferem',
        ]);

        $user = User::create([
            'nome' => $request->nome,
            'email' => $request->email,
            'matricula' => $request->matricula,
            'password' => Hash::make($request->password),
            'perfil' => 'estudante',
            'bolsista' => false,
            'curso' => $request->curso,
            'turno' => $request->turno,
        ]);

        // Cria token para login automático
        $token = $user->createToken('auth-token')->plainTextToken;

        return ApiResponse::created([
            'user' => [
                'id' => $user->id,
                'nome' => $user->nome,
                'email' => $user->email,
                'matricula' => $user->matricula,
                'perfil' => $user->perfil,
                'bolsista' => $user->bolsista,
                'curso' => $user->curso,
                'turno' => $user->turno,
            ],
            'token' => $token,
        ], 'Cadastro realizado com sucesso');
    }
}

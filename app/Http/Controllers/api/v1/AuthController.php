<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $credentials = [
            'matricula' => trim($request->matricula),
            'password' => $request->password,
        ];

        // Log temporário para depuração (Remover após resolver o problema)
        \Illuminate\Support\Facades\Log::info('Tentativa de login:', [
            'matricula_recebida' => $request->matricula,
            'matricula_trim' => $credentials['matricula']
        ]);

        if (!Auth::attempt($credentials)) {
            $userExists = User::where('matricula', $credentials['matricula'])->exists();
            \Illuminate\Support\Facades\Log::warning('Falha no login:', [
                'matricula' => $credentials['matricula'],
                'usuario_existe' => $userExists,
            ]);

            return ApiResponse::error(
                'Matrícula ou senha incorretos',
                ['matricula' => ['As credenciais fornecidas estão incorretas.']],
                401
            );
        }

        $user = Auth::user();

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
                'turno_refeicao' => $user->turno_refeicao,
                'turno_aula' => $user->turno_aula,
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
            'turno_refeicao' => $user->turno_refeicao,
            'turno_aula' => $user->turno_aula,
            'foto' => $user->foto_url,
        ], 'Dados do usuário recuperados com sucesso');
    }

    /**
     * Verifica se uma matrícula está na lista de bolsistas aprovados
     * GET /api/v1/verificar-matricula/{matricula}
     *
     * @param string $matricula
     * @return JsonResponse
     */
    public function verificarMatricula(string $matricula): JsonResponse
    {
        // Verificar se já existe usuário com essa matrícula
        $usuarioExistente = User::where('matricula', $matricula)->exists();

        if ($usuarioExistente) {
            return ApiResponse::error('Esta matricula ja esta cadastrada no sistema', 409);
        }

        // Verificar se está na lista de bolsistas aprovados
        $bolsista = \App\Models\Bolsista::where('matricula', $matricula)->first();

        if ($bolsista) {
            return ApiResponse::success([
                'bolsista' => true,
                'nome' => $bolsista->nome,
                'curso' => $bolsista->curso,
                'turno_refeicao' => $bolsista->turno_refeicao,
            ], 'Matricula encontrada na lista de bolsistas');
        }

        return ApiResponse::success([
            'bolsista' => false,
        ], 'Matricula nao encontrada na lista de bolsistas');
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
            // Turno: bolsista usa almoco/jantar, nao-bolsista usa matutino/vespertino/noturno
            'turno' => ['nullable', 'in:almoco,jantar,matutino,vespertino,noturno'],
        ], [
            'nome.required' => 'O nome e obrigatorio',
            'email.required' => 'O e-mail e obrigatorio',
            'email.email' => 'O e-mail deve ser valido',
            'email.unique' => 'Este e-mail ja esta cadastrado',
            'matricula.required' => 'A matricula e obrigatoria',
            'matricula.unique' => 'Esta matricula ja esta cadastrada',
            'password.required' => 'A senha e obrigatoria',
            'password.min' => 'A senha deve ter no minimo 6 caracteres',
            'password.confirmed' => 'As senhas nao conferem',
            'turno.in' => 'Turno invalido',
        ]);

        // Verificar se o estudante esta na lista de bolsistas aprovados
        $bolsistaAprovado = \App\Models\Bolsista::where('matricula', $request->matricula)->first();
        $ehBolsista = $bolsistaAprovado !== null;

        // Bolsista: usa turno da lista (almoco/jantar)
        // Nao-bolsista: usa turno de aula informado (matutino/vespertino/noturno)
        $turnoRefeicao = null;
        $turnoAula = null;

        if ($ehBolsista) {
            $turnoRefeicao = $bolsistaAprovado->turno_refeicao;
        } else {
            $turnoAula = $request->turno;
            // Sugestao de turno de refeicao para nao-bolsistas (fila extra)
            $turnoRefeicao = ($turnoAula === 'noturno') ? 'jantar' : 'almoco';
        }

        $user = User::create([
            'nome' => $request->nome,
            'email' => $request->email,
            'matricula' => $request->matricula,
            'password' => Hash::make($request->password),
            'perfil' => 'estudante',
            'bolsista' => $ehBolsista,
            'curso' => $request->curso ?? $bolsistaAprovado?->curso,
            'turno_refeicao' => $turnoRefeicao,
            'turno_aula' => $turnoAula,
        ]);

        // Se for bolsista, vincular com o registro na tabela bolsistas
        if ($bolsistaAprovado) {
            $bolsistaAprovado->update([
                'user_id' => $user->id,
                'vinculado_em' => now(),
            ]);
        }

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
                'turno_refeicao' => $user->turno_refeicao,
                'turno_aula' => $user->turno_aula,
            ],
            'token' => $token,
        ], 'Cadastro realizado com sucesso');
    }
}

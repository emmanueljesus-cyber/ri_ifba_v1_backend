<?php

namespace App\Http\Controllers\api\v1\Estudante;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\ImagemPerfilService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Controller para perfil do estudante (RF05)
 */
class PerfilController extends Controller
{
    public function __construct(
        private ImagemPerfilService $imagemService
    ) {}

    /**
     * RF05 - Exibe dados do perfil do estudante
     * GET /api/v1/estudante/perfil
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::standardSuccess([
            'id' => $user->id,
            'matricula' => $user->matricula,
            'nome' => $user->nome,
            'email' => $user->email,
            'curso' => $user->curso,
            'turno' => $user->turno,
            'bolsista' => $user->bolsista,
            'preferencia_alimentar' => $user->preferencia_alimentar ?? 'comum',
            'foto_url' => $user->foto_url,
            'perfil' => $user->perfil,
            'dias_cadastrados' => $user->getDiasCadastrados(),
        ]);
    }

    /**
     * RF05 - Atualiza preferência alimentar
     * PUT /api/v1/estudante/perfil/preferencia
     */
    public function atualizarPreferencia(Request $request): JsonResponse
    {
        $request->validate([
            'preferencia_alimentar' => 'required|in:comum,ovolactovegetariano',
        ]);

        $user = $request->user();
        $user->update([
            'preferencia_alimentar' => $request->input('preferencia_alimentar'),
        ]);

        return ApiResponse::standardSuccess(
            data: ['preferencia_alimentar' => $user->preferencia_alimentar],
            meta: ['mensagem' => 'Preferência atualizada. Entra em vigor no próximo dia útil.']
        );
    }

    /**
     * Atualiza dados básicos do perfil
     * PUT /api/v1/estudante/perfil
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'sometimes|email',
        ]);

        $user = $request->user();
        $user->update($request->only(['email']));

        return ApiResponse::standardSuccess(
            data: $user->only(['id', 'matricula', 'nome', 'email']),
            meta: ['atualizado' => true]
        );
    }

    /**
     * Atualiza foto de perfil do estudante
     * POST /api/v1/estudante/perfil/foto
     *
     * LGPD: Requer consentimento explícito do usuário
     */
    public function atualizarFoto(Request $request): JsonResponse
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'consentimento' => 'required|accepted', // LGPD: checkbox de consentimento
        ], [
            'consentimento.required' => 'Você deve aceitar os termos de uso da foto.',
            'consentimento.accepted' => 'Você deve autorizar o uso da foto para identificação.',
        ]);

        $user = $request->user();

        // Remove foto antiga se existir
        $this->imagemService->remover($user->foto_perfil);

        // Processa e salva nova foto (com redimensionamento automático)
        $path = $this->imagemService->processarEsalvar($request->file('foto'), $user->id);

        $user->update(['foto_perfil' => $path]);

        return ApiResponse::standardSuccess(
            data: ['foto_url' => $user->foto_url],
            meta: [
                'mensagem' => 'Foto atualizada com sucesso!',
                'lgpd' => 'Sua foto será usada exclusivamente para identificação no Refeitório Institucional.',
            ]
        );
    }

    /**
     * Remove foto de perfil do estudante
     * DELETE /api/v1/estudante/perfil/foto
     */
    public function removerFoto(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->foto_perfil) {
            return ApiResponse::standardError('foto', 'Nenhuma foto de perfil para remover.', 404);
        }

        // Remove arquivo do storage
        $this->imagemService->remover($user->foto_perfil);

        $user->update(['foto_perfil' => null]);

        return ApiResponse::standardSuccess(
            data: ['foto_url' => null],
            meta: ['mensagem' => 'Foto removida com sucesso!']
        );
    }

    /**
     * Atualiza os dias da semana que o estudante vai usar o refeitório
     * PUT /api/v1/estudante/perfil/dias-semana
     */
    public function atualizarDiasSemana(Request $request): JsonResponse
    {
        $request->validate([
            'dias' => 'required|array|min:1|max:5',
            'dias.*' => 'integer|between:1,5', // 1=Segunda até 5=Sexta
        ], [
            'dias.required' => 'Selecione ao menos um dia da semana.',
            'dias.min' => 'Selecione ao menos um dia da semana.',
            'dias.max' => 'Máximo de 5 dias permitidos.',
            'dias.*.between' => 'Dias devem ser de Segunda (1) a Sexta (5).',
        ]);

        $user = $request->user();

        // Verifica se é bolsista
        if (!$user->bolsista) {
            return ApiResponse::standardError('dias', 'Apenas bolsistas podem selecionar dias de uso.', 403);
        }

        // Remove dias antigos
        $user->diasSemana()->delete();

        // Insere novos dias
        $diasParaInserir = collect($request->input('dias'))->unique()->map(function ($dia) use ($user) {
            return ['user_id' => $user->id, 'dia_semana' => $dia];
        })->toArray();

        $user->diasSemana()->insert($diasParaInserir);

        // Nomes dos dias para resposta
        $nomesDias = [
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
        ];

        $diasSelecionados = collect($request->input('dias'))
            ->unique()
            ->sort()
            ->map(fn($d) => $nomesDias[$d])
            ->values();

        return ApiResponse::standardSuccess(
            data: [
                'dias_numeros' => $user->getDiasCadastrados(),
                'dias_nomes' => $diasSelecionados,
            ],
            meta: ['mensagem' => 'Dias de uso atualizados com sucesso!']
        );
    }

    /**
     * Altera a senha do usuário
     * PUT /api/v1/estudante/perfil/senha
     */
    public function alterarSenha(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'confirmed', Password::min(6)],
        ], [
            'current_password.required' => 'A senha atual é obrigatória.',
            'password.required' => 'A nova senha é obrigatória.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres.',
        ]);

        $user = $request->user();

        if (!$user) {
            return ApiResponse::standardError('user', 'Usuário não encontrado.', 401);
        }

        // Verifica se a senha atual está correta
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return ApiResponse::standardError('current_password', 'A senha atual está incorreta.', 422);
        }

        // Atualiza a senha
        $user->password = Hash::make($request->input('password'));
        $user->save();

        return ApiResponse::standardSuccess(
            data: [],
            meta: ['mensagem' => 'Senha alterada com sucesso!']
        );
    }
}

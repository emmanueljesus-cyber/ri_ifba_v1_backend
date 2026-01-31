<?php

namespace App\Http\Controllers\api\v1\Estudante;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\ImagemPerfilService;
use App\Models\SolicitacaoMudancaDia;
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
     * Helper: Obtém usuário autenticado ou fallback para debug
     */
    private function getUser(Request $request)
    {
        // Sempre retorna o usuário autenticado via Sanctum
        return $request->user();
    }

    /**
     * RF05 - Exibe dados do perfil do estudante
     * GET /api/v1/estudante/perfil
     */
    public function show(Request $request): JsonResponse
    {
        $user = $this->getUser($request);

        if (!$user) {
            return ApiResponse::error(
                config('app.debug')
                    ? 'Nenhum estudante não-bolsista encontrado. Execute as seeders.'
                    : 'Não autenticado',
                config('app.debug') ? 404 : 401
            );
        }

        return ApiResponse::standardSuccess([
            'id' => $user->id,
            'matricula' => $user->matricula,
            'nome' => $user->nome,
            'email' => $user->email,
            'curso' => $user->curso,
            'turno_refeicao' => $user->turno_refeicao,
            'turno_aula' => $user->turno_aula,
            'bolsista' => $user->bolsista,
            'preferencia_alimentar' => $user->preferencia_alimentar ?? 'comum',
            'restricoes_alimentares' => $user->restricoes_alimentares ?? [],
            'alergias' => $user->alergias,
            'foto_url' => $user->foto_url,
            'perfil' => $user->perfil,
            'dias_cadastrados' => $user->getDiasCadastrados(),
        ]);
    }

    /**
     * Carteirinha digital do estudante (QR fixo)
     * GET /api/v1/estudante/carteirinha
     */
    public function carteirinha(Request $request): JsonResponse
    {
        $user = $this->getUser($request);

        if (!$user) {
            return ApiResponse::error('N\u00e3o autenticado', null, 401);
        }

        // QR fixo baseado na matr\u00edcula para valida\u00e7\u00e3o r\u00e1pida no balc\u00e3o
        $qrToken = 'IFBA-' . $user->matricula;

        return ApiResponse::standardSuccess([
            'id' => $user->id,
            'nome' => $user->nome,
            'matricula' => $user->matricula,
            'curso' => $user->curso,
            'turno_refeicao' => $user->turno_refeicao,
            'qr_token' => $qrToken,
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

        $user = $this->getUser($request);
        if (!$user) {
            return ApiResponse::error('Não autenticado', 401);
        }

        $user->update([
            'preferencia_alimentar' => $request->input('preferencia_alimentar'),
        ]);

        return ApiResponse::standardSuccess(
            data: ['preferencia_alimentar' => $user->preferencia_alimentar],
            meta: ['mensagem' => 'Preferência atualizada. Entra em vigor no próximo dia útil.']
        );
    }

    /**
     * RF05 - Atualiza restrições e preferências alimentares (apenas bolsistas)
     * PUT /api/v1/estudante/perfil/restricoes-alimentares
     */
    public function atualizarRestricoesAlimentares(Request $request): JsonResponse
    {
        $request->validate([
            'preferencia_alimentar' => 'sometimes|in:comum,ovolactovegetariano',
            'restricoes_alimentares' => 'sometimes|array',
            'restricoes_alimentares.*' => 'string|max:255',
            'alergias' => 'sometimes|nullable|string|max:1000',
        ]);

        $user = $this->getUser($request);
        if (!$user) {
            return ApiResponse::error('Não autenticado', 401);
        }

        // Apenas bolsistas podem atualizar restrições alimentares
        if (!$user->bolsista) {
            return ApiResponse::standardError(
                'restricoes',
                'Apenas bolsistas podem atualizar restrições alimentares.',
                403
            );
        }

        $updatedData = [];
        
        if ($request->has('preferencia_alimentar')) {
            $updatedData['preferencia_alimentar'] = $request->input('preferencia_alimentar');
        }

        if ($request->has('restricoes_alimentares')) {
            $updatedData['restricoes_alimentares'] = $request->input('restricoes_alimentares');
        }

        if ($request->has('alergias')) {
            $updatedData['alergias'] = $request->input('alergias');
        }

        $user->update($updatedData);

        return ApiResponse::standardSuccess(
            data: [
                'preferencia_alimentar' => $user->preferencia_alimentar,
                'restricoes_alimentares' => $user->restricoes_alimentares ?? [],
                'alergias' => $user->alergias,
            ],
            meta: ['mensagem' => 'Preferências alimentares atualizadas com sucesso!']
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

        $user = $this->getUser($request);
        if (!$user) {
            return ApiResponse::error('Não autenticado', 401);
        }

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

        $user = $this->getUser($request);
        if (!$user) {
            return ApiResponse::error('Não autenticado', 401);
        }

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
        $user = $this->getUser($request);
        if (!$user) {
            return ApiResponse::error('Não autenticado', 401);
        }

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
     *
     * Cria uma solicitação pendente para aprovação do admin
     */
    public function atualizarDiasSemana(Request $request): JsonResponse
    {
        $request->validate([
            'dias' => 'required|array|min:1|max:5',
            'dias.*' => 'integer|between:1,5', // 1=Segunda até 5=Sexta
            'motivo' => 'required|string|min:5|max:500',
        ], [
            'dias.required' => 'Selecione ao menos um dia da semana.',
            'dias.min' => 'Selecione ao menos um dia da semana.',
            'dias.max' => 'Máximo de 5 dias permitidos.',
            'dias.*.between' => 'Dias devem ser de Segunda (1) a Sexta (5).',
            'motivo.required' => 'Informe o motivo da solicitação.',
            'motivo.min' => 'O motivo deve ter pelo menos 5 caracteres.',
        ]);

        $user = $this->getUser($request);
        if (!$user) {
            return ApiResponse::error('Não autenticado', 401);
        }

        // Verifica se é bolsista
        if (!$user->bolsista) {
            return ApiResponse::standardError('dias', 'Apenas bolsistas podem selecionar dias de uso.', 403);
        }

        // Verifica se já tem solicitação pendente
        $solicitacaoPendente = SolicitacaoMudancaDia::where('user_id', $user->id)
            ->where('status', 'pendente')
            ->exists();

        if ($solicitacaoPendente) {
            return ApiResponse::standardError(
                'solicitacao',
                'Você já possui uma solicitação pendente. Aguarde a avaliação do administrador.',
                400
            );
        }

        // Pegar dias atuais
        $diasAtuais = $user->diasSemana()->pluck('dia_semana')->toArray();

        // Criar solicitação
        $solicitacao = SolicitacaoMudancaDia::create([
            'user_id' => $user->id,
            'dias_atuais' => $diasAtuais,
            'dias_solicitados' => collect($request->input('dias'))->unique()->sort()->values()->toArray(),
            'motivo' => $request->input('motivo'),
            'status' => 'pendente',
        ]);

        // Nomes dos dias para resposta
        $nomesDias = [
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
        ];

        $diasSolicitados = collect($request->input('dias'))
            ->unique()
            ->sort()
            ->map(fn($d) => $nomesDias[$d])
            ->values();

        return ApiResponse::standardSuccess(
            data: [
                'solicitacao_id' => $solicitacao->id,
                'dias_solicitados' => $diasSolicitados,
                'status' => 'pendente',
            ],
            meta: ['mensagem' => 'Solicitação enviada com sucesso! Aguarde a aprovação do administrador.']
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

        $user = $this->getUser($request);

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

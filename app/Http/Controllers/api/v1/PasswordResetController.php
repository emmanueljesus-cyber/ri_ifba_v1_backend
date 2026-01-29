<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Controller para Redefinição de Senha
 *
 * Gerencia o fluxo de "Esqueci minha senha"
 */
class PasswordResetController extends Controller
{
    /**
     * Solicitar redefinição de senha
     * POST /api/v1/auth/forgot-password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'O e-mail é obrigatório',
            'email.email' => 'Informe um e-mail válido',
        ]);

        $user = User::where('email', $request->email)->first();

        // Por segurança, sempre retornamos sucesso mesmo se o email não existir
        if (!$user) {
            return ApiResponse::success(null, 'Se o e-mail estiver cadastrado, você receberá as instruções de redefinição.');
        }

        // Gerar token único
        $token = Str::random(64);

        // Remover tokens antigos do mesmo email
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        // Criar novo token
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now(),
        ]);

        // Enviar email com o token
        try {
            Mail::send('emails.password-reset', [
                'user' => $user,
                'token' => $token,
                'resetUrl' => config('app.frontend_url', 'http://localhost:5173') . '/reset-password?token=' . $token . '&email=' . urlencode($request->email),
            ], function ($message) use ($user) {
                $message->to($user->email, $user->nome)
                    ->subject('Redefinição de Senha - RI IFBA');
            });
        } catch (\Exception $e) {
            \Log::error('Erro ao enviar email de redefinição: ' . $e->getMessage());
            // Não retornamos erro para não expor informações
        }

        return ApiResponse::success(null, 'Se o e-mail estiver cadastrado, você receberá as instruções de redefinição.');
    }

    /**
     * Verificar se o token é válido
     * POST /api/v1/auth/verify-reset-token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyToken(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return ApiResponse::error('Token inválido ou expirado.', null, 400);
        }

        // Verificar se o token corresponde
        if (!Hash::check($request->token, $record->token)) {
            return ApiResponse::error('Token inválido ou expirado.', null, 400);
        }

        // Verificar se não expirou (1 hora)
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addHours(1)->isPast()) {
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();
            return ApiResponse::error('Token expirado. Solicite uma nova redefinição.', null, 400);
        }

        return ApiResponse::success(['valid' => true], 'Token válido.');
    }

    /**
     * Redefinir senha
     * POST /api/v1/auth/reset-password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'email.required' => 'O e-mail é obrigatório',
            'token.required' => 'O token é obrigatório',
            'password.required' => 'A nova senha é obrigatória',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres',
            'password.confirmed' => 'As senhas não conferem',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return ApiResponse::error('Token inválido ou expirado.', null, 400);
        }

        // Verificar se o token corresponde
        if (!Hash::check($request->token, $record->token)) {
            return ApiResponse::error('Token inválido ou expirado.', null, 400);
        }

        // Verificar se não expirou (1 hora)
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addHours(1)->isPast()) {
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();
            return ApiResponse::error('Token expirado. Solicite uma nova redefinição.', null, 400);
        }

        // Buscar usuário
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return ApiResponse::error('Usuário não encontrado.', null, 404);
        }

        // Atualizar senha
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Revogar todos os tokens de acesso do usuário
        $user->tokens()->delete();

        // Remover o token de reset
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return ApiResponse::success(null, 'Senha redefinida com sucesso. Você já pode fazer login.');
    }
}

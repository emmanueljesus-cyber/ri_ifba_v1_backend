<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autorização feita no middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = $this->route('usuario'); // pega o ID da rota

        return [
            'nome' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'matricula' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('users', 'matricula')->ignore($userId)
            ],
            'password' => ['sometimes', 'string', 'min:6'],
            'perfil' => ['sometimes', Rule::in(['admin', 'estudante'])],
            'bolsista' => ['sometimes', 'boolean'],
            'limite_faltas_mes' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'desligado' => ['sometimes', 'boolean'],
            'curso' => ['nullable', 'string', 'max:100'],
            'turno' => ['nullable', Rule::in(['matutino', 'vespertino', 'noturno'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nome.string' => 'O nome deve ser um texto',
            'email.email' => 'O email deve ser válido',
            'email.unique' => 'Este email já está cadastrado',
            'matricula.unique' => 'Esta matrícula já está cadastrada',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres',
            'perfil.in' => 'O perfil deve ser admin ou estudante',
            'turno.in' => 'O turno deve ser matutino, vespertino ou noturno',
        ];
    }
}


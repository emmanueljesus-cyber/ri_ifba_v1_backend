<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'matricula' => ['required', 'string', 'max:20', 'unique:users,matricula'],
            'password' => ['required', 'string', 'min:6'],
            'perfil' => ['required', Rule::in(['admin', 'estudante'])],
            'bolsista' => ['boolean'],
            'limite_faltas_mes' => ['integer', 'min:0', 'max:10'],
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
            'nome.required' => 'O nome é obrigatório',
            'email.required' => 'O email é obrigatório',
            'email.email' => 'O email deve ser válido',
            'email.unique' => 'Este email já está cadastrado',
            'matricula.required' => 'A matrícula é obrigatória',
            'matricula.unique' => 'Esta matrícula já está cadastrada',
            'password.required' => 'A senha é obrigatória',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres',
            'perfil.required' => 'O perfil é obrigatório',
            'perfil.in' => 'O perfil deve ser admin ou estudante',
            'turno.in' => 'O turno deve ser matutino, vespertino ou noturno',
        ];
    }
}


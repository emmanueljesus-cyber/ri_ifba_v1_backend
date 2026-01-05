<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PresencaValidarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->perfil->value === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'matricula' => 'required_without:presenca_id|string|exists:users,matricula',
            'presenca_id' => 'required_without:matricula|exists:presencas,id',
            'turno' => 'required_with:matricula|in:almoco,jantar',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'matricula.required_without' => 'É necessário informar a matrícula ou o ID da presença.',
            'matricula.exists' => 'Matrícula não encontrada no sistema.',
            'presenca_id.required_without' => 'É necessário informar o ID da presença ou a matrícula.',
            'presenca_id.exists' => 'Presença não encontrada.',
            'turno.required_with' => 'É necessário informar o turno ao validar por matrícula.',
            'turno.in' => 'Turno inválido. Use "almoco" ou "jantar".',
        ];
    }
}


<?php

namespace App\Http\Requests\Admin;

use App\Enums\TurnoRefeicao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BolsistaImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxSize = config('import.max_file_size', 5120);
        $mimes = implode(',', config('import.allowed_mimes', ['xlsx', 'xls', 'csv']));

        return [
            'file' => ['required', 'file', "mimes:{$mimes}", "max:{$maxSize}"],
            'turno_padrao' => ['nullable', Rule::enum(TurnoRefeicao::class)],
            'atualizar_existentes' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'O arquivo é obrigatório.',
            'file.file' => 'O campo deve ser um arquivo válido.',
            'file.mimes' => 'O arquivo deve ser do tipo: xlsx, xls ou csv.',
            'file.max' => 'O arquivo não pode ter mais de 5MB.',
            'turno_padrao.enum' => 'Turno padrão deve ser: almoco ou jantar.',
        ];
    }
}

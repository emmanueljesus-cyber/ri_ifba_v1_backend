<?php

namespace App\Http\Requests\Admin;

use App\Enums\TurnoRefeicao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CardapioImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // protegido pelo middleware do grupo admin
    }

    public function rules(): array
    {
        $maxSize = config('import.max_file_size', 5120);
        $mimes = implode(',', config('import.allowed_mimes', ['xlsx', 'xls']));

        return [
            'file' => ['required', 'file', "mimes:{$mimes}", "max:{$maxSize}"],
            'turno' => ['nullable', 'array'],
            'turno.*' => [Rule::enum(TurnoRefeicao::class)],
            'debug' => ['sometimes'],
        ];
    }

    public function messages(): array
    {
        $maxSize = config('import.max_file_size', 5120);
        $allowedMimes = implode(', ', config('import.allowed_mimes', ['xlsx', 'xls']));

        return [
            'file.required' => 'O arquivo é obrigatório.',
            'file.file' => 'O campo deve ser um arquivo válido.',
            'file.uploaded' => 'Ocorreu um erro ao enviar/importar o arquivo. Verifique e tente novamente.',
            'file.mimes' => "O arquivo deve ser do tipo: {$allowedMimes}.",
            'file.max' => "O arquivo não pode exceder {$maxSize}KB.",
            'turno.*.enum' => 'O turno deve ser: almoco ou jantar.',
        ];
    }
}

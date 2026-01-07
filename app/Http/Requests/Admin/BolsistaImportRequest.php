<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BolsistaImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // protegido pelo middleware do grupo admin
    }

    public function rules(): array
    {
        $maxSize = config('import.max_file_size', 5120);
        $mimes = implode(',', config('import.allowed_mimes', ['xlsx', 'xls', 'csv']));

        return [
            'file' => ['required', 'file', "mimes:{$mimes}", "max:{$maxSize}"],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'O arquivo é obrigatório.',
            'file.file' => 'O campo deve ser um arquivo válido.',
            'file.mimes' => 'O arquivo deve ser do tipo: xlsx, xls ou csv.',
            'file.max' => 'O arquivo não pode exceder ' . config('import.max_file_size', 5120) . 'KB.',
        ];
    }
}


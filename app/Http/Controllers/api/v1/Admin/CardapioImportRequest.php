<?php

namespace App\Http\Controllers\api\v1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CardapioImportRequest extends FormRequest
{
    public function authorize() { return $this->user()?->can('create', \App\Models\Cardapio::class); }

    public function rules()
    {
        return [
            'file' => ['required','file','mimes:xlsx,xls,csv','max:10240'],
        ];
    }
}

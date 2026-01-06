<?php

namespace App\Http\Requests\Admin;

use App\Enums\TurnoRefeicao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CardapioUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('cardapio');

        return [
            'data_do_cardapio'      => ['sometimes','date', Rule::unique('cardapios', 'data_do_cardapio')->ignore($id)],
            'turnos'                => ['sometimes','array','min:1'],
            'turnos.*'              => ['required', 'string', 'filled', Rule::enum(TurnoRefeicao::class)],
            'prato_principal_ptn01' => ['sometimes','string','max:255'],
            'prato_principal_ptn02' => ['sometimes','string','max:255'],
            'guarnicao'             => ['sometimes','nullable','string','max:255'],
            'acompanhamento_01'     => ['sometimes','string','max:255'],
            'acompanhamento_02'     => ['sometimes','string','max:255'],
            'salada'                => ['sometimes','nullable','string','max:255'],
            'ovo_lacto_vegetariano' => ['sometimes','nullable','string','max:255'],
            'suco'                  => ['sometimes','nullable','string','max:100'],
            'sobremesa'             => ['sometimes','nullable','string','max:100'],
        ];
    }
}

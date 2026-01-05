<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\TurnoRefeicao;

class CardapioStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // já proteja por middleware; aqui deixa true
        return true;
    }

    public function rules(): array
    {
        return [
            'data_do_cardapio'      => ['required','date','unique:cardapios,data_do_cardapio'],
            'turnos'                => ['nullable','array','min:1'],
            'turnos.*'              => ['required', 'string', 'filled', Rule::enum(TurnoRefeicao::class)],
            'prato_principal_ptn01' => ['required','string','max:255'],
            'prato_principal_ptn02' => ['required','string','max:255'],
            'guarnicao'             => ['nullable','string','max:255'],
            'acompanhamento_01'     => ['required','string','max:255'],
            'acompanhamento_02'     => ['required','string','max:255'],
            'salada'                => ['nullable','string','max:255'],
            'ovo_lacto_vegetariano' => ['nullable','string','max:255'],
            'suco'                  => ['nullable','string','max:100'],
            'sobremesa'             => ['nullable','string','max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'data_do_cardapio' => 'data do cardápio',
            'turnos' => 'turnos',
        ];
    }

    /**
     * Prepara os dados para validação
     * Se turnos não for informado, define padrão: ['almoco', 'jantar']
     */
    protected function prepareForValidation()
    {
        if (!$this->has('turnos') || empty($this->turnos)) {
            $this->merge([
                'turnos' => config('refeicoes.turnos_padrao', ['almoco', 'jantar']),
            ]);
        }
    }
}

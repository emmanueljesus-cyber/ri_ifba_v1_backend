<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CardapioTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    /**
     * Retorna dados de exemplo para o template
     */
    public function array(): array
    {
        return [
            [
                now()->format('d/m/Y'),
                'Frango Grelhado',
                'Omelete de Legumes',
                'Farofa de Ovos',
                'Arroz Branco',
                'Feijão Carioca',
                'Mix de Folhas',
                'Omelete',
                'Suco de Acerola',
                'Maçã'
            ],
            [
                now()->addDay()->format('d/m/Y'),
                'Bife Acebolado',
                '',
                'Macarrão Alho e Óleo',
                'Arroz com Cenoura',
                'Feijão Preto',
                'Salada de Tomate',
                'Grão de Bico',
                'Suco de Caju',
                'Gelatina'
            ],
        ];
    }

    /**
     * Define os cabeçalhos do template
     */
    public function headings(): array
    {
        return [
            'Data (DD/MM/AAAA) *',
            'Prato Principal 01 *',
            'Prato Principal 02',
            'Guarnição',
            'Acompanhamento 01 *',
            'Acompanhamento 02 *',
            'Salada',
            'Ovo Lacto Veg.',
            'Suco',
            'Sobremesa',
        ];
    }

    /**
     * Aplica estilos ao template
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Estilo do cabeçalho
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '27AE60'], // Verde IFBA-ish
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
            // Linhas de exemplo
            2 => ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBFAEF']]],
            3 => ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBFAEF']]],
        ];
    }
}

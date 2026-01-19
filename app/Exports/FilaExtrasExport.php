<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export para relatório de Fila de Extras
 */
class FilaExtrasExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $dados;

    public function __construct(array $dados)
    {
        $this->dados = $dados;
    }

    public function array(): array
    {
        return $this->dados;
    }

    public function headings(): array
    {
        return [
            'Nome',
            'Matrícula',
            'E-mail',
            'Curso',
            'Data',
            'Turno',
            'Status',
            'Inscrito em',
        ];
    }

    public function title(): string
    {
        return 'Fila de Extras';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Estilizar cabeçalho
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669'], // Emerald-600
                ],
            ],
        ];
    }
}

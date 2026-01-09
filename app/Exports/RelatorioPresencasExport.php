<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RelatorioPresencasExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected Collection $dados;

    public function __construct(Collection $dados)
    {
        $this->dados = $dados;
    }

    public function collection(): Collection
    {
        return $this->dados;
    }

    public function headings(): array
    {
        return [
            'Data',
            'Turno',
            'Matrícula',
            'Nome',
            'Curso',
            'Status',
            'Validado em',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
    }
}

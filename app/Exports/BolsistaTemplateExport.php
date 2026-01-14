<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BolsistaTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    /**
     * Retorna dados de exemplo para o template
     */
    public function array(): array
    {
        return [
            ['20231234567', 'João da Silva', 'joao.silva@aluno.ifba.edu.br', 'Informática', 'almoco', '1,2,3,4,5'],
            ['20231234568', 'Maria Santos', 'maria.santos@aluno.ifba.edu.br', 'Administração', 'jantar', '1,2,3,4,5'],
        ];
    }

    /**
     * Define os cabeçalhos do template
     */
    public function headings(): array
    {
        return [
            'Matrícula *',
            'Nome Completo',
            'Email',
            'Curso',
            'Turno (almoco/jantar) *',
            'Dias da Semana (1=seg, 2=ter, ...)',
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
                    'startColor' => ['rgb' => '4A90E2'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
            // Linhas de exemplo
            2 => ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4F8']]],
            3 => ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4F8']]],
        ];
    }
}

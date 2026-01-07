<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BolsistaTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            ['20231001', 'João Silva Santos', 'joao.silva@example.com', 'matutino', 'Técnico em Informática'],
            ['20231002', 'Maria Oliveira Costa', 'maria.oliveira@example.com', 'vespertino', 'Técnico em Edificações'],
            ['20231003', 'Pedro Santos Lima', 'pedro.santos@example.com', 'noturno', 'Técnico em Mecânica'],
        ];
    }

    public function headings(): array
    {
        return [
            'matricula',
            'nome',
            'email',
            'turno',
            'curso'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}


<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TemplateExportService
{
    /**
     * Gera e retorna o download do template de cardápios
     */
    public function downloadCardapioTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // 1. Headers
        $headers = [
            'Data (DD/MM/AAAA) *', 'Prato Principal 01 *', 'Prato Principal 02',
            'Guarnição', 'Acompanhamento 01 *', 'Acompanhamento 02 *',
            'Salada', 'Ovo Lacto Veg.', 'Suco', 'Sobremesa'
        ];
        
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
        
        // 2. Dados de exemplo
        $data = [
            [
                now()->format('d/m/Y'), 'Frango Grelhado', 'Omelete de Legumes', 'Farofa de Ovos',
                'Arroz Branco', 'Feijão Carioca', 'Mix de Folhas', 'Omelete', 'Suco de Acerola', 'Maçã'
            ],
            [
                now()->addDay()->format('d/m/Y'), 'Bife Acebolado', '', 'Macarrão Alho e Óleo',
                'Arroz com Cenoura', 'Feijão Preto', 'Salada de Tomate', 'Grão de Bico', 'Suco de Caju', 'Gelatina'
            ]
        ];
        
        $row = 2;
        foreach ($data as $rowData) {
            $col = 'A';
            foreach ($rowData as $cellData) {
                $sheet->setCellValue($col . $row, $cellData);
                $col++;
            }
            $row++;
        }
        
        // 3. Estilos
        // Cabeçalho (Linha 1) - Verde
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '27AE60'], // Verde IFBA
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
        
        // Linhas de dados (2 e 3) - Verde claro
        $rowStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EBFAEF']
            ]
        ];
        $sheet->getStyle('A2:J3')->applyFromArray($rowStyle);
        
        // 4. Download
        $filename = 'template_cardapios_' . now()->format('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Gera e retorna o download do template de bolsistas
     */
    public function downloadBolsistaTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // 1. Headers
        $headers = [
            'Matrícula *', 'Nome Completo', 'Email', 'Curso',
            'Turno (almoco/jantar) *', 'Dias da Semana (1=seg, 2=ter, ...)'
        ];
        
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
        
        // 2. Dados de exemplo
        $data = [
            ['20231234567', 'João da Silva', 'joao.silva@aluno.ifba.edu.br', 'Informática', 'almoco', '1,2,3,4,5'],
            ['20231234568', 'Maria Santos', 'maria.santos@aluno.ifba.edu.br', 'Administração', 'jantar', '1,2,3,4,5'],
        ];
        
        $row = 2;
        foreach ($data as $rowData) {
            $col = 'A';
            foreach ($rowData as $cellData) {
                // Forçar matrícula como texto para não perder zeros à esquerda
                if ($col === 'A') {
                    $sheet->setCellValueExplicit($col . $row, $cellData, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($col . $row, $cellData);
                }
                $col++;
            }
            $row++;
        }
        
        // 3. Estilos
        // Cabeçalho (Linha 1) - Azul
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4A90E2'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        
        // Linhas de dados (2 e 3) - Azul claro
        $rowStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F4F8']
            ]
        ];
        $sheet->getStyle('A2:F3')->applyFromArray($rowStyle);
        
        // 4. Download
        $filename = 'template_bolsistas_' . now()->format('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

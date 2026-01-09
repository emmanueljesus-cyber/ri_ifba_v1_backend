<?php

namespace App\Exports;

use App\Services\RelatorioSemanalService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class RelatorioMensalSemanalExport implements FromCollection, WithTitle, WithStyles, ShouldAutoSize
{
    protected int $mes;
    protected int $ano;
    protected array $relatorio;

    public function __construct(int $mes, int $ano)
    {
        $this->mes = $mes;
        $this->ano = $ano;
        
        $service = new RelatorioSemanalService();
        $this->relatorio = $service->gerarRelatorioMensal($mes, $ano);
    }

    public function collection(): Collection
    {
        $linhas = collect();

        // Linha vazia + título do mês
        $numSemanas = count($this->relatorio['semanas']);
        
        // Cabeçalho do mês (mesclado)
        $cabecalhoMes = [''];
        for ($i = 0; $i < $numSemanas; $i++) {
            $cabecalhoMes[] = $i === 0 ? $this->relatorio['mes_ano'] : '';
        }
        $cabecalhoMes[] = '';
        $cabecalhoMes[] = 'Total';
        $linhas->push($cabecalhoMes);

        // Linha de semanas
        $linhaSemanas = [''];
        foreach ($this->relatorio['semanas'] as $semana) {
            $linhaSemanas[] = $semana['label'];
        }
        $linhaSemanas[] = '';
        $linhaSemanas[] = 'Mês';
        $linhas->push($linhaSemanas);

        // Dados por categoria
        $categorias = [
            'presente' => 'Presente',
            'extra' => 'Extra',
            'ausente' => 'Ausente',
            'atestado' => 'Atestado',
            'justificado' => 'Justificado',
            'n_frequenta' => 'Ñ Frequenta',
        ];

        foreach ($categorias as $key => $label) {
            $linha = [$label];
            foreach ($this->relatorio['dados'][$key] as $valor) {
                $linha[] = $valor['tipo'] === 'texto' ? $valor['valor'] : $valor['valor'];
            }
            $linha[] = ''; // coluna vazia
            $linha[] = $this->relatorio['totais'][$key];
            $linhas->push($linha);
        }

        // Total mensal
        $linhaTotal = ['Total Mensal de Refeições'];
        for ($i = 0; $i < $numSemanas; $i++) {
            $linhaTotal[] = '-----';
        }
        $linhaTotal[] = '';
        $linhaTotal[] = $this->relatorio['total_mensal_refeicoes'];
        $linhas->push($linhaTotal);

        return $linhas;
    }

    public function title(): string
    {
        $meses = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];
        return $meses[$this->mes] . ' ' . $this->ano;
    }

    public function styles(Worksheet $sheet): array
    {
        $numSemanas = count($this->relatorio['semanas']);
        $ultimaColuna = chr(ord('A') + $numSemanas + 2); // +2 para colunas extras
        
        return [
            // Título do mês
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFEB9C'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Cabeçalho semanas
            2 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'BDD7EE'],
                ],
            ],
            // Presente (verde)
            3 => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'C6EFCE'],
                ],
            ],
            // Extra (amarelo claro)
            4 => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFEB9C'],
                ],
            ],
            // Ausente (vermelho claro)
            5 => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFC7CE'],
                ],
            ],
            // Atestado
            6 => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DDEBF7'],
                ],
            ],
            // Justificado (laranja)
            7 => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FCE4D6'],
                ],
            ],
            // N Frequenta (cinza)
            8 => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EDEDED'],
                ],
            ],
            // Total
            9 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9E1F2'],
                ],
            ],
        ];
    }
}

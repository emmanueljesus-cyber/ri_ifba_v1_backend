<?php

namespace Database\Seeders;

use App\Models\Cardapio;
use App\Models\Refeicao;
use App\Enums\TurnoRefeicao;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CardapioMensalSeeder extends Seeder
{
    /**
     * Cria cardápios para o mês inteiro (dias úteis)
     */
    public function run(): void
    {
        $inicio = now()->startOfMonth();
        $fim = now()->endOfMonth();

        $cardapiosCriados = 0;
        $refeicoesCriadas = 0;

        for ($data = $inicio->copy(); $data->lte($fim); $data->addDay()) {
            // Pular finais de semana (sábado = 6, domingo = 0)
            if ($data->dayOfWeek === Carbon::SATURDAY || $data->dayOfWeek === Carbon::SUNDAY) {
                continue;
            }

            // Verificar se já existe cardápio para este dia
            $cardapioExistente = Cardapio::where('data_do_cardapio', $data->format('Y-m-d'))->first();

            if ($cardapioExistente) {
                $this->command->info("   ⏭️  Cardápio já existe para {$data->format('d/m/Y')}");
                continue;
            }

            // Criar cardápio
            $cardapio = Cardapio::create([
                'data_do_cardapio' => $data->format('Y-m-d'),
                'prato_principal_ptn01' => $this->getPratoPrincipal($data->dayOfWeek),
                'prato_principal_ptn02' => $this->getPratoVegetariano($data->dayOfWeek),
                'guarnicao' => $this->getGuarnicao($data->dayOfWeek),
                'acompanhamento_01' => 'Arroz Branco',
                'acompanhamento_02' => 'Feijão Carioca',
                'salada' => 'Salada Verde com Tomate',
                'ovo_lacto_vegetariano' => $this->getPratoVegetariano($data->dayOfWeek),
                'suco' => $this->getSuco($data->dayOfWeek),
                'sobremesa' => $this->getSobremesa($data->dayOfWeek),
            ]);

            $cardapiosCriados++;

            // Criar refeições (almoço e jantar)
            Refeicao::create([
                'cardapio_id' => $cardapio->id,
                'data_do_cardapio' => $data->format('Y-m-d'),
                'turno' => TurnoRefeicao::ALMOCO,
                'capacidade' => 100,
            ]);

            Refeicao::create([
                'cardapio_id' => $cardapio->id,
                'data_do_cardapio' => $data->format('Y-m-d'),
                'turno' => TurnoRefeicao::JANTAR,
                'capacidade' => 80,
            ]);

            $refeicoesCriadas += 2;

            $this->command->info("   ✅ Criado: {$data->format('d/m/Y')} - {$cardapio->prato_principal_ptn01}");
        }

        $this->command->info('');
        $this->command->info('✅ Cardápios mensais criados com sucesso!');
        $this->command->info("📊 Resumo:");
        $this->command->info("   - {$cardapiosCriados} Cardápios criados");
        $this->command->info("   - {$refeicoesCriadas} Refeições criadas (almoço e jantar)");
        $this->command->info("   - Período: {$inicio->format('d/m/Y')} a {$fim->format('d/m/Y')}");
        $this->command->info("   - Apenas dias úteis (segunda a sexta)");
    }

    private function getNomeCardapio($diaSemana): string
    {
        // 1=Segunda, 2=Terça, 3=Quarta, 4=Quinta, 5=Sexta
        $nomes = [
            1 => 'Feijoada Completa',      // Segunda
            2 => 'Frango Grelhado',        // Terça
            3 => 'Carne de Panela',        // Quarta
            4 => 'Peixe ao Molho',         // Quinta
            5 => 'Estrogonofe de Carne',   // Sexta
        ];

        return $nomes[$diaSemana] ?? 'Prato do Dia';
    }

    private function getPratoPrincipal($diaSemana): string
    {
        $pratos = [
            1 => 'Feijoada com Linguiça e Bacon',
            2 => 'Frango Grelhado com Ervas',
            3 => 'Carne de Panela com Batata',
            4 => 'Peixe Assado ao Molho de Limão',
            5 => 'Estrogonofe de Carne com Champignon',
        ];

        return $pratos[$diaSemana] ?? 'Prato Principal';
    }

    private function getGuarnicao($diaSemana): string
    {
        $guarnicoes = [
            1 => 'Couve Refogada',
            2 => 'Batata Frita',
            3 => 'Farofa',
            4 => 'Legumes ao Vapor',
            5 => 'Batata Palha',
        ];

        return $guarnicoes[$diaSemana] ?? 'Guarnição';
    }

    private function getSobremesa($diaSemana): string
    {
        $sobremesas = [
            1 => 'Laranja',
            2 => 'Banana',
            3 => 'Melancia',
            4 => 'Maçã',
            5 => 'Pudim',
        ];

        return $sobremesas[$diaSemana] ?? 'Fruta da Época';
    }

    private function getPratoVegetariano($diaSemana): string
    {
        $pratos = [
            1 => 'Proteína de Soja ao Molho',
            2 => 'Omelete de Legumes',
            3 => 'Tofu Grelhado',
            4 => 'Hambúrguer de Grão de Bico',
            5 => 'Quibe de Berinjela',
        ];

        return $pratos[$diaSemana] ?? 'Opção Vegetariana';
    }

    private function getSuco($diaSemana): string
    {
        $sucos = [
            1 => 'Suco de Laranja',
            2 => 'Suco de Limão',
            3 => 'Suco de Maracujá',
            4 => 'Suco de Abacaxi',
            5 => 'Suco de Acerola',
        ];

        return $sucos[$diaSemana] ?? 'Suco Natural';
    }
}


<?php

namespace Database\Seeders;

use App\Models\Cardapio;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CardapioMensalSeeder extends Seeder
{
t    /**
     * Pratos para gerar cardápios variados
     */
    private array $pratosPrincipais = [
        'Fricassê de frango (com queijo, batata palha e creme de leite)',
        'Frango grelhado ao limão e ervas',
        'Picadinho de carne bovina ao molho',
        'Sobrecoxa ao molho barbecue',
        'Tilápia assada com limão',
        'Estrogonofe de carne',
        'Ragu de frango com ervas',
        'Carne moída refogada',
        'Arroz carreteiro',
        'Filé de frango ao molho pesto',
        'Frango xadrez',
        'Carne de panela com legumes',
        'Panqueca de carne ao molho sugo',
        'Frango ao curry suave',
        'Iscas de fígado aceboladas',
        'Carne suína assada com ervas',
        'Peixe ao molho de tomate',
        'Frango ao molho de tomate com manjericão',
        'Frango ao molho de laranja',
        'Almôndegas ao molho sugo',
        'Peito de frango ao molho branco',
        'Lasanha de carne',
        'Lombo suíno ao molho madeira',
        'Bife ao molho madeira',
        'Tilápia grelhada',
        'Carne assada fatiada',
        'Costelinha suína ao forno',
        'Peixe ao molho de coco',
        'Moqueca de peixe',
        'Frango à passarinho assado',
        'Bobó de frango',
        'Peixe empanado assado',
        'Frango à parmegiana',
        'Carne suína ao molho agridoce',
        'Feijoada leve',
        'Moela ao molho',
        'Escondidinho de carne seca',
        'Galinhada caipira',
        'Picanha ao molho chimichurri',
        'Maminha ao molho de vinho',
    ];

    private array $pratosSecundarios = [
        'Frango grelhado (versão simples)',
        'Sobrecoxa grelhada',
        'Filé de frango empanado',
        'Iscas de carne aceboladas',
        'Omelete de forno',
        'Frango desfiado',
        'Carne moída especial',
        'Peito de frango recheado',
        'Bife acebolado',
        'Linguiça calabresa grelhada',
    ];

    private array $guarnicoes = [
        'Purê de batata',
        'Polenta cremosa',
        'Batata rústica assada',
        'Mandioquinha sauté',
        'Vagem refogada',
        'Farofa rica (castanha, passas)',
        'Legumes salteados na manteiga',
        'Couve refogada',
        'Brócolis ao alho e óleo',
        'Berinjela ao forno',
        'Purê de mandioquinha',
        'Batata sauté',
        'Abobrinha refogada com alho',
        'Farofa de banana',
        'Chuchu com salsa',
        'Mix de legumes assados',
        'Chips de batata doce',
        'Legumes grelhados',
    ];

    private array $acompanhamentos1 = [
        'Arroz branco e arroz integral',
        'Macarrão alho e óleo',
        'Arroz com brócolis',
        'Arroz com milho',
        'Arroz com alho',
        'Arroz à grega',
        'Macarrão ao molho branco',
        'Arroz com cenoura',
        'Macarrão ao sugo',
        'Arroz vermelho',
        'Arroz com amendoas',
        'Macarrão ao molho rosé',
        'Arroz tailandês',
        'Arroz de coco',
    ];

    private array $acompanhamentos2 = [
        'Lentilha cozida',
        'Feijão preto (com caldo)',
        'Feijão carioca (com caldo)',
        'Ervilha seca com caldo',
        'Feijão fradinho',
        'Caldo de feijão (carioca)',
        'Feijão tropeiro',
        'Feijão verde',
    ];

    private array $saladas = [
        'Seleta de legumes com milho, ervilha e passas',
        'Tabule (trigo, tomate, hortelã)',
        'Pepino agridoce',
        'Beterraba ralada com laranja',
        'Salada de maionese (tradicional)',
        'Alface, tomate e cenoura ralada',
        'Salada de grão-de-bico com legumes',
        'Cenoura e vagem ao vapor',
        'Repolho roxo com maçã',
        'Vinagrete de tomate e cebola',
        'Mix de folhas com pepino e tomate-cereja',
        'Rúcula com tomate e parmesão',
        'Salada tropical',
        'Salada caesar',
        'Salada caprese',
        'Salada waldorf',
        'Salada mediterrânea',
    ];

    private array $ovoLacto = [
        'Lasanha de berinjela com queijo',
        'Torta de legumes',
        'Omelete de forno com legumes',
        'Risoto de cogumelos',
        'Fricassê de milho verde com queijo',
        'Panqueca de ricota e espinafre',
        'Macarrão ao pesto com queijo',
        'Escondidinho de legumes com queijo',
        'Abóbora recheada com legumes e queijo',
        'Quiche de espinafre e queijo',
        'Moqueca de banana-da-terra',
        'Estrogonofe de palmito',
        'Berinjela à parmegiana',
        'Nhoque de batata ao molho branco',
        'Canelone de ricota',
        'Curry de legumes',
    ];

    private array $sucos = [
        'Cajá/Goiaba',
        'Graviola/Abacaxi',
        'Uva/Maçã',
        'Laranja/Manga',
        'Caju/Acerola',
        'Melancia/Limão',
        'Maracujá/Limão',
        'Capim-cidreira/Limão',
        'Morango/Laranja',
        'Abacaxi/Hortelã',
        'Limão/Gengibre',
        'Pêra/Limão',
        'Lichia/Limão',
    ];

    private array $sobremesas = [
        'Maçã',
        'Arroz doce',
        'Salada de frutas',
        'Banana',
        'Doce de abóbora',
        'Mamão',
        'Melão',
        'Mousse de maracujá',
        'Abacaxi',
        'Brigadeiro (porção)',
        'Pudim de leite',
        'Laranja',
        'Canjica',
        'Gelatina',
        'Cocada',
        'Doce de leite',
        'Pavê de chocolate',
        'Torta de maçã',
        'Bolo de cenoura',
        'Manjar branco',
    ];

    /**
     * Cria cardápios de 01/01/2026 até 31/03/2026 (apenas dias úteis)
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🍽️  CRIANDO CARDÁPIOS DE JANEIRO A MARÇO 2026');
        $this->command->info('================================================');

        $dataInicio = Carbon::create(2026, 1, 1);
        $dataFim = Carbon::create(2026, 3, 31);

        $cardapiosCriados = 0;
        $refeicoesCriadas = 0;

        $dataAtual = $dataInicio->copy();

        while ($dataAtual->lte($dataFim)) {
            // Pular finais de semana
            if ($dataAtual->isSaturday() || $dataAtual->isSunday()) {
                $dataAtual->addDay();
                continue;
            }

            // Verificar se já existe cardápio para esta data
            $existente = Cardapio::where('data_do_cardapio', $dataAtual->format('Y-m-d'))->first();
            if ($existente) {
                $dataAtual->addDay();
                continue;
            }

            // Criar cardápio com pratos variados
            $cardapio = Cardapio::create([
                'data_do_cardapio' => $dataAtual->format('Y-m-d'),
                'prato_principal_ptn01' => $this->pratosPrincipais[array_rand($this->pratosPrincipais)],
                'prato_principal_ptn02' => $this->pratosSecundarios[array_rand($this->pratosSecundarios)],
                'guarnicao' => $this->guarnicoes[array_rand($this->guarnicoes)],
                'acompanhamento_01' => $this->acompanhamentos1[array_rand($this->acompanhamentos1)],
                'acompanhamento_02' => $this->acompanhamentos2[array_rand($this->acompanhamentos2)],
                'salada' => $this->saladas[array_rand($this->saladas)],
                'ovo_lacto_vegetariano' => $this->ovoLacto[array_rand($this->ovoLacto)],
                'suco' => $this->sucos[array_rand($this->sucos)],
                'sobremesa' => $this->sobremesas[array_rand($this->sobremesas)],
                'turnos' => ['almoco', 'jantar'],
            ]);

            $cardapiosCriados++;
            $refeicoesCriadas += 2; // almoço e jantar

            // Log a cada 10 cardápios
            if ($cardapiosCriados % 10 === 0) {
                $this->command->info("   📅 {$cardapiosCriados} cardápios criados...");
            }

            $dataAtual->addDay();
        }

        $this->command->info('');
        $this->command->info('✅ Cardápios criados com sucesso!');
        $this->command->info("📊 Resumo:");
        $this->command->info("   - {$cardapiosCriados} Cardápios criados");
        $this->command->info("   - {$refeicoesCriadas} Refeições criadas (almoço e jantar)");
        $this->command->info("   - Período: 01/01/2026 a 31/03/2026");
        $this->command->info("   - Apenas dias úteis (segunda a sexta)");
    }
}

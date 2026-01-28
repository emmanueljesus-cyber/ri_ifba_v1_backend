<?php

namespace Database\Seeders;

use App\Models\Cardapio;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CardapioMensalSeeder extends Seeder
{

    /**
     * Cardapio de Janeiro 2026 - Baseado na planilha oficial
     */
    private array $cardapioJaneiro2026 = [
        // dia => [ptn01, ptn02, guarnicao, acomp01, acomp02, salada, ovolacto, suco, sobremesa]
        '2026-01-01' => [
            'Fricasse de frango (com queijo, batata palha e creme de leite)',
            'Frango grelhado (versao simples)',
            'Pure de batata',
            'Arroz branco e arroz integral',
            'Lentilha cozida',
            'Seleta de legumes com milho, ervilha e passas',
            'Lasanha de berinjela com queijo',
            'Caja/Goiaba',
            'Maca'
        ],
        '2026-01-02' => [
            'Picadinho de carne bovina ao molho',
            'Sobrecoxa ao molho barbecue',
            'Polenta cremosa',
            'Macarrao alho e oleo',
            'Feijao preto (com caldo)',
            'Tabule (trigo, tomate, hortela)',
            'Torta de legumes',
            'Graviola/Abacaxi',
            'Arroz doce'
        ],
        '2026-01-03' => [
            'Tilapia assada com limao',
            'Estrogonofe de carne',
            'Batata rustica assada',
            'Arroz com brocolis',
            'Feijao carioca (com caldo)',
            'Pepino agridoce',
            'Omelete de forno com legumes',
            'Uva/Maca',
            'Salada de frutas'
        ],
        '2026-01-06' => [
            'Ragu de frango com ervas',
            'Carne moida refogada',
            'Mandioquinha saute',
            'Arroz com milho',
            'Caldo de feijao (carioca)',
            'Beterraba ralada com laranja',
            'Risoto de cogumelos',
            'Laranja/Manga',
            'Banana'
        ],
        '2026-01-07' => [
            'Arroz carreteiro',
            'File de frango ao molho pesto',
            'Vagem refogada',
            'Arroz com alho',
            'Ervilha seca com caldo',
            'Salada de maionese (tradicional)',
            'Fricasse de milho verde com queijo',
            'Caju/Acerola',
            'Doce de abobora'
        ],
        '2026-01-08' => [
            'Frango xadrez',
            'Carne de panela com legumes',
            'Farofa rica (castanha, passas)',
            'Arroz a grega',
            'Feijao fradinho',
            'Alface, tomate e cenoura ralada',
            'Panqueca de ricota e espinagre',
            'Melancia/Limao',
            'Mamao'
        ],
        '2026-01-09' => [
            'Picadinho de carne com quiabo',
            'Panqueca de carne ao molho sugo',
            'Legumes salteados na manteiga',
            'Macarrao ao molho branco',
            'Lentilha cozida',
            'Salada de grao-de-bico com legumes',
            'Macarrao ao pesto com queijo',
            'Maracuja/Limao',
            'Melao'
        ],
        '2026-01-10' => [
            'Frango ao curry suave',
            'Iscas de figado aceboladas',
            'Couve refogada',
            'Arroz com cenoura',
            'Feijao preto (com caldo)',
            'Cenoura e vagem ao vapor',
            'Escondidinho de legumes com queijo',
            'Capim-cidreira/Limao',
            'Mousse de maracuja'
        ],
        '2026-01-13' => [
            'Carne suina assada com ervas',
            'Peixe ao molho de tomate',
            'Brocolis ao alho e oleo',
            'Macarrao ao sugo',
            'Feijao carioca (com caldo)',
            'Repolho roxo com maca',
            'Abobora recheada com legumes e queijo',
            'Morango/Laranja',
            'Abacaxi'
        ],
        '2026-01-14' => [
            'Frango ao molho de tomate com manjericao',
            'Frango ao molho de laranja',
            'Berinjela ao forno',
            'Arroz vermelho',
            'Caldo de feijao (carioca)',
            'Vinagrete de tomate e cebola',
            'Quiche de espinafre e queijo',
            'Abacaxi/Hortela',
            'Brigadeiro (porcao)'
        ],
        '2026-01-15' => [
            'Almondegas ao molho sugo',
            'Peito de frango ao molho branco',
            'Pure de mandioquinha',
            'Arroz branco e arroz integral',
            'Ervilha seca com caldo',
            'Mix de folhas com pepino e tomate-cereja',
            'Moqueca de banana-da-terra',
            'Caja/Goiaba',
            'Pudim de leite'
        ],
        '2026-01-16' => [
            'Lasanha de carne',
            'Lombo suino ao molho madeira e champignon',
            'Batata saute',
            'Macarrao alho e oleo',
            'Feijao fradinho',
            'Rucula com tomate e parmesao',
            'Estrogonofe de palmito',
            'Graviola/Abacaxi',
            'Laranja'
        ],
        '2026-01-17' => [
            'Bife ao molho madeira',
            'Tilapia grelhada',
            'Abobrinha refogada com alho',
            'Arroz com brocolis',
            'Lentilha cozida',
            'Seleta de legumes com milho, ervilha e passas',
            'Lasanha de berinjela com queijo',
            'Uva/Maca',
            'Canjica'
        ],
        '2026-01-20' => [
            'Frango grelhado ao limao e ervas',
            'Carne assada fatiada',
            'Farofa de banana',
            'Arroz com milho',
            'Feijao preto (com caldo)',
            'Tabule (trigo, tomate, hortela)',
            'Torta de legumes',
            'Laranja/Manga',
            'Gelatina'
        ],
        '2026-01-21' => [
            'Carne assada ao molho roti',
            'Costelinha suina ao forno com barbecue',
            'Chuchu com salsa',
            'Arroz com alho',
            'Feijao carioca (com caldo)',
            'Pepino agridoce',
            'Omelete de forno com legumes',
            'Caju/Acerola',
            'Maca'
        ],
        '2026-01-22' => [
            'Peixe ao molho de coco',
            'Almondegas ao molho',
            'Mix de legumes assados',
            'Arroz a grega',
            'Caldo de feijao (carioca)',
            'Beterraba ralada com laranja',
            'Risoto de cogumelos',
            'Melancia/Limao',
            'Arroz doce'
        ],
        '2026-01-23' => [
            'Moqueca de peixe',
            'Frango a passarinho assado',
            'Pure de batata',
            'Macarrao ao molho branco',
            'Ervilha seca com caldo',
            'Salada de maionese (tradicional)',
            'Fricasse de milho verde com queijo',
            'Maracuja/Limao',
            'Salada de frutas'
        ],
        '2026-01-24' => [
            'Bobo de frango',
            'Peixe empanado assado',
            'Polenta cremosa',
            'Arroz com cenoura',
            'Feijao fradinho',
            'Alface, tomate e cenoura ralada',
            'Panqueca de ricota e espinafre',
            'Capim-cidreira/Limao',
            'Banana'
        ],
        // TESTE FIM DE SEMANA - Sabado 24/01/2026
        '2026-01-24-teste' => [
            'Feijoada especial de sabado',
            'Frango assado com ervas',
            'Couve mineira refogada',
            'Arroz branco e arroz integral',
            'Feijao preto especial',
            'Vinagrete completo',
            'Lasanha de berinjela',
            'Laranja/Acerola',
            'Mousse de chocolate'
        ],
        // TESTE FIM DE SEMANA - Domingo 25/01/2026
        '2026-01-25-teste' => [
            'Churrasco misto (picanha, fraldinha)',
            'Linguica toscana grelhada',
            'Farofa especial com bacon',
            'Arroz a grega',
            'Feijao tropeiro',
            'Salada completa com palmito',
            'Espetinho de legumes grelhados',
            'Maracuja/Hortela',
            'Pudim de leite condensado'
        ],
        '2026-01-27' => [
            'Frango a parmegiana',
            'Carne suina ao molho agridoce',
            'Batata rustica assada',
            'Macarrao ao sugo',
            'Lentilha cozida',
            'Salada de grao-de-bico com legumes',
            'Macarrao ao pesto com queijo',
            'Morango/Laranja',
            'Doce de abobora'
        ],
        '2026-01-28' => [
            'Feijoada leve (carnes selecionadas)',
            'Moela ao molho',
            'Mandioquinha saute',
            'Arroz vermelho',
            'Feijao preto (com caldo)',
            'Cenoura e vagem ao vapor',
            'Escondidinho de legumes com queijo',
            'Abacaxi/Hortela',
            'Mamao'
        ],
        '2026-01-29' => [
            'Carne moida com legumes',
            'Frango grelhado (versao simples)',
            'Vagem refogada',
            'Arroz branco e arroz integral',
            'Feijao carioca (com caldo)',
            'Repolho roxo com maca',
            'Abobora recheada com legumes e queijo',
            'Caja/Goiaba',
            'Melao'
        ],
        '2026-01-30' => [
            'Frango desfiado com milho e requeijao',
            'Sobrecoxa ao molho barbecue',
            'Farofa rica (castanha, passas)',
            'Macarrao alho e oleo',
            'Caldo de feijao (carioca)',
            'Vinagrete de tomate e cebola',
            'Quiche de espinafre e queijo',
            'Graviola/Abacaxi',
            'Mousse de maracuja'
        ],
        '2026-01-31' => [
            'Estrogonofe de carne',
            'File de frango ao molho pesto',
            'Legumes salteados na manteiga',
            'Arroz com brocolis',
            'Ervilha seca com caldo',
            'Mix de folhas com pepino e tomate-cereja',
            'Moqueca de banana-da-terra',
            'Uva/Maca',
            'Pudim de leite'
        ],
    ];

    /**
     * Cardapio de Fevereiro 2026 - Criacao autoral
     */
    private array $cardapioFevereiro2026 = [
        '2026-02-02' => [
            'Frango ao molho de mostarda e mel',
            'Bife acebolado',
            'Pure de batata doce',
            'Arroz branco e arroz integral',
            'Feijao carioca (com caldo)',
            'Salada tropical (alface, manga e queijo)',
            'Nhoque de batata ao molho branco',
            'Manga/Hortela',
            'Cocada'
        ],
        '2026-02-03' => [
            'Carne de sol desfiada com nata',
            'Frango ao molho de ervas finas',
            'Baiao de dois',
            'Arroz com acafrao',
            'Feijao verde',
            'Vinagrete especial (tomate, cebola, pimentao)',
            'Torta de palmito',
            'Caju/Laranja',
            'Banana caramelizada'
        ],
        '2026-02-04' => [
            'Peixe grelhado ao molho de maracuja',
            'Costelinha suina ao molho de laranja',
            'Farofa de cuscuz',
            'Macarrao integral ao alho',
            'Lentilha cozida',
            'Salada caesar (alface romana, croutons, parmesao)',
            'Berinjela a parmegiana',
            'Maracuja/Acerola',
            'Gelatina de morango'
        ],
        '2026-02-05' => [
            'Escondidinho de carne seca',
            'Peito de frango recheado com queijo e tomate',
            'Couve mineira refogada',
            'Arroz com amendoas',
            'Feijao tropeiro',
            'Salada de beterraba com cenoura',
            'Lasanha vegetariana de abobrinha',
            'Limao/Gengibre',
            'Doce de leite'
        ],
        '2026-02-06' => [
            'Galinhada caipira',
            'Lombo suino ao molho de abacaxi',
            'Batata gratinada',
            'Macarrao ao molho rose',
            'Feijao preto (com caldo)',
            'Tabule de quinoa',
            'Strogonoff de cogumelos',
            'Abacaxi/Menta',
            'Pave de chocolate'
        ],
        '2026-02-09' => [
            'Picanha ao molho chimichurri',
            'Frango assado com batatas',
            'Legumes grelhados (abobrinha, berinjela, pimentao)',
            'Arroz com brocolis',
            'Feijao carioca (com caldo)',
            'Salada mediterranea (pepino, tomate, azeitona)',
            'Quibe assado vegetariano',
            'Laranja/Cenoura',
            'Brigadeiro gourmet'
        ],
        '2026-02-10' => [
            'Carne assada com molho ferrugem',
            'File de frango empanado',
            'Pure de mandioca',
            'Arroz a piamontese',
            'Lentilha cozida',
            'Salada de graos (grao-de-bico, milho, ervilha)',
            'Espaguete ao molho pesto',
            'Uva/Maca',
            'Mousse de limao'
        ],
        '2026-02-11' => [
            'Frango ao molho thai',
            'Carne moida especial (com bacon e queijo)',
            'Arroz tailandes',
            'Macarrao ao molho branco',
            'Feijao fradinho',
            'Salada oriental (repolho, cenoura, gergelim)',
            'Curry de legumes com leite de coco',
            'Lichia/Limao',
            'Abacaxi com canela'
        ],
        '2026-02-12' => [
            'Tilapia ao molho de alcaparras',
            'Isca de carne ao molho oriental',
            'Risoto de limao siciliano',
            'Arroz com ervas',
            'Feijao preto (com caldo)',
            'Salada caprese (tomate, mussarela, manjericao)',
            'Ratatouille',
            'Morango/Laranja',
            'Torta de maca'
        ],
        '2026-02-13' => [
            'Feijoada tradicional',
            'Frango a mineira',
            'Couve refogada com bacon',
            'Arroz branco e arroz integral',
            'Feijao preto especial',
            'Vinagrete com coentro',
            'Feijoada vegetariana (com legumes)',
            'Laranja/Acerola',
            'Laranja'
        ],
        '2026-02-18' => [
            'Frango xadrez apimentado',
            'Bife a milanesa',
            'Batata rustica com alecrim',
            'Macarrao ao sugo',
            'Ervilha seca com caldo',
            'Salada waldorf (maca, aipo, nozes)',
            'Empada de palmito',
            'Maca/Canela',
            'Salada de frutas'
        ],
        '2026-02-19' => [
            'Costela bovina ao bafo',
            'Sobrecoxa grelhada com ervas',
            'Farofa de banana da terra',
            'Arroz com cenoura e milho',
            'Feijao carioca (com caldo)',
            'Salada de repolho com abacaxi',
            'Panqueca de espinagre e ricota',
            'Abacaxi/Gengibre',
            'Pudim de coco'
        ],
        '2026-02-20' => [
            'Peixe ao molho de camarao',
            'Carne de panela com cerveja',
            'Pirao de peixe',
            'Arroz de coco',
            'Lentilha cozida',
            'Salada de palmito e tomate',
            'Moqueca de legumes',
            'Coco/Limao',
            'Cartola (banana, queijo e canela)'
        ],
        '2026-02-23' => [
            'Pernil suino assado',
            'Frango ao molho de queijo',
            'Salpicao',
            'Macarrao ao molho quatro queijos',
            'Feijao fradinho',
            'Salada russa',
            'Canelone de ricota e espinagre',
            'Uva/Hortela',
            'Manjar branco'
        ],
        '2026-02-24' => [
            'Carne louca desfiada',
            'Coxa de frango ao forno com especiarias',
            'Polenta frita',
            'Arroz com acafrao',
            'Feijao preto (com caldo)',
            'Salada de folhas verdes com manga',
            'Tortilha espanhola',
            'Manga/Laranja',
            'Bolo de cenoura'
        ],
        '2026-02-25' => [
            'Maminha ao molho de vinho',
            'Frango grelhado ao molho de iogurte',
            'Chips de batata doce',
            'Arroz integral com castanhas',
            'Feijao carioca (com caldo)',
            'Salada de agriao com pera',
            'Shakshuka (ovos ao molho de tomate)',
            'Pera/Limao',
            'Doce de goiaba com queijo'
        ],
        '2026-02-26' => [
            'Xinxim de galinha',
            'Picanha suina grelhada',
            'Acaraje assado',
            'Arroz de hauca',
            'Feijao fradinho',
            'Salada de quiabo',
            'Vatapa vegetariano',
            'Caja/Caju',
            'Cocada baiana'
        ],
        '2026-02-27' => [
            'Rabada ao molho',
            'File de frango ao molho de champignon',
            'Polenta cremosa com queijo',
            'Macarrao ao molho bolonhesa',
            'Lentilha cozida',
            'Salada de tomate e pepino',
            'Espinafre gratinado',
            'Limao/Hortela',
            'Arroz doce com canela'
        ],
    ];

    /**
     * Cria cardapios para Janeiro e Fevereiro de 2026 (dias uteis)
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('CRIANDO CARDAPIOS JANEIRO E FEVEREIRO 2026');

        if ($this->testesFimDeSemana) {
            $this->command->info('');
            $this->command->warn('MODO TESTE ATIVADO - Fim de semana 24-25/01/2026 sera incluido');
            $this->command->warn('   Lembre-se de mudar $testesFimDeSemana para FALSE apos os testes!');
        }

        $this->command->info('');

        $cardapiosCriados = 0;
        $refeicoesCriadas = 0;

        // Processar Janeiro 2026
        $this->command->info('JANEIRO 2026:');
        foreach ($this->cardapioJaneiro2026 as $dataStr => $dados) {
            // Ignorar entradas de teste marcadas com sufixo
            if (str_ends_with($dataStr, '-teste')) {
                continue;
            }
            $resultado = $this->criarCardapio($dataStr, $dados);
            if ($resultado) {
                $cardapiosCriados++;
                $refeicoesCriadas += 2;
            }
        }

        $this->command->info('');
        $this->command->info('FEVEREIRO 2026:');
        foreach ($this->cardapioFevereiro2026 as $dataStr => $dados) {
            $resultado = $this->criarCardapio($dataStr, $dados);
            if ($resultado) {
                $cardapiosCriados++;
                $refeicoesCriadas += 2;
            }
        }

        $this->command->info('');
        $this->command->info('Cardapios criados com sucesso!');
        $this->command->info("Resumo:");
        $this->command->info("   - {$cardapiosCriados} Cardapios criados");
        $this->command->info("   - {$refeicoesCriadas} Refeicoes criadas (almoco e jantar)");
        $this->command->info("   - Periodo: Janeiro e Fevereiro de 2026");
        $this->command->info("   - Apenas dias uteis (segunda a sexta)");
    }

    private function criarCardapio(string $dataStr, array $dados): ?Cardapio
    {
        $data = Carbon::parse($dataStr);

        // Pular finais de semana
        if ($data->isSaturday() || $data->isSunday()) {
            $this->command->info("   Pulando final de semana: {$data->format('d/m/Y')} ({$data->dayName})");
            return null;
        }

        // Verificar se ja existe
        $cardapioExistente = Cardapio::where('data_do_cardapio', $data->format('Y-m-d'))->first();
        if ($cardapioExistente) {
            $this->command->info("   Cardapio ja existe para {$data->format('d/m/Y')}");
            return null;
        }

        // Criar cardapio com dados da planilha
        // [0] ptn01, [1] ptn02, [2] guarnicao, [3] acomp01, [4] acomp02,
        // [5] salada, [6] ovolacto, [7] suco, [8] sobremesa
        $cardapio = Cardapio::create([
            'data_do_cardapio' => $data->format('Y-m-d'),
            'prato_principal_ptn01' => $dados[0],
            'prato_principal_ptn02' => $dados[1],
            'guarnicao' => $dados[2],
            'acompanhamento_01' => $dados[3],
            'acompanhamento_02' => $dados[4],
            'salada' => $dados[5],
            'ovo_lacto_vegetariano' => $dados[6],
            'suco' => $dados[7],
            'sobremesa' => $dados[8],
            'turnos' => ['almoco', 'jantar'],
        ]);

        $this->command->info("   {$data->format('d/m/Y')} - {$dados[0]}");

        return $cardapio;
    }
}

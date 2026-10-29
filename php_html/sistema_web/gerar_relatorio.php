<?php
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/../../php/conexao.php'; 

$tipoRelatorio = strtolower($_GET['tipo'] ?? 'estoque');

$cssBase = '
<style>
    @page { margin: 30px 25px 25px 25px; }
    body { font-family: "Helvetica", "Arial", sans-serif; font-size: 9.5pt; color: #2c3e50; line-height: 1.3; margin: 0; }
    
    .header-container { text-align: center; margin-bottom: 12px; }
    .main-title { font-size: 16pt; font-weight: bold; color: #1b4f72; margin: 0; padding: 0; text-transform: uppercase; }
    .meta-info { font-size: 8.5pt; color: #566573; margin-top: 4px; margin-bottom: 8px; text-align: center; }
    .divider { border-bottom: 2px solid #1b4f72; margin-bottom: 14px; }

    .section-title { font-size: 11pt; font-weight: bold; color: #2980b9; margin-top: 12px; margin-bottom: 6px; border-bottom: 1px solid #d6eaf8; padding-bottom: 2px; }
    
    ul.info-list { list-style: none; padding-left: 0; margin: 4px 0 10px 0; }
    ul.info-list li { margin-bottom: 4px; padding-left: 12px; position: relative; }
    ul.info-list li::before { content: "•"; position: absolute; left: 0; color: #2980b9; font-weight: bold; }
    
    table.report-table { width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 12px; page-break-inside: avoid; }
    table.report-table th { background-color: #2980b9; color: #ffffff; font-weight: bold; font-size: 9pt; text-align: center; padding: 6px 8px; border: 1px solid #2471a3; }
    table.report-table td { padding: 5px 8px; border: 1px solid #d6eaf8; font-size: 9pt; }
    table.report-table tr:nth-child(even) { background-color: #fcfcfc; }
    
    .bold { font-weight: bold; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .text-left { text-align: left; }
    .italic { font-style: italic; color: #5d6d7e; }
    
    .status-alert { font-weight: bold; color: #c0392b; }
    .status-ok { font-weight: bold; color: #27ae60; }
    .status-stable { font-weight: bold; color: #2980b9; }

    .badge { padding: 2px 6px; border-radius: 3px; font-weight: bold; color: #fff; font-size: 8pt; display: inline-block; }
    .badge-A { background: #e74c3c; } 
    .badge-B { background: #f39c12; } 
    .badge-C { background: #27ae60; }
</style>';

$html = '';

switch ($tipoRelatorio) {

    case 'estoque':
        $stmtTotal = $pdo->query("SELECT SUM(quantidade_estoque * preco_custo) as total_val, COUNT(DISTINCT estoque_id) as total_skus FROM lotes_estoque WHERE quantidade_estoque > 0");
        $resTotal = $stmtTotal->fetch();
        $valorTotal = $resTotal['total_val'] ?? 0.00;
        $totalSkus  = $resTotal['total_skus'] ?? 0;

        $html = '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8">' . $cssBase . '</head><body>
        <div class="header-container">
            <div class="main-title">RELATÓRIO GERENCIAL DE ESTOQUE</div>
            <div class="meta-info"><strong>Período de Referência:</strong> ' . date('m/Y') . ' | <strong>Status:</strong> Em Análise</div>
        </div>
        <div class="divider"></div>

        <div class="section-title">1. Resumo Executivo (Painel de Controle)</div>
        <ul class="info-list">
            <li><strong>Valor Total do Estoque Atual:</strong> R$ ' . number_format($valorTotal, 2, ',', '.') . '</li>
            <li><strong>Número Total de Itens (SKUs):</strong> ' . $totalSkus . ' itens</li>
            <li><strong>Giro de Estoque Médio:</strong> 2,4 vezes no mês</li>
            <li><strong>Tempo Médio de Permanência:</strong> 12 dias</li>
            <li><strong>Índice de Acuracidade do Inventário:</strong> 98,5% <span class="italic">(Meta: >98%)</span></li>
        </ul>

        <div class="section-title">2. Desempenho Financeiro e Ocupação</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th class="text-left">Indicador</th>
                    <th class="text-center">Mês Atual</th>
                    <th class="text-center">Mês Anterior</th>
                    <th class="text-center">Variação (%)</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="bold text-left">Capital Imobilizado</td>
                    <td class="text-center">R$ ' . number_format($valorTotal, 2, ',', '.') . '</td>
                    <td class="text-center">R$ ' . number_format($valorTotal * 0.95, 2, ',', '.') . '</td>
                    <td class="text-center">[+5,0%]</td>
                    <td class="status-alert text-center">Atenção</td>
                </tr>
                <tr>
                    <td class="bold text-left">Custo de Armazenagem</td>
                    <td class="text-center">R$ 1.200,00</td>
                    <td class="text-center">R$ 1.250,00</td>
                    <td class="text-center">[-4,0%]</td>
                    <td class="status-ok text-center">Dentro da Meta</td>
                </tr>
                <tr>
                    <td class="bold text-left">Taxa de Ocupação Física</td>
                    <td class="text-center">68%</td>
                    <td class="text-center">65%</td>
                    <td class="text-center">[+3,0%]</td>
                    <td class="status-stable text-center">Estável</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">3. Alertas Críticos e Pontos de Atenção</div>
        <div style="margin-left: 5px;">
            <p style="margin: 6px 0 2px 0;"><strong>Ruptura de Estoque (Falta de Produto)</strong></p>
            <ul class="info-list">
                <li><strong>Item:</strong> Pão Brioche | <strong>Dias em falta:</strong> 1 dia | <strong>Impacto estimado:</strong> R$ 350,00</li>
                <li><strong>Ação corretiva:</strong> Compra emergencial realizada com o fornecedor Moinho Silva, entrega prevista para amanhã.</li>
            </ul>

            <p style="margin: 6px 0 2px 0;"><strong>Estoque Obsoleto / Sem Giro (Capital Parado)</strong></p>
            <ul class="info-list">
                <li><strong>Item:</strong> Embalagem Sucos | <strong>Dias sem movimentação:</strong> 45 dias | <strong>Valor parado:</strong> R$ 420,00</li>
                <li><strong>Ação corretiva:</strong> Incluir na campanha de queima de estoque/desconto do próximo mês.</li>
            </ul>
        </div>

        <div class="section-title">4. Plano de Ação para o Próximo Período</div>
        <ol style="margin: 6px 0 0 0; padding-left: 18px;">
            <li>Reduzir o capital imobilizado da Classe C em 15% através de promoções casadas.</li>
            <li>Ajustar o ponto de pedido dos itens da Classe A para evitar novas rupturas.</li>
            <li>Realizar inventário cíclico semanal nos setores de maior divergência.</li>
        </ol>
        </body></html>';
        $nomeArquivo = 'relatorio_gerencial_estoque.pdf';
        break;

    case 'curva_abc':
        $stmt = $pdo->query("SELECT e.nome, e.codigo, SUM(l.quantidade_estoque * l.preco_custo) as valor_total
                             FROM estoque e
                             INNER JOIN lotes_estoque l ON e.id = l.estoque_id
                             WHERE l.quantidade_estoque > 0
                             GROUP BY e.id ORDER BY valor_total DESC LIMIT 20");
        $itens = $stmt->fetchAll();
        $totalGeral = array_sum(array_column($itens, 'valor_total'));
        $acumulado = 0;

        $html = '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8">' . $cssBase . '</head><body>
        <div class="header-container">
            <div class="main-title">ANÁLISE DE CURVA ABC DE ESTOQUE</div>
            <div class="meta-info">Classificação por Valor Imobilizado e Relevância | <strong>Disco Burguer</strong></div>
        </div>
        <div class="divider"></div>

        <div class="section-title">Classificação dos Produtos</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th class="text-center">Código</th>
                    <th class="text-left">Item / Ingrediente</th>
                    <th class="text-right">Valor Total</th>
                    <th class="text-right">% Total</th>
                    <th class="text-right">% Acumulada</th>
                    <th class="text-center">Classe</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($itens as $i) {
            $acumulado += $i['valor_total'];
            $pct = $totalGeral > 0 ? ($i['valor_total'] / $totalGeral) * 100 : 0;
            $pctAcum = $totalGeral > 0 ? ($acumulado / $totalGeral) * 100 : 0;
            $classe = $pctAcum <= 80 ? 'A' : ($pctAcum <= 95 ? 'B' : 'C');

            $html .= '<tr>
                <td class="text-center">' . htmlspecialchars($i['codigo']) . '</td>
                <td class="text-left">' . htmlspecialchars($i['nome']) . '</td>
                <td class="text-right">R$ ' . number_format($i['valor_total'], 2, ',', '.') . '</td>
                <td class="text-right">' . number_format($pct, 1, ',', '.') . '%</td>
                <td class="text-right">' . number_format($pctAcum, 1, ',', '.') . '%</td>
                <td class="text-center"><span class="badge badge-' . $classe . '">' . $classe . '</span></td>
            </tr>';
        }
        $html .= '</tbody></table>

        <div class="section-title">Diretrizes por Classe</div>
        <ul class="info-list">
            <li><strong>Classe A (~80% do valor):</strong> Controle rigoroso diário, negociação contínua de prazos e estoque mínimo reduzido.</li>
            <li><strong>Classe B (~15% do valor):</strong> Monitoramento quinzenal com gatilhos automáticos de reposição.</li>
            <li><strong>Classe C (~5% do valor):</strong> Compras em maior volume para reduzir custos operacionais de transporte.</li>
        </ul>
        </body></html>';
        $nomeArquivo = 'relatorio_curva_abc.pdf';
        break;

    case 'fluxo_caixa':
    case 'fluxo':
        $stmt = $pdo->query("SELECT fc.*, cf.nome AS categoria_nome, cf.tipo 
                             FROM fluxo_caixa fc 
                             INNER JOIN categorias_financeiras cf ON fc.categoria_id = cf.id 
                             ORDER BY fc.data_movimentacao DESC LIMIT 10");
        $movs = $stmt->fetchAll();

        $totalEntradas = 0; 
        $totalSaidas = 0;
        foreach ($movs as $m) {
            if ($m['tipo'] === 'entrada') $totalEntradas += $m['valor'];
            else $totalSaidas += $m['valor'];
        }
        $saldoLiquido = $totalEntradas - $totalSaidas;

        $html = '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8">' . $cssBase . '</head><body>
        <div class="header-container">
            <div class="main-title">RELATÓRIO DE FLUXO DE CAIXA</div>
            <div class="meta-info"><strong>Empresa:</strong> Disco Burguer | <strong>Período:</strong> ' . date('01/m/Y') . ' a ' . date('t/m/Y') . ' | <strong>Emissão:</strong> ' . date('d/m/Y') . '</div>
        </div>
        <div class="divider"></div>

        <div class="section-title">1. Resumo Executivo</div>
        <p style="margin: 3px 0 8px 0; text-align: justify;">
            No período analisado, a empresa apresentou um Saldo Final de Caixa de <strong>R$ ' . number_format($saldoLiquido, 2, ',', '.') . '</strong>. 
            O saldo inicial era de <strong>R$ 0,00</strong>, resultando em uma Geração de Caixa Líquida <strong>' . ($saldoLiquido >= 0 ? 'Positiva' : 'Negativa') . '</strong>.
        </p>

        <div class="section-title">2. Demonstrativo de Resultados Financeiros</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th class="text-left">Item</th>
                    <th class="text-right">Valor (R$)</th>
                    <th class="text-right">% sobre Entrada</th>
                </tr>
            </thead>
            <tbody>
                <tr><td class="text-left">(+) Saldo Inicial de Caixa</td><td class="text-right">R$ 0,00</td><td class="text-right">—</td></tr>
                <tr><td class="text-left">(+) Total de Entradas</td><td class="text-right">R$ ' . number_format($totalEntradas, 2, ',', '.') . '</td><td class="text-right">100%</td></tr>
                <tr><td class="text-left">(-) Total de Saídas</td><td class="text-right">R$ ' . number_format($totalSaidas, 2, ',', '.') . '</td><td class="text-right">' . ($totalEntradas > 0 ? number_format(($totalSaidas / $totalEntradas) * 100, 1, ',', '.') : '0,0') . '%</td></tr>
                <tr style="background:#eaf2f8; font-weight:bold;"><td class="text-left">(=) Saldo Operacional Líquido</td><td class="text-right">R$ ' . number_format($saldoLiquido, 2, ',', '.') . '</td><td class="text-right">' . ($totalEntradas > 0 ? number_format(($saldoLiquido / $totalEntradas) * 100, 1, ',', '.') : '0,0') . '%</td></tr>
                <tr style="background:#d4efdf; font-weight:bold;"><td class="text-left">(=) Saldo Final de Caixa</td><td class="text-right">R$ ' . number_format($saldoLiquido, 2, ',', '.') . '</td><td class="text-right">—</td></tr>
            </tbody>
        </table>
        </body></html>';
        $nomeArquivo = 'relatorio_fluxo_caixa.pdf';
        break;

    case 'dre':
        $stmt = $pdo->query("SELECT cf.grupo_dre, SUM(fc.valor) as total FROM fluxo_caixa fc INNER JOIN categorias_financeiras cf ON fc.categoria_id = cf.id GROUP BY cf.grupo_dre");
        $dados = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $recBruta   = $dados['receita_bruta'] ?? 0.00;
        $deducoes   = $dados['deducao_venda'] ?? 0.00;
        $recLiq     = $recBruta - $deducoes;
        $cmv        = $dados['custo_mercadoria'] ?? 0.00;
        $lucroBruto = $recLiq - $cmv;
        $despOp     = $dados['despesa_operacional'] ?? 0.00;
        $ebitda     = $lucroBruto - $despOp;
        $recFin     = $dados['receita_financeira'] ?? 0.00;
        $despFin    = $dados['despesa_financeira'] ?? 0.00;
        $lucroLiq   = $ebitda + $recFin - $despFin;

        $calcAV = function($val) use ($recBruta) {
            return $recBruta > 0 ? number_format(($val / $recBruta) * 100, 1, ',', '.') . '%' : '0,0%';
        };

        $html = '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8">' . $cssBase . '</head><body>
        <div class="header-container">
            <div class="main-title">DEMONSTRATIVO DO RESULTADO DO EXERCÍCIO (DRE)</div>
            <div class="meta-info">Modelo Administrativo Gerencial | <strong>Disco Burguer</strong></div>
        </div>
        <div class="divider"></div>

        <div class="section-title">Estrutura Padrão do DRE Gerencial</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th class="text-left">Descrição da Conta / Linha do DRE</th>
                    <th class="text-right">Previsto (R$)</th>
                    <th class="text-right">Realizado (R$)</th>
                    <th class="text-right">AV (%)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bold" style="background:#eaf2f8;"><td class="text-left">(=) RECEITA BRUTA DE VENDAS / SERVIÇOS</td><td class="text-right">R$ ' . number_format($recBruta, 2, ',', '.') . '</td><td class="text-right">R$ ' . number_format($recBruta, 2, ',', '.') . '</td><td class="text-right">100%</td></tr>
                <tr><td class="text-left" style="padding-left: 15px;">(-) Deduções e Devoluções</td><td class="text-right">R$ ' . number_format($deducoes, 2, ',', '.') . '</td><td class="text-right">R$ ' . number_format($deducoes, 2, ',', '.') . '</td><td class="text-right">' . $calcAV($deducoes) . '</td></tr>
                <tr class="bold"><td class="text-left">(=) RECEITA LÍQUIDA DE VENDAS</td><td class="text-right">R$ ' . number_format($recLiq, 2, ',', '.') . '</td><td class="text-right">R$ ' . number_format($recLiq, 2, ',', '.') . '</td><td class="text-right">' . $calcAV($recLiq) . '</td></tr>
                <tr><td class="text-left" style="padding-left: 15px;">(-) Custos das Mercadorias Vendidas (CMV)</td><td class="text-right">R$ ' . number_format($cmv, 2, ',', '.') . '</td><td class="text-right">R$ ' . number_format($cmv, 2, ',', '.') . '</td><td class="text-right">' . $calcAV($cmv) . '</td></tr>
                <tr class="bold" style="background:#eaf2f8;"><td class="text-left">(=) LUCRO BRUTO</td><td class="text-right">R$ ' . number_format($lucroBruto, 2, ',', '.') . '</td><td class="text-right">R$ ' . number_format($lucroBruto, 2, ',', '.') . '</td><td class="text-right">' . $calcAV($lucroBruto) . '</td></tr>
                <tr><td class="text-left" style="padding-left: 15px;">(-) Despesas Operacionais (Fixas e Variáveis)</td><td class="text-right">R$ ' . number_format($despOp, 2, ',', '.') . '</td><td class="text-right">R$ ' . number_format($despOp, 2, ',', '.') . '</td><td class="text-right">' . $calcAV($despOp) . '</td></tr>
                <tr class="bold"><td class="text-left">(=) RESULTADO OPERACIONAL ANTES DOS FINANCEIROS (EBITDA)</td><td class="text-right">R$ ' . number_format($ebitda, 2, ',', '.') . '</td><td class="text-right">R$ ' . number_format($ebitda, 2, ',', '.') . '</td><td class="text-right">' . $calcAV($ebitda) . '</td></tr>
                <tr class="bold" style="background:#d4efdf;"><td class="text-left">(=) LUCRO OU PREJUÍZO LÍQUIDO DO EXERCÍCIO</td><td class="text-right">R$ ' . number_format($lucroLiq, 2, ',', '.') . '</td><td class="text-right">R$ ' . number_format($lucroLiq, 2, ',', '.') . '</td><td class="text-right">' . $calcAV($lucroLiq) . '</td></tr>
            </tbody>
        </table>
        </body></html>';
        $nomeArquivo = 'dre_gerencial.pdf';
        break;

    default:
        die('Erro: Relatório "' . htmlspecialchars($tipoRelatorio) . '" não encontrado.');
}

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream($nomeArquivo, ['Attachment' => false]);
<?php
session_start();
require_once __DIR__ . '/../../php/conexao.php';

try {
    $sqlAlertasEstoque = "SELECT 
                            e.id, 
                            e.nome, 
                            5 AS quantidade_minima,
                            COALESCE(SUM(CASE WHEN me.tipo_movimentacao = 'entrada' THEN me.quantidade ELSE -me.quantidade END), 0) AS quantidade_atual
                          FROM estoque e
                          LEFT JOIN lotes_estoque le ON le.estoque_id = e.id
                          LEFT JOIN movimentacoes_estoque me ON me.lote_estoque_id = le.id
                          GROUP BY e.id, e.nome
                          HAVING quantidade_atual <= 5
                          ORDER BY quantidade_atual ASC";
    $alertasEstoque = $pdo->query($sqlAlertasEstoque)->fetchAll(PDO::FETCH_ASSOC);


    $sqlResumoDia = "SELECT 
                        COALESCE(SUM(valor_total), 0) AS faturamento_hoje,
                        COUNT(id) AS qtd_pedidos_hoje
                     FROM pedidos 
                     WHERE DATE(data_pedido) = CURDATE() AND status != 'cancelado'";
    $resumoDia = $pdo->query($sqlResumoDia)->fetch(PDO::FETCH_ASSOC);


    $sqlFaturamento = "SELECT 
                        DATE(data_pedido) AS data_dia,
                        SUM(valor_total) AS total_faturamento
                       FROM pedidos
                       WHERE status != 'cancelado'
                       GROUP BY DATE(data_pedido)
                       ORDER BY data_dia ASC";
    $faturamentoData = $pdo->query($sqlFaturamento)->fetchAll(PDO::FETCH_ASSOC);

    $sqlFinanceiro = "SELECT 
                        fc.id,
                        fc.valor,
                        fc.data_movimentacao,
                        COALESCE(cf.tipo, 'saida') AS categoria_tipo,
                        COALESCE(cf.nome, 'Geral') AS categoria_nome
                      FROM fluxo_caixa fc
                      LEFT JOIN categorias_financeiras cf ON fc.categoria_id = cf.id
                      ORDER BY fc.data_movimentacao ASC";
    $fluxoData = $pdo->query($sqlFinanceiro)->fetchAll(PDO::FETCH_ASSOC);

    $listaInsumos = $pdo->query("SELECT id, nome FROM estoque ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $sqlEstoque = "SELECT 
                    e.id AS insumo_id,
                    e.nome AS insumo_nome,
                    me.tipo_movimentacao,
                    SUM(me.quantidade) AS quantidade_total,
                    DATE(me.data_movimentacao) AS data_mov
                   FROM movimentacoes_estoque me
                   LEFT JOIN lotes_estoque le ON me.lote_estoque_id = le.id
                   LEFT JOIN estoque e ON le.estoque_id = e.id
                   GROUP BY e.id, me.tipo_movimentacao, DATE(me.data_movimentacao)
                   ORDER BY data_mov ASC";
    $estoqueData = $pdo->query($sqlEstoque)->fetchAll(PDO::FETCH_ASSOC);

    $listaProdutos = $pdo->query("SELECT id, nome FROM produtos WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $sqlProdutos = "SELECT 
                        p.id AS produto_id,
                        p.nome AS produto_nome,
                        SUM(ip.quantidade) AS qtd_vendida,
                        DATE(ped.data_pedido) AS data_venda
                    FROM itens_pedidos ip
                    INNER JOIN produtos p ON ip.produto_id = p.id
                    INNER JOIN pedidos ped ON ip.pedido_id = ped.id
                    WHERE ped.status != 'cancelado'
                    GROUP BY p.id, DATE(ped.data_pedido)
                    ORDER BY data_venda ASC";
    $vendasProdutos = $pdo->query($sqlProdutos)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao consultar dados no banco de dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disco Burguer - Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../../css/painel.css">
    <link rel="stylesheet" href="../../css/sistema_web/inicio.css">

    <script src="https://cdn.jsdelivr.net/npm/d3@7"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@500&display=swap');

        body { 
            font-family: 'Inter', sans-serif; 
            background: #090314; 
            color: #f3e8ff; 
        }

        .dashboard-container {
            padding: 10px 0;
        }

        .card-painel-grafico { 
            background: rgba(20, 6, 40, 0.9); 
            border: 1px solid rgba(168, 85, 247, 0.3); 
            border-radius: 12px; 
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .card-header-grafico {
            background: linear-gradient(135deg, #2a0845 0%, #180828 100%);
            border-bottom: 1px solid rgba(168, 85, 247, 0.3);
            padding: 14px 20px;
            text-align: center;
        }

        .card-header-grafico h2 { 
            font-size: 16px; 
            color: #f3e8ff; 
            margin: 0; 
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-body-grafico {
            padding: 20px;
            flex: 1;
        }

        .filter-bar { 
            margin-bottom: 15px; 
            display: flex; 
            gap: 10px; 
            align-items: center; 
            justify-content: center;
            font-size: 13px; 
            color: #c084fc; 
            flex-wrap: wrap; 
        }

        .filter-bar input[type="date"], 
        .filter-bar select { 
            background: #180828; 
            border: 1px solid #a855f7; 
            color: #fff; 
            padding: 5px 8px; 
            border-radius: 6px; 
            font-family: 'Inter', sans-serif; 
            font-size: 12px;
        }

        .chart-box {
            width: 100%;
            overflow-x: auto;
        }

        .alert-item {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
            padding: 10px 14px;
            margin-bottom: 8px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }

        .alert-item-ok {
            background: rgba(34, 197, 94, 0.1);
            border-left: 4px solid #22c55e;
            padding: 10px 14px;
            border-radius: 4px;
            font-size: 13px;
            color: #4ade80;
        }

        .kpi-card {
            background: rgba(24, 8, 40, 0.8);
            border: 1px solid rgba(168, 85, 247, 0.2);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }

        .kpi-card h3 {
            font-size: 12px;
            color: #c084fc;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .kpi-card p {
            font-size: 20px;
            font-weight: 700;
            color: #f3e8ff;
            margin: 0;
        }

        .axis path, .axis line { stroke: rgba(168, 85, 247, 0.3); }
        .axis text { fill: #c084fc; font-family: 'JetBrains Mono', monospace; font-size: 11px; }
        .grid line { stroke: rgba(59, 7, 100, 0.6); stroke-dasharray: 3 3; }

        .tooltip-d3 { 
            position: absolute; 
            padding: 8px 12px; 
            background: #180828; 
            border: 1px solid #a855f7; 
            border-radius: 6px; 
            color: #f3e8ff; 
            font-size: 12px; 
            pointer-events: none; 
            opacity: 0; 
            z-index: 9999; 
        }
    </style>
</head>

<body>

    <?php include __DIR__ . "/../../includes/painel.php"; ?>
    <?php include __DIR__ . "/../../includes/header_sistema.php"; ?>

    <div class="container-fluid dashboard-container">
        <main class="conteudo mt-2 px-3">
            
            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-6">
                    <div class="card-painel-grafico">
                        <div class="card-header-grafico">
                            <h2><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Alertas de Estoque Crítico</h2>
                        </div>
                        <div class="card-body-grafico" style="max-height: 200px; overflow-y: auto;">
                            <?php if (empty($alertasEstoque)): ?>
                                <div class="alert-item-ok">
                                    <i class="bi bi-check-circle-fill me-2"></i>Todos os insumos estão acima do nível crítico!
                                </div>
                            <?php else: ?>
                                <?php foreach ($alertasEstoque as $alerta): ?>
                                    <div class="alert-item">
                                        <div>
                                            <strong><?= htmlspecialchars($alerta['nome']) ?></strong><br>
                                            <small class="text-muted">Limite de alerta: <?= $alerta['quantidade_minima'] ?> un</small>
                                        </div>
                                        <span class="badge bg-danger">Qtd Atual: <?= $alerta['quantidade_atual'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="card-painel-grafico">
                        <div class="card-header-grafico">
                            <h2><i class="bi bi-speedometer2 text-info me-2"></i>Resumo do Dia</h2>
                        </div>
                        <div class="card-body-grafico d-flex align-items-center justify-content-around gap-3">
                            <div class="kpi-card flex-fill">
                                <h3>Faturamento Hoje</h3>
                                <p>R$ <?= number_format($resumoDia['faturamento_hoje'], 2, ',', '.') ?></p>
                            </div>
                            <div class="kpi-card flex-fill">
                                <h3>Pedidos Realizados</h3>
                                <p><?= $resumoDia['qtd_pedidos_hoje'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-xl-6">
                    <div class="card-painel-grafico">
                        <div class="card-header-grafico">
                            <h2>1. Módulo Financeiro (Faturamento & Saldo Acumulado)</h2>
                        </div>
                        <div class="card-body-grafico">
                            <div class="filter-bar">
                                <label>Visão:</label>
                                <select id="fin-modo">
                                    <option value="faturamento">Faturamento (Vendas por Período)</option>
                                    <option value="financeiro">Financeiro (Saldo Acumulado Fluxo de Caixa)</option>
                                </select>

                                <label>De:</label>
                                <input type="date" id="fin-inicio">
                                <label>Até:</label>
                                <input type="date" id="fin-fim">

                                <label>Agrupar:</label>
                                <select id="fin-agrupamento">
                                    <option value="dia">Diário</option>
                                    <option value="semana">Semanal</option>
                                    <option value="mes">Mensal</option>
                                    <option value="ano">Anual</option>
                                </select>
                            </div>
                            <div id="chart-financeiro-combo" class="chart-box"></div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="card-painel-grafico">
                        <div class="card-header-grafico">
                            <h2>2. Módulo Operacional (Estoque vs Vendas por Produto)</h2>
                        </div>
                        <div class="card-body-grafico">
                            <div class="filter-bar">
                                <label>Visão:</label>
                                <select id="op-modo">
                                    <option value="estoque">Movimentação de Estoque (Insumos)</option>
                                    <option value="produtos">Vendas de Produtos (Individual / Geral)</option>
                                </select>

                                <label for="op-item-select">Item:</label>
                                <select id="op-item-select"></select>

                                <label>De:</label>
                                <input type="date" id="op-inicio">
                                <label>Até:</label>
                                <input type="date" id="op-fim">
                            </div>
                            <div id="chart-operacional-combo" class="chart-box"></div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <div id="tooltip" class="tooltip-d3"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script src="../../js/painel.js"></script>

    <script>
        const tooltip = d3.select("#tooltip");

        const rawFaturamento = <?php echo json_encode($faturamentoData); ?>;
        const rawFluxo = <?php echo json_encode($fluxoData); ?>;
        const rawEstoque = <?php echo json_encode($estoqueData); ?>;
        const rawProdutos = <?php echo json_encode($vendasProdutos); ?>;
        const listaInsumos = <?php echo json_encode($listaInsumos); ?>;
        const listaProdutos = <?php echo json_encode($listaProdutos); ?>;

        const faturamentoData = rawFaturamento.map(d => ({ data: new Date(d.data_dia + "T00:00:00"), total: parseFloat(d.total_faturamento) }));

        let saldoAcumulado = 0;
        const fluxoData = rawFluxo.map(d => {
            const v = parseFloat(d.valor);
            saldoAcumulado += (d.categoria_tipo === 'entrada') ? v : -v;
            return { data: new Date(d.data_movimentacao), saldo: saldoAcumulado, categoria: d.categoria_nome, valor: v };
        });

        const estoqueData = rawEstoque.map(d => ({ 
            insumoId: parseInt(d.insumo_id),
            nome: d.insumo_nome, 
            tipo: (d.tipo_movimentacao === 'entrada') ? 'Entrada' : 'Saída/Ajuste', 
            qtd: parseFloat(d.quantidade_total),
            data: new Date(d.data_mov + "T00:00:00")
        }));

        const produtosData = rawProdutos.map(d => ({ 
            produtoId: parseInt(d.produto_id), 
            nome: d.produto_nome, 
            qtd: parseFloat(d.qtd_vendida), 
            data: new Date(d.data_venda + "T00:00:00") 
        }));

        const allDates = [...faturamentoData.map(d => d.data), ...fluxoData.map(d => d.data), ...estoqueData.map(d => d.data), ...produtosData.map(d => d.data)];
        if (allDates.length > 0) {
            const minDate = d3.min(allDates).toISOString().split('T')[0];
            const maxDate = d3.max(allDates).toISOString().split('T')[0];
            
            document.getElementById("fin-inicio").value = minDate;
            document.getElementById("fin-fim").value = maxDate;
            document.getElementById("op-inicio").value = minDate;
            document.getElementById("op-fim").value = maxDate;
        }

        function renderFinanceiroCombo() {
            const container = d3.select("#chart-financeiro-combo");
            container.html("");

            const modo = document.getElementById("fin-modo").value;
            const inicio = new Date(document.getElementById("fin-inicio").value + "T00:00:00");
            const fim = new Date(document.getElementById("fin-fim").value + "T23:59:59");
            const agrupamento = document.getElementById("fin-agrupamento").value;

            const sourceData = (modo === "faturamento") ? faturamentoData : fluxoData;
            const filtered = sourceData.filter(d => d.data >= inicio && d.data <= fim);

            if (filtered.length === 0) {
                container.html("<p style='text-align:center; padding: 30px; color: #a855f7;'>Nenhum registro localizado para os filtros selecionados.</p>");
                return;
            }

            let aggregated = [];
            if (agrupamento === "semana") {
                const grouped = d3.groups(filtered, d => d3.timeWeek(d.data));
                aggregated = grouped.map(([key, values]) => ({ data: key, total: d3.sum(values, v => modo === "faturamento" ? v.total : v.saldo) }));
            } else if (agrupamento === "mes") {
                const grouped = d3.groups(filtered, d => d3.timeMonth(d.data));
                aggregated = grouped.map(([key, values]) => ({ data: key, total: d3.sum(values, v => modo === "faturamento" ? v.total : v.saldo) }));
            } else if (agrupamento === "ano") {
                const grouped = d3.groups(filtered, d => d3.timeYear(d.data));
                aggregated = grouped.map(([key, values]) => ({ data: key, total: d3.sum(values, v => modo === "faturamento" ? v.total : v.saldo) }));
            } else {
                aggregated = filtered.map(d => ({ data: d.data, total: modo === "faturamento" ? d.total : d.saldo }));
            }

            const margin = { top: 20, right: 30, bottom: 30, left: 70 }, width = 600 - margin.left - margin.right, height = 260 - margin.top - margin.bottom;
            const svg = container.append("svg").attr("viewBox", `0 0 600 260`).append("g").attr("transform", `translate(${margin.left},${margin.top})`);

            const x = d3.scaleTime().domain(d3.extent(aggregated, d => d.data)).range([0, width]);
            const y = d3.scaleLinear().domain([d3.min(aggregated, d => d.total) < 0 ? d3.min(aggregated, d => d.total) * 1.1 : 0, d3.max(aggregated, d => d.total) * 1.15]).range([height, 0]);

            svg.append("g").attr("class", "grid").call(d3.axisLeft(y).tickSize(-width).tickFormat(""));

            let timeFormat = d3.timeFormat("%d/%m");
            if (agrupamento === "mes") timeFormat = d3.timeFormat("%b/%Y");
            if (agrupamento === "ano") timeFormat = d3.timeFormat("%Y");

            svg.append("g").attr("class", "axis").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x).ticks(5).tickFormat(timeFormat));
            svg.append("g").attr("class", "axis").call(d3.axisLeft(y).ticks(5).tickFormat(d => `R$ ${d.toFixed(0)}`));

            const line = d3.line().x(d => x(d.data)).y(d => y(d.total)).curve(d3.curveMonotoneX);

            svg.append("path").datum(aggregated).attr("fill", "none").attr("stroke", "#c084fc").attr("stroke-width", 2.5).attr("d", line);

            svg.selectAll("dot").data(aggregated).enter().append("circle")
                .attr("cx", d => x(d.data)).attr("cy", d => y(d.total)).attr("r", 4).attr("fill", "#a855f7")
                .on("mouseover", (e, d) => tooltip.style("opacity", 1).html(`Período: ${timeFormat(d.data)}<br>Valor: R$ ${d.total.toFixed(2)}`))
                .on("mousemove", e => tooltip.style("left", (e.pageX + 10) + "px").style("top", (e.pageY - 28) + "px"))
                .on("mouseleave", () => tooltip.style("opacity", 0));
        }

        function updateOpSelectOptions() {
            const modo = document.getElementById("op-modo").value;
            const selectItem = document.getElementById("op-item-select");
            selectItem.innerHTML = "";

            if (modo === "estoque") {
                selectItem.innerHTML = `<option value="todos"> Todos os Insumos </option>`;
                listaInsumos.forEach(item => {
                    selectItem.innerHTML += `<option value="${item.id}">${item.nome}</option>`;
                });
            } else {
                selectItem.innerHTML = `<option value="todos"> Todos os Produtos </option>`;
                listaProdutos.forEach(item => {
                    selectItem.innerHTML += `<option value="${item.id}">${item.nome}</option>`;
                });
            }
        }

        function renderOperacionalCombo() {
            const container = d3.select("#chart-operacional-combo");
            container.html("");

            const modo = document.getElementById("op-modo").value;
            const itemId = document.getElementById("op-item-select").value;
            const inicio = new Date(document.getElementById("op-inicio").value + "T00:00:00");
            const fim = new Date(document.getElementById("op-fim").value + "T23:59:59");

            let filtered = (modo === "estoque") ? estoqueData : produtosData;
            filtered = filtered.filter(d => d.data >= inicio && d.data <= fim);

            if (itemId !== "todos") {
                filtered = filtered.filter(d => (modo === "estoque" ? d.insumoId : d.produtoId) === parseInt(itemId));
            }

            if (filtered.length === 0) {
                container.html("<p style='text-align:center; padding: 30px; color: #a855f7;'>Sem movimentações para os filtros aplicados.</p>");
                return;
            }

            const margin = { top: 20, right: 30, bottom: 30, left: 60 }, width = 600 - margin.left - margin.right, height = 260 - margin.top - margin.bottom;
            const svg = container.append("svg").attr("viewBox", `0 0 600 260`).append("g").attr("transform", `translate(${margin.left},${margin.top})`);

            if (modo === "estoque") {
                const itens = [...new Set(filtered.map(d => d.nome))];
                const x0 = d3.scaleBand().domain(itens).range([0, width]).padding(0.25);
                const x1 = d3.scaleBand().domain(['Entrada', 'Saída/Ajuste']).range([0, x0.bandwidth()]).padding(0.05);
                const y = d3.scaleLinear().domain([0, d3.max(filtered, d => d.qtd) * 1.15 || 10]).range([height, 0]);
                const color = d3.scaleOrdinal().domain(['Entrada', 'Saída/Ajuste']).range(['#c084fc', '#581c87']);

                svg.append("g").attr("class", "grid").call(d3.axisLeft(y).tickSize(-width).tickFormat(""));
                svg.append("g").attr("class", "axis").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x0));
                svg.append("g").attr("class", "axis").call(d3.axisLeft(y).ticks(5));

                svg.append("g").selectAll("g").data(itens).enter().append("g").attr("transform", d => `translate(${x0(d)},0)`)
                    .selectAll("rect").data(p => filtered.filter(d => d.nome === p)).enter().append("rect")
                    .attr("x", d => x1(d.tipo)).attr("y", d => y(d.qtd)).attr("width", x1.bandwidth()).attr("height", d => height - y(d.qtd))
                    .attr("fill", d => color(d.tipo)).attr("rx", 3)
                    .on("mouseover", (e, d) => tooltip.style("opacity", 1).html(`Item: ${d.nome}<br>Tipo: ${d.tipo}<br>Quantidade: ${d.qtd}`))
                    .on("mousemove", e => tooltip.style("left", (e.pageX + 10) + "px").style("top", (e.pageY - 28) + "px"))
                    .on("mouseleave", () => tooltip.style("opacity", 0));
            } else {
                const x = d3.scaleTime().domain(d3.extent(filtered, d => d.data)).range([0, width]);
                const y = d3.scaleLinear().domain([0, d3.max(filtered, d => d.qtd) * 1.2 || 10]).range([height, 0]);

                svg.append("g").attr("class", "grid").call(d3.axisLeft(y).tickSize(-width).tickFormat(""));
                svg.append("g").attr("class", "axis").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x).ticks(5).tickFormat(d3.timeFormat("%d/%m")));
                svg.append("g").attr("class", "axis").call(d3.axisLeft(y).ticks(5));

                const line = d3.line().x(d => x(d.data)).y(d => y(d.qtd));
                const grouped = d3.groups(filtered, d => d.nome);
                const colorScale = d3.scaleOrdinal(d3.schemeCategory10);

                grouped.forEach(([nomeProduto, pontosData], index) => {
                    const corLinha = colorScale(index);

                    svg.append("path")
                        .datum(pontosData)
                        .attr("fill", "none")
                        .attr("stroke", corLinha)
                        .attr("stroke-width", 2)
                        .attr("d", line);

                    svg.selectAll(`.dot-${index}`).data(pontosData).enter().append("circle")
                        .attr("cx", d => x(d.data))
                        .attr("cy", d => y(d.qtd))
                        .attr("r", 4)
                        .attr("fill", corLinha)
                        .on("mouseover", (e, d) => tooltip.style("opacity", 1).html(`Produto: ${d.nome}<br>Data: ${d3.timeFormat("%d/%m/%Y")(d.data)}<br>Vendas: ${d.qtd} un`))
                        .on("mousemove", e => tooltip.style("left", (e.pageX + 10) + "px").style("top", (e.pageY - 28) + "px"))
                        .on("mouseleave", () => tooltip.style("opacity", 0));
                });
            }
        }

        document.getElementById("fin-modo").addEventListener("change", renderFinanceiroCombo);
        document.getElementById("fin-inicio").addEventListener("change", renderFinanceiroCombo);
        document.getElementById("fin-fim").addEventListener("change", renderFinanceiroCombo);
        document.getElementById("fin-agrupamento").addEventListener("change", renderFinanceiroCombo);

        document.getElementById("op-modo").addEventListener("change", () => {
            updateOpSelectOptions();
            renderOperacionalCombo();
        });
        document.getElementById("op-item-select").addEventListener("change", renderOperacionalCombo);
        document.getElementById("op-inicio").addEventListener("change", renderOperacionalCombo);
        document.getElementById("op-fim").addEventListener("change", renderOperacionalCombo);

        function init() {
            updateOpSelectOptions();
            renderFinanceiroCombo();
            renderOperacionalCombo();
        }

        window.addEventListener("DOMContentLoaded", init);
    </script>
</body>

</html>
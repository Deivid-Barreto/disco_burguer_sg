<?php
$host = '127.0.0.1';
$dbname = 'discoburguer';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $sqlFaturamento = "SELECT 
                        DATE(data_pedido) AS data_dia,
                        SUM(valor_total) AS total_faturamento
                       FROM pedidos
                       WHERE status != 'cancelado'
                       GROUP BY DATE(data_pedido)
                       ORDER BY data_dia ASC";
    $faturamentoData = $pdo->query($sqlFaturamento)->fetchAll();

    $sqlFinanceiro = "SELECT 
                        fc.id,
                        fc.valor,
                        fc.data_movimentacao,
                        COALESCE(cf.tipo, 'saida') AS categoria_tipo,
                        COALESCE(cf.nome, 'Geral') AS categoria_nome
                      FROM fluxo_caixa fc
                      LEFT JOIN categorias_financeiras cf ON fc.categoria_id = cf.id
                      ORDER BY fc.data_movimentacao ASC";
    $fluxoData = $pdo->query($sqlFinanceiro)->fetchAll();

    $listaInsumos = $pdo->query("SELECT id, nome FROM estoque ORDER BY nome ASC")->fetchAll();
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
    $estoqueData = $pdo->query($sqlEstoque)->fetchAll();

    $listaProdutos = $pdo->query("SELECT id, nome FROM produtos WHERE ativo = 1 ORDER BY nome ASC")->fetchAll();
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
    $vendasProdutos = $pdo->query($sqlProdutos)->fetchAll();

} catch (PDOException $e) {
    die("Erro ao conectar no banco de dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Integrado — Disco Burguer</title>
  <script src="https://cdn.jsdelivr.net/npm/d3@7"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@500&display=swap');
    
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: #090314; color: #f3e8ff; padding: 24px; min-height: 100vh; }
    
    .container { max-width: 1000px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px; }
    
    header { background: rgba(20,6,40,0.9); border: 1px solid rgba(168,85,247,0.3); border-radius: 12px; padding: 20px; }
    header h1 { font-size: 20px; color: #f3e8ff; }
    
    .card { background: rgba(20,6,40,0.9); border: 1px solid rgba(168,85,247,0.3); border-radius: 12px; padding: 20px; }
    .card h2 { font-size: 16px; color: #e9d5ff; margin-bottom: 12px; }
    
    .filter-bar { margin-bottom: 15px; display: flex; gap: 12px; align-items: center; font-size: 13px; color: #c084fc; flex-wrap: wrap; }
    input[type="date"], select { background: #180828; border: 1px solid #a855f7; color: #fff; padding: 6px 10px; border-radius: 6px; font-family: 'Inter', sans-serif; }
    
    .axis path, .axis line { stroke: rgba(168, 85, 247, 0.3); }
    .axis text { fill: #c084fc; font-family: 'JetBrains Mono', monospace; font-size: 11px; }
    .grid line { stroke: rgba(59, 7, 100, 0.6); stroke-dasharray: 3 3; }
    
    .tooltip { position: absolute; padding: 8px 12px; background: #180828; border: 1px solid #a855f7; border-radius: 6px; color: #f3e8ff; font-size: 12px; pointer-events: none; opacity: 0; z-index: 10; }
  </style>
</head>
<body>

  <div class="container">
    <header>
      <h1>Dashboard Operacional e Financeiro — Disco Burguer</h1>
    </header>

    <div class="card">
      <h2>1. Módulo Financeiro (Faturamento & Saldo Acumulado)</h2>
      <div class="filter-bar">
        <label>Modo de Visão:</label>
        <select id="fin-modo">
          <option value="faturamento">Faturamento (Vendas por Período)</option>
          <option value="financeiro">Financeiro (Saldo Acumulado Fluxo de Caixa)</option>
        </select>

        <label>De:</label><input type="date" id="fin-inicio">
        <label>Até:</label><input type="date" id="fin-fim">
        
        <label>Agrupamento:</label>
        <select id="fin-agrupamento">
          <option value="dia">Diário</option>
          <option value="semana">Semanal</option>
          <option value="mes">Mensal</option>
          <option value="ano">Anual</option>
        </select>
      </div>
      <div id="chart-financeiro-combo"></div>
    </div>

    <div class="card">
      <h2>2. Módulo Operacional (Estoque vs. Vendas por Produto)</h2>
      <div class="filter-bar">
        <label>Visão:</label>
        <select id="op-modo">
          <option value="estoque">Movimentação de Estoque (Insumos)</option>
          <option value="produtos">Vendas de Produtos (Individual / Geral)</option>
        </select>

        <label for="op-item-select">Item:</label>
        <select id="op-item-select"></select>

        <label>De:</label><input type="date" id="op-inicio">
        <label>Até:</label><input type="date" id="op-fim">
      </div>
      <div id="chart-operacional-combo"></div>
    </div>
  </div>

  <div id="tooltip" class="tooltip"></div>

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

      const margin = { top: 20, right: 30, bottom: 30, left: 70 }, width = 920 - margin.left - margin.right, height = 260 - margin.top - margin.bottom;
      const svg = container.append("svg").attr("viewBox", `0 0 920 260`).append("g").attr("transform", `translate(${margin.left},${margin.top})`);

      const x = d3.scaleTime().domain(d3.extent(aggregated, d => d.data)).range([0, width]);
      const y = d3.scaleLinear().domain([d3.min(aggregated, d => d.total) < 0 ? d3.min(aggregated, d => d.total) * 1.1 : 0, d3.max(aggregated, d => d.total) * 1.15]).range([height, 0]);

      svg.append("g").attr("class", "grid").call(d3.axisLeft(y).tickSize(-width).tickFormat(""));

      let timeFormat = d3.timeFormat("%d/%m");
      if (agrupamento === "mes") timeFormat = d3.timeFormat("%b/%Y");
      if (agrupamento === "ano") timeFormat = d3.timeFormat("%Y");

      svg.append("g").attr("class", "axis").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x).ticks(6).tickFormat(timeFormat));
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
        selectItem.innerHTML = `<option value="todos">-- Todos os Insumos de Estoque --</option>`;
        listaInsumos.forEach(item => {
          selectItem.innerHTML += `<option value="${item.id}">${item.nome}</option>`;
        });
      } else {
        selectItem.innerHTML = `<option value="todos">-- Todos os Produtos --</option>`;
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

      const margin = { top: 20, right: 30, bottom: 30, left: 60 }, width = 920 - margin.left - margin.right, height = 260 - margin.top - margin.bottom;
      const svg = container.append("svg").attr("viewBox", `0 0 920 260`).append("g").attr("transform", `translate(${margin.left},${margin.top})`);

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
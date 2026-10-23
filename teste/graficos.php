<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Financeiro & Operacional — D3.js Puro</title>

  <!-- Biblioteca Principal: Apenas D3.js v7 -->
  <script src="https://cdn.jsdelivr.net/npm/d3@7"></script>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap');

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: radial-gradient(circle at 50% 0%, #1e0938 0%, #090314 100%);
      color: #f3e8ff;
      padding: 24px;
      min-height: 100vh;
    }

    .container {
      max-width: 1100px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    header {
      background: rgba(20, 6, 40, 0.8);
      border: 1px solid rgba(168, 85, 247, 0.3);
      border-radius: 16px;
      padding: 20px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
    }

    header h1 {
      font-size: 22px;
      font-weight: 700;
      color: #f3e8ff;
    }

    header p {
      font-size: 13px;
      color: #c084fc;
      margin-top: 4px;
    }

    .badge {
      font-family: 'JetBrains Mono', monospace;
      font-size: 12px;
      background: rgba(59, 7, 100, 0.8);
      border: 1px solid rgba(168, 85, 247, 0.4);
      color: #c084fc;
      padding: 6px 12px;
      border-radius: 8px;
    }

    .card {
      background: rgba(20, 6, 40, 0.8);
      border: 1px solid rgba(168, 85, 247, 0.3);
      border-radius: 16px;
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid rgba(168, 85, 247, 0.15);
      padding-bottom: 12px;
      flex-wrap: wrap;
      gap: 12px;
    }

    .card-title h2 {
      font-size: 16px;
      font-weight: 700;
      color: #e9d5ff;
    }

    .card-title p {
      font-size: 12px;
      color: #a855f7;
      margin-top: 2px;
    }

    .filters {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12px;
      color: #c084fc;
    }

    .filters input[type="date"],
    .filters select {
      background: #180828;
      border: 1px solid rgba(168, 85, 247, 0.4);
      color: #f3e8ff;
      padding: 6px 10px;
      border-radius: 6px;
      font-family: 'Inter', sans-serif;
      font-size: 12px;
      outline: none;
    }

    .filters input[type="date"]:focus,
    .filters select:focus {
      border-color: #a855f7;
    }

    .chart-svg {
      width: 100%;
      height: 300px;
      overflow: visible;
    }

    /* Estilização dos Eixos no D3 */
    .axis path,
    .axis line {
      stroke: rgba(168, 85, 247, 0.3);
    }

    .axis text {
      fill: #c084fc;
      font-family: 'JetBrains Mono', monospace;
      font-size: 11px;
    }

    .grid line {
      stroke: rgba(59, 7, 100, 0.6);
      stroke-dasharray: 3 3;
    }

    .grid path {
      stroke-width: 0;
    }

    /* Tooltip Customizado D3 */
    .d3-tooltip {
      position: absolute;
      padding: 8px 12px;
      background: #180828;
      border: 1px solid #a855f7;
      border-radius: 6px;
      color: #f3e8ff;
      font-size: 12px;
      pointer-events: none;
      opacity: 0;
      transition: opacity 0.15s ease;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
      z-index: 100;
      white-space: pre-line;
    }
  </style>
</head>
<body>

  <div class="container">

    <header>
      <div>
        <h1>Dashboard Financeiro & Operacional — Disco Burguer</h1>
        <p>Construído estritamente com D3.js (v7), HTML5 e CSS3</p>
      </div>
      <div class="badge">Tecnologia: D3.js Pure</div>
    </header>

    <!-- SEÇÃO 1: FINANCEIRO -->
    <section class="card">
      <div class="card-header">
        <div class="card-title">
          <h2>1. Financeiro — Evolução do Fluxo de Caixa</h2>
          <p>Saldo acumulado no tempo (Entradas - Saídas)</p>
        </div>
      </div>
      <div id="chart-financeiro"></div>
    </section>

    <!-- SEÇÃO 2: FATURAMENTO (COM FILTRO) -->
    <section class="card">
      <div class="card-header">
        <div class="card-title">
          <h2>2. Faturamento (Com Filtro de Período)</h2>
          <p>Receita total de vendas por data</p>
        </div>
        <div class="filters">
          <label for="filtro-inicio">De:</label>
          <input type="date" id="filtro-inicio" value="2026-10-01">
          <label for="filtro-fim">Até:</label>
          <input type="date" id="filtro-fim" value="2026-10-15">
        </div>
      </div>
      <div id="chart-faturamento"></div>
    </section>

    <!-- SEÇÃO 3: ESTOQUE (ENTRADAS E SAÍDAS) -->
    <section class="card">
      <div class="card-header">
        <div class="card-title">
          <h2>3. Estoque — Entradas e Saídas por Produto</h2>
          <p>Comparativo total de movimentações físicas por item</p>
        </div>
      </div>
      <div id="chart-estoque"></div>
    </section>

    <!-- SEÇÃO 4: PRODUTOS (GERAL E FILTRO INDIVIDUAL) -->
    <section class="card">
      <div class="card-header">
        <div class="card-title">
          <h2>4. Produtos — Movimentação Temporal</h2>
          <p>Histórico de saídas de produtos no tempo</p>
        </div>
        <div class="filters">
          <label for="select-produto">Produto:</label>
          <select id="select-produto">
            <option value="todos">Todos os Produtos</option>
            <option value="1">Disco Burguer Clássico</option>
            <option value="2">Smash Double Bacon</option>
            <option value="3">Batata Rústica Média</option>
            <option value="4">Refrigerante Lata 350ml</option>
          </select>
        </div>
      </div>
      <div id="chart-produtos"></div>
    </section>

  </div>

  <!-- Div Tooltip Compartilhada -->
  <div id="tooltip" class="d3-tooltip"></div>s

  <script>
    // DADOS DAS TABELAS
    const fluxoCaixaData = [
      { id: 1, valor: 120.50, tipo: 'entrada', data: new Date('2026-10-01') },
      { id: 2, valor: 85.00,  tipo: 'entrada', data: new Date('2026-10-02') },
      { id: 3, valor: 350.00, tipo: 'saida',   data: new Date('2026-10-03') },
      { id: 4, valor: 210.00, tipo: 'entrada', data: new Date('2026-10-04') },
      { id: 5, valor: 420.00, tipo: 'saida',   data: new Date('2026-10-05') },
      { id: 6, valor: 150.00, tipo: 'entrada', data: new Date('2026-10-06') },
      { id: 7, valor: 310.00, tipo: 'entrada', data: new Date('2026-10-07') },
      { id: 8, valor: 280.00, tipo: 'saida',   data: new Date('2026-10-08') },
      { id: 9, valor: 95.00,  tipo: 'entrada', data: new Date('2026-10-09') },
      { id: 10, valor: 450.00, tipo: 'entrada', data: new Date('2026-10-10') }
    ];

    const faturamentoData = [
      { data: new Date('2026-10-01'), total: 120.50 },
      { data: new Date('2026-10-02'), total: 85.00 },
      { data: new Date('2026-10-04'), total: 210.00 },
      { data: new Date('2026-10-06'), total: 150.00 },
      { data: new Date('2026-10-07'), total: 310.00 },
      { data: new Date('2026-10-09'), total: 95.00 },
      { data: new Date('2026-10-10'), total: 450.00 }
    ];

    const estoqueGeralData = [
      { produto: 'Disco Burguer', tipo: 'Entrada', qtd: 100 },
      { produto: 'Disco Burguer', tipo: 'Saída', qtd: 37 },
      { produto: 'Smash Bacon', tipo: 'Entrada', qtd: 80 },
      { produto: 'Smash Bacon', tipo: 'Saída', qtd: 33 },
      { produto: 'Batata Rústica', tipo: 'Entrada', qtd: 150 },
      { produto: 'Batata Rústica', tipo: 'Saída', qtd: 40 },
      { produto: 'Refrigerante', tipo: 'Entrada', qtd: 200 },
      { produto: 'Refrigerante', tipo: 'Saída', qtd: 65 }
    ];

    const produtosMovimentacaoData = [
      { produtoId: 1, produto: 'Disco Burguer Clássico', qtd: 12, data: new Date('2026-10-01') },
      { produtoId: 2, produto: 'Smash Double Bacon', qtd: 15, data: new Date('2026-10-01') },
      { produtoId: 3, produto: 'Batata Rústica Média', qtd: 40, data: new Date('2026-10-02') },
      { produtoId: 4, produto: 'Refrigerante Lata 350ml', qtd: 65, data: new Date('2026-10-02') },
      { produtoId: 1, produto: 'Disco Burguer Clássico', qtd: 25, data: new Date('2026-10-03') },
      { produtoId: 2, produto: 'Smash Double Bacon', qtd: 18, data: new Date('2026-10-03') }
    ];

    const tooltip = d3.select("#tooltip");

    // 1. GRÁFICO DE LINHA - FINANCEIRO
    function renderFinanceiro() {
      const container = d3.select("#chart-financeiro");
      container.html("");

      let saldo = 0;
      const dataProcessed = fluxoCaixaData.map(d => {
        saldo += d.tipo === 'entrada' ? d.valor : -d.valor;
        return { data: d.data, saldo: saldo };
      });

      const margin = { top: 20, right: 30, bottom: 30, left: 50 };
      const width = container.node().clientWidth - margin.left - margin.right;
      const height = 280 - margin.top - margin.bottom;

      const svg = container.append("svg")
        .attr("class", "chart-svg")
        .attr("viewBox", `0 0 ${width + margin.left + margin.right} ${height + margin.top + margin.bottom}`)
        .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

      const x = d3.scaleTime()
        .domain(d3.extent(dataProcessed, d => d.data))
        .range([0, width]);

      const y = d3.scaleLinear()
        .domain([d3.min(dataProcessed, d => d.saldo) - 50, d3.max(dataProcessed, d => d.saldo) + 50])
        .range([height, 0]);

      svg.append("g")
        .attr("class", "grid")
        .call(d3.axisLeft(y).tickSize(-width).tickFormat(""));

      svg.append("g")
        .attr("class", "axis")
        .attr("transform", `translate(0,${height})`)
        .call(d3.axisBottom(x).ticks(5).tickFormat(d3.timeFormat("%d/%m")));

      svg.append("g")
        .attr("class", "axis")
        .call(d3.axisLeft(y).ticks(5).tickFormat(d => `R$ ${d}`));

      // Gradiente D3
      const gradient = svg.append("defs")
        .append("linearGradient")
        .attr("id", "area-gradient")
        .attr("x1", "0%").attr("y1", "0%")
        .attr("x2", "0%").attr("y2", "100%");

      gradient.append("stop").attr("offset", "0%").attr("stop-color", "#a855f7").attr("stop-opacity", 0.4);
      gradient.append("stop").attr("offset", "100%").attr("stop-color", "#a855f7").attr("stop-opacity", 0.0);

      const area = d3.area()
        .x(d => x(d.data))
        .y0(height)
        .y1(d => y(d.saldo));

      svg.append("path")
        .datum(dataProcessed)
        .attr("fill", "url(#area-gradient)")
        .attr("d", area);

      const line = d3.line()
        .x(d => x(d.data))
        .y(d => y(d.saldo));

      svg.append("path")
        .datum(dataProcessed)
        .attr("fill", "none")
        .attr("stroke", "#c084fc")
        .attr("stroke-width", 2.5)
        .attr("d", line);

      svg.selectAll("dot")
        .data(dataProcessed)
        .enter().append("circle")
        .attr("cx", d => x(d.data))
        .attr("cy", d => y(d.saldo))
        .attr("r", 4)
        .attr("fill", "#a855f7")
        .on("mouseover", (event, d) => {
          tooltip.style("opacity", 1)
            .html(`Data: ${d3.timeFormat("%d/%m/%Y")(d.data)}<br>Saldo: R$ ${d.saldo.toFixed(2)}`);
        })
        .on("mousemove", event => {
          tooltip.style("left", (event.pageX + 10) + "px").style("top", (event.pageY - 28) + "px");
        })
        .on("mouseleave", () => tooltip.style("opacity", 0));
    }

    // GRÁFICO DE BARRAS - FATURAMENTO
    function renderFaturamento() {
      const container = d3.select("#chart-faturamento");
      container.html("");

      const inicio = new Date(document.getElementById("filtro-inicio").value);
      const fim = new Date(document.getElementById("filtro-fim").value);

      const filtered = faturamentoData.filter(d => d.data >= inicio && d.data <= fim);

      if (filtered.length === 0) {
        container.html("<p style='text-align:center; padding: 40px; color: #a855f7; font-size: 13px;'>Nenhum faturamento encontrado neste período.</p>");
        return;
      }

      const margin = { top: 20, right: 30, bottom: 30, left: 50 };
      const width = container.node().clientWidth - margin.left - margin.right;
      const height = 280 - margin.top - margin.bottom;

      const svg = container.append("svg")
        .attr("class", "chart-svg")
        .attr("viewBox", `0 0 ${width + margin.left + margin.right} ${height + margin.top + margin.bottom}`)
        .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

      const x = d3.scaleBand()
        .domain(filtered.map(d => d3.timeFormat("%d/%m")(d.data)))
        .range([0, width])
        .padding(0.3);

      const y = d3.scaleLinear()
        .domain([0, d3.max(filtered, d => d.total) * 1.1])
        .range([height, 0]);

      svg.append("g").attr("class", "grid").call(d3.axisLeft(y).tickSize(-width).tickFormat(""));
      svg.append("g").attr("class", "axis").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x));
      svg.append("g").attr("class", "axis").call(d3.axisLeft(y).ticks(5).tickFormat(d => `R$ ${d}`));

      svg.selectAll("bar")
        .data(filtered)
        .enter().append("rect")
        .attr("x", d => x(d3.timeFormat("%d/%m")(d.data)))
        .attr("y", d => y(d.total))
        .attr("width", x.bandwidth())
        .attr("height", d => height - y(d.total))
        .attr("fill", "#a855f7")
        .attr("rx", 4)
        .on("mouseover", (event, d) => {
          tooltip.style("opacity", 1)
            .html(`Data: ${d3.timeFormat("%d/%m/%Y")(d.data)}<br>Faturamento: R$ ${d.total.toFixed(2)}`);
        })
        .on("mousemove", event => {
          tooltip.style("left", (event.pageX + 10) + "px").style("top", (event.pageY - 28) + "px");
        })
        .on("mouseleave", () => tooltip.style("opacity", 0));
    }

    // 3. GRÁFICO DE BARRAS AGRUPADAS - ESTOQUE
    function renderEstoque() {
      const container = d3.select("#chart-estoque");
      container.html("");

      const margin = { top: 20, right: 30, bottom: 40, left: 50 };
      const width = container.node().clientWidth - margin.left - margin.right;
      const height = 280 - margin.top - margin.bottom;

      const svg = container.append("svg")
        .attr("class", "chart-svg")
        .attr("viewBox", `0 0 ${width + margin.left + margin.right} ${height + margin.top + margin.bottom}`)
        .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

      const produtos = [...new Set(estoqueGeralData.map(d => d.produto))];

      const x0 = d3.scaleBand().domain(produtos).range([0, width]).padding(0.2);
      const x1 = d3.scaleBand().domain(['Entrada', 'Saída']).range([0, x0.bandwidth()]).padding(0.05);

      const y = d3.scaleLinear()
        .domain([0, d3.max(estoqueGeralData, d => d.qtd) * 1.1])
        .range([height, 0]);

      const color = d3.scaleOrdinal().domain(['Entrada', 'Saída']).range(['#c084fc', '#581c87']);

      svg.append("g").attr("class", "grid").call(d3.axisLeft(y).tickSize(-width).tickFormat(""));
      svg.append("g").attr("class", "axis").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x0));
      svg.append("g").attr("class", "axis").call(d3.axisLeft(y).ticks(5));

      svg.append("g")
        .selectAll("g")
        .data(produtos)
        .enter().append("g")
        .attr("transform", d => `translate(${x0(d)},0)`)
        .selectAll("rect")
        .data(p => estoqueGeralData.filter(d => d.produto === p))
        .enter().append("rect")
        .attr("x", d => x1(d.tipo))
        .attr("y", d => y(d.qtd))
        .attr("width", x1.bandwidth())
        .attr("height", d => height - y(d.qtd))
        .attr("fill", d => color(d.tipo))
        .attr("rx", 3)
        .on("mouseover", (event, d) => {
          tooltip.style("opacity", 1)
            .html(`Produto: ${d.produto}<br>Tipo: ${d.tipo}<br>Qtd: ${d.qtd} un`);
        })
        .on("mousemove", event => {
          tooltip.style("left", (event.pageX + 10) + "px").style("top", (event.pageY - 28) + "px");
        })
        .on("mouseleave", () => tooltip.style("opacity", 0));
    }

    // 4. GRÁFICO TEMPORAL - PRODUTOS
    function renderProdutos() {
      const container = d3.select("#chart-produtos");
      container.html("");

      const selectedId = document.getElementById("select-produto").value;
      const filtered = selectedId === "todos" 
        ? produtosMovimentacaoData 
        : produtosMovimentacaoData.filter(p => p.produtoId === parseInt(selectedId));

      const margin = { top: 20, right: 30, bottom: 30, left: 50 };
      const width = container.node().clientWidth - margin.left - margin.right;
      const height = 280 - margin.top - margin.bottom;

      const svg = container.append("svg")
        .attr("class", "chart-svg")
        .attr("viewBox", `0 0 ${width + margin.left + margin.right} ${height + margin.top + margin.bottom}`)
        .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

      const x = d3.scaleTime()
        .domain(d3.extent(produtosMovimentacaoData, d => d.data))
        .range([0, width]);

      const y = d3.scaleLinear()
        .domain([0, d3.max(produtosMovimentacaoData, d => d.qtd) * 1.2])
        .range([height, 0]);

      svg.append("g").attr("class", "grid").call(d3.axisLeft(y).tickSize(-width).tickFormat(""));
      svg.append("g").attr("class", "axis").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x).ticks(5).tickFormat(d3.timeFormat("%d/%m")));
      svg.append("g").attr("class", "axis").call(d3.axisLeft(y).ticks(5));

      const line = d3.line()
        .x(d => x(d.data))
        .y(d => y(d.qtd));

      const grouped = d3.groups(filtered, d => d.produto);

      grouped.forEach(([key, values]) => {
        svg.append("path")
          .datum(values)
          .attr("fill", "none")
          .attr("stroke", "#c084fc")
          .attr("stroke-width", 2)
          .attr("d", line);

        svg.selectAll("dot")
          .data(values)
          .enter().append("circle")
          .attr("cx", d => x(d.data))
          .attr("cy", d => y(d.qtd))
          .attr("r", 4)
          .attr("fill", "#a855f7")
          .on("mouseover", (event, d) => {
            tooltip.style("opacity", 1)
              .html(`Produto: ${d.produto}<br>Data: ${d3.timeFormat("%d/%m/%Y")(d.data)}<br>Saída: ${d.qtd} un`);
          })
          .on("mousemove", event => {
            tooltip.style("left", (event.pageX + 10) + "px").style("top", (event.pageY - 28) + "px");
          })
          .on("mouseleave", () => tooltip.style("opacity", 0));
      });
    }

    function renderAll() {
      renderFinanceiro();
      renderFaturamento();
      renderEstoque();
      renderProdutos();
    }

    // Eventos
    document.getElementById("filtro-inicio").addEventListener("change", renderFaturamento);
    document.getElementById("filtro-fim").addEventListener("change", renderFaturamento);
    document.getElementById("select-produto").addEventListener("change", renderProdutos);

    window.addEventListener("DOMContentLoaded", renderAll);
    window.addEventListener("resize", renderAll);
  </script>
</body>
</html>
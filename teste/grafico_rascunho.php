<?php
// Configurações de Conexão com o Banco de Dados
$host = 'localhost';
$dbname = 'discoburguer';$username = 'root'; // Ajuste se necessário
$password = '';     // Ajuste se necessário

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username,$password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Consulta unindo fluxo_caixa com categorias_financeiras, funcionarios e pedidos
    $sql = "SELECT 
                fc.id,
                fc.pedido_id,
                fc.descricao,
                fc.valor,
                fc.data_movimentacao,
                COALESCE(cf.nome, 'Sem Categoria') AS categoria_nome,
                COALESCE(cf.tipo, 'entrada') AS categoria_tipo,
                COALESCE(f.nome, 'Não informado') AS funcionario_nome
            FROM fluxo_caixa fc
            LEFT JOIN categorias_financeiras cf ON fc.categoria_id = cf.id
            LEFT JOIN funcionarios f ON fc.funcionario_id = f.id
            ORDER BY fc.data_movimentacao ASC";

    $stmt = $pdo->query($sql);
    $movimentacoes =$stmt->fetchAll();

    // Processa os dados para calcular o Saldo Acumulado
    $dadosGrafico = [];$saldoAcumulado = 0;

    foreach ($movimentacoes as$mov) {
        $valor = (float)$mov['valor'];
        $tipo = strtolower($mov['categoria_tipo']);

        // Soma entradas e subtrai saídas
        if ($tipo === 'entrada') {
            $saldoAcumulado +=$valor;
        } else {
            $saldoAcumulado -=$valor;
        }

        $dadosGrafico[] = [
            'id' => (int) $mov['id'],
            'pedido_id' => $mov['pedido_id'] ? (int)$mov['pedido_id'] : 'N/A',
            'categoria' => $mov['categoria_nome'],
            'tipo' => strtoupper($tipo),
            'funcionario' => $mov['funcionario_nome'],
            'descricao' => $mov['descricao'] ?: 'Sem descrição',
            'valor' => $valor,
            'saldo_acumulado' => $saldoAcumulado,
            'data_movimentacao' => $mov['data_movimentacao']
        ];
    }

} catch (PDOException $e) {
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-[#090314] text-purple-100">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fluxo de Caixa - Disco Burguer</title>
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- D3.js & Observable Plot CDN -->
  <script src="https://cdn.jsdelivr.net/npm/d3@7"></script>
  <script src="https://cdn.jsdelivr.net/npm/@observablehq/plot@0.6"></script>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap');
    
    body {
      font-family: 'Inter', sans-serif;
      background: radial-gradient(circle at 50% 10%, #1e0938 0%, #090314 100%);
    }

    .plot-container svg {
      background: transparent !important;
      overflow: visible;
      width: 100% !important;
      height: auto !important;
    }

    .plot-container svg [aria-label="y-axis tick"] text,
    .plot-container svg [aria-label="x-axis tick"] text,
    .plot-container svg [aria-label="y-axis label"] text {
      fill: #c084fc !important;
      font-family: 'JetBrains Mono', monospace !important;
      font-size: 11px !important;
    }

    .plot-container svg [aria-label="y-grid"] line,
    .plot-container svg [aria-label="x-grid"] line {
      stroke: #3b0764 !important;
      stroke-opacity: 0.5 !important;
      stroke-dasharray: 2 4 !important;
    }

    .plot-container svg [aria-label="y-axis tick"] line,
    .plot-container svg [aria-label="x-axis tick"] line {
      stroke: #7e22ce !important;
    }

    .plot-container svg [aria-label="tip"] rect {
      fill: #180828 !important;
      stroke: #a855f7 !important;
      stroke-width: 1px !important;
      rx: 6px !important;
    }

    .plot-container svg [aria-label="tip"] text {
      fill: #f3e8ff !important;
      font-family: 'Inter', sans-serif !important;
    }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-8">

  <!-- Chart Card Container -->
  <main class="w-full max-w-4xl bg-[#140628]/80 backdrop-blur-xl border border-purple-800/40 rounded-2xl p-6 sm:p-10 shadow-[0_0_60px_-15px_rgba(168,85,247,0.2)]">
    
    <!-- Card Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-purple-900/40 pb-5 mb-6">
      <div>
        <div class="flex items-center space-x-2.5">
          <span class="w-2.5 h-2.5 rounded-full bg-purple-400 shadow-[0_0_12px_#c084fc] animate-pulse"></span>
          <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-purple-100">Evolução do Fluxo de Caixa</h1>
        </div>
        <p class="text-xs sm:text-sm text-purple-300/60 mt-1">Saldo Acumulado por Período • Disco Burguer</p>
      </div>
      <div class="text-xs font-mono text-purple-400/80 bg-purple-950/80 border border-purple-800/40 px-3 py-1.5 rounded-lg w-fit">
        Banco: MySQL (discoburguer)
      </div>
    </div>

    <!-- Observable Plot Render Target -->
    <div id="chart" class="plot-container w-full min-h-[400px] flex items-center justify-center"></div>

  </main>

  <script>
    // Injeção direta dos dados PHP convertidos para JSON
    const fluxoDados = <?php echo json_encode($dadosGrafico); ?>;

    // Converte as strings de data em objetos Date do JS
    const dataset = fluxoDados.map(item => ({
      ...item,
      Date: new Date(item.data_movimentacao)
    }));

    function renderPlot() {
      const chartContainer = document.getElementById("chart");
      chartContainer.innerHTML = "";

      if (dataset.length === 0) {
        chartContainer.innerHTML = `<p class="text-purple-400 text-sm">Nenhuma movimentação encontrada no banco de dados.</p>`;
        return;
      }

      // Renderiza o gráfico utilizando Observable Plot
      const plot = Plot.plot({
        width: chartContainer.clientWidth || 800,
        height: 420,
        padding: 40,
        style: {
          background: "transparent",
          color: "#c084fc",
          fontSize: "12px"
        },
        x: {
          type: "time",
          label: null,
          grid: true
        },
        y: {
          label: "↑ Saldo Acumulado (R$)",
          grid: true
        },
        marks: [
          // Linha de base y = 0
          Plot.ruleY([0], { stroke: "#3b0764", strokeWidth: 1.5 }),

          // Área preenchida sob a curva do saldo
          Plot.areaY(dataset, {
            x: "Date",
            y: "saldo_acumulado",
            fill: "#a855f7",
            fillOpacity: 0.12
          }),

          // Linha principal do Saldo
          Plot.lineY(dataset, {
            x: "Date",
            y: "saldo_acumulado",
            stroke: "#a855f7",
            strokeWidth: 2.5,
            strokeLinejoin: "round",
            strokeLinecap: "round"
          }),

          // Pontos de cada movimentação
          Plot.dot(dataset, {
            x: "Date",
            y: "saldo_acumulado",
            fill: "#c084fc",
            r: 4
          }),

          // Tooltip interativo com as informações completas da tabela
          Plot.tip(
            dataset,
            Plot.pointerX({
              x: "Date",
              y: "saldo_acumulado",
              title: (d) => 
                `ID Movimentação: #${d.id}\n` +
                `Data: ${d3.timeFormat("%d/%m/%Y %H:%M")(d.Date)}\n` +
                `Descrição: ${d.descricao}\n` +
                `Tipo: ${d.tipo} (R$ ${d.valor.toFixed(2)})\n` +
                `Categoria: ${d.categoria}\n` +
                `Funcionário: ${d.funcionario}\n` +
                `Pedido Associado: ${d.pedido_id !== 'N/A' ? '#' + d.pedido_id : 'Nenhum'}\n` +
                `------------------------\n` +
                `Saldo Acumulado: R$ ${d.saldo_acumulado.toFixed(2)}`,
              fill: "#180828",
              stroke: "#c084fc"
            })
          )
        ]
      });

      chartContainer.appendChild(plot);
    }

    window.addEventListener("DOMContentLoaded", renderPlot);
    window.addEventListener("resize", renderPlot);
  </script>
</body>
</html>
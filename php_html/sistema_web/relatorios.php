<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disco Burguer - Relatórios</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../css/painel.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background: #090314;
            color: #f3e8ff;
        }

        main.conteudo {
            margin-left: 260px;
            padding: 25px;
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 991px) {
            main.conteudo {
                margin-left: 0 !important;
                padding: 15px;
            }
        }

        .section-header {
            border-bottom: 1px solid rgba(168, 85, 247, 0.3);
            padding-bottom: 8px;
            margin-bottom: 20px;
            color: #c084fc;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-relatorio {
            background: rgba(20, 6, 40, 0.9);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 12px;
            padding: 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s, border-color 0.2s;
        }

        .card-relatorio:hover {
            transform: translateY(-3px);
            border-color: #a855f7;
        }

        .card-relatorio .icon-box {
            font-size: 28px;
            color: #c084fc;
            margin-bottom: 12px;
        }

        .card-relatorio h3 {
            font-size: 16px;
            font-weight: 700;
            color: #f3e8ff;
            margin-bottom: 8px;
        }

        .card-relatorio p {
            font-size: 13px;
            color: #a78bfa;
            margin-bottom: 20px;
            line-height: 1.4;
        }

        .btn-gerar-pdf {
            background: #a855f7;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s;
            cursor: pointer;
        }

        .btn-gerar-pdf:hover {
            background: #9333ea;
            color: #fff;
        }

        .filter-panel {
            background: rgba(24, 8, 40, 0.8);
            border: 1px solid rgba(168, 85, 247, 0.2);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 28px;
        }

        .filter-panel label {
            font-size: 12px;
            color: #c084fc;
            margin-bottom: 4px;
        }

        .filter-panel input[type="date"] {
            background: #180828;
            border: 1px solid #a855f7;
            color: #fff;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 13px;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . "/../../includes/painel.php"; ?>

    <?php include __DIR__ . "/../../includes/header_sistema.php"; ?>

    <main class="conteudo">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h4 font-weight-bold mb-1">Central de Relatórios Gerenciais</h1>
                    <p class="text-muted small mb-0">Exporte demonstrativos em PDF para análise e tomada de decisões.</p>
                </div>
            </div>

            <div class="filter-panel">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label>Data Início:</label>
                        <input type="date" id="data_inicio" class="form-control" value="<?= date('Y-m-01') ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label>Data Fim:</label>
                        <input type="date" id="data_fim" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <small class="text-muted d-block mt-1">Os relatórios de Fluxo de Caixa e DRE utilizarão este intervalo de datas.</small>
                    </div>
                </div>
            </div>

            <div class="section-header">
                <i class="bi bi-box-seam me-2"></i>Módulo Operacional e Estoque
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card-relatorio">
                        <div>
                            <div class="icon-box"><i class="bi bi-boxes"></i></div>
                            <h3>Relatório Gerencial de Estoque</h3>
                            <p>Posição atual do estoque, movimentações de entrada/saída, lotes e identificação de insumos abaixo do mínimo.</p>
                        </div>
                        <a href="gerar_relatorio.php?tipo=estoque" target="_blank" onclick="this.href='gerar_relatorio.php?tipo=estoque&data_inicio=' + document.getElementById('data_inicio').value + '&data_fim=' + document.getElementById('data_fim').value;" class="btn-gerar-pdf">
                            <i class="bi bi-file-earmark-pdf-fill"></i> Gerar PDF
                        </a>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card-relatorio">
                        <div>
                            <div class="icon-box"><i class="bi bi-pie-chart-fill"></i></div>
                            <h3>Análise de Curva ABC</h3>
                            <p>Classificação dos produtos e insumos por impacto no faturamento e giro de vendas (Categorias A, B e C).</p>
                        </div>
                        <a href="gerar_relatorio.php?tipo=curva_abc" target="_blank" onclick="this.href='gerar_relatorio.php?tipo=curva_abc&data_inicio=' + document.getElementById('data_inicio').value + '&data_fim=' + document.getElementById('data_fim').value;" class="btn-gerar-pdf">
                            <i class="bi bi-file-earmark-pdf-fill"></i> Gerar PDF
                        </a>
                    </div>
                </div>
            </div>

            <div class="section-header">
                <i class="bi bi-cash-stack me-2"></i>Módulo Financeiro
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card-relatorio">
                        <div>
                            <div class="icon-box"><i class="bi bi-arrow-left-right"></i></div>
                            <h3>Relatório de Fluxo de Caixa</h3>
                            <p>Detalhamento de entradas e saídas financeiras por categoria do período, saldo inicial e saldo acumulado final.</p>
                        </div>
                        <a href="gerar_relatorio.php?tipo=fluxo_caixa" target="_blank" onclick="this.href='gerar_relatorio.php?tipo=fluxo_caixa&data_inicio=' + document.getElementById('data_inicio').value + '&data_fim=' + document.getElementById('data_fim').value;" class="btn-gerar-pdf">
                            <i class="bi bi-file-earmark-pdf-fill"></i> Gerar PDF
                        </a>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card-relatorio">
                        <div>
                            <div class="icon-box"><i class="bi bi-journal-check"></i></div>
                            <h3>DRE Gerencial</h3>
                            <p>Demonstrativo do Resultado do Exercício: receita bruta, custos operacionais, despesas fixas e lucro líquido.</p>
                        </div>
                        <a href="gerar_relatorio.php?tipo=dre" target="_blank" onclick="this.href='gerar_relatorio.php?tipo=dre&data_inicio=' + document.getElementById('data_inicio').value + '&data_fim=' + document.getElementById('data_fim').value;" class="btn-gerar-pdf">
                            <i class="bi bi-file-earmark-pdf-fill"></i> Gerar PDF
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script src="../../js/painel.js"></script>
</body>

</html>
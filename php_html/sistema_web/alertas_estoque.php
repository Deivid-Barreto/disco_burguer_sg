<?php

require_once __DIR__ . '/../../php/conexao.php'; 

if (isset($_GET['ajax']) &&$_GET['ajax'] == 'true') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $sqlEstoque = "
            SELECT 
                e.id AS estoque_id,
                e.codigo,
                e.nome AS produto_nome,
                e.unidade_medida,
                e.estoque_minimo,
                COALESCE(SUM(l.quantidade_estoque), 0) AS estoque_atual,
                CASE 
                    WHEN COALESCE(SUM(l.quantidade_estoque), 0) = 0 THEN 'ZERADO'
                    ELSE 'ABAIXO DO MÍNIMO'
                END AS nivel_status
            FROM estoque e
            LEFT JOIN lotes_estoque l ON e.id = l.estoque_id
            GROUP BY e.id, e.codigo, e.nome, e.unidade_medida, e.estoque_minimo
            HAVING COALESCE(SUM(l.quantidade_estoque), 0) <= e.estoque_minimo
            ORDER BY estoque_atual ASC;
        ";
        
        $stmtEstoque = $pdo->query($sqlEstoque);
        $alertasEstoque =$stmtEstoque->fetchAll(PDO::FETCH_ASSOC);

        $sqlValidade = "
            SELECT 
                l.id AS lote_id,
                e.id AS estoque_id,
                l.numero_lote,
                e.nome AS produto_nome,
                e.unidade_medida,
                l.quantidade_estoque,
                l.data_validade,
                DATEDIFF(l.data_validade, CURRENT_DATE()) AS dias_para_vencer,
                CASE 
                    WHEN l.data_validade < CURRENT_DATE() THEN 'VENCIDO'
                    WHEN DATEDIFF(l.data_validade, CURRENT_DATE()) = 0 THEN 'HOJE'
                    ELSE 'PRÓXIMO'
                END AS status_validade
            FROM lotes_estoque l
            INNER JOIN estoque e ON l.estoque_id = e.id
            WHERE l.quantidade_estoque > 0
              AND l.data_validade <= DATE_ADD(CURRENT_DATE(), INTERVAL 15 DAY)
            ORDER BY l.data_validade ASC;
        ";

        $stmtValidade = $pdo->query($sqlValidade);
        $alertasValidade =$stmtValidade->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'sucesso' => true,
            'estoque_minimo' => $alertasEstoque,
            'validade' => $alertasValidade,
            'ultima_atualizacao' => date('d/m/Y H:i:s')
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            'sucesso' => false,
            'erro' => $e->getMessage()
        ]);
    }
    
    exit; 
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Alertas de Estoque</title>
</head>
<body>

    <div>
        <h2>Alertas do Estoque</h2>
    </div>

    <div>
        <div>
            <h2>Produtos com Estoque Baixo / Zerado</h2>
            <table>
                <thead>
                    <tr>
                        <th>Cód.</th>
                        <th>Produto</th>
                        <th>Qtd. Atual</th>
                        <th>Mínimo</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="tabela-estoque-minimo">
                    <tr><td colspan="5">Não há alertas.</td></tr>
                </tbody>
            </table>
        </div>

        <div>
            <h2>Lotes Próximos do Vencimento / Vencidos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Lote</th>
                        <th>Produto</th>
                        <th>Qtd.</th>
                        <th>Validade</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody id="tabela-validade">
                    <tr><td colspan="5">Não há alertas.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="info-atualizacao"></div>

    <script>
        function buscarAlertas() {
            fetch('buscar_alertas_estoque.php?ajax=true')
                .then(resposta => resposta.json())
                .then(res => {
                    if (!res.sucesso) {
                        return;
                    }

                    let htmlEstoque = '';
                    if (res.estoque_minimo.length > 0) {
                        res.estoque_minimo.forEach(item => {
                            htmlEstoque += `
                                <tr onclick="window.location.href='estoque.php?id=${item.estoque_id}'">
                                    <td>${item.codigo}</td>
                                    <td>${item.produto_nome}</td>
                                    <td>${item.estoque_atual} ${item.unidade_medida}</td>
                                    <td>${item.estoque_minimo} ${item.unidade_medida}</td>
                                    <td>${item.nivel_status}</td>
                                </tr>
                            `;
                        });
                    } else {
                        htmlEstoque = '<tr><td colspan="5">Não há alertas.</td></tr>';
                    }
                    document.getElementById('tabela-estoque-minimo').innerHTML = htmlEstoque;

                    let htmlValidade = '';
                    if (res.validade.length > 0) {
                        res.validade.forEach(lote => {
                            let partesData = lote.data_validade.split('-');
                            let dataFormatada = `${partesData[2]}/${partesData[1]}/${partesData[0]}`;
                            
                            let textoSituacao = '';
                            if (lote.dias_para_vencer < 0) {
                                textoSituacao = `Vencido há ${Math.abs(lote.dias_para_vencer)} dia(s)`;
                            } else if (lote.dias_para_vencer == 0) {
                                textoSituacao = `Vence HOJE!`;
                            } else {
                                textoSituacao = `Vence em ${lote.dias_para_vencer} dia(s)`;
                            }

                            htmlValidade += `
                                <tr onclick="window.location.href='estoque.php?id=${lote.estoque_id}&lote_id=${lote.lote_id}'">
                                    <td>${lote.numero_lote}</td>
                                    <td>${lote.produto_nome}</td>
                                    <td>${lote.quantidade_estoque} ${lote.unidade_medida}</td>
                                    <td>${dataFormatada}</td>
                                    <td>${textoSituacao}</td>
                                </tr>
                            `;
                        });
                    } else {
                        htmlValidade = '<tr><td colspan="5">Não há alertas.</td></tr>';
                    }
                    document.getElementById('tabela-validade').innerHTML = htmlValidade;

                    document.getElementById('info-atualizacao').innerText = 'Última verificação: ' + res.ultima_atualizacao;
                })
                .catch(error => console.error(error));
        }

        buscarAlertas();
        setInterval(buscarAlertas, 15000);
    </script>

</body>
</html>
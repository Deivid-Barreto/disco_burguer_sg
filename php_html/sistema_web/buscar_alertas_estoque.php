<?php

// select bem feito da tabela de estoque

require_once __DIR__ . '/../../php/conexao.php'; 
if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
    try {
        $sql = "SELECT tipo_alerta, mensagem, data_alerta FROM alertas_estoque ORDER BY data_alerta DESC";
        $stmt = $pdo->query($sql);
        $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($alertas) > 0) {
            foreach ($alertas as $alerta) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($alerta['tipo_alerta']) . "</td>";
                echo "<td>" . htmlspecialchars($alerta['mensagem']) . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($alerta['data_alerta'])) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3'>Nenhum alerta registrado.</td></tr>";
        }
    } catch (PDOException $e) {
        echo "<tr><td colspan='3'>Erro ao buscar alertas.</td></tr>";
    }
    
    exit; 
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Alertas</title>
</head>
<body>

    <table>
        <thead>
            <tr>
                <th>Tipo de Alerta</th>
                <th>Mensagem</th>
                <th>Data e Hora</th>
            </tr>
        </thead>
        <tbody id="tabela-alertas">
        </tbody>
    </table>

    <script>
        function buscarAlertas() {
            fetch('buscar_alertas.php?ajax=true')
                .then(resposta => resposta.text())
                .then(html => {
                    document.getElementById('tabela-alertas').innerHTML = html;
                })
                .catch(error => console.error('Erro na atualização:', error));
        }

        buscarAlertas();
        setInterval(buscarAlertas, 10000);
    </script>

</body>
</html>
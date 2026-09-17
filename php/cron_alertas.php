<?php

require_once __DIR__ . '/conexao.php'; 

try {
    
    $pdo->beginTransaction();

    
    $sqlEstoqueMinimo = "
        INSERT INTO alertas_estoque (estoque_id, tipo_alerta, mensagem)
        SELECT 
            e.id, 
            'estoque_minimo', 
            CONCAT('ATENCAO: O estoque total de \"', e.nome, '\" atingiu nivel critico: ', COALESCE(SUM(l.quantidade_estoque), 0), ' ', e.unidade_medida)
        FROM estoque e
        LEFT JOIN lotes_estoque l ON e.id = l.estoque_id
        GROUP BY e.id, e.nome, e.estoque_minimo, e.unidade_medida
        HAVING COALESCE(SUM(l.quantidade_estoque), 0) <= (e.estoque_minimo + 10.000)
           AND NOT EXISTS (
               SELECT 1 FROM alertas_estoque a 
               WHERE a.estoque_id = e.id 
                 AND a.tipo_alerta = 'estoque_minimo' 
                 AND DATE(a.data_alerta) = CURDATE()
           )
    ";
    $stmtMinimo = $pdo->prepare($sqlEstoqueMinimo);
    $stmtMinimo->execute();
    $alertasMinimo = $stmtMinimo->rowCount();

    
    $sqlValidade = "
        INSERT INTO alertas_estoque (estoque_id, tipo_alerta, mensagem)
        SELECT 
            l.estoque_id, 
            'validade', 
            CONCAT('ALERTA DE VALIDADE: O insumo \"', e.nome, '\" (Lote: ', l.numero_lote, ') vence em ', DATEDIFF(l.data_validade, CURDATE()), ' dia(s).')
        FROM lotes_estoque l
        INNER JOIN estoque e ON l.estoque_id = e.id
        WHERE DATEDIFF(l.data_validade, CURDATE()) <= 7
          AND DATEDIFF(l.data_validade, CURDATE()) >= 0
          AND NOT EXISTS (
              SELECT 1 FROM alertas_estoque a 
              WHERE a.estoque_id = l.estoque_id 
                AND a.tipo_alerta = 'validade' 
                AND a.mensagem LIKE CONCAT('%(Lote: ', l.numero_lote, ')%')
                AND DATE(a.data_alerta) = CURDATE()
          )
    ";
    $stmtValidade = $pdo->prepare($sqlValidade);
    $stmtValidade->execute();
    $alertasValidade = $stmtValidade->rowCount();

    
    $pdo->commit();

    // Print de log para acompanhar a execução do Cron Job
    echo "[" . date('Y-m-d H:i:s') . "] Cron finalizado com sucesso! Alertas gerados - Estoque minimo: {$alertasMinimo} | Validade: {$alertasValidade}\n";

} catch (PDOException $e) {
    
    $pdo->rollBack();
    
    
    error_log("[" . date('Y-m-d H:i:s') . "] Erro no cron de alertas: " . $e->getMessage());
    echo "Erro na execução do Cron: " . $e->getMessage() . "\n";
}
?>
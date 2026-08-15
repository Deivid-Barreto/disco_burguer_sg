<?php
header('Content-Type: application/json; charset=utf-8');
include 'conexao.php';

$sql = "SELECT id, nome AS nome FROM categorias ORDER BY id ASC";
$resultado = $conn->query($sql);

if ($resultado) {
    $categorias = [];
    while ($row = $resultado->fetch_assoc()) {
        $categorias[] = $row;
    }
    echo json_encode($categorias);
}else{
    echo json_encode(["error"=> "Erro ao buscar categorias:" . $conn->error]);
}

$conn->close();

?>
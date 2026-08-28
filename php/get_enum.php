<?php
session_start();
include 'conexao.php';

$categorias = [];
$sql = "SHOW COLUMNS FROM `$produtos` LIKE ?";
$conn = $conexao->prepare($sql);

if ($stmt) {
    $stmt->bind_param("s", $coluna);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $coluna_info = $resultado->fetch_assoc();

    if ($coluna_info) {
        preg_match("/^enum\((.*)\)$/", $coluna_info['Type'], $matches);
        if (!empty($matches)) {
            $categorias = str_getcsv($matches, ',', "'");
        }
    }
    $stmt->close();
}
$conexao->close();
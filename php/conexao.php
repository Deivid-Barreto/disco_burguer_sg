<?php
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "discoburguer";

$conn = new mysqli($host, $usuario, $senha, $banco);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>
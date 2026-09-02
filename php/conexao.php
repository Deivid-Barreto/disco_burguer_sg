<?php
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "discoburguer";

$conn = new mysqli($host, $usuario, $senha, $banco);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
    // pag de carregamento p sempre
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
   
</body>
</html>
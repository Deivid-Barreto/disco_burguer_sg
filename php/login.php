<?php
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Lógica para autenticar o usuário no banco
    // ...

    // Redireciona para o index após login com sucesso
    header("Location: ../index.html");
    exit();
}
?>
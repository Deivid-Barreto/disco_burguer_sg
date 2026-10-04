<?php
require_once __DIR__ . '/../../php/conexao.php';

$nome = "Administrador";
$email = "admin@admin.com"; 
$senha_pura = "123456"; 


$senha_criptografada = password_hash($senha_pura, PASSWORD_DEFAULT);
$cargo = "administrador";

try {
    $stmt = $pdo->prepare("INSERT INTO funcionarios (nome, email, senha_hash, cargo, ativo) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute([$nome, $email, $senha_criptografada, $cargo]);
    
    echo "Administrador criado com sucesso! Agora você já pode acessar o login.php. (Lembre-se de deletar este arquivo).";
} catch (PDOException $e) {
    echo "Erro ao criar administrador: " . $e->getMessage();
}
?>
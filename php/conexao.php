<?php
$host = 'discoburguer-sgbd-discoburguer.d.aivencloud.com';
$port = '21477'; // Substitua pela porta exibida no painel Aiven
$db   = 'defaultdb'; 
$user = 'avnadmin';
$pass = 'AVNS_OPFWfmtmtZz4KxfF3_F';

$options = [
    // Caminho absoluto para o arquivo ca.pem na mesma pasta deste script
    PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/ca.pem',
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, $options);
    echo "Conexão realizada com sucesso!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
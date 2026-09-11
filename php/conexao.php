<?php

if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
    $host     = 'localhost';
    $dbname   = 'discoburguer';
    $username = 'root';
    $password = ''; 

} else {

    $host     = 'sql107.infinityfree.com'; 
    $dbname   = 'if0_42538913_discoburguerorder';   
    $username = 'if0_42538913';           
    $password = 'ebg5cc3VQy';     

}


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}

if (isset($pdo)) {
    echo "<h2 style='color: green;'>✅ Conexão realizada com sucesso!</h2>";
} else {
    echo "<h2 style='color: red;'>❌ Não foi possível conectar ao banco de dados.</h2>";
}

?>
<?php
session_start();
require_once __DIR__ . '/../../php/conexao.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_post = $_POST['csrf_token'] ?? '';
    
    if (!hash_equals($_SESSION['csrf_token'], $token_post)) {
        die("Requisição inválida ou não autorizada.");
    }

    $email = trim($_POST['email'] ?? '');
    $senha_digitada = trim($_POST['senha'] ?? '');

    if (!empty($email) && !empty($senha_digitada)) {
        $stmt = $pdo->prepare("SELECT id, nome, senha_hash, cargo FROM funcionarios WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($funcionario && password_verify($senha_digitada, $funcionario['senha_hash'])) {
            session_regenerate_id(true);
            
            $_SESSION['funcionario_id'] = $funcionario['id'];
            $_SESSION['funcionario_nome'] = $funcionario['nome'];
            $_SESSION['funcionario_cargo'] = strtolower($funcionario['cargo']); 
            
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            switch ($_SESSION['funcionario_cargo']) {
                case 'administrador':
                    header("Location: testeadm.php"); 
                    break;
                case 'operador_caixa':
                    header("Location: testeopc.php"); 
                    break;
                case 'atendente':
                    header("Location: testeaten.php"); 
                    break;
                case 'cozinheiro':
                    header("Location: testecoz.php"); 
                    break;
            }
            exit;
        } else {
            $erro = "E-mail ou senha inválidos, ou usuário inativo.";
        }
    } else {
        $erro = "Por favor, preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <div class="login-box">
        <h1>Acesso ao Sistema</h1>

        <?php if (!empty($erro)): ?>
            <div class="erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="campo">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required autofocus placeholder="Digite seu e-mail">
            </div>

            <div class="campo">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required placeholder="Digite sua senha">
            </div>

            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>
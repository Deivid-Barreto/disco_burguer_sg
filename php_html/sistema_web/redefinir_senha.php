<?php
session_start();
require_once __DIR__ . '/../../php/conexao.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$erro = '';
$sucesso = '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (empty($token)) {
    die("Acesso inválido: Token de redefinição não fornecido.");
}

$stmt = $pdo->prepare("SELECT id FROM funcionarios WHERE reset_token = ? AND reset_expira > NOW() AND ativo = 1");
$stmt->execute([$token]);
$funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$funcionario) {
    die("O link de redefinição é inválido ou já expirou. Solicite um novo link.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_post = $_POST['csrf_token'] ?? '';
    
    if (!hash_equals($_SESSION['csrf_token'], $token_post)) {
        die("Requisição inválida ou não autorizada.");
    }

    $nova_senha = trim($_POST['nova_senha'] ?? '');
    $confirmar_senha = trim($_POST['confirmar_senha'] ?? '');

    if (!empty($nova_senha) && !empty($confirmar_senha)) {
        if ($nova_senha === $confirmar_senha) {
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

            $update = $pdo->prepare("UPDATE funcionarios SET senha_hash = ?, reset_token = NULL, reset_expira = NULL WHERE id = ?");
            $update->execute([$senha_hash, $funcionario['id']]);

            $sucesso = "Senha redefinida com sucesso!";
        } else {
            $erro = "As senhas não coincidem.";
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
    <title>Redefinir Senha</title>
</head>
<body>
    <div>
        <h1>Redefinir Senha</h1>

        <?php if (!empty($erro)): ?>
            <div><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if (!empty($sucesso)): ?>
            <div>
                <?= htmlspecialchars($sucesso) ?><br><br>
                <a href="login.php">Clique aqui para fazer login</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div>
                    <label for="nova_senha">Nova Senha</label>
                    <input type="password" id="nova_senha" name="nova_senha" required placeholder="Digite a nova senha">
                </div>

                <div>
                    <label for="confirmar_senha">Confirmar Nova Senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required placeholder="Confirme a nova senha">
                </div>

                <button type="submit">Salvar Nova Senha</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
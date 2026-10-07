<?php
session_start();
require_once __DIR__ . '/../../php/conexao.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$erro = '';
$mensagem_recuperacao = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_post = $_POST['csrf_token'] ?? '';
    
    if (!hash_equals($_SESSION['csrf_token'], $token_post)) {
        die("Requisição inválida ou não autorizada.");
    }


    $acao = $_POST['acao'] ?? 'login';

    if ($acao === 'login') {
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
                    case 'administrador': header("Location: testeadm.php"); break;
                    case 'operador_caixa': header("Location: testeopc.php"); break;
                    case 'atendente': header("Location: testeaten.php"); break;
                    case 'cozinheiro': header("Location: testecoz.php"); break;
                }
                exit;
            } else {
                $erro = "E-mail ou senha inválidos, ou usuário inativo.";
            }
        } else {
            $erro = "Por favor, preencha todos os campos.";
        }
    } 
    
    
    elseif ($acao === 'recuperar') {
        $email_recuperacao = trim($_POST['email_recuperacao'] ?? '');

        if (!empty($email_recuperacao)) {
            $stmt = $pdo->prepare("SELECT id FROM funcionarios WHERE email = ? AND ativo = 1");
            $stmt->execute([$email_recuperacao]);
            $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($funcionario) {
                $token_reset = bin2hex(random_bytes(32));
                $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $update = $pdo->prepare("UPDATE funcionarios SET reset_token = ?, reset_expira = ? WHERE id = ?");
                $update->execute([$token_reset, $expiracao, $funcionario['id']]);

                $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                $dominio = $_SERVER['HTTP_HOST'];
                $caminho = dirname($_SERVER['REQUEST_URI']);
                $link_redefinicao = "{$protocolo}://{$dominio}{$caminho}/redefinir_senha.php?token={$token_reset}";

                $mensagem_recuperacao = "
                    <strong>Link gerado com sucesso!</strong><br><br>
                    Clique abaixo para testar a redefinição:<br>
                    <a href='{$link_redefinicao}' style='word-break: break-all;'>{$link_redefinicao}</a>
                ";
            } else {
                $mensagem_recuperacao = "Se o e-mail estiver cadastrado, o link será gerado.";
            }
        } else {
            $erro = "Por favor, informe seu e-mail.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script>
       
        function mostrarRecuperacao() {
            document.getElementById('form-login').style.display = 'none';
            document.getElementById('form-recuperar').style.display = 'block';
        }
        function mostrarLogin() {
            document.getElementById('form-recuperar').style.display = 'none';
            document.getElementById('form-login').style.display = 'block';
        }
    </script>
</head>
<body>
    <div class="login-box">
        <h1>Acesso ao Sistema</h1>

        <?php if (!empty($erro)): ?>
            <div style="color: red; margin-bottom: 15px;"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if (!empty($mensagem_recuperacao)): ?>
            <div>
                <?= $mensagem_recuperacao ?>
            </div>
        <?php endif; ?>

        <!-- ÁREA DE LOGIN -->
        <div id="form-login" style="<?= (isset($_POST['acao']) && $_POST['acao'] === 'recuperar') ? 'display: none;' : 'display: block;' ?>">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="acao" value="login"> 

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
            
            <div style="margin-top: 15px;">
                <a href="#" onclick="mostrarRecuperacao(); return false;">Esqueceu a senha?</a>
            </div>
        </div>

   
        <div id="form-recuperar" style="<?= (isset($_POST['acao']) && $_POST['acao'] === 'recuperar') ? 'display: block;' : 'display: none;' ?>">
            <p>Informe seu e-mail para gerar o link de acesso.</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="acao" value="recuperar">

                <div class="campo">
                    <label for="email_recuperacao">E-mail cadastrado</label>
                    <input type="email" id="email_recuperacao" name="email_recuperacao" required placeholder="Digite seu e-mail">
                </div>

                <button type="submit">Gerar link</button>
            </form>
            
            <div>
                <a href="#" onclick="mostrarLogin(); return false;">Voltar para o Login</a>
            </div>
        </div>

    </div>
</body>
</html>
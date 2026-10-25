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
                    case 'administrador': header("Location: inicio.php"); break;
                    case 'operador_caixa': header("Location: testeopc.php"); break;
                    case 'atendente': header("Location: ../sistema_garcom/pedido.php"); break;
                    case 'cozinheiro': header("Location: testecoz.php"); break;
                }
                exit;
            } else {
               // estilizar a mensagem de erro para que fique visível no front-end
                $erro = "E-mail ou senha inválidos, ou usuário inativo.";
            }
        } else {
            $erro = "Por favor, preencha todos os campos.";
        }
    }
    //estilizar a parte de recuperação de senha para que fique visível no front-end 
    elseif ($acao === 'recuperar') {
        $email_recuperacao = trim($_POST['email_recuperacao'] ?? '');

        if (!empty($email_recuperacao)) {
            $stmt = $pdo->prepare("SELECT id FROM funcionarios WHERE email = ? AND ativo = 1");
            $stmt->execute([$email_recuperacao]);
            $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

            // estilizar a mensagem de recuperação para que fique visível no front-end
            $mensagem_recuperacao = "Caso o e-mail informado esteja cadastrado, você receberá as instruções de redefinição em instantes.";

            if ($funcionario) {
                $token_reset = bin2hex(random_bytes(32));
                $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $update = $pdo->prepare("UPDATE funcionarios SET reset_token = ?, reset_expira = ? WHERE id = ?");
                $update->execute([$token_reset, $expiracao, $funcionario['id']]);

                $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                $dominio = $_SERVER['HTTP_HOST'];
                $caminho = dirname($_SERVER['REQUEST_URI']);
                $link_redefinicao = "{$protocolo}://{$dominio}{$caminho}/redefinir_senha.php?token={$token_reset}";

               
                $apiKey = "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"; 

                $payload = [
                    "sender" => [
                        "name" => "Discoburguer 80s",
                        "email" => "discoburguer80@gmail.com"
                    ],
                    "to" => [
                        ["email" => $email_recuperacao]
                    ],
                    "subject" => "Redefinição de Senha",
                    "htmlContent" => "
                        <h2>Redefinição de Senha</h2>
                        <p>Você solicitou a redefinição de senha para acessar o sistema.</p>
                        <p>Clique no link abaixo para criar uma nova senha (válido por 1 hora):</p>
                        <p><a href='{$link_redefinicao}'>Redefinir Minha Senha</a></p>
                        <p>Se o link não funcionar, copie e cole no navegador:</p>
                        <p>{$link_redefinicao}</p>
                    "
                ];

                $ch = curl_init("https://api.brevo.com/v3/smtp/email");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "accept: application/json",
                    "api-key: " . $apiKey,
                    "content-type: application/json"
                ]);

                curl_exec($ch);
                curl_close($ch);
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
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <link rel="stylesheet" href="../../css/sistema_web/login.css">


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
<body >
   
       

        <main>
        <img class="icone-login" src="../../img/Icone-disco-burguer.png" alt="">
        <div class="card-login container-fluid d-flex  flex-column col-6">
        <h1 class="titulo-login text-center mb-4">BEM-VINDO!</h1>

        <?php if (!empty($erro)): ?>
            <div><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if (!empty($mensagem_recuperacao)): ?>
            <div><?= htmlspecialchars($mensagem_recuperacao) ?></div>
        <?php endif; ?>

        <div  id="form-login" style="<?= (isset($_POST['acao']) && $_POST['acao'] === 'recuperar') ? 'display: none;' : 'display: block;'?>">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="acao" value="login"> 

                <div class="d-flex flex-column mb-3">
                    <label for="email">E-mail</label>
                   
                    <input type="email" id="email" name="email" required autofocus placeholder="Digite seu e-mail">
                </div>

                <div class="d-flex flex-column mb-3">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required placeholder="Digite sua senha">
                </div>

            <div class="text-end">
                <a href="#" onclick="mostrarRecuperacao(); return false;">Esqueceu a senha?</a>
            </div>

            <button class="btn-entrar d-block mx-auto mt-4" type="submit">Entrar</button>
            </form>
            </div>
           
            
            
        
        </div>
            
        <div id="form-recuperar" style="<?= (isset($_POST['acao']) && $_POST['acao'] === 'recuperar') ? 'display: block;' : 'display: none;' ?>">
            <p>Informe seu e-mail para receber o link de redefinição.</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="acao" value="recuperar">

                <div>
                    <label for="email_recuperacao">E-mail cadastrado</label>
                    <input type="email" id="email_recuperacao" name="email_recuperacao" required placeholder="Digite seu e-mail">
                </div>

                <button type="submit">Enviar link</button>
            </form>
            
            <div>
                <a href="#" onclick="mostrarLogin(); return false;">Voltar para o Login</a>
            </div>
        </div>

        </main>
    
  

<footer class="rodape-login">
    <a href="../../index.php" class="link-voltar">← Voltar ao site</a>
    <p>© 2026 Disco Burguer - Sistema de Gestão</p>
</footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>
</html>
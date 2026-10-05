<?php
session_start();
require_once __DIR__ . '/../../php/conexao.php';
require_once __DIR__ . '/verificar_login.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$funcionario_logado_id = $_SESSION['funcionario_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ação não autorizada.');
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $acao = $_POST['acao'] ?? '';
    $id_alvo = (int)($_POST['id'] ?? 0);
    
    if ($acao === 'deletar') {
        if ($id_alvo !== (int)$funcionario_logado_id) {
            $stmt = $pdo->prepare("DELETE FROM funcionarios WHERE id = ?");
            $stmt->execute([$id_alvo]);
        }
    } elseif ($acao === 'alternar_status') {
        if ($id_alvo !== (int)$funcionario_logado_id) {
            $novo_status = $_POST['status_atual'] == 1 ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE funcionarios SET ativo = ? WHERE id = ?");
            $stmt->execute([$novo_status, $id_alvo]);
        }
    } elseif ($acao === 'salvar') {
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO funcionarios (cargo, nome, ativo, email, senha_hash, numero_telefone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['cargo'], $_POST['nome'], $ativo, $_POST['email'], $hash, $_POST['numero_telefone']]);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$funcionarios = $pdo->query("SELECT id, cargo, nome, ativo, email, numero_telefone FROM funcionarios ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Funcionários</title></head>
<body>
    <button onclick="abrirModal()">CADASTRAR FUNCIONÁRIO</button>
    <table>
        <thead><tr><th>Nome</th><th>Cargo</th><th>Email</th><th>Ativo</th><th>Ações</th></tr></thead>
        <tbody>
            <?php foreach ($funcionarios as $f): ?>
            <tr>
                <td><?= htmlspecialchars($f['nome']) ?></td>
                <td><?= htmlspecialchars($f['cargo']) ?></td>
                <td><?= htmlspecialchars($f['email']) ?></td>
                <td><?= $f['ativo'] ? 'Sim' : 'Não' ?></td>
                <td>
                    <?php if ((int)$f['id'] !== (int)$funcionario_logado_id): ?>
                        <form id="formStatus_<?= $f['id'] ?>" method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="acao" value="alternar_status">
                            <input type="hidden" name="id" value="<?= $f['id'] ?>">
                            <input type="hidden" name="status_atual" value="<?= $f['ativo'] ?>">
                            <button type="button" onclick="dispararConfirmacao('Deseja alternar o status deste funcionário?', document.getElementById('formStatus_<?= $f['id'] ?>'))"><?= $f['ativo'] ? 'Desativar' : 'Ativar' ?></button>
                        </form>
                        <form id="formDelFunc_<?= $f['id'] ?>" method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="acao" value="deletar">
                            <input type="hidden" name="id" value="<?= $f['id'] ?>">
                            <button type="button" onclick="dispararConfirmacao('Tem certeza que deseja EXCLUIR este funcionário?', document.getElementById('formDelFunc_<?= $f['id'] ?>'))">Excluir</button>
                        </form>
                    <?php else: ?>
                        <span>(Você)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <dialog id="modalFuncionario">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="salvar">
            <div><label>Nome:</label><input type="text" name="nome" id="form_nome" required></div>
            <div>
                <label>Cargo:</label>
                <select name="cargo" id="form_cargo" required>
                    <option value="administrador">Administrador</option>
                    <option value="atendente">Atendente</option>
                    <option value="operador_caixa">Operador de Caixa</option>
                    <option value="cozinheiro">Cozinheiro</option>
                </select>
            </div>
            <div><label>Email:</label><input type="email" name="email" id="form_email" required></div>
            <div><label>Senha:</label><input type="password" name="senha" id="form_senha" required></div>
            <div><label>Telefone:</label><input type="text" name="numero_telefone" id="form_telefone"></div>
            <div><label><input type="checkbox" name="ativo" id="form_ativo" value="1" checked> Ativo</label></div>
            <button type="submit">Salvar</button>
            <button type="button" onclick="document.getElementById('modalFuncionario').close()">Cancelar</button>
        </form>
    </dialog>

    <dialog id="modalConfirmacaoCustom">
        <h3 id="tituloConfirmacao">Confirmação</h3>
        <p id="mensagemConfirmacao">Tem certeza que deseja prosseguir?</p>
        <button type="button" id="btnSimConfirmacao">Sim</button>
        <button type="button" onclick="document.getElementById('modalConfirmacaoCustom').close()">Cancelar</button>
    </dialog>

    <script>
    let formParaSubmeter = null;

    function dispararConfirmacao(mensagem, form) {
        formParaSubmeter = form;
        document.getElementById('mensagemConfirmacao').innerText = mensagem;
        document.getElementById('modalConfirmacaoCustom').showModal();
    }

    document.getElementById('btnSimConfirmacao').addEventListener('click', function() {
        document.getElementById('modalConfirmacaoCustom').close();
        if (formParaSubmeter) {
            formParaSubmeter.submit();
        }
    });

    function abrirModal() {
        document.getElementById('form_nome').value = '';
        document.getElementById('form_email').value = '';
        document.getElementById('form_telefone').value = '';
        document.getElementById('form_senha').value = '';
        document.getElementById('form_ativo').checked = true;
        document.getElementById('form_cargo').selectedIndex = 0;
        document.getElementById('modalFuncionario').showModal();
    }
    </script>
</body>
</html>
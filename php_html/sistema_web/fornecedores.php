<?php
session_start();
require_once __DIR__ . '/../../php/conexao.php';


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$funcionario_id = $_SESSION['funcionario_id']; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ação não autorizada.');
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $acao = $_POST['acao'] ?? '';
    if ($acao === 'deletar') {
        $stmt = $pdo->prepare("DELETE FROM fornecedores WHERE id = ?");
        $stmt->execute([$_POST['id']]);
    } elseif ($acao === 'salvar') {
        $id = $_POST['id'] ?? null;
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        if ($id) {
            $stmt = $pdo->prepare("UPDATE fornecedores SET nome_razao_social=?, cnpj_cpf=?, telefone=?, ativo=? WHERE id=?");
            $stmt->execute([$_POST['nome_razao_social'], $_POST['cnpj_cpf'], $_POST['telefone'], $ativo, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO fornecedores (nome_razao_social, cnpj_cpf, telefone, ativo, funcionario_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['nome_razao_social'], $_POST['cnpj_cpf'], $_POST['telefone'], $ativo, $funcionario_id]);
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$fornecedores = $pdo->query("SELECT * FROM fornecedores ORDER BY nome_razao_social ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Fornecedores</title></head>
<body>
    <button onclick="abrirModal()">CADASTRAR FORNECEDOR</button>
    <table>
        <thead><tr><th>Razão Social</th><th>CNPJ/CPF</th><th>Telefone</th><th>Ativo</th><th>Ações</th></tr></thead>
        <tbody>
            <?php foreach ($fornecedores as $f): ?>
            <tr>
                <td><?= htmlspecialchars($f['nome_razao_social']) ?></td>
                <td><?= htmlspecialchars($f['cnpj_cpf']) ?></td>
                <td><?= htmlspecialchars($f['telefone']) ?></td>
                <td><?= $f['ativo'] ? 'Sim' : 'Não' ?></td>
                <td>
                    <button onclick='editar(<?= json_encode($f) ?>)'>Editar</button>
                    <form id="formDelFornecedor_<?= $f['id'] ?>" method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="acao" value="deletar">
                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
                        <button type="button" onclick="dispararConfirmacao('Tem certeza que deseja EXCLUIR este fornecedor?', document.getElementById('formDelFornecedor_<?= $f['id'] ?>'))">Excluir</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <dialog id="modalFornecedor">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="id" id="form_id">
            <div><label>Razão Social / Nome:</label><input type="text" name="nome_razao_social" id="form_nome" required></div>
            <div><label>CNPJ / CPF:</label><input type="text" name="cnpj_cpf" id="form_doc"></div>
            <div><label>Telefone:</label><input type="text" name="telefone" id="form_telefone"></div>
            <div><label><input type="checkbox" name="ativo" id="form_ativo" value="1" checked> Ativo</label></div>
            <button type="submit">Salvar</button>
            <button type="button" onclick="document.getElementById('modalFornecedor').close()">Cancelar</button>
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
        document.getElementById('form_id').value = '';
        document.getElementById('form_nome').value = '';
        document.getElementById('form_doc').value = '';
        document.getElementById('form_telefone').value = '';
        document.getElementById('form_ativo').checked = true;
        document.getElementById('modalFornecedor').showModal();
    }
    
    function editar(f) {
        dispararConfirmacao("Deseja editar este fornecedor?", function() {
            document.getElementById('form_id').value = f.id;
            document.getElementById('form_nome').value = f.nome_razao_social;
            document.getElementById('form_doc').value = f.cnpj_cpf;
            document.getElementById('form_telefone').value = f.telefone;
            document.getElementById('form_ativo').checked = f.ativo == 1;
            document.getElementById('modalFornecedor').showModal();
        });
    }
    </script>
</body>
</html>
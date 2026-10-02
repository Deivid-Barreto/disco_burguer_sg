<?php
session_start();

require_once __DIR__ . '/../../php/conexao.php'; 

$funcionario_id = $_SESSION['funcionario_id'] ?? null;


/* =========================
   AÇÕES POST
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';


    /* =========================
       ADICIONAR MESA
    ========================= */

    if ($acao === 'adicionar_mesa') {

        try {

            $numero = (int) ($_POST['numero'] ?? 0);
            $quantidade = (int) ($_POST['quantidade_lugares'] ?? 0);

            if ($numero <= 0 || $quantidade <= 0) {
                echo "Preencha os dados corretamente.";
                exit;
            }

            /* Verifica se já existe */

            $stmt = $pdo->prepare("
                SELECT id
                FROM mesas
                WHERE numero = ?
            ");

            $stmt->execute([$numero]);

            if ($stmt->fetch()) {
                echo "Essa mesa já existe.";
                exit;
            }


            /* Cadastra */

            $stmt = $pdo->prepare("
                INSERT INTO mesas
                (
                    numero,
                    quantidade_lugares
                )
                VALUES (?, ?)
            ");

            $stmt->execute([
                $numero,
                $quantidade
            ]);

            $id = $pdo->lastInsertId();


            echo "
                <div id='mesa-{$id}'>
                    <b>Mesa {$numero}</b>
                    <p>Lugares: {$quantidade}</p>
                    <p>Status: livre</p>
                    <button onclick=\"abrirEditarMesa({$id}, {$numero}, {$quantidade}, 'livre')\">EDITAR</button>
                    <button onclick=\"deletarMesa({$id})\">EXCLUIR</button>
                </div>
                <hr>
            ";

        } catch (PDOException $e) {

            echo "Erro ao cadastrar mesa: " . $e->getMessage();

        }

        exit;
    }


    /* =========================
       EDITAR MESA
    ========================= */

    if ($acao === 'editar_mesa') {

        try {

            $id = (int) ($_POST['id'] ?? 0);
            $numero = (int) ($_POST['numero'] ?? 0);
            $quantidade = (int) ($_POST['quantidade_lugares'] ?? 0);
            $status = trim($_POST['status'] ?? 'livre');

            if ($id <= 0 || $numero <= 0 || $quantidade <= 0) {
                echo "Preencha os dados corretamente.";
                exit;
            }

            $stmt = $pdo->prepare("
                UPDATE mesas
                SET numero = ?, quantidade_lugares = ?, status = ?
                WHERE id = ?
            ");

            $stmt->execute([$numero, $quantidade, $status, $id]);

            echo "ok";

        } catch (PDOException $e) {

            echo "Erro ao editar mesa: " . $e->getMessage();

        }

        exit;
    }


    /* =========================
       DELETAR MESA
    ========================= */

    if ($acao === 'deletar_mesa') {

        try {

            $id = (int) ($_POST['id'] ?? 0);

            if ($id <= 0) {
                echo "Mesa inválida.";
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM mesas WHERE id = ?");
            $stmt->execute([$id]);

            echo "ok";

        } catch (PDOException $e) {

            echo "Erro ao excluir mesa: " . $e->getMessage();

        }

        exit;
    }


    /* =========================
       LISTAR MESAS
    ========================= */

    if ($acao === 'listar_mesas') {

        try {

            $mesas = $pdo->query("
                SELECT
                    id,
                    numero,
                    status,
                    quantidade_lugares
                FROM mesas
                ORDER BY numero ASC
            ")->fetchAll(PDO::FETCH_ASSOC);


            foreach ($mesas as $mesa) {

                $status_esc = htmlspecialchars($mesa['status'], ENT_QUOTES);

                echo "
                    <div id='mesa-{$mesa['id']}'>
                        <b>Mesa {$mesa['numero']}</b>
                        <p>Lugares: {$mesa['quantidade_lugares']}</p>
                        <p>Status: {$status_esc}</p>
                        <button onclick=\"abrirEditarMesa({$mesa['id']}, {$mesa['numero']}, {$mesa['quantidade_lugares']}, '{$status_esc}')\">EDITAR</button>
                        <button onclick=\"deletarMesa({$mesa['id']})\">EXCLUIR</button>
                    </div>
                    <hr>
                ";
            }


            if (empty($mesas)) {

                echo "<p>Nenhuma mesa cadastrada.</p>";
            }

        } catch (PDOException $e) {

            echo "Erro ao carregar mesas: " . $e->getMessage();

        }

        exit;
    }


    exit;
}


/* =========================
   DADOS INICIAIS
========================= */

$mesas = $pdo->query("
    SELECT
        id,
        numero,
        status,
        quantidade_lugares
    FROM mesas
    ORDER BY numero ASC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciamento de Mesas</title>
</head>
<body>


<!-- =========================
     ÁREA MESAS
========================= -->

<div id="areaMesas">

    <h2>Mesas</h2>

    <button onclick="abrirAdicionarMesa()">ADICIONAR MESA</button>

    <br><br>

    <div id="containerMesas">

        <?php if (empty($mesas)): ?>

            <p>Nenhuma mesa cadastrada.</p>

        <?php else: ?>

            <?php foreach ($mesas as $mesa): ?>

                <div id="mesa-<?= $mesa['id'] ?>">

                    <b>Mesa <?= $mesa['numero'] ?></b>

                    <p>Lugares: <?= $mesa['quantidade_lugares'] ?></p>

                    <p>Status: <?= htmlspecialchars($mesa['status']) ?></p>

                    <button onclick="abrirEditarMesa(<?= $mesa['id'] ?>, <?= $mesa['numero'] ?>, <?= $mesa['quantidade_lugares'] ?>, '<?= htmlspecialchars($mesa['status'], ENT_QUOTES) ?>')">
                        EDITAR
                    </button>

                    <button onclick="deletarMesa(<?= $mesa['id'] ?>)">
                        EXCLUIR
                    </button>

                </div>

                <hr>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>


<!-- =========================
     MODAL MESA
========================= -->

<dialog id="modalMesa">

    <h3 id="tituloModalMesa">Adicionar Mesa</h3>

    <form id="formMesa" onsubmit="salvarMesa(event)">

        <input type="hidden" name="acao" id="acaoMesa" value="adicionar_mesa">
        <input type="hidden" name="id" id="idMesa" value="">

        <div>
            <label>Número da Mesa:</label>
            <input type="number" name="numero" id="numeroMesa" min="1" required>
        </div>

        <br>

        <div>
            <label>Quantidade de Lugares:</label>
            <input type="number" name="quantidade_lugares" id="lugaresMesa" min="1" required>
        </div>

        <br>

        <div id="campoStatusMesa" style="display:none;">
            <label>Status:</label>
            <select name="status" id="statusMesa">
                <option value="livre">Livre</option>
                <option value="ocupada">Ocupada</option>
                <option value="reservada">Reservada</option>
            </select>
            <br><br>
        </div>

        <button type="button" onclick="document.getElementById('modalMesa').close()">CANCELAR</button>

        <button type="submit">SALVAR</button>

    </form>

</dialog>


<script>

/* =========================
   AÇÕES DE MESAS (CRUD)
========================= */

function abrirAdicionarMesa() {
    document.getElementById('formMesa').reset();
    document.getElementById('tituloModalMesa').innerText = 'Adicionar Mesa';
    document.getElementById('acaoMesa').value = 'adicionar_mesa';
    document.getElementById('idMesa').value = '';
    document.getElementById('campoStatusMesa').style.display = 'none';
    document.getElementById('modalMesa').showModal();
}

function abrirEditarMesa(id, numero, quantidade, status) {
    document.getElementById('tituloModalMesa').innerText = 'Editar Mesa';
    document.getElementById('acaoMesa').value = 'editar_mesa';
    document.getElementById('idMesa').value = id;
    document.getElementById('numeroMesa').value = numero;
    document.getElementById('lugaresMesa').value = quantidade;
    document.getElementById('statusMesa').value = status;
    document.getElementById('campoStatusMesa').style.display = 'block';
    document.getElementById('modalMesa').showModal();
}

async function deletarMesa(id) {
    if (!confirm('Deseja realmente excluir esta mesa?')) return;

    const dados = new FormData();
    dados.append('acao', 'deletar_mesa');
    dados.append('id', id);

    const resposta = await fetch('', { method: 'POST', body: dados });
    const texto = await resposta.text();

    if (texto.trim() === 'ok') {
        carregarMesas();
    } else {
        alert(texto);
    }
}

async function carregarMesas() {
    const dados = new FormData();
    dados.append('acao', 'listar_mesas');

    const resposta = await fetch('', { method: 'POST', body: dados });
    document.getElementById('containerMesas').innerHTML = await resposta.text();
}

async function salvarMesa(e) {
    e.preventDefault();
    const form = e.target;
    const acao = document.getElementById('acaoMesa').value;

    const resposta = await fetch('', { method: 'POST', body: new FormData(form) });
    const texto = await resposta.text();

    if (acao === 'adicionar_mesa') {
        if (!texto.trim().startsWith('<div')) {
            alert(texto);
            return;
        }
        carregarMesas();
    } else {
        if (texto.trim() === 'ok') {
            carregarMesas();
        } else {
            alert(texto);
            return;
        }
    }

    form.reset();
    document.getElementById('modalMesa').close();
}

</script>

</body>
</html>
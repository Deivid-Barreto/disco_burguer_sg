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
                    <p>
                        Lugares: {$quantidade}
                    </p>
                    <p>
                        Status: livre
                    </p>
                </div>
                <hr>
            ";

        } catch (PDOException $e) {

            echo "Erro ao cadastrar mesa: " . $e->getMessage();

        }

        exit;
    }


    /* =========================
       ADICIONAR RESERVA
    ========================= */

    if ($acao === 'adicionar_reserva') {

        try {

            $mesa_id = (int) ($_POST['mesa_id'] ?? 0);
            $nome = trim($_POST['nome_cliente'] ?? '');
            $telefone = trim($_POST['telefone'] ?? '');
            $quantidade = (int) ($_POST['quantidade_pessoas'] ?? 0);
            $data = trim($_POST['data_reserva'] ?? '');


            if (
                $mesa_id <= 0 ||
                $nome === '' ||
                $telefone === '' ||
                $quantidade <= 0 ||
                $data === ''
            ) {

                echo "Preencha todos os campos.";
                exit;
            }


            /* datetime-local -> DATETIME */

            $data = str_replace('T', ' ', $data);


            /* =========================
               VERIFICAR MESA
            ========================= */

            $stmt = $pdo->prepare("
                SELECT
                    numero,
                    quantidade_lugares,
                    status
                FROM mesas
                WHERE id = ?
            ");

            $stmt->execute([$mesa_id]);

            $mesa = $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$mesa) {

                echo "Mesa não encontrada.";
                exit;
            }


            /* =========================
               VERIFICAR CAPACIDADE
            ========================= */

            if (
                $quantidade >
                (int) $mesa['quantidade_lugares']
            ) {

                echo "
                    A mesa possui apenas
                    {$mesa['quantidade_lugares']}
                    lugares.
                ";

                exit;
            }


            /* =========================
               VERIFICAR RESERVA
            ========================= */

            $stmt = $pdo->prepare("
                SELECT id
                FROM mesas_reservadas
                WHERE mesa_id = ?
                AND data_reserva = ?
                AND status_reserva = 'ativa'
            ");

            $stmt->execute([
                $mesa_id,
                $data
            ]);


            if ($stmt->fetch()) {

                echo "
                    Essa mesa já possui
                    uma reserva nesse horário.
                ";

                exit;
            }


            /* =========================
               CADASTRAR RESERVA
            ========================= */

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO mesas_reservadas
                (
                    mesa_id,
                    nome_cliente,
                    telefone,
                    quantidade_pessoas,
                    data_reserva
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $mesa_id,
                $nome,
                $telefone,
                $quantidade,
                $data
            ]);

            $id = $pdo->lastInsertId();


            /* Marca a mesa como reservada */

            $stmt = $pdo->prepare("
                UPDATE mesas
                SET status = 'reservada'
                WHERE id = ?
            ");

            $stmt->execute([$mesa_id]);

            $pdo->commit();


            $data_formatada =
                date('d/m/Y H:i', strtotime($data));


            echo "
                <div id='reserva-{$id}'>

                    <b>
                        Mesa {$mesa['numero']}
                    </b>

                    <p>
                        Cliente: " .
                        htmlspecialchars($nome) .
                    "</p>

                    <p>
                        Telefone: " .
                        htmlspecialchars($telefone) .
                    "</p>

                    <p>
                        Pessoas: {$quantidade}
                    </p>

                    <p>
                        Data: {$data_formatada}
                    </p>

                    <p>
                        Status: ativa
                    </p>

                </div>

                <hr>
            ";

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            echo "Erro ao cadastrar reserva: " .
                 $e->getMessage();

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

                echo "
                    <div id='mesa-{$mesa['id']}'>

                        <b>
                            Mesa {$mesa['numero']}
                        </b>

                        <p>
                            Lugares:
                            {$mesa['quantidade_lugares']}
                        </p>

                        <p>
                            Status:
                            {$mesa['status']}
                        </p>

                    </div>

                    <hr>
                ";
            }


            if (empty($mesas)) {

                echo "
                    <p>
                        Nenhuma mesa cadastrada.
                    </p>
                ";
            }

        } catch (PDOException $e) {

            echo "Erro ao carregar mesas: " .
                 $e->getMessage();

        }

        exit;
    }


    /* =========================
       LISTAR RESERVAS
    ========================= */

    if ($acao === 'listar_reservas') {

        try {

            $reservas = $pdo->query("
                SELECT
                    mr.id,
                    mr.mesa_id,
                    m.numero AS numero_mesa,
                    mr.nome_cliente,
                    mr.telefone,
                    mr.quantidade_pessoas,
                    mr.data_reserva,
                    mr.status_reserva
                FROM mesas_reservadas mr

                INNER JOIN mesas m
                    ON m.id = mr.mesa_id

                ORDER BY mr.data_reserva ASC
            ")->fetchAll(PDO::FETCH_ASSOC);


            foreach ($reservas as $reserva) {

                $data = date(
                    'd/m/Y H:i',
                    strtotime($reserva['data_reserva'])
                );


                echo "
                    <div id='reserva-{$reserva['id']}'>

                        <b>
                            Mesa {$reserva['numero_mesa']}
                        </b>

                        <p>
                            Cliente: " .
                            htmlspecialchars(
                                $reserva['nome_cliente']
                            ) .
                        "</p>

                        <p>
                            Telefone: " .
                            htmlspecialchars(
                                $reserva['telefone']
                            ) .
                        "</p>

                        <p>
                            Pessoas:
                            {$reserva['quantidade_pessoas']}
                        </p>

                        <p>
                            Data:
                            {$data}
                        </p>

                        <p>
                            Status:
                            {$reserva['status_reserva']}
                        </p>

                    </div>

                    <hr>
                ";
            }


            if (empty($reservas)) {

                echo "
                    <p>
                        Nenhuma reserva cadastrada.
                    </p>
                ";
            }

        } catch (PDOException $e) {

            echo "Erro ao carregar reservas: " .
                 $e->getMessage();

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


$reservas = $pdo->query("
    SELECT
        mr.id,
        mr.mesa_id,
        m.numero AS numero_mesa,
        mr.nome_cliente,
        mr.telefone,
        mr.quantidade_pessoas,
        mr.data_reserva,
        mr.status_reserva
    FROM mesas_reservadas mr

    INNER JOIN mesas m
        ON m.id = mr.mesa_id

    ORDER BY mr.data_reserva ASC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <title>Mesas e Reservas</title>

</head>

<body>


<!-- =========================
     BOTÕES PRINCIPAIS
========================= -->

<button onclick="mostrarMesas()">
    MESAS
</button>

<button onclick="mostrarReservas()">
    RESERVAS
</button>


<br><br>


<!-- =========================
     ÁREA MESAS
========================= -->

<div id="areaMesas">

    <h2>Mesas</h2>

    <button onclick="
        document
            .getElementById('modalMesa')
            .showModal()
    ">
        ADICIONAR MESA
    </button>

    <br><br>

    <div id="containerMesas">

        <?php if (empty($mesas)): ?>

            <p>
                Nenhuma mesa cadastrada.
            </p>

        <?php else: ?>

            <?php foreach ($mesas as $mesa): ?>

                <div id="mesa-<?= $mesa['id'] ?>">

                    <b>
                        Mesa <?= $mesa['numero'] ?>
                    </b>

                    <p>
                        Lugares:
                        <?= $mesa['quantidade_lugares'] ?>
                    </p>

                    <p>
                        Status:
                        <?= htmlspecialchars($mesa['status']) ?>
                    </p>

                </div>

                <hr>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>


<!-- =========================
     ÁREA RESERVAS
========================= -->

<div
    id="areaReservas"
    style="display:none;"
>

    <h2>Reservas</h2>

    <button onclick="
        carregarMesasSelect();
        document
            .getElementById('modalReserva')
            .showModal()
    ">
        ADICIONAR RESERVA
    </button>

    <br><br>

    <div id="containerReservas">

        <?php if (empty($reservas)): ?>

            <p>
                Nenhuma reserva cadastrada.
            </p>

        <?php else: ?>

            <?php foreach ($reservas as $reserva): ?>

                <div id="reserva-<?= $reserva['id'] ?>">

                    <b>
                        Mesa <?= $reserva['numero_mesa'] ?>
                    </b>

                    <p>
                        Cliente:
                        <?= htmlspecialchars(
                            $reserva['nome_cliente']
                        ) ?>
                    </p>

                    <p>
                        Telefone:
                        <?= htmlspecialchars(
                            $reserva['telefone']
                        ) ?>
                    </p>

                    <p>
                        Pessoas:
                        <?= $reserva['quantidade_pessoas'] ?>
                    </p>

                    <p>
                        Data:
                        <?= date(
                            'd/m/Y H:i',
                            strtotime(
                                $reserva['data_reserva']
                            )
                        ) ?>
                    </p>

                    <p>
                        Status:
                        <?= htmlspecialchars(
                            $reserva['status_reserva']
                        ) ?>
                    </p>

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

    <h3>
        Adicionar Mesa
    </h3>

    <form
        id="formMesa"
        onsubmit="salvarMesa(event)"
    >

        <input
            type="hidden"
            name="acao"
            value="adicionar_mesa"
        >

        <div>

            <label>
                Número da Mesa:
            </label>

            <input
                type="number"
                name="numero"
                min="1"
                required
            >

        </div>

        <br>

        <div>

            <label>
                Quantidade de Lugares:
            </label>

            <input
                type="number"
                name="quantidade_lugares"
                min="1"
                required
            >

        </div>

        <br>

        <button
            type="button"
            onclick="
                document
                    .getElementById('modalMesa')
                    .close()
            "
        >
            CANCELAR
        </button>

        <button type="submit">
            SALVAR
        </button>

    </form>

</dialog>


<!-- =========================
     MODAL RESERVA
========================= -->

<dialog id="modalReserva">

    <h3>
        Adicionar Reserva
    </h3>

    <form
        id="formReserva"
        onsubmit="salvarReserva(event)"
    >

        <input
            type="hidden"
            name="acao"
            value="adicionar_reserva"
        >


        <div>

            <label>
                Mesa:
            </label>

            <select
                name="mesa_id"
                id="mesaSelect"
                required
            >

                <option value="">
                    Selecione uma mesa
                </option>

            </select>

        </div>

        <br>


        <div>

            <label>
                Nome do Cliente:
            </label>

            <input
                type="text"
                name="nome_cliente"
                required
            >

        </div>

        <br>


        <div>

            <label>
                Telefone:
            </label>

            <input
                type="text"
                name="telefone"
                required
            >

        </div>

        <br>


        <div>

            <label>
                Quantidade de Pessoas:
            </label>

            <input
                type="number"
                name="quantidade_pessoas"
                min="1"
                required
            >

        </div>

        <br>


        <div>

            <label>
                Data e Hora:
            </label>

            <input
                type="datetime-local"
                name="data_reserva"
                required
            >

        </div>

        <br>


        <button
            type="button"
            onclick="
                document
                    .getElementById('modalReserva')
                    .close()
            "
        >
            CANCELAR
        </button>

        <button type="submit">
            SALVAR
        </button>

    </form>

</dialog>


<script>

/* =========================
   MOSTRAR MESAS
========================= */

function mostrarMesas() {

    document.getElementById(
        'areaMesas'
    ).style.display = 'block';

    document.getElementById(
        'areaReservas'
    ).style.display = 'none';

    carregarMesas();

}


/* =========================
   MOSTRAR RESERVAS
========================= */

function mostrarReservas() {

    document.getElementById(
        'areaMesas'
    ).style.display = 'none';

    document.getElementById(
        'areaReservas'
    ).style.display = 'block';

    carregarReservas();

}


/* =========================
   CARREGAR MESAS
========================= */

async function carregarMesas() {

    const dados = new FormData();

    dados.append(
        'acao',
        'listar_mesas'
    );


    const resposta = await fetch('', {

        method: 'POST',

        body: dados

    });


    document.getElementById(
        'containerMesas'
    ).innerHTML = await resposta.text();

}


/* =========================
   SALVAR MESA
========================= */

async function salvarMesa(e) {

    e.preventDefault();

    const form = e.target;

    const resposta = await fetch('', {

        method: 'POST',

        body: new FormData(form)

    });

    const texto = await resposta.text();


    /* Qualquer resposta que não seja o HTML da mesa é erro */

    if (!texto.trim().startsWith('<div')) {

        alert(texto);

        return;
    }


    const container =
        document.getElementById(
            'containerMesas'
        );


    const vazio =
        container.querySelector('p');


    if (
        vazio &&
        vazio.innerText.includes(
            'Nenhuma mesa'
        )
    ) {

        vazio.remove();

    }


    container.insertAdjacentHTML(
        'beforeend',
        texto
    );


    form.reset();

    document
        .getElementById('modalMesa')
        .close();

}


/* =========================
   CARREGAR MESAS NO SELECT
========================= */

async function carregarMesasSelect() {

    const dados = new FormData();

    dados.append(
        'acao',
        'listar_mesas'
    );


    const resposta = await fetch('', {

        method: 'POST',

        body: dados

    });


    const texto =
        await resposta.text();


    const select =
        document.getElementById(
            'mesaSelect'
        );


    select.innerHTML = `
        <option value="">
            Selecione uma mesa
        </option>
    `;


    const temporario =
        document.createElement('div');

    temporario.innerHTML = texto;


    const elementos =
        temporario.querySelectorAll(
            '[id^="mesa-"]'
        );


    elementos.forEach(elemento => {

        const id =
            elemento.id.replace(
                'mesa-',
                ''
            );


        const titulo =
            elemento.querySelector('b');


        if (!titulo) {
            return;
        }


        const numero =
            titulo.innerText
                .replace(
                    'Mesa ',
                    ''
                );


        select.innerHTML += `
            <option value="${id}">
                Mesa ${numero}
            </option>
        `;

    });

}


/* =========================
   SALVAR RESERVA
========================= */

async function salvarReserva(e) {

    e.preventDefault();

    const form = e.target;


    const resposta = await fetch('', {

        method: 'POST',

        body: new FormData(form)

    });


    const texto =
        await resposta.text();


    /* Qualquer resposta que não seja o HTML da reserva é erro */

    if (!texto.trim().startsWith('<div')) {

        alert(texto);

        return;
    }


    const container =
        document.getElementById(
            'containerReservas'
        );


    const vazio =
        container.querySelector('p');


    if (
        vazio &&
        vazio.innerText.includes(
            'Nenhuma reserva'
        )
    ) {

        vazio.remove();

    }


    container.insertAdjacentHTML(
        'beforeend',
        texto
    );


    /* Atualiza a lista de mesas (status passou a reservada) */

    carregarMesas();


    form.reset();

    document
        .getElementById('modalReserva')
        .close();

}


/* =========================
   CARREGAR RESERVAS
========================= */

async function carregarReservas() {

    const dados = new FormData();

    dados.append(
        'acao',
        'listar_reservas'
    );


    const resposta = await fetch('', {

        method: 'POST',

        body: dados

    });


    const texto =
        await resposta.text();


    document.getElementById(
        'containerReservas'
    ).innerHTML = texto;

}


/* =========================
   INICIAR
========================= */

mostrarMesas();

</script>

</body>

</html>
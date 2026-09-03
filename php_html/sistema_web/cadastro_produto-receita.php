<?php
include '../../php/conexao.php';

if (isset($_GET['action']) && $_GET['action'] === 'buscar_estoque') {
    header('Content-Type: application/json; charset=utf-8');
    
    $insumos = [];
    $sql = "SELECT id, nome, unidade_medida FROM estoque ORDER BY nome ASC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $insumos[] = $row;
        }
    }

    echo json_encode([
        'sucesso' => true,
        'dados' => $insumos
    ]);
    exit; 
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header('Content-Type: application/json; charset=utf-8');

    $nome        = $_POST['nome'] ?? '';
    $categoria   = $_POST['categoria'] ?? '';
    $preco_venda = $_POST['preco_venda'] ?? 0;
    $make_or_buy = $_POST['make_or_buy'] ?? '';
    $descricao   = $_POST['descricao'] ?? '';
    $visivel     = $_POST['visivel'] ?? 0;

    $ingredientes = $_POST['ingredientes'] ?? [];
    $quantidades  = $_POST['quantidades'] ?? [];

    // Validação de negócio
    if ($make_or_buy === 'produto_acabado' && empty($ingredientes)) {
        echo json_encode([
            'sucesso' => false, 
            'mensagem' => 'Para Produto Acabado, é obrigatório selecionar ao menos um ingrediente para a receita.'
        ]);
        exit;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        // INÍCIO DA TRANSAÇÃO SQL
        $conn->begin_transaction();

        // Step 1: Inserir o Produto
        $sql_prod = "INSERT INTO produtos (categorias, nome, descricao, preco_venda, visivel, make_or_buy) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_prod = $conn->prepare($sql_prod);
        $stmt_prod->bind_param("sssdis", $categoria, $nome, $descricao, $preco_venda, $visivel, $make_or_buy);
        $stmt_prod->execute();

        $id_produto = $conn->insert_id;
        $stmt_prod->close();

        // Step 2: Upload da Foto (se enviada)
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $arquivo = $_FILES['foto'];
            $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
            $novo_nome = "produto_" . $id_produto . "_" . bin2hex(random_bytes(4)) . "." . $extensao;
            $pasta_destino = "../../img/produtos/";

            if (move_uploaded_file($arquivo['tmp_name'], $pasta_destino . $novo_nome)) {
                $caminho_banco = $pasta_destino . $novo_nome;
                $stmt_img = $conn->prepare("UPDATE produtos SET imagem_caminho = ? WHERE id = ?");
                $stmt_img->bind_param("si", $caminho_banco, $id_produto);
                $stmt_img->execute();
                $stmt_img->close();
            }
        }

        // Step 3: Inserir Ingredientes em Receitas
        if ($make_or_buy === 'produto_acabado' && !empty($ingredientes)) {
            $stmt_rec = $conn->prepare("INSERT INTO receitas (produto_id, ingrediente_id, quantidade_usada) VALUES (?, ?, ?)");

            foreach ($ingredientes as $ingrediente_id) {
                $qtd_usada = isset($quantidades[$ingrediente_id]) ? floatval($quantidades[$ingrediente_id]) : 0;
                if ($qtd_usada > 0) {
                    $stmt_rec->bind_param("iid", $id_produto, $ingrediente_id, $qtd_usada);
                    $stmt_rec->execute();
                }
            }
            $stmt_rec->close();
        }

        
        $conn->commit();

        echo json_encode([
            'sucesso' => true, 
            'mensagem' => 'Produto e receita cadastrados com sucesso!'
        ]);

    } catch (Exception $e) {
        
        $conn->rollback();
        echo json_encode([
            'sucesso' => false, 
            'mensagem' => 'Erro ao salvar no banco: ' . $e->getMessage()
        ]);
    }
    exit; 
}


?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de novo produto</title>
</head>
<body>
    <main>
        
        <div id="mensagem"></div>

        <form id="formProduto" enctype="multipart/form-data">
            <section>
                <h2>Cadastro de novo produto</h2>
                
                <div>
                    <label for="nome">Nome:</label>
                    <input type="text" name="nome" id="nome" required>
                </div>

                <div>
                    <label for="preco_venda">Preço:</label>
                    <input type="number" id="preco_venda" name="preco_venda" step="0.01" min="0" placeholder="0.00" required>
                </div>

                <div>
                    <label for="tipo">Qual é o tipo de estoque do produto?</label>
                    <select name="make_or_buy" id="tipo" required onchange="alternarSessaoReceita()">
                        <option value="" hidden></option>
                        <option value="produto_acabado">Produto acabado</option>
                        <option value="revenda">Revenda</option>
                    </select>
                </div>

                <div>
                    <label for="select_categoria">Categoria alimentícia:</label>
                    <select name="categoria" id="select_categoria" required>
                        <option value="" hidden></option>
                        <option value="hamburguer">Hambúrguer</option>
                        <option value="porcoes">Porções</option>
                        <option value="bebidas">Bebidas</option>
                        <option value="combos">Combo</option>
                        <option value="sobremesa_gelada">Sobremesa gelada</option>
                        <option value="extras">Extra</option>
                    </select>
                </div>

                <div>
                    <label for="descricao">Descrição:</label>
                    <textarea id="descricao" name="descricao" rows="5" cols="40" required></textarea>
                </div>

                <div>
                    <label for="imagem">Foto:</label>
                    <input type="file" id="imagem" name="foto">
                </div>
            
                <div>
                    <label>Este item é visível no cardápio?</label>
                    <input type="radio" id="sim" name="visivel" value="1">
                    <label for="sim">Sim</label>
                    <input type="radio" id="nao" name="visivel" value="0" checked>
                    <label for="nao">Não</label>
                </div>

                
                <div id="secao_receita" class="campo-receita" style="display: none;">
                    <h3>Composição da Receita (Insumos)</h3>
                    <div id="lista_insumos">Carregando estoque...</div>
                </div>

                <br>
                <div>
                    <button type="submit">Cadastrar</button>
                    <button type="reset" onclick="document.getElementById('secao_receita').style.display='none'">Limpar</button>
                    <a href="index.php">Sair</a>
                </div>
            </section>
        </form>
    </main>


    <script>
        
        document.addEventListener('DOMContentLoaded', carregarInsumos);

        async function carregarInsumos() {
            const container = document.getElementById('lista_insumos');
            try {
                const response = await fetch('cadastro_produto.php?action=buscar_estoque');
                const res = await response.json();

                if (!res.sucesso || res.dados.length === 0) {
                    container.innerHTML = '<p class="erro">Atenção: Nenhum insumo cadastrado na tabela estoque. Cadastre insumos antes de continuar.</p>';
                    return;
                }

                let html = '<p>Selecione os ingredientes e informe a quantidade usada por porção:</p>';
                res.dados.forEach(insumo => {
                    html += `
                        <div class="item-ingrediente">
                            <input type="checkbox" 
                                   name="ingredientes[]" 
                                   value="${insumo.id}" 
                                   id="ing_${insumo.id}" 
                                   onchange="toggleQuantidade(this, ${insumo.id})">
                            
                            <label for="ing_${insumo.id}">
                                ${insumo.nome} (${insumo.unidade_medida})
                            </label>

                            <input type="number" 
                                   step="0.01" 
                                   min="0.01" 
                                   name="quantidades[${insumo.id}]" 
                                   id="qtd_${insumo.id}" 
                                   placeholder="Qtd usada" 
                                   class="qtd-input">
                        </div>
                    `;
                });
                container.innerHTML = html;

            } catch (err) {
                container.innerHTML = '<p class="erro">Erro ao carregar lista de estoque.</p>';
            }
        }

        
        function alternarSessaoReceita() {
            const selectTipo = document.getElementById('tipo');
            const secaoReceita = document.getElementById('secao_receita');
            secaoReceita.style.display = (selectTipo.value === 'produto_acabado') ? 'block' : 'none';
        }

        
        function toggleQuantidade(checkbox, idInsumo) {
            const campoQtd = document.getElementById('qtd_' + idInsumo);
            if (checkbox.checked) {
                campoQtd.style.display = 'inline-block';
                campoQtd.required = true;
            } else {
                campoQtd.style.display = 'none';
                campoQtd.required = false;
                campoQtd.value = '';
            }
        }

       
        document.getElementById('formProduto').addEventListener('submit', async function(e) {
            e.preventDefault();

            const divMsg = document.getElementById('mensagem');
            divMsg.innerHTML = '';

            const formData = new FormData(this);

            try {
                const response = await fetch('cadastro_produto.php', {
                    method: 'POST',
                    body: formData
                });

                const res = await response.json();

                if (res.sucesso) {
                    divMsg.innerHTML = `<p class="sucesso">${res.mensagem}</p>`;
                    this.reset();
                    document.getElementById('secao_receita').style.display = 'none';
                } else {
                    divMsg.innerHTML = `<p class="erro">${res.mensagem}</p>`;
                }
            } catch (err) {
                divMsg.innerHTML = '<p class="erro">Erro ao processar o formulário.</p>';
            }
        });
    </script>
</body>
</html>
<?php
include '../../php/conexao.php'; 

$mensagem_sucesso = "";
$mensagem_erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nome        = $_POST['nome']; 
    $categoria   = $_POST['categoria'];
    $preco_venda = $_POST['preco_venda']; 
    $make_or_buy = $_POST['make_or_buy']; 
    $descricao   = $_POST['descricao'];
    $visivel     = $_POST['visivel']; // Adicionado para capturar o radio button
    
    $sql = "INSERT INTO produtos (categorias, nome, descricao, preco_venda, visivel, make_or_buy) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdis", $categoria, $nome, $descricao, $preco_venda, $visivel, $make_or_buy);

    if ($stmt->execute()) {
        $id_produto = $conn->insert_id;
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            
            $arquivo = $_FILES['foto'];
            $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
            
            $novo_nome = "produto_" . $id_produto . "_" . bin2hex(random_bytes(4)) . "." . $extensao;
            // Ajustado para refletir a pasta 'img' da sua estrutura
            $pasta_destino = "../../img/produtos/"; 
            $caminho_completo = $pasta_destino . $novo_nome;

            if (move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
                $sql_update = "UPDATE produtos SET imagem_caminho = ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("si", $caminho_completo, $id_produto);
                $stmt_update->execute();
                $stmt_update->close();
                
                $mensagem_sucesso = "Produto cadastrado e imagem salva com sucesso!";
            } 
        }   
    }
    $stmt->close();
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
        <!-- Adicionado o enctype necessário para upload de arquivos -->
        <form method="POST" action="cadastro_produto.php" enctype="multipart/form-data">
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

                <div id="tipo">
                    <label for="tipo">Qual é o tipo de estoque do produto?</label>
                    <select name="make_or_buy" id="tipo" required>
                        <option value="" hidden></option>
                         <option value="produto_acabado">Produto acabado</option>
                         <option value="revenda">Revenda</option>
                    </select>
                </div>

                <div>
                    <label for="select_categoria">Categoria alimentícia:</label>
                    <select name="categoria" id="select_categoria" required>
                        <option value="" hidden></option>
                        <option value="hamburguer">Hamburguer</option>
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

                <!-- Descomentado e renomeado para coincidir com o PHP -->
                <div>
                    <label for="imagem">Foto:</label>
                    <input type="file" id="imagem" name="foto" required>
                </div>
            
                <div>
                    <label>Este item é visível no cardápio?</label>
                    <input type="radio" id="sim" name="visivel" value="1">
                    <label for="sim">Sim</label>
                    <input type="radio" id="nao" name="visivel" value="0" checked>
                    <label for="nao">Não</label>
                </div>

                <div>
                    <button type="submit">Cadastrar</button>
                    <button type="reset">Limpar</button>
                    <a href="">Sair</a>
                </div>
            </section>
        </form>
    </main>
</body>
</html>
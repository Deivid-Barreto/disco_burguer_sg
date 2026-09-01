<?php
include '../../php/conexao.php'; 

//fazer pag de carregamento, se der errado!!
$mensagem_sucesso = "";
$mensagem_erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nome        = $_POST['nome']; 
    $categoria   = $_POST['categoria'];
    $preco_venda = $_POST['preco_venda']; 
    $make_or_buy = ['make_or_buy']; 
    $categoria   = $_POST['categoria'];
    $descricao   = $_POST['descricao'];
    // $imagem_caminho = $_POST['imagem_caminho'];
    
    
    $sql = "INSERT INTO produtos (categorias, nome, descricao, preco_venda, visivel, make_or_buy) VALUES (?, ?, ?, ?, ?, ?)";
}        

?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>cad de novo produto</title>
</head>

<body>
    <main>
        <form method="POST" action="cadastro_produto.php">
            <section>
                <h2>Cadastro de novo produto</h2>
                
                <div>
                    <label for="nome">Nome:</label>
                    <input type="text" name="nome" id="nome" required>
                </div>

                <div>
                    <label for="preco_venda">Preço:</label>
                    <input type="number" id="preco_venda" name="preco_venda" step="0.01" min="0" placeholder="0,00"
                        required>
                </div>

                <div id="tipo">
                    <label for="tipo">Qual é o tipo de estoque do pruduto?</label>
                    <select name="make_or_buy" id="tipo">
                         <option value="produto_acabado">Produto acabado</option>
                         <option value="revenda">Revenda</option>
                    </select>
                </div>

                <div>
                    <label for="select_categoria">Categoria alimentícia:</label>
                    <select name="categoria" id="select_categoria" required>
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

                

            <!--    <div>
                    <label for="imagem">Foto:</label>
                    <input type="file" id="imagem" name="imagem_caminho" required>
                </div>
            -->
                <div>
                    <label for="sim">Este item é visível no cardpio?</label>
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

    <script>
    </script>


</body>

</html>
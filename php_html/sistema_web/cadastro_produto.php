<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>cad de novo produto</title>
</head>

<body>
    <main>
        <form method="POST">
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
                    <label for="tipo">Qual é o tipo de produto a ser cadastrado?</label>
                    <select name="make_or_buy" id="tipo">
                         <option value="">Carregando tipos..</option>
                    </select>
                    <input type="hidden" name="tipo_id_hiden" value="">
                </div>

                <div>
                    <label for="select_categoria">Categoria:</label>
                    <select name="categoria" id="select_categoria" required>
                        <option value="">Carregando categorias...</option>
                    </select>
                    <input type="hidden" name="categoria_id_hidden" value="">
                </div>

                <div>
                    <label for="descricao">Descrição:</label>
                    <textarea id="descricao" name="descricao" rows="5" cols="40" required></textarea>
                </div>

                

                <div>
                    <label for="imagem">Foto:</label>
                    <input type="file" id="imagem" name="imagemcaminho" required>
                </div>

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
         document.addEventListener("DOMContentLoaded", function() {
            const selectElement = document.getElementById("select_categoria");
            fetch("get_enum.php")
                .then(response => response.json())
                .then(categorias => {
                    selectElement.innerHTML = '<option value="">-- Escolha uma opção --</option>';
                    categorias.forEach(opcao => {
                        const option = document.createElement("option");
                        option.value = opcao;
                        option.textContent = opcao;
                        selectElement.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error("Erro ao carregar ENUM:", error);
                    selectElement.innerHTML = '<option value="">Erro ao carregar opções</option>';
                });
        });
    </script>


</body>

</html>
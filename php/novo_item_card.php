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
                <div id="bloco_revenda">
                    <label for="sim">Este item é revenda?</label>
                    <input type="radio" id="sim" name="revenda" value="1">
                    <label for="sim">Sim</label>
                    <input type="radio" id="nao" name="revenda" value="0" checked>
                    <label for="nao">Não</label>
                </div>

                <div id="bloco_combo">
                    <label for="sim">Este item é um combo?</label>
                    <input type="radio" id="sim" name="combo" value="1">
                    <label for="sim">Sim</label>
                    <input type="radio" id="nao" name="combo" value="0" checked>
                    <label for="nao">Não</label>
                </div>

                <div>
                    <label for="nome">Nome:</label>
                    <input type="text" name="nome" id="nome" required>
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
                    <label for="preco_venda">Preço:</label>
                    <input type="number" id="preco_venda" name="preco_venda" step="0.01" min="0" placeholder="0,00"
                        required>
                </div>

                <div>
                    <label for="imagem">Foto:</label>
                    <input type="file" id="imagem" name="imagemcaminho" required>
                </div>

                <div>
                    <label for="sim">Este item é visível no cardpio?</label>
                    <input type="radio" id="sim" name="mostrar_no_cardapio" value="1">
                    <label for="sim">Sim</label>
                    <input type="radio" id="nao" name="mostrar_no_cardapio" value="0" checked>
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
        document.addEventListener("DOMContentLoaded", function () {
    const selectcategoria = document.getElementById('select_categoria');
    const blocorevendacontainer = document.getElementById('bloco_revenda');
    const blococombocontainer = document.getElementById('bloco_combo');
    const radiosrevenda = document.querySelectorAll('input[name="revenda"]');
    const radioscombo = document.querySelectorAll('input[name="combo"]');

    function aplicarregrasinterface() {
        const revendaSelecionada = document.querySelector('input[name="revenda"]:checked')?.value === '1';
        const comboSelecionado = document.querySelector('input[name="combo"]:checked')?.value === '1';

        if (revendaSelecionada) {
            // Se for revenda: esconde combo e trava na categoria de revenda
            if (blococombocontainer) blococombocontainer.style.display = 'none';
            if (blocorevendacontainer) blocorevendacontainer.style.display = 'block';

            selectcategoria.value = '4';
            selectcategoria.disabled = true;

        } else if (comboSelecionado) {
            
            if (blocorevendacontainer) blocorevendacontainer.style.display = 'none';
            if (blococombocontainer) blococombocontainer.style.display = 'block';

            selectcategoria.value = '2'; 
            selectcategoria.disabled = true;

        } else {
         
            if (blocorevendacontainer) blocorevendacontainer.style.display = 'block';
            if (blococombocontainer) blococombocontainer.style.display = 'block';

            selectcategoria.disabled = false;
            selectcategoria.value = '';
        }
    }

   
    fetch('buscar_categorias.php')
        .then(response => response.json())
        .then(categorias => {
            selectcategoria.innerHTML = '<option value="">Selecione uma categoria...</option>';
            
            categorias.forEach(cat => {
                let option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.nome.toUpperCase();
                selectcategoria.appendChild(option);
            });

            
            aplicarregrasinterface();
        })
        .catch(error => console.error('Erro ao carregar categorias', error));

   
    radiosrevenda.forEach(radio => radio.addEventListener('change', aplicarregrasinterface));
    radioscombo.forEach(radio => radio.addEventListener('change', aplicarregrasinterface));
});
    </script>


</body>

</html>
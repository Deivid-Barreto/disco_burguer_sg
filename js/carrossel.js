
  const itens = document.querySelectorAll(".carrossel-item");
    const total = itens.length;
    let indiceAtual = 0;

    function atualizarCarrossel() {
      itens.forEach(function(item, i) {
        let distancia = i - indiceAtual;

        if (distancia > total / 2) distancia -= total;
        if (distancia < -total / 2) distancia += total;

        item.classList.remove("item-ativo", "item-esquerda", "item-direita");

        if (distancia === 0) item.classList.add("item-ativo");
        else if (distancia === -1) item.classList.add("item-esquerda");
        else if (distancia === 1) item.classList.add("item-direita");
      });
    }

    document.querySelector(".seta-proximo").addEventListener("click", function() {
      indiceAtual = (indiceAtual + 1) % total;
      atualizarCarrossel();
    });

    document.querySelector(".seta-anterior").addEventistener("click", function() {
      indiceAtual = (indiceAtual - 1 + total) % total;
      atualizarCarrossel();
    });

    atualizarCarrossel();
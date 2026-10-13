document.addEventListener("DOMContentLoaded", () => {
  const botoesAbrirModal = document.querySelectorAll(".btn-abrir-modal");
  
  // Instancia o modal do Bootstrap
  const modalProdutoElement = document.getElementById("modalProduto");
  let modalInstancia = null;
  
  if(modalProdutoElement){
    modalInstancia = new bootstrap.Modal(modalProdutoElement);
  }

  botoesAbrirModal.forEach((botao) => {
    botao.addEventListener("click", (event) => {
      // Pega os dados armazenados nos atributos data-* do botão
      const nome = botao.getAttribute("data-nome");
      const descricao = botao.getAttribute("data-descricao");
      const preco = botao.getAttribute("data-preco");
      const imagem = botao.getAttribute("data-imagem");

      // Preenche os elementos dentro do modal
      document.getElementById("modalNome").textContent = nome;
      document.getElementById("modalDescricao").textContent = descricao;
      document.getElementById("modalPreco").textContent = preco;
      document.getElementById("modalImagem").src = imagem;

      // Abre o modal
      if(modalInstancia){
         modalInstancia.show();
      }
    });
  });
});
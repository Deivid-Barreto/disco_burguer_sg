<div class="modal fade" id="modalProduto" tabindex="-1" aria-labelledby="modalProdutoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-tamanho-personalizado">
    <div class="modal-content modal-neon position-relative">
      
      <button type="button" class="btn-fechar-neon" data-bs-dismiss="modal" aria-label="Close">&times;</button>
      
      <div class="modal-body p-4">
        <div class="row align-items-stretch">
          
          <!-- Lado Esquerdo: Imagem -->
          <div class="col-lg-6 mb-3 mb-lg-0">
            <div class="imagem-modal-container">
              <img id="modalImagem" src="" alt="Imagem do Produto">
            </div>
          </div>
          
          <!-- Lado Direito: Informações e Ações -->
          <div class="col-lg-6 text-start d-flex flex-column justify-content-between py-1">
            <div>
              <h2 id="modalNome" class="fw-bold text-white mb-2 modal-titulo"></h2>
              <p id="modalDescricao" class="text-light opacity-75 mb-3 modal-descricao"></p>
            </div>
            
            <div>
              <h3 id="modalPreco" class="fw-bold mb-3 modal-preco"></h3>
              <button id="btnComprar" class="btn button-itens-cardapio w-100 py-2">COMPRAR</button>
            </div>
          </div>
          
        </div>
      </div>
    </div>
  </div>
</div>
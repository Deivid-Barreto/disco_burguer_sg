
    const scrollCards = document.getElementById('scroll-cards');
    const btnEsquerda = document.getElementById('btn-esquerda');
    const btnDireita = document.getElementById('btn-direita');

    // ==========================================
    // 1. CÓDIGO DAS SETAS (Já estava antes)
    // ==========================================
    btnEsquerda.addEventListener('click', () => {
        scrollCards.scrollBy({ left: -450, behavior: 'smooth' });
    });

    btnDireita.addEventListener('click', () => {
        scrollCards.scrollBy({ left: 450, behavior: 'smooth' });
    });

    // ==========================================
    // 2. CÓDIGO PARA ARRASTAR COM O MOUSE (NOVO)
    // ==========================================
    let isDown = false; // Verifica se o botão do mouse está apertado
    let startX;         // Posição inicial do clique
    let scrollLeft;     // Onde o scroll estava quando clicou

    scrollCards.addEventListener('mousedown', (e) => {
        isDown = true;
        // Pega a posição exata onde o usuário clicou
        startX = e.pageX - scrollCards.offsetLeft;
        scrollLeft = scrollCards.scrollLeft;
        
        // Remove o smooth temporariamente para o arraste não ficar travando/lento
        scrollCards.style.scrollBehavior = 'auto'; 
    });

    scrollCards.addEventListener('mouseleave', () => {
        isDown = false; // Se o mouse sair do container, para de arrastar
    });

    scrollCards.addEventListener('mouseup', () => {
        isDown = false; // Se soltar o clique, para de arrastar
        
        // Devolve o smooth para as setas voltarem a ser suaves
        scrollCards.style.scrollBehavior = 'smooth'; 
    });

    scrollCards.addEventListener('mousemove', (e) => {
        if (!isDown) return; // Se não estiver clicando, não faz nada
        e.preventDefault(); // Evita selecionar texto ou imagem sem querer
        
        // Calcula a distância que o mouse moveu
        const x = e.pageX - scrollCards.offsetLeft;
        const walk = (x - startX) * 1.5; // O "1.5" é a velocidade. Aumente se quiser mais rápido.
        
        // Move o scroll
        scrollCards.scrollLeft = scrollLeft - walk;
    });

// Função de rolagem suave lenta (já criada anteriormente)
function rolagemSuaveLenta(elementoAlvo, duracaoEmMs) {
    const alvo = document.querySelector(elementoAlvo);
    if (!alvo) return;

    const posicaoInicial = window.pageYOffset;
    const posicaoFinal = alvo.getBoundingClientRect().top + posicaoInicial;
    const distancia = posicaoFinal - posicaoInicial;
    let tempoInicio = null;

    function animacao(tempoAtual) {
        if (tempoInicio === null) tempoInicio = tempoAtual;
        const tempoDecorrido = tempoAtual - tempoInicio;
        
        const progresso = Math.min(tempoDecorrido / duracaoEmMs, 1);
        const ease = progresso < 0.5 ? 2 * progresso * progresso : -1 + (4 - 2 * progresso) * progresso;

        window.scrollTo(0, posicaoInicial + (distancia * ease));

        if (tempoDecorrido < duracaoEmMs) {
            requestAnimationFrame(animacao);
        }
    }

    requestAnimationFrame(animacao);
}

document.addEventListener("DOMContentLoaded", function () {
    // Função genérica para lidar com cliques em links de âncora na Home
    function configurarLinkSmooth(classeSeletor, idAlvo) {
        const links = document.querySelectorAll(classeSeletor);

        links.forEach(link => {
            link.addEventListener("click", function (e) {
                const caminhoAtual = window.location.pathname;
                const ehPaginaInicial = caminhoAtual.endsWith("index.php") || caminhoAtual === "/" || caminhoAtual.endsWith("");

                if (ehPaginaInicial) {
                    e.preventDefault();
                    rolagemSuaveLenta(idAlvo, 1500); // 1500ms = 1.5 segundos de rolagem
                    history.pushState(null, null, idAlvo);
                }
            });
        });

        // Caso a página já abra direto com a âncora na URL
        if (window.location.hash === idAlvo) {
            setTimeout(() => {
                rolagemSuaveLenta(idAlvo, 1500);
            }, 100);
        }
    }

    // Ativa para o Feedback e para o Contato/Footer
    configurarLinkSmooth(".link-feedback", "#feedback");
    configurarLinkSmooth(".link-contato", "#contato");
});
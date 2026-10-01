
  (function () {
    const slides = [...document.querySelectorAll('#carrossel-mp .slide-mp')];
    const pontosBox = document.getElementById('pontos-mp');
    const pista = document.querySelector('#carrossel-mp .pista-mp');
    const n = slides.length;
    let atual = 0;
    let timer;

    // cria os pontinhos
    const pontos = slides.map((_, i) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.setAttribute('aria-label', 'Ir para o item ' + (i + 1));
      b.addEventListener('click', () => { ir(i); reiniciar(); });
      pontosBox.appendChild(b);
      return b;
    });

    function render() {
      slides.forEach((s, i) => {
        let d = i - atual;
        if (d > n / 2) d -= n;       // dá a volta (circular)
        if (d < -n / 2) d += n;
        s.dataset.pos = Math.abs(d) <= 1 ? d : 'oculto';
      });
      pontos.forEach((p, i) => p.classList.toggle('ativo', i === atual));
    }

    function ir(i) { atual = (i + n) % n; render(); }
    function proximo() { ir(atual + 1); }
    function anterior() { ir(atual - 1); }

    // setas
    document.querySelector('.seta-next').addEventListener('click', () => { proximo(); reiniciar(); });
    document.querySelector('.seta-prev').addEventListener('click', () => { anterior(); reiniciar(); });

    // clicar numa imagem lateral traz ela pro centro
    slides.forEach((s, i) => s.addEventListener('click', () => { ir(i); reiniciar(); }));

    // arrastar / deslizar (mouse e toque)
    let inicioX = null;
    pista.addEventListener('pointerdown', e => { inicioX = e.clientX; });
    pista.addEventListener('pointerup', e => {
      if (inicioX === null) return;
      const dx = e.clientX - inicioX;
      if (Math.abs(dx) > 40) { dx < 0 ? proximo() : anterior(); reiniciar(); }
      inicioX = null;
    });

    // autoplay (apague essas linhas se não quiser)
    function reiniciar() { clearInterval(timer); timer = setInterval(proximo, 4000); }
    reiniciar();

    render();
  })();

const menu = document.getElementById('menuLateral');

// quando o menu abrir: empurra o conteúdo
menu.addEventListener('show.bs.offcanvas', () => {
    document.body.classList.add('menu-aberto');
});

// quando o menu fechar: o conteúdo volta ao lugar
menu.addEventListener('hidden.bs.offcanvas', () => {
    document.body.classList.remove('menu-aberto');
});

// se a tela for grande (computador), abre o menu sozinho
if (window.innerWidth >= 992) {
    bootstrap.Offcanvas.getOrCreateInstance(menu).show();
}
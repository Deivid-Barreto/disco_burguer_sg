// Scripts globais da aplicação
console.log("Sistema Disco Burguer carregado com sucesso!");

// Exemplo de interatividade nos botões de categoria
document.querySelectorAll('.menu-categories button').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelector('.menu-categories button.active')?.classList.remove('active');
        button.classList.add('active');
    });
});
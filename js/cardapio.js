document.addEventListener("DOMContentLoaded", () => {

  const sections = document.querySelectorAll(".secao-cardapio");
  const navLinks = document.querySelectorAll(".btn-categoria");


  const observerOptions = {
    root: null,
    rootMargin: "-20% 0px -60% 0px",
    threshold: 0
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const activeId = entry.target.getAttribute("id");

        navLinks.forEach((link) => {
          link.classList.remove("active");
          if (link.getAttribute("href") === `#${activeId}`) {
            link.classList.add("active");
          }
        });
      }
    });
  }, observerOptions);


  sections.forEach((section) => observer.observe(section));
});

window.addEventListener("scroll", () => {
  const menu = document.querySelector(".menu-categorias");
  const footer = document.querySelector("footer");

  if (!menu || !footer) return;

  const footerRect = footer.getBoundingClientRect();
  const windowHeight = window.innerHeight;

  // Se rolou mais que 200px e o rodapé ainda está longe da tela
  if (window.scrollY > 200) {
    // Verifica se o topo do rodapé já entrou na área visível da tela (com margem de 100px)
    if (footerRect.top <= windowHeight - 50) {
      // Quando o rodapé estiver chegando, remove o fixed para ele parar de flutuar
      menu.classList.remove("fixo");
    } else {
      // Caso contrário, mantém fixo flutuando na tela
      menu.classList.add("fixo");
    }
  } else {
    // Se estiver no topo da página, remove o fixo
    menu.classList.remove("fixo");
  }
});
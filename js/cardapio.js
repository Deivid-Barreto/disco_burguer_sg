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
  const footer = document.querySelector("footer"); // Seleciona o rodapé

  if (!menu) return;

  const scrollY = window.scrollY;

  // Lógica para aparecer/sumir o menu fixo com base no scroll e no footer
  if (footer) {
    const footerRect = footer.getBoundingClientRect();
    const windowHeight = window.innerHeight;

    // Se o topo do footer entrou na tela (ou chegou perto do menu), esconde o menu
    if (footerRect.top <= windowHeight - 100) {
      menu.classList.remove("fixo");
      return;
    }
  }

  // Comportamento normal de fixar após 200px
  if (scrollY > 200) {
    menu.classList.add("fixo");
  } else {
    menu.classList.remove("fixo");
  }
});  
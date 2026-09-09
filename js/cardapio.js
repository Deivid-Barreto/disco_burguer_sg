document.addEventListener("DOMContentLoaded", () => {

  const sections = document.querySelectorAll(".secao-cardapio");
  const navLinks = document.querySelectorAll(".btn-categoria");

  // Configuração do observador de tela
  const observerOptions = {
    root: null,
    rootMargin: "-10% 0px -60% 0px",
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
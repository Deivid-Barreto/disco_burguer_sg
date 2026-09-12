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
  

  if (window.scrollY > 200) {
    menu.classList.add("fixo");
  } else {
    menu.classList.remove("fixo");
  }
});
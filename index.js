document.addEventListener("DOMContentLoaded", () => {
  const menuItems = Array.from(document.querySelectorAll(".menu__item"));
  const sections = menuItems
    .map((item) => document.querySelector(item.getAttribute("href")))
    .filter(Boolean);

  function setActive(id) {
    menuItems.forEach((item) => {
      item.classList.toggle("is-active", item.getAttribute("href") === `#${id}`);
    });
  }

  // Destaca o item clicado imediatamente (não espera o scroll terminar)
  menuItems.forEach((item) => {
    item.addEventListener("click", () => {
      setActive(item.getAttribute("href").slice(1));
    });
  });

  // Mantém o menu sincronizado com a seção visível ao rolar a página
  const observer = new IntersectionObserver(
    (entries) => {
      const visible = entries
        .filter((entry) => entry.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

      if (visible) {
        setActive(visible.target.id);
      }
    },
    { threshold: [0.4, 0.6] }
  );

  sections.forEach((section) => observer.observe(section));

  // Ativa o primeiro item por padrão
  if (menuItems.length) {
    setActive(sections[0]?.id ?? menuItems[0].getAttribute("href").slice(1));
  }
});

// Aqui pode vir lógica geral: ajustes responsivos, escuta de resize etc.
window.addEventListener('resize', () => {
  // Exemplo: adaptar altura do chat
  const vh = window.innerHeight * 0.01;
  document.documentElement.style.setProperty('--vh', `${vh}px`);
});

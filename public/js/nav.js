document.addEventListener('DOMContentLoaded', function() {
    // Destacar link ativo com base na URL atual
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href) && !link.closest('.nav-item-with-submenu')) {
            link.classList.add('active');
        }
    });
    
    // Manipuladores para os botões de "Criar Sala" e "Entrar em Sala"
    document.querySelectorAll('.create-room').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Funcionalidade "Criar Sala" será implementada em breve!');
        });
    });
    
    document.querySelectorAll('.join-room').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Funcionalidade "Entrar em Sala" será implementada em breve!');
        });
    });
});
      
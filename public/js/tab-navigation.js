(function() {
    // Seleciona todos os elementos dropdown do menu
    const dropdowns = document.querySelectorAll('.dropdown');
    
    // Configura cada dropdown para funcionar com hover
    dropdowns.forEach(dropdown => {
        // Adiciona delay para evitar fechamento acidental
        let timeout;
        
        // Mouse enter: mostra o menu
        dropdown.addEventListener('mouseenter', () => {
            clearTimeout(timeout);
            
            // Fecha outros dropdowns primeiro
            document.querySelectorAll('.dropdown').forEach(other => {
                if (other !== dropdown) {
                    other.classList.remove('dropdown-active');
                }
            });
            
            dropdown.classList.add('dropdown-active');
        });
        
        // Mouse leave: esconde o menu com delay
        dropdown.addEventListener('mouseleave', () => {
            timeout = setTimeout(() => {
                dropdown.classList.remove('dropdown-active');
            }, 200);
        });
    });
    
    // Código para os links de Criar/Entrar em sala
    document.querySelectorAll('.create-room').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Funcionalidade "Criar Sala" será implementada em breve!');
        });
    });
    
    document.querySelectorAll('.join-room').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Funcionalidade "Entrar em Sala" será implementada em breve!');
        });
    });
})();

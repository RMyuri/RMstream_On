/**
 * JavaScript para a funcionalidade de pesquisa no RMStream
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elementos
    const searchInput = document.querySelector('.search-input');
    const searchForm = document.querySelector('.search-form');
    const searchToggle = document.getElementById('searchToggle');
    
    // Focar no campo de pesquisa
    if (searchInput && !searchInput.value.trim()) {
        searchInput.focus();
    }
    
    // Animação na barra de pesquisa
    if (searchInput) {
        searchInput.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        searchInput.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    }
    
    // Botão de pesquisa na navegação
    if (searchToggle) {
        searchToggle.addEventListener('click', function() {
            // Rolar para a barra de pesquisa e focar
            const searchHero = document.querySelector('.search-hero');
            if (searchHero) {
                searchHero.scrollIntoView({ behavior: 'smooth' });
                
                // Focar após a animação de scroll
                setTimeout(() => {
                    if (searchInput) searchInput.focus();
                }, 500);
            }
        });
    }
    
    // Destacar termos pesquisados nos resultados
    highlightSearchTerms();
    
    // Animação de entrada para os resultados
    animateResults();
    
    /**
     * Destaca os termos pesquisados nos resultados
     */
    function highlightSearchTerms() {
        // Obter termo de pesquisa da URL
        const urlParams = new URLSearchParams(window.location.search);
        const searchTerm = urlParams.get('q');
        
        if (!searchTerm) return;
        
        // Elementos onde destacar (nomes, textos, etc)
        const elements = document.querySelectorAll('.search-result-name, .search-result-text');
        
        elements.forEach(element => {
            const text = element.textContent;
            
            // Criar regex para cada palavra da busca (case insensitive)
            const searchWords = searchTerm.split(/\s+/).filter(word => word.length > 2);
            
            if (searchWords.length > 0) {
                let newHtml = text;
                
                searchWords.forEach(word => {
                    // Escapar caracteres especiais do regex
                    const escapedWord = word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    const regex = new RegExp(`(${escapedWord})`, 'gi');
                    newHtml = newHtml.replace(regex, '<mark>$1</mark>');
                });
                
                if (newHtml !== text) {
                    element.innerHTML = newHtml;
                }
            }
        });
    }
    
    /**
     * Anima a entrada dos resultados na página
     */
    function animateResults() {
        const cards = document.querySelectorAll('.search-result-card');
        
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 + (index * 50)); // Atraso crescente para cada card
        });
    }
    
    // Implementação das funções de amizade
    initFriendshipButtons();
    
    function initFriendshipButtons() {
        document.querySelectorAll('.add-friend-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                sendFriendRequest(userId, this);
            });
        });
        
        document.querySelectorAll('.accept-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                acceptFriendRequest(userId, this);
            });
        });
        
        document.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                rejectFriendRequest(userId, this);
            });
        });
    }
    
    function sendFriendRequest(userId, button) {
        // Desabilitar botão e mostrar loading
        button.disabled = true;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        // Enviar solicitação via AJAX
        const formData = new FormData();
        formData.append('action', 'send_request');
        formData.append('user_id', userId);
        
        fetch('/RMStream/api/friendship.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualizar botão
                button.innerHTML = '<i class="fas fa-clock"></i> Solicitação Enviada';
                button.classList.remove('primary');
                button.classList.add('secondary');
            } else {
                // Restaurar botão com mensagem de erro
                button.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 3000);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            button.innerHTML = '<i class="fas fa-exclamation-circle"></i> Erro';
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 3000);
        });
    }
    
    function acceptFriendRequest(userId, button) {
        // Desabilitar botão e mostrar loading
        button.disabled = true;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        // Enviar solicitação via AJAX
        const formData = new FormData();
        formData.append('action', 'accept_request');
        formData.append('user_id', userId);
        
        fetch('/RMStream/api/friendship.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualizar os botões
                const parentDiv = button.closest('.search-result-actions');
                parentDiv.innerHTML = `
                    <a href="/RMStream/views/chat/conversation.php?user_id=${userId}" class="search-result-btn primary">
                        <i class="fas fa-comment"></i> Mensagem
                    </a>
                `;
            } else {
                // Restaurar botão com mensagem de erro
                button.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 3000);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            button.innerHTML = '<i class="fas fa-exclamation-circle"></i> Erro';
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 3000);
        });
    }
    
    function rejectFriendRequest(userId, button) {
        // Implementação similar a acceptFriendRequest
        button.disabled = true;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        const formData = new FormData();
        formData.append('action', 'reject_request');
        formData.append('user_id', userId);
        
        fetch('/RMStream/api/friendship.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualizar os botões
                const parentDiv = button.closest('.search-result-actions');
                parentDiv.innerHTML = `
                    <button class="search-result-btn primary add-friend-btn" data-user-id="${userId}">
                        <i class="fas fa-user-plus"></i> Adicionar Amigo
                    </button>
                    <a href="/RMStream/views/profile/index.php?id=${userId}" class="search-result-btn secondary">
                        <i class="fas fa-user"></i> Ver Perfil
                    </a>
                `;
                
                // Reativar os botões
                initFriendshipButtons();
            } else {
                // Restaurar botão com mensagem de erro
                button.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 3000);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            button.innerHTML = '<i class="fas fa-exclamation-circle"></i> Erro';
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 3000);
        });
    }
});
                if (loadingMore) loadingMore.remove();
            }
            
            console.error("Search error:", error);
        } finally {
            isLoading = false;
        }
    }

    // Detectar quando o usuário rola até o final da página
    function setupInfiniteScroll() {
        window.addEventListener('scroll', () => {
            const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
            
            // Se estiver próximo do final da página (100px de margem)
            if (scrollTop + clientHeight >= scrollHeight - 100 && !isLoading && !noMoreResults && query) {
                searchYouTube(query, true);
            }
        });
    }

    // Se tiver uma query, busca vídeos
    if (query) {
        searchYouTube(query);
        setupInfiniteScroll();
    } else {
        resultsDiv.innerHTML = "<div class='no-results'>Digite algo na busca acima.</div>";
    }
})();

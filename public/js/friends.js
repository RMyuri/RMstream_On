/**
 * Gerenciamento de amizades para RMStream
 */
document.addEventListener('DOMContentLoaded', function() {
    // Referências aos elementos DOM
    const friendsTabs = document.querySelectorAll('.friends-tab');
    const friendsSections = document.querySelectorAll('.friends-section');
    
    // Botões de ações de amizade
    const friendButtons = document.querySelectorAll('[data-action]');
    
    // Inicialização de tabs
    initTabs();
    
    // Inicialização de ações de amizade
    initFriendActions();
    
    /**
     * Inicializa as abas da interface
     */
    function initTabs() {
        friendsTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remover classe active de todas as abas
                friendsTabs.forEach(t => t.classList.remove('active'));
                
                // Adicionar classe active na aba clicada
                this.classList.add('active');
                
                // Esconder todas as seções
                friendsSections.forEach(section => {
                    section.style.display = 'none';
                });
                
                // Mostrar a seção correspondente
                const targetSection = document.getElementById(this.getAttribute('data-tab') + 'Section');
                if (targetSection) {
                    targetSection.style.display = 'block';
                }
            });
        });
    }
    
    /**
     * Inicializa os botões de ações de amizade
     */
    function initFriendActions() {
        friendButtons.forEach(button => {
            button.addEventListener('click', handleFriendAction);
        });
    }
    
    /**
     * Manipula as ações de amizade (adicionar, aceitar, rejeitar, etc.)
     */
    function handleFriendAction(event) {
        const button = event.currentTarget;
        const action = button.getAttribute('data-action');
        const userId = button.getAttribute('data-user-id');
        
        if (!action || !userId) return;
        
        // Desabilitar o botão e mostrar estado de carregamento
        button.disabled = true;
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        // Preparar dados para envio
        const formData = new FormData();
        formData.append('action', action);
        formData.append('user_id', userId);
        
        // Enviar solicitação
        fetch('/RMStream/api/friendship.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar feedback temporário
                showFeedback(button, data.message, 'success');
                
                // Atualizar interface com base na ação
                updateInterface(button, action, userId);
            } else {
                // Mostrar mensagem de erro
                showFeedback(button, data.message, 'error');
                
                // Restaurar botão
                setTimeout(() => {
                    button.disabled = false;
                    button.innerHTML = originalHtml;
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showFeedback(button, 'Erro ao processar solicitação', 'error');
            
            // Restaurar botão
            setTimeout(() => {
                button.disabled = false;
                button.innerHTML = originalHtml;
            }, 2000);
        });
    }
    
    /**
     * Exibe feedback temporário após uma ação
     */
    function showFeedback(element, message, type) {
        // Criar elemento de feedback
        const feedback = document.createElement('div');
        feedback.className = `feedback ${type}`;
        feedback.textContent = message;
        
        // Encontrar elemento pai adequado para inserir o feedback
        const parent = element.closest('.friend-card, .friend-request-card, .sent-request-card, .user-card') || element.parentNode;
        
        // Inserir feedback
        parent.appendChild(feedback);
        
        // Remover após um tempo
        setTimeout(() => {
            feedback.classList.add('fade-out');
            setTimeout(() => {
                if (feedback.parentNode) {
                    feedback.parentNode.removeChild(feedback);
                }
            }, 300);
        }, 3000);
    }
    
    /**
     * Atualiza a interface após uma ação de amizade
     */
    function updateInterface(button, action, userId) {
        const card = button.closest('.friend-card, .friend-request-card, .sent-request-card, .user-card');
        
        if (!card) return;
        
        switch (action) {
            case 'send_request':
                // Transformar o botão de adicionar em cancelar
                button.setAttribute('data-action', 'cancel_request');
                button.innerHTML = '<i class="fas fa-times"></i> Cancelar';
                button.classList.remove('add-friend-btn');
                button.classList.add('cancel-btn');
                button.disabled = false;
                break;
                
            case 'accept_request':
                // Animar o card para remoção
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '0';
                card.style.transform = 'translateX(100%)';
                
                setTimeout(() => {
                    card.remove();
                    
                    // Verificar se há mais solicitações
                    const remainingRequests = document.querySelectorAll('#requestsSection .friend-request-card');
                    if (remainingRequests.length === 0) {
                        document.querySelector('#requestsSection .friend-requests-list').innerHTML = `
                            <div class="empty-list">
                                <i class="fas fa-user-plus"></i>
                                <p>Você não tem solicitações de amizade pendentes.</p>
                            </div>
                        `;
                    }
                    
                    // Adicionar à lista de amigos
                    refreshFriendsList();
                }, 500);
                break;
                
            case 'reject_request':
            case 'cancel_request':
            case 'unfriend':
                // Animar o card para remoção
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '0';
                card.style.transform = 'translateX(100%)';
                
                setTimeout(() => {
                    card.remove();
                    
                    // Verificar se há mais itens na lista
                    const container = card.parentNode;
                    if (container && container.children.length === 0) {
                        // Adicionar mensagem de lista vazia
                        const emptyMessage = document.createElement('div');
                        emptyMessage.className = 'empty-list';
                        
                        if (action === 'reject_request') {
                            emptyMessage.innerHTML = `
                                <i class="fas fa-user-plus"></i>
                                <p>Você não tem solicitações de amizade pendentes.</p>
                            `;
                            container.appendChild(emptyMessage);
                        } else if (action === 'cancel_request') {
                            emptyMessage.innerHTML = `
                                <i class="fas fa-paper-plane"></i>
                                <p>Você não enviou solicitações de amizade que estejam pendentes.</p>
                            `;
                            container.appendChild(emptyMessage);
                        } else if (action === 'unfriend') {
                            emptyMessage.innerHTML = `
                                <i class="fas fa-user-friends"></i>
                                <p>Você ainda não tem amigos. Comece adicionando pessoas!</p>
                            `;
                            container.appendChild(emptyMessage);
                        }
                    }
                    
                    // Se for unfriend, também atualizar a interface para mostrar o botão de adicionar novamente
                    if (action === 'unfriend') {
                        refreshAddFriendsList();
                    }
                }, 500);
                break;
        }
    }
    
    /**
     * Atualiza a lista de amigos após uma nova amizade
     */
    function refreshFriendsList() {
        // Aqui você pode implementar uma chamada AJAX para buscar a lista atualizada
        // Ou simplesmente recarregar a página após um atraso
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
    
    /**
     * Atualiza a lista de usuários para adicionar
     */
    function refreshAddFriendsList() {
        // Aqui você pode implementar uma chamada AJAX para buscar a lista atualizada
        // Ou simplesmente recarregar a página após um atraso
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
});

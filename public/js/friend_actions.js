document.addEventListener('DOMContentLoaded', function() {
    // Adicionar listeners para botões de amizade
    document.querySelectorAll('.friend-btn').forEach(btn => {
        if (!btn.classList.contains('already-friend')) {
            btn.addEventListener('click', handleFriendAction);
        }
    });
    
    // Adicionar listener para o formulário de busca por ID
    const userSearchForm = document.getElementById('user-search-form');
    if (userSearchForm) {
        userSearchForm.addEventListener('submit', handleUserSearch);
    }
    
    function handleFriendAction(e) {
        const btn = e.currentTarget;
        const action = btn.dataset.action;
        const userId = btn.dataset.userId;
        
        if (!action || !userId) return;
        
        btn.disabled = true;
        
        // Adicionar animação ao botão
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
        btn.classList.add('processing');
        
        const formData = new FormData();
        formData.append('action', action);
        formData.append('user_id', userId);
        
        fetch('/RMStream/api/friendship.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensagem de sucesso
                showToast(data.message || 'Ação realizada com sucesso!', 'success');
                
                // Atualizar UI de acordo com a ação
                if (action === 'send_request') {
                    animateButtonChange(btn, 'cancel-request', 'cancel_request', '<i class="fas fa-user-times"></i> Cancelar Solicitação');
                } else if (action === 'cancel_request' || action === 'reject_request') {
                    animateButtonChange(btn, 'add-friend', 'send_request', '<i class="fas fa-user-plus"></i> Adicionar Amigo');
                    
                    // Se for uma solicitação recebida, remover o card
                    const requestCard = btn.closest('.friend-request-card');
                    if (requestCard) {
                        fadeOutElement(requestCard);
                    }
                } else if (action === 'accept_request') {
                    // Transformar em botão de conversa
                    const userDiv = btn.closest('.btn-group') || btn.parentElement;
                    
                    if (userDiv) {
                        const conversationBtn = document.createElement('a');
                        conversationBtn.href = `/RMStream/views/chat/conversation.php?user=${userId}`;
                        conversationBtn.className = 'friend-btn already-friend animated-friend-btn';
                        conversationBtn.innerHTML = '<i class="fas fa-comments"></i> Conversar';
                        
                        // Aplicar animação
                        fadeOutElement(btn);
                        setTimeout(() => {
                            userDiv.innerHTML = '';
                            userDiv.appendChild(conversationBtn);
                            setTimeout(() => {
                                conversationBtn.classList.add('show');
                            }, 50);
                        }, 300);
                    }
                    
                    // Se for uma solicitação recebida, remover o card
                    const requestCard = btn.closest('.friend-request-card');
                    if (requestCard) {
                        fadeOutElement(requestCard);
                    }
                }
            } else {
                // Mostrar mensagem de erro
                showToast(data.message || 'Erro ao processar solicitação', 'error');
                btn.disabled = false;
                btn.classList.remove('processing');
                
                // Restaurar texto original
                if (action === 'send_request') {
                    btn.innerHTML = '<i class="fas fa-user-plus"></i> Adicionar Amigo';
                } else if (action === 'cancel_request') {
                    btn.innerHTML = '<i class="fas fa-user-times"></i> Cancelar Solicitação';
                } else if (action === 'accept_request') {
                    btn.innerHTML = '<i class="fas fa-check"></i> Aceitar';
                } else if (action === 'reject_request') {
                    btn.innerHTML = '<i class="fas fa-times"></i> Recusar';
                }
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showToast('Ocorreu um erro ao processar sua solicitação.', 'error');
            btn.disabled = false;
            btn.classList.remove('processing');
            
            // Restaurar texto original
            if (action === 'send_request') {
                btn.innerHTML = '<i class="fas fa-user-plus"></i> Adicionar Amigo';
            } else if (action === 'cancel_request') {
                btn.innerHTML = '<i class="fas fa-user-times"></i> Cancelar Solicitação';
            } else if (action === 'accept_request') {
                btn.innerHTML = '<i class="fas fa-check"></i> Aceitar';
            } else if (action === 'reject_request') {
                btn.innerHTML = '<i class="fas fa-times"></i> Recusar';
            }
        });
    }
    
    function handleUserSearch(e) {
        e.preventDefault();
        const userId = document.getElementById('user-id-search').value.trim();
        
        if (!userId) {
            showToast('Por favor, insira um ID de usuário válido', 'error');
            return;
        }
        
        // Mostrar indicador de carregamento
        const resultsContainer = document.getElementById('search-results');
        if (resultsContainer) {
            resultsContainer.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Procurando usuário...</div>';
        }
        
        // Buscar o usuário pelo ID
        fetch(`/RMStream/api/search_user.php?id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.user) {
                    // Mostrar resultado do usuário
                    if (resultsContainer) {
                        const user = data.user;
                        
                        // Definir o tipo de botão com base no status da amizade
                        let friendButton = '';
                        if (user.friendship_status === 'accepted') {
                            friendButton = `<a href="/RMStream/views/chat/conversation.php?user=${user.id}" class="friend-btn already-friend animated-friend-btn show">
                                <i class="fas fa-comments"></i> Conversar
                            </a>`;
                        } else if (user.friendship_status === 'pending_sent') {
                            friendButton = `<button class="friend-btn cancel-request" data-action="cancel_request" data-user-id="${user.id}">
                                <i class="fas fa-user-times"></i> Cancelar Solicitação
                            </button>`;
                        } else if (user.friendship_status === 'pending_received') {
                            friendButton = `<div class="btn-group">
                                <button class="friend-btn accept-request" data-action="accept_request" data-user-id="${user.id}">
                                    <i class="fas fa-check"></i> Aceitar
                                </button>
                                <button class="friend-btn reject-request" data-action="reject_request" data-user-id="${user.id}">
                                    <i class="fas fa-times"></i> Recusar
                                </button>
                            </div>`;
                        } else {
                            friendButton = `<button class="friend-btn add-friend" data-action="send_request" data-user-id="${user.id}">
                                <i class="fas fa-user-plus"></i> Adicionar Amigo
                            </button>`;
                        }
                        
                        resultsContainer.innerHTML = `
                            <div class="user-card">
                                <div class="user-card-header">
                                    <img src="${user.profile_image || '/RMStream/public/images/default-avatar.png'}" alt="Foto de perfil" class="user-avatar">
                                    <div class="user-info">
                                        <h3>${user.display_name || user.username}</h3>
                                        <p>@${user.username}</p>
                                        <p class="user-id">ID: ${user.id}</p>
                                    </div>
                                </div>
                                <div class="user-card-actions">
                                    <a href="/RMStream/views/profile/index.php?id=${user.id}" class="view-profile-btn">
                                        <i class="fas fa-user"></i> Ver Perfil
                                    </a>
                                    ${friendButton}
                                </div>
                            </div>
                        `;
                        
                        // Adicionar event listeners aos novos botões
                        const newFriendBtns = resultsContainer.querySelectorAll('.friend-btn:not(.already-friend)');
                        newFriendBtns.forEach(btn => {
                            btn.addEventListener('click', handleFriendAction);
                        });
                    }
                } else {
                    // Mostrar mensagem de erro
                    if (resultsContainer) {
                        resultsContainer.innerHTML = `<div class="not-found">
                            <i class="fas fa-user-slash"></i>
                            <p>${data.message || 'Usuário não encontrado'}</p>
                        </div>`;
                    }
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                if (resultsContainer) {
                    resultsContainer.innerHTML = `<div class="not-found error">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Ocorreu um erro ao buscar o usuário.</p>
                    </div>`;
                }
            });
    }
    
    // Função para animar a mudança de botão
    function animateButtonChange(button, newClass, newAction, newHTML) {
        button.classList.add('fade-out');
        setTimeout(() => {
            button.className = `friend-btn ${newClass}`;
            button.dataset.action = newAction;
            button.innerHTML = newHTML;
            button.classList.remove('fade-out', 'processing');
            button.classList.add('fade-in');
            button.disabled = false;
            setTimeout(() => {
                button.classList.remove('fade-in');
            }, 300);
        }, 300);
    }
    
    // Função para animar o desaparecimento de um elemento
    function fadeOutElement(element) {
        element.style.opacity = '0';
        element.style.transform = 'translateX(100px)';
        setTimeout(() => {
            element.remove();
        }, 300);
    }
    
    function showToast(message, type = 'info') {
        // Verificar se já existe um toast e removê-lo
        const existingToast = document.querySelector('.toast');
        if (existingToast) {
            existingToast.remove();
        }
        
        // Criar novo toast
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        // Adicionar à página
        document.body.appendChild(toast);
        
        // Mostrar com animação
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Remover após 3 segundos
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }
    
    // Função para abrir modal com animação
    function openModalWithAnimation(modalElement) {
        if (!modalElement) return;
        
        // Mostrar o modal
        modalElement.style.display = 'block';
        modalElement.classList.add('show');
        
        // Prevenir scrolling do body
        document.body.style.overflow = 'hidden';
    }

    // Função para fechar modal com animação
    function closeModalWithAnimation(modalElement) {
        if (!modalElement) return;
        
        // Adicionar classe de animação de saída
        modalElement.classList.add('closing');
        
        // Esperar a animação terminar e então esconder
        setTimeout(() => {
            modalElement.style.display = 'none';
            modalElement.classList.remove('show', 'closing');
            
            // Restaurar scrolling
            document.body.style.overflow = '';
        }, 300);
    }

    // Buscar todos os modais
    const modals = document.querySelectorAll('.modal');
    
    // Adicionar handlers para fechar modais com animação
    modals.forEach(modal => {
        // Botões de fechar
        const closeButtons = modal.querySelectorAll('.modal-close, .modal-close-btn');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                closeModalWithAnimation(modal);
            });
        });
        
        // Fechar ao clicar fora
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModalWithAnimation(modal);
            }
        });
    });
    
    // Fechar modais com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            modals.forEach(modal => {
                if (modal.style.display === 'block') {
                    closeModalWithAnimation(modal);
                }
            });
        }
    });
});

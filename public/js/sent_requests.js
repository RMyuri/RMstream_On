/**
 * Gerenciamento de solicitações de amizade enviadas
 */
document.addEventListener('DOMContentLoaded', function() {
    // Modal de solicitações enviadas
    const sentRequestsBtn = document.getElementById('sentRequestsBtn');
    const sentRequestsModal = document.getElementById('sentRequestsModal');
    const sentRequestsList = document.getElementById('sentRequestsList');
    
    if (sentRequestsBtn) {
        sentRequestsBtn.addEventListener('click', function() {
            openModalWithAnimation(sentRequestsModal);
            loadSentRequests();
        });
    }
    
    // Fechar modal
    document.querySelectorAll('#sentRequestsModal .modal-close, #sentRequestsModal .modal-close-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            closeModalWithAnimation(sentRequestsModal);
        });
    });
    
    // Fechar ao clicar fora
    window.addEventListener('click', function(e) {
        if (e.target === sentRequestsModal) {
            closeModalWithAnimation(sentRequestsModal);
        }
    });
    
    // Fechar com tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sentRequestsModal && sentRequestsModal.style.display === 'block') {
            closeModalWithAnimation(sentRequestsModal);
        }
    });
    
    // Carregar solicitações enviadas
    function loadSentRequests() {
        if (!sentRequestsList) return;
        
        sentRequestsList.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Carregando solicitações...</div>';
        
        fetch('/RMStream/api/sent_requests.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.requests && data.requests.length > 0) {
                    let html = '';
                    data.requests.forEach((request, index) => {
                        const date = new Date(request.created_at);
                        const formattedDate = date.toLocaleDateString('pt-BR') + ' às ' + date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
                        
                        html += `
                        <div class="request-sent-card" style="opacity: 0; transform: translateY(20px);">
                            <div class="request-sent-user">
                                <img src="${request.profile_image || '/RMStream/public/images/default-avatar.png'}" alt="Avatar" class="request-sent-avatar">
                                <div class="request-sent-info">
                                    <div class="request-sent-name">${request.display_name || request.username}</div>
                                    <div class="request-sent-username">@${request.username}</div>
                                    <div class="request-sent-time">Enviada em ${formattedDate}</div>
                                </div>
                            </div>
                            <div class="request-sent-actions">
                                <button class="friend-btn cancel-request" data-action="cancel_request" data-user-id="${request.friend_id}">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                            </div>
                        </div>`;
                    });
                    
                    sentRequestsList.innerHTML = html;
                    
                    // Animar entrada dos cards
                    const cards = sentRequestsList.querySelectorAll('.request-sent-card');
                    cards.forEach((card, index) => {
                        setTimeout(() => {
                            card.style.transition = 'all 0.3s ease';
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        }, 50 * index);
                    });
                    
                    // Adicionar listeners para botões de cancelar
                    sentRequestsList.querySelectorAll('.friend-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const userId = this.getAttribute('data-user-id');
                            const action = this.getAttribute('data-action');
                            const card = this.closest('.request-sent-card');
                            
                            handleFriendAction(this, userId, action, card);
                        });
                    });
                } else {
                    sentRequestsList.innerHTML = '<div class="no-requests-sent"><p>Você não enviou nenhuma solicitação de amizade.</p></div>';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                sentRequestsList.innerHTML = '<div class="no-requests-sent error"><p>Erro ao carregar solicitações. Tente novamente.</p></div>';
            });
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
    
    // Lidar com ações de amizade
    function handleFriendAction(buttonElement, userId, action, cardElement) {
        if (!buttonElement || !userId || !action) return;
        
        // Desabilitar o botão e mostrar indicador de carregamento
        buttonElement.disabled = true;
        const originalHtml = buttonElement.innerHTML;
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
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
                // Mostrar mensagem de sucesso
                if (cardElement) {
                    // Animar remoção do card
                    cardElement.style.opacity = '0';
                    cardElement.style.transform = 'translateX(100%)';
                    
                    setTimeout(() => {
                        cardElement.remove();
                        
                        // Verificar se há mais cards
                        const remainingCards = sentRequestsList.querySelectorAll('.request-sent-card');
                        if (remainingCards.length === 0) {
                            sentRequestsList.innerHTML = '<div class="no-requests-sent"><p>Você não enviou nenhuma solicitação de amizade.</p></div>';
                        }
                    }, 300);
                }
            } else {
                // Mostrar mensagem de erro
                alert(data.message || 'Erro ao processar solicitação');
                buttonElement.disabled = false;
                buttonElement.innerHTML = originalHtml;
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao processar sua solicitação.');
            buttonElement.disabled = false;
            buttonElement.innerHTML = originalHtml;
        });
    }
});

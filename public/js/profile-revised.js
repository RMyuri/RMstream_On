/**
 * JavaScript revisado para a página de perfil do RMStream
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elementos DOM
    const requestsBtn = document.getElementById('requestsBtn');
    const sentRequestsModal = document.getElementById('sentRequestsModal');
    const sentRequestsList = document.getElementById('sentRequestsList');
    const blockBtns = document.querySelectorAll('.block-btn');
    const reportBtns = document.querySelectorAll('.report-btn');
    const blockModal = document.getElementById('blockModal');
    const reportModal = document.getElementById('reportModal');
    const blockUsername = document.getElementById('blockUsername');
    const reportUsername = document.getElementById('reportUsername');
    const reportUserId = document.getElementById('reportUserId');
    const confirmBlockBtn = document.getElementById('confirmBlock');
    const reportForm = document.getElementById('reportForm');
    const modalCloseBtns = document.querySelectorAll('.modal-close, .modal-close-btn');
    
    // Inicialização
    initModals();
    initFriendActions();
    
    /**
     * Inicializa comportamentos de modais
     */
    function initModals() {
        // Botão de solicitações enviadas
        if (requestsBtn && sentRequestsModal) {
            requestsBtn.addEventListener('click', function() {
                openModal(sentRequestsModal);
                loadSentRequests();
            });
        }
        
        // Botões de fechar modais
        modalCloseBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if (modal) closeModal(modal);
            });
        });
        
        // Fechar ao clicar fora
        document.addEventListener('click', function(e) {
            document.querySelectorAll('.modal.show').forEach(modal => {
                if (e.target === modal) {
                    closeModal(modal);
                }
            });
        });
        
        // Fechar com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(modal => {
                    closeModal(modal);
                });
            }
        });
    }
    
    /**
     * Inicializa ações relacionadas a amigos
     */
    function initFriendActions() {
        // Botões de bloquear
        blockBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const username = this.getAttribute('data-username');
                
                if (blockUsername) blockUsername.textContent = username;
                if (confirmBlockBtn) confirmBlockBtn.setAttribute('data-user-id', userId);
                
                openModal(blockModal);
            });
        });
        
        // Botões de reportar
        reportBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const username = this.getAttribute('data-username');
                
                if (reportUsername) reportUsername.textContent = username;
                if (reportUserId) reportUserId.value = userId;
                
                openModal(reportModal);
            });
        });
        
        // Confirmação de bloqueio
        if (confirmBlockBtn) {
            confirmBlockBtn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                blockUser(userId);
            });
        }
        
        // Formulário de denúncia
        if (reportForm) {
            reportForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitReport(this);
            });
        }
    }
    
    /**
     * Abre um modal com animação
     */
    function openModal(modal) {
        if (!modal) return;
        
        // Reset do form se for o modal de report
        if (modal === reportModal && reportForm) {
            reportForm.reset();
        }
        
        modal.style.display = 'block';
        
        // Força um reflow para garantir que a animação funcione
        void modal.offsetWidth;
        
        modal.classList.add('show');
        document.body.style.overflow = 'hidden'; // Previne scroll
    }
    
    /**
     * Fecha um modal com animação
     */
    function closeModal(modal) {
        if (!modal) return;
        
        modal.classList.remove('show');
        
        // Aguardar a animação terminar
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = ''; // Restaura scroll
        }, 300);
    }
    
    /**
     * Carrega solicitações de amizade enviadas
     */
    function loadSentRequests() {
        if (!sentRequestsList) return;
        
        sentRequestsList.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i><p>Carregando solicitações...</p></div>';
        
        fetch('/RMStream/api/sent_requests.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.requests && data.requests.length > 0) {
                    let html = '';
                    
                    data.requests.forEach(request => {
                        const date = new Date(request.created_at);
                        const formattedDate = date.toLocaleDateString('pt-BR') + ' às ' + 
                                             date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
                        
                        html += `
                        <div class="request-card" data-request-id="${request.id}">
                            <div class="request-user">
                                <img src="${request.profile_image || '/RMStream/public/images/default-avatar.png'}" alt="Avatar" class="request-avatar">
                                <div class="request-info">
                                    <div class="request-name">${request.display_name || request.username}</div>
                                    <div class="request-username">@${request.username}</div>
                                    <div class="request-time">Enviada em ${formattedDate}</div>
                                </div>
                            </div>
                            <button class="cancel-request-btn" data-user-id="${request.friend_id}">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>`;
                    });
                    
                    sentRequestsList.innerHTML = html;
                    
                    // Adicionar listeners para botões de cancelar
                    sentRequestsList.querySelectorAll('.cancel-request-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const userId = this.getAttribute('data-user-id');
                            cancelRequest(userId, this.closest('.request-card'));
                        });
                    });
                    
                    // Animar entrada dos cards
                    animateItems(sentRequestsList.querySelectorAll('.request-card'));
                } else {
                    sentRequestsList.innerHTML = `
                    <div class="empty-requests">
                        <i class="fas fa-paper-plane"></i>
                        <p>Você não enviou nenhuma solicitação de amizade.</p>
                    </div>`;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                sentRequestsList.innerHTML = `
                <div class="request-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Erro ao carregar solicitações. Tente novamente.</p>
                </div>`;
            });
    }
    
    /**
     * Cancela uma solicitação de amizade
     */
    function cancelRequest(userId, cardElement) {
        if (!userId || !cardElement) return;
        
        const btn = cardElement.querySelector('.cancel-request-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
        
        const formData = new FormData();
        formData.append('action', 'cancel_request');
        formData.append('user_id', userId);
        
        fetch('/RMStream/api/friendship.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Animar remoção do card
                cardElement.style.opacity = '0';
                cardElement.style.transform = 'translateX(100%)';
                
                setTimeout(() => {
                    cardElement.remove();
                    
                    // Verificar se há mais cards
                    const remainingCards = sentRequestsList.querySelectorAll('.request-card');
                    if (remainingCards.length === 0) {
                        sentRequestsList.innerHTML = `
                        <div class="empty-requests">
                            <i class="fas fa-paper-plane"></i>
                            <p>Você não enviou nenhuma solicitação de amizade.</p>
                        </div>`;
                    }
                }, 300);
            } else {
                alert(data.message || 'Erro ao cancelar solicitação');
                
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-times"></i> Cancelar';
                }
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao processar sua solicitação.');
            
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-times"></i> Cancelar';
            }
        });
    }
    
    /**
     * Bloqueia um usuário
     */
    function blockUser(userId) {
        if (!userId) return;
        
        const btn = confirmBlockBtn;
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
        }
        
        const formData = new FormData();
        formData.append('action', 'block');
        formData.append('user_id', userId);
        
        fetch('/RMStream/api/user_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fechar modal
                closeModal(blockModal);
                
                // Recarregar página para atualizar lista de amigos
                setTimeout(() => {
                    window.location.reload();
                }, 300);
            } else {
                alert(data.message || 'Erro ao bloquear usuário');
                
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'Bloquear';
                }
                
                closeModal(blockModal);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao processar sua solicitação.');
            
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = 'Bloquear';
            }
            
            closeModal(blockModal);
        });
    }
    
    /**
     * Envia uma denúncia
     */
    function submitReport(form) {
        if (!form) return;
        
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        }
        
        const formData = new FormData(form);
        
        fetch('/RMStream/api/report_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensagem de sucesso
                form.innerHTML = `
                <div class="report-success">
                    <i class="fas fa-check-circle"></i>
                    <p>Denúncia enviada com sucesso! Nossa equipe irá analisar o caso em breve.</p>
                </div>`;
                
                // Fechar modal após alguns segundos
                setTimeout(() => {
                    closeModal(reportModal);
                    
                    // Reset do form após fechar
                    setTimeout(() => {
                        form.reset();
                        form.innerHTML = document.getElementById('reportForm').innerHTML;
                        initFriendActions(); // Reinicializar eventos
                    }, 300);
                }, 3000);
            } else {
                alert(data.message || 'Erro ao enviar denúncia');
                
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Enviar Denúncia';
                }
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao processar sua solicitação.');
            
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Enviar Denúncia';
            }
        });
    }
    
    /**
     * Anima a entrada de elementos na tela
     */
    function animateItems(items) {
        if (!items || !items.length) return;
        
        items.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                item.style.transition = 'all 0.3s ease';
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, 50 * index);
        });
    }
});

/**
 * JavaScript para a página principal de chat
 */
document.addEventListener('DOMContentLoaded', function() {
    // Elementos DOM
    const directChatList = document.getElementById('directChatList');
    const groupChatList = document.getElementById('groupChatList');
    const chatTabs = document.querySelectorAll('.chat-tab');
    const searchChatInput = document.getElementById('searchChat');
    const newChatBtn = document.getElementById('newChatBtn');
    const startNewChatBtn = document.getElementById('startNewChat');
    const newChatModal = document.getElementById('newChatModal');
    const friendList = document.getElementById('friendList');
    const searchFriendsInput = document.getElementById('searchFriends');
    const emptyState = document.getElementById('emptyState');
    
    // Inicialização
    initTabs();
    initSearch();
    initNewChatModal();
    initChatItems();
    
    /**
     * Inicializa as abas de chat (direto/grupos)
     */
    function initTabs() {
        chatTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remover active de todas as abas
                chatTabs.forEach(t => t.classList.remove('active'));
                
                // Adicionar active na aba atual
                this.classList.add('active');
                
                // Mostrar lista correspondente
                const tabType = this.getAttribute('data-tab');
                if (tabType === 'direct') {
                    directChatList.style.display = 'block';
                    groupChatList.style.display = 'none';
                } else {
                    directChatList.style.display = 'none';
                    groupChatList.style.display = 'block';
                }
            });
        });
    }
    
    /**
     * Inicializa a busca de conversas
     */
    function initSearch() {
        if (searchChatInput) {
            searchChatInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const activeTab = document.querySelector('.chat-tab.active').getAttribute('data-tab');
                const chatItems = activeTab === 'direct' ? 
                    directChatList.querySelectorAll('.chat-item') : 
                    groupChatList.querySelectorAll('.chat-item');
                
                chatItems.forEach(item => {
                    const chatName = item.querySelector('.chat-name').textContent.toLowerCase();
                    const lastMessage = item.querySelector('.chat-last-message').textContent.toLowerCase();
                    
                    if (chatName.includes(searchTerm) || lastMessage.includes(searchTerm)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Verificar se há resultados
                const visibleItems = Array.from(chatItems).filter(item => item.style.display !== 'none');
                
                if (visibleItems.length === 0) {
                    // Nenhum resultado
                    const emptyList = activeTab === 'direct' ? 
                        directChatList.querySelector('.empty-chat-list') : 
                        groupChatList.querySelector('.empty-chat-list');
                    
                    if (!emptyList) {
                        const emptyDiv = document.createElement('div');
                        emptyDiv.className = 'empty-chat-list search-empty';
                        emptyDiv.innerHTML = `<p>Nenhum resultado encontrado para "${searchTerm}"</p>`;
                        
                        if (activeTab === 'direct') {
                            directChatList.appendChild(emptyDiv);
                        } else {
                            groupChatList.appendChild(emptyDiv);
                        }
                    } else if (!emptyList.classList.contains('search-empty')) {
                        emptyList.style.display = 'none';
                        
                        const emptyDiv = document.createElement('div');
                        emptyDiv.className = 'empty-chat-list search-empty';
                        emptyDiv.innerHTML = `<p>Nenhum resultado encontrado para "${searchTerm}"</p>`;
                        
                        if (activeTab === 'direct') {
                            directChatList.appendChild(emptyDiv);
                        } else {
                            groupChatList.appendChild(emptyDiv);
                        }
                    }
                } else {
                    // Remover mensagem de "nenhum resultado"
                    const emptySearch = activeTab === 'direct' ? 
                        directChatList.querySelector('.search-empty') : 
                        groupChatList.querySelector('.search-empty');
                    
                    if (emptySearch) {
                        emptySearch.remove();
                    }
                    
                    // Mostrar mensagem original se não houver resultados
                    const emptyList = activeTab === 'direct' ? 
                        directChatList.querySelector('.empty-chat-list:not(.search-empty)') : 
                        groupChatList.querySelector('.empty-chat-list:not(.search-empty)');
                    
                    if (emptyList) {
                        emptyList.style.display = 'block';
                    }
                }
            });
        }
    }
    
    /**
     * Inicializa o modal de nova conversa
     */
    function initNewChatModal() {
        // Abrir modal
        if (newChatBtn) {
            newChatBtn.addEventListener('click', openNewChatModal);
        }
        
        if (startNewChatBtn) {
            startNewChatBtn.addEventListener('click', openNewChatModal);
        }
        
        // Fechar modal
        const closeButtons = document.querySelectorAll('.modal-close');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if (modal) {
                    closeModal(modal);
                }
            });
        });
        
        // Fechar ao clicar fora
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal(e.target);
            }
        });
        
        // Buscar amigos no modal
        if (searchFriendsInput) {
            searchFriendsInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const friendItems = friendList.querySelectorAll('.friend-item');
                
                friendItems.forEach(item => {
                    const friendName = item.querySelector('.friend-name').textContent.toLowerCase();
                    
                    if (friendName.includes(searchTerm)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Verificar se há resultados
                const visibleItems = Array.from(friendItems).filter(item => item.style.display !== 'none');
                
                if (visibleItems.length === 0 && friendItems.length > 0) {
                    // Nenhum resultado
                    const emptySearch = friendList.querySelector('.empty-search');
                    
                    if (!emptySearch) {
                        const emptyDiv = document.createElement('div');
                        emptyDiv.className = 'empty-search';
                        emptyDiv.innerHTML = `<p>Nenhum amigo encontrado para "${searchTerm}"</p>`;
                        friendList.appendChild(emptyDiv);
                    } else {
                        emptySearch.innerHTML = `<p>Nenhum amigo encontrado para "${searchTerm}"</p>`;
                        emptySearch.style.display = 'block';
                    }
                } else {
                    // Remover mensagem de "nenhum resultado"
                    const emptySearch = friendList.querySelector('.empty-search');
                    if (emptySearch) {
                        emptySearch.style.display = 'none';
                    }
                }
            });
        }
    }
    
    /**
     * Abre o modal de nova conversa
     */
    function openNewChatModal() {
        if (!newChatModal) return;
        
        // Carregar lista de amigos
        loadFriendsList();
        
        // Mostrar modal
        newChatModal.style.display = 'block';
        
        // Focar na busca
        if (searchFriendsInput) {
            searchFriendsInput.focus();
        }
    }
    
    /**
     * Fecha um modal
     */
    function closeModal(modal) {
        if (!modal) return;
        modal.style.display = 'none';
        
        // Limpar busca
        if (modal === newChatModal && searchFriendsInput) {
            searchFriendsInput.value = '';
            
            // Remover mensagem de "nenhum resultado"
            const emptySearch = friendList.querySelector('.empty-search');
            if (emptySearch) {
                emptySearch.style.display = 'none';
            }
            
            // Mostrar todos os amigos
            const friendItems = friendList.querySelectorAll('.friend-item');
            friendItems.forEach(item => {
                item.style.display = 'flex';
            });
        }
    }
    
    /**
     * Carrega a lista de amigos para o modal
     */
    function loadFriendsList() {
        if (!friendList) return;
        
        // Mostrar loading
        friendList.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Carregando amigos...</div>';
        
        // Buscar amigos da API
        fetch('/RMStream/api/get_friends.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.friends.length > 0) {
                    let html = '';
                    
                    data.friends.forEach(friend => {
                        html += `
                        <div class="friend-item" data-user-id="${friend.id}">
                            <img src="${friend.profile_image || '/RMStream/public/images/default-avatar.png'}" alt="Avatar" class="friend-avatar">
                            <div class="friend-info">
                                <div class="friend-name">${friend.display_name || friend.username}</div>
                            </div>
                        </div>`;
                    });
                    
                    friendList.innerHTML = html;
                    
                    // Adicionar event listeners
                    const friendItems = friendList.querySelectorAll('.friend-item');
                    friendItems.forEach(item => {
                        item.addEventListener('click', function() {
                            const userId = this.getAttribute('data-user-id');
                            if (userId) {
                                // Redirecionar para a conversa
                                window.location.href = `/RMStream/views/chat/conversation.php?user_id=${userId}`;
                            }
                        });
                    });
                } else {
                    friendList.innerHTML = `
                    <div class="empty-friends">
                        <p>Você ainda não tem amigos. Adicione amigos para conversar.</p>
                        <a href="/RMStream/views/chat/find_friends.php" class="add-friends-btn">
                            <i class="fas fa-user-plus"></i> Adicionar Amigos
                        </a>
                    </div>`;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                friendList.innerHTML = '<div class="error">Erro ao carregar amigos. Tente novamente.</div>';
            });
    }
    
    /**
     * Inicializa os itens de chat (cliques)
     */
    function initChatItems() {
        // Conversas diretas
        const directItems = directChatList.querySelectorAll('.chat-item');
        directItems.forEach(item => {
            item.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                if (userId) {
                    window.location.href = `/RMStream/views/chat/conversation.php?user_id=${userId}`;
                }
            });
        });
        
        // Grupos
        const groupItems = groupChatList.querySelectorAll('.chat-item');
        groupItems.forEach(item => {
            item.addEventListener('click', function() {
                const groupId = this.getAttribute('data-group-id');
                if (groupId) {
                    window.location.href = `/RMStream/views/chat/conversation.php?group_id=${groupId}`;
                }
            });
        });
    }
});

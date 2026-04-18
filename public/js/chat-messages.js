(function() {
    // Elementos do DOM
    const messageContainer = document.getElementById('messageContainer');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const fileInput = document.getElementById('fileInput');
    const attachmentBtn = document.getElementById('attachmentBtn');
    const emojiBtn = document.getElementById('emojiBtn');
    
    // Dados da conversa (definidos na página)
    const currentUser = conversationData.currentUser;
    const otherUser = conversationData.otherUser;
    const areFriends = conversationData.areFriends;
    
    // Variáveis para controle de polling
    let lastMessageId = getLastMessageId();
    let pollingInterval;
    
    // Função para obter o ID da última mensagem exibida
    function getLastMessageId() {
        const messages = document.querySelectorAll('.message');
        if (messages.length > 0) {
            const lastMessage = messages[messages.length - 1];
            return lastMessage.getAttribute('data-id') || 0;
        }
        return 0;
    }
    
    // Função para enviar mensagem
    async function sendMessage(content, attachment = null) {
        if (!content.trim() && !attachment) return;
        
        try {
            const formData = new FormData();
            formData.append('receiver_id', otherUser.id);
            formData.append('content', content);
            
            if (attachment) {
                formData.append('attachment', attachment);
            }
            
            const response = await fetch('/RMStream/api/send_message.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Limpar campo de entrada
                messageInput.value = '';
                messageInput.style.height = 'auto';
                
                // Adicionar mensagem à interface
                addMessageToUI({
                    id: data.message_id,
                    sender_id: currentUser.id,
                    content: content,
                    attachment: data.attachment_url || null,
                    created_at: new Date().toISOString()
                }, true);
                
                // Rolar para o final
                scrollToBottom();
            } else {
                console.error('Erro ao enviar mensagem:', data.message);
                alert('Erro ao enviar mensagem: ' + data.message);
            }
        } catch (error) {
            console.error('Erro ao enviar mensagem:', error);
            alert('Erro ao enviar mensagem. Tente novamente.');
        }
    }
    
    // Função para adicionar mensagem à interface
    function addMessageToUI(message, isNew = false) {
        const isOutgoing = message.sender_id == currentUser.id;
        
        const messageElement = document.createElement('div');
        messageElement.className = `message ${isOutgoing ? 'outgoing' : 'incoming'}`;
        messageElement.setAttribute('data-id', message.id);
        
        const avatar = isOutgoing ? currentUser.avatar : otherUser.avatar;
        
        messageElement.innerHTML = `
            <img src="${avatar}" alt="Avatar" class="message-avatar">
            <div class="message-content">
                <div class="message-text">${escapeHtml(message.content)}</div>
                ${message.attachment ? `<img src="${message.attachment}" alt="Anexo" class="message-attachment">` : ''}
                <div class="message-time">${formatTime(message.created_at)}</div>
            </div>
        `;
        
        // Adicionar classe para animação se for nova mensagem
        if (isNew) {
            messageElement.classList.add('message-new');
            setTimeout(() => {
                messageElement.classList.remove('message-new');
            }, 500);
        }
        
        messageContainer.appendChild(messageElement);
        
        // Atualizar o último ID de mensagem
        lastMessageId = message.id;
    }
    
    // Função para buscar novas mensagens
    async function fetchNewMessages() {
        try {
            const response = await fetch(`/RMStream/api/get_messages.php?user=${otherUser.id}&last_id=${lastMessageId}`);
            const data = await response.json();
            
            if (data.success && data.messages.length > 0) {
                // Adicionar novas mensagens à interface
                data.messages.forEach(message => {
                    addMessageToUI(message, true);
                });
                
                // Rolar para o final se a mensagem for recente
                const isRecent = (new Date() - new Date(data.messages[0].created_at)) < 60000; // 1 minuto
                if (isRecent) {
                    scrollToBottom();
                }
            }
        } catch (error) {
            console.error('Erro ao buscar novas mensagens:', error);
        }
    }
    
    // Iniciar polling para novas mensagens
    function startMessagePolling() {
        // Buscar mensagens a cada 5 segundos
        pollingInterval = setInterval(fetchNewMessages, 5000);
    }
    
    // Parar polling
    function stopMessagePolling() {
        clearInterval(pollingInterval);
    }
    
    // Função para rolar para o final da conversa
    function scrollToBottom() {
        messageContainer.scrollTop = messageContainer.scrollHeight;
    }
    
    // Funções auxiliares
    function formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Event listeners
    if (sendBtn && messageInput) {
        // Enviar ao clicar no botão
        sendBtn.addEventListener('click', () => {
            const content = messageInput.value.trim();
            sendMessage(content);
        });
        
        // Enviar ao pressionar Enter (sem Shift)
        messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const content = messageInput.value.trim();
                sendMessage(content);
            }
        });
        
        // Ajustar altura do textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
    
    // Upload de arquivo
    if (attachmentBtn && fileInput) {
        attachmentBtn.addEventListener('click', () => {
            fileInput.click();
        });
        
        fileInput.addEventListener('change', async () => {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                
                // Verificar tamanho (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    alert('O arquivo é muito grande. Tamanho máximo: 5MB');
                    return;
                }
                
                // Verificar tipo
                if (!file.type.startsWith('image/')) {
                    alert('Por favor, selecione uma imagem.');
                    return;
                }
                
                // Enviar mensagem com anexo
                sendMessage('', file);
                
                // Limpar input
                fileInput.value = '';
            }
        });
    }
    
    // Emoji picker
    if (emojiBtn) {
        emojiBtn.addEventListener('click', () => {
            // Implementação simplificada - apenas alguns emojis comuns
            const emojis = ['😊', '😂', '❤️', '👍', '🎉', '🔥', '😎', '🤔'];
            
            // Criar menu de emojis
            const emojiMenu = document.createElement('div');
            emojiMenu.className = 'emoji-menu';
            emojiMenu.style.cssText = `
                position: absolute;
                bottom: 50px;
                left: 10px;
                background: #333;
                border-radius: 8px;
                padding: 8px;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 100;
            `;
            
            // Adicionar emojis
            emojis.forEach(emoji => {
                const emojiButton = document.createElement('button');
                emojiButton.textContent = emoji;
                emojiButton.className = 'emoji-btn';
                emojiButton.style.cssText = `
                    background: transparent;
                    border: none;
                    font-size: 1.5em;
                    cursor: pointer;
                    width: 40px;
                    height: 40px;
                    border-radius: 5px;
                    transition: background 0.2s;
                `;
                
                emojiButton.addEventListener('mouseover', () => {
                    emojiButton.style.background = '#444';
                });
                
                emojiButton.addEventListener('mouseout', () => {
                    emojiButton.style.background = 'transparent';
                });
                
                emojiButton.addEventListener('click', () => {
                    // Inserir emoji no textarea
                    messageInput.value += emoji;
                    messageInput.focus();
                    emojiMenu.remove();
                });
                
                emojiMenu.appendChild(emojiButton);
            });
            
            // Adicionar menu à página
            document.querySelector('.chat-input-container').appendChild(emojiMenu);
            
            // Remover menu ao clicar fora
            document.addEventListener('click', function removeMenu(e) {
                if (!emojiMenu.contains(e.target) && e.target !== emojiBtn) {
                    emojiMenu.remove();
                    document.removeEventListener('click', removeMenu);
                }
            });
        });
    }
    
    // Rolar para o final ao carregar a página
    scrollToBottom();
    
    // Iniciar polling para novas mensagens
    startMessagePolling();
    
    // Parar polling ao sair da página
    window.addEventListener('beforeunload', stopMessagePolling);
})();

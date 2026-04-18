/**
 * Script para gerenciar a página de conversa do chat
 */
document.addEventListener('DOMContentLoaded', function() {
    // Elementos DOM
    const messagesContainer = document.getElementById('messagesContainer');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const attachmentBtn = document.getElementById('attachmentBtn');
    const fileInput = document.getElementById('fileInput');
    const attachmentPreview = document.getElementById('attachmentPreview');
    const attachmentPreviewContent = document.getElementById('attachmentPreviewContent');
    const attachmentPreviewRemove = document.getElementById('attachmentPreviewRemove');
    const emojiBtn = document.getElementById('emojiBtn');
    
    // Modais e painéis
    const groupInfoBtn = document.querySelector('.group-info-btn');
    const groupInfoPanel = document.getElementById('groupInfoPanel');
    const closeGroupInfoBtn = document.getElementById('closeGroupInfoBtn');
    const imageViewerModal = document.getElementById('imageViewerModal');
    const videoViewerModal = document.getElementById('videoViewerModal');
    const fileViewerModal = document.getElementById('fileViewerModal');
    
    // Inicializações
    let currentFile = null;
    let messagesOffset = 0;
    let allMessagesLoaded = false;
    let isLoadingMessages = false;
    
    // Inicializar
    initConversation();
    
    /**
     * Inicializa a conversa
     */
    function initConversation() {
        // Carregar mensagens iniciais
        loadMessages();
        
        // Event listeners
        initEventListeners();
    }
    
    /**
     * Inicializa os event listeners
     */
    function initEventListeners() {
        // Enviar mensagem ao clicar no botão
        if (sendBtn) {
            sendBtn.addEventListener('click', sendMessage);
        }
        
        // Enviar mensagem ao pressionar Enter (shift+enter para nova linha)
        if (messageInput) {
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
                
                // Auto-expandir textarea
                setTimeout(() => {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                }, 0);
            });
        }
        
        // Anexar arquivo
        if (attachmentBtn && fileInput) {
            attachmentBtn.addEventListener('click', function() {
                fileInput.click();
            });
            
            fileInput.addEventListener('change', handleFileSelection);
        }
        
        // Remover anexo
        if (attachmentPreviewRemove) {
            attachmentPreviewRemove.addEventListener('click', removeAttachment);
        }
        
        // Rolagem infinita para carregar mais mensagens
        if (messagesContainer) {
            messagesContainer.addEventListener('scroll', function() {
                if (messagesContainer.scrollTop === 0 && !allMessagesLoaded && !isLoadingMessages) {
                    loadMoreMessages();
                }
            });
        }
        
        // Painel de informações do grupo
        if (groupInfoBtn && groupInfoPanel) {
            groupInfoBtn.addEventListener('click', function() {
                groupInfoPanel.classList.add('open');
            });
        }
        
        if (closeGroupInfoBtn && groupInfoPanel) {
            closeGroupInfoBtn.addEventListener('click', function() {
                groupInfoPanel.classList.remove('open');
            });
        }
        
        // Visualizadores de mídia
        document.addEventListener('click', function(e) {
            // Verificar cliques em imagens de mensagens
            if (e.target.classList.contains('message-image')) {
                openImageViewer(e.target.src);
            }
            
            // Verificar cliques em vídeos de mensagens
            if (e.target.classList.contains('message-video-thumb')) {
                const videoUrl = e.target.getAttribute('data-video-url');
                openVideoViewer(videoUrl);
            }
            
            // Verificar cliques em arquivos de mensagens
            if (e.target.closest('.message-file')) {
                const fileElement = e.target.closest('.message-file');
                const fileUrl = fileElement.getAttribute('data-file-url');
                const fileName = fileElement.getAttribute('data-file-name');
                const fileSize = fileElement.getAttribute('data-file-size');
                openFileViewer(fileUrl, fileName, fileSize);
            }
        });
        
        // Fechar modais ao clicar no botão de fechar
        document.querySelectorAll('.modal-close').forEach(closeBtn => {
            closeBtn.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if (modal) modal.style.display = 'none';
                
                // Parar vídeo se estiver sendo reproduzido
                if (modal === videoViewerModal) {
                    const video = document.getElementById('videoViewerElement');
                    if (video) video.pause();
                }
            });
        });
        
        // Fechar modais ao clicar fora
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
                
                // Parar vídeo se estiver sendo reproduzido
                if (e.target === videoViewerModal) {
                    const video = document.getElementById('videoViewerElement');
                    if (video) video.pause();
                }
            }
        });
    }
    
    /**
     * Carrega as mensagens da conversa
     */
    function loadMessages() {
        isLoadingMessages = true;
        messagesContainer.innerHTML = '<div class="messages-loading"><div class="spinner"></div><p>Carregando mensagens...</p></div>';
        
        fetch(`/RMStream/api/messages.php?type=${conversationData.type}&id=${conversationData.id}&offset=${messagesOffset}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messagesContainer.innerHTML = '';
                    
                    if (data.messages.length === 0) {
                        messagesContainer.innerHTML = '<div class="no-messages">Nenhuma mensagem encontrada. Envie uma mensagem para iniciar a conversa.</div>';
                        allMessagesLoaded = true;
                    } else {
                        renderMessages(data.messages);
                        allMessagesLoaded = !data.has_more;
                        messagesOffset += data.messages.length;
                        
                        // Rolar para o final
                        setTimeout(() => {
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        }, 100);
                    }
                } else {
                    messagesContainer.innerHTML = `<div class="messages-error"><p>${data.message || 'Erro ao carregar mensagens'}</p></div>`;
                }
                
                isLoadingMessages = false;
            })
            .catch(error => {
                console.error('Erro:', error);
                messagesContainer.innerHTML = '<div class="messages-error"><p>Erro ao carregar mensagens. Tente novamente.</p></div>';
                isLoadingMessages = false;
            });
    }
    
    /**
     * Carrega mais mensagens (mensagens mais antigas)
     */
    function loadMoreMessages() {
        if (allMessagesLoaded || isLoadingMessages) return;
        
        isLoadingMessages = true;
        
        // Adicionar indicador de carregamento no topo
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'messages-loading-more';
        loadingIndicator.innerHTML = '<div class="spinner"></div>';
        messagesContainer.prepend(loadingIndicator);
        
        // Guardar altura atual para manter posição de scroll
        const currentHeight = messagesContainer.scrollHeight;
        
        fetch(`/RMStream/api/messages.php?type=${conversationData.type}&id=${conversationData.id}&offset=${messagesOffset}`)
            .then(response => response.json())
            .then(data => {
                // Remover indicador de carregamento
                loadingIndicator.remove();
                
                if (data.success) {
                    if (data.messages.length > 0) {
                        // Renderizar mensagens mais antigas no topo
                        renderMessagesAtTop(data.messages);
                        messagesOffset += data.messages.length;
                        
                        // Manter posição de scroll após adicionar novas mensagens
                        const newHeight = messagesContainer.scrollHeight;
                        messagesContainer.scrollTop = newHeight - currentHeight;
                    }
                    
                    allMessagesLoaded = !data.has_more;
                    
                    // Mostrar indicador de "todas as mensagens carregadas" se aplicável
                    if (allMessagesLoaded) {
                        const allLoadedIndicator = document.createElement('div');
                        allLoadedIndicator.className = 'all-messages-loaded';
                        allLoadedIndicator.textContent = 'Todas as mensagens carregadas';
                        messagesContainer.prepend(allLoadedIndicator);
                    }
                }
                
                isLoadingMessages = false;
            })
            .catch(error => {
                console.error('Erro:', error);
                loadingIndicator.remove();
                isLoadingMessages = false;
                
                // Mostrar erro temporariamente
                const errorIndicator = document.createElement('div');
                errorIndicator.className = 'messages-error-indicator';
                errorIndicator.textContent = 'Erro ao carregar mais mensagens';
                messagesContainer.prepend(errorIndicator);
                
                setTimeout(() => {
                    errorIndicator.remove();
                }, 3000);
            });
    }
    
    /**
     * Renderiza mensagens na conversa
     */
    function renderMessages(messages) {
        let currentDate = null;
        
        messages.forEach(message => {
            // Verificar se precisa mostrar data
            const messageDate = new Date(message.created_at).toLocaleDateString();
            if (messageDate !== currentDate) {
                currentDate = messageDate;
                const dateDiv = document.createElement('div');
                dateDiv.className = 'message-date-divider';
                dateDiv.textContent = formatDate(message.created_at);
                messagesContainer.appendChild(dateDiv);
            }
            
            // Criar elemento de mensagem
            const messageElement = createMessageElement(message);
            messagesContainer.appendChild(messageElement);
        });
    }
    
    /**
     * Renderiza mensagens no topo da conversa (mensagens mais antigas)
     */
    function renderMessagesAtTop(messages) {
        // Inserir na ordem inversa para manter cronologia
        let currentDate = null;
        const fragment = document.createDocumentFragment();
        
        for (let i = messages.length - 1; i >= 0; i--) {
            const message = messages[i];
            
            // Verificar se precisa mostrar data
            const messageDate = new Date(message.created_at).toLocaleDateString();
            if (messageDate !== currentDate) {
                currentDate = messageDate;
                const dateDiv = document.createElement('div');
                dateDiv.className = 'message-date-divider';
                dateDiv.textContent = formatDate(message.created_at);
                fragment.appendChild(dateDiv);
            }
            
            // Criar elemento de mensagem
            const messageElement = createMessageElement(message);
            fragment.appendChild(messageElement);
        }
        
        // Inserir no início do container
        if (messagesContainer.firstChild) {
            messagesContainer.insertBefore(fragment, messagesContainer.firstChild);
        } else {
            messagesContainer.appendChild(fragment);
        }
    }
    
    /**
     * Cria um elemento de mensagem
     */
    function createMessageElement(message) {
        const isCurrentUser = parseInt(message.sender_id) === conversationData.currentUser.id;
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isCurrentUser ? 'message-sent' : 'message-received'}`;
        messageDiv.setAttribute('data-message-id', message.id);
        
        let innerHtml = '';
        
        // Cabeçalho da mensagem (nome e avatar para mensagens recebidas)
        if (!isCurrentUser) {
            innerHtml += `
                <div class="message-header">
                    <img src="${message.profile_image || '/RMStream/public/images/default-avatar.png'}" alt="Avatar" class="message-avatar">
                    <span class="message-sender">${message.display_name || message.username}</span>
                </div>
            `;
        }
        
        // Conteúdo da mensagem
        innerHtml += '<div class="message-content">';
        
        // Tipo de mídia
        if (message.media_type === 'image') {
            innerHtml += `
                <div class="message-media">
                    <img src="${message.media_url}" alt="Imagem" class="message-image">
                </div>
            `;
        } else if (message.media_type === 'video') {
            innerHtml += `
                <div class="message-media">
                    <div class="message-video" data-video-url="${message.media_url}">
                        <img src="/RMStream/public/images/video-placeholder.png" alt="Vídeo" class="message-video-thumb" data-video-url="${message.media_url}">
                        <div class="message-video-play"><i class="fas fa-play"></i></div>
                    </div>
                </div>
            `;
        } else if (message.media_type === 'file') {
            const fileName = message.media_url.split('/').pop();
            const fileExt = fileName.split('.').pop().toUpperCase();
            
            innerHtml += `
                <div class="message-file" data-file-url="${message.media_url}" data-file-name="${fileName}" data-file-size="Desconhecido">
                    <div class="message-file-icon"><i class="fas fa-file"></i> ${fileExt}</div>
                    <div class="message-file-info">
                        <div class="message-file-name">${fileName}</div>
                        <div class="message-file-action"><i class="fas fa-download"></i> Baixar</div>
                    </div>
                </div>
            `;
        }
        
        // Texto da mensagem
        if (message.content) {
            innerHtml += `<div class="message-text">${formatMessageText(message.content)}</div>`;
        }
        
        innerHtml += '</div>';
        
        // Rodapé da mensagem (horário)
        innerHtml += `
            <div class="message-footer">
                <span class="message-time">${formatTime(message.created_at)}</span>
                ${isCurrentUser ? '<span class="message-status"><i class="fas fa-check"></i></span>' : ''}
            </div>
        `;
        
        messageDiv.innerHTML = innerHtml;
        return messageDiv;
    }
    
    /**
     * Envia uma mensagem
     */
    function sendMessage() {
        const content = messageInput.value.trim();
        
        // Verificar se há conteúdo ou arquivo
        if (!content && !currentFile) {
            return;
        }
        
        // Desabilitar botão durante o envio
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        // Preparar dados para envio
        const formData = new FormData();
        formData.append('type', conversationData.type);
        formData.append('id', conversationData.id);
        formData.append('content', content);
        
        if (currentFile) {
            formData.append('attachment', currentFile);
        }
        
        // Enviar mensagem
        fetch('/RMStream/api/messages.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Limpar campo de mensagem e anexo
                messageInput.value = '';
                messageInput.style.height = 'auto';
                removeAttachment();
                
                // Adicionar mensagem à conversa
                const messageElement = createMessageElement(data.messageData);
                messagesContainer.appendChild(messageElement);
                
                // Rolar para o final
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            } else {
                alert(data.message || 'Erro ao enviar mensagem');
            }
            
            // Restaurar botão
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao enviar a mensagem');
            
            // Restaurar botão
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        });
    }
    
    /**
     * Manipula a seleção de arquivo
     */
    function handleFileSelection(event) {
        const file = event.target.files[0];
        
        if (!file) {
            return;
        }
        
        // Verificar tamanho do arquivo (limite de 10MB)
        if (file.size > 10 * 1024 * 1024) {
            alert('Arquivo muito grande. O tamanho máximo é 10MB.');
            fileInput.value = '';
            return;
        }
        
        currentFile = file;
        
        // Mostrar prévia do arquivo
        attachmentPreview.style.display = 'flex';
        
        // Verificar tipo de arquivo
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                attachmentPreviewContent.innerHTML = `<img src="${e.target.result}" alt="Preview" class="attachment-image-preview">`;
            };
            reader.readAsDataURL(file);
        } else if (file.type.startsWith('video/')) {
            attachmentPreviewContent.innerHTML = `
                <div class="attachment-video-preview">
                    <i class="fas fa-video"></i>
                    <span>${file.name}</span>
                </div>
            `;
        } else {
            attachmentPreviewContent.innerHTML = `
                <div class="attachment-file-preview">
                    <i class="fas fa-file"></i>
                    <span>${file.name}</span>
                </div>
            `;
        }
    }
    
    /**
     * Remove o anexo selecionado
     */
    function removeAttachment() {
        currentFile = null;
        fileInput.value = '';
        attachmentPreview.style.display = 'none';
        attachmentPreviewContent.innerHTML = '';
    }
    
    /**
     * Abre o visualizador de imagens
     */
    function openImageViewer(imageUrl) {
        const imageViewerImg = document.getElementById('imageViewerImg');
        if (imageViewerImg) {
            imageViewerImg.src = imageUrl;
        }
        
        imageViewerModal.style.display = 'block';
    }
    
    /**
     * Abre o visualizador de vídeos
     */
    function openVideoViewer(videoUrl) {
        const videoElement = document.getElementById('videoViewerElement');
        if (videoElement) {
            const source = videoElement.querySelector('source');
            if (source) {
                source.src = videoUrl;
                videoElement.load();
            }
        }
        
        videoViewerModal.style.display = 'block';
    }
    
    /**
     * Abre o visualizador de arquivos
     */
    function openFileViewer(fileUrl, fileName, fileSize) {
        const fileNameElement = document.getElementById('fileViewerName');
        const fileSizeElement = document.getElementById('fileViewerSize');
        const fileDownloadLink = document.getElementById('fileViewerDownload');
        
        if (fileNameElement) fileNameElement.textContent = fileName;
        if (fileSizeElement) fileSizeElement.textContent = fileSize;
        if (fileDownloadLink) fileDownloadLink.href = fileUrl;
        
        fileViewerModal.style.display = 'block';
    }
    
    /**
     * Formata texto da mensagem (quebras de linha, links, etc.)
     */
    function formatMessageText(text) {
        // Converter quebras de linha
        text = text.replace(/\n/g, '<br>');
        
        // Converter URLs em links clicáveis
        text = text.replace(
            /(https?:\/\/[^\s]+)/g, 
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
        );
        
        return text;
    }
    
    /**
     * Formata data para exibição
     */
    function formatDate(dateString) {
        const date = new Date(dateString);
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        
        if (date.toDateString() === today.toDateString()) {
            return 'Hoje';
        } else if (date.toDateString() === yesterday.toDateString()) {
            return 'Ontem';
        } else {
            return date.toLocaleDateString('pt-BR', { 
                day: 'numeric', 
                month: 'long', 
                year: 'numeric' 
            });
        }
    }
    
    /**
     * Formata hora para exibição
     */
    function formatTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }
});

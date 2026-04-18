<?php
require_once '../../includes/config.php';

// Verificar se o usuário está logado
requireLogin();

$userId = $_SESSION['user_id'];
$conversationId = isset($_GET['user']) ? (int)$_GET['user'] : 0;

if (!$conversationId) {
    header('Location: ' . SITE_URL . '/views/chat/index.php');
    exit;
}

// Verificar se o usuário existe e é amigo
try {
    $pdo = getDbConnection();
    
    // Buscar dados do outro usuário
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.display_name, u.profile_image, u.email, f.status
        FROM users u
        LEFT JOIN friendships f ON 
            ((f.user_id = :userId AND f.friend_id = u.id) OR (f.friend_id = :userId AND f.user_id = u.id))
        WHERE u.id = :conversationId
    ");
    $stmt->bindParam(':userId', $userId);
    $stmt->bindParam(':conversationId', $conversationId);
    $stmt->execute();
    
    $otherUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$otherUser) {
        header('Location: ' . SITE_URL . '/views/chat/index.php');
        exit;
    }
    
    // Verificar se são amigos
    $areFriends = ($otherUser['status'] === 'accepted');
    
    // Marcar mensagens como lidas
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE sender_id = :conversationId AND receiver_id = :userId AND is_read = 0
    ");
    $stmt->bindParam(':conversationId', $conversationId);
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();
    
    // Buscar mensagens anteriores
    $stmt = $pdo->prepare("
        SELECT m.id, m.sender_id, m.content, m.attachment, m.created_at, 
               u.username, u.display_name, u.profile_image
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = :userId AND m.receiver_id = :conversationId) 
           OR (m.sender_id = :conversationId AND m.receiver_id = :userId)
        ORDER BY m.created_at
        LIMIT 50
    ");
    $stmt->bindParam(':userId', $userId);
    $stmt->bindParam(':conversationId', $conversationId);
    $stmt->execute();
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar amigos para a barra lateral
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.display_name, u.profile_image, f.status
        FROM friendships f
        JOIN users u ON (f.user_id = u.id AND f.user_id != :userId) OR (f.friend_id = u.id AND f.friend_id != :userId)
        WHERE (f.user_id = :userId OR f.friend_id = :userId) AND f.status = 'accepted'
        ORDER BY u.display_name, u.username
    ");
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar mensagens recentes para cada amigo
    foreach ($friends as &$friend) {
        $stmt = $pdo->prepare("
            SELECT content, created_at, sender_id, is_read
            FROM messages
            WHERE (sender_id = :userId AND receiver_id = :friendId) OR (sender_id = :friendId AND receiver_id = :userId)
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->bindParam(':userId', $userId);
        $stmt->bindParam(':friendId', $friend['id']);
        $stmt->execute();
        $lastMessage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastMessage) {
            $friend['last_message'] = $lastMessage['content'];
            $friend['last_message_time'] = $lastMessage['created_at'];
            $friend['unread'] = ($lastMessage['sender_id'] != $userId && !$lastMessage['is_read']) ? true : false;
        } else {
            $friend['last_message'] = 'Nenhuma mensagem ainda';
            $friend['last_message_time'] = null;
            $friend['unread'] = false;
        }
    }
    
    // Ordenar por mensagem mais recente
    usort($friends, function($a, $b) {
        if (empty($a['last_message_time']) && empty($b['last_message_time'])) return 0;
        if (empty($a['last_message_time'])) return 1;
        if (empty($b['last_message_time'])) return -1;
        return strtotime($b['last_message_time']) - strtotime($a['last_message_time']);
    });
    
} catch (PDOException $e) {
    $error = 'Erro ao carregar conversa: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Chat com <?php echo htmlspecialchars($otherUser['display_name'] ?? $otherUser['username']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/RMStream/public/css/style.css">
    <link rel="stylesheet" href="/RMStream/public/css/nav.css">
    <link rel="stylesheet" href="/RMStream/public/css/chat.css">
    <link rel="stylesheet" href="/RMStream/public/css/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav>
        <a href="/RMStream/views/index.php" class="logo">RMStream</a>
        <div class="nav-container">
            <div class="nav-item">
                <a href="/RMStream/views/index.php" class="nav-link">Início</a>
            </div>
            <div class="nav-item nav-item-with-submenu">
                <a href="/RMStream/views/room.php" class="nav-link">Room</a>
                <div class="submenu">
                    <a href="#" class="submenu-item create-room">Criar Sala</a>
                    <a href="#" class="submenu-item join-room">Entrar em Sala</a>
                </div>
            </div>
            <div class="nav-item">
                <a href="/RMStream/views/chart.php" class="nav-link">Chart</a>
            </div>
            <div class="nav-item">
                <a href="/RMStream/views/chat/index.php" class="nav-link active">Chat</a>
            </div>
            <div class="nav-item">
                <a href="/RMStream/views/profile/index.php" class="nav-link">Perfil</a>
            </div>
        </div>
        <div class="nav-profile">
            <a href="/RMStream/views/profile/index.php">
                <img src="<?php echo isset($_SESSION['profile_image']) && $_SESSION['profile_image'] ? $_SESSION['profile_image'] : '/RMStream/public/images/default-avatar.png'; ?>" 
                     alt="Avatar" class="profile-avatar">
            </a>
            <a href="/RMStream/views/auth/logout.php" class="nav-link">Sair</a>
        </div>
    </nav>
    
    <div class="chat-container">
        <!-- Barra lateral com lista de chats -->
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <h2 class="sidebar-title">Conversas</h2>
                <div class="sidebar-actions">
                    <a href="/RMStream/views/chat/find_friends.php" class="find-friends-btn" title="Adicionar Amigos">
                        <i class="fas fa-user-plus"></i>
                    </a>
                    <button class="new-chat-btn" id="newChatBtn" title="Nova conversa">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            
            <div class="chat-tabs">
                <div class="chat-tab active" data-tab="direct">Direto</div>
                <div class="chat-tab" data-tab="groups">Grupos</div>
            </div>
            
            <!-- Botão principal para adicionar usuários -->
            <a href="/RMStream/views/chat/find_friends.php" class="add-users-btn">
                <i class="fas fa-user-plus"></i> Adicionar Usuários
            </a>
            
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Buscar conversas..." id="searchChat">
            </div>
            
            <div class="chat-list" id="directChatList">
                <?php if (empty($friends)): ?>
                    <div class="empty-chat-list">
                        <p>Nenhum amigo encontrado. Adicione amigos para começar a conversar.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($friends as $friend): ?>
                        <div class="chat-item <?php echo $friend['id'] == $conversationId ? 'active' : ''; ?>" data-user-id="<?php echo $friend['id']; ?>">
                            <img src="<?php echo $friend['profile_image'] ?? '/RMStream/public/images/default-avatar.png'; ?>" alt="Avatar" class="chat-avatar">
                            <div class="chat-info">
                                <div class="chat-name"><?php echo htmlspecialchars($friend['display_name'] ?? $friend['username']); ?></div>
                                <div class="chat-last-message"><?php echo htmlspecialchars(substr($friend['last_message'], 0, 40) . (strlen($friend['last_message']) > 40 ? '...' : '')); ?></div>
                            </div>
                            <div class="chat-meta">
                                <?php if ($friend['last_message_time']): ?>
                                    <span class="chat-time"><?php echo date('H:i', strtotime($friend['last_message_time'])); ?></span>
                                <?php endif; ?>
                                <?php if ($friend['unread']): ?>
                                    <span class="chat-badge">1</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="chat-list" id="groupChatList" style="display: none;">
                <div class="empty-chat-list">
                    <p>Nenhum grupo encontrado. Crie ou participe de um grupo para começar.</p>
                    <button class="create-group-btn">Criar novo grupo</button>
                </div>
            </div>
        </div>
        
        <!-- Área principal de chat -->
        <div class="chat-main">
            <!-- Cabeçalho da conversa -->
            <div class="chat-header">
                <div class="chat-header-info">
                    <img src="<?php echo $otherUser['profile_image'] ?? '/RMStream/public/images/default-avatar.png'; ?>" 
                         alt="Avatar" class="chat-header-avatar">
                    <div>
                        <div class="chat-header-name"><?php echo htmlspecialchars($otherUser['display_name'] ?? $otherUser['username']); ?></div>
                        <div class="chat-header-status">
                            <?php if ($areFriends): ?>
                                <span class="status-online">● Online</span>
                            <?php else: ?>
                                <span class="status-offline">○ Não é seu amigo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="chat-actions">
                    <?php if ($areFriends): ?>
                        <button class="chat-action-btn call-audio" id="audioCallBtn" title="Chamada de áudio">
                            <i class="fas fa-phone"></i>
                        </button>
                        <button class="chat-action-btn call-video" id="videoCallBtn" title="Chamada de vídeo">
                            <i class="fas fa-video"></i>
                        </button>
                    <?php endif; ?>
                    <a href="/RMStream/views/profile/index.php?id=<?php echo $otherUser['id']; ?>" class="chat-action-btn" title="Ver perfil">
                        <i class="fas fa-user"></i>
                    </a>
                </div>
            </div>
            
            <!-- Mensagens -->
            <div class="chat-messages" id="messageContainer">
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?php echo $message['sender_id'] == $userId ? 'outgoing' : 'incoming'; ?>" data-id="<?php echo $message['id']; ?>">
                            <img src="<?php echo $message['profile_image'] ?? '/RMStream/public/images/default-avatar.png'; ?>" 
                                 alt="Avatar" class="message-avatar">
                            <div class="message-content">
                                <div class="message-text"><?php echo htmlspecialchars($message['content']); ?></div>
                                <?php if ($message['attachment']): ?>
                                    <img src="<?php echo htmlspecialchars($message['attachment']); ?>" alt="Anexo" class="message-attachment">
                                <?php endif; ?>
                                <div class="message-time"><?php echo date('H:i', strtotime($message['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-messages">
                        <p>Nenhuma mensagem ainda. Diga olá!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Formulário de entrada -->
            <div class="chat-input-container">
                <div class="chat-input-wrapper">
                    <textarea id="messageInput" class="chat-input" placeholder="Digite uma mensagem..." rows="1"></textarea>
                    <div class="chat-toolbar">
                        <button class="toolbar-btn" id="emojiBtn" title="Emoji">
                            <i class="far fa-smile"></i>
                        </button>
                        <button class="toolbar-btn" id="attachmentBtn" title="Anexar arquivo">
                            <i class="fas fa-paperclip"></i>
                        </button>
                    </div>
                </div>
                <button id="sendBtn" class="send-btn" title="Enviar">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
        
        <!-- Painel de informações do grupo (inicialmente oculto) -->
        <?php if ($isGroup): ?>
        <div class="group-info-panel" id="groupInfoPanel">
            <div class="group-info-header">
                <h3>Informações do Grupo</h3>
                <button class="close-panel-btn" id="closeGroupInfoBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="group-info-content">
                <div class="group-info-avatar">
                    <img src="<?php echo $chat['avatar'] ?? '/RMStream/public/images/default-group.png'; ?>" alt="Group Avatar">
                </div>
                
                <h2 class="group-info-name"><?php echo htmlspecialchars($chat['name']); ?></h2>
                
                <div class="group-info-description">
                    <?php if (!empty($chat['description'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($chat['description'])); ?></p>
                    <?php else: ?>
                        <p class="no-description">Sem descrição</p>
                    <?php endif; ?>
                </div>
                
                <div class="group-info-created">
                    Criado em <?php echo date('d/m/Y', strtotime($chat['created_at'])); ?>
                </div>
                
                <div class="group-members-section">
                    <h3>Membros (<?php echo count($members); ?>)</h3>
                    <div class="group-members-list">
                        <?php foreach ($members as $member): ?>
                            <div class="group-member">
                                <img src="<?php echo $member['profile_image'] ?? '/RMStream/public/images/default-avatar.png'; ?>" alt="Avatar" class="member-avatar">
                                <div class="member-info">
                                    <div class="member-name"><?php echo htmlspecialchars($member['display_name'] ?? $member['username']); ?></div>
                                    <div class="member-role"><?php echo $member['role'] === 'admin' ? 'Administrador' : 'Membro'; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if ($chat['role'] === 'admin'): ?>
                    <div class="group-admin-actions">
                        <button class="add-member-btn" id="addMemberBtn">
                            <i class="fas fa-user-plus"></i> Adicionar membros
                        </button>
                        <button class="edit-group-btn" id="editGroupBtn">
                            <i class="fas fa-edit"></i> Editar grupo
                        </button>
                        <button class="leave-group-btn danger" id="leaveGroupBtn">
                            <i class="fas fa-sign-out-alt"></i> Sair do grupo
                        </button>
                    </div>
                <?php else: ?>
                    <div class="group-member-actions">
                        <button class="leave-group-btn danger" id="leaveGroupBtn">
                            <i class="fas fa-sign-out-alt"></i> Sair do grupo
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Modal de confirmação para sair do grupo -->
        <div id="leaveGroupModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Sair do grupo</h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja sair do grupo <strong><?php echo htmlspecialchars($chat['name'] ?? ''); ?></strong>?</p>
                    <div class="modal-actions">
                        <button id="confirmLeaveBtn" class="danger-btn">Sim, sair do grupo</button>
                        <button class="cancel-btn modal-close-btn">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal para adicionar membros ao grupo -->
        <div id="addMemberModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Adicionar membros</h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="search-box">
                        <input type="text" id="searchFriendForGroup" placeholder="Buscar amigos...">
                    </div>
                    <div class="friends-list-for-group" id="friendsListForGroup">
                        <!-- Lista de amigos será carregada aqui -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal para visualizar imagem -->
        <div id="imageViewerModal" class="modal image-viewer-modal">
            <div class="modal-content image-viewer-content">
                <button class="modal-close">&times;</button>
                <img src="" alt="Image Preview" id="imageViewerImg">
            </div>
        </div>
        
        <!-- Modal para visualizar vídeo -->
        <div id="videoViewerModal" class="modal video-viewer-modal">
            <div class="modal-content video-viewer-content">
                <button class="modal-close">&times;</button>
                <video controls id="videoViewerElement">
                    <source src="" type="video/mp4">
                    Seu navegador não suporta a reprodução de vídeos.
                </video>
            </div>
        </div>
        
        <!-- Modal para visualizar arquivo -->
        <div id="fileViewerModal" class="modal file-viewer-modal">
            <div class="modal-content file-viewer-content">
                <button class="modal-close">&times;</button>
                <div class="file-info">
                    <i class="fas fa-file-alt file-icon"></i>
                    <h3 class="file-name" id="fileViewerName"></h3>
                    <p class="file-size" id="fileViewerSize"></p>
                </div>
                <div class="file-actions">
                    <a href="" id="fileViewerDownload" class="file-download-btn" download>
                        <i class="fas fa-download"></i> Baixar arquivo
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="/RMStream/public/js/nav.js"></script>
    <script src="/RMStream/public/js/notifications.js"></script>
    <script src="/RMStream/public/js/chat-conversation.js"></script>
    <script src="/RMStream/public/js/chat-realtime.js"></script>
</body>
</html>
                name: "<?php echo htmlspecialchars($_SESSION['username']); ?>",
                avatar: "<?php echo isset($_SESSION['profile_image']) && $_SESSION['profile_image'] ? $_SESSION['profile_image'] : '/RMStream/public/images/default-avatar.png'; ?>"
            }
        };
    </script>
    <script src="/RMStream/public/js/chat-conversation.js"></script>
</body>
</html>
